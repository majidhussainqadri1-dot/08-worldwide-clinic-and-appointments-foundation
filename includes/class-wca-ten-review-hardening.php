<?php
/**
 * Ten-round post-closure hardening for File 08.
 *
 * Adds cross-cutting safeguards without taking ownership away from the
 * canonical clinic/appointment services.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Ten_Review_Hardening {
	const CONTRACT_VERSION = '1.0.0';
	/** @var array<string,array<string,mixed>> */
	private static $claims = array();

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ), 90 );
		add_action( 'template_redirect', array( __CLASS__, 'canonicalize_appointment_alias' ), 0 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'pre_dispatch' ), 15, 3 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'post_dispatch' ), 65, 3 );
		add_action( 'init', array( __CLASS__, 'legacy_shortcodes' ), 100 );
		foreach ( self::legacy_mutation_actions() as $action ) {
			add_action( 'admin_post_' . $action, array( __CLASS__, 'block_legacy_browser_mutation' ), 0 );
		}
	}

	/** Register a timezone-correct slot-search adapter after the base route. */
	public static function register_routes() {
		register_rest_route(
			'wca/v1',
			'/slots',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'slots' ),
				'permission_callback' => '__return_true',
			),
			true
		);
		register_rest_route(
			'wca/v1',
			'/mutation-status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'mutation_status' ),
				'permission_callback' => array( 'WCA_REST', 'authenticated' ),
			)
		);
	}

	/** Redirect the accidentally emitted plural detail URL without rewrite-flush dependency. */
	public static function canonicalize_appointment_alias() {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		$relative = trim( $path, '/' );
		if ( $home_path && 0 === strpos( $relative, $home_path . '/' ) ) { $relative = substr( $relative, strlen( $home_path ) + 1 ); }
		if ( ! preg_match( '#^appointments/([0-9a-fA-F-]{36})/?$#', $relative, $matches ) ) { return; }
		wp_safe_redirect( home_url( '/appointment/' . rawurlencode( strtolower( $matches[1] ) ) . '/' ), 301 );
		exit;
	}

	/**
	 * Cross-cutting mutation guard: rate limiting, full-request idempotency,
	 * transition preconditions, payment authority, and doctor/clinic scope.
	 */
	public static function pre_dispatch( $result, $server, $request ) {
		if ( null !== $result || ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}
		$route  = (string) $request->get_route();
		$method = strtoupper( (string) $request->get_method() );
		if ( self::is_legacy_numeric_rest_route( $route ) && ! (bool) apply_filters( 'wca_allow_legacy_numeric_rest_routes', false ) ) {
			return new WP_Error( 'wca_legacy_numeric_rest_disabled', __( 'This legacy numeric-ID endpoint is disabled. Use the current opaque-reference endpoint.', 'worldwide-clinic-appointments' ), array( 'status' => 410 ) );
		}
		if ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) || ! self::is_core_mutation_route( $route ) ) {
			return $result;
		}
		$actor = absint( get_current_user_id() );
		if ( ! $actor ) {
			return new WP_Error( 'wca_auth_required', __( 'Authentication is required.', 'worldwide-clinic-appointments' ), array( 'status' => 401 ) );
		}
		$scope = 'tenreview_' . substr( hash( 'sha256', $route ), 0, 24 );
		if ( SWC_Helpers::rate_limit_hit( $scope, $actor, 60, 300 ) ) {
			return new WP_Error( 'wca_rate_limit', __( 'Too many requests. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => 300 ) );
		}

		$data = self::request_data( $request );
		if ( preg_match( '#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/transitions$#', $route ) ) {
			if ( empty( $data['expected_status'] ) || empty( $data['expected_version'] ) || absint( $data['expected_version'] ) < 1 ) {
				return new WP_Error( 'wca_transition_precondition_required', __( 'Current expected status and record version are required for an appointment transition.', 'worldwide-clinic-appointments' ), array( 'status' => 428 ) );
			}
		}
		if ( preg_match( '#^/wca/v1/appointment-refs/([0-9a-fA-F-]{36})/payment-intents$#', $route, $matches ) ) {
			$appointment_id = self::appointment_id_from_ref( $matches[1] );
			if ( ! $appointment_id ) { return new WP_Error( 'wca_appointment_not_found', __( 'Appointment was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
			$patient  = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
			$guardian = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
			if ( $actor !== $patient && $actor !== $guardian ) {
				return new WP_Error( 'wca_payment_payer_required', __( 'Only the patient or the currently authorized guardian may create a payment intent.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
			}
			$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor );
			if ( is_wp_error( $access ) ) { return $access; }
		}
		if ( '/wca/v1/availability' === $route ) {
			$scope_check = self::validate_availability_doctor_scope( $data, $actor );
			if ( is_wp_error( $scope_check ) ) { return $scope_check; }
		}

		$fingerprint_body = $data;
		unset( $fingerprint_body['idempotency_key'], $fingerprint_body['_wpnonce'] );
		$fingerprint_query = (array) $request->get_query_params();
		unset( $fingerprint_query['_wpnonce'] );
		$fingerprint = array(
			'route' => $route,
			'url'   => (array) $request->get_url_params(),
			'query' => $fingerprint_query,
			'body'  => $fingerprint_body,
		);
		$key = trim( (string) $request->get_header( 'Idempotency-Key' ) );
		if ( ! $key && ! empty( $data['idempotency_key'] ) ) { $key = trim( (string) $data['idempotency_key'] ); }
		if ( $key && ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $key ) ) {
			return new WP_Error( 'wca_idempotency_invalid', __( 'The idempotency key format is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		if ( ! $key ) {
			return new WP_Error( 'wca_idempotency_required', __( 'An explicit idempotency key is required for this mutation.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$claim = WCA_Repository::claim_idempotency( 'http_' . substr( hash( 'sha256', $route ), 0, 24 ), $key, $actor, $fingerprint );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) {
			$response = rest_ensure_response( isset( $claim['response'] ) ? $claim['response'] : array() );
			$response->set_status( absint( $claim['response_code'] ?? 200 ) ?: 200 );
			$response->header( 'X-WCA-Idempotency-Key', $key );
			$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
			return $response;
		}
		if ( empty( $claim['claimed_new'] ) ) {
			return new WP_Error( 'wca_idempotency_in_progress', __( 'This mutation is already being processed. Retry with the same idempotency key shortly.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) );
		}
		self::$claims[ spl_object_hash( $request ) ] = array( 'id' => absint( $claim['id'] ), 'key' => $key, 'route' => $route, 'data' => $data );
		return $result;
	}

	public static function post_dispatch( $response, $server, $request ) {
		if ( ! ( $request instanceof WP_REST_Request ) ) { return $response; }
		$hash = spl_object_hash( $request );
		if ( empty( self::$claims[ $hash ] ) ) { return $response; }
		$claim = self::$claims[ $hash ];
		unset( self::$claims[ $hash ] );
		if ( is_wp_error( $response ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $response; }
		$response = $response instanceof WP_REST_Response ? $response : rest_ensure_response( $response );
		if ( ! ( $response instanceof WP_REST_Response ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $response; }
		$status = absint( $response->get_status() );
		if ( $status >= 200 && $status < 400 ) {
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], $status, $response->get_data() ) ) {
				WCA_Observability::metric( 'http_idempotency_finalize_failed_total', 1, array( 'route_scope' => substr( hash( 'sha256', $claim['route'] ), 0, 12 ) ) );
				return new WP_Error( 'wca_idempotency_finalize_failed', __( 'The mutation may have completed, but replay evidence could not be finalized. Query mutation status before retrying.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'reconciliation_required' => true ) );
			}
		} else {
			WCA_Repository::release_idempotency( $claim['id'] );
		}
		$response->header( 'X-WCA-Idempotency-Key', $claim['key'] );
		return $response;
	}

	/** Authoritative status lookup for an explicit mutation idempotency key. */
	public static function mutation_status( WP_REST_Request $request ) {
		$actor = absint( get_current_user_id() );
		$route = (string) $request->get_param( 'route' );
		$key = trim( (string) $request->get_header( 'Idempotency-Key' ) );
		if ( ! $key ) { $key = trim( (string) $request->get_param( 'idempotency_key' ) ); }
		if ( ! self::is_core_mutation_route( $route ) || ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $key ) ) {
			return new WP_Error( 'wca_mutation_status_request', __( 'A valid mutation route and idempotency key are required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$scope = 'http_' . substr( hash( 'sha256', $route ), 0, 24 );
		$result = WCA_Repository::idempotency_status( $scope, $key, $actor );
		if ( is_wp_error( $result ) ) { return $result; }
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	/** Search a display-local date range without truncating rule-zone boundary days. */
	public static function slots( WP_REST_Request $request ) {
		if ( SWC_Helpers::rate_limit_hit( 'tenreview_slot_search', 0, 120, 60 ) ) {
			return new WP_Error( 'wca_rate_limit', __( 'Too many slot searches. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => 60 ) );
		}
		$query = WCA_Plan_Guard::resolve_public_slot_query( array_map( 'sanitize_text_field', $request->get_params() ) );
		if ( is_wp_error( $query ) ) { return $query; }
		$from = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $query['date_from'] ) ? (string) $query['date_from'] : gmdate( 'Y-m-d' );
		$to   = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $query['date_to'] ) ? (string) $query['date_to'] : gmdate( 'Y-m-d', time() + 30 * DAY_IN_SECONDS );
		$limit = min( 500, max( 1, absint( $query['limit'] ?? 100 ) ) );
		$expanded = $query;
		$expanded['date_from'] = gmdate( 'Y-m-d', strtotime( $from . ' -1 day UTC' ) );
		$expanded['date_to']   = gmdate( 'Y-m-d', strtotime( $to . ' +1 day UTC' ) );
		$expanded['limit'] = $limit;
		$expanded['display_date_from'] = $from;
		$expanded['display_date_to'] = $to;
		$result = WCA_Service::search_slots( $expanded );
		if ( is_wp_error( $result ) ) { return $result; }
		$zone = new DateTimeZone( (string) $query['timezone'] );
		$items = array();
		foreach ( (array) ( $result['slots'] ?? array() ) as $slot ) {
			try {
				$local = ( new DateTimeImmutable( (string) $slot['start_utc'], new DateTimeZone( 'UTC' ) ) )->setTimezone( $zone );
			} catch ( Exception $e ) { continue; }
			$date = $local->format( 'Y-m-d' );
			if ( $date >= $from && $date <= $to ) { $items[] = $slot; }
			if ( count( $items ) >= $limit ) { break; }
		}
		$result['slots'] = $items;
		$result['timezone'] = (string) $query['timezone'];
		$result['display_window'] = array( 'date_from' => $from, 'date_to' => $to );
		$response = rest_ensure_response( $result );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	private static function validate_availability_doctor_scope( $data, $actor ) {
		$clinic = WCA_Repository::get_clinic( absint( $data['clinic_id'] ?? 0 ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$manage = WCA_Authorization::can_manage_clinic( $clinic, $actor );
		if ( is_wp_error( $manage ) ) { return $manage; }
		$doctor = absint( $data['doctor_user_id'] ?? $actor );
		if ( ! $doctor || ! SWC_Doctor_Authority::is_eligible( $doctor ) ) { return new WP_Error( 'wca_doctor_ineligible', __( 'The doctor is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		if ( user_can( $actor, 'manage_worldwide_clinic' ) || $doctor === $actor || $doctor === absint( $clinic['owner_user_id'] ) ) { return true; }
		$delegated = array_merge( WCA_Authorization::delegated_clinic_ids( $doctor, 'schedule' ), WCA_Authorization::delegated_clinic_ids( $doctor, 'clinic_manage' ) );
		$allowed = in_array( absint( $clinic['id'] ), array_map( 'absint', $delegated ), true );
		$allowed = (bool) apply_filters( 'wca_doctor_may_serve_clinic', $allowed, $doctor, absint( $clinic['id'] ), $actor );
		return $allowed ? true : new WP_Error( 'wca_availability_doctor_scope', __( 'The selected doctor has no current authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	private static function appointment_id_from_ref( $ref ) {
		$ref = strtolower( sanitize_text_field( $ref ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) { return 0; }
		$ids = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private', 'publish' ), 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => '_swc_public_ref', 'meta_value' => $ref ) );
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private static function request_data( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		if ( ! is_array( $data ) ) { $data = $request->get_body_params(); }
		return is_array( $data ) ? $data : array();
	}

	private static function is_legacy_numeric_rest_route( $route ) {
		return (bool) preg_match( '#^/wca/v1/(?:clinics/[0-9]+/(?:submit-review|activate)|appointments/[0-9]+(?:/transitions|/calendar\.ics|/payment-intents)?)$#', (string) $route );
	}

	private static function is_core_mutation_route( $route ) {
		/* Future24 owns its own durable mutate() replay ledger. Continuity mutations
		 * intentionally use this cross-cutting HTTP guard so every write has one
		 * explicit Idempotency-Key, uniform abuse control, and mutation-status path. */
		if ( 0 === strpos( $route, '/wca/v1/future24/' ) ) { return false; }
		$patterns = array(
			'#^/wca/v1/(?:clinics|branches|services|availability|slot-holds|appointments|complaints)$#',
			'#^/wca/v1/clinic-refs/[0-9a-fA-F-]{36}/(?:submit-review|activate)$#',
			'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents)$#',
			'#^/wca/v1/continuity/appointments/[0-9a-fA-F-]{36}/(?:intake(?:/submit)?|consents|followups)$#',
			'#^/wca/v1/continuity/followups/[0-9a-fA-F-]{36}/complete$#',
			'#^/wca/v1/clinics/[0-9]+/(?:submit-review|activate)$#',
			'#^/wca/v1/appointments/[0-9]+/(?:transitions|payment-intents)$#',
		);
		foreach ( $patterns as $pattern ) { if ( preg_match( $pattern, $route ) ) { return true; } }
		return false;
	}

	private static function branch_projection_event( $request_data, $response_data ) {
		$clinic = WCA_Repository::get_clinic( absint( $request_data['clinic_id'] ?? 0 ), false );
		$branch_ref = is_array( $response_data ) ? sanitize_text_field( $response_data['public_ref'] ?? '' ) : '';
		if ( ! $clinic || ! $branch_ref ) { return; }
		$trace = WCA_Observability::trace_id();
		$payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'clinic_ref' => (string) $clinic['public_ref'], 'branch_ref' => $branch_ref, 'change' => 'branch_created', 'trace_id' => $trace );
		WCA_Repository::append_event( 'ClinicBranchChanged.v1', 'branch', $branch_ref, $payload, get_current_user_id(), $trace );
		WCA_Repository::enqueue( 'ClinicBranchChanged.v1', $branch_ref, $payload, $trace );
		WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', (string) $clinic['public_ref'], array( 'entity' => 'clinic', 'entity_ref' => (string) $clinic['public_ref'], 'change' => 'branch_created', 'trace_id' => $trace ), $trace );
	}

	public static function legacy_shortcodes() {
		if ( (bool) apply_filters( 'wca_allow_legacy_numeric_browser_actions', false ) ) { return; }
		remove_shortcode( 'swc_request_appointment' );
		remove_shortcode( 'swc_my_appointments' );
		remove_shortcode( 'swc_doctor_appointments' );
		remove_shortcode( 'swc_doctor_availability' );
		add_shortcode( 'swc_request_appointment', array( __CLASS__, 'legacy_booking_notice' ) );
		add_shortcode( 'swc_my_appointments', array( __CLASS__, 'legacy_appointments_notice' ) );
		add_shortcode( 'swc_doctor_appointments', array( __CLASS__, 'legacy_appointments_notice' ) );
		add_shortcode( 'swc_doctor_availability', array( __CLASS__, 'legacy_dashboard_notice' ) );
	}

	private static function legacy_mutation_actions() {
		return array( 'swc_submit_appointment', 'swc_patient_cancel', 'swc_patient_accept_reschedule', 'swc_patient_accept_reassignment', 'swc_patient_decline_reassignment', 'swc_doctor_update', 'swc_save_availability' );
	}

	public static function block_legacy_browser_mutation() {
		if ( (bool) apply_filters( 'wca_allow_legacy_numeric_browser_actions', false ) ) { return; }
		wp_die( esc_html__( 'This legacy appointment action is disabled. Use the current File 08 clinic and appointment interface.', 'worldwide-clinic-appointments' ), esc_html__( 'Legacy workflow disabled', 'worldwide-clinic-appointments' ), array( 'response' => 410 ) );
	}

	public static function legacy_booking_notice() { return self::migration_notice( __( 'Choose a verified clinic and use its current appointment booking page.', 'worldwide-clinic-appointments' ), home_url( '/appointments/' ) ); }
	public static function legacy_appointments_notice() { return self::migration_notice( __( 'Use the current opaque-reference appointment workspace.', 'worldwide-clinic-appointments' ), home_url( '/appointments/' ) ); }
	public static function legacy_dashboard_notice() { return self::migration_notice( __( 'Use the current clinic dashboard for availability and scheduling.', 'worldwide-clinic-appointments' ), home_url( '/clinic/dashboard/' ) ); }

	private static function migration_notice( $message, $url ) {
		return '<div class="wca-shell"><div class="wca-alert wca-alert-info" role="status">' . esc_html( $message ) . ' <a class="wca-button" href="' . esc_url( $url ) . '">' . esc_html__( 'Continue', 'worldwide-clinic-appointments' ) . '</a></div></div>';
	}
}
