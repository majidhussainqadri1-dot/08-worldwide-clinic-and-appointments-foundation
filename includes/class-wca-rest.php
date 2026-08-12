<?php
/**
 * Versioned REST API for public discovery and protected clinic workflows.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_REST {
	const NAMESPACE = 'wca/v1';

	public static function hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'security_headers' ), 10, 3 );
	}

	public static function register_routes() {
		register_rest_route( self::NAMESPACE, '/contracts', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'contracts' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NAMESPACE, '/clinics', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'clinics' ), 'permission_callback' => '__return_true' ),
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create_clinic' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
		) );
		register_rest_route( self::NAMESPACE, '/clinics/(?P<id>[a-zA-Z0-9_-]+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'clinic' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NAMESPACE, '/clinics/(?P<id>\d+)/submit-review', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'submit_clinic_review' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/clinics/(?P<id>\d+)/activate', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'activate_clinic' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/branches', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'create_branch' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/services', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'save_service' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/availability', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'save_availability' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/slots', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'slots' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( self::NAMESPACE, '/slot-holds', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'hold_slot' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/appointments', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'request_appointment' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/appointments/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'appointment' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/appointments/(?P<id>\d+)/transitions', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'transition' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/appointments/(?P<id>\d+)/calendar.ics', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'calendar' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/appointments/(?P<id>\d+)/payment-intents', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'payment_intent' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/complaints', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'complaint' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/health', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'health' ),
			'permission_callback' => array( __CLASS__, 'admin' ),
		) );
	}

	public static function authenticated() {
		return is_user_logged_in() ? true : new WP_Error( 'wca_auth_required', __( 'Authentication is required.', 'worldwide-clinic-appointments' ), array( 'status' => 401 ) );
	}

	public static function admin() {
		return current_user_can( 'manage_worldwide_clinic' ) || current_user_can( 'manage_options' ) ? true : new WP_Error( 'wca_admin_required', __( 'Administrator permission is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	private static function rate_limit( $scope, $limit = 60, $window = 60 ) {
		$result = SWC_Helpers::rate_limit_hit( 'rest_' . sanitize_key( $scope ), absint( get_current_user_id() ), $limit, $window );
		return $result ? new WP_Error( 'wca_rate_limit', __( 'Too many requests. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => $window ) ) : true;
	}

	private static function data( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		return is_array( $data ) ? $data : $request->get_params();
	}

	private static function respond( $result, $success_status = 200 ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->set_status( $success_status );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	public static function contracts() {
		return self::respond( WCA_Contracts::contract_manifest() );
	}

	public static function clinics( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'public_clinics', 120, 60 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$args = array(
			'status'       => 'active',
			'country_code' => sanitize_text_field( $request->get_param( 'country' ) ),
			'city'         => sanitize_text_field( $request->get_param( 'city' ) ),
			'search'       => sanitize_text_field( $request->get_param( 'search' ) ),
			'per_page'     => min( 50, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) ),
		);
		$filter_hash = hash( 'sha256', wp_json_encode( array( $args['country_code'], $args['city'], $args['search'], $args['per_page'] ) ) );
		$cursor = strtolower( sanitize_text_field( $request->get_param( 'cursor' ) ) );
		if ( $cursor ) {
			if ( ! preg_match( '/^[0-9a-f-]{36}$/', $cursor ) ) { return new WP_Error( 'wca_cursor_invalid', __( 'The clinic cursor is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
			$state = get_transient( 'wca_clinic_cursor_' . md5( $cursor ) );
			if ( ! is_array( $state ) || ! hash_equals( (string) ( $state['filter_hash'] ?? '' ), $filter_hash ) ) { return new WP_Error( 'wca_cursor_expired', __( 'The clinic cursor expired or does not match these filters.', 'worldwide-clinic-appointments' ), array( 'status' => 410 ) ); }
			$args['cursor_updated_at'] = sanitize_text_field( $state['updated_at'] ?? '' );
			$args['cursor_id'] = absint( $state['id'] ?? 0 );
		}
		$rows = WCA_Repository::list_clinics( $args );
		$items = array();
		foreach ( $rows as $row ) {
			$projection = WCA_Service::public_clinic_projection( $row['public_ref'] );
			if ( $projection ) { $items[] = $projection; }
		}
		$next_cursor = '';
		if ( count( $rows ) === $args['per_page'] ) {
			$last = end( $rows );
			if ( is_array( $last ) && ! empty( $last['id'] ) && ! empty( $last['updated_at'] ) ) {
				$next_cursor = strtolower( wp_generate_uuid4() );
				$stored = set_transient( 'wca_clinic_cursor_' . md5( $next_cursor ), array( 'id' => absint( $last['id'] ), 'updated_at' => (string) $last['updated_at'], 'filter_hash' => $filter_hash ), 15 * MINUTE_IN_SECONDS );
				if ( ! $stored ) { return new WP_Error( 'wca_cursor_store_failed', __( 'Clinic pagination state could not be stored safely. Please retry the search.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'retry_after' => 1 ) ); }
			}
		}
		$payload = array( 'items' => $items, 'per_page' => $args['per_page'], 'next_cursor' => $next_cursor, 'generated_at' => gmdate( 'c' ) );
		$etag = '"' . hash( 'sha256', wp_json_encode( array( $filter_hash, array_map( static function ( $item ) { return array( $item['public_ref'] ?? '', $item['updated_at'] ?? '', $item['record_version'] ?? 0 ); }, $items ), $next_cursor ) ) ) . '"';
		if ( hash_equals( $etag, (string) $request->get_header( 'If-None-Match' ) ) ) {
			$response = new WP_REST_Response( null, 304 );
		} else {
			$response = rest_ensure_response( $payload );
		}
		$response->header( 'ETag', $etag );
		$response->header( 'Cache-Control', 'public, max-age=60, stale-while-revalidate=120' );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	public static function clinic( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'public_clinic', 120, 60 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$identifier = sanitize_text_field( $request['id'] );
		if ( ctype_digit( $identifier ) ) { return new WP_Error( 'wca_public_numeric_id_disabled', __( 'Numeric internal clinic identifiers are not public API identities.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$projection = WCA_Service::public_clinic_projection( $identifier );
		return $projection ? self::respond( $projection ) : new WP_Error( 'wca_clinic_not_found', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
	}

	public static function create_clinic( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'create_clinic', 10, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( WCA_Service::create_clinic( self::data( $request ) ), 201 );
	}

	public static function submit_clinic_review( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'clinic_review', 20, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( WCA_Service::submit_clinic_for_review( absint( $request['id'] ), absint( $request->get_param( 'expected_version' ) ) ) );
	}

	public static function activate_clinic( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'clinic_activate', 20, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( WCA_Service::activate_clinic( absint( $request['id'] ), absint( $request->get_param( 'expected_version' ) ) ) );
	}

	public static function create_branch( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'branch_mutation', 30, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( WCA_Service::create_branch( self::data( $request ) ), 201 );
	}

	public static function save_service( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'service_mutation', 60, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$data = self::data( $request );
		return self::respond( WCA_Service::save_service( $data, absint( $data['service_id'] ?? 0 ), absint( $data['expected_version'] ?? 0 ) ), empty( $data['service_id'] ) ? 201 : 200 );
	}

	public static function save_availability( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'availability_mutation', 60, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$data = self::data( $request );
		return self::respond( WCA_Service::set_availability( $data, absint( $data['rule_id'] ?? 0 ), absint( $data['expected_version'] ?? 0 ) ), empty( $data['rule_id'] ) ? 201 : 200 );
	}

	public static function slots( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'slot_search', 120, 60 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$query = WCA_Plan_Guard::resolve_public_slot_query( array_map( 'sanitize_text_field', $request->get_params() ) );
		return is_wp_error( $query ) ? $query : self::respond( WCA_Service::search_slots( $query ) );
	}

	public static function hold_slot( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'slot_hold', 30, 300 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( WCA_Service::hold_slot( self::data( $request ) ), 201 );
	}

	public static function request_appointment( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'request_appointment', 10, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( self::protected_mutation_projection( WCA_Service::request_appointment( self::data( $request ) ), 'appointment' ), 201 );
	}

	public static function appointment( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'appointment_read', 120, 60 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$id = absint( $request['id'] );
		$access = WCA_Authorization::can_view_appointment( $id, 0, sanitize_key( $request->get_header( 'X-WCA-Access-Purpose' ) ) );
		if ( is_wp_error( $access ) ) { return $access; }
		return self::respond( self::appointment_projection( $id ) );
	}

	private static function appointment_projection( $id ) {
		$clinic = WCA_Repository::get_clinic( absint( SWC_Helpers::meta( $id, 'clinic_id' ) ), false );
		$service = WCA_Repository::get_service( absint( SWC_Helpers::meta( $id, 'service_id' ) ), false );
		return array(
			'public_ref'        => (string) SWC_Helpers::meta( $id, 'public_ref', 'appointment-' . $id ),
			'status'            => SWC_Helpers::status( $id ),
			'version'           => SWC_Helpers::record_version( $id ),
			'scheduled_at_utc'  => (string) SWC_Helpers::meta( $id, 'preferred_at_utc' ),
			'end_at_utc'        => (string) SWC_Helpers::meta( $id, 'appointment_end_utc' ),
			'timezone'          => (string) SWC_Helpers::meta( $id, 'patient_timezone' ),
			'consultation_type' => (string) SWC_Helpers::meta( $id, 'consultation_type' ),
			'clinic_ref'        => $clinic ? (string) $clinic['public_ref'] : '',
			'service_ref'       => $service ? (string) $service['public_ref'] : '',
			'allowed_actions'   => WCA_Contracts::allowed_transitions( WCA_Authorization::appointment_actor( $id, get_current_user_id() ), SWC_Helpers::status( $id ) ),
			'clinical_authority'=> false,
		);
	}

	private static function protected_mutation_projection( $result, $type ) {
		if ( is_wp_error( $result ) || ! is_array( $result ) ) { return $result; }
		if ( 'appointment' === $type ) {
			return array_intersect_key( $result, array_flip( array( 'public_ref','status','version','trace_id' ) ) );
		}
		if ( 'payment' === $type ) {
			return array_intersect_key( $result, array_flip( array( 'public_ref','status','currency','amount_minor','platform_commission_minor','version','created_at','updated_at' ) ) );
		}
		if ( 'complaint' === $type ) {
			return array_intersect_key( $result, array_flip( array( 'public_ref','category','status','version','created_at','updated_at' ) ) );
		}
		return array();
	}

	public static function transition( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'appointment_transition', 60, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$data = self::data( $request );
		$raw_next = sanitize_key( (string) ( $data['next_status'] ?? '' ) );
		if ( ! WCA_Contracts::is_appointment_status( $raw_next, true ) ) {
			return new WP_Error( 'wca_invalid_status', __( 'A valid target status is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		return self::respond( self::protected_mutation_projection( WCA_Service::transition_appointment( absint( $request['id'] ), $raw_next, $data ), 'appointment' ) );
	}

	public static function calendar( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'appointment_calendar', 60, 60 );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$ics = WCA_Service::appointment_ics( absint( $request['id'] ) );
		if ( is_wp_error( $ics ) ) { return $ics; }
		$response = new WP_REST_Response( $ics, 200 );
		$response->header( 'Content-Type', 'text/calendar; charset=utf-8' );
		$response->header( 'Content-Disposition', 'attachment; filename="appointment.ics"' );
		$response->header( 'Cache-Control', 'private, no-store' );
		return $response;
	}

	public static function payment_intent( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'payment_intent', 20, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( self::protected_mutation_projection( WCA_Service::create_payment_intent( absint( $request['id'] ), sanitize_key( $request->get_param( 'provider' ) ?: 'manual' ), get_current_user_id(), trim( (string) $request->get_header( 'Idempotency-Key' ) ) ), 'payment' ), 201 );
	}

	public static function complaint( WP_REST_Request $request ) {
		$rate = self::rate_limit( 'complaint', 10, HOUR_IN_SECONDS );
		if ( is_wp_error( $rate ) ) { return $rate; }
		return self::respond( self::protected_mutation_projection( WCA_Service::create_complaint( self::data( $request ) ), 'complaint' ), 201 );
	}

	public static function health() {
		return self::respond( WCA_Observability::health() );
	}

	public static function security_headers( $response, $server, $request ) {
		$route  = $request instanceof WP_REST_Request ? $request->get_route() : '';
		$method = $request instanceof WP_REST_Request ? strtoupper( (string) $request->get_method() ) : '';
		if ( 0 === strpos( $route, '/' . self::NAMESPACE ) ) {
			$response->header( 'X-Content-Type-Options', 'nosniff' );
			$response->header( 'Referrer-Policy', 'strict-origin-when-cross-origin' );
			$response->header( 'Permissions-Policy', 'camera=(), microphone=(), geolocation=()' );
			$protected_mutation = ! in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true );
			$sensitive_read = false !== strpos( $route, '/appointments' ) || false !== strpos( $route, '/slot-holds' ) || false !== strpos( $route, '/complaints' ) || false !== strpos( $route, '/health' );
			if ( $protected_mutation || $sensitive_read ) {
				$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
				$response->header( 'Pragma', 'no-cache' );
				$response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );
			}
		}
		return $response;
	}
}