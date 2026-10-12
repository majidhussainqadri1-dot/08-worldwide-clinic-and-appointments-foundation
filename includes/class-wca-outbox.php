<?php
/**
 * Reliable transactional outbox and scheduled maintenance.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Outbox {
	const CRON_HOOK      = 'wca_process_outbox';
	const MAINTENANCE_HOOK = 'wca_maintenance';
	const MAX_ATTEMPTS   = 8;
	const BATCH_SIZE     = 20;

	public static function hooks() {
		add_action( self::CRON_HOOK, array( __CLASS__, 'cron_process' ) );
		add_action( self::MAINTENANCE_HOOK, array( __CLASS__, 'cron_maintenance' ) );
		add_action( 'shutdown', array( __CLASS__, 'opportunistic_process' ), 99 );
	}

	public static function schedule() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + 60, 'wca_every_five_minutes', self::CRON_HOOK );
		}
		if ( ! wp_next_scheduled( self::MAINTENANCE_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::MAINTENANCE_HOOK );
		}
	}

	public static function unschedule() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
		wp_clear_scheduled_hook( self::MAINTENANCE_HOOK );
	}

	public static function cron_schedules( $schedules ) {
		$schedules['wca_every_five_minutes'] = array(
			'interval' => 5 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every five minutes (File 08)', 'worldwide-clinic-appointments' ),
		);
		return $schedules;
	}

	public static function cron_process() {
		$result = self::process( self::BATCH_SIZE );
		if ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'outbox_cron_failed', array( 'error_code' => $result->get_error_code() ) ); }
		return $result;
	}

	public static function cron_maintenance() {
		$result = self::maintenance();
		if ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'maintenance_cron_failed', array( 'error_code' => $result->get_error_code() ) ); }
		return $result;
	}

	public static function opportunistic_process() {
		if ( wp_doing_cron() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( get_transient( 'wca_outbox_opportunistic_lock' ) ) {
			return;
		}
		set_transient( 'wca_outbox_opportunistic_lock', 1, MINUTE_IN_SECONDS );
		$result = self::process( 5 );
		if ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'outbox_opportunistic_failed', array( 'error_code' => $result->get_error_code() ) ); }
		return $result;
	}

	public static function process( $limit = self::BATCH_SIZE ) {
		global $wpdb;
		/* Repository claim selection predates row-level fencing. Serialize the dispatcher
		 * with a MySQL advisory lock so cron, shutdown, and overlapping workers cannot
		 * claim/finalize the same outbox item. MySQL releases this lock on connection loss. */
		$lock_name = 'wca-file08-outbox-dispatch';
		$locked_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $lock_name ) );
		if ( null === $locked_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_outbox_lock_read_failed', __( 'The outbox dispatcher lock could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$locked = (int) $locked_raw;
		if ( 1 !== $locked ) {
			WCA_Observability::metric( 'outbox_worker_contention_total', 1 );
			return 0;
		}
		try {
			$recovered = WCA_Repository::recover_stale_outbox( 300 );
			if ( is_wp_error( $recovered ) ) { return $recovered; }
			if ( $recovered > 0 ) { WCA_Observability::metric( 'outbox_stale_recovered_total', $recovered ); }
			$worker = 'wp-' . substr( hash( 'sha256', wp_generate_uuid4() ), 0, 16 );
			$items  = WCA_Repository::claim_outbox( min( 100, max( 1, absint( $limit ) ) ), $worker );
			if ( is_wp_error( $items ) ) { return $items; }
			foreach ( $items as $item ) {
				$attempts = absint( $item['attempts'] ?? 0 );
				try {
					$payload = isset( $item['payload'] ) && is_array( $item['payload'] ) ? $item['payload'] : json_decode( (string) ( $item['payload_json'] ?? '' ), true );
					if ( ! is_array( $payload ) ) {
						throw new RuntimeException( 'Invalid outbox payload.' );
					}
					$result = self::dispatch( (string) $item['message_id'], (string) $item['topic'], (string) $item['aggregate_ref'], $payload, (string) $item['trace_id'] );
					if ( is_wp_error( $result ) || true !== $result ) {
						throw new RuntimeException( is_wp_error( $result ) ? $result->get_error_message() : 'Outbox consumer did not positively acknowledge the event.' );
					}
					if ( ! WCA_Repository::complete_outbox( absint( $item['id'] ), $worker ) ) {
						throw new RuntimeException( 'Outbox delivery completed externally but durable worker-fenced finalization failed.' );
					}
					WCA_Observability::metric( 'outbox_delivered_total', 1, array( 'topic' => self::metric_topic( $item['topic'] ) ) );
				} catch ( Throwable $error ) {
					$failed = WCA_Repository::fail_outbox( absint( $item['id'] ), $error->getMessage(), $attempts, $worker );
					if ( ! $failed ) { WCA_Observability::metric( 'outbox_finalize_contention_total', 1 ); }
					WCA_Observability::log( 'error', 'outbox_delivery_failed', array(
						'topic'       => self::metric_topic( $item['topic'] ),
						'aggregate'   => (string) $item['aggregate_ref'],
						'attempts'    => $attempts,
						'dead_letter' => ( $attempts + 1 ) >= self::MAX_ATTEMPTS,
						'trace_id'    => (string) $item['trace_id'],
					) );
				}
			}
			return count( $items );
		} finally {
			$released_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
			if ( 1 !== (int) $released_raw ) { WCA_Observability::metric( 'outbox_lock_release_failed_total', 1 ); WCA_Observability::log( 'error', 'outbox_lock_release_failed', array( 'db_error' => '' !== (string) $wpdb->last_error ) ); }
		}
	}

	private static function dispatch( $message_id, $topic, $aggregate_ref, $payload, $trace_id ) {
		$envelope = array(
			'message_id'    => sanitize_text_field( $message_id ),
			'topic'         => $topic,
			'aggregate_ref' => $aggregate_ref,
			'payload'       => $payload,
			'trace_id'      => $trace_id,
			'contract'      => WCA_Contracts::FILE19_EVENT_CONTRACT_VERSION,
			'occurred_at'   => gmdate( 'c' ),
		);

		/**
		 * Canonical integration point. Consumers must be idempotent and reject
		 * incompatible contract versions. File 08 never writes another module's tables.
		 */
		do_action( 'wca_outbox_event', $envelope );
		do_action( 'wca_outbox_event_' . sanitize_key( str_replace( '.', '_', $topic ) ), $envelope );

		if ( 'File19.NotificationRequested.v1' === $topic ) {
			return self::dispatch_notification( $payload, $trace_id, $message_id );
		}
		if ( 0 === strpos( $topic, 'File17.' ) ) {
			return self::require_owner_receipt( 'file17', 'wca_file17_dispatch_result', $envelope );
		}
		if ( 0 === strpos( $topic, 'File26.' ) ) {
			return self::require_owner_receipt( 'file26', 'wca_file26_dispatch_result', $envelope );
		}
		if ( 0 === strpos( $topic, 'CF03.' ) ) {
			return self::require_owner_receipt( 'cf03', 'wca_cf03_dispatch_result', $envelope );
		}
		if ( 0 === strpos( $topic, 'CF02.' ) ) {
			return self::require_owner_receipt( 'cf02', 'wca_cf02_dispatch_result', $envelope );
		}
		return apply_filters( 'wca_outbox_dispatch_result', true, $envelope );
	}

	/**
	 * External canonical owners must durably acknowledge consumption. No handler,
	 * false, null or malformed receipt keeps the outbox item retryable rather
	 * than silently marking File17/File26/CF02/CF03 work delivered.
	 *
	 * Consumers return literal true only after durable, idempotent acceptance
	 * of envelope.message_id. File 08 never writes another owner's tables.
	 */
	private static function require_owner_receipt( $owner, $hook, $envelope ) {
		$result = apply_filters( $hook, false, $envelope );
		if ( true === $result ) { return true; }
		if ( is_wp_error( $result ) ) { return $result; }
		return new WP_Error( 'wca_outbox_owner_not_acknowledged', __( 'The external owner has not confirmed durable event acceptance; delivery will be retried.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'owner' => sanitize_key( $owner ) ) );
	}

	/**
	 * Hand notification facts to File 19's current event/producer API.
	 *
	 * File 19 is the notification delivery/preferences owner; never bypass it
	 * through an unconsented direct wp_mail fallback. Our outbox message ID is
	 * the durable idempotency identity across dispatcher retries.
	 */
	private static function dispatch_notification( $payload, $trace_id, $message_id ) {
		$provider = 'file19';
		$recipients = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $payload['recipients'] ?? array() ) ) ) ) );
		if ( empty( $recipients ) ) { return true; }
		if ( WCA_Observability::circuit_open( $provider ) ) {
			return new WP_Error( 'wca_provider_circuit_open', __( 'Notification service is temporarily unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'retry_after' => 60 ) );
		}
		if ( ! function_exists( 'sun_register_notification_producer' ) || ! function_exists( 'sun_ingest_domain_event' ) ) {
			WCA_Observability::circuit_failure( $provider, 'file19_contract_unavailable' );
			return new WP_Error( 'wca_file19_contract_unavailable', __( 'The canonical notification service is unavailable; delivery will be retried.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		$message_id = sanitize_text_field( (string) $message_id );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{1,128}$/', $message_id ) ) {
			return new WP_Error( 'wca_notification_message_id_invalid', __( 'The notification identifier is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$registered = sun_register_notification_producer( 'file08', array(
			'owner'               => 'File 08',
			'event_types'         => array( 'Clinic.AppointmentUpdated' ),
			'schema_versions'     => array( '1.0.0' ),
			'allowed_data_fields' => array( 'action_name', 'summary' ),
		) );
		if ( true !== $registered ) {
			WCA_Observability::circuit_failure( $provider, 'file19_producer_registration_failed' );
			return new WP_Error( 'wca_file19_producer_unavailable', __( 'Notification producer registration failed; delivery will be retried.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		$event = array(
			'producer'       => 'file08',
			'owner'          => 'File 08',
			'event_id'       => 'wca-notification-' . $message_id,
			'event_type'     => 'Clinic.AppointmentUpdated',
			'schema_version' => '1.0.0',
			'occurred_at'    => gmdate( 'c' ),
			'recipients'    => $recipients,
			'trace_id'      => sanitize_text_field( (string) $trace_id ),
			'category'      => 'clinic',
			'priority'      => 'normal',
			'sensitivity'   => 'sensitive',
			'source_version'=> WCA_VERSION,
			'data'          => array(
				'action_name' => __( 'Clinic appointment update', 'worldwide-clinic-appointments' ),
				'summary'     => __( 'Sign in to the platform to view your appointment update securely.', 'worldwide-clinic-appointments' ),
			),
		);
		$result = sun_ingest_domain_event( $event );
		if ( is_wp_error( $result ) || ! is_array( $result ) || ! in_array( (string) ( $result['status'] ?? '' ), array( 'processed', 'duplicate' ), true ) ) {
			WCA_Observability::circuit_failure( $provider, 'file19_ingest_failed' );
			return new WP_Error( 'wca_file19_delivery', __( 'The notification service did not confirm durable acceptance; delivery will be retried.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		WCA_Observability::circuit_success( $provider );
		return true;
	}

	public static function maintenance() {
		$errors = array();
		$expired = WCA_Repository::expire_slot_holds();
		if ( false === $expired ) {
			$errors[] = 'slot_hold_expiry';
			WCA_Observability::log( 'error', 'slot_hold_expiry_failed', array() );
		}
		$retention = WCA_Privacy::apply_retention();
		if ( is_wp_error( $retention ) ) {
			$errors[] = $retention->get_error_code();
		}
		$processed = self::process( self::BATCH_SIZE );
		if ( is_wp_error( $processed ) ) { $errors[] = $processed->get_error_code(); WCA_Observability::log( 'error', 'outbox_process_failed', array( 'error_code' => $processed->get_error_code() ) ); }
		WCA_Observability::health();
		if ( $errors ) {
			WCA_Observability::metric( 'maintenance_failure_total', 1, array( 'scope' => 'outbox' ) );
			return new WP_Error( 'wca_maintenance_incomplete', __( 'One or more maintenance operations could not be completed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'operations' => $errors ) );
		}
		return true;
	}

	private static function metric_topic( $topic ) {
		return substr( sanitize_key( str_replace( '.', '_', (string) $topic ) ), 0, 80 );
	}
}
