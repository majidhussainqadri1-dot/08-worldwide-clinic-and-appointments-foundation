<?php
/**
 * Privacy-safe logs, traces, metrics, health, alerts, and circuit breakers.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Observability {
	private static $trace_id = '';

	public static function hooks() {
		add_action( 'send_headers', array( __CLASS__, 'trace_header' ) );
		add_action( 'wca_daily_health_snapshot', array( __CLASS__, 'capture_health_snapshot' ) );
		if ( ! wp_next_scheduled( 'wca_daily_health_snapshot' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'wca_daily_health_snapshot' );
		}
	}

	public static function trace_id() {
		if ( self::$trace_id ) {
			return self::$trace_id;
		}
		$incoming = isset( $_SERVER['HTTP_X_REQUEST_ID'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REQUEST_ID'] ) ) : '';
		self::$trace_id = preg_match( '/^[A-Za-z0-9._:-]{8,80}$/', $incoming ) ? $incoming : WCA_Repository::uuid();
		return self::$trace_id;
	}

	public static function trace_header() {
		if ( ! headers_sent() ) {
			header( 'X-Request-ID: ' . self::trace_id(), true );
		}
	}

	/** @return array<string,mixed> */
	public static function redact( $context ) {
		$blocked = array(
			'password', 'pass', 'secret', 'token', 'authorization', 'cookie', 'nonce', 'api_key', 'key',
			'phone', 'whatsapp', 'email', 'address_private', 'reason', 'summary', 'note', 'clinical',
			'patient_message', 'doctor_private_note', 'evidence', 'attachment', 'consent_evidence',
		);
		$out = array();
		foreach ( (array) $context as $key => $value ) {
			$key_string = strtolower( (string) $key );
			$deny = false;
			foreach ( $blocked as $needle ) {
				if ( false !== strpos( $key_string, $needle ) ) {
					$deny = true;
					break;
				}
			}
			if ( $deny ) {
				$out[ $key ] = '[REDACTED]';
			} elseif ( is_array( $value ) ) {
				$out[ $key ] = self::redact( $value );
			} elseif ( is_scalar( $value ) || null === $value ) {
				$out[ $key ] = is_string( $value ) ? substr( $value, 0, 500 ) : $value;
			}
		}
		return $out;
	}

	public static function log( $level, $event, $context = array() ) {
		$level = in_array( $level, array( 'debug', 'info', 'notice', 'warning', 'error', 'critical' ), true ) ? $level : 'info';
		$record = array(
			'timestamp_utc' => gmdate( 'c' ),
			'level'         => $level,
			'event'         => sanitize_key( $event ),
			'trace_id'      => self::trace_id(),
			'context'       => self::redact( (array) $context ),
		);
		error_log( '[WCA] ' . wp_json_encode( $record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		do_action( 'wca_structured_log', $record );
		if ( in_array( $level, array( 'error', 'critical' ), true ) ) {
			self::metric( 'errors_total', 1, array( 'event' => $record['event'], 'level' => $level ) );
		}
		return $record;
	}

	public static function metric( $key, $value = 1, $dimensions = array() ) {
		if ( class_exists( 'WCA_Repository' ) && class_exists( 'WCA_Schema' ) ) {
			return WCA_Repository::metric( $key, $value, $dimensions );
		}
		return false;
	}

	/** @return array<string,mixed> */
	public static function health() {
		$checks = array(
			'runtime_version' => defined( 'WCA_VERSION' ) ? WCA_VERSION : '',
			'schema'          => class_exists( 'WCA_Schema' ) ? WCA_Schema::health() : array(),
			'dependencies'    => class_exists( 'SWC_Activator' ) ? SWC_Activator::dependencies_ready() : false,
			'legacy_checks'   => class_exists( 'SWC_Activator' ) ? SWC_Activator::system_checks() : array(),
			'cron'            => array(
				'outbox'      => (bool) wp_next_scheduled( WCA_Outbox::CRON_HOOK ),
				'maintenance' => (bool) wp_next_scheduled( WCA_Outbox::MAINTENANCE_HOOK ),
			),
			'circuit_breakers' => (array) get_option( 'wca_circuit_breakers', array() ),
			'trace_id'         => self::trace_id(),
			'generated_at_utc' => gmdate( 'c' ),
		);
		$checks['ok'] = self::all_true( $checks['schema'] ) && (bool) $checks['dependencies'];
		return $checks;
	}

	private static function all_true( $values ) {
		foreach ( (array) $values as $value ) {
			if ( is_bool( $value ) && ! $value ) {
				return false;
			}
		}
		return true;
	}

	public static function capture_health_snapshot() {
		$health = self::health();
		update_option( 'wca_health_snapshot', $health, false );
		self::metric( 'health_snapshot', ! empty( $health['ok'] ) ? 1 : 0 );
		if ( empty( $health['ok'] ) ) {
			self::log( 'error', 'health_snapshot_failed', $health );
		}
	}

	public static function circuit_open( $provider ) {
		$all = (array) get_option( 'wca_circuit_breakers', array() );
		$key = sanitize_key( $provider );
		if ( empty( $all[ $key ]['open_until'] ) ) {
			return false;
		}
		return strtotime( $all[ $key ]['open_until'] . ' UTC' ) > time();
	}

	public static function circuit_failure( $provider, $error = '' ) {
		$all = (array) get_option( 'wca_circuit_breakers', array() );
		$key = sanitize_key( $provider );
		$state = (array) ( $all[ $key ] ?? array( 'failures' => 0 ) );
		$state['failures']   = absint( $state['failures'] ?? 0 ) + 1;
		$state['last_error'] = substr( sanitize_text_field( $error ), 0, 300 );
		$state['updated_at'] = WCA_Repository::now();
		if ( $state['failures'] >= 5 ) {
			$state['open_until'] = gmdate( 'Y-m-d H:i:s', time() + min( HOUR_IN_SECONDS, 60 * (int) pow( 2, min( 6, $state['failures'] - 5 ) ) ) );
		}
		$all[ $key ] = $state;
		$written = SWC_Helpers::update_option_strict( 'wca_circuit_breakers', $all, 'wca_circuit_state_write' );
		self::log( 'warning', 'provider_failure', array( 'provider' => $key, 'failures' => $state['failures'] ) );
		if ( is_wp_error( $written ) ) { self::log( 'error', 'provider_circuit_state_persistence_failed', array( 'provider' => $key ) ); return $written; }
		return true;
	}

	public static function circuit_success( $provider ) {
		$all = (array) get_option( 'wca_circuit_breakers', array() );
		$key = sanitize_key( $provider );
		if ( isset( $all[ $key ] ) ) {
			unset( $all[ $key ] );
			$written = SWC_Helpers::update_option_strict( 'wca_circuit_breakers', $all, 'wca_circuit_state_clear' );
			if ( is_wp_error( $written ) ) { self::log( 'error', 'provider_circuit_state_persistence_failed', array( 'provider' => $key ) ); return $written; }
		}
		return true;
	}
}
