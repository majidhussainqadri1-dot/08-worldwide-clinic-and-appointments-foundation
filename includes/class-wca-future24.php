<?php
/**
 * File 08 Future Clinic Intelligence & Interoperability 24.
 *
 * Implements the 24 approved future capabilities as an additive File 08
 * scheduling/continuity extension. Clinical diagnosis/prescribing, message or
 * call transport, notification delivery, identity/verification, global shell,
 * visual-token ownership and public ranking remain with their canonical owners.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Future24 {
	const CONTRACT_VERSION = '1.0.0';
	const SCHEMA_VERSION   = '1.0.0';
	const SCHEMA_OPTION    = 'wca_future24_schema_version';
	const POLICY_VERSION   = '2026-08-10.1';
	const MAX_PAYLOAD      = 16384;
	const MIN_FORECAST_N   = 20;

	/** @return array<string,string> */
	public static function tables() {
		global $wpdb;
		return array(
			'records' => $wpdb->prefix . 'wca_future24_records',
		);
	}

	/** @return array<string,array<string,mixed>> */
	public static function capabilities() {
		return array(
			'F08-FUT-01' => array( 'slug' => 'smart_waitlist', 'title' => 'Smart Cancellation Waitlist', 'priority' => 'P0', 'automation' => 'offer_only' ),
			'F08-FUT-02' => array( 'slug' => 'flexible_request_windows', 'title' => 'Flexible Appointment Request Windows', 'priority' => 'P0', 'automation' => 'suggest_only' ),
			'F08-FUT-03' => array( 'slug' => 'recurring_series', 'title' => 'Recurring / Series Appointments', 'priority' => 'P0', 'automation' => 'intent_only' ),
			'F08-FUT-04' => array( 'slug' => 'multi_resource_scheduling', 'title' => 'Multi-Resource Scheduling', 'priority' => 'P0', 'automation' => 'atomic_reservation' ),
			'F08-FUT-05' => array( 'slug' => 'group_capacity', 'title' => 'Capacity-Based / Group Appointment Mode', 'priority' => 'P1', 'automation' => 'capacity_guarded' ),
			'F08-FUT-06' => array( 'slug' => 'safe_reschedule', 'title' => 'One-Tap Safe Reschedule', 'priority' => 'P0', 'automation' => 'compensation_safe' ),
			'F08-FUT-07' => array( 'slug' => 'smart_buffers', 'title' => 'Smart Buffer & Transition Rules', 'priority' => 'P0', 'automation' => 'manager_configured' ),
			'F08-FUT-08' => array( 'slug' => 'capacity_heatmap', 'title' => 'Availability Capacity Heatmap', 'priority' => 'P1', 'automation' => 'aggregate_only' ),
			'F08-FUT-09' => array( 'slug' => 'schedule_advisor', 'title' => 'Schedule Optimization Advisor', 'priority' => 'P1', 'automation' => 'advisory_only' ),
			'F08-FUT-10' => array( 'slug' => 'aggregate_no_show_forecast', 'title' => 'Privacy-Safe No-Show Forecasting', 'priority' => 'P2', 'automation' => 'aggregate_only' ),
			'F08-FUT-11' => array( 'slug' => 'dynamic_previsit_questionnaire', 'title' => 'Structured Dynamic Pre-Visit Questionnaire', 'priority' => 'P0', 'automation' => 'template_only' ),
			'F08-FUT-12' => array( 'slug' => 'readiness_center', 'title' => 'Appointment Readiness Center', 'priority' => 'P0', 'automation' => 'status_only' ),
			'F08-FUT-13' => array( 'slug' => 'prerequisite_rules', 'title' => 'Prerequisite & Document Rules', 'priority' => 'P1', 'automation' => 'policy_configured' ),
			'F08-FUT-14' => array( 'slug' => 'family_guardian_hub', 'title' => 'Family / Guardian Appointment Hub', 'priority' => 'P0', 'automation' => 'none' ),
			'F08-FUT-15' => array( 'slug' => 'digital_checkin_queue', 'title' => 'Digital Check-In & Arrival Queue', 'priority' => 'P1', 'automation' => 'arrival_only' ),
			'F08-FUT-16' => array( 'slug' => 'privacy_safe_queue_position', 'title' => 'Privacy-Preserving Live Queue Position', 'priority' => 'P1', 'automation' => 'aggregate_only' ),
			'F08-FUT-17' => array( 'slug' => 'disruption_recovery', 'title' => 'Doctor Delay / Clinic Disruption State', 'priority' => 'P0', 'automation' => 'rebook_offer_only' ),
			'F08-FUT-18' => array( 'slug' => 'support_interpreter_participant', 'title' => 'Consultation Support Person / Interpreter Role', 'priority' => 'P1', 'automation' => 'none' ),
			'F08-FUT-19' => array( 'slug' => 'virtual_room_context', 'title' => 'Secure Virtual-Room Provisioning Contract', 'priority' => 'P0', 'automation' => 'request_only' ),
			'F08-FUT-20' => array( 'slug' => 'fhir_adapter', 'title' => 'FHIR Interoperability Adapter', 'priority' => 'P1', 'automation' => 'projection_only' ),
			'F08-FUT-21' => array( 'slug' => 'smart_scheduling_links', 'title' => 'SMART Scheduling Links Compatibility', 'priority' => 'P1', 'automation' => 'find_hold_book' ),
			'F08-FUT-22' => array( 'slug' => 'external_calendar_reconciliation', 'title' => 'External Calendar Two-Way Reconciliation', 'priority' => 'P1', 'automation' => 'busy_only' ),
			'F08-FUT-23' => array( 'slug' => 'clinical_episode_chain', 'title' => 'Clinical Episode / Follow-Up Chain', 'priority' => 'P1', 'automation' => 'link_only' ),
			'F08-FUT-24' => array( 'slug' => 'governance_layer', 'title' => 'Appointment Intelligence & Interoperability Governance Layer', 'priority' => 'P0', 'automation' => 'governed' ),
		);
	}

	public static function boot() {
		self::maybe_upgrade();
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ), 80 );
		add_shortcode( 'wca_future24_center', array( __CLASS__, 'shortcode_center' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 40 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_route_assets' ), 60 );
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'enforce_slot_hold_policy_pre_dispatch' ), 30, 3 );
		add_filter( 'rest_post_dispatch', array( __CLASS__, 'apply_slot_policies_to_rest' ), 30, 3 );
		add_action( 'wca_outbox_event', array( __CLASS__, 'observe_outbox_event' ), 30, 1 );
		add_action( WCA_Outbox::MAINTENANCE_HOOK, array( __CLASS__, 'maintenance' ), 30 );
	}

	public static function activate() { self::install_schema(); }

	public static function maybe_upgrade() {
		if ( self::SCHEMA_VERSION !== (string) get_option( self::SCHEMA_OPTION, '' ) ) {
			self::install_schema();
		}
	}

	public static function install_schema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::tables()['records'];
		$collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			public_ref char(36) NOT NULL,
			feature_id varchar(20) NOT NULL,
			clinic_id bigint(20) unsigned NOT NULL DEFAULT 0,
			appointment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			subject_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			parent_ref varchar(80) NOT NULL DEFAULT '',
			status varchar(32) NOT NULL DEFAULT 'active',
			starts_at datetime NULL,
			ends_at datetime NULL,
			capacity int(10) unsigned NOT NULL DEFAULT 0,
			version bigint(20) unsigned NOT NULL DEFAULT 1,
			payload_json longtext NOT NULL,
			expires_at datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY public_ref (public_ref),
			KEY feature_status (feature_id,status),
			KEY clinic_feature (clinic_id,feature_id,status),
			KEY appointment_feature (appointment_id,feature_id,status),
			KEY parent_feature (parent_ref,feature_id,status),
			KEY time_window (starts_at,ends_at,status),
			KEY subject_feature (subject_user_id,feature_id,status)
		) {$collate};";
		dbDelta( $sql );
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	public static function register_assets() {
		wp_register_style( 'wca-future24', WCA_URL . 'assets/css/future24.css', array( 'wca-clinic' ), WCA_VERSION );
		wp_register_script( 'wca-future24', WCA_URL . 'assets/js/future24.js', array(), WCA_VERSION, true );
	}

	private static function enqueue_assets() {
		wp_enqueue_style( 'wca-future24' );
		wp_enqueue_script( 'wca-future24' );
		wp_localize_script( 'wca-future24', 'WCAFuture24', array(
			'root'  => esc_url_raw( rest_url( 'wca/v1/future24/' ) ),
			'baseRest' => esc_url_raw( rest_url( 'wca/v1/' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
		) );
	}

	public static function enqueue_route_assets() {
		if ( ! is_user_logged_in() || ! class_exists( 'WCA_Routes' ) ) { return; }
		if ( in_array( WCA_Routes::route(), array( 'appointments', 'appointment', 'book', 'dashboard' ), true ) ) { self::enqueue_assets(); }
	}

	public static function shortcode_center( $atts ) {
		if ( ! is_user_logged_in() ) { return ''; }
		$atts = shortcode_atts( array( 'appointment' => '' ), $atts, 'wca_future24_center' );
		$ref = strtolower( sanitize_text_field( $atts['appointment'] ) );
		self::enqueue_assets();
		return '<section class="wca-card wca-future24" data-wca-f24-center data-appointment-ref="' . esc_attr( $ref ) . '"><h2>' . esc_html__( 'Appointment intelligence center', 'worldwide-clinic-appointments' ) . '</h2><p>' . esc_html__( 'Scheduling, readiness, family, queue and interoperability tools. This surface does not diagnose or prescribe.', 'worldwide-clinic-appointments' ) . '</p><div data-wca-f24-readiness aria-live="polite"></div><div data-wca-f24-family aria-live="polite"></div><p data-wca-status role="status" aria-live="polite"></p></section>';
	}

	public static function register_routes() {
		register_rest_route( 'wca/v1', '/future24/manifest', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_manifest' ), 'permission_callback' => '__return_true' ) );
		$auth = array( __CLASS__, 'authorize_rest_request' );
		$routes = array(
			'/future24/waitlist' => array( 'POST', 'rest_waitlist' ),
			'/future24/windows' => array( 'POST', 'rest_windows' ),
			'/future24/series' => array( 'POST', 'rest_series' ),
			'/future24/resources' => array( 'POST', 'rest_resource' ),
			'/future24/resources/(?P<ref>[0-9a-fA-F-]{36})/reserve' => array( 'POST', 'rest_resource_reserve' ),
			'/future24/group-sessions' => array( 'POST', 'rest_group_session' ),
			'/future24/group-sessions/(?P<ref>[0-9a-fA-F-]{36})/join' => array( 'POST', 'rest_group_join' ),
			'/future24/group-sessions/(?P<ref>[0-9a-fA-F-]{36})/leave' => array( 'POST', 'rest_group_leave' ),
			'/future24/group-sessions/(?P<ref>[0-9a-fA-F-]{36})/cancel' => array( 'POST', 'rest_group_cancel' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/safe-reschedule' => array( 'POST', 'rest_safe_reschedule' ),
			'/future24/buffers' => array( 'POST', 'rest_buffers' ),
			'/future24/heatmap' => array( 'GET', 'rest_heatmap' ),
			'/future24/advisor' => array( 'GET', 'rest_advisor' ),
			'/future24/no-show-forecast' => array( 'GET', 'rest_no_show' ),
			'/future24/questionnaires' => array( 'POST', 'rest_questionnaire' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/questionnaire' => array( 'GET', 'rest_questionnaire_for_appointment' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/readiness' => array( 'GET', 'rest_readiness' ),
			'/future24/prerequisites' => array( 'POST', 'rest_prerequisites' ),
			'/future24/family' => array( 'GET', 'rest_family' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/arrive' => array( 'POST', 'rest_arrive' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/queue' => array( 'GET', 'rest_queue' ),
			'/future24/disruptions' => array( 'POST', 'rest_disruption' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/participants' => array( 'POST', 'rest_participant' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/participants/(?P<participant>[0-9a-fA-F-]{36})/revoke' => array( 'POST', 'rest_participant_revoke' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/virtual-room' => array( 'POST', 'rest_virtual_room' ),
			'/future24/fhir/(?P<type>[a-z]+)/(?P<ref>[0-9a-fA-F-]{36})' => array( 'GET', 'rest_fhir' ),
			'/future24/smart/find' => array( 'GET', 'rest_smart_find' ),
			'/future24/smart/hold' => array( 'POST', 'rest_smart_hold' ),
			'/future24/smart/book' => array( 'POST', 'rest_smart_book' ),
			'/future24/external-calendar/busy' => array( 'POST', 'rest_external_busy' ),
			'/future24/episodes' => array( 'POST', 'rest_episode' ),
			'/future24/governance' => array( 'GET', 'rest_governance' ),
		);
		foreach ( $routes as $path => $definition ) {
			register_rest_route( 'wca/v1', $path, array(
				'methods'             => 'GET' === $definition[0] ? WP_REST_Server::READABLE : WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, $definition[1] ),
				'permission_callback' => $auth,
			) );
		}
	}

	public static function authenticated() {
		return is_user_logged_in() ? true : new WP_Error( 'wca_auth_required', __( 'Authentication is required.', 'worldwide-clinic-appointments' ), array( 'status' => 401 ) );
	}


	/** Every protected Future24 REST request is authenticated and rate-limited before its callback runs. */
	public static function authorize_rest_request( WP_REST_Request $request ) {
		$auth = self::authenticated();
		if ( is_wp_error( $auth ) ) { return $auth; }
		$method = strtoupper( (string) $request->get_method() );
		$route  = (string) $request->get_route();
		$scope  = 'rest_' . substr( hash( 'sha256', $method . '|' . $route ), 0, 20 );
		$write  = in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true );
		return self::rate( $scope, $write ? 90 : 240, HOUR_IN_SECONDS );
	}

	/** Resolve patient/guardian scheduling intent and recheck File 00 age/guardian truth. */
	private static function patient_context( $data, $actor = 0 ) {
		$actor = absint( $actor ?: get_current_user_id() );
		$patient_id  = absint( isset( $data['patient_user_id'] ) ? $data['patient_user_id'] : $actor );
		$guardian_id = absint( isset( $data['guardian_user_id'] ) ? $data['guardian_user_id'] : 0 );
		$guard = WCA_Authorization::guardian_context( $patient_id, $guardian_id, $actor );
		if ( is_wp_error( $guard ) ) { return $guard; }
		return array( 'patient_user_id' => $patient_id, 'guardian_user_id' => $guardian_id, 'actor_user_id' => $actor );
	}

	/** Validate that an optional service reference belongs to the selected clinic. */
	private static function service_for_clinic( $clinic_id, $service_ref ) {
		$service_ref = strtolower( sanitize_text_field( $service_ref ) );
		if ( '' === $service_ref ) { return null; }
		$service = WCA_Repository::get_service_by_ref( $service_ref, true );
		if ( ! $service || absint( $service['clinic_id'] ) !== absint( $clinic_id ) ) {
			return new WP_Error( 'wca_future24_service_scope', __( 'The selected service does not belong to this active clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		return $service;
	}


	/** Validate an optional public branch reference against the selected clinic. */
	private static function branch_for_clinic( $clinic_id, $branch_ref ) {
		$branch_ref = strtolower( sanitize_text_field( $branch_ref ) );
		if ( '' === $branch_ref ) { return null; }
		foreach ( WCA_Repository::list_branches( absint( $clinic_id ), false ) as $branch ) {
			if ( ! empty( $branch['public_ref'] ) && hash_equals( strtolower( (string) $branch['public_ref'] ), $branch_ref ) ) { return $branch; }
		}
		return new WP_Error( 'wca_future24_branch_scope', __( 'The selected branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
	}

	/** Recheck current guardian relationship independently of the viewer role. */
	private static function current_guardian_state( $patient_id, $guardian_id ) {
		if ( ! class_exists( 'WCA_Central_Governance' ) ) {
			return new WP_Error( 'wca_guardian_recheck_unavailable', __( 'Current guardian eligibility could not be rechecked.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$claim = WCA_Central_Governance::age_guardian_claim( absint( $patient_id ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( empty( $claim['guardian_required'] ) ) { return true; }
		$guardian_id = absint( $guardian_id );
		if ( ! $guardian_id ) { return new WP_Error( 'wca_guardian_required', __( 'A current verified guardian is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		return WCA_Central_Governance::validate_patient_guardian( absint( $patient_id ), $guardian_id, $guardian_id );
	}

	public static function rest_manifest() {
		return self::respond( array(
			'contract' => 'wca.future24-manifest',
			'version' => self::CONTRACT_VERSION,
			'schema_version' => self::SCHEMA_VERSION,
			'policy_version' => self::POLICY_VERSION,
			'capabilities' => self::capabilities(),
			'clinical_diagnosis' => false,
			'automated_prescribing' => false,
			'message_transport_owner' => 'File17',
			'notification_delivery_owner' => 'File19',
			'search_ranking_owner' => 'File26',
		) );
	}

	private static function data( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		$data = is_array( $data ) ? $data : $request->get_params();
		$key = trim( (string) $request->get_header( 'Idempotency-Key' ) );
		if ( $key ) { $data['idempotency_key'] = $key; }
		return $data;
	}

	/** Execute one protected Future24 mutation with a durable replay key. */
	private static function mutate( WP_REST_Request $request, $scope, $method, $args = array(), $status = 200 ) {
		$key = trim( (string) $request->get_header( 'Idempotency-Key' ) );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $key ) ) {
			return new WP_Error( 'wca_idempotency_required', __( 'A valid Idempotency-Key header is required for this scheduling mutation.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$actor = get_current_user_id();
		$fingerprint = array(
			'route' => (string) $request->get_route(),
			'url' => (array) $request->get_url_params(),
			'query' => (array) $request->get_query_params(),
			'body' => (array) ( is_array( $request->get_json_params() ) ? $request->get_json_params() : array() ),
		);
		$claim = WCA_Repository::claim_idempotency( 'future24_' . sanitize_key( $scope ), $key, $actor, $fingerprint );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) {
			return self::respond( isset( $claim['response'] ) ? $claim['response'] : array(), absint( $claim['response_code'] ?? $status ) ?: $status );
		}
		if ( empty( $claim['claimed_new'] ) ) {
			return new WP_Error( 'wca_idempotency_in_progress', __( 'This scheduling mutation is already being processed. Retry with the same Idempotency-Key shortly.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) );
		}
		$result = call_user_func_array( array( __CLASS__, $method ), (array) $args );
		if ( is_wp_error( $result ) ) {
			WCA_Repository::release_idempotency( $claim['id'] );
			return $result;
		}
		WCA_Repository::complete_idempotency( $claim['id'], $status, $result );
		return self::respond( $result, $status );
	}

	private static function respond( $result, $status = 200 ) {
		if ( is_wp_error( $result ) ) { return $result; }
		$response = rest_ensure_response( $result );
		$response->set_status( $status );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	private static function rate( $scope, $limit = 60, $window = 60 ) {
		return SWC_Helpers::rate_limit_hit( 'future24_' . sanitize_key( $scope ), get_current_user_id(), $limit, $window )
			? new WP_Error( 'wca_rate_limit', __( 'Too many requests. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => $window ) )
			: true;
	}

	/** Acquire a short MySQL advisory lock for semantic de-duplication across different replay keys. */
	private static function semantic_lock( $scope, $identity ) {
		global $wpdb;
		$lock = 'wca-f24-' . sanitize_key( $scope ) . '-' . substr( hash( 'sha256', (string) $identity ), 0, 32 );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) {
			return new WP_Error( 'wca_future24_busy', __( 'This scheduling operation is already being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		return $lock;
	}

	private static function release_semantic_lock( $lock ) {
		global $wpdb;
		if ( is_string( $lock ) && '' !== $lock ) { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
	}

	/** Generic operational record writer. Never stores clinical narrative. */
	private static function put_record( $feature_id, $data, $actor_user_id = 0 ) {
		global $wpdb;
		$feature_id = strtoupper( sanitize_text_field( $feature_id ) );
		if ( ! isset( self::capabilities()[ $feature_id ] ) ) {
			return new WP_Error( 'wca_future24_feature', __( 'Unknown future capability.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$payload = self::sanitize_operational_payload( isset( $data['payload'] ) && is_array( $data['payload'] ) ? $data['payload'] : array(), true );
		if ( is_wp_error( $payload ) ) { return $payload; }
		$json = wp_json_encode( $payload );
		if ( ! is_string( $json ) || strlen( $json ) > self::MAX_PAYLOAD ) {
			return new WP_Error( 'wca_future24_payload', __( 'Operational payload is too large.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) );
		}
		$now = WCA_Repository::now();
		$row = array(
			'public_ref' => WCA_Repository::uuid(),
			'feature_id' => $feature_id,
			'clinic_id' => absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 ),
			'appointment_id' => absint( isset( $data['appointment_id'] ) ? $data['appointment_id'] : 0 ),
			'actor_user_id' => $actor_user_id,
			'subject_user_id' => absint( isset( $data['subject_user_id'] ) ? $data['subject_user_id'] : 0 ),
			'parent_ref' => sanitize_text_field( isset( $data['parent_ref'] ) ? $data['parent_ref'] : '' ),
			'status' => sanitize_key( isset( $data['status'] ) ? $data['status'] : 'active' ),
			'starts_at' => self::utc( isset( $data['starts_at'] ) ? $data['starts_at'] : '' ),
			'ends_at' => self::utc( isset( $data['ends_at'] ) ? $data['ends_at'] : '' ),
			'capacity' => min( 10000, absint( isset( $data['capacity'] ) ? $data['capacity'] : 0 ) ),
			'version' => 1,
			'payload_json' => $json,
			'expires_at' => self::utc( isset( $data['expires_at'] ) ? $data['expires_at'] : '' ),
			'created_at' => $now,
			'updated_at' => $now,
		);
		if ( false === $wpdb->insert( self::tables()['records'], $row ) ) {
			return new WP_Error( 'wca_future24_store', __( 'The scheduling record could not be stored.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
		}
		$record_id = (int) $wpdb->insert_id;
		$audit = self::audit( $feature_id, 'record_created', $row['public_ref'], array( 'status' => $row['status'] ), $row['actor_user_id'], false );
		if ( is_wp_error( $audit ) ) {
			$wpdb->delete( self::tables()['records'], array( 'id' => $record_id ) );
			return $audit;
		}
		return self::public_record( array_merge( $row, array( 'id' => $record_id ) ) );
	}

	private static function put_system_record( $feature_id, $data ) {
		global $wpdb;
		if ( ! isset( self::capabilities()[ $feature_id ] ) ) { return new WP_Error( 'wca_future24_feature', __( 'Unsupported Future24 capability.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$table = self::tables()['records'];
		$payload = self::sanitize_operational_payload( isset( $data['payload'] ) ? $data['payload'] : array(), true );
		if ( is_wp_error( $payload ) ) { return $payload; }
		$now = WCA_Repository::now();
		$row = array(
			'public_ref' => WCA_Repository::uuid(),
			'feature_id' => $feature_id,
			'clinic_id' => absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 ),
			'appointment_id' => absint( isset( $data['appointment_id'] ) ? $data['appointment_id'] : 0 ),
			'actor_user_id' => 0,
			'subject_user_id' => absint( isset( $data['subject_user_id'] ) ? $data['subject_user_id'] : 0 ),
			'parent_ref' => strtolower( sanitize_text_field( isset( $data['parent_ref'] ) ? $data['parent_ref'] : '' ) ),
			'status' => sanitize_key( isset( $data['status'] ) ? $data['status'] : 'active' ),
			'starts_at' => self::utc( isset( $data['starts_at'] ) ? $data['starts_at'] : '' ) ?: null,
			'ends_at' => self::utc( isset( $data['ends_at'] ) ? $data['ends_at'] : '' ) ?: null,
			'capacity' => min( 1000, absint( isset( $data['capacity'] ) ? $data['capacity'] : 0 ) ),
			'version' => 1,
			'payload_json' => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ),
			'expires_at' => self::utc( isset( $data['expires_at'] ) ? $data['expires_at'] : '' ) ?: null,
			'created_at' => $now,
			'updated_at' => $now,
		);
		if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_future24_system_insert', __( 'The Future24 system record could not be stored.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$record_id = (int) $wpdb->insert_id;
		$audit = self::audit( $feature_id, 'system_record_created', $row['public_ref'], array( 'status' => $row['status'], 'system_actor' => true ), 0, false );
		if ( is_wp_error( $audit ) ) {
			$wpdb->delete( $table, array( 'id' => $record_id ) );
			return $audit;
		}
		return self::public_record( array_merge( array( 'id' => $record_id ), $row ) );
	}

	public static function observe_outbox_event( $envelope ) {
		if ( ! is_array( $envelope ) || 'AppointmentCancelled.v1' !== (string) ( isset( $envelope['topic'] ) ? $envelope['topic'] : '' ) ) { return; }
		self::offer_waitlist_for_cancelled_appointment( sanitize_text_field( isset( $envelope['aggregate_ref'] ) ? $envelope['aggregate_ref'] : '' ) );
	}


	/** Iterate every active waitlist entry in stable bounded pages so an eligible patient beyond an arbitrary first page is not starved. */
	private static function waitlist_candidates( $clinic_id ) {
		global $wpdb;
		$table = self::tables()['records'];
		$cursor = 0;
		$batch = 100;
		do {
			$rows = (array) $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) AND id>%d ORDER BY id ASC LIMIT %d",
				absint( $clinic_id ), WCA_Repository::now(), $cursor, $batch
			), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $rows as $row ) {
				$cursor = max( $cursor, absint( $row['id'] ?? 0 ) );
				yield $row;
			}
		} while ( count( $rows ) === $batch );
	}

	private static function offer_waitlist_for_cancelled_appointment( $appointment_ref ) {
		global $wpdb;
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return 0; }
		$clinic_id = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) );
		$service_id = absint( SWC_Helpers::meta( $appointment_id, 'service_id', 0 ) );
		$start = self::utc( SWC_Helpers::meta( $appointment_id, 'preferred_at_utc', '' ) );
		$end = self::utc( SWC_Helpers::meta( $appointment_id, 'appointment_end_utc', '' ) );
		if ( ! $clinic_id || ! $start || ! $end ) { return 0; }
		$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;
		$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';
		$table = self::tables()['records'];
		$offered = 0;
		$slot_date = substr( $start, 0, 10 );
		foreach ( self::waitlist_candidates( $clinic_id ) as $wait ) {
			$payload = json_decode( (string) $wait['payload_json'], true );
			$payload = is_array( $payload ) ? $payload : array();
			$wanted_service = strtolower( sanitize_text_field( isset( $payload['service_ref'] ) ? $payload['service_ref'] : '' ) );
			if ( $wanted_service && $service_ref && ! hash_equals( $wanted_service, $service_ref ) ) { continue; }
			$date_from = sanitize_text_field( isset( $payload['date_from'] ) ? $payload['date_from'] : '' );
			$date_to = sanitize_text_field( isset( $payload['date_to'] ) ? $payload['date_to'] : '' );
			if ( $date_from && $slot_date < $date_from ) { continue; }
			if ( $date_to && $slot_date > $date_to ) { continue; }
			$patient_id = absint( $wait['subject_user_id'] );
			$recipient_id = absint( isset( $wait['actor_user_id'] ) ? $wait['actor_user_id'] : $patient_id );
			if ( $recipient_id !== $patient_id ) {
				$guardian_state = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_id, $recipient_id, $recipient_id ) : new WP_Error( 'wca_guardian_recheck_unavailable', 'unavailable' );
				if ( is_wp_error( $guardian_state ) ) { continue; }
			} else {
				$self_state = self::current_guardian_state( $patient_id, 0 );
				if ( is_wp_error( $self_state ) ) { continue; }
			}
			$recipient_claims = WCA_Authorization::claims( $recipient_id );
			if ( is_wp_error( $recipient_claims ) ) { continue; }
			$offer_lock = self::semantic_lock( 'waitlist-offer', (string) $wait['public_ref'] . '|' . $start . '|' . $end );
			if ( is_wp_error( $offer_lock ) ) { continue; }
			try {
				$wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$duplicate = $wpdb->get_var( $wpdb->prepare( "SELECT public_ref FROM {$table} WHERE feature_id='F08-FUT-01' AND parent_ref=%s AND status='offer_pending' AND starts_at=%s AND ends_at=%s AND (expires_at IS NULL OR expires_at>%s) LIMIT 1", (string) $wait['public_ref'], $start, $end, WCA_Repository::now() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( $duplicate ) { $wpdb->query( 'COMMIT' ); continue; } // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$offer = self::put_system_record( 'F08-FUT-01', array(
					'clinic_id' => $clinic_id,
					'subject_user_id' => absint( $wait['subject_user_id'] ),
					'parent_ref' => (string) $wait['public_ref'],
					'status' => 'offer_pending',
					'starts_at' => $start,
					'ends_at' => $end,
					'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 15 * MINUTE_IN_SECONDS ),
					'payload' => array( 'service_ref' => $service_ref, 'released_slot' => true, 'auto_book' => false, 'confirmation_required' => true ),
				) );
				if ( is_wp_error( $offer ) ) { $wpdb->query( 'ROLLBACK' ); continue; } // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$queued = WCA_Repository::enqueue( 'File19.NotificationRequested.v1', (string) $offer['public_ref'], array(
					'event' => 'waitlist_offer_available',
					'appointment_ref' => '',
					'recipients' => array( $recipient_id ),
					'offer_ref' => (string) $offer['public_ref'],
					'delivery_owner' => 'File19',
				), WCA_Observability::trace_id() );
				if ( is_wp_error( $queued ) ) { $wpdb->query( 'ROLLBACK' ); continue; } // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$offered++;
			} finally {
				self::release_semantic_lock( $offer_lock );
			}
			if ( $offered >= 10 ) { break; }
		}
		return $offered;
	}

	private static function sanitize_operational_payload( $payload, $strict = false ) {
		$payload = (array) $payload;
		if ( $strict && count( $payload ) > 80 ) {
			return new WP_Error( 'wca_future24_payload_keys', __( 'Operational payload contains too many fields.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) );
		}
		$out = array();
		$blocked = '/(diagnos|prescri|symptom|reason|clinical_note|private_note|intake_answer|patient_name|email|phone|whatsapp|address_private|treatment|remedy)/i';
		$source = $strict ? $payload : array_slice( $payload, 0, 80, true );
		foreach ( $source as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! $key || preg_match( $blocked, $key ) ) { continue; }
			if ( is_bool( $value ) ) { $out[ $key ] = $value; continue; }
			if ( is_int( $value ) || is_float( $value ) ) { $out[ $key ] = $value; continue; }
			if ( is_string( $value ) ) { $out[ $key ] = substr( sanitize_text_field( $value ), 0, 1000 ); continue; }
			if ( is_array( $value ) ) {
				if ( $strict && count( $value ) > 50 ) {
					return new WP_Error( 'wca_future24_payload_items', __( 'An operational payload list contains too many items.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) );
				}
				$items = array();
				foreach ( $strict ? $value : array_slice( $value, 0, 50 ) as $item ) {
					if ( is_scalar( $item ) ) { $items[] = substr( sanitize_text_field( (string) $item ), 0, 500 ); }
				}
				$out[ $key ] = $items;
			}
		}
		return $out;
	}

	private static function public_record( $row ) {
		$payload = json_decode( isset( $row['payload_json'] ) ? (string) $row['payload_json'] : '{}', true );
		return array(
			'public_ref' => (string) $row['public_ref'],
			'feature_id' => (string) $row['feature_id'],
			'parent_ref' => (string) $row['parent_ref'],
			'status' => (string) $row['status'],
			'starts_at_utc' => (string) $row['starts_at'],
			'ends_at_utc' => (string) $row['ends_at'],
			'capacity' => absint( $row['capacity'] ),
			'payload' => is_array( $payload ) ? $payload : array(),
			'expires_at_utc' => (string) $row['expires_at'],
			'version' => absint( $row['version'] ),
			'updated_at_utc' => (string) $row['updated_at'],
		);
	}

	private static function get_record( $ref, $feature_id = '' ) {
		global $wpdb;
		$table = self::tables()['records'];
		$ref = strtolower( sanitize_text_field( $ref ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) { return null; }
		if ( $feature_id ) {
			return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s AND feature_id=%s LIMIT 1", $ref, $feature_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", $ref ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function appointment_id( $ref ) {
		$ids = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 2, 'no_found_rows' => true, 'meta_key' => '_swc_public_ref', 'meta_value' => strtolower( sanitize_text_field( $ref ) ) ) );
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private static function appointment_ref( $id ) { return strtolower( (string) SWC_Helpers::meta( absint( $id ), 'public_ref', '' ) ); }

	private static function require_appointment( $ref, $actor = 0 ) {
		$id = self::appointment_id( $ref );
		if ( ! $id ) { return new WP_Error( 'wca_appointment_not_found', __( 'Appointment was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$access = WCA_Authorization::can_view_appointment( $id, absint( $actor ?: get_current_user_id() ) );
		return is_wp_error( $access ) ? $access : $id;
	}

	private static function require_clinic_manager( $clinic_id, $actor = 0 ) {
		$clinic = WCA_Repository::get_clinic( absint( $clinic_id ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, absint( $actor ?: get_current_user_id() ) );
		return is_wp_error( $auth ) ? $auth : $clinic;
	}

	private static function utc( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		if ( '' === $value ) { return null; }
		$utc = new DateTimeZone( 'UTC' );
		$formats = array(
			array( '!Y-m-d H:i:s', 'Y-m-d H:i:s', $utc ),
			array( '!Y-m-d H:i', 'Y-m-d H:i', $utc ),
			array( '!Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s\Z', $utc ),
			array( '!Y-m-d\TH:i\Z', 'Y-m-d\TH:i\Z', $utc ),
			array( '!Y-m-d\TH:i:sP', 'Y-m-d\TH:i:sP', null ),
			array( '!Y-m-d\TH:iP', 'Y-m-d\TH:iP', null ),
		);
		foreach ( $formats as $entry ) {
			$dt = null === $entry[2] ? DateTimeImmutable::createFromFormat( $entry[0], $value ) : DateTimeImmutable::createFromFormat( $entry[0], $value, $entry[2] );
			$errors = DateTimeImmutable::getLastErrors();
			if ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) {
				return $dt->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
			}
		}
		return null;
	}

	private static function audit( $feature_id, $action, $object_ref, $context = array(), $actor_user_id = 0, $automated = false ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$payload = array(
			'feature_id' => $feature_id,
			'action' => sanitize_key( $action ),
			'policy_version' => self::POLICY_VERSION,
			'automated' => (bool) $automated,
			'automated_clinical_decision' => false,
			'context' => self::sanitize_operational_payload( $context ),
		);
		if ( ! class_exists( 'WCA_Repository' ) ) {
			return new WP_Error( 'wca_future24_audit_unavailable', __( 'Future24 governance audit storage is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		return WCA_Repository::append_event( 'Future24GovernanceRecorded.v1', 'future24', sanitize_text_field( $object_ref ), $payload, $actor_user_id, WCA_Observability::trace_id() );
	}

	/* FUT-01 */
	public static function join_waitlist( $data, $actor = 0 ) {
		global $wpdb;
		$clinic_id = absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 );
		if ( ! $clinic_id || ! WCA_Repository::get_clinic( $clinic_id, true ) ) { return new WP_Error( 'wca_waitlist_clinic', __( 'An active clinic is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$context = self::patient_context( $data, $actor );
		if ( is_wp_error( $context ) ) { return $context; }
		$service_ref = strtolower( sanitize_text_field( isset( $data['service_ref'] ) ? $data['service_ref'] : '' ) );
		$service = self::service_for_clinic( $clinic_id, $service_ref );
		if ( is_wp_error( $service ) ) { return $service; }
		$date_from = sanitize_text_field( isset( $data['date_from'] ) ? $data['date_from'] : '' );
		$date_to   = sanitize_text_field( isset( $data['date_to'] ) ? $data['date_to'] : '' );
		$today = gmdate( 'Y-m-d' );
		$max_date = gmdate( 'Y-m-d', time() + 180 * DAY_IN_SECONDS );
		if ( ! WCA_Service::valid_date( $date_from ) || ! WCA_Service::valid_date( $date_to ) || $date_from < $today || $date_to < $date_from || $date_to > $max_date ) {
			return new WP_Error( 'wca_waitlist_dates', __( 'A valid future waitlist date range of no more than 180 days is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$timezone = sanitize_text_field( isset( $data['timezone'] ) ? $data['timezone'] : 'UTC' );
		if ( ! WCA_Service::valid_timezone( $timezone ) ) { return new WP_Error( 'wca_waitlist_timezone', __( 'A valid time zone is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$fingerprint = hash( 'sha256', implode( '|', array( $clinic_id, $context['patient_user_id'], $service_ref, $date_from, $date_to, $timezone ) ) );
		$table = self::tables()['records'];
		$lock = self::semantic_lock( 'waitlist', $fingerprint );
		if ( is_wp_error( $lock ) ) { return $lock; }
		try {
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND subject_user_id=%d AND parent_ref=%s AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1", $clinic_id, $context['patient_user_id'], $fingerprint, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $existing ) { return self::public_record( $existing ); }
			return self::put_record( 'F08-FUT-01', array(
				'clinic_id' => $clinic_id,
				'subject_user_id' => $context['patient_user_id'],
				'parent_ref' => $fingerprint,
				'status' => 'waiting',
				'expires_at' => gmdate( 'Y-m-d H:i:s', min( strtotime( $date_to . ' 23:59:59 UTC' ) + DAY_IN_SECONDS, time() + 181 * DAY_IN_SECONDS ) ),
				'payload' => array(
					'service_ref' => $service_ref,
					'date_from' => $date_from,
					'date_to' => $date_to,
					'timezone' => $timezone,
					'guardian_user_id' => $context['guardian_user_id'],
					'auto_book' => false,
				),
			), $context['actor_user_id'] );
		} finally {
			self::release_semantic_lock( $lock );
		}
	}

	/* FUT-02 */
	public static function save_windows( $data, $actor = 0 ) {
		global $wpdb;
		$clinic_id = absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 );
		if ( ! $clinic_id || ! WCA_Repository::get_clinic( $clinic_id, true ) ) {
			return new WP_Error( 'wca_windows_clinic', __( 'An active clinic is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$context = self::patient_context( $data, $actor );
		if ( is_wp_error( $context ) ) { return $context; }
		$service_ref = strtolower( sanitize_text_field( isset( $data['service_ref'] ) ? $data['service_ref'] : '' ) );
		$service = self::service_for_clinic( $clinic_id, $service_ref );
		if ( is_wp_error( $service ) ) { return $service; }
		$timezone = sanitize_text_field( isset( $data['timezone'] ) ? $data['timezone'] : 'UTC' );
		if ( ! WCA_Service::valid_timezone( $timezone ) ) { return new WP_Error( 'wca_windows_timezone', __( 'A valid time zone is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$raw_windows = (array) ( isset( $data['windows'] ) ? $data['windows'] : array() );
		if ( count( $raw_windows ) > 12 ) { return new WP_Error( 'wca_windows_limit', __( 'No more than 12 scheduling windows may be saved in one request.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
		$windows = array();
		$latest_end = 0;
		$now = time();
		foreach ( $raw_windows as $window ) {
			if ( ! is_array( $window ) ) { continue; }
			$start = self::utc( isset( $window['start'] ) ? $window['start'] : '' );
			$end = self::utc( isset( $window['end'] ) ? $window['end'] : '' );
			$start_ts = $start ? strtotime( $start . ' UTC' ) : 0;
			$end_ts = $end ? strtotime( $end . ' UTC' ) : 0;
			if ( $start && $end && $end > $start && $start_ts >= $now - 5 * MINUTE_IN_SECONDS && $end_ts > $now && $start_ts <= $now + 180 * DAY_IN_SECONDS && $end_ts - $start_ts <= 14 * DAY_IN_SECONDS ) {
				$windows[] = $start . '/' . $end;
				$latest_end = max( $latest_end, $end_ts );
			}
		}
		$windows = array_values( array_unique( $windows ) );
		if ( ! $windows ) { return new WP_Error( 'wca_windows_required', __( 'At least one valid future appointment window is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		sort( $windows, SORT_STRING );
		$fingerprint = hash( 'sha256', implode( '|', array( $clinic_id, $context['patient_user_id'], $service_ref, $timezone, implode( ',', $windows ) ) ) );
		$table = self::tables()['records'];
		$lock = self::semantic_lock( 'windows', $fingerprint );
		if ( is_wp_error( $lock ) ) { return $lock; }
		try {
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE feature_id='F08-FUT-02' AND clinic_id=%d AND subject_user_id=%d AND parent_ref=%s AND status='open' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1", $clinic_id, $context['patient_user_id'], $fingerprint, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $existing ) { return self::public_record( $existing ); }
			return self::put_record( 'F08-FUT-02', array(
				'clinic_id' => $clinic_id,
				'subject_user_id' => $context['patient_user_id'],
				'parent_ref' => $fingerprint,
				'status' => 'open',
				'expires_at' => gmdate( 'Y-m-d H:i:s', min( $latest_end + DAY_IN_SECONDS, $now + 181 * DAY_IN_SECONDS ) ),
				'payload' => array( 'windows' => $windows, 'service_ref' => $service_ref, 'timezone' => $timezone, 'guardian_user_id' => $context['guardian_user_id'], 'auto_book' => false ),
			), $context['actor_user_id'] );
		} finally {
			self::release_semantic_lock( $lock );
		}
	}


	/** Advance a monthly recurrence while preserving the original day-of-month when possible and clamping only to the target month's last day. */
	private static function advance_months_anchored( DateTimeImmutable $cursor, $months, $anchor_day ) {
		$months = max( 1, absint( $months ) );
		$anchor_day = min( 31, max( 1, absint( $anchor_day ) ) );
		$target = $cursor->modify( 'first day of this month' )->modify( '+' . $months . ' months' );
		$day = min( $anchor_day, absint( $target->format( 't' ) ) );
		return $target->setDate( absint( $target->format( 'Y' ) ), absint( $target->format( 'n' ) ), $day );
	}

	/* FUT-03 */
	public static function create_series( $data, $actor = 0 ) {
		$appointment = self::require_appointment( sanitize_text_field( isset( $data['appointment_ref'] ) ? $data['appointment_ref'] : '' ), $actor );
		if ( is_wp_error( $appointment ) ) { return $appointment; }
		if ( ! in_array( SWC_Helpers::status( $appointment ), array( 'requested', 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true ) ) {
			return new WP_Error( 'wca_series_state', __( 'A recurrence intent cannot be created from this appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$frequency = sanitize_key( isset( $data['frequency'] ) ? $data['frequency'] : 'weekly' );
		if ( ! in_array( $frequency, array( 'weekly', 'monthly', 'custom_days' ), true ) ) { return new WP_Error( 'wca_series_frequency', __( 'Unsupported recurrence frequency.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$count = min( 24, max( 1, absint( isset( $data['count'] ) ? $data['count'] : 1 ) ) );
		$interval = min( 12, max( 1, absint( isset( $data['interval'] ) ? $data['interval'] : 1 ) ) );
		$origin = self::utc( SWC_Helpers::meta( $appointment, 'preferred_at_utc', '' ) );
		if ( ! $origin ) { return new WP_Error( 'wca_series_origin', __( 'The originating appointment needs a valid scheduled time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$occurrences = array();
		$cursor = new DateTimeImmutable( $origin . ' UTC' );
		$origin_ts = $cursor->getTimestamp();
		$anchor_day = absint( $cursor->format( 'j' ) );
		$custom_days = min( 365, max( 1, absint( isset( $data['custom_days'] ) ? $data['custom_days'] : $interval ) ) );
		for ( $i = 1; $i <= $count; $i++ ) {
			if ( 'weekly' === $frequency ) { $cursor = $cursor->modify( '+' . $interval . ' weeks' ); }
			elseif ( 'monthly' === $frequency ) { $cursor = self::advance_months_anchored( $cursor, $interval, $anchor_day ); }
			else { $cursor = $cursor->modify( '+' . $custom_days . ' days' ); }
			if ( $cursor->getTimestamp() - $origin_ts > 730 * DAY_IN_SECONDS ) { return new WP_Error( 'wca_series_horizon', __( 'Recurring appointment intents may not extend beyond two years from the originating appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
			$occurrences[] = $cursor->format( 'Y-m-d H:i:s' );
		}
		return self::put_record( 'F08-FUT-03', array( 'appointment_id' => $appointment, 'clinic_id' => absint( SWC_Helpers::meta( $appointment, 'clinic_id', 0 ) ), 'status' => 'series_intent', 'payload' => array( 'frequency' => $frequency, 'interval' => $interval, 'count' => $count, 'occurrence_dates_utc' => $occurrences, 'auto_book' => false, 'parent_appointment_ref' => self::appointment_ref( $appointment ) ) ), $actor );
	}

	/* FUT-04 */
	public static function create_resource( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$type = sanitize_key( $data['resource_type'] ?? 'room' );
		if ( ! in_array( $type, array( 'room', 'device', 'equipment', 'staff_pool', 'virtual_capacity' ), true ) ) { return new WP_Error( 'wca_resource_type', __( 'Unsupported scheduling resource type.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$branch = self::branch_for_clinic( $clinic_id, isset( $data['branch_ref'] ) ? $data['branch_ref'] : '' );
		if ( is_wp_error( $branch ) ) { return $branch; }
		$branch_ref = $branch ? strtolower( (string) $branch['public_ref'] ) : '';
		return self::put_record( 'F08-FUT-04', array( 'clinic_id' => $clinic_id, 'status' => 'resource_active', 'capacity' => max( 1, absint( $data['capacity'] ?? 1 ) ), 'payload' => array( 'resource_type' => $type, 'label' => sanitize_text_field( $data['label'] ?? '' ), 'branch_ref' => $branch_ref ) ), $actor );
	}

	public static function reserve_resource( $resource_ref, $data, $actor = 0 ) {
		global $wpdb;
		$resource = self::get_record( $resource_ref, 'F08-FUT-04' );
		if ( ! $resource || 'resource_active' !== $resource['status'] ) { return new WP_Error( 'wca_resource_missing', __( 'Scheduling resource is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$clinic = self::require_clinic_manager( $resource['clinic_id'], $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$start = self::utc( isset( $data['start_utc'] ) ? $data['start_utc'] : '' );
		$end = self::utc( isset( $data['end_utc'] ) ? $data['end_utc'] : '' );
		if ( ! $start || ! $end || $end <= $start || strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) > DAY_IN_SECONDS ) {
			return new WP_Error( 'wca_resource_window', __( 'A valid UTC resource window of no more than 24 hours is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$appointment_id = 0;
		$appointment_ref = sanitize_text_field( isset( $data['appointment_ref'] ) ? $data['appointment_ref'] : '' );
		if ( $appointment_ref ) {
			$appointment_id = self::require_appointment( $appointment_ref, $actor );
			if ( is_wp_error( $appointment_id ) ) { return $appointment_id; }
			if ( absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) ) !== absint( $resource['clinic_id'] ) ) {
				return new WP_Error( 'wca_resource_appointment_scope', __( 'The appointment and scheduling resource must belong to the same clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
		}
		$lock = 'wca-f24-resource-' . substr( hash( 'sha256', strtolower( $resource_ref ) ), 0, 32 );
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) );
		if ( 1 !== $locked ) { return new WP_Error( 'wca_resource_busy', __( 'The resource is being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		try {
			$table = self::tables()['records'];
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-04' AND parent_ref=%s AND status='reserved' AND (expires_at IS NULL OR expires_at>%s) AND starts_at<%s AND ends_at>%s", strtolower( $resource_ref ), WCA_Repository::now(), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $count >= max( 1, absint( $resource['capacity'] ) ) ) { return new WP_Error( 'wca_resource_conflict', __( 'The resource is already at capacity for this time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$resource_payload = json_decode( (string) $resource['payload_json'], true );
			$resource_type = is_array( $resource_payload ) && isset( $resource_payload['resource_type'] ) ? sanitize_key( $resource_payload['resource_type'] ) : '';
			return self::put_record( 'F08-FUT-04', array(
				'clinic_id' => absint( $resource['clinic_id'] ),
				'appointment_id' => absint( $appointment_id ),
				'parent_ref' => strtolower( $resource_ref ),
				'status' => 'reserved',
				'starts_at' => $start,
				'ends_at' => $end,
				'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( $end . ' UTC' ) + 6 * HOUR_IN_SECONDS ),
				'payload' => array( 'reservation_kind' => 'multi_resource', 'resource_type' => $resource_type, 'cross_clinic_scope_checked' => true ),
			), $actor );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}


	/* FUT-05 */
	public static function create_group_session( $data, $actor = 0 ) {
		$clinic_id = absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$start = self::utc( isset( $data['start_utc'] ) ? $data['start_utc'] : '' );
		$end = self::utc( isset( $data['end_utc'] ) ? $data['end_utc'] : '' );
		if ( ! $start || ! $end || $end <= $start || strtotime( $start . ' UTC' ) <= time() - HOUR_IN_SECONDS || strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) > DAY_IN_SECONDS ) {
			return new WP_Error( 'wca_group_window', __( 'A valid current or future group-session window of no more than 24 hours is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$service = self::service_for_clinic( $clinic_id, isset( $data['service_ref'] ) ? $data['service_ref'] : '' );
		if ( is_wp_error( $service ) ) { return $service; }
		$service_ref = $service ? strtolower( (string) $service['public_ref'] ) : '';
		$capacity = min( 100, max( 2, absint( isset( $data['capacity'] ) ? $data['capacity'] : 2 ) ) );
		return self::put_record( 'F08-FUT-05', array(
			'clinic_id' => $clinic_id,
			'status' => 'group_open',
			'starts_at' => $start,
			'ends_at' => $end,
			'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( $end . ' UTC' ) + 6 * HOUR_IN_SECONDS ),
			'capacity' => $capacity,
			'payload' => array( 'service_ref' => $service_ref, 'visibility' => 'participants_private' ),
		), $actor );
	}

	public static function join_group_session( $session_ref, $data = array(), $actor = 0 ) {
		global $wpdb;
		$actor = absint( $actor ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$context = self::patient_context( is_array( $data ) ? $data : array(), $actor );
		if ( is_wp_error( $context ) ) { return $context; }
		$session = self::get_record( $session_ref, 'F08-FUT-05' );
		if ( ! $session || 'group_open' !== $session['status'] || ( ! empty( $session['expires_at'] ) && strtotime( $session['expires_at'] . ' UTC' ) <= time() ) ) {
			return new WP_Error( 'wca_group_missing', __( 'Group appointment session is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		$clinic = WCA_Repository::get_clinic( absint( $session['clinic_id'] ), true );
		$session_payload = json_decode( (string) $session['payload_json'], true );
		$service_ref = is_array( $session_payload ) ? sanitize_text_field( $session_payload['service_ref'] ?? '' ) : '';
		$service = $service_ref ? self::service_for_clinic( absint( $session['clinic_id'] ), $service_ref ) : null;
		if ( ! $clinic || is_wp_error( $service ) || ! $service ) { return new WP_Error( 'wca_group_scope_stale', __( 'This group appointment is no longer bookable because its clinic or service is not active.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! empty( $session['starts_at'] ) && strtotime( $session['starts_at'] . ' UTC' ) <= time() ) { return new WP_Error( 'wca_group_started', __( 'This group appointment has already started and is closed to new joins.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$table = self::tables()['records'];
		$lock = 'wca-f24-group-' . substr( hash( 'sha256', strtolower( $session_ref ) ), 0, 32 );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) { return new WP_Error( 'wca_group_busy', __( 'The group session is being updated.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		try {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT public_ref FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status='group_member' LIMIT 1", strtolower( $session_ref ), $context['patient_user_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $exists ) { return self::public_record( self::get_record( $exists, 'F08-FUT-05' ) ); }
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND status='group_member'", strtolower( $session_ref ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $count >= absint( $session['capacity'] ) ) { return new WP_Error( 'wca_group_full', __( 'This group appointment is full.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			return self::put_record( 'F08-FUT-05', array(
				'clinic_id' => absint( $session['clinic_id'] ),
				'subject_user_id' => $context['patient_user_id'],
				'parent_ref' => strtolower( $session_ref ),
				'status' => 'group_member',
				'expires_at' => $session['expires_at'],
				'payload' => array( 'member_ref' => WCA_Authorization::subject_uuid( $context['patient_user_id'] ), 'guardian_user_id' => $context['guardian_user_id'], 'peer_identity_visible' => false ),
			), $context['actor_user_id'] );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}


	public static function leave_group_session( $session_ref, $data = array(), $actor = 0 ) {
		global $wpdb;
		$actor = absint( $actor ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$context = self::patient_context( is_array( $data ) ? $data : array(), $actor );
		if ( is_wp_error( $context ) ) { return $context; }
		$session = self::get_record( $session_ref, 'F08-FUT-05' );
		if ( ! $session || ! in_array( (string) $session['status'], array( 'group_open', 'group_cancelled' ), true ) ) {
			return new WP_Error( 'wca_group_missing', __( 'Group appointment session is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		$table = self::tables()['records'];
		$lock = 'wca-f24-group-' . substr( hash( 'sha256', strtolower( $session_ref ) ), 0, 32 );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) { return new WP_Error( 'wca_group_busy', __( 'The group session is being updated.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		try {
			$member = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status IN ('group_member','group_left','group_cancelled') ORDER BY id DESC LIMIT 1", strtolower( $session_ref ), $context['patient_user_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( ! $member ) { return array( 'session_ref' => strtolower( $session_ref ), 'left' => true, 'already_absent' => true ); }
			if ( in_array( (string) $member['status'], array( 'group_left', 'group_cancelled' ), true ) ) { return self::public_record( $member ); }
			$ok = $wpdb->update( $table, array( 'status' => 'group_left', 'version' => absint( $member['version'] ) + 1, 'updated_at' => WCA_Repository::now() ), array( 'id' => absint( $member['id'] ), 'status' => 'group_member', 'version' => absint( $member['version'] ) ) );
			if ( 1 !== (int) $ok ) { return new WP_Error( 'wca_group_leave_conflict', __( 'Group membership changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			self::audit( 'F08-FUT-05', 'group_member_left', strtolower( (string) $member['public_ref'] ), array( 'session_ref' => strtolower( $session_ref ) ), $context['actor_user_id'], false );
			return self::public_record( self::get_record( $member['public_ref'], 'F08-FUT-05' ) );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	public static function cancel_group_session( $session_ref, $data = array(), $actor = 0 ) {
		global $wpdb;
		$actor = absint( $actor ?: get_current_user_id() );
		$session = self::get_record( $session_ref, 'F08-FUT-05' );
		if ( ! $session ) { return new WP_Error( 'wca_group_missing', __( 'Group appointment session is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$clinic = self::require_clinic_manager( absint( $session['clinic_id'] ), $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		if ( 'group_cancelled' === (string) $session['status'] ) { return self::public_record( $session ); }
		if ( 'group_open' !== (string) $session['status'] ) { return new WP_Error( 'wca_group_state', __( 'This group appointment cannot be cancelled from its current state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$table = self::tables()['records'];
		$lock = 'wca-f24-group-' . substr( hash( 'sha256', strtolower( $session_ref ) ), 0, 32 );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) { return new WP_Error( 'wca_group_busy', __( 'The group session is being updated.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		try {
			$now = WCA_Repository::now();
			$ok = $wpdb->update( $table, array( 'status' => 'group_cancelled', 'version' => absint( $session['version'] ) + 1, 'updated_at' => $now ), array( 'id' => absint( $session['id'] ), 'status' => 'group_open', 'version' => absint( $session['version'] ) ) );
			if ( 1 !== (int) $ok ) { return new WP_Error( 'wca_group_cancel_conflict', __( 'Group session changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='group_cancelled',version=version+1,updated_at=%s WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND status='group_member'", $now, strtolower( $session_ref ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			self::audit( 'F08-FUT-05', 'group_session_cancelled', strtolower( $session_ref ), array( 'reason_code' => sanitize_key( isset( $data['reason_code'] ) ? $data['reason_code'] : 'operator_cancelled' ) ), $actor, false );
			return self::public_record( self::get_record( $session_ref, 'F08-FUT-05' ) );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	/* FUT-06 */
	public static function safe_reschedule( $appointment_ref, $data, $actor = 0 ) {
		$id = self::require_appointment( $appointment_ref, $actor );
		if ( is_wp_error( $id ) ) { return $id; }
		$hold = sanitize_text_field( $data['hold_token'] ?? '' );
		if ( ! $hold ) { return new WP_Error( 'wca_reschedule_hold', __( 'A new held appointment time is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$version = SWC_Helpers::record_version( $id );
		$status = SWC_Helpers::status( $id );
		$proposal = WCA_Service::transition_appointment( $id, 'reschedule_pending', array( 'hold_token' => $hold, 'expected_status' => $status, 'expected_version' => $version, 'reason_code' => 'future24_one_tap' ), $actor );
		if ( is_wp_error( $proposal ) ) { return $proposal; }
		$confirm = WCA_Service::transition_appointment( $id, 'confirmed', array( 'expected_status' => 'reschedule_pending', 'expected_version' => SWC_Helpers::record_version( $id ), 'reason_code' => 'future24_one_tap' ), $actor );
		if ( is_wp_error( $confirm ) ) {
			self::audit( 'F08-FUT-06', 'reschedule_confirmation_pending', $appointment_ref, array( 'old_slot_preserved' => true ), $actor, false );
			return $confirm;
		}
		self::audit( 'F08-FUT-06', 'safe_reschedule_completed', $appointment_ref, array( 'compensation_safe' => true ), $actor, false );
		return $confirm;
	}

	/* FUT-07 */
	public static function set_buffers( $data, $actor = 0 ) {
		$clinic_id = absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$before = min( 240, absint( isset( $data['buffer_before'] ) ? $data['buffer_before'] : 0 ) );
		$after = min( 240, absint( isset( $data['buffer_after'] ) ? $data['buffer_after'] : 0 ) );
		$travel = min( 480, absint( isset( $data['travel_gap_minutes'] ) ? $data['travel_gap_minutes'] : 0 ) );
		$continuous = min( 30, absint( isset( $data['max_continuous_consultations'] ) ? $data['max_continuous_consultations'] : 0 ) );
		/*
		 * Future24 policy is immutable/additive. We deliberately do not bulk-write
		 * the canonical availability-rule table here; slot projection and hold
		 * enforcement consume the latest authorized policy record instead.
		 */
		return self::put_record( 'F08-FUT-07', array(
			'clinic_id' => $clinic_id,
			'status' => 'policy_active',
			'payload' => array(
				'buffer_before' => $before,
				'buffer_after' => $after,
				'travel_gap_minutes' => $travel,
				'max_continuous_consultations' => $continuous,
				'enforcement' => 'server_slot_projection_and_hold_guard',
				'canonical_availability_rows_mutated' => false,
			),
		), $actor );
	}

	public static function enforce_slot_hold_policy_pre_dispatch( $result, $server, $request ) {
		if ( null !== $result || ! ( $request instanceof WP_REST_Request ) || '/wca/v1/slot-holds' !== (string) $request->get_route() || 'POST' !== strtoupper( (string) $request->get_method() ) ) { return $result; }
		$data = $request->get_json_params();
		$data = is_array( $data ) ? $data : $request->get_params();
		$guard = self::guard_slot_hold_data( $data );
		return is_wp_error( $guard ) ? $guard : $result;
	}

	private static function guard_slot_hold_data( $data ) {
		$slot = array(
			'clinic_ref' => sanitize_text_field( isset( $data['clinic_ref'] ) ? $data['clinic_ref'] : '' ),
			'practitioner_ref' => sanitize_text_field( isset( $data['practitioner_ref'] ) ? $data['practitioner_ref'] : '' ),
			'branch_ref' => sanitize_text_field( isset( $data['branch_ref'] ) ? $data['branch_ref'] : '' ),
			'start_utc' => sanitize_text_field( isset( $data['start_utc'] ) ? $data['start_utc'] : '' ),
			'end_utc' => sanitize_text_field( isset( $data['end_utc'] ) ? $data['end_utc'] : '' ),
		);
		if ( ! self::slot_allowed_by_policy( $slot ) ) {
			return new WP_Error( 'wca_future24_slot_policy', __( 'The selected time is unavailable under the current clinic buffer, travel, or continuous-consultation policy.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		return true;
	}

	public static function apply_slot_policies_to_rest( $response, $server, $request ) {
		if ( ! ( $request instanceof WP_REST_Request ) || '/wca/v1/slots' !== (string) $request->get_route() || is_wp_error( $response ) ) {
			return $response;
		}
		$data = method_exists( $response, 'get_data' ) ? $response->get_data() : null;
		if ( ! is_array( $data ) || empty( $data['slots'] ) || ! is_array( $data['slots'] ) ) { return $response; }
		$data['slots'] = self::apply_slot_policies( $data['slots'] );
		$data['future24_policy_applied'] = true;
		if ( method_exists( $response, 'set_data' ) ) { $response->set_data( $data ); }
		return $response;
	}

	private static function apply_slot_policies( $slots ) {
		$out = array();
		foreach ( (array) $slots as $slot ) {
			if ( self::slot_allowed_by_policy( $slot ) ) { $out[] = $slot; }
		}
		return array_values( $out );
	}

	private static function slot_allowed_by_policy( $slot ) {
		global $wpdb;
		$clinic = WCA_Repository::get_clinic( isset( $slot['clinic_ref'] ) ? $slot['clinic_ref'] : '', true );
		if ( ! $clinic ) { return false; }
		$table = self::tables()['records'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT payload_json FROM {$table} WHERE feature_id='F08-FUT-07' AND clinic_id=%d AND status='policy_active' ORDER BY id DESC LIMIT 1", absint( $clinic['id'] ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) { return true; }
		$policy = json_decode( (string) $row['payload_json'], true );
		$policy = is_array( $policy ) ? $policy : array();
		$before = min( 240, absint( isset( $policy['buffer_before'] ) ? $policy['buffer_before'] : 0 ) );
		$after = min( 240, absint( isset( $policy['buffer_after'] ) ? $policy['buffer_after'] : 0 ) );
		$travel = min( 480, absint( isset( $policy['travel_gap_minutes'] ) ? $policy['travel_gap_minutes'] : 0 ) );
		$maximum = min( 30, absint( isset( $policy['max_continuous_consultations'] ) ? $policy['max_continuous_consultations'] : 0 ) );
		if ( ! $before && ! $after && ! $travel && ! $maximum ) { return true; }
		$doctor_id = WCA_Plan_Guard::practitioner_id( isset( $slot['practitioner_ref'] ) ? $slot['practitioner_ref'] : '' );
		$start = self::utc( isset( $slot['start_utc'] ) ? $slot['start_utc'] : '' );
		$end = self::utc( isset( $slot['end_utc'] ) ? $slot['end_utc'] : '' );
		if ( ! $doctor_id || ! $start || ! $end ) { return false; }
		$day_start = substr( $start, 0, 10 ) . ' 00:00:00';
		$day_end = substr( $start, 0, 10 ) . ' 23:59:59';
		$near = 0;
		$slot_branch = strtolower( sanitize_text_field( isset( $slot['branch_ref'] ) ? $slot['branch_ref'] : '' ) );
		$page = 1;
		$batch = 200;
		do {
			$q = new WP_Query( array(
				'post_type' => SWC_Helpers::TYPE,
				'post_status' => array( 'private', 'publish' ),
				'fields' => 'ids',
				'posts_per_page' => $batch,
				'paged' => $page,
				'orderby' => 'ID',
				'order' => 'ASC',
				'no_found_rows' => true,
				'meta_query' => array(
					array( 'key' => '_swc_doctor_id', 'value' => $doctor_id, 'compare' => '=' ),
					array( 'key' => '_swc_preferred_at_utc', 'value' => array( $day_start, $day_end ), 'compare' => 'BETWEEN', 'type' => 'DATETIME' ),
				),
			) );
			$ids = array_map( 'absint', (array) $q->posts );
			foreach ( $ids as $appointment_id ) {
				$status = SWC_Helpers::status( $appointment_id );
				if ( in_array( $status, array( 'declined','cancelled','no_show' ), true ) ) { continue; }
				$a_start = self::utc( SWC_Helpers::meta( $appointment_id, 'preferred_at_utc', '' ) );
				$a_end = self::utc( SWC_Helpers::meta( $appointment_id, 'appointment_end_utc', '' ) );
				if ( ! $a_start || ! $a_end ) { continue; }
				if ( $maximum && abs( strtotime( $a_start . ' UTC' ) - strtotime( $start . ' UTC' ) ) <= 6 * HOUR_IN_SECONDS ) { $near++; }
				$gap_before = strtotime( $start . ' UTC' ) - strtotime( $a_end . ' UTC' );
				$gap_after = strtotime( $a_start . ' UTC' ) - strtotime( $end . ' UTC' );
				if ( $before && $gap_before >= 0 && $gap_before < $before * MINUTE_IN_SECONDS ) { return false; }
				if ( $after && $gap_after >= 0 && $gap_after < $after * MINUTE_IN_SECONDS ) { return false; }
				if ( $travel ) {
					$branch_id = absint( SWC_Helpers::meta( $appointment_id, 'branch_id', 0 ) );
					$branches_table = WCA_Schema::tables()['branches'];
					$appointment_branch = $branch_id ? strtolower( (string) $wpdb->get_var( $wpdb->prepare( "SELECT public_ref FROM {$branches_table} WHERE id=%d LIMIT 1", $branch_id ) ) ) : ''; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( $appointment_branch && $slot_branch && ! hash_equals( $appointment_branch, $slot_branch ) ) {
						if ( ( $gap_before >= 0 && $gap_before < $travel * MINUTE_IN_SECONDS ) || ( $gap_after >= 0 && $gap_after < $travel * MINUTE_IN_SECONDS ) ) { return false; }
					}
				}
			}
			$page++;
		} while ( count( $ids ) === $batch );

		return ! $maximum || $near < $maximum;
	}

	/* FUT-08 */
	public static function heatmap( $clinic_id, $days = 30, $actor = 0 ) {
		global $wpdb;
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$days = min( 90, max( 7, absint( $days ) ) );
		$from = gmdate( 'Y-m-d 00:00:00' );
		$to = gmdate( 'Y-m-d 23:59:59', time() + ( $days - 1 ) * DAY_IN_SECONDS );
		$ids = self::clinic_appointments_between_all( $clinic_id, $from, $to );
		$rules_table = WCA_Schema::tables()['availability'];
		$rules = (array) $wpdb->get_results( $wpdb->prepare( "SELECT rrule_json,capacity,status FROM {$rules_table} WHERE clinic_id=%d AND status='active' ORDER BY id ASC", absint( $clinic_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$map = array();
		for ( $i = 0; $i < $days; $i++ ) {
			$date = gmdate( 'Y-m-d', time() + $i * DAY_IN_SECONDS );
			$map[ $date ] = array( 'booked' => 0, 'requested' => 0, 'confirmed' => 0, 'configured_capacity' => 0, 'free_estimate' => 0 );
			$weekday = strtolower( gmdate( 'l', strtotime( $date . ' UTC' ) ) );
			foreach ( $rules as $rule ) {
				$rrule = json_decode( (string) $rule['rrule_json'], true );
				if ( ! is_array( $rrule ) || ! in_array( $weekday, (array) ( isset( $rrule['days'] ) ? $rrule['days'] : array() ), true ) ) { continue; }
				$start_h = isset( $rrule['start'] ) ? $rrule['start'] : '';
				$end_h = isset( $rrule['end'] ) ? $rrule['end'] : '';
				$interval = max( 10, absint( isset( $rrule['interval_minutes'] ) ? $rrule['interval_minutes'] : 30 ) );
				if ( preg_match( '/^(\\d{2}):(\\d{2})$/', $start_h, $sm ) && preg_match( '/^(\\d{2}):(\\d{2})$/', $end_h, $em ) ) {
					$minutes = ( (int) $em[1] * 60 + (int) $em[2] ) - ( (int) $sm[1] * 60 + (int) $sm[2] );
					if ( $minutes > 0 ) { $map[ $date ]['configured_capacity'] += intdiv( $minutes, $interval ) * max( 1, absint( $rule['capacity'] ) ); }
				}
			}
		}
		foreach ( $ids as $id ) {
			$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' );
			$day = substr( $when, 0, 10 );
			if ( ! isset( $map[ $day ] ) ) { continue; }
			$status = SWC_Helpers::status( $id );
			if ( ! in_array( $status, array( 'declined','cancelled','no_show' ), true ) ) { $map[ $day ]['booked']++; }
			if ( isset( $map[ $day ][ $status ] ) ) { $map[ $day ][ $status ]++; }
		}
		foreach ( $map as $date => $row ) { $map[ $date ]['free_estimate'] = max( 0, $row['configured_capacity'] - $row['booked'] ); }
		return array(
			'contract' => 'wca.capacity-heatmap',
			'version' => self::CONTRACT_VERSION,
			'clinic_ref' => (string) $clinic['public_ref'],
			'days' => $map,
			'privacy' => 'aggregate_only',
			'projection_note' => 'Configured capacity is an operational estimate; current slot search remains authoritative.',
		);
	}

	/* FUT-09 */
	public static function advisor( $clinic_id, $actor = 0 ) {
		$heat = self::heatmap( $clinic_id, 30, $actor );
		if ( is_wp_error( $heat ) ) { return $heat; }
		$items = array();
		foreach ( $heat['days'] as $date => $row ) {
			$capacity = max( 0, absint( isset( $row['configured_capacity'] ) ? $row['configured_capacity'] : 0 ) );
			$booked = max( 0, absint( isset( $row['booked'] ) ? $row['booked'] : 0 ) );
			$ratio = $capacity ? $booked / $capacity : 0;
			if ( $capacity && $ratio >= 0.8 ) { $items[] = array( 'date' => $date, 'type' => 'high_demand', 'suggestion' => 'Consider opening additional capacity or buffer review.', 'auto_apply' => false ); }
			if ( $capacity >= 4 && $ratio <= 0.2 ) { $items[] = array( 'date' => $date, 'type' => 'low_demand', 'suggestion' => 'Consider consolidating availability if clinically and operationally appropriate.', 'auto_apply' => false ); }
		}
		return array( 'contract' => 'wca.schedule-advisor', 'version' => self::CONTRACT_VERSION, 'advisory_only' => true, 'recommendations' => array_slice( $items, 0, 30 ) );
	}

	/* FUT-10 */
	public static function no_show_forecast( $clinic_id, $actor = 0 ) {
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$ids = self::clinic_appointments_between_all( $clinic_id, gmdate( 'Y-m-d H:i:s', time() - 365 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );
		$total = 0; $no_show = 0;
		foreach ( $ids as $id ) { $status = SWC_Helpers::status( $id ); if ( in_array( $status, array( 'completed', 'no_show' ), true ) ) { $total++; if ( 'no_show' === $status ) { $no_show++; } } }
		if ( $total < self::MIN_FORECAST_N ) { return array( 'contract' => 'wca.no-show-forecast', 'version' => self::CONTRACT_VERSION, 'suppressed' => true, 'minimum_sample' => self::MIN_FORECAST_N, 'patient_scoring' => false ); }
		$rate = round( $no_show / $total, 4 );
		return array( 'contract' => 'wca.no-show-forecast', 'version' => self::CONTRACT_VERSION, 'suppressed' => false, 'sample_size' => $total, 'rate' => $rate, 'range' => array( max( 0, round( $rate - 0.05, 4 ) ), min( 1, round( $rate + 0.05, 4 ) ) ), 'patient_scoring' => false, 'access_penalty' => false );
	}

	/* FUT-11 */
	public static function save_questionnaire( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$service = self::service_for_clinic( $clinic_id, isset( $data['service_ref'] ) ? $data['service_ref'] : '' );
		if ( is_wp_error( $service ) ) { return $service; }
		$service_ref = $service ? strtolower( (string) $service['public_ref'] ) : '';
		$allowed = array( 'reason', 'category', 'symptoms_summary', 'medications_summary', 'allergies_summary', 'accessibility_needs', 'preferred_language', 'notes' );
		$fields = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $data['fields'] ?? array() ) ), $allowed ) );
		if ( ! $fields ) { return new WP_Error( 'wca_questionnaire_fields', __( 'At least one approved secure intake field is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-11', array( 'clinic_id' => $clinic_id, 'status' => 'template_active', 'payload' => array( 'service_ref' => $service_ref, 'fields' => $fields, 'answers_owner' => 'WCA_Continuity encrypted intake', 'automated_diagnosis' => false ) ), $actor );
	}


	/** Read every active Future24 policy/template row for one clinic with stable keyset pagination. */
	private static function feature_rows_for_clinic( $feature_id, $clinic_id, $status, $shape = 'payload' ) {
		global $wpdb;
		$table = self::tables()['records'];
		$feature_id = strtoupper( sanitize_text_field( $feature_id ) );
		$status = sanitize_key( $status );
		if ( ! isset( self::capabilities()[ $feature_id ] ) || ! $clinic_id || ! $status ) { return array(); }
		$columns = 'questionnaire' === $shape ? 'id,public_ref,payload_json,version,updated_at' : 'id,payload_json';
		$out = array();
		$cursor = 0;
		$batch = 100;
		do {
			$rows = (array) $wpdb->get_results( $wpdb->prepare(
				"SELECT {$columns} FROM {$table} WHERE feature_id=%s AND clinic_id=%d AND status=%s AND id>%d ORDER BY id ASC LIMIT %d",
				$feature_id, absint( $clinic_id ), $status, $cursor, $batch
			), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $rows as $row ) { $cursor = max( $cursor, absint( $row['id'] ?? 0 ) ); $out[] = $row; }
		} while ( count( $rows ) === $batch );
		return $out;
	}

	public static function questionnaire_for_appointment( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$id = self::require_appointment( $appointment_ref, $actor );
		if ( is_wp_error( $id ) ) { return $id; }
		$clinic_id = absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) );
		$service_id = absint( SWC_Helpers::meta( $id, 'service_id', 0 ) );
		$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;
		$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';
		$table = self::tables()['records'];
		$rows = array_reverse( self::feature_rows_for_clinic( 'F08-FUT-11', $clinic_id, 'template_active', 'questionnaire' ) );
		foreach ( $rows as $row ) {
			$payload = json_decode( (string) $row['payload_json'], true );
			if ( ! is_array( $payload ) ) { continue; }
			$template_service = strtolower( sanitize_text_field( isset( $payload['service_ref'] ) ? $payload['service_ref'] : '' ) );
			if ( $template_service && $service_ref && ! hash_equals( $template_service, $service_ref ) ) { continue; }
			return array(
				'contract' => 'wca.dynamic-previsit-questionnaire',
				'version' => self::CONTRACT_VERSION,
				'template_ref' => strtolower( (string) $row['public_ref'] ),
				'appointment_ref' => strtolower( $appointment_ref ),
				'fields' => array_values( (array) ( isset( $payload['fields'] ) ? $payload['fields'] : array() ) ),
				'answers_owner' => 'WCA_Continuity encrypted intake',
				'automated_diagnosis' => false,
				'template_version' => absint( $row['version'] ),
				'updated_at_utc' => (string) $row['updated_at'],
			);
		}
		return array( 'contract' => 'wca.dynamic-previsit-questionnaire', 'version' => self::CONTRACT_VERSION, 'appointment_ref' => strtolower( $appointment_ref ), 'fields' => array(), 'answers_owner' => 'WCA_Continuity encrypted intake', 'automated_diagnosis' => false );
	}

	/* FUT-12 */
	public static function readiness( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$actor_id = absint( $actor ?: get_current_user_id() );
		$id = self::require_appointment( $appointment_ref, $actor_id );
		if ( is_wp_error( $id ) ) { return $id; }
		$consent = class_exists( 'WCA_Continuity_Guards' ) ? WCA_Continuity_Guards::consent_state( $appointment_ref, $actor_id ) : array();
		$intake = WCA_Continuity::tables()['intake'];
		$intake_status = (string) $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$intake} WHERE appointment_id=%d LIMIT 1", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$prereq = self::prerequisite_state( $id );
		$patient_id = absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) );
		$guardian_id = absint( SWC_Helpers::meta( $id, 'guardian_user_id', 0 ) );
		$guardian_check = self::current_guardian_state( $patient_id, $guardian_id );
		$type = sanitize_key( SWC_Helpers::meta( $id, 'consultation_type', '' ) );
		$remote = in_array( $type, array( 'online','hybrid' ), true );
		$teleconsult = ! $remote || ( ! is_wp_error( $consent ) && ! empty( $consent['scopes']['teleconsult'] ) && 'granted' === $consent['scopes']['teleconsult']['status'] );
		$checks = array(
			'appointment_confirmed' => in_array( SWC_Helpers::status( $id ), array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true ),
			'privacy_consent_current' => ! is_wp_error( $consent ) && ! empty( $consent['scopes']['privacy_notice'] ) && 'granted' === $consent['scopes']['privacy_notice']['status'],
			'previsit_submitted' => 'submitted' === $intake_status,
			'prerequisites_complete' => ! empty( $prereq['complete'] ),
			'guardian_recheck_runtime' => ! is_wp_error( $guardian_check ),
			'remote_context_ready' => $teleconsult,
		);
		$ready = ! in_array( false, $checks, true );
		return array( 'contract' => 'wca.appointment-readiness', 'version' => self::CONTRACT_VERSION, 'appointment_ref' => strtolower( $appointment_ref ), 'ready' => $ready, 'state' => $ready ? 'ready' : 'action_required', 'checks' => $checks, 'prerequisites' => $prereq, 'clinical_fitness_assessed' => false );
	}

	/* FUT-13 */
	public static function save_prerequisites( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$service_ref = strtolower( sanitize_text_field( $data['service_ref'] ?? '' ) );
		$service = self::service_for_clinic( $clinic_id, $service_ref );
		if ( is_wp_error( $service ) ) { return $service; }
		$raw_requirements = (array) ( $data['requirements'] ?? array() );
		if ( count( $raw_requirements ) > 20 ) { return new WP_Error( 'wca_prerequisite_rules_limit', __( 'No more than 20 prerequisite rules may be saved in one policy.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
		$requirements = array();
		foreach ( $raw_requirements as $item ) {
			if ( ! is_array( $item ) ) { continue; }
			$type = sanitize_key( $item['type'] ?? 'document' );
			$label = substr( sanitize_text_field( $item['label'] ?? '' ), 0, 120 );
			if ( ! $label ) { continue; }
			$behavior = sanitize_key( $item['missing_behavior'] ?? ( $data['missing_behavior'] ?? 'provisional' ) );
			if ( ! in_array( $behavior, array( 'provisional','block' ), true ) ) { $behavior = 'provisional'; }
			$requirements[] = array( 'key' => $type . ':' . $label, 'type' => $type, 'label' => $label, 'missing_behavior' => $behavior );
		}
		if ( ! $requirements ) { return new WP_Error( 'wca_prerequisite_rules_empty', __( 'At least one valid prerequisite rule is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-13', array(
			'clinic_id' => $clinic_id,
			'status' => 'rule_active',
			'payload' => array( 'service_ref' => $service_ref, 'requirements' => $requirements, 'blocking_is_explicit' => true ),
		), $actor );
	}

	private static function prerequisite_state( $appointment_id ) {
		global $wpdb;
		$table = self::tables()['records'];
		$clinic_id = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) );
		$service_id = absint( SWC_Helpers::meta( $appointment_id, 'service_id', 0 ) );
		$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;
		$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';
		$rules = array_reverse( self::feature_rows_for_clinic( 'F08-FUT-13', $clinic_id, 'rule_active', 'payload' ) );
		$requirements = array();
		foreach ( $rules as $row ) {
			$p = json_decode( (string) $row['payload_json'], true );
			if ( ! is_array( $p ) ) { continue; }
			$rule_service = strtolower( sanitize_text_field( $p['service_ref'] ?? '' ) );
			if ( $rule_service && ( ! $service_ref || ! hash_equals( $rule_service, $service_ref ) ) ) { continue; }
			foreach ( (array) ( $p['requirements'] ?? array() ) as $r ) {
				if ( is_string( $r ) ) { $key=sanitize_text_field($r); if($key){$requirements[$key]='block';} continue; }
				if ( ! is_array( $r ) ) { continue; }
				$key = sanitize_text_field( $r['key'] ?? '' );
				if ( ! $key && isset( $r['type'],$r['label'] ) ) { $key=sanitize_key($r['type']).':'.substr(sanitize_text_field($r['label']),0,120); }
				if ( ! $key ) { continue; }
				$requirements[$key] = 'block' === sanitize_key( $r['missing_behavior'] ?? 'provisional' ) ? 'block' : 'provisional';
			}
		}
		$raw_evidence = (array) SWC_Helpers::meta( $appointment_id, 'prerequisite_evidence_refs', array() );
		$evidence = array();
		foreach ( $raw_evidence as $key => $item ) {
			if ( is_string($key) && !is_numeric($key) && $item ) { $evidence[sanitize_text_field($key)] = true; }
			if ( is_string($item) ) { $evidence[sanitize_text_field($item)] = true; }
			if ( is_array($item) ) {
				$r=sanitize_text_field($item['requirement'] ?? '');
				if(!$r && isset($item['type'],$item['label'])){$r=sanitize_key($item['type']).':'.substr(sanitize_text_field($item['label']),0,120);}
				$ref=sanitize_text_field($item['ref'] ?? '');
				if($r && $ref && strlen($ref)<=255){$evidence[$r]=true;}
			}
		}
		$missing_blocking=array(); $missing_provisional=array();
		foreach($requirements as $r=>$behavior){ if(isset($evidence[$r])){continue;} if('block'===$behavior){$missing_blocking[]=$r;}else{$missing_provisional[]=$r;} }
		return array(
			'required_count'=>count($requirements),
			'blocking_count'=>count(array_filter($requirements,static function($v){return 'block'===$v;})),
			'evidence_count'=>count(array_intersect_key($evidence,$requirements)),
			'complete'=>!$missing_blocking,
			'missing_requirements'=>$missing_blocking,
			'provisional_missing_requirements'=>$missing_provisional,
			'provisional_missing_does_not_block'=>true,
			'count_only_evidence_is_not_sufficient'=>true,
		);
	}


	/* FUT-14 */
	public static function family_hub( $actor = 0 ) {
		$actor = absint( $actor ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor ); if ( is_wp_error( $claims ) ) { return $claims; }
		if ( empty( $claims['guardian'] ) ) { return array( 'contract' => 'wca.family-hub', 'version' => self::CONTRACT_VERSION, 'guardian' => false, 'appointments' => array() ); }
		$out = array();
		$page = 1;
		$batch = 200;
		do {
			$q = new WP_Query( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'fields' => 'ids', 'posts_per_page' => $batch, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true, 'meta_key' => '_swc_guardian_user_id', 'meta_value' => $actor ) );
			$ids = array_map( 'absint', (array) $q->posts );
			foreach ( $ids as $id ) {
				$patient_id = absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) );
				$guard = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_id, $actor, $actor ) : new WP_Error( 'wca_guardian_recheck_unavailable', 'unavailable' );
				if ( is_wp_error( $guard ) ) { continue; }
				$out[] = array( 'appointment_ref' => self::appointment_ref( $id ), 'status' => SWC_Helpers::status( $id ), 'scheduled_at_utc' => (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) );
			}
			$page++;
		} while ( count( $ids ) === $batch );
		return array( 'contract' => 'wca.family-hub', 'version' => self::CONTRACT_VERSION, 'guardian' => true, 'appointments' => $out, 'relationship_recheck' => 'performed_for_each_returned_appointment', 'pagination_complete' => true );
	}

	/* FUT-15 */
	public static function arrive( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$actor_id = absint( $actor ?: get_current_user_id() );
		$id = self::require_appointment( $appointment_ref, $actor_id );
		if ( is_wp_error( $id ) ) { return $id; }
		$who = WCA_Authorization::appointment_actor( $id, $actor_id );
		if ( ! in_array( $who, array( 'patient','guardian' ), true ) ) { return new WP_Error( 'wca_arrival_actor', __( 'Only the patient or verified guardian may announce arrival.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		if ( ! in_array( SWC_Helpers::status( $id ), array( 'confirmed','reschedule_pending' ), true ) ) {
			return new WP_Error( 'wca_arrival_state', __( 'Arrival may only be announced for a confirmed or pending-reschedule appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$start = self::utc( SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) );
		$end = self::utc( SWC_Helpers::meta( $id, 'appointment_end_utc', '' ) );
		if ( ! $start || ! $end ) { return new WP_Error( 'wca_arrival_time', __( 'The appointment time is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$now = time();
		if ( $now < strtotime( $start . ' UTC' ) - 4 * HOUR_IN_SECONDS || $now > strtotime( $end . ' UTC' ) + 6 * HOUR_IN_SECONDS ) {
			return new WP_Error( 'wca_arrival_window', __( 'Arrival may only be announced near the scheduled appointment time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$lock = self::semantic_lock( 'arrival', $id . '|' . $actor_id );
		if ( is_wp_error( $lock ) ) { return $lock; }
		try {
			$table = self::tables()['records'];
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND subject_user_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1", $id, $actor_id, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $existing ) { return self::public_record( $existing ); }
			return self::put_record( 'F08-FUT-15', array(
				'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ),
				'appointment_id' => $id,
				'subject_user_id' => $actor_id,
				'status' => 'arrived',
				'starts_at' => WCA_Repository::now(),
				'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( $end . ' UTC' ) + 6 * HOUR_IN_SECONDS ),
				'payload' => array( 'queue_token' => substr( hash( 'sha256', strtolower( $appointment_ref ) . '|' . wp_salt( 'nonce' ) ), 0, 12 ), 'clinical_checkin' => false, 'operational_signal_only' => true ),
			), $actor_id );
		} finally {
			self::release_semantic_lock( $lock );
		}
	}

	/* FUT-16 */
	public static function queue_position( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$id = self::require_appointment( $appointment_ref, $actor );
		if ( is_wp_error( $id ) ) { return $id; }
		$table = self::tables()['records'];
		$clinic_id = absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) );
		$now = WCA_Repository::now();
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1", $id, $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $current ) { return new WP_Error( 'wca_queue_not_arrived', __( 'Arrival has not been registered or has expired.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$ahead = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-15' AND clinic_id=%d AND status='arrived' AND created_at<%s AND (expires_at IS NULL OR expires_at>%s)", $clinic_id, $current['created_at'], $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'contract' => 'wca.private-queue-position', 'version' => self::CONTRACT_VERSION, 'appointment_ref' => strtolower( $appointment_ref ), 'appointments_ahead' => $ahead, 'estimated_delay_minutes' => $ahead * 15, 'other_patient_identity_exposed' => false, 'estimate_only' => true );
	}

	/* FUT-17 */
	public static function create_disruption( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$start=self::utc($data['start_utc'] ?? ''); $end=self::utc($data['end_utc'] ?? '');
		$start_ts=$start?strtotime($start.' UTC'):0; $end_ts=$end?strtotime($end.' UTC'):0; $now=time();
		if(!$start||!$end||$end_ts<=$start_ts||$end_ts<=$now||$start_ts<$now-5*MINUTE_IN_SECONDS||$end_ts-max($start_ts,$now)>14*DAY_IN_SECONDS){
			return new WP_Error('wca_disruption_window',__('A current or future disruption window of no more than 14 days is required.','worldwide-clinic-appointments'),array('status'=>400));
		}
		$effective_start=gmdate('Y-m-d H:i:s',max($start_ts,$now));
		$affected=array();
		foreach(self::clinic_appointments_between_all($clinic_id,$effective_start,$end) as $appointment_id){
			if(!in_array(SWC_Helpers::status($appointment_id),array('requested','confirmed','reschedule_pending'),true)){continue;}
			$scheduled=self::utc(SWC_Helpers::meta($appointment_id,'preferred_at_utc',''));
			if(!$scheduled||strtotime($scheduled.' UTC')<$now){continue;}
			$affected[]=$appointment_id;
		}
		$affected=array_values(array_unique($affected));
		$record=self::put_record('F08-FUT-17',array(
			'clinic_id'=>$clinic_id,'status'=>'disruption_active','starts_at'=>$effective_start,'ends_at'=>$end,
			'expires_at'=>gmdate('Y-m-d H:i:s',$end_ts+DAY_IN_SECONDS),
			'payload'=>array('reason_code'=>sanitize_key($data['reason_code'] ?? 'operational_delay'),'rebooking_mode'=>'offer_only','auto_cancel'=>false,'affected_count'=>count($affected),'affected_states'=>array('requested','confirmed','reschedule_pending'),'past_appointments_excluded'=>true),
		),$actor);
		if(is_wp_error($record)){return $record;}
		foreach($affected as $appointment_id){
			$recipients=array_values(array_unique(array_filter(array(
				absint(SWC_Helpers::meta($appointment_id,'patient_user_id',get_post_field('post_author',$appointment_id))),
				absint(SWC_Helpers::meta($appointment_id,'guardian_user_id',0)),
				absint(SWC_Helpers::meta($appointment_id,'doctor_id',0)),
			))));
			WCA_Repository::enqueue('File19.NotificationRequested.v1',self::appointment_ref($appointment_id),array(
				'event'=>'clinic_disruption','appointment_ref'=>self::appointment_ref($appointment_id),'clinic_ref'=>(string)$clinic['public_ref'],
				'disruption_ref'=>$record['public_ref'],'recipients'=>$recipients,'delivery_owner'=>'File19','auto_cancel'=>false,
			),WCA_Observability::trace_id());
		}
		$record['affected_count']=count($affected); return $record;
	}

	/* FUT-18 */
	public static function add_participant( $appointment_ref, $data, $actor = 0 ) {
		global $wpdb;
		$actor_id=absint($actor?:get_current_user_id()); $id=self::require_appointment($appointment_ref,$actor_id); if(is_wp_error($id)){return $id;}
		$who=WCA_Authorization::appointment_actor($id,$actor_id);
		if(!in_array($who,array('patient','guardian'),true)){return new WP_Error('wca_support_actor',__('Only the patient or verified guardian may add a support participant.','worldwide-clinic-appointments'),array('status'=>403));}
		if(!in_array(SWC_Helpers::status($id),array('requested','confirmed','reschedule_pending','checked_in'),true)){return new WP_Error('wca_support_state',__('A support participant cannot be added in this appointment state.','worldwide-clinic-appointments'),array('status'=>409));}
		$subject=strtolower(sanitize_text_field($data['subject_uuid'] ?? ''));
		if(!preg_match('/^[0-9a-f-]{36}$/',$subject)){return new WP_Error('wca_support_subject',__('A valid platform participant subject reference is required.','worldwide-clinic-appointments'),array('status'=>400));}
		$subject_user_id=self::subject_user_id($subject);
		if(!$subject_user_id){return new WP_Error('wca_support_subject',__('A valid platform participant subject reference is required.','worldwide-clinic-appointments'),array('status'=>400));}
		$subject_claims=WCA_Authorization::claims($subject_user_id);
		if(is_wp_error($subject_claims)){return new WP_Error('wca_support_subject_ineligible',__('The selected support participant is not currently eligible.','worldwide-clinic-appointments'),array('status'=>409));}
		$role=sanitize_key($data['role'] ?? 'support'); if(!in_array($role,array('support','interpreter'),true)){$role='support';}
		$table=self::tables()['records'];
		$lock=self::semantic_lock('support-participant',$id.'|'.$subject_user_id);
		if(is_wp_error($lock)){return $lock;}
		try{
			$existing=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE feature_id='F08-FUT-18' AND appointment_id=%d AND subject_user_id=%d AND status='participant_active' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1",$id,$subject_user_id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if($existing){$payload=json_decode((string)$existing['payload_json'],true); if(is_array($payload)&&sanitize_key($payload['role'] ?? '')===$role){return self::public_record($existing);} return new WP_Error('wca_support_participant_conflict',__('This participant already has an active appointment role.','worldwide-clinic-appointments'),array('status'=>409));}
			$end=self::utc(SWC_Helpers::meta($id,'appointment_end_utc','')); $expiry=$end?min(strtotime($end.' UTC')+DAY_IN_SECONDS,time()+30*DAY_IN_SECONDS):time()+7*DAY_IN_SECONDS;
			$wpdb->query('START TRANSACTION'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result=self::put_record('F08-FUT-18',array('appointment_id'=>$id,'clinic_id'=>absint(SWC_Helpers::meta($id,'clinic_id',0)),'subject_user_id'=>$subject_user_id,'status'=>'participant_active','expires_at'=>gmdate('Y-m-d H:i:s',$expiry),'payload'=>array('subject_uuid'=>$subject,'role'=>$role,'appointment_bound'=>true,'revocable'=>true,'clinical_write_authority'=>false)),$actor_id);
			if(is_wp_error($result)){$wpdb->query('ROLLBACK'); return $result;} // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$queued=WCA_Repository::enqueue('File17.AppointmentParticipantChanged.v1',strtolower($appointment_ref),array('appointment_ref'=>strtolower($appointment_ref),'participant_ref'=>$result['public_ref'],'subject_uuid'=>$subject,'role'=>$role,'status'=>'active','expires_at_utc'=>$result['expires_at'],'transport_owner'=>'File17','clinical_write_authority'=>false),WCA_Observability::trace_id());
			if(is_wp_error($queued)){$wpdb->query('ROLLBACK'); return $queued;} // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query('COMMIT'); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			return $result;
		} finally {
			self::release_semantic_lock($lock);
		}
	}

	public static function revoke_participant( $appointment_ref, $participant_ref, $actor = 0 ) {
		global $wpdb;
		$actor_id = absint( $actor ?: get_current_user_id() );
		$id = self::require_appointment( $appointment_ref, $actor_id );
		if ( is_wp_error( $id ) ) { return $id; }
		$who = WCA_Authorization::appointment_actor( $id, $actor_id );
		if ( ! in_array( $who, array( 'patient','guardian' ), true ) ) { return new WP_Error( 'wca_support_revoke_actor', __( 'Only the patient or verified guardian may revoke this participant.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$row = self::get_record( $participant_ref, 'F08-FUT-18' );
		if ( ! $row || absint( $row['appointment_id'] ) !== $id || 'participant_active' !== $row['status'] ) { return new WP_Error( 'wca_support_participant_missing', __( 'The active support participant was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$table = self::tables()['records'];
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='revoked',version=version+1,expires_at=%s,updated_at=%s WHERE id=%d AND status='participant_active'", WCA_Repository::now(), WCA_Repository::now(), absint( $row['id'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( 1 !== $updated ) { return new WP_Error( 'wca_support_revoke_conflict', __( 'The support participant changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$payload = json_decode( (string) $row['payload_json'], true );
		$payload = is_array( $payload ) ? $payload : array();
		WCA_Repository::enqueue( 'File17.AppointmentParticipantChanged.v1', strtolower( $appointment_ref ), array( 'appointment_ref' => strtolower( $appointment_ref ), 'participant_ref' => strtolower( $participant_ref ), 'subject_uuid' => sanitize_text_field( isset( $payload['subject_uuid'] ) ? $payload['subject_uuid'] : '' ), 'role' => sanitize_key( isset( $payload['role'] ) ? $payload['role'] : 'support' ), 'status' => 'revoked', 'transport_owner' => 'File17' ), WCA_Observability::trace_id() );
		self::audit( 'F08-FUT-18', 'participant_revoked', strtolower( $participant_ref ), array( 'appointment_ref' => strtolower( $appointment_ref ) ), $actor_id, false );
		return array( 'contract' => 'wca.support-participant', 'version' => self::CONTRACT_VERSION, 'participant_ref' => strtolower( $participant_ref ), 'status' => 'revoked' );
	}

	/* FUT-19 */
	public static function request_virtual_room( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$actor_id=absint($actor?:get_current_user_id()); $id=self::require_appointment($appointment_ref,$actor_id); if(is_wp_error($id)){return $id;}
		if(!in_array(SWC_Helpers::status($id),array('confirmed','reschedule_pending','checked_in'),true)){return new WP_Error('wca_virtual_room_state',__('A virtual room requires a confirmed, pending-reschedule, or checked-in appointment.','worldwide-clinic-appointments'),array('status'=>409));}
		$type=sanitize_key(SWC_Helpers::meta($id,'consultation_type','')); if(!in_array($type,array('online','hybrid'),true)){return new WP_Error('wca_virtual_room_mode',__('A virtual room is only available for online or hybrid appointments.','worldwide-clinic-appointments'),array('status'=>409));}
		$consent=class_exists('WCA_Continuity_Guards')?WCA_Continuity_Guards::consent_state($appointment_ref,$actor_id):new WP_Error('wca_virtual_room_consent_state',__('Current teleconsult consent could not be verified.','worldwide-clinic-appointments'));
		if(is_wp_error($consent)||empty($consent['scopes']['teleconsult'])||'granted'!==$consent['scopes']['teleconsult']['status']){return new WP_Error('wca_virtual_room_consent',__('Current teleconsult consent is required before requesting a virtual room.','worldwide-clinic-appointments'),array('status'=>409));}
		$lock=self::semantic_lock('virtual-room',$id);
		if(is_wp_error($lock)){return $lock;}
		try {
			$table=self::tables()['records'];
			$existing=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE feature_id='F08-FUT-19' AND appointment_id=%d AND status='room_requested' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1",$id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if($existing){return self::public_record($existing);}
			$record=self::put_record('F08-FUT-19',array('appointment_id'=>$id,'clinic_id'=>absint(SWC_Helpers::meta($id,'clinic_id',0)),'subject_user_id'=>$actor_id,'status'=>'room_requested','expires_at'=>gmdate('Y-m-d H:i:s',time()+HOUR_IN_SECONDS),'payload'=>array('appointment_ref'=>strtolower($appointment_ref),'transport_owner'=>'File17','recording_assumed'=>false,'teleconsult_consent_verified'=>true,'idempotent_request'=>true)),$actor_id);
			if(!is_wp_error($record)){WCA_Repository::enqueue('File17.VirtualRoomRequested.v1',strtolower($appointment_ref),array('appointment_ref'=>strtolower($appointment_ref),'request_ref'=>$record['public_ref'],'recording_allowed'=>false,'teleconsult_consent_verified'=>true),WCA_Observability::trace_id());}
			return $record;
		} finally {
			self::release_semantic_lock($lock);
		}
	}

	private static function subject_user_id( $subject ) {
		$subject = strtolower( sanitize_text_field( $subject ) );
		if ( function_exists( 'smc_get_user_id_by_subject_uuid' ) ) {
			$id = absint( smc_get_user_id_by_subject_uuid( $subject ) );
			if ( $id ) { return $id; }
		}
		$users = get_users( array( 'fields' => 'ids', 'number' => 2, 'meta_key' => '_smc_subject_uuid', 'meta_value' => $subject ) );
		return 1 === count( $users ) ? absint( $users[0] ) : 0;
	}

	/* FUT-20 */
	public static function fhir_projection( $type, $ref, $actor = 0 ) {
		$type = sanitize_key( $type ); $ref = strtolower( sanitize_text_field( $ref ) );
		if ( 'appointment' === $type ) {
			$id = self::require_appointment( $ref, $actor ); if ( is_wp_error( $id ) ) { return $id; }
			$start = self::utc( SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) );
			$end = self::utc( SWC_Helpers::meta( $id, 'appointment_end_utc', '' ) );
			if ( ! $start || ! $end || $end <= $start ) { return new WP_Error( 'wca_fhir_time', __( 'Appointment scheduling times are unavailable or invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			return array( 'resourceType' => 'Appointment', 'id' => $ref, 'status' => self::fhir_status( SWC_Helpers::status( $id ) ), 'start' => gmdate( 'c', strtotime( $start . ' UTC' ) ), 'end' => gmdate( 'c', strtotime( $end . ' UTC' ) ), 'meta' => array( 'profile' => array( 'wca.future24/fhir-appointment/' . self::CONTRACT_VERSION ) ) );
		}
		if ( 'clinic' === $type ) {
			$clinic = WCA_Service::public_clinic_projection( $ref ); if ( ! $clinic ) { return new WP_Error( 'wca_fhir_clinic', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
			return array( 'resourceType' => 'HealthcareService', 'id' => (string) $clinic['public_ref'], 'active' => 'active' === $clinic['status'], 'name' => (string) $clinic['name'], 'communication' => array_values( (array) $clinic['languages'] ), 'extension' => array( array( 'url' => 'wca-zero-commission', 'valueBoolean' => true ) ) );
		}
		return new WP_Error( 'wca_fhir_type', __( 'Unsupported interoperability resource type.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
	}

	private static function fhir_status( $status ) {
		$map = array( 'requested'=>'proposed','confirmed'=>'booked','declined'=>'cancelled','reschedule_pending'=>'pending','checked_in'=>'arrived','completed'=>'fulfilled','cancelled'=>'cancelled','no_show'=>'noshow' );
		return isset( $map[ $status ] ) ? $map[ $status ] : 'pending';
	}

	/* FUT-21 */
	public static function smart_find( $params ) {
		$params=is_array($params)?$params:array(); $allowed=array('clinic_ref','service_ref','practitioner_ref','date_from','date_to','timezone','limit'); $clean=array();
		foreach($allowed as $key){if(!array_key_exists($key,$params)){continue;} if('limit'===$key){$clean[$key]=min(500,max(1,absint($params[$key])));continue;} if(!is_scalar($params[$key])){return new WP_Error('wca_smart_find_parameter',__('Scheduling-link query parameters must be scalar values.','worldwide-clinic-appointments'),array('status'=>400));} $clean[$key]=sanitize_text_field((string)$params[$key]);}
		$query=WCA_Plan_Guard::resolve_public_slot_query($clean); if(is_wp_error($query)){return $query;}
		$result=WCA_Service::search_slots($query); if(is_wp_error($result)){return $result;} $items=array();
		foreach(self::apply_slot_policies((array)($result['slots'] ?? array())) as $slot){if(!self::external_busy_conflict_ref($slot['practitioner_ref'] ?? '',$slot['start_utc'] ?? '',$slot['end_utc'] ?? '')){$items[]=$slot;}}
		return array('contract'=>'wca.smart-scheduling-links','version'=>self::CONTRACT_VERSION,'operation'=>'find','slots'=>$items,'freshness_version'=>$result['freshness_version'] ?? '','generated_at_utc'=>$result['generated_at_utc'] ?? gmdate('c'),'authoritative_owner'=>'File08');
	}

	public static function smart_hold( $data, $actor = 0 ) { $guard = self::guard_slot_hold_data( $data ); if ( is_wp_error( $guard ) ) { return $guard; } $result = WCA_Service::hold_slot( $data, $actor ); return is_wp_error( $result ) ? $result : array( 'contract' => 'wca.smart-scheduling-links', 'version' => self::CONTRACT_VERSION, 'operation' => 'hold', 'hold_token' => isset( $result['hold_token'] ) ? $result['hold_token'] : '', 'expires_at_utc' => isset( $result['expires_at'] ) ? $result['expires_at'] : '' ); }
	public static function smart_book( $data, $actor = 0 ) { $result = wca_request_appointment_command( $data, $actor ); return is_wp_error( $result ) ? $result : array_merge( array( 'contract' => 'wca.smart-scheduling-links', 'version' => self::CONTRACT_VERSION, 'operation' => 'book' ), $result ); }

	/* FUT-22 */
	public static function save_external_busy( $data, $actor = 0 ) {
		$doctor_id = absint( $actor ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $doctor_id );
		if ( is_wp_error( $claims ) || empty( $claims['doctor'] ) ) { return new WP_Error( 'wca_external_calendar_doctor', __( 'Verified doctor authority is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$start = self::utc( isset( $data['start_utc'] ) ? $data['start_utc'] : '' );
		$end = self::utc( isset( $data['end_utc'] ) ? $data['end_utc'] : '' );
		$start_ts = $start ? strtotime( $start . ' UTC' ) : 0;
		$end_ts = $end ? strtotime( $end . ' UTC' ) : 0;
		if ( ! $start || ! $end || $end <= $start || $end_ts - $start_ts > 14 * DAY_IN_SECONDS || $start_ts < time() - DAY_IN_SECONDS || $start_ts > time() + 180 * DAY_IN_SECONDS ) {
			return new WP_Error( 'wca_external_busy_window', __( 'A valid external busy window within the next 180 days and no longer than 14 days is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$practitioner_ref = WCA_Plan_Guard::practitioner_ref( $doctor_id );
		if ( ! $practitioner_ref ) { return new WP_Error( 'wca_external_calendar_practitioner', __( 'A canonical practitioner reference is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		return self::put_record( 'F08-FUT-22', array(
			'subject_user_id' => $doctor_id,
			'parent_ref' => $practitioner_ref,
			'status' => 'busy',
			'starts_at' => $start,
			'ends_at' => $end,
			'expires_at' => gmdate( 'Y-m-d H:i:s', min( $end_ts + DAY_IN_SECONDS, time() + 181 * DAY_IN_SECONDS ) ),
			'payload' => array( 'calendar_ref_hash' => hash( 'sha256', sanitize_text_field( isset( $data['calendar_ref'] ) ? $data['calendar_ref'] : 'external' ) ), 'provider_token_stored' => false, 'source' => 'external_busy_projection' ),
		), $actor );
	}

	private static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) {
		global $wpdb; $start=self::utc($start); $end=self::utc($end); $practitioner_ref=sanitize_text_field($practitioner_ref); if(!$practitioner_ref||!$start||!$end){return false;} $table=self::tables()['records'];
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND parent_ref=%s AND status='busy' AND (expires_at IS NULL OR expires_at>%s) AND starts_at<%s AND ends_at>%s LIMIT 1", $practitioner_ref, WCA_Repository::now(), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* FUT-23 */
	public static function create_episode( $data, $actor = 0 ) {
		$raw_refs = (array) ( isset( $data['appointment_refs'] ) ? $data['appointment_refs'] : array() );
		if ( count( $raw_refs ) > 50 ) { return new WP_Error( 'wca_episode_appointment_limit', __( 'No more than 50 appointments may be linked in one episode.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
		$refs = array();
		$scope = null;
		foreach ( $raw_refs as $ref ) {
			$id = self::require_appointment( $ref, $actor );
			if ( is_wp_error( $id ) ) { return $id; }
			$current = array(
				'patient_id' => absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) ),
				'doctor_id' => absint( SWC_Helpers::meta( $id, 'doctor_id', 0 ) ),
				'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ),
			);
			if ( null === $scope ) { $scope = $current; }
			elseif ( $scope !== $current ) { return new WP_Error( 'wca_episode_scope', __( 'Every appointment in an episode must belong to the same patient, doctor, and clinic scope.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$refs[] = strtolower( sanitize_text_field( $ref ) );
		}
		$refs = array_values( array_unique( $refs ) );
		if ( ! $refs ) { return new WP_Error( 'wca_episode_appointments', __( 'At least one authorized appointment is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-23', array(
			'appointment_id' => self::appointment_id( $refs[0] ),
			'clinic_id' => absint( isset( $scope['clinic_id'] ) ? $scope['clinic_id'] : 0 ),
			'status' => 'episode_open',
			'payload' => array( 'appointment_refs' => $refs, 'clinical_narrative_stored' => false, 'public_timeline' => false, 'scope_consistency' => 'same_patient_doctor_clinic' ),
		), $actor );
	}

	/* FUT-24 */
	public static function governance_log( $actor = 0 ) {
		$claims = WCA_Authorization::claims( $actor ); if ( is_wp_error( $claims ) ) { return $claims; }
		return array( 'contract' => 'wca.future24-governance', 'version' => self::CONTRACT_VERSION, 'policy_version' => self::POLICY_VERSION, 'capability_count' => count( self::capabilities() ), 'automatic_diagnosis' => false, 'automatic_prescribing' => false, 'emergency_replacement' => false, 'donor_or_paid_visibility' => false, 'clinical_actions_require_human_professional' => true, 'cross_file_writes' => false );
	}

	private static function clinic_appointments( $clinic_id, $days, $limit ) {
		$after = gmdate( 'Y-m-d H:i:s', time() - absint( $days ) * DAY_IN_SECONDS );
		return self::clinic_appointments_between( $clinic_id, $after, gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ), $limit );
	}

	/** Return the complete affected appointment set in bounded pages; no silent fixed-count truncation. */
	private static function clinic_appointments_between_all( $clinic_id, $from, $to ) {
		$out = array();
		$page = 1;
		$batch = 200;
		do {
			$q = new WP_Query( array(
				'post_type' => SWC_Helpers::TYPE,
				'post_status' => array( 'private','publish' ),
				'fields' => 'ids',
				'posts_per_page' => $batch,
				'paged' => $page,
				'orderby' => 'ID',
				'order' => 'ASC',
				'no_found_rows' => true,
				'meta_query' => array(
					array( 'key' => '_swc_clinic_id', 'value' => absint( $clinic_id ), 'compare' => '=' ),
					array( 'key' => '_swc_preferred_at_utc', 'value' => array( self::utc( $from ), self::utc( $to ) ), 'compare' => 'BETWEEN', 'type' => 'DATETIME' ),
				),
			) );
			$ids = array_map( 'absint', (array) $q->posts );
			$out = array_merge( $out, $ids );
			$page++;
		} while ( count( $ids ) === $batch );
		return array_values( array_unique( $out ) );
	}

	private static function clinic_appointments_between( $clinic_id, $from, $to, $limit ) {
		$q = new WP_Query( array(
			'post_type' => SWC_Helpers::TYPE,
			'post_status' => array( 'private','publish' ),
			'fields' => 'ids',
			'posts_per_page' => min( 2000, max( 1, absint( $limit ) ) ),
			'no_found_rows' => true,
			'meta_query' => array(
				array( 'key' => '_swc_clinic_id', 'value' => absint( $clinic_id ), 'compare' => '=' ),
				array( 'key' => '_swc_preferred_at_utc', 'value' => array( self::utc( $from ), self::utc( $to ) ), 'compare' => 'BETWEEN', 'type' => 'DATETIME' ),
			),
		) );
		return array_map( 'absint', (array) $q->posts );
	}

	public static function maintenance() {
		global $wpdb; $table=self::tables()['records']; $now=WCA_Repository::now();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='expired',updated_at=%s,version=version+1 WHERE expires_at IS NOT NULL AND expires_at<%s AND status IN ('waiting','open','reserved','group_open','group_member','arrived','participant_active','room_requested','busy','disruption_active')", $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* REST wrappers */
	public static function rest_waitlist( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'waitlist','join_waitlist',array($d),201); }
	public static function rest_windows( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'windows','save_windows',array($d),201); }
	public static function rest_series( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'series','create_series',array($d),201); }
	public static function rest_resource( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'resource','create_resource',array($d),201); }
	public static function rest_resource_reserve( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'resource_reserve','reserve_resource',array($r['ref'],$d),201); }
	public static function rest_group_session( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'group_session','create_group_session',array($d),201); }
	public static function rest_group_join( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'group_join','join_group_session',array($r['ref'],$d),201); }
	public static function rest_group_leave( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'group_leave','leave_group_session',array($r['ref'],$d),200); }
	public static function rest_group_cancel( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'group_cancel','cancel_group_session',array($r['ref'],$d),200); }
	public static function rest_safe_reschedule( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'safe_reschedule','safe_reschedule',array($r['ref'],$d),200); }
	public static function rest_buffers( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'buffers','set_buffers',array($d),200); }
	public static function rest_heatmap( WP_REST_Request $r ){ return self::respond(self::heatmap(absint($r->get_param('clinic_id')),absint($r->get_param('days')?:30))); }
	public static function rest_advisor( WP_REST_Request $r ){ return self::respond(self::advisor(absint($r->get_param('clinic_id')))); }
	public static function rest_no_show( WP_REST_Request $r ){ return self::respond(self::no_show_forecast(absint($r->get_param('clinic_id')))); }
	public static function rest_questionnaire( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'questionnaire','save_questionnaire',array($d),201); }
	public static function rest_questionnaire_for_appointment( WP_REST_Request $r ){ return self::respond(self::questionnaire_for_appointment($r['ref'])); }
	public static function rest_readiness( WP_REST_Request $r ){ return self::respond(self::readiness($r['ref'])); }
	public static function rest_prerequisites( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'prerequisites','save_prerequisites',array($d),201); }
	public static function rest_family(){ return self::respond(self::family_hub()); }
	public static function rest_arrive( WP_REST_Request $r ){ return self::mutate($r,'arrive','arrive',array($r['ref']),201); }
	public static function rest_queue( WP_REST_Request $r ){ return self::respond(self::queue_position($r['ref'])); }
	public static function rest_disruption( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'disruption','create_disruption',array($d),201); }
	public static function rest_participant( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'participant','add_participant',array($r['ref'],$d),201); }
	public static function rest_participant_revoke( WP_REST_Request $r ){ return self::mutate($r,'participant_revoke','revoke_participant',array($r['ref'],$r['participant']),200); }
	public static function rest_virtual_room( WP_REST_Request $r ){ return self::mutate($r,'virtual_room','request_virtual_room',array($r['ref']),202); }
	public static function rest_fhir( WP_REST_Request $r ){ return self::respond(self::fhir_projection($r['type'],$r['ref'])); }
	public static function rest_smart_find( WP_REST_Request $r ){ return self::respond(self::smart_find($r->get_params())); }
	public static function rest_smart_hold( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'smart_hold','smart_hold',array($d),201); }
	public static function rest_smart_book( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'smart_book','smart_book',array($d),201); }
	public static function rest_external_busy( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'external_busy','save_external_busy',array($d),201); }
	public static function rest_episode( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'episode','create_episode',array($d),201); }
	public static function rest_governance(){ return self::respond(self::governance_log()); }
}

/** Public helper for canonical cross-file feature discovery. */
function wca_future24_manifest() { return WCA_Future24::capabilities(); }
