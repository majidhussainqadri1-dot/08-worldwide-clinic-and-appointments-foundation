<?php
/**
 * Installation, schema migration, page ownership, repair, and rollback.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Activator {
	const DB_VERSION = '3.1.0';

	public static function dependencies_ready() {
		return class_exists( 'SPD_Helpers' )
			&& class_exists( 'SDD_Helpers' )
			&& class_exists( 'GDO_Helpers' )
			&& function_exists( 'smc_user_status' )
			&& function_exists( 'smc_is_founder' );
	}

	public static function dependency_message() {
		return __( 'Activate Files 00, 03, 07, and 09 before activating Worldwide Clinic. File 08 consumes their identity and verification contracts and does not create verification itself.', 'worldwide-clinic-appointments' );
	}

	public static function activate() {
		if ( ! self::dependencies_ready() ) {
			deactivate_plugins( plugin_basename( SWC_FILE ) );
			wp_die( esc_html( self::dependency_message() ), '', array( 'back_link' => true ) );
		}

		try {
			self::create_activation_snapshot();
			self::add_capabilities();
			self::register_type();
			self::install_schema();
			WCA_Schema::install();
			WCA_Continuity::install_schema();
			WCA_Future24::install_schema();
			WCA_Routes::register();
			WCA_Outbox::schedule();
			self::repair_pages();
			self::migrate_existing_records();
			foreach ( array( 'swc_version' => SWC_VERSION, 'swc_db_version' => self::DB_VERSION ) as $option => $value ) {
				$written = SWC_Helpers::update_option_strict( $option, $value, 'swc_activation_version_write' );
				if ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 activation version state could not be persisted.' ); }
			}
			set_transient( 'swc_activation_notice', '1', 120 );
			flush_rewrite_rules();
		} catch ( Throwable $e ) {
			$rollback = self::rollback_activation();
			deactivate_plugins( plugin_basename( SWC_FILE ) );
			$message = is_wp_error( $rollback )
				? sprintf( __( 'Worldwide Clinic activation failed and rollback is incomplete (%1$s): %2$s', 'worldwide-clinic-appointments' ), $rollback->get_error_code(), $e->getMessage() )
				: sprintf( __( 'Worldwide Clinic activation was rolled back: %s', 'worldwide-clinic-appointments' ), $e->getMessage() );
			wp_die( esc_html( $message ), '', array( 'back_link' => true ) );
		}
	}

	public static function deactivate() {
		WCA_Outbox::unschedule();
		wp_clear_scheduled_hook( 'wca_daily_health_snapshot' );
		self::remove_capabilities();
		// Data, owned pages, audit evidence, and migration snapshots are preserved.
		flush_rewrite_rules();
	}

	public static function maybe_upgrade() {
		if ( ! self::dependencies_ready() ) {
			return;
		}
		self::register_type();
		WCA_Routes::register();
		WCA_Schema::maybe_upgrade();
		WCA_Outbox::schedule();
		if ( self::DB_VERSION !== (string) get_option( 'swc_db_version', '' ) ) {
			self::install_schema();
			self::migrate_existing_records();
			$written = SWC_Helpers::update_option_strict( 'swc_db_version', self::DB_VERSION, 'swc_upgrade_db_version_write' );
			if ( is_wp_error( $written ) ) { WCA_Observability::log( 'error', 'legacy_upgrade_marker_failed', array( 'option' => 'swc_db_version' ) ); return $written; }
		}
		if ( SWC_VERSION !== (string) get_option( 'swc_version', '' ) ) {
			$written = SWC_Helpers::update_option_strict( 'swc_version', SWC_VERSION, 'swc_upgrade_runtime_version_write' );
			if ( is_wp_error( $written ) ) { WCA_Observability::log( 'error', 'legacy_upgrade_marker_failed', array( 'option' => 'swc_version' ) ); return $written; }
		}
		return true;
	}

	public static function register_type() {
		$capabilities = array(
			'edit_post'              => 'edit_swc_appointment',
			'read_post'              => 'read_swc_appointment',
			'delete_post'            => 'delete_swc_appointment',
			'edit_posts'             => 'edit_swc_appointments',
			'edit_others_posts'      => 'edit_others_swc_appointments',
			'publish_posts'          => 'publish_swc_appointments',
			'read_private_posts'     => 'read_private_swc_appointments',
			'delete_posts'           => 'delete_swc_appointments',
			'delete_private_posts'   => 'delete_private_swc_appointments',
			'delete_published_posts' => 'delete_published_swc_appointments',
			'delete_others_posts'    => 'delete_others_swc_appointments',
			'edit_private_posts'     => 'edit_private_swc_appointments',
			'edit_published_posts'   => 'edit_published_swc_appointments',
			'create_posts'           => 'do_not_allow',
		);
		register_post_type(
			SWC_Helpers::TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Clinic Appointments', 'worldwide-clinic-appointments' ),
					'singular_name' => __( 'Clinic Appointment', 'worldwide-clinic-appointments' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'show_ui'             => false,
				'show_in_rest'        => false,
				'exclude_from_search' => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => array( 'title', 'author' ),
				'capability_type'     => array( 'swc_appointment', 'swc_appointments' ),
				'capabilities'        => $capabilities,
				'map_meta_cap'        => true,
			)
		);
	}

	public static function add_capabilities() {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			throw new RuntimeException( 'Administrator role is unavailable.' );
		}
		foreach ( self::capabilities() as $capability ) {
			$administrator->add_cap( $capability );
		}
	}

	public static function remove_capabilities() {
		$administrator = get_role( 'administrator' );
		if ( ! $administrator ) {
			return;
		}
		foreach ( self::capabilities() as $capability ) {
			$administrator->remove_cap( $capability );
		}
	}

	private static function capabilities() {
		return array(
			'manage_worldwide_clinic',
			'manage_wca_clinics',
			'manage_wca_complaints',
			'manage_wca_operations',
			'edit_swc_appointment',
			'read_swc_appointment',
			'delete_swc_appointment',
			'edit_swc_appointments',
			'edit_others_swc_appointments',
			'publish_swc_appointments',
			'read_private_swc_appointments',
			'delete_swc_appointments',
			'delete_private_swc_appointments',
			'delete_published_swc_appointments',
			'delete_others_swc_appointments',
			'edit_private_swc_appointments',
			'edit_published_swc_appointments',
		);
	}

	public static function install_schema() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$collate = $wpdb->get_charset_collate();
		$audit   = $wpdb->prefix . 'swc_audit_log';
		$rate    = $wpdb->prefix . 'swc_rate_limits';

		dbDelta(
			"CREATE TABLE {$audit} (
				id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
				appointment_id bigint(20) unsigned NOT NULL,
				actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
				actor_role varchar(30) NOT NULL DEFAULT 'system',
				action varchar(80) NOT NULL DEFAULT '',
				event varchar(80) NOT NULL DEFAULT '',
				old_status varchar(40) NOT NULL DEFAULT '',
				new_status varchar(40) NOT NULL DEFAULT '',
				old_doctor_id bigint(20) unsigned NOT NULL DEFAULT 0,
				new_doctor_id bigint(20) unsigned NOT NULL DEFAULT 0,
				source varchar(40) NOT NULL DEFAULT 'web',
				note text NOT NULL,
				reason text NOT NULL,
				details_json longtext NOT NULL,
				created_at datetime NOT NULL,
				PRIMARY KEY  (id),
				KEY appointment_id (appointment_id),
				KEY actor_id (actor_id),
				KEY event (event),
				KEY created_at (created_at)
			) {$collate};"
		);

		dbDelta(
			"CREATE TABLE {$rate} (
				key_hash char(64) NOT NULL,
				hits int(10) unsigned NOT NULL DEFAULT 0,
				window_started datetime NOT NULL,
				expires_at datetime NOT NULL,
				PRIMARY KEY  (key_hash),
				KEY expires_at (expires_at)
			) {$collate};"
		);

		if ( ! self::table_exists( $audit ) || ! self::table_exists( $rate ) ) {
			throw new RuntimeException( 'Required File 08 database tables could not be created.' );
		}
	}

	private static function table_exists( $table ) {
		global $wpdb;
		return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
	}

	public static function migrate_existing_records() {
		global $wpdb;
		$cursor = absint( get_option( 'swc_legacy_record_migration_cursor', 0 ) );
		$migrated = 0;
		do {
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND post_status IN ('publish','private','draft') AND ID>%d ORDER BY ID ASC LIMIT 200",
					SWC_Helpers::TYPE,
					$cursor
				)
			); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $ids ) {
				throw new RuntimeException( 'File 08 legacy migration could not read the next bounded batch.' );
			}
			foreach ( (array) $ids as $id ) {
				$id = absint( $id );
				self::migrate_existing_record_strict( $id );
				$cursor = max( $cursor, $id );
				$migrated++;
			}
			if ( $ids ) {
				$checkpoint = SWC_Helpers::update_option_strict( 'swc_legacy_record_migration_cursor', $cursor, 'swc_migration_checkpoint_write' );
				if ( is_wp_error( $checkpoint ) ) { throw new RuntimeException( 'File 08 legacy migration checkpoint could not be persisted.' ); }
			}
		} while ( 200 === count( $ids ) );
		$cleared = SWC_Helpers::delete_option_strict( 'swc_legacy_record_migration_cursor', 'swc_migration_checkpoint_clear' );
		if ( is_wp_error( $cleared ) ) { throw new RuntimeException( 'File 08 legacy migration checkpoint could not be cleared.' ); }
		return $migrated;
	}

	private static function migrate_existing_record_strict( $id ) {
		global $wpdb;
		$started = $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( false === $started ) { throw new RuntimeException( 'File 08 legacy record migration transaction could not start.' ); }
		try {
			if ( '' === SWC_Helpers::appointment_public_ref( $id ) ) {
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_public_ref', WCA_Repository::uuid(), 'swc_migration_public_ref_write' );
				if ( is_wp_error( $written ) ) { throw new RuntimeException( $written->get_error_message() ); }
			}
			if ( 'private' !== get_post_status( $id ) ) {
				$updated = wp_update_post( array( 'ID' => $id, 'post_status' => 'private' ), true );
				if ( is_wp_error( $updated ) || 'private' !== get_post_status( $id ) ) { throw new RuntimeException( 'File 08 legacy appointment visibility could not be migrated.' ); }
			}
			if ( '' === get_post_meta( $id, '_swc_patient_user_id', true ) ) {
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_patient_user_id', absint( get_post_field( 'post_author', $id ) ), 'swc_migration_patient_write' );
				if ( is_wp_error( $written ) ) { throw new RuntimeException( $written->get_error_message() ); }
			}
			if ( ! get_post_meta( $id, '_swc_record_version', true ) ) {
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_record_version', 1, 'swc_migration_version_write' );
				if ( is_wp_error( $written ) ) { throw new RuntimeException( $written->get_error_message() ); }
			}
			$legacy_note = get_post_meta( $id, '_swc_doctor_note', true );
			if ( '' !== $legacy_note && '' === get_post_meta( $id, '_swc_doctor_private_note', true ) ) {
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_doctor_private_note', $legacy_note, 'swc_migration_note_write' );
				if ( is_wp_error( $written ) ) { throw new RuntimeException( $written->get_error_message() ); }
				$deleted = SWC_Helpers::delete_meta_strict( $id, '_swc_doctor_note', 'swc_migration_note_delete' );
				if ( is_wp_error( $deleted ) ) { throw new RuntimeException( $deleted->get_error_message() ); }
			}
			if ( '' === get_post_meta( $id, '_swc_appointment_duration', true ) ) {
				$doctor = absint( get_post_meta( $id, '_swc_doctor_id', true ) );
				$duration = min( 180, max( 10, absint( SWC_Helpers::doctor_meta( $doctor, 'duration', 30 ) ) ) );
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_appointment_duration', $duration, 'swc_migration_duration_write' );
				if ( is_wp_error( $written ) ) { throw new RuntimeException( $written->get_error_message() ); }
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) { throw new RuntimeException( 'File 08 legacy record migration transaction could not commit.' ); } // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		} catch ( Throwable $e ) {
			$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			wp_cache_delete( absint( $id ), 'post_meta' );
			if ( false === $rolled_back ) { throw new RuntimeException( 'File 08 legacy migration failed and rollback could not be verified; storage state is uncertain.', 0, $e ); }
			throw $e;
		}
	}

	public static function repair_pages() {
		$spf = (array) get_option( 'spf_page_map', array() );
		$map = (array) get_option( 'swc_page_map', array() );

		$map['clinic']       = self::ensure_page( ! empty( $spf['clinic'] ) ? absint( $spf['clinic'] ) : absint( $map['clinic'] ?? 0 ), 'Worldwide Clinic', 'worldwide-clinic-appointments', '[swc_worldwide_clinic]', true );
		$map['request']      = self::ensure_page( absint( $map['request'] ?? 0 ), 'Request an Appointment', 'request-appointment', '[swc_request_appointment]' );
		$map['patient']      = self::ensure_page( absint( $map['patient'] ?? 0 ), 'My Appointments', 'my-appointments', '[swc_my_appointments]' );
		$map['doctor']       = self::ensure_page( absint( $map['doctor'] ?? 0 ), 'Doctor Appointments', 'doctor-appointments', '[swc_doctor_appointments]' );
		$map['availability'] = self::ensure_page( absint( $map['availability'] ?? 0 ), 'Doctor Availability', 'doctor-availability', '[swc_doctor_availability]' );

		$written = SWC_Helpers::update_option_strict( 'swc_page_map', $map, 'swc_page_map_write' );
		if ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 page map could not be persisted.' ); }
		$spf['clinic'] = $map['clinic'];
		$written = SWC_Helpers::update_option_strict( 'spf_page_map', $spf, 'swc_shared_page_map_write' );
		if ( is_wp_error( $written ) ) { throw new RuntimeException( 'Shared platform page map could not be persisted safely.' ); }
		return $map;
	}

	private static function ensure_page( $id, $title, $slug, $shortcode, $allow_platform_placeholder = false ) {
		$page = $id ? get_post( $id ) : null;
		if ( $page instanceof WP_Post && 'page' === $page->post_type && 'trash' !== $page->post_status ) {
			$owned       = '1' === get_post_meta( $page->ID, '_swc_managed_page', true );
			$placeholder = $allow_platform_placeholder && trim( $page->post_content ) === '[sabri_platform_module key="clinic"]';
			if ( $owned || has_shortcode( $page->post_content, self::shortcode_tag( $shortcode ) ) || $placeholder ) {
				self::snapshot_page( $page->ID, false );
				$result = wp_update_post(
					array(
						'ID'           => $page->ID,
						'post_title'   => $title,
						'post_content' => $shortcode,
						'post_status'  => 'publish',
					),
					true
				);
				if ( is_wp_error( $result ) ) {
					throw new RuntimeException( $result->get_error_message() );
				}
				$owned_write = SWC_Helpers::update_meta_strict( $page->ID, '_swc_managed_page', '1', 'swc_managed_page_write' );
				if ( is_wp_error( $owned_write ) ) { throw new RuntimeException( 'File 08 page ownership marker could not be persisted.' ); }
				return $page->ID;
			}
		}

		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $existing instanceof WP_Post && 'trash' !== $existing->post_status ) {
			$owned = '1' === get_post_meta( $existing->ID, '_swc_managed_page', true );
			if ( $owned && has_shortcode( $existing->post_content, self::shortcode_tag( $shortcode ) ) ) {
				return $existing->ID;
			}
			// Never overwrite or silently map an unrelated same-slug page.
			$slug .= '-file-08';
		}

		$new_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $shortcode,
				'post_status'  => 'publish',
				'post_type'    => 'page',
			),
			true
		);
		if ( is_wp_error( $new_id ) ) {
			throw new RuntimeException( $new_id->get_error_message() );
		}
		$owned_write = SWC_Helpers::update_meta_strict( $new_id, '_swc_managed_page', '1', 'swc_managed_page_write' );
		if ( is_wp_error( $owned_write ) ) { wp_delete_post( $new_id, true ); throw new RuntimeException( 'File 08 page ownership marker could not be persisted.' ); }
		self::snapshot_page( $new_id, true );
		return absint( $new_id );
	}

	private static function shortcode_tag( $shortcode ) {
		return trim( strtok( trim( $shortcode, '[]' ), ' ' ) );
	}

	private static function create_activation_snapshot() {
		// Every activation/deployment attempt gets a fresh immediate pre-change snapshot.
		$snapshot = array(
			'created_at' => current_time( 'mysql', true ),
			'swc_map'    => (array) get_option( 'swc_page_map', array() ),
			'spf_map'    => (array) get_option( 'spf_page_map', array() ),
			'pages'      => array(),
		);
		$written = SWC_Helpers::update_option_strict( 'swc_activation_snapshot', $snapshot, 'swc_activation_snapshot_write' );
		if ( is_wp_error( $written ) ) {
			throw new RuntimeException( 'File 08 activation rollback snapshot could not be persisted.' );
		}
	}

	private static function snapshot_page( $id, $created ) {
		$snapshot = (array) get_option( 'swc_activation_snapshot', array() );
		if ( isset( $snapshot['pages'][ $id ] ) ) {
			return;
		}
		$page = get_post( absint( $id ) );
		$snapshot['pages'][ $id ] = array(
			'created'      => (bool) $created,
			'post_title'   => $page instanceof WP_Post ? $page->post_title : '',
			'post_name'    => $page instanceof WP_Post ? $page->post_name : '',
			'post_content' => $page instanceof WP_Post ? $page->post_content : '',
			'post_status'  => $page instanceof WP_Post ? $page->post_status : '',
			'managed_meta' => $page instanceof WP_Post ? get_post_meta( $page->ID, '_swc_managed_page', true ) : '',
		);
		$written = SWC_Helpers::update_option_strict( 'swc_activation_snapshot', $snapshot, 'swc_page_snapshot_write' );
		if ( is_wp_error( $written ) ) {
			if ( $created ) { wp_delete_post( absint( $id ), true ); }
			throw new RuntimeException( 'File 08 page rollback snapshot could not be persisted.' );
		}
	}

	public static function rollback_pages() {
		$snapshot = (array) get_option( 'swc_activation_snapshot', array() );
		if ( empty( $snapshot ) ) { return true; }
		foreach ( (array) ( $snapshot['pages'] ?? array() ) as $id => $original ) {
			$page = get_post( absint( $id ) );
			if ( ! $page instanceof WP_Post ) { continue; }
			$still_owned = '1' === get_post_meta( $page->ID, '_swc_managed_page', true ) && (bool) preg_match( '/^\s*\[swc_[a-z0-9_]+\]\s*$/', (string) $page->post_content );
			if ( ! $still_owned ) { continue; }
			if ( ! empty( $original['created'] ) ) {
				$updated = wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'draft' ), true );
				if ( is_wp_error( $updated ) || 'draft' !== get_post_status( $page->ID ) ) { return new WP_Error( 'swc_rollback_created_page', __( 'A newly created File 08 page could not be disabled during rollback.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				continue;
			}
			$updated = wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_title'   => $original['post_title'],
					'post_name'    => $original['post_name'],
					'post_content' => $original['post_content'],
					'post_status'  => $original['post_status'],
				),
				true
			);
			if ( is_wp_error( $updated ) ) { return new WP_Error( 'swc_rollback_page_write', __( 'A File 08 page could not be restored during rollback.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			if ( '' === $original['managed_meta'] ) {
				$meta = SWC_Helpers::delete_meta_strict( $page->ID, '_swc_managed_page', 'swc_rollback_page_meta_delete' );
			} else {
				$meta = SWC_Helpers::update_meta_strict( $page->ID, '_swc_managed_page', $original['managed_meta'], 'swc_rollback_page_meta_write' );
			}
			if ( is_wp_error( $meta ) ) { return $meta; }
		}
		foreach ( array( 'swc_page_map' => (array) ( $snapshot['swc_map'] ?? array() ), 'spf_page_map' => (array) ( $snapshot['spf_map'] ?? array() ) ) as $option => $value ) {
			$written = SWC_Helpers::update_option_strict( $option, $value, 'swc_rollback_map_write' );
			if ( is_wp_error( $written ) ) { return $written; }
		}
		$deleted = SWC_Helpers::delete_option_strict( 'swc_activation_snapshot', 'swc_rollback_snapshot_delete' );
		return is_wp_error( $deleted ) ? $deleted : true;
	}

	private static function rollback_activation() {
		WCA_Outbox::unschedule();
		wp_clear_scheduled_hook( 'wca_daily_health_snapshot' );
		$rolled_back = self::rollback_pages();
		if ( is_wp_error( $rolled_back ) ) { WCA_Observability::log( 'error', 'activation_rollback_incomplete', array( 'code' => $rolled_back->get_error_code() ) ); }
		self::remove_capabilities();
		return $rolled_back;
	}

	public static function system_checks() {
		global $wpdb;
		$map = SWC_Helpers::pages();
		return array(
			__( 'Required dependencies', 'worldwide-clinic-appointments' ) => self::dependencies_ready(),
			__( 'Audit table', 'worldwide-clinic-appointments' )          => self::table_exists( $wpdb->prefix . 'swc_audit_log' ),
			__( 'Rate-limit table', 'worldwide-clinic-appointments' )     => self::table_exists( $wpdb->prefix . 'swc_rate_limits' ),
			__( 'Database version', 'worldwide-clinic-appointments' )     => self::DB_VERSION === (string) get_option( 'swc_db_version', '' ),
			__( 'Clinic page', 'worldwide-clinic-appointments' )          => ! empty( $map['clinic'] ),
			__( 'Request page', 'worldwide-clinic-appointments' )         => ! empty( $map['request'] ),
			__( 'Patient dashboard', 'worldwide-clinic-appointments' )    => ! empty( $map['patient'] ),
			__( 'Doctor dashboard', 'worldwide-clinic-appointments' )     => ! empty( $map['doctor'] ),
			__( 'Availability page', 'worldwide-clinic-appointments' )    => ! empty( $map['availability'] ),
		);
	}

	public static function purge_all_data() {
		global $wpdb;
		$allowed = WCA_Privacy::assert_purge_allowed( get_current_user_id() );
		if ( is_wp_error( $allowed ) ) { return $allowed; }
		WCA_Observability::log( 'warning', 'irreversible_purge_started', array( 'actor_user_id' => absint( get_current_user_id() ) ) );
		do {
			$wpdb->last_error = '';
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type=%s ORDER BY ID ASC LIMIT 200", SWC_Helpers::TYPE ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $ids || $wpdb->last_error ) { return new WP_Error( 'swc_purge_post_query', __( 'File 08 appointments could not be enumerated safely for purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			foreach ( (array) $ids as $id ) {
				if ( false === wp_delete_post( absint( $id ), true ) ) { return new WP_Error( 'swc_purge_post_delete', __( 'A File 08 appointment could not be deleted during the guarded purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			}
		} while ( 200 === count( $ids ) );
		$canonical = WCA_Schema::purge_canonical_data();
		if ( is_wp_error( $canonical ) ) { WCA_Observability::log( 'critical', 'irreversible_purge_partial_failure', array( 'scope' => 'canonical', 'code' => $canonical->get_error_code() ) ); return $canonical; }
		$continuity = WCA_Continuity::purge_owned_data();
		if ( is_wp_error( $continuity ) ) { WCA_Observability::log( 'critical', 'irreversible_purge_partial_failure', array( 'scope' => 'continuity', 'code' => $continuity->get_error_code() ) ); return $continuity; }
		$future24 = WCA_Future24::purge_owned_data();
		if ( is_wp_error( $future24 ) ) { WCA_Observability::log( 'critical', 'irreversible_purge_partial_failure', array( 'scope' => 'future24', 'code' => $future24->get_error_code() ) ); return $future24; }
		if ( false === $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swc_audit_log" ) ) { return new WP_Error( 'swc_purge_audit_table', __( 'The File 08 audit table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swc_rate_limits" ) ) { return new WP_Error( 'swc_purge_rate_table', __( 'The File 08 rate-limit table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$like = $wpdb->esc_like( '_swc_' ) . '%';
		if ( false === $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $like ) ) ) { return new WP_Error( 'swc_purge_usermeta', __( 'File 08 user metadata could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$options = array( 'swc_page_map', 'swc_version', 'swc_db_version', 'swc_clinic_phone', 'swc_clinic_whatsapp', 'swc_emergency_notice', 'swc_activation_snapshot', 'swc_legacy_record_migration_cursor', WCA_Compatibility::MIGRATION_OPTION, 'swc_last_audit_error', 'swc_last_delivery_error', 'wca_runtime_migration_failure' );
		foreach ( $options as $option ) {
			$deleted = SWC_Helpers::delete_option_strict( $option, 'swc_purge_option_delete' );
			if ( is_wp_error( $deleted ) ) { return $deleted; }
		}
		self::remove_capabilities();
		WCA_Observability::log( 'warning', 'irreversible_purge_completed', array( 'actor_user_id' => absint( get_current_user_id() ) ) );
		return true;
	}

}
