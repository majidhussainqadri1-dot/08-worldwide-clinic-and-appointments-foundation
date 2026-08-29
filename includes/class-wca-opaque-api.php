<?php
/**
 * Opaque-reference REST constitution for File 08.
 *
 * Internal numeric primary keys remain implementation details only. Canonical
 * browser/cross-file APIs use public UUID references. Legacy numeric routes
 * are fail-closed by default and may only be temporarily re-enabled through
 * an explicit migration filter.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Opaque_API {
	const CONTRACT_VERSION = '1.0.0';

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ), 40 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'block_legacy_numeric_routes' ), 20, 3 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'strip_native_ids' ), 50, 3 );
	}

	public static function register_routes() {
		register_rest_route( 'wca/v1', '/appointment-refs/(?P<ref>[0-9a-fA-F-]{36})', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'appointment' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/appointment-refs/(?P<ref>[0-9a-fA-F-]{36})/transitions', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'transition' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/appointment-refs/(?P<ref>[0-9a-fA-F-]{36})/calendar.ics', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'calendar' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/appointment-refs/(?P<ref>[0-9a-fA-F-]{36})/payment-intents', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'payment_intent' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/clinic-refs/(?P<ref>[0-9a-fA-F-]{36})/submit-review', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'submit_clinic_review' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/clinic-refs/(?P<ref>[0-9a-fA-F-]{36})/activate', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( __CLASS__, 'activate_clinic' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
	}

	public static function block_legacy_numeric_routes( $result, $server, $request ) {
		if ( null !== $result || ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}
		$route = (string) $request->get_route();
		$legacy = preg_match( '#^/wca/v1/appointments/\d+(?:/transitions|/calendar\.ics|/payment-intents)?$#', $route )
			|| preg_match( '#^/wca/v1/clinics/\d+/(?:submit-review|activate)$#', $route );
		if ( ! $legacy ) {
			return $result;
		}
		if ( (bool) apply_filters( 'wca_allow_legacy_numeric_rest_routes', false, $route, $request ) ) {
			return $result;
		}
		return new WP_Error(
			'wca_legacy_numeric_route_disabled',
			__( 'This legacy numeric route is disabled. Use the opaque-reference API.', 'worldwide-clinic-appointments' ),
			array( 'status' => 410, 'contract' => 'wca.opaque-object-refs', 'version' => self::CONTRACT_VERSION )
		);
	}

	public static function appointment( WP_REST_Request $request ) {
		$id = self::appointment_id( $request['ref'] );
		if ( is_wp_error( $id ) ) { return $id; }
		if ( ! $id ) { return self::not_found(); }
		$access = self::appointment_access( $id, sanitize_key( $request->get_header( 'X-WCA-Access-Purpose' ) ) );
		if ( is_wp_error( $access ) ) { return $access; }
		return self::respond( self::appointment_projection( $id ) );
	}

	public static function transition( WP_REST_Request $request ) {
		$id = self::appointment_id( $request['ref'] );
		if ( is_wp_error( $id ) ) { return $id; }
		if ( ! $id ) { return self::not_found(); }
		$access = self::appointment_access( $id );
		if ( is_wp_error( $access ) ) { return $access; }
		$data = self::data( $request );
		$next = sanitize_key( isset( $data['next_status'] ) ? $data['next_status'] : '' );
		if ( ! WCA_Contracts::is_appointment_status( $next, true ) ) {
			return new WP_Error( 'wca_invalid_status', __( 'A valid target status is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		return self::respond( WCA_Service::transition_appointment( $id, $next, $data ) );
	}

	public static function calendar( WP_REST_Request $request ) {
		$id = self::appointment_id( $request['ref'] );
		if ( is_wp_error( $id ) ) { return $id; }
		if ( ! $id ) { return self::not_found(); }
		$access = self::appointment_access( $id );
		if ( is_wp_error( $access ) ) { return $access; }
		$proxy = new WP_REST_Request( 'GET', '/wca/v1/appointments/' . $id . '/calendar.ics' );
		$proxy->set_url_params( array( 'id' => $id ) );
		return WCA_REST::calendar( $proxy );
	}

	public static function payment_intent( WP_REST_Request $request ) {
		$id = self::appointment_id( $request['ref'] );
		if ( is_wp_error( $id ) ) { return $id; }
		if ( ! $id ) { return self::not_found(); }
		$access = self::appointment_access( $id );
		if ( is_wp_error( $access ) ) { return $access; }
		$proxy = new WP_REST_Request( 'POST', '/wca/v1/appointments/' . $id . '/payment-intents' );
		$proxy->set_url_params( array( 'id' => $id ) );
		$proxy->set_body_params( self::data( $request ) );
		$proxy->set_header( 'Idempotency-Key', trim( (string) $request->get_header( 'Idempotency-Key' ) ) );
		return WCA_REST::payment_intent( $proxy );
	}

	public static function submit_clinic_review( WP_REST_Request $request ) {
		WCA_Repository::clear_read_error();
		$clinic = WCA_Repository::get_clinic( sanitize_text_field( $request['ref'] ), false );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		return self::respond( WCA_Service::submit_clinic_for_review( absint( $clinic['id'] ), $request->get_param( 'expected_version' ) ) );
	}

	public static function activate_clinic( WP_REST_Request $request ) {
		WCA_Repository::clear_read_error();
		$clinic = WCA_Repository::get_clinic( sanitize_text_field( $request['ref'] ), false );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		return self::respond( WCA_Service::activate_clinic( absint( $clinic['id'] ), $request->get_param( 'expected_version' ) ) );
	}

	public static function strip_native_ids( $response, $server, $request ) {
		if ( ! ( $request instanceof WP_REST_Request ) || 0 !== strpos( (string) $request->get_route(), '/wca/v1/' ) || is_wp_error( $response ) ) {
			return $response;
		}
		if ( ! ( $response instanceof WP_REST_Response ) ) {
			$response = rest_ensure_response( $response );
		}
		$data = $response->get_data();
		$response->set_data( self::sanitize_external_payload( $data ) );
		return $response;
	}

	/** @return mixed */
	private static function sanitize_external_payload( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}
		$out = array();
		$forbidden = array(
			'id', 'native_id', 'clinic_id', 'service_id', 'branch_id', 'rule_id',
			'appointment_id', 'doctor_user_id', 'patient_user_id', 'guardian_user_id',
			'actor_user_id', 'created_by_user_id', 'owner_user_id',
		);
		foreach ( $value as $key => $item ) {
			if ( is_string( $key ) && in_array( $key, $forbidden, true ) ) {
				continue;
			}
			$out[ $key ] = self::sanitize_external_payload( $item );
		}
		return $out;
	}

	/** @return array<string,mixed> */
	private static function appointment_projection( $id ) {
		$status = SWC_Helpers::status( $id );
		$actor  = WCA_Authorization::appointment_actor( $id, get_current_user_id() );
		return array(
			'contract'          => 'wca.appointment-projection',
			'version'           => self::CONTRACT_VERSION,
			'public_ref'        => self::appointment_ref( $id ),
			'status'            => $status,
			'record_version'    => SWC_Helpers::record_version( $id ),
			'scheduled_at_utc'  => (string) SWC_Helpers::meta( $id, 'preferred_at_utc' ),
			'end_at_utc'        => (string) SWC_Helpers::meta( $id, 'appointment_end_utc' ),
			'timezone'          => (string) SWC_Helpers::meta( $id, 'patient_timezone' ),
			'consultation_type' => (string) SWC_Helpers::meta( $id, 'consultation_type' ),
			'allowed_actions'   => WCA_Contracts::allowed_transitions( $actor, $status ),
			'clinical_authority'=> false,
		);
	}

	private static function data( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		return is_array( $data ) ? $data : $request->get_params();
	}

	private static function respond( $result, $status = 200 ) {
		if ( is_wp_error( $result ) ) { return $result; }
		$response = rest_ensure_response( self::sanitize_external_payload( $result ) );
		$response->set_status( $status );
		$response->header( 'X-WCA-Object-Contract', 'wca.opaque-object-refs/' . self::CONTRACT_VERSION );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );
		return $response;
	}

	private static function appointment_access( $id, $purpose = '' ) {
		$access = WCA_Authorization::can_view_appointment( absint( $id ), 0, sanitize_key( $purpose ) );
		if ( ! is_wp_error( $access ) ) { return true; }
		$data = $access->get_error_data();
		$status = is_array( $data ) ? absint( $data['status'] ?? 0 ) : 0;
		if ( in_array( $status, array( 401, 403, 404 ), true ) ) { return self::not_found(); }
		return $access;
	}

	private static function appointment_id( $ref ) {
		global $wpdb;
		$ref = strtolower( sanitize_text_field( $ref ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return 0; }
		foreach ( array( '_swc_public_ref', 'public_ref' ) as $meta_key ) {
			$sql = $wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID WHERE p.post_type=%s AND p.post_status NOT IN ('trash','auto-draft','inherit') AND pm.meta_key=%s AND pm.meta_value=%s ORDER BY p.ID ASC LIMIT 2",
				SWC_Helpers::TYPE,
				$meta_key,
				$ref
			);
			$ids_raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( '' !== (string) $wpdb->last_error ) {
				return new WP_Error( 'wca_appointment_ref_read_failed', __( 'Appointment reference could not be resolved safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
			}
			$ids = array_values( array_filter( array_map( 'absint', (array) $ids_raw ) ) );
			if ( 1 === count( $ids ) ) { return $ids[0]; }
			if ( count( $ids ) > 1 ) { return 0; }
		}
		return 0;
	}

	private static function appointment_ref( $id ) {
		$ref = (string) SWC_Helpers::meta( absint( $id ), 'public_ref', '' );
		return preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ? strtolower( $ref ) : '';
	}

	private static function not_found() {
		return new WP_Error( 'wca_appointment_not_found', __( 'Appointment was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
	}
}
