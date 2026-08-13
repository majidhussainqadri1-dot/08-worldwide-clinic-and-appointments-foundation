<?php
/**
 * Restricted, encrypted clinical-continuity subdomain for File 08.
 *
 * Owns only File 08 pre-visit intake and follow-up records. File 17 owns
 * message/call transport, File 19 owns notification delivery, File 24 owns
 * assurance, and File 26 owns search/index/ranking. No public patient record,
 * diagnosis or automated prescribing is created here.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Continuity {
	const SCHEMA_OPTION = 'wca_continuity_schema_version';
	const SCHEMA_VERSION = '1.1.0';
	const CONTRACT_VERSION = '1.1.0';
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
		add_action( WCA_Outbox::MAINTENANCE_HOOK, array( __CLASS__, 'maintenance' ), 20 );
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
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
		$tables  = self::tables();
		$collate = $wpdb->get_charset_collate();
		$definitions = array(
			"CREATE TABLE {$tables['intake']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				patient_user_id bigint(20) unsigned NOT NULL,
				guardian_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				key_id varchar(64) NOT NULL,
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
				KEY guardian_user_id (guardian_user_id),
				KEY updated_at (updated_at)
			) {$collate};",
			"CREATE TABLE {$tables['followups']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				patient_user_id bigint(20) unsigned NOT NULL,
				doctor_user_id bigint(20) unsigned NOT NULL,
				created_by_user_id bigint(20) unsigned NOT NULL,
				due_at datetime NOT NULL,
				key_id varchar(64) NOT NULL,
				cipher_alg varchar(30) NOT NULL,
				ciphertext longtext NOT NULL,
				nonce varchar(255) NOT NULL,
				auth_tag varchar(255) NOT NULL DEFAULT '',
				payload_hash char(64) NOT NULL,
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
				KEY doctor_status (doctor_user_id,status),
				KEY created_by_user_id (created_by_user_id)
			) {$collate};",
		);
		foreach ( $definitions as $sql ) { dbDelta( $sql ); }
		foreach ( $tables as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $exists !== $table ) { throw new RuntimeException( 'File 08 continuity table could not be created: ' . sanitize_key( $name ) ); }
		}
		$written = SWC_Helpers::update_option_strict( self::SCHEMA_OPTION, self::SCHEMA_VERSION, 'wca_continuity_schema_version_write' );
		if ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 continuity schema version could not be persisted.' ); }
	}

	/** @return array<string,mixed> */
	public static function health() {
		global $wpdb;
		$out = array(
			'contract'       => 'wca.continuity-health',
			'version'        => self::CONTRACT_VERSION,
			'schema_version' => (string) get_option( self::SCHEMA_OPTION, '' ),
			'tables'         => array(),
			'keyring'        => self::keyring_health(),
		);
		foreach ( self::tables() as $name => $table ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
			$out['tables'][ $name ] = $exists === $table ? 'ok' : 'missing';
		}
		$out['status'] = self::SCHEMA_VERSION === $out['schema_version'] && ! in_array( 'missing', $out['tables'], true ) && 'ok' === $out['keyring'] ? 'ok' : 'degraded';
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
		$patient_user_id  = self::patient_id( $appointment_id );
		$guardian_user_id = 'guardian' === $actor ? $actor_user_id : 0;
		$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
		if ( is_wp_error( $guardian ) ) { return $guardian; }
		$status = SWC_Helpers::status( $appointment_id );
		if ( in_array( $status, array( 'declined', 'cancelled', 'no_show' ), true ) ) {
			return new WP_Error( 'wca_intake_state', __( 'Pre-visit intake is unavailable for this appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$sanitized = self::sanitize_intake( is_array( $payload ) ? $payload : array() );
		if ( is_wp_error( $sanitized ) ) { return $sanitized; }
		$red_flag = WCA_Service::emergency_red_flag( implode( ' ', array_filter( array( $sanitized['reason'], $sanitized['category'], $sanitized['symptoms_summary'] ) ) ) );
		if ( $red_flag ) {
			WCA_Observability::metric( 'previsit_emergency_diversion_total', 1, array( 'category' => $red_flag['category'] ) );
			return new WP_Error( 'wca_emergency_diversion', $red_flag['message'], array( 'status' => 422, 'emergency' => true, 'category' => $red_flag['category'] ) );
		}
		if ( $submit && ! self::active_consent( $appointment_id, 'appointment_processing' ) ) {
			return new WP_Error( 'wca_intake_consent', __( 'Current appointment-processing consent is required before intake submission.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$sealed = self::seal( $sanitized );
		if ( is_wp_error( $sealed ) ) { return $sealed; }
		$table   = self::tables()['intake'];
		$now = WCA_Repository::now();
		$expected_version = absint( isset( $payload['expected_version'] ) ? $payload['expected_version'] : 0 );
		$row = array(
			'patient_user_id'   => $patient_user_id,
			'guardian_user_id'  => $guardian_user_id,
			'key_id'            => $sealed['key_id'],
			'cipher_alg'        => $sealed['alg'],
			'ciphertext'        => $sealed['ciphertext'],
			'nonce'             => $sealed['nonce'],
			'auth_tag'          => $sealed['tag'],
			'payload_hash'      => hash( 'sha256', self::stable_json( $sanitized ) ),
			'status'            => $submit ? 'submitted' : 'draft',
			'emergency_checked' => 1,
			'updated_at'        => $now,
		);
		if ( $submit ) { $row['submitted_at'] = $now; }
		$mutation = WCA_Repository::transaction( function () use ( $table, $row, $appointment_id, $expected_version, $submit, $actor_user_id ) {
			global $wpdb;
			$row = $row;
			$current = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE appointment_id=%d LIMIT 1 FOR UPDATE", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $current ) {
				if ( $expected_version && $expected_version !== absint( $current['version'] ) ) {
					return new WP_Error( 'wca_intake_stale', __( 'Pre-visit intake changed. Refresh before saving.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
				}
				$row['version'] = absint( $current['version'] ) + 1;
				$changed = $wpdb->update( $table, $row, array( 'id' => absint( $current['id'] ), 'version' => absint( $current['version'] ) ) );
				if ( 1 !== (int) $changed ) { return new WP_Error( 'wca_intake_update', __( 'Pre-visit intake could not be updated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
				$public_ref = (string) $current['public_ref'];
			} else {
				$row['public_ref']     = WCA_Repository::uuid();
				$row['appointment_id'] = $appointment_id;
				$row['version']        = 1;
				$row['created_at']     = WCA_Repository::now();
				if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_intake_insert', __( 'Pre-visit intake could not be stored.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				$public_ref = (string) $row['public_ref'];
			}
			$trace = WCA_Observability::trace_id();
			$event = WCA_Repository::append_event( $submit ? 'PreVisitIntakeSubmitted.v1' : 'PreVisitIntakeSaved.v1', 'previsit_intake', $public_ref, array( 'intake_ref' => $public_ref, 'appointment_ref' => self::appointment_ref( $appointment_id ), 'status' => $submit ? 'submitted' : 'draft' ), $actor_user_id, $trace );
			return is_wp_error( $event ) ? $event : $public_ref;
		}, 'wca_intake_mutation_transaction' );
		if ( is_wp_error( $mutation ) ) { return $mutation; }
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
		if ( 'doctor' === $actor ) {
			if ( 'submitted' !== (string) $row['status'] || ! in_array( SWC_Helpers::status( $appointment_id ), array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true ) ) {
				return new WP_Error( 'wca_intake_not_available', __( 'Submitted pre-visit intake is not available in this appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
			}
		} elseif ( ! in_array( $actor, array( 'patient', 'guardian' ), true ) ) {
			return self::not_found();
		}
		$payload = self::open( $row );
		if ( is_wp_error( $payload ) ) { return $payload; }
		return array(
			'contract'        => 'wca.previsit-intake',
			'version'         => self::CONTRACT_VERSION,
			'public_ref'      => (string) $row['public_ref'],
			'appointment_ref' => self::appointment_ref( $appointment_id ),
			'status'          => (string) $row['status'],
			'record_version'  => absint( $row['version'] ),
			'payload'         => $payload,
			'submitted_at'    => (string) $row['submitted_at'],
			'updated_at'      => (string) $row['updated_at'],
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
		if ( ! in_array( $scope, self::context_consent_scopes(), true ) ) {
			return new WP_Error( 'wca_consent_scope', __( 'Unsupported consent scope.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$patient_user_id  = self::patient_id( $appointment_id );
		$guardian_user_id = 'guardian' === $actor ? $actor_user_id : 0;
		$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
		if ( is_wp_error( $guardian ) ) { return $guardian; }
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$terms = 'wca-context:' . $scope . ':2026-08-10';
		$result = WCA_Repository::record_consent( array(
			'appointment_id'     => $appointment_id,
			'actor_user_id'      => $actor_user_id,
			'actor_subject_uuid' => $claims['subject_uuid'],
			'guardian_user_id'   => $guardian_user_id,
			'scope'              => $scope,
			'terms_version'      => '2026-08-10.1',
			'terms_text'         => $terms,
			'legal_basis'        => 'consent',
			'metadata'           => array( 'contract' => self::CONTRACT_VERSION, 'purpose' => 'appointment-continuity' ),
		) );
		if ( is_wp_error( $result ) ) { return $result; }
		return array( 'contract' => 'wca.context-consent', 'version' => self::CONTRACT_VERSION, 'scope' => $scope, 'status' => 'granted', 'appointment_ref' => self::appointment_ref( $appointment_id ) );
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
		if ( ! in_array( $scope, self::context_consent_scopes(), true ) ) { return new WP_Error( 'wca_consent_scope', __( 'Unsupported consent scope.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$table = WCA_Schema::tables()['consents'];
		$result = WCA_Repository::transaction( function () use ( $table, $appointment_id, $scope, $actor_user_id ) {
			global $wpdb;
			$changed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND scope=%s AND actor_user_id=%d AND status='granted'", WCA_Repository::now(), $appointment_id, $scope, $actor_user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( false === $changed ) { return new WP_Error( 'wca_consent_revoke', __( 'Consent could not be revoked.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			if ( 0 === (int) $changed ) { return new WP_Error( 'wca_consent_not_active', __( 'No active consent matched this revocation request.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$event = WCA_Repository::append_event( 'AppointmentConsentRevoked.v1', 'appointment', self::appointment_ref( $appointment_id ), array( 'appointment_ref' => self::appointment_ref( $appointment_id ), 'scope' => $scope ), $actor_user_id, WCA_Observability::trace_id() );
			return is_wp_error( $event ) ? $event : true;
		}, 'wca_consent_revoke_transaction' );
		return $result;
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
		$active = in_array( $status, array( 'confirmed', 'reschedule_pending', 'checked_in', 'completed' ), true );
		$patient_id = self::patient_id( $appointment_id );
		$doctor_id  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) );
		return array(
			'contract'               => 'wca.file17-clinic-context',
			'version'                => self::CONTRACT_VERSION,
			'appointment_ref'        => self::appointment_ref( $appointment_id ),
			'patient_subject_uuid'   => WCA_Authorization::subject_uuid( $patient_id ),
			'doctor_subject_uuid'    => WCA_Authorization::subject_uuid( $doctor_id ),
			'appointment_status'     => $status,
			'relationship_active'    => $active,
			'messaging_allowed'      => $active && self::active_consent( $appointment_id, 'messaging' ),
			'call_allowed'           => $active && self::active_consent( $appointment_id, 'teleconsult' ),
			'recording_allowed'      => $active && self::active_consent( $appointment_id, 'recording' ),
			'clinical_record_access' => false,
			'public_social_context'  => false,
			'checked_at_utc'         => gmdate( 'c' ),
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
		if ( ! self::followup_actor_allowed( $appointment_id, $actor_user_id ) ) { return new WP_Error( 'wca_followup_actor', __( 'Only an authorized treating professional or explicitly delegated clinical staff member may create a follow-up plan.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		if ( 'completed' !== SWC_Helpers::status( $appointment_id ) ) { return new WP_Error( 'wca_followup_state', __( 'Follow-up planning requires a completed appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! self::active_consent( $appointment_id, 'followup' ) ) { return new WP_Error( 'wca_followup_consent', __( 'Current follow-up consent is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$due = WCA_Plan_Guard::strict_utc( isset( $data['due_at_utc'] ) ? $data['due_at_utc'] : '' );
		if ( ! $due || strtotime( $due . ' UTC' ) <= time() ) { return new WP_Error( 'wca_followup_due', __( 'A future UTC follow-up time is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$payload = self::sanitize_followup( is_array( $data ) ? $data : array() );
		if ( is_wp_error( $payload ) ) { return $payload; }
		$sealed = self::seal( $payload );
		if ( is_wp_error( $sealed ) ) { return $sealed; }
		$doctor_id = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) );
		$row = array(
			'public_ref'         => WCA_Repository::uuid(),
			'appointment_id'     => $appointment_id,
			'patient_user_id'    => self::patient_id( $appointment_id ),
			'doctor_user_id'     => $doctor_id,
			'created_by_user_id' => $actor_user_id,
			'due_at'             => $due,
			'key_id'             => $sealed['key_id'],
			'cipher_alg'         => $sealed['alg'],
			'ciphertext'         => $sealed['ciphertext'],
			'nonce'              => $sealed['nonce'],
			'auth_tag'           => $sealed['tag'],
			'payload_hash'       => hash( 'sha256', self::stable_json( $payload ) ),
			'status'             => 'scheduled',
			'version'            => 1,
			'created_at'         => WCA_Repository::now(),
			'updated_at'         => WCA_Repository::now(),
		);
		$stored = WCA_Repository::transaction( function () use ( $row, $appointment_id, $due, $actor_user_id ) {
			global $wpdb;
			if ( false === $wpdb->insert( self::tables()['followups'], $row ) ) { return new WP_Error( 'wca_followup_insert', __( 'Follow-up plan could not be stored.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$event = WCA_Repository::append_event( 'FollowUpPlanCreated.v1', 'followup', $row['public_ref'], array( 'followup_ref' => $row['public_ref'], 'appointment_ref' => self::appointment_ref( $appointment_id ), 'due_at_utc' => $due ), $actor_user_id, WCA_Observability::trace_id() );
			return is_wp_error( $event ) ? $event : true;
		}, 'wca_followup_create_transaction' );
		if ( is_wp_error( $stored ) ) { return $stored; }
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
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) && ! self::followup_actor_allowed( $appointment_id, $actor_user_id ) ) { return self::not_found(); }
		$table = self::tables()['followups'];
		$out = array();
		$cursor = 0;
		$batch = 100;
		do {
			$rows_raw = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref FROM {$table} WHERE appointment_id=%d AND id>%d ORDER BY id ASC LIMIT %d", $appointment_id, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_list_read_failed', __( 'Follow-up plans could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
			$rows = (array) $rows_raw;
			foreach ( $rows as $row ) {
				$cursor = max( $cursor, absint( $row['id'] ?? 0 ) );
				$item = self::get_followup( $row['public_ref'], $actor_user_id );
				if ( ! is_wp_error( $item ) ) { $out[] = $item; }
			}
		} while ( count( $rows ) === $batch );
		return $out;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function get_followup( $followup_ref, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$followup_ref = sanitize_text_field( $followup_ref );
		$table = self::tables()['followups'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", $followup_ref ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_read_failed', __( 'Follow-up plan state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( ! $row ) { return new WP_Error( 'wca_followup_missing', __( 'Follow-up plan was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$access = WCA_Authorization::can_view_appointment( absint( $row['appointment_id'] ), $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( absint( $row['appointment_id'] ), $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) && ! self::followup_actor_allowed( absint( $row['appointment_id'] ), $actor_user_id ) ) { return self::not_found(); }
		$payload = self::open( $row );
		if ( is_wp_error( $payload ) ) { return $payload; }
		return array(
			'contract'        => 'wca.followup-plan',
			'version'         => self::CONTRACT_VERSION,
			'public_ref'      => (string) $row['public_ref'],
			'appointment_ref' => self::appointment_ref( absint( $row['appointment_id'] ) ),
			'due_at_utc'      => (string) $row['due_at'],
			'status'          => (string) $row['status'],
			'plan'            => $payload,
			'record_version'  => absint( $row['version'] ),
			'updated_at'      => (string) $row['updated_at'],
		);
	}

	/** @return true|WP_Error */
	public static function complete_followup( $followup_ref, $actor_user_id = 0 ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$table = self::tables()['followups'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", sanitize_text_field( $followup_ref ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_read_failed', __( 'Follow-up plan state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( ! $row ) { return new WP_Error( 'wca_followup_missing', __( 'Follow-up plan was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$access = WCA_Authorization::can_view_appointment( absint( $row['appointment_id'] ), $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$actor = WCA_Authorization::appointment_actor( absint( $row['appointment_id'] ), $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor' ), true ) && ! self::followup_actor_allowed( absint( $row['appointment_id'] ), $actor_user_id ) ) { return self::not_found(); }
		if ( 'scheduled' !== (string) $row['status'] ) { return new WP_Error( 'wca_followup_state', __( 'Follow-up plan is not active.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$now = WCA_Repository::now();
		$result = WCA_Repository::transaction( function () use ( $table, $row, $now, $actor_user_id ) {
			global $wpdb;
			$changed = $wpdb->update( $table, array( 'status' => 'completed', 'completed_at' => $now, 'updated_at' => $now, 'version' => absint( $row['version'] ) + 1 ), array( 'id' => absint( $row['id'] ), 'status' => 'scheduled', 'version' => absint( $row['version'] ) ) );
			if ( 1 !== (int) $changed ) { return new WP_Error( 'wca_followup_stale', __( 'Follow-up plan changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$event = WCA_Repository::append_event( 'FollowUpPlanCompleted.v1', 'followup', (string) $row['public_ref'], array( 'followup_ref' => (string) $row['public_ref'], 'appointment_ref' => self::appointment_ref( absint( $row['appointment_id'] ) ) ), $actor_user_id, WCA_Observability::trace_id() );
			return is_wp_error( $event ) ? $event : true;
		}, 'wca_followup_complete_transaction' );
		return $result;
	}

	public static function maintenance() {
		$reminders = self::process_due_followups();
		if ( is_wp_error( $reminders ) ) { WCA_Observability::log( 'error', 'continuity_reminder_scan_failed', array( 'error_code' => $reminders->get_error_code() ) ); return $reminders; }
		$retention = self::apply_retention();
		if ( is_wp_error( $retention ) ) { WCA_Observability::log( 'error', 'continuity_retention_failed', array( 'error_code' => $retention->get_error_code() ) ); }
		return $retention;
	}

	public static function process_due_followups() {
		global $wpdb;
		$table  = self::tables()['followups'];
		$now    = WCA_Repository::now();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() + self::FOLLOWUP_REMINDER_WINDOW );
		$cursor = 0;
		$batch  = 100;
		$sent   = 0;
		do {
			$rows_raw = $wpdb->get_results( $wpdb->prepare( "SELECT id FROM {$table} WHERE status='scheduled' AND reminder_sent_at IS NULL AND due_at<=%s AND due_at>=%s AND id>%d ORDER BY id ASC LIMIT %d", $cutoff, $now, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_reminder_scan_read_failed', __( 'Due follow-up reminders could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
			$rows = (array) $rows_raw;
			foreach ( $rows as $candidate ) {
				$id = absint( $candidate['id'] );
				$cursor = max( $cursor, $id );
				if ( ! $id ) { continue; }
				$outcome = WCA_Repository::transaction( function () use ( $table, $id, $now ) {
					global $wpdb;
					$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d AND status='scheduled' AND reminder_sent_at IS NULL FOR UPDATE", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_reminder_lock_read_failed', __( 'Follow-up reminder state could not be locked safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
					if ( ! $row ) { return false; }
					$trace = WCA_Observability::trace_id();
					$queued = WCA_Repository::enqueue( 'File19.NotificationRequested.v1', (string) $row['public_ref'], array(
						'recipients'      => array( absint( $row['patient_user_id'] ) ),
						'event'           => 'followup_due',
						'appointment_ref' => self::appointment_ref( absint( $row['appointment_id'] ) ),
						'followup_ref'    => (string) $row['public_ref'],
						'contract'        => self::CONTRACT_VERSION,
					), $trace );
					if ( is_wp_error( $queued ) ) { return $queued; }
					$changed = $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET reminder_sent_at=%s,updated_at=%s,version=version+1 WHERE id=%d AND status='scheduled' AND reminder_sent_at IS NULL AND version=%d", $now, $now, $id, absint( $row['version'] ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( 1 !== (int) $changed ) { return new WP_Error( 'wca_followup_reminder_conflict', __( 'Follow-up reminder state changed before it could be queued safely.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
					return array( 'sent' => true, 'followup_ref' => (string) $row['public_ref'], 'trace_id' => $trace );
				}, 'wca_followup_reminder_transaction' );
				if ( is_wp_error( $outcome ) ) {
					WCA_Observability::log( 'error', 'followup_reminder_transaction_failed', array( 'followup_id' => $id, 'error_code' => $outcome->get_error_code() ) );
					continue;
				}
				if ( is_array( $outcome ) && ! empty( $outcome['sent'] ) ) { $sent++; }
			}
		} while ( count( $rows ) === $batch );
		return $sent;
	}

	public static function apply_retention() {
		global $wpdb;
		$intake_days   = max( 30, absint( apply_filters( 'wca_intake_retention_days', 365 ) ) );
		$followup_days = max( 30, absint( apply_filters( 'wca_followup_retention_days', 730 ) ) );
		$intake_cutoff = gmdate( 'Y-m-d H:i:s', time() - $intake_days * DAY_IN_SECONDS );
		$follow_cutoff = gmdate( 'Y-m-d H:i:s', time() - $followup_days * DAY_IN_SECONDS );
		$intake_table  = self::tables()['intake'];
		$follow_table  = self::tables()['followups'];
		$batch = 200;
		$cursor = 0;
		do {
			$intakes_raw = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id FROM {$intake_table} WHERE updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d", $intake_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $intakes_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_intake_retention_read_failed', __( 'Expired pre-visit records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$intakes = (array) $intakes_raw;
			foreach ( $intakes as $row ) {
				$row_id = absint( $row['id'] );
				if ( self::legal_hold( 'intake', $row ) ) { $cursor = max( $cursor, $row_id ); continue; }
				$status = SWC_Helpers::status( absint( $row['appointment_id'] ) );
				if ( WCA_Contracts::is_terminal( $status ) ) {
					$deleted = $wpdb->delete( $intake_table, array( 'id' => $row_id ), array( '%d' ) );
					if ( false === $deleted ) { return new WP_Error( 'wca_intake_retention_delete', __( 'Expired pre-visit data could not be removed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				}
				$cursor = max( $cursor, $row_id );
			}
		} while ( count( $intakes ) === $batch );
		$cursor = 0;
		do {
			$followups_raw = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id,status FROM {$follow_table} WHERE updated_at<%s AND status IN ('completed','cancelled') AND id>%d ORDER BY id ASC LIMIT %d", $follow_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $followups_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_retention_read_failed', __( 'Expired follow-up records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$followups = (array) $followups_raw;
			foreach ( $followups as $row ) {
				$row_id = absint( $row['id'] );
				if ( ! self::legal_hold( 'followup', $row ) ) {
					$deleted = $wpdb->delete( $follow_table, array( 'id' => $row_id ), array( '%d' ) );
					if ( false === $deleted ) { return new WP_Error( 'wca_followup_retention_delete', __( 'Expired follow-up data could not be removed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				}
				$cursor = max( $cursor, $row_id );
			}
		} while ( count( $followups ) === $batch );
		return true;
	}

	public static function register_exporter( $exporters ) {
		$exporters['wca-continuity'] = array( 'exporter_friendly_name' => __( 'Worldwide Clinic continuity data', 'worldwide-clinic-appointments' ), 'callback' => array( __CLASS__, 'privacy_exporter' ) );
		return $exporters;
	}

	public static function register_eraser( $erasers ) {
		$erasers['wca-continuity'] = array( 'eraser_friendly_name' => __( 'Worldwide Clinic continuity data', 'worldwide-clinic-appointments' ), 'callback' => array( __CLASS__, 'privacy_eraser' ) );
		return $erasers;
	}

	/** @return array<string,mixed> */
	public static function privacy_exporter( $email_address, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', sanitize_email( $email_address ) );
		if ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
		$user_id = absint( $user->ID );
		$page = max( 1, absint( $page ) );
		$limit = 50;
		$offset = ( $page - 1 ) * $limit;
		$data = array();
		$intake_table = self::tables()['intake'];
		$follow_table = self::tables()['followups'];
		$intakes_raw = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$intake_table} WHERE patient_user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $limit, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $intakes_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_continuity_export_intake_query', __( 'Continuity intake data could not be read safely for export.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$intakes = (array) $intakes_raw;
		foreach ( $intakes as $row ) {
			$payload = self::open( $row );
			if ( is_wp_error( $payload ) ) { return new WP_Error( 'wca_continuity_export_intake_decrypt', __( 'Continuity intake data could not be decrypted safely for export.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$data[] = array( 'group_id' => 'wca-continuity-intake', 'group_label' => __( 'Clinic pre-visit information', 'worldwide-clinic-appointments' ), 'item_id' => 'intake-' . $row['public_ref'], 'data' => self::export_fields( $payload, array( 'Appointment reference' => self::appointment_ref( absint( $row['appointment_id'] ) ), 'Status' => $row['status'], 'Updated' => $row['updated_at'] ) ) );
		}
		$followups_raw = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$follow_table} WHERE patient_user_id=%d ORDER BY id ASC LIMIT %d OFFSET %d", $user_id, $limit, $offset ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $followups_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_continuity_export_followup_query', __( 'Continuity follow-up data could not be read safely for export.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$followups = (array) $followups_raw;
		foreach ( $followups as $row ) {
			$payload = self::open( $row );
			if ( is_wp_error( $payload ) ) { return new WP_Error( 'wca_continuity_export_followup_decrypt', __( 'Continuity follow-up data could not be decrypted safely for export.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$data[] = array( 'group_id' => 'wca-continuity-followup', 'group_label' => __( 'Clinic follow-up plans', 'worldwide-clinic-appointments' ), 'item_id' => 'followup-' . $row['public_ref'], 'data' => self::export_fields( $payload, array( 'Appointment reference' => self::appointment_ref( absint( $row['appointment_id'] ) ), 'Due' => $row['due_at'], 'Status' => $row['status'] ) ) );
		}
		$intake_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$intake_table} WHERE patient_user_id=%d", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $intake_count && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_continuity_export_intake_count', __( 'Continuity intake export could not determine completion safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$follow_count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$follow_table} WHERE patient_user_id=%d", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $follow_count && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_continuity_export_followup_count', __( 'Continuity follow-up export could not determine completion safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$count_more = absint( $intake_count ) + absint( $follow_count );
		return array( 'data' => $data, 'done' => ( $offset + $limit ) >= $count_more );
	}

	/** @return array<string,mixed> */
	public static function privacy_eraser( $email_address, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', sanitize_email( $email_address ) );
		if ( ! $user ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true ); }
		$user_id = absint( $user->ID );
		$page = max( 1, absint( $page ) );
		$base = 'wca_continuity_erase_' . substr( hash( 'sha256', strtolower( sanitize_email( $email_address ) ) ), 0, 24 );
		if ( 1 === $page ) {
			delete_transient( $base . '_intake' );
			delete_transient( $base . '_followups' );
		}
		$removed = false;
		$retained = false;
		$messages = array();
		$done = true;
		foreach ( array( 'intake' => 'patient_user_id', 'followups' => 'patient_user_id' ) as $type => $field ) {
			$table = self::tables()[ $type ];
			$cursor_key = $base . '_' . $type;
			$cursor = absint( get_transient( $cursor_key ) );
			$rows_raw = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d", $user_id, $cursor, 100 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $rows_raw && '' !== (string) $wpdb->last_error ) {
				$messages[] = __( 'Continuity privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' );
				$done = false;
				continue;
			}
			$rows = (array) $rows_raw;
			$last = $cursor;
			foreach ( $rows as $row ) {
				$row_id = absint( $row['id'] );
				if ( self::legal_hold( 'followups' === $type ? 'followup' : 'intake', $row ) ) { $retained = true; $last = max( $last, $row_id ); continue; }
				$deleted = $wpdb->delete( $table, array( 'id' => $row_id ), array( '%d' ) );
				if ( false === $deleted ) {
					$messages[] = __( 'Continuity privacy erasure encountered a storage failure and will retry without skipping the affected record.', 'worldwide-clinic-appointments' );
					$done = false;
					break;
				}
				if ( 0 === (int) $deleted ) {
					$still_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id=%d AND {$field}=%d", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( null === $still_exists && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Continuity privacy erasure could not verify a zero-row delete safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }
					if ( $still_exists ) { $messages[] = __( 'Continuity privacy erasure could not remove an affected record and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }
				}
				$last = max( $last, $row_id );
				$removed = true;
			}
			if ( $last > $cursor ) { set_transient( $cursor_key, $last, HOUR_IN_SECONDS ); }
			$more = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT 1", $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $more && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Continuity privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
			elseif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }
		}
		$intake_table = self::tables()['intake'];
		$guardian_update = $wpdb->update( $intake_table, array( 'guardian_user_id' => 0 ), array( 'guardian_user_id' => $user_id ), array( '%d' ), array( '%d' ) );
		if ( false === $guardian_update ) { $messages[] = __( 'Guardian continuity references could not be anonymized safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
		elseif ( 0 === (int) $guardian_update ) {
			$guardian_remaining = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$intake_table} WHERE guardian_user_id=%d LIMIT 1", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $guardian_remaining && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Guardian continuity references could not be verified safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
			elseif ( $guardian_remaining ) { $messages[] = __( 'Guardian continuity references remain linked and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
		}
		if ( $retained ) { $messages[] = __( 'Some clinic continuity records are retained under an active legal, safety or professional record hold.', 'worldwide-clinic-appointments' ); }
		return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => $messages, 'done' => $done );
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
		register_rest_route( 'wca/v1', '/continuity/health', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_health' ), 'permission_callback' => array( __CLASS__, 'admin' ) ) );
	}

	public static function authenticated() { return is_user_logged_in() ? true : new WP_Error( 'wca_auth_required', __( 'Authentication is required.', 'worldwide-clinic-appointments' ), array( 'status' => 401 ) ); }
	public static function admin() { return current_user_can( 'manage_wca_operations' ) || current_user_can( 'manage_worldwide_clinic' ) || current_user_can( 'manage_options' ) ? true : new WP_Error( 'wca_admin_required', __( 'Operations permission is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
	public static function rest_get_intake( WP_REST_Request $request ) { return self::no_store( self::get_intake( $request['ref'] ) ); }
	public static function rest_save_intake( WP_REST_Request $request ) { $r=self::rate_limit('intake_save',30,300); if(is_wp_error($r)){return $r;} return self::no_store( self::save_intake( $request['ref'], self::request_data( $request ), 0, false ) ); }
	public static function rest_submit_intake( WP_REST_Request $request ) { $r=self::rate_limit('intake_submit',10,HOUR_IN_SECONDS); if(is_wp_error($r)){return $r;} return self::no_store( self::save_intake( $request['ref'], self::request_data( $request ), 0, true ) ); }
	public static function rest_grant_consent( WP_REST_Request $request ) { $d=self::request_data($request); return self::no_store( self::grant_context_consent( $request['ref'], isset($d['scope'])?$d['scope']:'' ), 201 ); }
	public static function rest_revoke_consent( WP_REST_Request $request ) { $d=self::request_data($request); return self::no_store( self::revoke_context_consent( $request['ref'], isset($d['scope'])?$d['scope']:'' ) ); }
	public static function rest_file17_context( WP_REST_Request $request ) { return self::no_store( self::file17_context( $request['ref'] ) ); }
	public static function rest_list_followups( WP_REST_Request $request ) { return self::no_store( self::list_followups( $request['ref'] ) ); }
	public static function rest_create_followup( WP_REST_Request $request ) { $r=self::rate_limit('followup_create',20,HOUR_IN_SECONDS); if(is_wp_error($r)){return $r;} return self::no_store( self::create_followup( $request['ref'], self::request_data($request) ), 201 ); }
	public static function rest_complete_followup( WP_REST_Request $request ) { return self::no_store( self::complete_followup( $request['ref'] ) ); }
	public static function rest_health() { return self::no_store( self::health() ); }

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
		return self::payload_size_ok( $out ) ? $out : new WP_Error( 'wca_intake_size', __( 'Pre-visit information is too large.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function sanitize_followup( $data ) {
		$resources = (array) ( isset( $data['resources'] ) ? $data['resources'] : array() );
		if ( count( $resources ) > 20 ) { return new WP_Error( 'wca_followup_resource_limit', __( 'No more than 20 follow-up resources may be saved in one plan.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
		$out = array(
			'purpose'      => self::bounded_text( isset( $data['purpose'] ) ? $data['purpose'] : '', 191 ),
			'instructions' => self::bounded_textarea( isset( $data['instructions'] ) ? $data['instructions'] : '', 5000 ),
			'limitations'  => self::bounded_textarea( isset( $data['limitations'] ) ? $data['limitations'] : '', 1500 ),
			'resources'    => self::sanitize_resource_refs( $resources ),
		);
		if ( '' === $out['purpose'] ) { return new WP_Error( 'wca_followup_purpose', __( 'A follow-up purpose is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		return self::payload_size_ok( $out ) ? $out : new WP_Error( 'wca_followup_size', __( 'Follow-up plan is too large.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) );
	}

	/** @return array<int,array<string,string>> */
	private static function sanitize_resource_refs( $resources ) {
		$out = array();
		$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
		foreach ( (array) $resources as $resource ) {
			if ( ! is_array( $resource ) ) { continue; }
			$type = sanitize_key( isset( $resource['type'] ) ? $resource['type'] : 'educational' );
			$ref  = sanitize_text_field( isset( $resource['ref'] ) ? $resource['ref'] : '' );
			$url  = esc_url_raw( isset( $resource['url'] ) ? $resource['url'] : '' );
			if ( $url ) {
				$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
				$allowed = $host && $home_host && hash_equals( $home_host, $host );
				$allowed = (bool) apply_filters( 'wca_followup_resource_url_allowed', $allowed, $url, $type, $ref );
				if ( ! $allowed ) { $url = ''; }
			}
			if ( ! $ref && ! $url ) { continue; }
			$out[] = array( 'type' => $type, 'ref' => $ref, 'url' => $url );
		}
		return $out;
	}

	/** @return array<string,string>|WP_Error */
	private static function seal( $payload ) {
		$primary = self::primary_key();
		if ( is_wp_error( $primary ) ) { return $primary; }
		$plain = self::stable_json( $payload );
		if ( function_exists( 'sodium_crypto_secretbox' ) ) {
			$nonce  = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );
			$cipher = sodium_crypto_secretbox( $plain, $nonce, $primary['key'] );
			return array( 'key_id' => $primary['id'], 'alg' => 'sodium-secretbox-v1', 'ciphertext' => bin2hex( $cipher ), 'nonce' => bin2hex( $nonce ), 'tag' => '' );
		}
		if ( function_exists( 'openssl_encrypt' ) ) {
			$nonce = random_bytes( 12 );
			$tag = '';
			$cipher = openssl_encrypt( $plain, 'aes-256-gcm', $primary['key'], OPENSSL_RAW_DATA, $nonce, $tag, 'wca-continuity-v1' );
			if ( false !== $cipher ) { return array( 'key_id' => $primary['id'], 'alg' => 'aes-256-gcm-v1', 'ciphertext' => bin2hex( $cipher ), 'nonce' => bin2hex( $nonce ), 'tag' => bin2hex( $tag ) ); }
		}
		return new WP_Error( 'wca_crypto_unavailable', __( 'Secure storage is unavailable. Sensitive data was not saved.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function open( $row ) {
		$keys = self::keyring();
		$key_id = sanitize_text_field( isset( $row['key_id'] ) ? $row['key_id'] : '' );
		if ( ! $key_id || ! isset( $keys[ $key_id ] ) ) { return new WP_Error( 'wca_crypto_key_unavailable', __( 'The encryption key needed for this record is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$cipher = self::decode_hex( isset( $row['ciphertext'] ) ? $row['ciphertext'] : '' );
		$nonce  = self::decode_hex( isset( $row['nonce'] ) ? $row['nonce'] : '' );
		if ( false === $cipher || false === $nonce ) { return new WP_Error( 'wca_crypto_payload', __( 'Sensitive data could not be decoded.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$plain = false;
		if ( 'sodium-secretbox-v1' === (string) $row['cipher_alg'] && function_exists( 'sodium_crypto_secretbox_open' ) ) {
			$plain = sodium_crypto_secretbox_open( $cipher, $nonce, $keys[ $key_id ] );
		} elseif ( 'aes-256-gcm-v1' === (string) $row['cipher_alg'] && function_exists( 'openssl_decrypt' ) ) {
			$tag = self::decode_hex( isset( $row['auth_tag'] ) ? $row['auth_tag'] : '' );
			if ( false !== $tag ) { $plain = openssl_decrypt( $cipher, 'aes-256-gcm', $keys[ $key_id ], OPENSSL_RAW_DATA, $nonce, $tag, 'wca-continuity-v1' ); }
		}
		if ( ! is_string( $plain ) ) { return new WP_Error( 'wca_crypto_integrity', __( 'Sensitive data failed integrity verification.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$data = json_decode( $plain, true );
		if ( ! is_array( $data ) || ! hash_equals( (string) $row['payload_hash'], hash( 'sha256', self::stable_json( $data ) ) ) ) { return new WP_Error( 'wca_crypto_hash', __( 'Sensitive data failed integrity verification.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		return $data;
	}

	/** @return array{id:string,key:string}|WP_Error */
	private static function primary_key() {
		$material = apply_filters( 'wca_continuity_encryption_key', '', self::CONTRACT_VERSION );
		if ( is_string( $material ) && strlen( $material ) >= 32 ) {
			$id = 'external-' . substr( hash( 'sha256', $material ), 0, 16 );
			return array( 'id' => $id, 'key' => hash( 'sha256', $material, true ) );
		}
		$fallback = wp_salt( 'auth' );
		if ( ! is_string( $fallback ) || strlen( $fallback ) < 32 ) { return new WP_Error( 'wca_crypto_key', __( 'A secure encryption key is not available.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$id = 'wp-auth-' . substr( hash( 'sha256', $fallback ), 0, 16 );
		return array( 'id' => $id, 'key' => hash( 'sha256', $fallback . '|wca-continuity|' . home_url( '/' ), true ) );
	}

	/** @return array<string,string> */
	private static function keyring() {
		$primary = self::primary_key();
		$keys = is_wp_error( $primary ) ? array() : array( $primary['id'] => $primary['key'] );
		$extra = apply_filters( 'wca_continuity_decryption_keys', array(), self::CONTRACT_VERSION );
		foreach ( (array) $extra as $id => $material ) {
			$id = sanitize_key( $id );
			if ( $id && is_string( $material ) && strlen( $material ) >= 32 ) { $keys[ $id ] = hash( 'sha256', $material, true ); }
		}
		return $keys;
	}

	private static function keyring_health() { return self::keyring() ? 'ok' : 'missing'; }
	private static function decode_hex( $value ) { $value=trim((string)$value); if(''===$value||0!==strlen($value)%2||!ctype_xdigit($value)){return false;} return hex2bin($value); }
	private static function stable_json( $value ) { $json=wp_json_encode($value,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE); return is_string($json)?$json:'{}'; }
	private static function payload_size_ok( $value ) { return strlen( self::stable_json( $value ) ) <= self::MAX_PAYLOAD_BYTES; }
	private static function bounded_text( $value, $max ) { $value=sanitize_text_field($value); return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max); }
	private static function bounded_textarea( $value, $max ) { $value=sanitize_textarea_field($value); return function_exists('mb_substr')?mb_substr($value,0,$max):substr($value,0,$max); }
	private static function context_consent_scopes() { return array( 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' ); }
	private static function request_data( WP_REST_Request $request ) { $data=$request->get_json_params(); return is_array($data)?$data:$request->get_params(); }
	private static function rate_limit( $scope, $limit=30, $window=300 ) { return SWC_Helpers::rate_limit_hit('continuity_'.sanitize_key($scope),absint(get_current_user_id()),$limit,$window)?new WP_Error('wca_rate_limit',__('Too many requests. Please try again later.','worldwide-clinic-appointments'),array('status'=>429,'retry_after'=>$window)):true; }
	private static function no_store( $result, $status=200 ) { if(is_wp_error($result)){return $result;} $response=rest_ensure_response($result); $response->set_status($status); $response->header('Cache-Control','private, no-store, max-age=0'); $response->header('X-Robots-Tag','noindex, noarchive, nofollow'); $response->header('X-Request-ID',WCA_Observability::trace_id()); return $response; }
	private static function active_consent( $appointment_id, $scope ) { global $wpdb; $table=WCA_Schema::tables()['consents']; $count=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL",absint($appointment_id),sanitize_key($scope))); return absint($count)>0; } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	private static function patient_id( $appointment_id ) { return absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ); }
	private static function appointment_ref( $appointment_id ) { $ref=(string)SWC_Helpers::meta(absint($appointment_id),'public_ref',''); return preg_match('/^[0-9a-f-]{36}$/i',$ref)?strtolower($ref):''; }
	private static function appointment_id( $ref ) { $ref=sanitize_text_field($ref); if(!preg_match('/^[0-9a-f-]{36}$/i',$ref)){return 0;} $ids=get_posts(array('post_type'=>SWC_Helpers::TYPE,'post_status'=>'any','fields'=>'ids','posts_per_page'=>2,'no_found_rows'=>true,'meta_key'=>'_swc_public_ref','meta_value'=>$ref)); if(1!==count($ids)){$ids=get_posts(array('post_type'=>SWC_Helpers::TYPE,'post_status'=>'any','fields'=>'ids','posts_per_page'=>2,'no_found_rows'=>true,'meta_key'=>'public_ref','meta_value'=>$ref));} return 1===count($ids)?absint($ids[0]):0; }
	private static function followup_actor_allowed( $appointment_id, $user_id ) { $user_id=absint($user_id); if('doctor'===WCA_Authorization::appointment_actor($appointment_id,$user_id)){return true;} $clinic_id=absint(SWC_Helpers::meta($appointment_id,'clinic_id',0)); $delegated=(array)get_user_meta($user_id,'_wca_clinic_delegations',true); $entry=isset($delegated[$clinic_id])&&is_array($delegated[$clinic_id])?$delegated[$clinic_id]:array(); $allowed=!empty($entry['active'])&&(!empty($entry['clinical_followup'])||!empty($entry['clinical'])); return (bool)apply_filters('wca_followup_actor_allowed',$allowed,$appointment_id,$user_id); }
	private static function legal_hold( $type, $row ) { return (bool)apply_filters('wca_continuity_legal_hold',false,sanitize_key($type),(array)$row); }
	private static function not_found() { return new WP_Error('wca_appointment_not_found',__('Appointment was not found.','worldwide-clinic-appointments'),array('status'=>404)); }

	/** @return array<int,array<string,string>> */
	private static function export_fields( $payload, $extra ) {
		$out = array();
		foreach ( (array) $extra as $name => $value ) { $out[] = array( 'name' => (string) $name, 'value' => is_scalar( $value ) ? (string) $value : self::stable_json( $value ) ); }
		foreach ( (array) $payload as $name => $value ) { $out[] = array( 'name' => ucwords( str_replace( '_', ' ', sanitize_key( $name ) ) ), 'value' => is_scalar( $value ) ? (string) $value : self::stable_json( $value ) ); }
		return $out;
	}
}

/** Versioned File 17 helper; File 17 consumes context but owns transport. */
function wca_get_file17_clinic_context( $appointment_ref, $actor_user_id = 0 ) {
	return WCA_Continuity::file17_context( $appointment_ref, $actor_user_id );
}
