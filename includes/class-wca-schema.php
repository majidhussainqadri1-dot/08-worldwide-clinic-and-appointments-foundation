<?php
/**
 * Canonical File 08 database schema, migration, verification, and rollback.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Schema {
	const OPTION_DB_VERSION      = 'wca_db_version';
	const OPTION_SCHEMA_SNAPSHOT = 'wca_schema_snapshot';
	const OPTION_MIGRATION_STATE = 'wca_migration_state';

	/** @return array<string,string> */
	public static function tables() {
		global $wpdb;
		return array(
			'clinics'              => $wpdb->prefix . 'wca_clinics',
			'branches'             => $wpdb->prefix . 'wca_branches',
			'services'             => $wpdb->prefix . 'wca_services',
			'availability'         => $wpdb->prefix . 'wca_availability_rules',
			'slot_holds'           => $wpdb->prefix . 'wca_slot_holds',
			'consents'             => $wpdb->prefix . 'wca_consents',
			'events'               => $wpdb->prefix . 'wca_events',
			'review_eligibility'   => $wpdb->prefix . 'wca_review_eligibility',
			'clinical_context'     => $wpdb->prefix . 'wca_clinical_context_refs',
			'complaints'           => $wpdb->prefix . 'wca_complaints',
			'outbox'               => $wpdb->prefix . 'wca_outbox',
			'payment_intents'      => $wpdb->prefix . 'wca_payment_intents',
			'calendar_mappings'    => $wpdb->prefix . 'wca_calendar_mappings',
			'idempotency'          => $wpdb->prefix . 'wca_idempotency',
			'metrics'              => $wpdb->prefix . 'wca_metrics',
		);
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$tables  = self::tables();
		$collate = $wpdb->get_charset_collate();

		self::capture_snapshot();

		$definitions = array(
			"CREATE TABLE {$tables['clinics']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				slug varchar(191) NOT NULL,
				owner_user_id bigint(20) unsigned NOT NULL,
				owner_subject_uuid char(36) NOT NULL DEFAULT '',
				name varchar(191) NOT NULL,
				summary text NOT NULL,
				languages_json longtext NOT NULL,
				contacts_json longtext NOT NULL,
				policies_json longtext NOT NULL,
				status varchar(30) NOT NULL DEFAULT 'draft',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				archived_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				UNIQUE KEY slug (slug),
				KEY owner_user_id (owner_user_id),
				KEY status (status)
			) {$collate};",
			"CREATE TABLE {$tables['branches']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				clinic_id bigint(20) unsigned NOT NULL,
				name varchar(191) NOT NULL,
				country_code char(2) NOT NULL DEFAULT '',
				region varchar(120) NOT NULL DEFAULT '',
				city varchar(120) NOT NULL DEFAULT '',
				address_public text NOT NULL,
				address_private text NOT NULL,
				timezone varchar(100) NOT NULL DEFAULT 'UTC',
				contacts_json longtext NOT NULL,
				visibility varchar(20) NOT NULL DEFAULT 'public',
				status varchar(20) NOT NULL DEFAULT 'active',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY clinic_id (clinic_id),
				KEY country_city (country_code,city),
				KEY status (status)
			) {$collate};",
			"CREATE TABLE {$tables['services']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				clinic_id bigint(20) unsigned NOT NULL,
				branch_id bigint(20) unsigned NOT NULL DEFAULT 0,
				doctor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				name varchar(191) NOT NULL,
				consultation_type varchar(30) NOT NULL,
				duration_minutes smallint(5) unsigned NOT NULL,
				currency char(3) NOT NULL,
				fee_minor bigint(20) unsigned NOT NULL DEFAULT 0,
				fee_max_minor bigint(20) unsigned NOT NULL DEFAULT 0,
				tax_policy text NOT NULL,
				refund_policy text NOT NULL,
				cancellation_policy text NOT NULL,
				platform_commission_bps smallint(5) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'active',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY clinic_status (clinic_id,status),
				KEY doctor_user_id (doctor_user_id)
			) {$collate};",
			"CREATE TABLE {$tables['availability']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				clinic_id bigint(20) unsigned NOT NULL,
				branch_id bigint(20) unsigned NOT NULL DEFAULT 0,
				service_id bigint(20) unsigned NOT NULL DEFAULT 0,
				doctor_user_id bigint(20) unsigned NOT NULL,
				timezone varchar(100) NOT NULL,
				rrule_json longtext NOT NULL,
				breaks_json longtext NOT NULL,
				exceptions_json longtext NOT NULL,
				buffer_before smallint(5) unsigned NOT NULL DEFAULT 0,
				buffer_after smallint(5) unsigned NOT NULL DEFAULT 0,
				capacity smallint(5) unsigned NOT NULL DEFAULT 1,
				status varchar(20) NOT NULL DEFAULT 'active',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY doctor_service (doctor_user_id,service_id),
				KEY clinic_status (clinic_id,status)
			) {$collate};",
			"CREATE TABLE {$tables['slot_holds']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				hold_token char(64) NOT NULL,
				idempotency_key char(64) NOT NULL,
				clinic_id bigint(20) unsigned NOT NULL,
				branch_id bigint(20) unsigned NOT NULL DEFAULT 0,
				service_id bigint(20) unsigned NOT NULL DEFAULT 0,
				doctor_user_id bigint(20) unsigned NOT NULL,
				patient_user_id bigint(20) unsigned NOT NULL,
				start_utc datetime NOT NULL,
				end_utc datetime NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'held',
				appointment_id bigint(20) unsigned NOT NULL DEFAULT 0,
				expires_at datetime NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY hold_token (hold_token),
				UNIQUE KEY idempotency_key (idempotency_key),
				KEY resource_window (doctor_user_id,start_utc,end_utc,status),
				KEY clinic_branch (clinic_id,branch_id),
				KEY expires_at (expires_at)
			) {$collate};",
			"CREATE TABLE {$tables['consents']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL,
				actor_subject_uuid char(36) NOT NULL DEFAULT '',
				guardian_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				scope varchar(80) NOT NULL,
				terms_version varchar(40) NOT NULL,
				terms_hash char(64) NOT NULL,
				legal_basis varchar(80) NOT NULL DEFAULT 'consent',
				status varchar(20) NOT NULL DEFAULT 'granted',
				granted_at datetime NOT NULL,
				revoked_at datetime NULL,
				metadata_json longtext NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY appointment_scope (appointment_id,scope),
				KEY actor_user_id (actor_user_id)
			) {$collate};",
			"CREATE TABLE {$tables['events']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				event_id char(36) NOT NULL,
				event_type varchar(100) NOT NULL,
				aggregate_type varchar(40) NOT NULL,
				aggregate_ref varchar(80) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				trace_id char(36) NOT NULL,
				payload_json longtext NOT NULL,
				privacy_class varchar(30) NOT NULL DEFAULT 'restricted',
				occurred_at datetime NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY event_id (event_id),
				KEY aggregate (aggregate_type,aggregate_ref),
				KEY event_type (event_type),
				KEY trace_id (trace_id),
				KEY occurred_at (occurred_at)
			) {$collate};",
			"CREATE TABLE {$tables['review_eligibility']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				reviewer_user_id bigint(20) unsigned NOT NULL,
				doctor_user_id bigint(20) unsigned NOT NULL,
				clinic_id bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(20) NOT NULL DEFAULT 'eligible',
				eligibility_hash char(64) NOT NULL,
				granted_at datetime NOT NULL,
				expires_at datetime NOT NULL,
				used_at datetime NULL,
				revoked_at datetime NULL,
				revocation_reason varchar(191) NOT NULL DEFAULT '',
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				UNIQUE KEY appointment_reviewer (appointment_id,reviewer_user_id),
				KEY doctor_status (doctor_user_id,status),
				KEY expiry_status (expires_at,status)
			) {$collate};",
			"CREATE TABLE {$tables['clinical_context']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				patient_subject_uuid char(36) NOT NULL,
				practitioner_subject_uuid char(36) NOT NULL,
				purpose varchar(80) NOT NULL,
				access_status varchar(30) NOT NULL DEFAULT 'scheduling_only',
				treating_relationship_asserted tinyint(1) unsigned NOT NULL DEFAULT 0,
				clinical_read tinyint(1) unsigned NOT NULL DEFAULT 0,
				clinical_write tinyint(1) unsigned NOT NULL DEFAULT 0,
				prescription_authority tinyint(1) unsigned NOT NULL DEFAULT 0,
				break_glass tinyint(1) unsigned NOT NULL DEFAULT 0,
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				expires_at datetime NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY appointment_id (appointment_id),
				KEY expires_at (expires_at)
			) {$collate};",
			"CREATE TABLE {$tables['complaints']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL DEFAULT 0,
				clinic_id bigint(20) unsigned NOT NULL DEFAULT 0,
				complainant_user_id bigint(20) unsigned NOT NULL,
				category varchar(60) NOT NULL,
				summary text NOT NULL,
				evidence_refs_json longtext NOT NULL,
				purpose_limit varchar(191) NOT NULL,
				status varchar(30) NOT NULL DEFAULT 'submitted',
				assigned_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				outcome_json longtext NOT NULL,
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				closed_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				KEY appointment_id (appointment_id),
				KEY clinic_status (clinic_id,status),
				KEY complainant_user_id (complainant_user_id)
			) {$collate};",
			"CREATE TABLE {$tables['outbox']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				message_id char(36) NOT NULL,
				topic varchar(100) NOT NULL,
				aggregate_ref varchar(80) NOT NULL,
				payload_json longtext NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'pending',
				attempts smallint(5) unsigned NOT NULL DEFAULT 0,
				next_attempt_at datetime NOT NULL,
				locked_at datetime NULL,
				locked_by varchar(80) NOT NULL DEFAULT '',
				last_error varchar(500) NOT NULL DEFAULT '',
				trace_id char(36) NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				delivered_at datetime NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY message_id (message_id),
				KEY dispatch (status,next_attempt_at),
				KEY topic (topic),
				KEY trace_id (trace_id)
			) {$collate};",
			"CREATE TABLE {$tables['payment_intents']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				provider varchar(60) NOT NULL DEFAULT 'manual',
				provider_ref varchar(191) NULL DEFAULT NULL,
				request_key char(64) NULL DEFAULT NULL,
				currency char(3) NOT NULL,
				amount_minor bigint(20) unsigned NOT NULL,
				platform_commission_minor bigint(20) unsigned NOT NULL DEFAULT 0,
				status varchar(30) NOT NULL DEFAULT 'pending',
				version bigint(20) unsigned NOT NULL DEFAULT 1,
				metadata_json longtext NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				UNIQUE KEY provider_ref (provider,provider_ref),
				UNIQUE KEY appointment_request (appointment_id,provider,request_key),
				KEY appointment_id (appointment_id),
				KEY status (status)
			) {$collate};",
			"CREATE TABLE {$tables['calendar_mappings']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				public_ref char(36) NOT NULL,
				appointment_id bigint(20) unsigned NOT NULL,
				provider varchar(60) NOT NULL,
				provider_event_ref varchar(191) NOT NULL,
				etag varchar(191) NOT NULL DEFAULT '',
				last_synced_at datetime NULL,
				sync_status varchar(30) NOT NULL DEFAULT 'pending',
				conflict_status varchar(30) NOT NULL DEFAULT 'none',
				metadata_json longtext NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY public_ref (public_ref),
				UNIQUE KEY provider_event (provider,provider_event_ref),
				KEY appointment_id (appointment_id),
				KEY sync_status (sync_status)
			) {$collate};",
			"CREATE TABLE {$tables['idempotency']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				scope varchar(80) NOT NULL,
				key_hash char(64) NOT NULL,
				actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
				request_hash char(64) NOT NULL,
				response_code smallint(5) unsigned NOT NULL DEFAULT 0,
				response_json longtext NOT NULL,
				status varchar(20) NOT NULL DEFAULT 'processing',
				expires_at datetime NOT NULL,
				created_at datetime NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY scope_key_actor (scope,key_hash,actor_user_id),
				KEY expires_at (expires_at),
				KEY status (status)
			) {$collate};",
			"CREATE TABLE {$tables['metrics']} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				metric_key varchar(120) NOT NULL,
				metric_bucket datetime NOT NULL,
				count_value bigint(20) unsigned NOT NULL DEFAULT 0,
				sum_value decimal(24,6) NOT NULL DEFAULT 0,
				min_value decimal(24,6) NULL,
				max_value decimal(24,6) NULL,
				dimensions_hash char(64) NOT NULL,
				dimensions_json text NOT NULL,
				updated_at datetime NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY metric_bucket_dimensions (metric_key,metric_bucket,dimensions_hash),
				KEY metric_bucket (metric_key,metric_bucket)
			) {$collate};",
		);

		foreach ( $definitions as $sql ) {
			dbDelta( $sql );
		}

		$missing = array();
		foreach ( $tables as $key => $table ) {
			if ( ! self::table_exists( $table ) ) {
				$missing[] = $key;
			}
		}
		if ( $missing ) {
			throw new RuntimeException( 'File 08 canonical tables missing: ' . implode( ', ', $missing ) );
		}

		update_option( self::OPTION_DB_VERSION, WCA_Contracts::SCHEMA_VERSION, false );
		update_option(
			self::OPTION_MIGRATION_STATE,
			array(
				'status'       => 'installed',
				'from_version' => (string) get_option( 'swc_db_version', '' ),
				'to_version'   => WCA_Contracts::SCHEMA_VERSION,
				'completed_at' => current_time( 'mysql', true ),
			),
			false
		);
	}

	public static function maybe_upgrade() {
		if ( WCA_Contracts::SCHEMA_VERSION !== (string) get_option( self::OPTION_DB_VERSION, '' ) ) {
			self::install();
		}
	}

	public static function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	private static function capture_snapshot() {
		if ( get_option( self::OPTION_SCHEMA_SNAPSHOT, false ) ) {
			return;
		}
		update_option(
			self::OPTION_SCHEMA_SNAPSHOT,
			array(
				'captured_at'       => current_time( 'mysql', true ),
				'swc_version'       => (string) get_option( 'swc_version', '' ),
				'swc_db_version'    => (string) get_option( 'swc_db_version', '' ),
				'wca_db_version'    => (string) get_option( self::OPTION_DB_VERSION, '' ),
				'swc_page_map'      => (array) get_option( 'swc_page_map', array() ),
				'wca_page_map'      => (array) get_option( 'wca_page_map', array() ),
			),
			false
		);
	}

	/** @return array<string,bool> */
	public static function health() {
		$health = array();
		foreach ( self::tables() as $key => $table ) {
			$health[ 'table_' . $key ] = self::table_exists( $table );
		}
		$health['schema_version'] = WCA_Contracts::SCHEMA_VERSION === (string) get_option( self::OPTION_DB_VERSION, '' );
		return $health;
	}

	public static function rollback_to_snapshot() {
		$snapshot = (array) get_option( self::OPTION_SCHEMA_SNAPSHOT, array() );
		if ( ! $snapshot ) {
			return new WP_Error( 'wca_no_snapshot', __( 'No File 08 schema snapshot is available.', 'worldwide-clinic-appointments' ) );
		}
		update_option( 'swc_page_map', (array) ( $snapshot['swc_page_map'] ?? array() ), false );
		update_option( 'wca_page_map', (array) ( $snapshot['wca_page_map'] ?? array() ), false );
		update_option( 'swc_version', (string) ( $snapshot['swc_version'] ?? '' ), false );
		update_option( 'swc_db_version', (string) ( $snapshot['swc_db_version'] ?? '' ), false );
		update_option( self::OPTION_DB_VERSION, (string) ( $snapshot['wca_db_version'] ?? '' ), false );
		update_option(
			self::OPTION_MIGRATION_STATE,
			array(
				'status'       => 'rolled_back_metadata_only',
				'completed_at' => current_time( 'mysql', true ),
				'note'         => 'Canonical tables retained to prevent silent data loss.',
			),
			false
		);
		return true;
	}

	public static function purge_canonical_data() {
		global $wpdb;
		foreach ( array_reverse( self::tables() ) as $table ) {
			if ( false === $wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				return new WP_Error( 'wca_purge_table_failed', __( 'A canonical File 08 table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ) ) );
			}
		}
		foreach ( array( self::OPTION_DB_VERSION, self::OPTION_SCHEMA_SNAPSHOT, self::OPTION_MIGRATION_STATE, 'wca_page_map', 'wca_runtime_version', 'wca_health_snapshot', 'wca_circuit_breakers' ) as $option ) {
			$deleted = delete_option( $option );
			if ( false === $deleted && false !== get_option( $option, false ) ) {
				return new WP_Error( 'wca_purge_option_failed', __( 'A canonical File 08 option could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'option' => sanitize_key( $option ) ) );
			}
		}
		return true;
	}
}
