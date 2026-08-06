<?php
/**
 * Installation, schema migration, page ownership, repair, and rollback.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Activator {
	const DB_VERSION = '3.0.0';

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

		self::create_activation_snapshot();
		try {
			self::add_capabilities();
			self::register_type();
			self::install_schema();
			WCA_Schema::install();
			WCA_Routes::register();
			WCA_Outbox::schedule();
			self::repair_pages();
			self::migrate_existing_records();
			update_option( 'swc_version', SWC_VERSION, false );
			update_option( 'swc_db_version', self::DB_VERSION, false );
			set_transient( 'swc_activation_notice', '1', 120 );
			flush_rewrite_rules();
		} catch ( Throwable $e ) {
			self::rollback_activation();
			deactivate_plugins( plugin_basename( SWC_FILE ) );
			wp_die(
				esc_html( sprintf( __( 'Worldwide Clinic activation was rolled back: %s', 'worldwide-clinic-appointments' ), $e->getMessage() ) ),
				'',
				array( 'back_link' => true )
			);
		}
	}

	public static function deactivate() {
		WCA_Outbox::unschedule();
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
			update_option( 'swc_db_version', self::DB_VERSION, false );
		}
		if ( SWC_VERSION !== (string) get_option( 'swc_version', '' ) ) {
			update_option( 'swc_version', SWC_VERSION, false );
		}
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
		$ids = get_posts(
			array(
				'post_type'      => SWC_Helpers::TYPE,
				'post_status'    => array( 'publish', 'private', 'draft' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);
		foreach ( $ids as $id ) {
			if ( 'private' !== get_post_status( $id ) ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'private' ) );
			}
			if ( '' === get_post_meta( $id, '_swc_patient_user_id', true ) ) {
				update_post_meta( $id, '_swc_patient_user_id', absint( get_post_field( 'post_author', $id ) ) );
			}
			if ( ! get_post_meta( $id, '_swc_record_version', true ) ) {
				update_post_meta( $id, '_swc_record_version', 1 );
			}
			$legacy_note = get_post_meta( $id, '_swc_doctor_note', true );
			if ( '' !== $legacy_note && '' === get_post_meta( $id, '_swc_doctor_private_note', true ) ) {
				update_post_meta( $id, '_swc_doctor_private_note', $legacy_note );
				delete_post_meta( $id, '_swc_doctor_note' );
			}
			if ( '' === get_post_meta( $id, '_swc_appointment_duration', true ) ) {
				$doctor = absint( get_post_meta( $id, '_swc_doctor_id', true ) );
				update_post_meta( $id, '_swc_appointment_duration', min( 180, max( 10, absint( SWC_Helpers::doctor_meta( $doctor, 'duration', 30 ) ) ) ) );
			}
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

		update_option( 'swc_page_map', $map, false );
		$spf['clinic'] = $map['clinic'];
		update_option( 'spf_page_map', $spf, false );
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
				update_post_meta( $page->ID, '_swc_managed_page', '1' );
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
		update_post_meta( $new_id, '_swc_managed_page', '1' );
		self::snapshot_page( $new_id, true );
		return absint( $new_id );
	}

	private static function shortcode_tag( $shortcode ) {
		return trim( strtok( trim( $shortcode, '[]' ), ' ' ) );
	}

	private static function create_activation_snapshot() {
		if ( get_option( 'swc_activation_snapshot', false ) ) {
			return;
		}
		update_option(
			'swc_activation_snapshot',
			array(
				'created_at' => current_time( 'mysql', true ),
				'swc_map'    => (array) get_option( 'swc_page_map', array() ),
				'spf_map'    => (array) get_option( 'spf_page_map', array() ),
				'pages'      => array(),
			),
			false
		);
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
		update_option( 'swc_activation_snapshot', $snapshot, false );
	}

	public static function rollback_pages() {
		$snapshot = (array) get_option( 'swc_activation_snapshot', array() );
		if ( empty( $snapshot ) ) {
			return;
		}
		foreach ( (array) ( $snapshot['pages'] ?? array() ) as $id => $original ) {
			$page = get_post( absint( $id ) );
			if ( ! $page instanceof WP_Post ) {
				continue;
			}
			$still_owned = '1' === get_post_meta( $page->ID, '_swc_managed_page', true ) && (bool) preg_match( '/^\s*\[swc_[a-z0-9_]+\]\s*$/', (string) $page->post_content );
			if ( ! $still_owned ) {
				// Never overwrite an administrator's post-activation page edits.
				continue;
			}
			if ( ! empty( $original['created'] ) ) {
				wp_update_post( array( 'ID' => $page->ID, 'post_status' => 'draft' ) );
				continue;
			}
			wp_update_post(
				array(
					'ID'           => $page->ID,
					'post_title'   => $original['post_title'],
					'post_name'    => $original['post_name'],
					'post_content' => $original['post_content'],
					'post_status'  => $original['post_status'],
				)
			);
			if ( '' === $original['managed_meta'] ) {
				delete_post_meta( $page->ID, '_swc_managed_page' );
			} else {
				update_post_meta( $page->ID, '_swc_managed_page', $original['managed_meta'] );
			}
		}
		update_option( 'swc_page_map', (array) ( $snapshot['swc_map'] ?? array() ), false );
		update_option( 'spf_page_map', (array) ( $snapshot['spf_map'] ?? array() ), false );
		delete_option( 'swc_activation_snapshot' );
	}

	private static function rollback_activation() {
		self::rollback_pages();
		self::remove_capabilities();
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
		$ids = get_posts(
			array(
				'post_type'      => SWC_Helpers::TYPE,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		foreach ( $ids as $id ) {
			wp_delete_post( $id, true );
		}
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swc_audit_log" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}swc_rate_limits" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$like = $wpdb->esc_like( '_swc_' ) . '%';
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s", $like ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( array( 'swc_page_map', 'swc_version', 'swc_db_version', 'swc_clinic_phone', 'swc_clinic_whatsapp', 'swc_emergency_notice', 'swc_activation_snapshot', 'swc_last_audit_error', 'swc_last_delivery_error' ) as $option ) {
			delete_option( $option );
		}
		self::remove_capabilities();
	}
}
