<?php
/**
 * Short-lived signed calendar links for private appointment export.
 *
 * Browser navigation cannot reliably attach a REST nonce. This module issues
 * a short-lived bearer signature bound to the opaque appointment reference and
 * current participant. Exported ICS contains scheduling facts only.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Calendar_Link {
	const CONTRACT_VERSION = '1.1.0';
	const TTL = 600;

	public static function boot() { add_action( 'rest_api_init', array( __CLASS__, 'register_route' ), 70 ); }

	public static function register_route() {
		register_rest_route( 'wca/v1', '/calendar-links/(?P<ref>[0-9a-fA-F-]{36})', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'signer' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/calendar-links/(?P<ref>[0-9a-fA-F-]{36})\.ics', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'download' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( 'wca/v1', '/calendar-provider-webhooks/(?P<provider>[a-z0-9_-]{2,60})', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'provider_webhook' ),
			'permission_callback' => '__return_true',
		) );
	}

	public static function provider_webhook( WP_REST_Request $request ) {
		$provider = sanitize_key( (string) $request['provider'] );
		$allowed = apply_filters( 'wca_calendar_provider_ids', array() );
		$allowed = is_array( $allowed ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $allowed ) ) ) ) : array();
		if ( ! in_array( $provider, $allowed, true ) ) { return new WP_Error( 'wca_calendar_provider_unavailable', __( 'Calendar provider adapter is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$raw = (string) $request->get_body();
		if ( '' === $raw || strlen( $raw ) > 65536 ) { return new WP_Error( 'wca_calendar_webhook_payload', __( 'Calendar provider payload is empty or too large.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$verified = apply_filters( 'wca_calendar_provider_verify_webhook', null, $provider, $request, $raw );
		if ( is_wp_error( $verified ) ) { return $verified; }
		if ( ! is_array( $verified ) || true !== ( $verified['verified'] ?? false ) ) { return new WP_Error( 'wca_calendar_webhook_signature', __( 'Calendar provider signature could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$event_id = sanitize_text_field( (string) ( $verified['event_id'] ?? '' ) );
		$occurred_at = sanitize_text_field( (string) ( $verified['occurred_at'] ?? '' ) );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $event_id ) || false === self::strict_utc_timestamp( $occurred_at ) || abs( time() - self::strict_utc_timestamp( $occurred_at ) ) > 900 ) { return new WP_Error( 'wca_calendar_webhook_replay_window', __( 'Calendar provider event identity or timestamp is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$doctor_id = absint( $verified['doctor_user_id'] ?? 0 );
		if ( ! $doctor_id || ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) { return new WP_Error( 'wca_calendar_webhook_doctor', __( 'Calendar provider event is not bound to an eligible practitioner.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$claim = WCA_Repository::claim_idempotency( 'calendar_provider_webhook', $provider . ':' . $event_id, 0, array( 'provider' => $provider, 'event_id' => $event_id, 'doctor_user_id' => $doctor_id ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return rest_ensure_response( $claim['response'] ); }
		if ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_calendar_webhook_in_progress', __( 'This calendar provider event is already being reconciled.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$result = WCA_Repository::transaction( function () use ( $verified, $provider, $event_id, $doctor_id, $claim ) {
			$busy_count = 0;
			foreach ( (array) ( $verified['busy_windows'] ?? array() ) as $window ) {
				if ( ! is_array( $window ) ) { return new WP_Error( 'wca_calendar_webhook_window', __( 'Calendar busy-window payload is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
				$saved = WCA_Future24::save_verified_external_busy( $doctor_id, $provider, $event_id, $window );
				if ( is_wp_error( $saved ) ) { return $saved; }
				$busy_count++;
			}
			$mapping = null;
			if ( ! empty( $verified['appointment_ref'] ) || ! empty( $verified['provider_event_ref'] ) ) {
				$mapping = WCA_Repository::upsert_calendar_mapping_from_provider( $provider, $verified, $doctor_id );
				if ( is_wp_error( $mapping ) ) { return $mapping; }
			}
			$trace = WCA_Observability::trace_id();
			$audit = WCA_Repository::append_event( 'CalendarProviderReconciled.v1', 'calendar_provider', $provider . ':' . $event_id, array( 'event_id' => WCA_Repository::uuid(), 'provider' => $provider, 'source_event_id' => $event_id, 'doctor_subject_uuid' => WCA_Authorization::subject_uuid( $doctor_id ), 'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'trace_id' => $trace ), $doctor_id, $trace );
			if ( is_wp_error( $audit ) ) { return $audit; }
			$response = array( 'accepted' => true, 'provider' => $provider, 'event_id' => $event_id, 'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'canonical_appointment_mutated' => false );
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], 202, $response ) ) { return new WP_Error( 'wca_calendar_webhook_finalize', __( 'Calendar provider reconciliation could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			return $response;
		}, 'wca_calendar_provider_webhook_transaction' );
		if ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $result; }
		$response = rest_ensure_response( $result );
		$response->set_status( 202 );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		return $response;
	}

	public static function signer( WP_REST_Request $request ) {
		$url = self::url( sanitize_text_field( $request['ref'] ), get_current_user_id() );
		if ( is_wp_error( $url ) ) { return $url; }
		if ( ! $url ) { return new WP_Error( 'wca_calendar_forbidden', __( 'Calendar export is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$response = rest_ensure_response( array( 'contract' => 'wca.signed-calendar-link', 'version' => self::CONTRACT_VERSION, 'url' => esc_url_raw( $url ), 'expires_in' => self::TTL ) );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		return $response;
	}

	public static function url( $appointment_ref, $user_id = 0 ) {
		$appointment_ref = strtolower( sanitize_text_field( $appointment_ref ) );
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( ! $user_id || ! preg_match( '/^[0-9a-f-]{36}$/', $appointment_ref ) ) { return ''; }
		$id = self::appointment_id( $appointment_ref );
		if ( is_wp_error( $id ) ) { return $id; }
		if ( ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return ''; }
		$exp = time() + self::TTL;
		$subject = WCA_Authorization::subject_uuid( $user_id );
		if ( ! $subject ) { return ''; }
		$sig = self::signature( $appointment_ref, $subject, $exp );
		return add_query_arg( array( 'sub' => $subject, 'exp' => $exp, 'sig' => $sig ), rest_url( 'wca/v1/calendar-links/' . rawurlencode( $appointment_ref ) . '.ics' ) );
	}

	public static function download( WP_REST_Request $request ) {
		$ref = strtolower( sanitize_text_field( $request['ref'] ) );
		$subject = strtolower( sanitize_text_field( $request->get_param( 'sub' ) ) );
		$exp = absint( $request->get_param( 'exp' ) );
		$sig = sanitize_text_field( $request->get_param( 'sig' ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) || ! preg_match( '/^[0-9a-f-]{36}$/', $subject ) || ! $exp || $exp < time() || $exp > time() + self::TTL + 60 ) {
			return new WP_Error( 'wca_calendar_link_invalid', __( 'This calendar link is invalid or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$expected = self::signature( $ref, $subject, $exp );
		if ( ! $sig || ! hash_equals( $expected, $sig ) ) { return new WP_Error( 'wca_calendar_link_invalid', __( 'This calendar link is invalid or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$user_id = self::user_id_from_subject( $subject );
		if ( is_wp_error( $user_id ) ) { return $user_id; }
		$id = self::appointment_id( $ref );
		if ( is_wp_error( $id ) ) { return $id; }
		if ( ! $user_id || ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return new WP_Error( 'wca_calendar_link_invalid', __( 'This calendar link is invalid or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$status = SWC_Helpers::status( $id );
		if ( ! in_array( $status, array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true ) ) { return new WP_Error( 'wca_calendar_state', __( 'Calendar export is unavailable for this appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$start = (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' );
		$end = (string) SWC_Helpers::meta( $id, 'appointment_end_utc', '' );
		$start_ts = self::strict_utc_timestamp( $start );
		$end_ts = self::strict_utc_timestamp( $end );
		if ( false === $start_ts || false === $end_ts || $end_ts <= $start_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment time is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$uid = strtolower( $ref ) . '@' . sanitize_key( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabri Social Homeopathy Platform//File 08//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\n";
		$ics .= 'UID:' . self::ics( $uid ) . "\r\n";
		$ics .= 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ) . "\r\n";
		$ics .= 'DTSTART:' . gmdate( 'Ymd\THis\Z', $start_ts ) . "\r\n";
		$ics .= 'DTEND:' . gmdate( 'Ymd\THis\Z', $end_ts ) . "\r\n";
		$ics .= 'SUMMARY:' . self::ics( __( 'Clinic appointment', 'worldwide-clinic-appointments' ) ) . "\r\n";
		$ics .= 'DESCRIPTION:' . self::ics( __( 'Private appointment schedule. Open the platform for current status and instructions.', 'worldwide-clinic-appointments' ) ) . "\r\n";
		$ics .= "END:VEVENT\r\nEND:VCALENDAR\r\n";
		$response = new WP_REST_Response( $ics, 200 );
		$response->header( 'Content-Type', 'text/calendar; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="appointment-' . substr( $ref, 0, 8 ) . '.ics"' );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		$response->header( 'Referrer-Policy', 'no-referrer' );
		return $response;
	}

	private static function signature( $ref, $subject, $exp ) {
		$key = hash( 'sha256', wp_salt( 'auth' ) . '|wca-calendar-link|' . home_url( '/' ), true );
		return hash_hmac( 'sha256', strtolower( $ref ) . '|' . strtolower( $subject ) . '|' . absint( $exp ), $key );
	}

	private static function user_id_from_subject( $subject ) {
		global $wpdb;
		$subject = strtolower( sanitize_text_field( $subject ) );
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key=%s AND meta_value=%s ORDER BY user_id ASC LIMIT 2", '_smc_subject_uuid', $subject ) );
		if ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_calendar_subject_read_failed', __( 'Calendar participant identity could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private static function appointment_id( $ref ) {
		global $wpdb;
		$ref = strtolower( sanitize_text_field( $ref ) );
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key=%s WHERE p.post_type=%s AND pm.meta_value=%s ORDER BY p.ID ASC LIMIT 2", '_swc_public_ref', SWC_Helpers::TYPE, $ref ) );
		if ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_calendar_appointment_read_failed', __( 'Calendar appointment state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private static function strict_utc_timestamp( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) { return false; }
		$utc = new DateTimeZone( 'UTC' );
		foreach ( array( array( '!Y-m-d H:i:s', 'Y-m-d H:i:s' ), array( '!Y-m-d H:i', 'Y-m-d H:i' ), array( '!Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s\Z' ), array( '!Y-m-d\TH:i\Z', 'Y-m-d\TH:i\Z' ) ) as $entry ) {
			$dt = DateTimeImmutable::createFromFormat( $entry[0], $value, $utc );
			$errors = DateTimeImmutable::getLastErrors();
			if ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) { return $dt->getTimestamp(); }
		}
		return false;
	}

	private static function ics( $value ) {
		$value = str_replace( array( "\r", "\n" ), ' ', (string) $value );
		return str_replace( array( '\\', ';', ',', ':' ), array( '\\\\', '\\;', '\\,', '\\:' ), $value );
	}
}

function wca_signed_calendar_url( $appointment_ref, $user_id = 0 ) { return WCA_Calendar_Link::url( $appointment_ref, $user_id ); }
