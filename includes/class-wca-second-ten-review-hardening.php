<?php
/**
 * Second fresh ten-round corrective hardening for File 08.
 *
 * This class closes cross-cutting REST boundaries found after the first ten-round
 * closure. Domain authorization and persistence remain with their native owners.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Second_Ten_Review_Hardening {
	const CONTRACT_VERSION = '1.0.0';

	public static function boot() {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'pre_dispatch' ), 5, 3 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'post_dispatch' ), 80, 3 );
	}

	/**
	 * Reject ambiguous stale mutation replays before the legacy HTTP idempotency
	 * guard can reclaim them, and reject impossible Future24 calendar values.
	 */
	public static function pre_dispatch( $result, $server, $request ) {
		if ( null !== $result || ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}
		$route  = (string) $request->get_route();
		$method = strtoupper( (string) $request->get_method() );
		if ( 0 !== strpos( $route, '/wca/v1/' ) ) {
			return $result;
		}

		if ( 0 === strpos( $route, '/wca/v1/future24/' ) && in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			$valid = self::validate_calendar_payload( (array) $request->get_params() );
			if ( is_wp_error( $valid ) ) { return $valid; }
		}

		if ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			return $result;
		}
		$actor = absint( get_current_user_id() );
		$key   = trim( (string) $request->get_header( 'Idempotency-Key' ) );
		$data  = (array) $request->get_params();
		if ( ! $key && ! empty( $data['idempotency_key'] ) ) {
			$key = trim( (string) $data['idempotency_key'] );
		}
		if ( ! $actor || ! $key ) { return $result; }

		global $wpdb;
		$table = WCA_Schema::tables()['idempotency'];
		$scope = 'tenreview_' . substr( hash( 'sha256', $route ), 0, 24 );
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id,status,updated_at FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1",
				$scope,
				hash( 'sha256', $key ),
				$actor
			),
			ARRAY_A
		);
		if ( $row && 'processing' === (string) $row['status'] && strtotime( (string) $row['updated_at'] . ' UTC' ) <= time() - 2 * MINUTE_IN_SECONDS ) {
			WCA_Observability::metric( 'idempotency_stale_processing_blocked_total', 1, array( 'scope' => $scope ) );
			return new WP_Error(
				'wca_idempotency_in_progress',
				__( 'This mutation has an ambiguous in-flight reservation and cannot be replayed automatically.', 'worldwide-clinic-appointments' ),
				array( 'status' => 409, 'reconciliation_required' => true )
			);
		}
		return $result;
	}

	/** Strip native numeric identifiers from every Future24 REST response. */
	public static function post_dispatch( $response, $server, $request ) {
		if ( ! ( $request instanceof WP_REST_Request ) || 0 !== strpos( (string) $request->get_route(), '/wca/v1/future24/' ) ) {
			return $response;
		}
		if ( is_wp_error( $response ) ) { return $response; }
		$response = $response instanceof WP_REST_Response ? $response : rest_ensure_response( $response );
		if ( ! ( $response instanceof WP_REST_Response ) ) { return $response; }
		$response->set_data( self::public_payload( $response->get_data() ) );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		return $response;
	}

	/** @return true|WP_Error */
	private static function validate_calendar_payload( $value, $depth = 0 ) {
		if ( $depth > 7 || ! is_array( $value ) ) { return true; }
		foreach ( $value as $key => $item ) {
			$key_string = is_string( $key ) ? sanitize_key( $key ) : '';
			if ( is_array( $item ) ) {
				$nested = self::validate_calendar_payload( $item, $depth + 1 );
				if ( is_wp_error( $nested ) ) { return $nested; }
				continue;
			}
			if ( ! is_scalar( $item ) || '' === trim( (string) $item ) ) { continue; }
			$text = trim( (string) $item );
			if ( self::is_date_key( $key_string ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $text ) && ! WCA_Service::valid_date( $text ) ) {
				return new WP_Error( 'wca_future24_date_invalid', __( 'A Future24 calendar date is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400, 'field' => $key_string ) );
			}
			if ( self::is_time_key( $key_string ) && ! self::strict_utc( $text ) ) {
				return new WP_Error( 'wca_future24_time_invalid', __( 'A Future24 calendar timestamp is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400, 'field' => $key_string ) );
			}
		}
		return true;
	}

	private static function is_date_key( $key ) {
		return (bool) preg_match( '/(^|_)(date|date_from|date_to|preferred_date|effective_from|effective_until)$/', (string) $key );
	}

	private static function is_time_key( $key ) {
		return (bool) preg_match( '/(^|_)(start_utc|end_utc|starts_at_utc|ends_at_utc|scheduled_at_utc|expires_at_utc|available_at_utc|arrival_at_utc)$/', (string) $key );
	}

	private static function strict_utc( $value ) {
		$utc = new DateTimeZone( 'UTC' );
		$formats = array(
			array( '!Y-m-d H:i:s', 'Y-m-d H:i:s' ),
			array( '!Y-m-d H:i', 'Y-m-d H:i' ),
			array( '!Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s\Z' ),
			array( '!Y-m-d\TH:i\Z', 'Y-m-d\TH:i\Z' ),
		);
		foreach ( $formats as $entry ) {
			$dt = DateTimeImmutable::createFromFormat( $entry[0], $value, $utc );
			$errors = DateTimeImmutable::getLastErrors();
			if ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) {
				return $dt->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
			}
		}
		foreach ( array( array( '!Y-m-d\TH:i:sP', 'Y-m-d\TH:i:sP' ), array( '!Y-m-d\TH:iP', 'Y-m-d\TH:iP' ) ) as $entry ) {
			$dt = DateTimeImmutable::createFromFormat( $entry[0], $value );
			$errors = DateTimeImmutable::getLastErrors();
			if ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) {
				return $dt->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
			}
		}
		return null;
	}

	private static function public_payload( $value, $depth = 0 ) {
		if ( $depth > 8 || ! is_array( $value ) ) { return $value; }
		$blocked = array( 'id', 'appointment_id', 'clinic_id', 'service_id', 'branch_id', 'patient_id', 'patient_user_id', 'doctor_id', 'doctor_user_id', 'guardian_user_id', 'subject_user_id', 'actor_user_id', 'owner_user_id', 'native_user_id' );
		$out = array();
		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) && in_array( sanitize_key( $key ), $blocked, true ) ) { continue; }
			$out[ $key ] = is_array( $item ) ? self::public_payload( $item, $depth + 1 ) : $item;
		}
		return $out;
	}
}
