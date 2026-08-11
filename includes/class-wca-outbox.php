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
		add_action( self::CRON_HOOK, array( __CLASS__, 'process' ) );
		add_action( self::MAINTENANCE_HOOK, array( __CLASS__, 'maintenance' ) );
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

	public static function opportunistic_process() {
		if ( wp_doing_cron() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( get_transient( 'wca_outbox_opportunistic_lock' ) ) {
			return;
		}
		set_transient( 'wca_outbox_opportunistic_lock', 1, MINUTE_IN_SECONDS );
		self::process( 5 );
	}

	public static function process( $limit = self::BATCH_SIZE ) {
		global $wpdb;
		/* Repository claim selection predates row-level fencing. Serialize the dispatcher
		 * with a MySQL advisory lock so cron, shutdown, and overlapping workers cannot
		 * claim/finalize the same outbox item. MySQL releases this lock on connection loss. */
		$lock_name = 'wca-file08-outbox-dispatch';
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,0)', $lock_name ) );
		if ( 1 !== $locked ) {
			WCA_Observability::metric( 'outbox_worker_contention_total', 1 );
			return 0;
		}
		try {
			$recovered = WCA_Repository::recover_stale_outbox( 300 );
			if ( $recovered > 0 ) { WCA_Observability::metric( 'outbox_stale_recovered_total', $recovered ); }
			$worker = 'wp-' . substr( hash( 'sha256', wp_generate_uuid4() ), 0, 16 );
			$items  = WCA_Repository::claim_outbox( min( 100, max( 1, absint( $limit ) ) ), $worker );
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
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
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
			return self::dispatch_notification( $payload, $trace_id );
		}
		if ( 0 === strpos( $topic, 'CF03.' ) ) {
			return apply_filters( 'wca_cf03_dispatch_result', true, $envelope );
		}
		if ( 0 === strpos( $topic, 'CF02.' ) ) {
			return apply_filters( 'wca_cf02_dispatch_result', true, $envelope );
		}
		return apply_filters( 'wca_outbox_dispatch_result', true, $envelope );
	}

	private static function dispatch_notification( $payload, $trace_id ) {
		if ( function_exists( 'sn_notify_users' ) ) {
			$result = sn_notify_users( array_map( 'absint', (array) ( $payload['recipients'] ?? array() ) ), sanitize_key( $payload['event'] ?? 'clinic_update' ), array(
				'appointment_ref' => sanitize_text_field( $payload['appointment_ref'] ?? '' ),
				'trace_id'        => $trace_id,
			) );
			return false === $result ? new WP_Error( 'wca_file19_delivery', 'File 19 rejected the notification.' ) : true;
		}

		// Privacy-minimal fallback: no clinical reason, note, phone, or appointment time.
		$subject = __( 'Clinic appointment update', 'worldwide-clinic-appointments' );
		$message = __( 'There is an update to your clinic appointment. Sign in to the platform to view it securely.', 'worldwide-clinic-appointments' );
		$sent    = false;
		foreach ( array_unique( array_filter( array_map( 'absint', (array) ( $payload['recipients'] ?? array() ) ) ) ) as $user_id ) {
			$user = get_userdata( $user_id );
			if ( $user && is_email( $user->user_email ) ) {
				$sent = wp_mail( $user->user_email, $subject, $message ) || $sent;
			}
		}
		return $sent || empty( $payload['recipients'] ) ? true : new WP_Error( 'wca_mail_delivery', 'Notification fallback delivery failed.' );
	}

	public static function maintenance() {
		WCA_Repository::expire_slot_holds();
		WCA_Privacy::apply_retention();
		self::process( self::BATCH_SIZE );
		WCA_Observability::health();
	}

	private static function metric_topic( $topic ) {
		return substr( sanitize_key( str_replace( '.', '_', (string) $topic ) ), 0, 80 );
	}
}
