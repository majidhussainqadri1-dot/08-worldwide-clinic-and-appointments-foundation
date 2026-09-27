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
					if ( is_wp_error( $result ) ) {
						throw new RuntimeException( $result->get_error_message() );
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
			return self::dispatch_notification( $payload, $trace_id, $message_id, $aggregate_ref );
		}
		if ( 0 === strpos( $topic, 'CF03.' ) ) {
			return apply_filters( 'wca_cf03_dispatch_result', true, $envelope );
		}
		if ( 0 === strpos( $topic, 'CF02.' ) ) {
			return apply_filters( 'wca_cf02_dispatch_result', true, $envelope );
		}
		return apply_filters( 'wca_outbox_dispatch_result', true, $envelope );
	}

	private static function dispatch_notification( $payload, $trace_id, $message_id, $aggregate_ref ) {
		if ( ! function_exists( 'sun_ingest_domain_event' ) || ! function_exists( 'sun_register_notification_producer' ) ) {
			return new WP_Error( 'wca_file19_unavailable', __( 'The canonical File 19 notification service is unavailable.', 'worldwide-clinic-appointments' ), array( 'status'=>503, 'retry_after'=>60 ) );
		}
		WCA_Plugin::register_file19_producer();
		$recipients = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $payload['recipients'] ?? array() ) ) ) ) );
		if ( empty( $recipients ) ) { return true; }
		$event_key = sanitize_key( (string) ( $payload['event'] ?? 'clinic_update' ) );
		$event_type = self::file19_event_type( $event_key );
		$appointment_ref = sanitize_text_field( (string) ( $payload['appointment_ref'] ?? $aggregate_ref ) );
		$event = array(
			'producer' => 'file08-clinic',
			'owner' => 'File 08',
			'event_id' => 'wca-notification:' . substr( preg_replace( '/[^A-Za-z0-9._:\-]/', '', (string) $message_id ), 0, 160 ),
			'event_type' => $event_type,
			'schema_version' => '1.0',
			'occurred_at' => gmdate( DATE_ATOM ),
			'recipients' => array_map( static function ( $user_id ) { return array( 'user_id'=>(int)$user_id ); }, $recipients ),
			'trace_id' => substr( preg_replace( '/[^A-Za-z0-9._:\-]/', '', (string) $trace_id ), 0, 100 ),
			'category' => 'clinic',
			'priority' => self::file19_priority( $event_key ),
			'sensitivity' => 'sensitive',
			'subject' => array( 'type'=>'appointment', 'public_id'=>$appointment_ref ),
			'data' => array(
				'action_name' => __( 'Clinic appointment update', 'worldwide-clinic-appointments' ),
				'summary' => __( 'There is an update to your clinic appointment. Sign in to review it securely.', 'worldwide-clinic-appointments' ),
				'status' => $event_key,
			),
		);
		$result = sun_ingest_domain_event( $event );
		if ( is_wp_error( $result ) ) {
			WCA_Observability::circuit_failure( 'file19', $result->get_error_code() );
			return new WP_Error( 'wca_file19_delivery', __( 'File 19 rejected or could not persist the clinic notification event.', 'worldwide-clinic-appointments' ), array( 'status'=>503, 'source_error'=>$result->get_error_code() ) );
		}
		WCA_Observability::circuit_success( 'file19' );
		return true;
	}

	private static function file19_event_type( $event_key ) {
		$map = array(
			'appointment_requested' => 'Clinic.AppointmentRequested',
			'appointmentconfirmed_v1' => 'Clinic.AppointmentConfirmed',
			'appointmentdeclined_v1' => 'Clinic.AppointmentDeclined',
			'appointmentrescheduleproposed_v1' => 'Clinic.AppointmentRescheduleProposed',
			'appointmentcheckedin_v1' => 'Clinic.AppointmentCheckedIn',
			'appointmentcompleted_v1' => 'Clinic.AppointmentCompleted',
			'appointmentcancelled_v1' => 'Clinic.AppointmentCancelled',
			'appointmentnoshow_v1' => 'Clinic.AppointmentNoShow',
			'appointmentchanged_v1' => 'Clinic.AppointmentChanged',
			'complaint_submitted' => 'Clinic.AppointmentComplaintSubmitted',
			'doctor_authority_hold' => 'Clinic.DoctorAuthorityHoldApplied',
		);
		return $map[ sanitize_key( (string) $event_key ) ] ?? 'Clinic.AppointmentChanged';
	}

	private static function file19_priority( $event_key ) {
		$event_key = sanitize_key( (string) $event_key );
		return in_array( $event_key, array( 'appointmentcancelled_v1','appointmentdeclined_v1','doctor_authority_hold' ), true ) ? 'high' : 'normal';
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
