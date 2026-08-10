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
	const CONTRACT_VERSION = '1.0.1';
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
	}

	public static function signer( WP_REST_Request $request ) {
		$url = self::url( sanitize_text_field( $request['ref'] ), get_current_user_id() );
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
		$id = self::appointment_id( $ref );
		if ( ! $user_id || ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return new WP_Error( 'wca_calendar_link_invalid', __( 'This calendar link is invalid or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$status = SWC_Helpers::status( $id );
		if ( ! in_array( $status, array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true ) ) { return new WP_Error( 'wca_calendar_state', __( 'Calendar export is unavailable for this appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$start = (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' );
		$end = (string) SWC_Helpers::meta( $id, 'appointment_end_utc', '' );
		if ( ! $start || ! $end ) { return new WP_Error( 'wca_calendar_time', __( 'Confirmed appointment time is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$uid = strtolower( $ref ) . '@' . sanitize_key( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		$ics = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Sabri Social Homeopathy Platform//File 08//EN\r\nCALSCALE:GREGORIAN\r\nMETHOD:PUBLISH\r\nBEGIN:VEVENT\r\n";
		$ics .= 'UID:' . self::ics( $uid ) . "\r\n";
		$ics .= 'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ) . "\r\n";
		$ics .= 'DTSTART:' . gmdate( 'Ymd\THis\Z', strtotime( $start . ' UTC' ) ) . "\r\n";
		$ics .= 'DTEND:' . gmdate( 'Ymd\THis\Z', strtotime( $end . ' UTC' ) ) . "\r\n";
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
		if ( function_exists( 'smc_get_user_id_by_subject_uuid' ) ) { $id = absint( smc_get_user_id_by_subject_uuid( $subject ) ); if ( $id ) { return $id; } }
		$users = get_users( array( 'fields' => 'ids', 'number' => 2, 'meta_key' => '_smc_subject_uuid', 'meta_value' => $subject ) );
		return 1 === count( $users ) ? absint( $users[0] ) : 0;
	}

	private static function appointment_id( $ref ) {
		$ids = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 2, 'no_found_rows' => true, 'meta_key' => '_swc_public_ref', 'meta_value' => strtolower( sanitize_text_field( $ref ) ) ) );
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private static function ics( $value ) {
		$value = str_replace( array( "\r", "\n" ), ' ', (string) $value );
		return str_replace( array( '\\', ';', ',', ':' ), array( '\\\\', '\\;', '\\,', '\\:' ), $value );
	}
}

function wca_signed_calendar_url( $appointment_ref, $user_id = 0 ) { return WCA_Calendar_Link::url( $appointment_ref, $user_id ); }
