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
		$auth = array( __CLASS__, 'authenticated' );
		$routes = array(
			'/future24/waitlist' => array( 'POST', 'rest_waitlist' ),
			'/future24/windows' => array( 'POST', 'rest_windows' ),
			'/future24/series' => array( 'POST', 'rest_series' ),
			'/future24/resources' => array( 'POST', 'rest_resource' ),
			'/future24/resources/(?P<ref>[0-9a-fA-F-]{36})/reserve' => array( 'POST', 'rest_resource_reserve' ),
			'/future24/group-sessions' => array( 'POST', 'rest_group_session' ),
			'/future24/group-sessions/(?P<ref>[0-9a-fA-F-]{36})/join' => array( 'POST', 'rest_group_join' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/safe-reschedule' => array( 'POST', 'rest_safe_reschedule' ),
			'/future24/buffers' => array( 'POST', 'rest_buffers' ),
			'/future24/heatmap' => array( 'GET', 'rest_heatmap' ),
			'/future24/advisor' => array( 'GET', 'rest_advisor' ),
			'/future24/no-show-forecast' => array( 'GET', 'rest_no_show' ),
			'/future24/questionnaires' => array( 'POST', 'rest_questionnaire' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/readiness' => array( 'GET', 'rest_readiness' ),
			'/future24/prerequisites' => array( 'POST', 'rest_prerequisites' ),
			'/future24/family' => array( 'GET', 'rest_family' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/arrive' => array( 'POST', 'rest_arrive' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/queue' => array( 'GET', 'rest_queue' ),
			'/future24/disruptions' => array( 'POST', 'rest_disruption' ),
			'/future24/appointments/(?P<ref>[0-9a-fA-F-]{36})/participants' => array( 'POST', 'rest_participant' ),
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
		return is_array( $data ) ? $data : $request->get_params();
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
		$payload = self::sanitize_operational_payload( isset( $data['payload'] ) && is_array( $data['payload'] ) ? $data['payload'] : array() );
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
		self::audit( $feature_id, 'record_created', $row['public_ref'], array( 'status' => $row['status'] ), $row['actor_user_id'], false );
		return self::public_record( array_merge( $row, array( 'id' => $wpdb->insert_id ) ) );
	}

	private static function sanitize_operational_payload( $payload ) {
		$out = array();
		$blocked = '/(diagnos|prescri|symptom|reason|clinical_note|private_note|intake_answer|patient_name|email|phone|whatsapp|address_private|treatment|remedy)/i';
		foreach ( array_slice( (array) $payload, 0, 80, true ) as $key => $value ) {
			$key = sanitize_key( $key );
			if ( ! $key || preg_match( $blocked, $key ) ) { continue; }
			if ( is_bool( $value ) ) { $out[ $key ] = $value; continue; }
			if ( is_int( $value ) || is_float( $value ) ) { $out[ $key ] = $value; continue; }
			if ( is_string( $value ) ) { $out[ $key ] = substr( sanitize_text_field( $value ), 0, 1000 ); continue; }
			if ( is_array( $value ) ) {
				$items = array();
				foreach ( array_slice( $value, 0, 50 ) as $item ) {
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
		$value = sanitize_text_field( (string) $value );
		if ( ! $value ) { return null; }
		$ts = strtotime( $value . ( false === stripos( $value, 'UTC' ) && false === strpos( $value, 'Z' ) ? ' UTC' : '' ) );
		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
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
		if ( class_exists( 'WCA_Repository' ) ) {
			WCA_Repository::append_event( 'Future24GovernanceRecorded.v1', 'future24', sanitize_text_field( $object_ref ), $payload, $actor_user_id, WCA_Observability::trace_id() );
		}
	}

	/* FUT-01 */
	public static function join_waitlist( $data, $actor = 0 ) {
		$clinic_id = absint( isset( $data['clinic_id'] ) ? $data['clinic_id'] : 0 );
		if ( ! $clinic_id || ! WCA_Repository::get_clinic( $clinic_id, true ) ) { return new WP_Error( 'wca_waitlist_clinic', __( 'An active clinic is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-01', array(
			'clinic_id' => $clinic_id,
			'subject_user_id' => absint( $actor ?: get_current_user_id() ),
			'status' => 'waiting',
			'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 90 * DAY_IN_SECONDS ),
			'payload' => array(
				'service_ref' => sanitize_text_field( isset( $data['service_ref'] ) ? $data['service_ref'] : '' ),
				'date_from' => sanitize_text_field( isset( $data['date_from'] ) ? $data['date_from'] : '' ),
				'date_to' => sanitize_text_field( isset( $data['date_to'] ) ? $data['date_to'] : '' ),
				'timezone' => sanitize_text_field( isset( $data['timezone'] ) ? $data['timezone'] : 'UTC' ),
				'auto_book' => false,
			),
		), $actor );
	}

	/* FUT-02 */
	public static function save_windows( $data, $actor = 0 ) {
		$windows = array();
		foreach ( array_slice( (array) ( isset( $data['windows'] ) ? $data['windows'] : array() ), 0, 12 ) as $window ) {
			if ( ! is_array( $window ) ) { continue; }
			$start = self::utc( isset( $window['start'] ) ? $window['start'] : '' );
			$end = self::utc( isset( $window['end'] ) ? $window['end'] : '' );
			if ( $start && $end && $end > $start ) { $windows[] = $start . '/' . $end; }
		}
		if ( ! $windows ) { return new WP_Error( 'wca_windows_required', __( 'At least one valid appointment window is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-02', array( 'clinic_id' => absint( $data['clinic_id'] ?? 0 ), 'subject_user_id' => absint( $actor ?: get_current_user_id() ), 'status' => 'open', 'payload' => array( 'windows' => $windows, 'service_ref' => sanitize_text_field( $data['service_ref'] ?? '' ), 'timezone' => sanitize_text_field( $data['timezone'] ?? 'UTC' ) ) ), $actor );
	}

	/* FUT-03 */
	public static function create_series( $data, $actor = 0 ) {
		$appointment = self::require_appointment( sanitize_text_field( $data['appointment_ref'] ?? '' ), $actor );
		if ( is_wp_error( $appointment ) ) { return $appointment; }
		$frequency = sanitize_key( $data['frequency'] ?? 'weekly' );
		if ( ! in_array( $frequency, array( 'weekly', 'monthly', 'custom_days' ), true ) ) { return new WP_Error( 'wca_series_frequency', __( 'Unsupported recurrence frequency.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$count = min( 24, max( 1, absint( $data['count'] ?? 1 ) ) );
		$interval = min( 12, max( 1, absint( $data['interval'] ?? 1 ) ) );
		$origin = self::utc( SWC_Helpers::meta( $appointment, 'preferred_at_utc', '' ) );
		if ( ! $origin ) { return new WP_Error( 'wca_series_origin', __( 'The originating appointment needs a valid scheduled time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$occurrences = array();
		$cursor = new DateTimeImmutable( $origin . ' UTC' );
		$custom_days = min( 365, max( 1, absint( $data['custom_days'] ?? $interval ) ) );
		for ( $i = 1; $i <= $count; $i++ ) {
			if ( 'weekly' === $frequency ) { $cursor = $cursor->modify( '+' . $interval . ' weeks' ); }
			elseif ( 'monthly' === $frequency ) { $cursor = $cursor->modify( '+' . $interval . ' months' ); }
			else { $cursor = $cursor->modify( '+' . $custom_days . ' days' ); }
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
		return self::put_record( 'F08-FUT-04', array( 'clinic_id' => $clinic_id, 'status' => 'resource_active', 'capacity' => max( 1, absint( $data['capacity'] ?? 1 ) ), 'payload' => array( 'resource_type' => $type, 'label' => sanitize_text_field( $data['label'] ?? '' ), 'branch_ref' => sanitize_text_field( $data['branch_ref'] ?? '' ) ) ), $actor );
	}

	public static function reserve_resource( $resource_ref, $data, $actor = 0 ) {
		global $wpdb;
		$resource = self::get_record( $resource_ref, 'F08-FUT-04' );
		if ( ! $resource || 'resource_active' !== $resource['status'] ) { return new WP_Error( 'wca_resource_missing', __( 'Scheduling resource is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$clinic = self::require_clinic_manager( $resource['clinic_id'], $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$start = self::utc( $data['start_utc'] ?? '' ); $end = self::utc( $data['end_utc'] ?? '' );
		if ( ! $start || ! $end || $end <= $start ) { return new WP_Error( 'wca_resource_window', __( 'A valid UTC resource window is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$lock = 'wca-f24-resource-' . substr( hash( 'sha256', $resource_ref ), 0, 32 );
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) );
		if ( 1 !== $locked ) { return new WP_Error( 'wca_resource_busy', __( 'The resource is being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		try {
			$table = self::tables()['records'];
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-04' AND parent_ref=%s AND status='reserved' AND starts_at<%s AND ends_at>%s", strtolower( $resource_ref ), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $count >= max( 1, absint( $resource['capacity'] ) ) ) { return new WP_Error( 'wca_resource_conflict', __( 'The resource is already at capacity for this time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$resource_payload = json_decode( (string) $resource['payload_json'], true );
			$resource_type = is_array( $resource_payload ) && isset( $resource_payload['resource_type'] ) ? sanitize_key( $resource_payload['resource_type'] ) : '';
			return self::put_record( 'F08-FUT-04', array( 'clinic_id' => absint( $resource['clinic_id'] ), 'appointment_id' => self::appointment_id( $data['appointment_ref'] ?? '' ), 'parent_ref' => strtolower( $resource_ref ), 'status' => 'reserved', 'starts_at' => $start, 'ends_at' => $end, 'payload' => array( 'reservation_kind' => 'multi_resource', 'resource_type' => $resource_type ) ), $actor );
		} finally { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
	}

	/* FUT-05 */
	public static function create_group_session( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$capacity = min( 100, max( 2, absint( $data['capacity'] ?? 2 ) ) );
		return self::put_record( 'F08-FUT-05', array( 'clinic_id' => $clinic_id, 'status' => 'group_open', 'starts_at' => $data['start_utc'] ?? '', 'ends_at' => $data['end_utc'] ?? '', 'capacity' => $capacity, 'payload' => array( 'service_ref' => sanitize_text_field( $data['service_ref'] ?? '' ), 'visibility' => 'participants_private' ) ), $actor );
	}

	public static function join_group_session( $session_ref, $actor = 0 ) {
		global $wpdb;
		$actor = absint( $actor ?: get_current_user_id() );
		$session = self::get_record( $session_ref, 'F08-FUT-05' );
		if ( ! $session || 'group_open' !== $session['status'] ) { return new WP_Error( 'wca_group_missing', __( 'Group appointment session is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$table = self::tables()['records'];
		$lock = 'wca-f24-group-' . substr( hash( 'sha256', $session_ref ), 0, 32 );
		if ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) { return new WP_Error( 'wca_group_busy', __( 'The group session is being updated.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		try {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT public_ref FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status='group_member' LIMIT 1", $session_ref, $actor ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $exists ) { return self::public_record( self::get_record( $exists, 'F08-FUT-05' ) ); }
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND status='group_member'", $session_ref ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $count >= absint( $session['capacity'] ) ) { return new WP_Error( 'wca_group_full', __( 'This group appointment is full.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			return self::put_record( 'F08-FUT-05', array( 'clinic_id' => absint( $session['clinic_id'] ), 'subject_user_id' => $actor, 'parent_ref' => $session_ref, 'status' => 'group_member', 'payload' => array( 'member_ref' => WCA_Authorization::subject_uuid( $actor ) ) ), $actor );
		} finally { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
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
		global $wpdb;
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$before = min( 240, absint( $data['buffer_before'] ?? 0 ) );
		$after = min( 240, absint( $data['buffer_after'] ?? 0 ) );
		$table = WCA_Schema::tables()['availability'];
		$where = array( 'clinic_id' => $clinic_id, 'status' => 'active' );
		$updated = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET buffer_before=%d, buffer_after=%d, version=version+1, updated_at=%s WHERE clinic_id=%d AND status='active'", $before, $after, WCA_Repository::now(), $clinic_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $updated ) { return new WP_Error( 'wca_buffer_update', __( 'Buffer policy could not be updated.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		return self::put_record( 'F08-FUT-07', array( 'clinic_id' => $clinic_id, 'status' => 'policy_active', 'payload' => array( 'buffer_before' => $before, 'buffer_after' => $after, 'travel_gap_minutes' => min( 480, absint( $data['travel_gap_minutes'] ?? 0 ) ), 'max_continuous_consultations' => min( 30, absint( $data['max_continuous_consultations'] ?? 0 ) ) ) ), $actor );
	}

	/* FUT-08 */
	public static function heatmap( $clinic_id, $days = 30, $actor = 0 ) {
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$days = min( 90, max( 7, absint( $days ) ) );
		$ids = self::clinic_appointments( $clinic_id, $days, 1000 );
		$map = array();
		foreach ( $ids as $id ) {
			$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' );
			if ( ! $when ) { continue; }
			$day = substr( $when, 0, 10 );
			if ( ! isset( $map[ $day ] ) ) { $map[ $day ] = array( 'total' => 0, 'completed' => 0, 'cancelled' => 0, 'no_show' => 0 ); }
			$map[ $day ]['total']++;
			$status = SWC_Helpers::status( $id );
			if ( isset( $map[ $day ][ $status ] ) ) { $map[ $day ][ $status ]++; }
		}
		ksort( $map );
		return array( 'contract' => 'wca.capacity-heatmap', 'version' => self::CONTRACT_VERSION, 'clinic_ref' => (string) $clinic['public_ref'], 'days' => $map, 'privacy' => 'aggregate_only' );
	}

	/* FUT-09 */
	public static function advisor( $clinic_id, $actor = 0 ) {
		$heat = self::heatmap( $clinic_id, 30, $actor );
		if ( is_wp_error( $heat ) ) { return $heat; }
		$items = array();
		foreach ( $heat['days'] as $date => $row ) {
			if ( $row['total'] >= 8 ) { $items[] = array( 'date' => $date, 'type' => 'high_demand', 'suggestion' => 'Consider opening additional capacity or buffer review.', 'auto_apply' => false ); }
			if ( $row['total'] <= 1 ) { $items[] = array( 'date' => $date, 'type' => 'low_demand', 'suggestion' => 'Consider consolidating availability if clinically and operationally appropriate.', 'auto_apply' => false ); }
		}
		return array( 'contract' => 'wca.schedule-advisor', 'version' => self::CONTRACT_VERSION, 'advisory_only' => true, 'recommendations' => array_slice( $items, 0, 30 ) );
	}

	/* FUT-10 */
	public static function no_show_forecast( $clinic_id, $actor = 0 ) {
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$ids = self::clinic_appointments( $clinic_id, 365, 2000 );
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
		$allowed = array( 'reason', 'category', 'symptoms_summary', 'medications_summary', 'allergies_summary', 'accessibility_needs', 'preferred_language', 'notes' );
		$fields = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $data['fields'] ?? array() ) ), $allowed ) );
		if ( ! $fields ) { return new WP_Error( 'wca_questionnaire_fields', __( 'At least one approved secure intake field is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-11', array( 'clinic_id' => $clinic_id, 'status' => 'template_active', 'payload' => array( 'service_ref' => sanitize_text_field( $data['service_ref'] ?? '' ), 'fields' => $fields, 'answers_owner' => 'WCA_Continuity encrypted intake', 'automated_diagnosis' => false ) ), $actor );
	}

	/* FUT-12 */
	public static function readiness( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$id = self::require_appointment( $appointment_ref, $actor );
		if ( is_wp_error( $id ) ) { return $id; }
		$consent = class_exists( 'WCA_Continuity_Guards' ) ? WCA_Continuity_Guards::consent_state( $appointment_ref, absint( $actor ?: get_current_user_id() ) ) : array();
		$intake = WCA_Continuity::tables()['intake'];
		$intake_status = (string) $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$intake} WHERE appointment_id=%d LIMIT 1", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$prereq = self::prerequisite_state( $id );
		$checks = array(
			'appointment_confirmed' => in_array( SWC_Helpers::status( $id ), array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true ),
			'privacy_consent_current' => ! is_wp_error( $consent ) && ! empty( $consent['scopes']['privacy_notice'] ) && 'granted' === $consent['scopes']['privacy_notice']['status'],
			'previsit_submitted' => 'submitted' === $intake_status,
			'prerequisites_complete' => $prereq['complete'],
			'guardian_recheck_runtime' => true,
		);
		$ready = ! in_array( false, $checks, true );
		return array( 'contract' => 'wca.appointment-readiness', 'version' => self::CONTRACT_VERSION, 'appointment_ref' => strtolower( $appointment_ref ), 'ready' => $ready, 'state' => $ready ? 'ready' : 'action_required', 'checks' => $checks, 'prerequisites' => $prereq );
	}

	/* FUT-13 */
	public static function save_prerequisites( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$clinic = self::require_clinic_manager( $clinic_id, $actor );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$required = array();
		foreach ( array_slice( (array) ( $data['requirements'] ?? array() ), 0, 20 ) as $item ) {
			if ( ! is_array( $item ) ) { continue; }
			$required[] = sanitize_key( $item['type'] ?? 'document' ) . ':' . substr( sanitize_text_field( $item['label'] ?? '' ), 0, 120 );
		}
		return self::put_record( 'F08-FUT-13', array( 'clinic_id' => $clinic_id, 'status' => 'rule_active', 'payload' => array( 'service_ref' => sanitize_text_field( $data['service_ref'] ?? '' ), 'requirements' => $required, 'missing_behavior' => in_array( $data['missing_behavior'] ?? '', array( 'provisional', 'block' ), true ) ? $data['missing_behavior'] : 'provisional' ) ), $actor );
	}

	private static function prerequisite_state( $appointment_id ) {
		global $wpdb;
		$table = self::tables()['records'];
		$clinic_id = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) );
		$rules = (array) $wpdb->get_results( $wpdb->prepare( "SELECT payload_json FROM {$table} WHERE feature_id='F08-FUT-13' AND clinic_id=%d AND status='rule_active' ORDER BY id DESC LIMIT 20", $clinic_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$requirements = array(); foreach ( $rules as $row ) { $p=json_decode($row['payload_json'],true); foreach((array)($p['requirements']??array()) as $r){$requirements[$r]=true;} }
		$evidence = (array) SWC_Helpers::meta( $appointment_id, 'prerequisite_evidence_refs', array() );
		$complete = ! $requirements || count( $evidence ) >= count( $requirements );
		return array( 'required_count' => count( $requirements ), 'evidence_count' => count( $evidence ), 'complete' => $complete );
	}

	/* FUT-14 */
	public static function family_hub( $actor = 0 ) {
		$actor = absint( $actor ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor ); if ( is_wp_error( $claims ) ) { return $claims; }
		if ( empty( $claims['guardian'] ) ) { return array( 'contract' => 'wca.family-hub', 'version' => self::CONTRACT_VERSION, 'guardian' => false, 'appointments' => array() ); }
		$q = new WP_Query( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'fields' => 'ids', 'posts_per_page' => 100, 'no_found_rows' => true, 'meta_key' => '_swc_guardian_user_id', 'meta_value' => $actor ) );
		$out = array(); foreach ( (array) $q->posts as $id ) { $out[] = array( 'appointment_ref' => self::appointment_ref( $id ), 'status' => SWC_Helpers::status( $id ), 'scheduled_at_utc' => (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) ); }
		return array( 'contract' => 'wca.family-hub', 'version' => self::CONTRACT_VERSION, 'guardian' => true, 'appointments' => $out, 'relationship_recheck' => 'required_on_every_protected_action' );
	}

	/* FUT-15 */
	public static function arrive( $appointment_ref, $actor = 0 ) {
		$id = self::require_appointment( $appointment_ref, $actor ); if ( is_wp_error( $id ) ) { return $id; }
		$who = WCA_Authorization::appointment_actor( $id, absint( $actor ?: get_current_user_id() ) );
		if ( ! in_array( $who, array( 'patient','guardian' ), true ) ) { return new WP_Error( 'wca_arrival_actor', __( 'Only the patient or verified guardian may announce arrival.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		return self::put_record( 'F08-FUT-15', array( 'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ), 'appointment_id' => $id, 'subject_user_id' => absint( $actor ?: get_current_user_id() ), 'status' => 'arrived', 'starts_at' => WCA_Repository::now(), 'payload' => array( 'queue_token' => substr( hash( 'sha256', strtolower( $appointment_ref ) . '|' . wp_salt( 'nonce' ) ), 0, 12 ), 'clinical_checkin' => false ) ), $actor );
	}

	/* FUT-16 */
	public static function queue_position( $appointment_ref, $actor = 0 ) {
		global $wpdb;
		$id = self::require_appointment( $appointment_ref, $actor ); if ( is_wp_error( $id ) ) { return $id; }
		$table = self::tables()['records']; $clinic_id = absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) );
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND status='arrived' ORDER BY id DESC LIMIT 1", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $current ) { return new WP_Error( 'wca_queue_not_arrived', __( 'Arrival has not been registered.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$ahead = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-15' AND clinic_id=%d AND status='arrived' AND created_at<%s", $clinic_id, $current['created_at'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'contract' => 'wca.private-queue-position', 'version' => self::CONTRACT_VERSION, 'appointment_ref' => strtolower( $appointment_ref ), 'appointments_ahead' => $ahead, 'estimated_delay_minutes' => $ahead * 15, 'other_patient_identity_exposed' => false );
	}

	/* FUT-17 */
	public static function create_disruption( $data, $actor = 0 ) {
		$clinic_id = absint( $data['clinic_id'] ?? 0 ); $clinic = self::require_clinic_manager( $clinic_id, $actor ); if ( is_wp_error( $clinic ) ) { return $clinic; }
		$record = self::put_record( 'F08-FUT-17', array( 'clinic_id' => $clinic_id, 'status' => 'disruption_active', 'starts_at' => $data['start_utc'] ?? '', 'ends_at' => $data['end_utc'] ?? '', 'payload' => array( 'reason_code' => sanitize_key( $data['reason_code'] ?? 'operational_delay' ), 'rebooking_mode' => 'offer_only', 'auto_cancel' => false ) ), $actor );
		if ( is_wp_error( $record ) ) { return $record; }
		WCA_Repository::enqueue( 'File19.NotificationRequested.v1', $record['public_ref'], array( 'event' => 'clinic_disruption', 'clinic_ref' => (string) $clinic['public_ref'], 'disruption_ref' => $record['public_ref'], 'delivery_owner' => 'File19' ), WCA_Observability::trace_id() );
		return $record;
	}

	/* FUT-18 */
	public static function add_participant( $appointment_ref, $data, $actor = 0 ) {
		$id = self::require_appointment( $appointment_ref, $actor ); if ( is_wp_error( $id ) ) { return $id; }
		$who = WCA_Authorization::appointment_actor( $id, absint( $actor ?: get_current_user_id() ) );
		if ( ! in_array( $who, array( 'patient','guardian' ), true ) ) { return new WP_Error( 'wca_support_actor', __( 'Only the patient or verified guardian may add a support participant.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$subject = strtolower( sanitize_text_field( $data['subject_uuid'] ?? '' ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $subject ) || ! self::subject_user_id( $subject ) ) { return new WP_Error( 'wca_support_subject', __( 'A valid platform participant subject reference is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$role = sanitize_key( $data['role'] ?? 'support' ); if ( ! in_array( $role, array( 'support','interpreter' ), true ) ) { $role = 'support'; }
		return self::put_record( 'F08-FUT-18', array( 'appointment_id' => $id, 'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ), 'status' => 'participant_active', 'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 7 * DAY_IN_SECONDS ), 'payload' => array( 'subject_uuid' => $subject, 'role' => $role, 'appointment_bound' => true, 'revocable' => true ) ), $actor );
	}

	/* FUT-19 */
	public static function request_virtual_room( $appointment_ref, $actor = 0 ) {
		$id = self::require_appointment( $appointment_ref, $actor ); if ( is_wp_error( $id ) ) { return $id; }
		$type = sanitize_key( SWC_Helpers::meta( $id, 'consultation_type', '' ) );
		if ( ! in_array( $type, array( 'online','hybrid' ), true ) ) { return new WP_Error( 'wca_virtual_room_mode', __( 'A virtual room is only available for online or hybrid appointments.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$record = self::put_record( 'F08-FUT-19', array( 'appointment_id' => $id, 'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ), 'status' => 'room_requested', 'expires_at' => gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ), 'payload' => array( 'appointment_ref' => strtolower( $appointment_ref ), 'transport_owner' => 'File17', 'recording_assumed' => false ) ), $actor );
		if ( ! is_wp_error( $record ) ) { WCA_Repository::enqueue( 'File17.VirtualRoomRequested.v1', strtolower( $appointment_ref ), array( 'appointment_ref' => strtolower( $appointment_ref ), 'request_ref' => $record['public_ref'], 'recording_allowed' => false ), WCA_Observability::trace_id() ); }
		return $record;
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
		$query = WCA_Plan_Guard::resolve_public_slot_query( array_map( 'sanitize_text_field', $params ) );
		if ( is_wp_error( $query ) ) { return $query; }
		$result = WCA_Service::search_slots( $query );
		if ( is_wp_error( $result ) ) { return $result; }
		$items = array();
		foreach ( (array) ( isset( $result['slots'] ) ? $result['slots'] : array() ) as $slot ) {
			if ( ! self::external_busy_conflict_ref( isset( $slot['practitioner_ref'] ) ? $slot['practitioner_ref'] : '', isset( $slot['start_utc'] ) ? $slot['start_utc'] : '', isset( $slot['end_utc'] ) ? $slot['end_utc'] : '' ) ) { $items[] = $slot; }
		}
		return array( 'contract' => 'wca.smart-scheduling-links', 'version' => self::CONTRACT_VERSION, 'operation' => 'find', 'slots' => $items, 'freshness_version' => isset( $result['freshness_version'] ) ? $result['freshness_version'] : '', 'generated_at_utc' => isset( $result['generated_at_utc'] ) ? $result['generated_at_utc'] : gmdate( 'c' ), 'authoritative_owner' => 'File08' );
	}

	public static function smart_hold( $data, $actor = 0 ) { $result = WCA_Service::hold_slot( $data, $actor ); return is_wp_error( $result ) ? $result : array( 'contract' => 'wca.smart-scheduling-links', 'version' => self::CONTRACT_VERSION, 'operation' => 'hold', 'hold_token' => $result['hold_token'] ?? '', 'expires_at_utc' => $result['expires_at'] ?? '' ); }
	public static function smart_book( $data, $actor = 0 ) { $result = wca_request_appointment_command( $data, $actor ); return is_wp_error( $result ) ? $result : array_merge( array( 'contract' => 'wca.smart-scheduling-links', 'version' => self::CONTRACT_VERSION, 'operation' => 'book' ), $result ); }

	/* FUT-22 */
	public static function save_external_busy( $data, $actor = 0 ) {
		$doctor_id = absint( $actor ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $doctor_id ); if ( is_wp_error( $claims ) || empty( $claims['doctor'] ) ) { return new WP_Error( 'wca_external_calendar_doctor', __( 'Verified doctor authority is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$start = self::utc( $data['start_utc'] ?? '' ); $end = self::utc( $data['end_utc'] ?? '' ); if ( ! $start || ! $end || $end <= $start ) { return new WP_Error( 'wca_external_busy_window', __( 'A valid busy window is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$practitioner_ref = WCA_Plan_Guard::practitioner_ref( $doctor_id );
		if ( ! $practitioner_ref ) { return new WP_Error( 'wca_external_calendar_practitioner', __( 'A canonical practitioner reference is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		return self::put_record( 'F08-FUT-22', array( 'subject_user_id' => $doctor_id, 'parent_ref' => $practitioner_ref, 'status' => 'busy', 'starts_at' => $start, 'ends_at' => $end, 'expires_at' => gmdate( 'Y-m-d H:i:s', time() + 180 * DAY_IN_SECONDS ), 'payload' => array( 'calendar_ref_hash' => hash( 'sha256', sanitize_text_field( $data['calendar_ref'] ?? 'external' ) ), 'provider_token_stored' => false, 'source' => 'external_busy_projection' ) ), $actor );
	}

	private static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) {
		global $wpdb; $start=self::utc($start); $end=self::utc($end); $practitioner_ref=sanitize_text_field($practitioner_ref); if(!$practitioner_ref||!$start||!$end){return false;} $table=self::tables()['records'];
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND parent_ref=%s AND status='busy' AND starts_at<%s AND ends_at>%s LIMIT 1", $practitioner_ref, $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* FUT-23 */
	public static function create_episode( $data, $actor = 0 ) {
		$refs = array(); foreach ( array_slice( (array) ( $data['appointment_refs'] ?? array() ), 0, 50 ) as $ref ) { $id=self::require_appointment($ref,$actor); if(is_wp_error($id)){return $id;} $refs[]=strtolower(sanitize_text_field($ref)); }
		if ( ! $refs ) { return new WP_Error( 'wca_episode_appointments', __( 'At least one authorized appointment is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::put_record( 'F08-FUT-23', array( 'appointment_id' => self::appointment_id( $refs[0] ), 'clinic_id' => absint( SWC_Helpers::meta( self::appointment_id( $refs[0] ), 'clinic_id', 0 ) ), 'status' => 'episode_open', 'payload' => array( 'appointment_refs' => $refs, 'clinical_narrative_stored' => false, 'public_timeline' => false ) ), $actor );
	}

	/* FUT-24 */
	public static function governance_log( $actor = 0 ) {
		$claims = WCA_Authorization::claims( $actor ); if ( is_wp_error( $claims ) ) { return $claims; }
		return array( 'contract' => 'wca.future24-governance', 'version' => self::CONTRACT_VERSION, 'policy_version' => self::POLICY_VERSION, 'capability_count' => count( self::capabilities() ), 'automatic_diagnosis' => false, 'automatic_prescribing' => false, 'emergency_replacement' => false, 'donor_or_paid_visibility' => false, 'clinical_actions_require_human_professional' => true, 'cross_file_writes' => false );
	}

	private static function clinic_appointments( $clinic_id, $days, $limit ) {
		$after = gmdate( 'Y-m-d H:i:s', time() - absint( $days ) * DAY_IN_SECONDS );
		$q = new WP_Query( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'fields' => 'ids', 'posts_per_page' => min( 2000, absint( $limit ) ), 'no_found_rows' => true, 'meta_query' => array( array( 'key' => '_swc_clinic_id', 'value' => absint( $clinic_id ), 'compare' => '=' ), array( 'key' => '_swc_preferred_at_utc', 'value' => $after, 'compare' => '>=', 'type' => 'DATETIME' ) ) ) );
		return array_map( 'absint', (array) $q->posts );
	}

	public static function maintenance() {
		global $wpdb; $table=self::tables()['records']; $now=WCA_Repository::now();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='expired',updated_at=%s,version=version+1 WHERE expires_at IS NOT NULL AND expires_at<%s AND status IN ('waiting','reserved','participant_active','busy')", $now, $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/* REST wrappers */
	public static function rest_waitlist( WP_REST_Request $r ){ $x=self::rate('waitlist',20,HOUR_IN_SECONDS); if(is_wp_error($x)){return$x;} return self::respond(self::join_waitlist(self::data($r)),201); }
	public static function rest_windows( WP_REST_Request $r ){ return self::respond(self::save_windows(self::data($r)),201); }
	public static function rest_series( WP_REST_Request $r ){ return self::respond(self::create_series(self::data($r)),201); }
	public static function rest_resource( WP_REST_Request $r ){ return self::respond(self::create_resource(self::data($r)),201); }
	public static function rest_resource_reserve( WP_REST_Request $r ){ return self::respond(self::reserve_resource($r['ref'],self::data($r)),201); }
	public static function rest_group_session( WP_REST_Request $r ){ return self::respond(self::create_group_session(self::data($r)),201); }
	public static function rest_group_join( WP_REST_Request $r ){ return self::respond(self::join_group_session($r['ref']),201); }
	public static function rest_safe_reschedule( WP_REST_Request $r ){ return self::respond(self::safe_reschedule($r['ref'],self::data($r))); }
	public static function rest_buffers( WP_REST_Request $r ){ return self::respond(self::set_buffers(self::data($r))); }
	public static function rest_heatmap( WP_REST_Request $r ){ return self::respond(self::heatmap(absint($r->get_param('clinic_id')),absint($r->get_param('days')?:30))); }
	public static function rest_advisor( WP_REST_Request $r ){ return self::respond(self::advisor(absint($r->get_param('clinic_id')))); }
	public static function rest_no_show( WP_REST_Request $r ){ return self::respond(self::no_show_forecast(absint($r->get_param('clinic_id')))); }
	public static function rest_questionnaire( WP_REST_Request $r ){ return self::respond(self::save_questionnaire(self::data($r)),201); }
	public static function rest_readiness( WP_REST_Request $r ){ return self::respond(self::readiness($r['ref'])); }
	public static function rest_prerequisites( WP_REST_Request $r ){ return self::respond(self::save_prerequisites(self::data($r)),201); }
	public static function rest_family(){ return self::respond(self::family_hub()); }
	public static function rest_arrive( WP_REST_Request $r ){ return self::respond(self::arrive($r['ref']),201); }
	public static function rest_queue( WP_REST_Request $r ){ return self::respond(self::queue_position($r['ref'])); }
	public static function rest_disruption( WP_REST_Request $r ){ return self::respond(self::create_disruption(self::data($r)),201); }
	public static function rest_participant( WP_REST_Request $r ){ return self::respond(self::add_participant($r['ref'],self::data($r)),201); }
	public static function rest_virtual_room( WP_REST_Request $r ){ return self::respond(self::request_virtual_room($r['ref']),202); }
	public static function rest_fhir( WP_REST_Request $r ){ return self::respond(self::fhir_projection($r['type'],$r['ref'])); }
	public static function rest_smart_find( WP_REST_Request $r ){ return self::respond(self::smart_find($r->get_params())); }
	public static function rest_smart_hold( WP_REST_Request $r ){ return self::respond(self::smart_hold(self::data($r)),201); }
	public static function rest_smart_book( WP_REST_Request $r ){ return self::respond(self::smart_book(self::data($r)),201); }
	public static function rest_external_busy( WP_REST_Request $r ){ return self::respond(self::save_external_busy(self::data($r)),201); }
	public static function rest_episode( WP_REST_Request $r ){ return self::respond(self::create_episode(self::data($r)),201); }
	public static function rest_governance(){ return self::respond(self::governance_log()); }
}

/** Public helper for canonical cross-file feature discovery. */
function wca_future24_manifest() { return WCA_Future24::capabilities(); }
