<?php
/**
 * Restricted clinical-continuity subdomain for File 08.
 *
 * Provides purpose-limited pre-visit intake, contextual consent, File 17
 * consultation authorization and doctor-defined follow-up/reminder plans.
 * It deliberately does not implement diagnosis, automated prescribing,
 * message transport or public patient records.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Continuity {
	const SCHEMA_OPTION = 'wca_continuity_schema_version';
	const SCHEMA_VERSION = '1.0.0';
	const CONTRACT_VERSION = '1.0.0';
	const MAX_PAYLOAD_BYTES = 32768;
	const FOLLOWUP_REMINDER_WINDOW = DAY_IN_SECONDS;

	/** @return array<string,string> */
	public static function tables() {
		global $wpdb;
		return array(
			'intake'    => $wpdb->prefix . 'wca_previsit_intake',
			'followups' => $wpdb->prefix . 'wca_followups',
		);
	}

	public static function boot() {
		self::maybe_upgrade();
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( WCA_Outbox::MAINTENANCE_HOOK, array( __CLASS__, 'process_due_followups' ), 20 );
		add_shortcode( 'wca_previsit_intake', array( __CLASS__, 'previsit_shortcode' ) );
		add_shortcode( 'wca_followup_plan', array( __CLASS__, 'followup_shortcode' ) );
	}

	public static function activate() {
		self::install_schema();
	}

	public static function maybe_upgrade() {
		if ( self::SCHEMA_VERSION !== (string) get_option( self::SCHEMA_OPTION, '' ) ) {
			self::install_schema();
		}
	}

	public static function install_schema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$tables = self::tables();
		$collate = $wpdb->get_charset_collate();
		$definitions = array(
			"CREATE TABLE {$tables['intake']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				patient_user_id bigint(20) unsigned NOT NULL,
				guardian_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				cipher_alg varchar(30) NOT NULL,
				ciphertext longtext NOT NULL,
				nonce varchar(255) NOT NULL,
				auth_tag varchar(255) NOT NULL DEFAULT '',
				payload_hash char(64) NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'draft',
				emergency_checked tinyint(1) unsigned NOT NULL DEFAULT 0,
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				submitted_at datetime NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				UNIQUE KEY appointment_id (appointment_id),
				KEY patient_status (patient_user_id,status),
				KEY updated_at (updated_at)
			) {$collate};",
			"CREATE TABLE {$tables['followups']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				patient_user_id bigint(20) unsigned NOT NULL,
				doctor_user_id bigint(20) unsigned NOT NULL,
				due_at datetime NOT NULL,
				cipher_alg varchar(30) NOT NULL,
				ciphertext longtext NOT NULL,
				nonce varchar(255) NOT NULL,
				auth_tag varchar(255) NOT NULL DEFAULT '',
				payload_hash char(64) NOT NULL,
				resource_refs_json longtext NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'scheduled',
				reminder_sent_at datetime NULL,
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				completed_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY appointment_status (appointment_id,status),
				KEY patient_due (patient_user_id,due_at,status),
				KEY doctor_status (doctor_user_id,status)
			) {$collate};",
		);
		foreach ( $definitions as $sql ) { dbDelta( $sql ); }
		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION, false );
	}

	/** @return array<string,mixed> */
	public static function health() {
		global $wpdb;
		$out = array( 'schema_version' => (string) get_option( self::SCHEMA_OPTION, '' ), 'tables' => array() );
		foreach ( self::tables() as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$out['tables'][ $name ] = $exists === $table ? 'ok' : 'missing';
		}
		$out['status'] = self::SCHEMA_VERSION === $out['schema_version'] && ! in_array( 'missing', $out['tables'], true ) ? 'ok' : 'degraded';
		return $out;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function save_intake( $appointment_ref, $payload, $actor_user_id = 0, $submit = false ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian' ), true ) ) {
			return new WP_Error( 'wca_intake_actor', __( 'Only the patient or current verified guardian may save pre-visit intake.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$patient_user_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$guardian_user_id = 'guardian' === $actor ? $actor_user_id : absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
		$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, 'guardian' === $actor ? $guardian_user_id : 0, $actor_user_id );
		if ( is_wp_error( $guardian ) ) { return $guardian; }
		$status = SWC_Helpers::status( $appointment_id );
		if ( in_array( $status, array( 'declined', 'cancelled', 'no_show' ), true ) ) {
			return new WP_Error( 'wca_intake_state', __( 'Pre-visit intake is unavailable for this appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$sanitized = self::sanitize_intake( is_array( $payload ) ? $payload : array() );
		if ( is_wp_error( $sanitized ) ) { return $sanitized; }
		$red_flag = WCA_Service::emergency_red_flag( implode( ' ', array_filter( array(
			isset( $sanitized['reason'] ) ? $sanitized['reason'] : '',
			isset( $sanitized['category'] ) ? $sanitized['category'] : '',
			isset( $sanitized['symptoms_summary'] ) ? $sanitized['symptoms_summary'] : '',
		) ) ) );
		if ( $red_flag ) {
			WCA_Observability::metric( 'previsit_emergency_diversion_total', 1, array( 'category' => $red_flag['category'] ) );
			return new WP_Error( 'wca_emergency_diversion', $red_flag['message'], array( 'status' => 422, 'emergency' => true, 'category' => $red_flag['category'] ) );
		}
		if ( $submit && ! self::active_consent( $appointment_id, 'appointment_processing' ) ) {
			return new WP_Error( 'wca_intake_consent', __( 'Current appointment-processing consent is required before intake submission.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$sealed = self::seal( $sanitized );
		if ( is_wp_error( $sealed ) ) { return $sealed; }
		$table = self::tables()['intake'];
		$current = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE appointment_id=%d LIMIT 1", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$now = WCA_Repository::now();
		$expected_version = absint( isset( $payload['expected_version'] ) ? $payload['expected_version'] : 0 );
		$row = array(
			'patient_user_id'  => $patient_user_id,
			'guardian_user_id' => $guardian_user_id,
			'cipher_alg'       => $sealed['alg'],
			'ciphertext'       => $sealed['ciphertext'],
			'nonce'            => $sealed['nonce'],
			'auth_tag'         => $sealed['tag'],
			'payload_hash'     => hash( 'sha256', wp_json_encode( $sanitized ) ),
			'status'           => $submit ? 'submitted' : 'draft',
			'emergency_checked'=> 1,
			'updated_at'       => $now,
		);
		if ( $submit ) { $row['submitted_at'] = $now; }
		if ( $current ) {
			if ( $expected_version && $expected_version !== absint( $current['version'] ) ) {
				return new WP_Error( 'wca_intake_stale', __( 'Pre-visit intake changed. Refresh before saving.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
			$row['version'] = absint( $current['version'] ) + 1;
			$changed = $wpdb->update( $table, $row, array( 'id' => absint( $current['id'] ), 'version' => absint( $current['version'] ) ) );
			if ( false === $changed || 0 === $changed ) { return new WP_Error( 'wca_intake_update', __( 'Pre-visit intake could not be updated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$public_ref = (string) $current['public_ref'];
		} else {
			$row['public_ref'] = WCA_Repository::uuid();
			$row['appointment_id'] = $appointment_id;
			$row['version'] = 1;
			$row['created_at'] = $now;
			if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_intake_insert', __( 'Pre-visit intake could not be stored.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$public_ref = (string) $row['public_ref'];
		}
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( $submit ? 'PreVisitIntakeSubmitted.v1' : 'PreVisitIntakeSaved.v1', 'previsit_intake', $public_ref, array( 'intake_ref' => $public_ref, 'appointment_ref' => self::appointment_ref( $appointment_id ), 'status' => $submit ? 'submitted' : 'draft' ), $actor_user_id, $trace );
		return self::get_intake( $appointment_ref, $actor_user_id );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function get_intake( $appointment_ref, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$table = self::tables()['intake'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE appointment_id=%d LIMIT 1", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) { return new WP_Error( 'wca_intake_missing', __( 'No pre-visit intake has been saved.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( 'doctor' === $actor && 'submitted' !== (string) $row['status'] ) {
			return new WP_Error( 'wca_intake_not_submitted', __( 'The patient has not submitted pre-visit intake.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) ) {
			return new WP_Error( 'wca_intake_scope', __( 'Pre-visit intake is limited to appointment participants.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		$payload = self::open( $row );
		if ( is_wp_error( $payload ) ) { return $payload; }
		return array(
			'contract'       => 'wca.previsit-intake',
			'version'        => self::CONTRACT_VERSION,
			'public_ref'     => (string) $row['public_ref'],
			'appointment_ref'=> self::appointment_ref( $appointment_id ),
			'status'         => (string) $row['status'],
			'record_version' => absint( $row['version'] ),
			'payload'        => $payload,
			'submitted_at'   => (string) $row['submitted_at'],
			'updated_at'     => (string) $row['updated_at'],
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function grant_context_consent( $appointment_ref, $scope, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian' ), true ) ) {
			return new WP_Error( 'wca_consent_actor', __( 'Only the patient or verified guardian may grant this consent.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$scope = sanitize_key( $scope );
		$allowed = array( 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' );
		if ( ! in_array( $scope, $allowed, true ) ) {
			return new WP_Error( 'wca_consent_scope', __( 'Unsupported consent scope.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$patient_user_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$guardian_user_id = 'guardian' === $actor ? $actor_user_id : 0;
		$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
		if ( is_wp_error( $guardian ) ) { return $guardian; }
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$terms = 'wca-context:' . $scope . ':2026-08-10';
		$result = WCA_Repository::record_consent( array(
			'appointment_id'    => $appointment_id,
			'actor_user_id'     => $actor_user_id,
			'actor_subject_uuid'=> $claims['subject_uuid'],
			'guardian_user_id'  => $guardian_user_id,
			'scope'             => $scope,
			'terms_version'     => '2026-08-10.1',
			'terms_text'        => $terms,
			'legal_basis'       => 'consent',
			'metadata'          => array( 'contract' => self::CONTRACT_VERSION, 'purpose' => 'appointment-continuity' ),
		) );
		if ( is_wp_error( $result ) ) { return $result; }
		return array( 'scope' => $scope, 'status' => 'granted', 'appointment_ref' => self::appointment_ref( $appointment_id ), 'contract' => self::CONTRACT_VERSION );
	}

	/** @return true|WP_Error */
	public static function revoke_context_consent( $appointment_ref, $scope, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian' ), true ) ) { return new WP_Error( 'wca_consent_actor', __( 'Only the patient or verified guardian may revoke this consent.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$scope = sanitize_key( $scope );
		if ( ! in_array( $scope, array( 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' ), true ) ) { return new WP_Error( 'wca_consent_scope', __( 'Unsupported consent scope.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$table = WCA_Schema::tables()['consents'];
		$now = WCA_Repository::now();
		$changed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND scope=%s AND actor_user_id=%d AND status='granted'", $now, $appointment_id, $scope, $actor_user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'AppointmentConsentRevoked.v1', 'appointment', self::appointment_ref( $appointment_id ), array( 'appointment_ref' => self::appointment_ref( $appointment_id ), 'scope' => $scope ), $actor_user_id, $trace );
		return false === $changed ? new WP_Error( 'wca_consent_revoke', __( 'Consent could not be revoked.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ) : true;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function file17_context( $appointment_ref, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) ) { return self::not_found(); }
		$status = SWC_Helpers::status( $appointment_id );
		$relationship_active = in_array( $status, array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true );
		$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$doctor_id = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) );
		return array(
			'contract'              => 'wca.file17-clinic-context',
			'version'               => self::CONTRACT_VERSION,
			'appointment_ref'       => self::appointment_ref( $appointment_id ),
			'patient_subject_uuid'  => WCA_Authorization::subject_uuid( $patient_id ),
			'doctor_subject_uuid'   => WCA_Authorization::subject_uuid( $doctor_id ),
			'appointment_status'    => $status,
			'relationship_active'   => $relationship_active,
			'messaging_allowed'     => $relationship_active && self::active_consent( $appointment_id, 'messaging' ),
			'call_allowed'          => $relationship_active && self::active_consent( $appointment_id, 'teleconsult' ),
			'recording_allowed'     => $relationship_active && self::active_consent( $appointment_id, 'recording' ),
			'clinical_record_access'=> false,
			'public_social_context' => false,
			'checked_at_utc'        => gmdate( 'c' ),
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_followup( $appointment_ref, $data, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		$allowed = 'doctor' === $actor || (bool) apply_filters( 'wca_followup_actor_allowed', false, $appointment_id, $actor_user_id );
		if ( ! $allowed ) { return new WP_Error( 'wca_followup_actor', __( 'Only an authorized treating professional may create a follow-up plan.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		if ( 'completed' !== SWC_Helpers::status( $appointment_id ) ) { return new WP_Error( 'wca_followup_state', __( 'Follow-up planning requires a completed appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! self::active_consent( $appointment_id, 'followup' ) ) { return new WP_Error( 'wca_followup_consent', __( 'Current follow-up consent is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$due = self::strict_utc( isset( $data['due_at_utc'] ) ? $data['due_at_utc'] : '' );
		if ( ! $due || strtotime( $due . ' UTC' ) <= time() ) { return new WP_Error( 'wca_followup_due', __( 'A future UTC follow-up time is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$payload = self::sanitize_followup( is_array( $data ) ? $data : array() );
		if ( is_wp_error( $payload ) ) { return $payload; }
		$sealed = self::seal( $payload );
		if ( is_wp_error( $sealed ) ) { return $sealed; }
		$resources = self::sanitize_resource_refs( isset( $data['resources'] ) ? $data['resources'] : array() );
		$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$doctor_id = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) );
		$row = array(
			'public_ref'        => WCA_Repository::uuid(),
			'appointment_id'    => $appointment_id,
			'patient_user_id'   => $patient_id,
			'doctor_user_id'    => $doctor_id,
			'due_at'            => $due,
			'cipher_alg'        => $sealed['alg'],
			'ciphertext'        => $sealed['ciphertext'],
			'nonce'             => $sealed['nonce'],
			'auth_tag'          => $sealed['tag'],
			'payload_hash'      => hash( 'sha256', wp_json_encode( $payload ) ),
			'resource_refs_json'=> wp_json_encode( $resources ),
			'status'            => 'scheduled',
			'version'           => 1,
			'created_at'        => WCA_Repository::now(),
			'updated_at'        => WCA_Repository::now(),
		);
		if ( false === $wpdb->insert( self::tables()['followups'], $row ) ) { return new WP_Error( 'wca_followup_insert', __( 'Follow-up plan could not be stored.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'FollowUpPlanCreated.v1', 'followup', $row['public_ref'], array( 'followup_ref' => $row['public_ref'], 'appointment_ref' => self::appointment_ref( $appointment_id ), 'due_at_utc' => $due ), $actor_user_id, $trace );
		return self::get_followup( $row['public_ref'], $actor_user_id );
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	public static function list_followups( $appointment_ref, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$appointment_id = self::appointment_id( $appointment_ref );
		if ( ! $appointment_id ) { return self::not_found(); }
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) ) { return self::not_found(); }
		$table = self::tables()['followups'];
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT public_ref FROM {$table} WHERE appointment_id=%d ORDER BY due_at ASC,id ASC LIMIT 100", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$out = array();
		foreach ( $rows as $row ) {
			$item = self::get_followup( $row['public_ref'], $actor_user_id );
			if ( ! is_wp_error( $item ) ) { $out[] = $item; }
		}
		return $out;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function get_followup( $followup_ref, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$followup_ref = sanitize_text_field( $followup_ref );
		$table = self::tables()['followups'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", $followup_ref ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) { return new WP_Error( 'wca_followup_missing', __( 'Follow-up plan was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$access = WCA_Authorization::can_view_appointment( absint( $row['appointment_id'] ), $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( absint( $row['appointment_id'] ), $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) ) { return self::not_found(); }
		$payload = self::open( $row );
		if ( is_wp_error( $payload ) ) { return $payload; }
		$resources = json_decode( (string) $row['resource_refs_json'], true );
		return array(
			'contract'       => 'wca.followup-plan',
			'version'        => self::CONTRACT_VERSION,
			'public_ref'     => (string) $row['public_ref'],
			'appointment_ref'=> self::appointment_ref( absint( $row['appointment_id'] ) ),
			'due_at_utc'     => (string) $row['due_at'],
			'status'         => (string) $row['status'],
			'plan'           => $payload,
			'resources'      => is_array( $resources ) ? $resources : array(),
			'record_version' => absint( $row['version'] ),
			'updated_at'     => (string) $row['updated_at'],
		);
	}

	/** @return true|WP_Error */
	public static function complete_followup( $followup_ref, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$followup_ref = sanitize_text_field( $followup_ref );
		$table = self::tables()['followups'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", $followup_ref ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) { return new WP_Error( 'wca_followup_missing', __( 'Follow-up plan was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$access = WCA_Authorization::can_view_appointment( absint( $row['appointment_id'] ), $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( absint( $row['appointment_id'] ), $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) ) { return self::not_found(); }
		if ( 'scheduled' !== (string) $row['status'] ) { return new WP_Error( 'wca_followup_state', __( 'Follow-up plan is not active.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$now = WCA_Repository::now();
		$changed = $wpdb->update( $table, array( 'status' => 'completed', 'completed_at' => $now, 'updated_at' => $now, 'version' => absint( $row['version'] ) + 1 ), array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ) ) );
		if ( false === $changed || 0 === $changed ) { return new WP_Error( 'wca_followup_stale', __( 'Follow-up plan changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'FollowUpPlanCompleted.v1', 'followup', $followup_ref, array( 'followup_ref' => $followup_ref, 'appointment_ref' => self::appointment_ref( absint( $row['appointment_id'] ) ) ), $actor_user_id, $trace );
		return true;
	}

	public static function process_due_followups() {
		global $wpdb;
		$table = self::tables()['followups'];
		$now = WCA_Repository::now();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() + self::FOLLOWUP_REMINDER_WINDOW );
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status='scheduled' AND reminder_sent_at IS NULL AND due_at<=%s AND due_at>=%s ORDER BY due_at ASC LIMIT 100", $cutoff, $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $rows as $row ) {
			$trace = WCA_Observability::trace_id();
			WCA_Repository::enqueue( 'File19.NotificationRequested.v1', (string) $row['public_ref'], array(
				'recipients'      => array( absint( $row['patient_user_id'] ) ),
				'event'           => 'followup_due',
				'appointment_ref' => self::appointment_ref( absint( $row['appointment_id'] ) ),
				'followup_ref'    => (string) $row['public_ref'],
				'contract'        => self::CONTRACT_VERSION,
			), $trace );
			$wpdb->update( $table, array( 'reminder_sent_at' => $now, 'updated_at' => $now, 'version' => absint( $row['version'] ) + 1 ), array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ) ) );
		}
	}

	public static function register_routes() {
		register_rest_route( 'wca/v1', '/continuity/appointments/(?P<ref>[0-9a-fA-F-]{36})/intake', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_get_intake' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
			array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( __CLASS__, 'rest_save_intake' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
		) );
		register_rest_route( 'wca/v1', '/continuity/appointments/(?P<ref>[0-9a-fA-F-]{36})/intake/submit', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_submit_intake' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ) );
		register_rest_route( 'wca/v1', '/continuity/appointments/(?P<ref>[0-9a-fA-F-]{36})/consents', array(
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_grant_consent' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
			array( 'methods' => WP_REST_Server::DELETABLE, 'callback' => array( __CLASS__, 'rest_revoke_consent' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
		) );
		register_rest_route( 'wca/v1', '/continuity/appointments/(?P<ref>[0-9a-fA-F-]{36})/file17-context', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_file17_context' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ) );
		register_rest_route( 'wca/v1', '/continuity/appointments/(?P<ref>[0-9a-fA-F-]{36})/followups', array(
			array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_list_followups' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
			array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_create_followup' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ),
		) );
		register_rest_route( 'wca/v1', '/continuity/followups/(?P<ref>[0-9a-fA-F-]{36})/complete', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_complete_followup' ), 'permission_callback' => array( __CLASS__, 'authenticated' ) ) );
	}

	public static function authenticated() {
		return is_user_logged_in() ? true : new WP_Error( 'wca_auth_required', __( 'Authentication is required.', 'worldwide-clinic-appointments' ), array( 'status' => 401 ) );
	}

	private static function request_data( WP_REST_Request $request ) {
		$data = $request->get_json_params();
		return is_array( $data ) ? $data : $request->get_params();
	}

	private static function no_store( $result, $status = 200 ) {
		if ( is_wp_error( $result ) ) { return $result; }
		$response = rest_ensure_response( $result );
		$response->set_status( $status );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		return $response;
	}

	private static function rate_limit( $scope, $limit = 30, $window = 300 ) {
		return SWC_Helpers::rate_limit_hit( 'continuity_' . sanitize_key( $scope ), absint( get_current_user_id() ), $limit, $window ) ? new WP_Error( 'wca_rate_limit', __( 'Too many requests. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => $window ) ) : true;
	}

	public static function rest_get_intake( WP_REST_Request $request ) { return self::no_store( self::get_intake( $request['ref'] ) ); }
	public static function rest_save_intake( WP_REST_Request $request ) { $r=self::rate_limit('intake_save',30,300); if(is_wp_error($r)){return $r;} return self::no_store( self::save_intake( $request['ref'], self::request_data( $request ), 0, false ) ); }
	public static function rest_submit_intake( WP_REST_Request $request ) { $r=self::rate_limit('intake_submit',10,HOUR_IN_SECONDS); if(is_wp_error($r)){return $r;} return self::no_store( self::save_intake( $request['ref'], self::request_data( $request ), 0, true ) ); }
	public static function rest_grant_consent( WP_REST_Request $request ) { $data=self::request_data($request); return self::no_store( self::grant_context_consent( $request['ref'], isset($data['scope'])?$data['scope']:'' ), 201 ); }
	public static function rest_revoke_consent( WP_REST_Request $request ) { $data=self::request_data($request); return self::no_store( self::revoke_context_consent( $request['ref'], isset($data['scope'])?$data['scope']:'' ) ); }
	public static function rest_file17_context( WP_REST_Request $request ) { return self::no_store( self::file17_context( $request['ref'] ) ); }
	public static function rest_list_followups( WP_REST_Request $request ) { return self::no_store( self::list_followups( $request['ref'] ) ); }
	public static function rest_create_followup( WP_REST_Request $request ) { $r=self::rate_limit('followup_create',20,HOUR_IN_SECONDS); if(is_wp_error($r)){return $r;} return self::no_store( self::create_followup( $request['ref'], self::request_data($request) ), 201 ); }
	public static function rest_complete_followup( WP_REST_Request $request ) { return self::no_store( self::complete_followup( $request['ref'] ) ); }

	public static function previsit_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'appointment' => '' ), $atts, 'wca_previsit_intake' );
		$ref = sanitize_text_field( $atts['appointment'] );
		if ( ! is_user_logged_in() || ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return ''; }
		self::enqueue_assets();
		return '<section class="wca-card wca-continuity" data-wca-previsit data-appointment-ref="' . esc_attr( $ref ) . '"><h2>' . esc_html__( 'Pre-visit information', 'worldwide-clinic-appointments' ) . '</h2><div class="wca-alert" role="note">' . esc_html__( 'Do not wait here for an emergency. If symptoms are severe or life-threatening, seek qualified local emergency care now.', 'worldwide-clinic-appointments' ) . '</div><form class="wca-form"><label>' . esc_html__( 'Reason for visit', 'worldwide-clinic-appointments' ) . '<textarea name="reason" maxlength="1500" required></textarea></label><label>' . esc_html__( 'Category', 'worldwide-clinic-appointments' ) . '<input name="category" maxlength="80"></label><label>' . esc_html__( 'Short symptom summary', 'worldwide-clinic-appointments' ) . '<textarea name="symptoms_summary" maxlength="3000"></textarea></label><label>' . esc_html__( 'Allergies or sensitivities', 'worldwide-clinic-appointments' ) . '<textarea name="allergies_summary" maxlength="1500"></textarea></label><label>' . esc_html__( 'Accessibility or communication needs', 'worldwide-clinic-appointments' ) . '<textarea name="accessibility_needs" maxlength="1000"></textarea></label><div class="wca-actions"><button class="wca-button wca-button-secondary" type="button" data-action="save">' . esc_html__( 'Save draft', 'worldwide-clinic-appointments' ) . '</button><button class="wca-button" type="button" data-action="submit">' . esc_html__( 'Submit securely', 'worldwide-clinic-appointments' ) . '</button></div><p data-wca-status role="status" aria-live="polite"></p></form></section>';
	}

	public static function followup_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'appointment' => '' ), $atts, 'wca_followup_plan' );
		$ref = sanitize_text_field( $atts['appointment'] );
		if ( ! is_user_logged_in() || ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return ''; }
		self::enqueue_assets();
		return '<section class="wca-card wca-continuity" data-wca-followups data-appointment-ref="' . esc_attr( $ref ) . '"><h2>' . esc_html__( 'Follow-up plan', 'worldwide-clinic-appointments' ) . '</h2><p>' . esc_html__( 'Doctor-defined follow-up and approved educational resources appear here. This surface does not generate treatment with AI.', 'worldwide-clinic-appointments' ) . '</p><div data-wca-followup-list aria-live="polite"></div><p data-wca-status role="status" aria-live="polite"></p></section>';
	}

	private static function enqueue_assets() {
		wp_enqueue_script( 'wca-continuity', WCA_URL . 'assets/js/continuity.js', array(), WCA_VERSION, true );
		wp_localize_script( 'wca-continuity', 'WCAContinuity', array( 'root' => esc_url_raw( rest_url( 'wca/v1/continuity/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
	}

	/** @return array<string,string>|WP_Error */
	private static function sanitize_intake( $payload ) {
		$out = array(
			'reason'              => self::bounded_textarea( isset( $payload['reason'] ) ? $payload['reason'] : '', 1500 ),
			'category'            => self::bounded_text( isset( $payload['category'] ) ? $payload['category'] : '', 80 ),
			'symptoms_summary'    => self::bounded_textarea( isset( $payload['symptoms_summary'] ) ? $payload['symptoms_summary'] : '', 3000 ),
			'medications_summary' => self::bounded_textarea( isset( $payload['medications_summary'] ) ? $payload['medications_summary'] : '', 2000 ),
			'allergies_summary'   => self::bounded_textarea( isset( $payload['allergies_summary'] ) ? $payload['allergies_summary'] : '', 1500 ),
			'accessibility_needs' => self::bounded_textarea( isset( $payload['accessibility_needs'] ) ? $payload['accessibility_needs'] : '', 1000 ),
			'preferred_language'  => self::bounded_text( isset( $payload['preferred_language'] ) ? $payload['preferred_language'] : '', 80 ),
			'notes'               => self::bounded_textarea( isset( $payload['notes'] ) ? $payload['notes'] : '', 2000 ),
		);
		if ( '' === $out['reason'] ) { return new WP_Error( 'wca_intake_reason', __( 'A short reason for the visit is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$encoded = wp_json_encode( $out );
		if ( ! is_string( $encoded ) || strlen( $encoded ) > self::MAX_PAYLOAD_BYTES ) { return new WP_Error( 'wca_intake_size', __( 'Pre-visit information is too large.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
		return $out;
	}

	/** @return array<string,string>|WP_Error */
	private static function sanitize_followup( $data ) {
		$out = array(
			'purpose'      => self::bounded_text( isset( $data['purpose'] ) ? $data['purpose'] : '', 191 ),
			'instructions' => self::bounded_textarea( isset( $data['instructions'] ) ? $data['instructions'] : '', 5000 ),
			'limitations'  => self::bounded_textarea( isset( $data['limitations'] ) ? $data['limitations'] : '', 1500 ),
		);
		if ( '' === $out['purpose'] ) { return new WP_Error( 'wca_followup_purpose', __( 'A follow-up purpose is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$encoded = wp_json_encode( $out );
		if ( ! is_string( $encoded ) || strlen( $encoded ) > self::MAX_PAYLOAD_BYTES ) { return new WP_Error( 'wca_followup_size', __( 'Follow-up plan is too large.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
		return $out;
	}

	/** @return array<int,array<string,string>> */
	private static function sanitize_resource_refs( $resources ) {
		$out = array();
		foreach ( array_slice( (array) $resources, 0, 20 ) as $resource ) {
			if ( ! is_array( $resource ) ) { continue; }
			$type = sanitize_key( isset( $resource['type'] ) ? $resource['type'] : 'educational' );
			$ref = sanitize_text_field( isset( $resource['ref'] ) ? $resource['ref'] : '' );
			$url = esc_url_raw( isset( $resource['url'] ) ? $resource['url'] : '' );
			if ( ! $ref && ! $url ) { continue; }
			$out[] = array( 'type' => $type, 'ref' => $ref, 'url' => $url );
		}
		return $out;
	}

	private static function bounded_text( $value, $max ) { return function_exists( 'mb_substr' ) ? mb_substr( sanitize_text_field( $value ), 0, $max ) : substr( sanitize_text_field( $value ), 0, $max ); }
	private static function bounded_textarea( $value, $max ) { return function_exists( 'mb_substr' ) ? mb_substr( sanitize_textarea_field( $value ), 0, $max ) : substr( sanitize_textarea_field( $value ), 0, $max ); }

	/** @return array<string,string>|WP_Error */
	private static function seal( $payload ) {
		$plain = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
		if ( ! is_string( $plain ) ) { return new WP_Error( 'wca_crypto_encode', __( 'Sensitive data could not be encoded.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$key = hash( 'sha256', wp_salt( 'auth' ) . '|wca-continuity|' . home_url( '/' ), true );
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plain, $nonce, $key );
			return array( 'alg' => 'sodium-secretbox-v1', 'ciphertext' => base64_encode( $cipher ), 'nonce' => base64_encode( $nonce ), 'tag' => '' );
		}
		if ( function_exists( 'openssl_encrypt' ) ) {
			$nonce = random_bytes( 12 );
			$tag = '';
			$cipher = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, 'wca-continuity-v1' );
			if ( false !== $cipher ) { return array( 'alg' => 'aes-256-gcm-v1', 'ciphertext' => base64_encode( $cipher ), 'nonce' => base64_encode( $nonce ), 'tag' => base64_encode( $tag ) ); }
		}
		return new WP_Error( 'wca_crypto_unavailable', __( 'Secure storage is unavailable. Sensitive data was not saved.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function open( $row ) {
		$key = hash( 'sha256', wp_salt( 'auth' ) . '|wca-continuity|' . home_url( '/' ), true );
		$cipher = base64_decode( (string) $row['ciphertext'], true );
		$nonce = base64_decode( (string) $row['nonce'], true );
		if ( false === $cipher || false === $nonce ) { return new WP_Error( 'wca_crypto_payload', __( 'Sensitive data could not be decoded.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$plain = false;
		if ( 'sodium-secretbox-v1' === (string) $row['cipher_alg'] && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$plain = sodium_crypto_secretbox_open( $cipher, $nonce, $key );
		} elseif ( 'aes-256-gcm-v1' === (string) $row['cipher_alg'] && function_exists( 'openssl_decrypt' ) ) {
			$tag = base64_decode( (string) $row['auth_tag'], true );
			if ( false !== $tag ) { $plain = openssl_decrypt( $cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, 'wca-continuity-v1' ); }
		}
		if ( ! is_string( $plain ) ) { return new WP_Error( 'wca_crypto_integrity', __( 'Sensitive data failed integrity verification.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$data = json_decode( $plain, true );
		if ( ! is_array( $data ) ) { return new WP_Error( 'wca_crypto_json', __( 'Sensitive data could not be read.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		if ( ! hash_equals( (string) $row['payload_hash'], hash( 'sha256', wp_json_encode( $data ) ) ) ) { return new WP_Error( 'wca_crypto_hash', __( 'Sensitive data failed integrity verification.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		return $data;
	}

	private static function active_consent( $appointment_id, $scope ) {
		global $wpdb;
		$table = WCA_Schema::tables()['consents'];
		$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL", absint( $appointment_id ), sanitize_key( $scope ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return absint( $count ) > 0;
	}

	private static function strict_utc( $value ) {
		$value = trim( (string) $value );
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
		$errors = DateTimeImmutable::getLastErrors();
		return $date && ( ! is_array( $errors ) || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $date->format( 'Y-m-d H:i:s' ) === $value ? $value : '';
	}

	private static function appointment_id( $ref ) {
		$ref = sanitize_text_field( $ref );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return 0; }
		$ids = get_posts( array( 'post_type' => 'swc_appointment', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 2, 'no_found_rows' => true, 'meta_key' => '_swc_public_ref', 'meta_value' => $ref ) );
		if ( 1 !== count( $ids ) ) {
			$ids = get_posts( array( 'post_type' => 'swc_appointment', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 2, 'no_found_rows' => true, 'meta_key' => 'public_ref', 'meta_value' => $ref ) );
		}
		return 1 === count( $ids ) ? absint( $ids[0] ) : 0;
	}

	private static function appointment_ref( $appointment_id ) {
		$ref = (string) SWC_Helpers::meta( absint( $appointment_id ), 'public_ref', '' );
		return preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ? strtolower( $ref ) : '';
	}

	private static function not_found() { return new WP_Error( 'wca_appointment_not_found', __( 'Appointment was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
}

/** Versioned cross-file helper; File 17 consumes context but owns messages/calls. */
function wca_get_file17_clinic_context( $appointment_ref, $actor_user_id = 0 ) {
	return WCA_Continuity::file17_context( $appointment_ref, $actor_user_id );
}
