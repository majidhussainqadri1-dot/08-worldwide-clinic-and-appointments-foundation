<?php
/**
 * Runtime guards and user-journey bridge for the restricted File 08
 * continuity subdomain.
 *
 * This layer deliberately contains no companion-module writes. It strengthens
 * optimistic concurrency, supplies a bounded privacy eraser, exposes safe
 * consent state, and mounts the existing continuity client on the canonical
 * appointment route.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Continuity_Guards {
	const CONTRACT_VERSION = '1.0.0';
	const ERASE_BATCH = 200;
	const CURSOR_TTL = HOUR_IN_SECONDS;

	public static function boot() {
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'enforce_intake_version' ), 10, 3 );
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ), 30 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_appointment_experience' ), 30 );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'replace_continuity_eraser' ), 100 );
	}

	/**
	 * Existing intake rows may only be changed with the exact current record
	 * version. The first write is versionless by definition.
	 *
	 * @param mixed           $result  Pre-dispatch result.
	 * @param WP_REST_Server  $server  REST server.
	 * @param WP_REST_Request $request Request.
	 * @return mixed
	 */
	public static function enforce_intake_version( $result, $server, $request ) {
		if ( null !== $result || ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}
		$method = strtoupper( (string) $request->get_method() );
		if ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH' ), true ) ) {
			return $result;
		}
		$route = (string) $request->get_route();
		if ( ! preg_match( '#^/wca/v1/continuity/appointments/([0-9a-fA-F-]{36})/intake(?:/submit)?$#', $route, $match ) ) {
			return $result;
		}
		$appointment_id = self::appointment_id( $match[1] );
		if ( ! $appointment_id ) {
			return $result;
		}
		global $wpdb;
		$table = WCA_Continuity::tables()['intake'];
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT id,version FROM {$table} WHERE appointment_id=%d LIMIT 1", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $current ) {
			return $result;
		}
		$data = $request->get_json_params();
		$data = is_array( $data ) ? $data : $request->get_params();
		$expected = absint( isset( $data['expected_version'] ) ? $data['expected_version'] : 0 );
		if ( ! $expected ) {
			return new WP_Error(
				'wca_intake_version_required',
				__( 'This pre-visit record already exists. Refresh it before saving changes.', 'worldwide-clinic-appointments' ),
				array( 'status' => 409, 'current_version' => absint( $current['version'] ) )
			);
		}
		if ( $expected !== absint( $current['version'] ) ) {
			return new WP_Error(
				'wca_intake_stale',
				__( 'Pre-visit information changed on the server. Refresh before saving.', 'worldwide-clinic-appointments' ),
				array( 'status' => 409, 'current_version' => absint( $current['version'] ) )
			);
		}
		return $result;
	}

	public static function register_routes() {
		register_rest_route(
			'wca/v1',
			'/continuity/appointments/(?P<ref>[0-9a-fA-F-]{36})/consents',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'rest_consents' ),
				'permission_callback' => array( 'WCA_Continuity', 'authenticated' ),
			)
		);
	}

	public static function rest_consents( WP_REST_Request $request ) {
		$result = self::consent_state( sanitize_text_field( $request['ref'] ), get_current_user_id() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function consent_state( $appointment_ref, $actor_user_id ) {
		global $wpdb;
		$appointment_id = self::appointment_id( $appointment_ref );
		$actor_user_id   = absint( $actor_user_id );
		if ( ! $appointment_id ) {
			return new WP_Error( 'wca_appointment_not_found', __( 'Appointment was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) {
			return $access;
		}
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		$can_manage = in_array( $actor, array( 'patient', 'guardian' ), true );
		if ( $can_manage ) {
			$patient_id  = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
			$guardian_id = 'guardian' === $actor ? $actor_user_id : 0;
			$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $guardian_id, $actor_user_id );
			if ( is_wp_error( $guardian ) ) {
				return $guardian;
			}
		}
		$table = WCA_Schema::tables()['consents'];
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				"SELECT scope,status,terms_version,granted_at,revoked_at FROM {$table} WHERE appointment_id=%d ORDER BY id ASC",
				$appointment_id
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$scopes = array( 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' );
		$state  = array();
		foreach ( $scopes as $scope ) {
			$state[ $scope ] = array( 'status' => 'not_granted', 'terms_version' => '', 'granted_at' => '', 'revoked_at' => '' );
		}
		foreach ( $rows as $row ) {
			$scope = sanitize_key( $row['scope'] );
			if ( ! isset( $state[ $scope ] ) ) { continue; }
			$state[ $scope ] = array(
				'status'        => sanitize_key( $row['status'] ),
				'terms_version' => sanitize_text_field( $row['terms_version'] ),
				'granted_at'    => sanitize_text_field( $row['granted_at'] ),
				'revoked_at'    => sanitize_text_field( $row['revoked_at'] ),
			);
		}
		return array(
			'contract'            => 'wca.context-consent-state',
			'version'             => self::CONTRACT_VERSION,
			'appointment_ref'     => strtolower( sanitize_text_field( $appointment_ref ) ),
			'appointment_status'  => SWC_Helpers::status( $appointment_id ),
			'actor'               => $actor,
			'can_manage_consents' => $can_manage,
			'can_edit_intake'      => $can_manage,
			'can_create_followup'  => 'doctor' === $actor,
			'scopes'              => $state,
			'checked_at_utc'      => gmdate( 'c' ),
		);
	}

	public static function enqueue_appointment_experience() {
		if ( ! is_user_logged_in() || ! class_exists( 'WCA_Routes' ) || 'appointment' !== WCA_Routes::route() ) {
			return;
		}
		$ref = sanitize_text_field( WCA_Routes::ref() );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) {
			return;
		}
		wp_enqueue_script( 'wca-continuity', WCA_URL . 'assets/js/continuity.js', array(), WCA_VERSION, true );
		wp_localize_script(
			'wca-continuity',
			'WCAContinuity',
			array(
				'root'           => esc_url_raw( rest_url( 'wca/v1/continuity/' ) ),
				'nonce'          => wp_create_nonce( 'wp_rest' ),
				'appointmentRef' => strtolower( $ref ),
			)
		);
	}

	public static function replace_continuity_eraser( $erasers ) {
		$erasers['wca-continuity'] = array(
			'eraser_friendly_name' => __( 'Worldwide Clinic continuity data', 'worldwide-clinic-appointments' ),
			'callback'             => array( __CLASS__, 'privacy_eraser' ),
		);
		return $erasers;
	}

	/**
	 * Cursor-based bounded erasure avoids the old “first 100 then done” defect
	 * and cannot become stuck behind legal-hold rows.
	 *
	 * @return array<string,mixed>
	 */
	public static function privacy_eraser( $email_address, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', sanitize_email( $email_address ) );
		if ( ! $user ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		$user_id = absint( $user->ID );
		$page    = max( 1, absint( $page ) );
		$base    = 'wca_erase_' . substr( hash( 'sha256', strtolower( sanitize_email( $email_address ) ) ), 0, 24 );
		if ( 1 === $page ) {
			delete_transient( $base . '_intake' );
			delete_transient( $base . '_followups' );
		}

		$removed  = false;
		$retained = false;
		$messages = array();
		$done     = true;
		$map = array(
			'intake'    => array( 'table' => WCA_Continuity::tables()['intake'], 'field' => 'patient_user_id', 'hold' => 'intake' ),
			'followups' => array( 'table' => WCA_Continuity::tables()['followups'], 'field' => 'patient_user_id', 'hold' => 'followup' ),
		);

		foreach ( $map as $name => $config ) {
			$cursor_key = $base . '_' . $name;
			$cursor = absint( get_transient( $cursor_key ) );
			$table  = $config['table'];
			$field  = $config['field'];
			$rows = (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d",
					$user_id,
					$cursor,
					self::ERASE_BATCH
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

			$last_id = $cursor;
			foreach ( $rows as $row ) {
				$row_id = absint( $row['id'] );
				$held = (bool) apply_filters( 'wca_continuity_legal_hold', false, $config['hold'], $row );
				if ( $held ) {
					$retained = true;
					$last_id = max( $last_id, $row_id );
					continue;
				}
				$deleted = $wpdb->delete( $table, array( 'id' => $row_id ), array( '%d' ) );
				if ( false === $deleted ) {
					$messages[] = __( 'Continuity privacy erasure encountered a storage failure and will retry without skipping the affected record.', 'worldwide-clinic-appointments' );
					$done = false;
					break;
				}
				if ( 0 === (int) $deleted ) {
					$still_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id=%d AND {$field}=%d", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( $still_exists ) { $messages[] = __( 'Continuity privacy erasure could not remove an affected record and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }
				}
				$last_id = max( $last_id, $row_id );
				$removed = true;
			}
			if ( $last_id > $cursor ) {
				set_transient( $cursor_key, $last_id, self::CURSOR_TTL );
			}
			$more = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT 1",
					$user_id,
					$last_id
				)
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $more ) {
				$done = false;
			} else {
				delete_transient( $cursor_key );
			}
		}

		if ( 1 === $page ) {
			$intake_table = WCA_Continuity::tables()['intake'];
			$guardian_update = $wpdb->update( $intake_table, array( 'guardian_user_id' => 0 ), array( 'guardian_user_id' => $user_id ), array( '%d' ), array( '%d' ) );
			if ( false === $guardian_update ) { $messages[] = __( 'Guardian continuity references could not be anonymized safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
			elseif ( 0 === (int) $guardian_update ) {
				$guardian_remaining = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$intake_table} WHERE guardian_user_id=%d LIMIT 1", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( $guardian_remaining ) { $messages[] = __( 'Guardian continuity references remain linked and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
			}
		}
		if ( $retained ) {
			$messages[] = __( 'Some clinic continuity records are retained under an active legal, safety, or professional-record hold.', 'worldwide-clinic-appointments' );
		}
		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $messages,
			'done'           => $done,
		);
	}

	private static function appointment_id( $ref ) {
		$ref = sanitize_text_field( $ref );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) {
			return 0;
		}
		$ids = get_posts(
			array(
				'post_type'      => SWC_Helpers::TYPE,
				'post_status'    => 'any',
				'fields'         => 'ids',
				'posts_per_page' => 2,
				'no_found_rows'  => true,
				'meta_key'       => '_swc_public_ref',
				'meta_value'     => $ref,
			)
		);
		if ( 1 !== count( $ids ) ) {
			$ids = get_posts(
				array(
					'post_type'      => SWC_Helpers::TYPE,
					'post_status'    => 'any',
					'fields'         => 'ids',
					'posts_per_page' => 2,
					'no_found_rows'  => true,
					'meta_key'       => 'public_ref',
					'meta_value'     => $ref,
				)
			);
		}
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}
}
