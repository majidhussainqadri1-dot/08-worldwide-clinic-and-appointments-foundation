<?php
/**
 * Privacy lifecycle for File 08 canonical stores.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Privacy {
	const RETENTION_OPTION = 'wca_retention_policy';
	const ERASE_BATCH = 100;
	const CURSOR_TTL = HOUR_IN_SECONDS;

	public static function hooks() {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_policy' ) );
	}

	public static function register_policy() {
		if ( ! get_option( self::RETENTION_OPTION ) ) {
			add_option( self::RETENTION_OPTION, array(
				'completed_appointments_days' => 2555,
				'cancelled_appointments_days' => 730,
				'events_days'                 => 2555,
				'outbox_delivered_days'       => 30,
				'idempotency_days'            => 7,
				'metrics_days'                => 395,
				'future24_operational_days'   => 395,
				'legal_hold_respected'        => true,
			), '', false );
		}
	}

	public static function register_exporter( $exporters ) {
		$exporters['wca-file-08'] = array(
			'exporter_friendly_name' => __( 'Worldwide Clinic and Appointments', 'worldwide-clinic-appointments' ),
			'callback'               => array( __CLASS__, 'export' ),
		);
		return $exporters;
	}

	public static function register_eraser( $erasers ) {
		$erasers['wca-file-08'] = array(
			'eraser_friendly_name' => __( 'Worldwide Clinic and Appointments', 'worldwide-clinic-appointments' ),
			'callback'             => array( __CLASS__, 'erase' ),
		);
		return $erasers;
	}

	public static function export( $email, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', sanitize_email( $email ) );
		if ( ! $user ) { return array( 'data' => array(), 'done' => true ); }
		$page = max( 1, absint( $page ) );
		$data = array();

		$appointments = get_posts( array(
			'post_type'      => SWC_Helpers::TYPE,
			'post_status'    => array( 'private', 'publish', 'draft' ),
			'posts_per_page' => 50,
			'paged'          => $page,
			'fields'         => 'ids',
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array( 'relation' => 'OR',
				array( 'key' => '_swc_patient_user_id', 'value' => $user->ID ),
				array( 'key' => '_swc_doctor_id', 'value' => $user->ID ),
				array( 'key' => '_swc_guardian_user_id', 'value' => $user->ID ),
			),
		) );
		foreach ( $appointments as $appointment_id ) {
			$fields = array();
			foreach ( array( 'public_ref','status','preferred_at_utc','appointment_end_utc','patient_timezone','consultation_type','clinic_id','service_id','created_via','consent_version','consent_at','checked_in_at_utc','completed_at_utc' ) as $key ) {
				$value = SWC_Helpers::meta( $appointment_id, $key );
				if ( '' !== (string) $value ) { $fields[] = array( 'name' => $key, 'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) ); }
			}
			$data[] = array( 'group_id' => 'wca-appointments', 'group_label' => __( 'Clinic appointments', 'worldwide-clinic-appointments' ), 'item_id' => 'appointment-' . $appointment_id, 'data' => $fields );
		}

		$future_rows = array();
		$table = self::future24_table();
		if ( $table ) {
			$offset = ( $page - 1 ) * 50;
			$future_rows = (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT id,public_ref,feature_id,status,appointment_id,clinic_id,parent_ref,starts_at,ends_at,expires_at,created_at,updated_at,payload_json
					 FROM {$table}
					 WHERE actor_user_id=%d OR subject_user_id=%d
					 ORDER BY id ASC LIMIT 50 OFFSET %d",
					absint( $user->ID ),
					absint( $user->ID ),
					$offset
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $future_rows as $row ) {
				$fields = array();
				foreach ( array( 'public_ref','feature_id','status','parent_ref','starts_at','ends_at','expires_at','created_at','updated_at' ) as $key ) {
					if ( '' !== (string) ( $row[ $key ] ?? '' ) ) { $fields[] = array( 'name' => $key, 'value' => (string) $row[ $key ] ); }
				}
				$data[] = array( 'group_id' => 'wca-future24', 'group_label' => __( 'Clinic scheduling and appointment intelligence records', 'worldwide-clinic-appointments' ), 'item_id' => 'future24-' . absint( $row['id'] ), 'data' => $fields );
			}
		}
		return array( 'data' => $data, 'done' => count( $appointments ) < 50 && count( $future_rows ) < 50 );
	}

	public static function erase( $email, $page = 1 ) {
		global $wpdb;
		$user = get_user_by( 'email', sanitize_email( $email ) );
		if ( ! $user ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true ); }
		$user_id = absint( $user->ID );
		$page = max( 1, absint( $page ) );
		$base = 'wca_core_erase_' . substr( hash( 'sha256', strtolower( sanitize_email( $email ) ) ), 0, 24 );
		if ( 1 === $page ) {
			delete_transient( $base . '_appointments' );
			delete_transient( $base . '_future24' );
		}

		$removed = false;
		$retained = false;
		$messages = array();
		$done = true;

		$cursor_key = $base . '_appointments';
		$cursor = absint( get_transient( $cursor_key ) );
		$ids = self::appointment_ids_after( $user_id, $cursor, self::ERASE_BATCH );
		$last_id = $cursor;
		foreach ( $ids as $id ) {
			$last_id = max( $last_id, $id );
			if ( self::legal_hold( $id ) ) {
				$retained = true;
				continue;
			}
			foreach ( array( 'reason','patient_message','phone','whatsapp','country','city','doctor_private_note','transition_reason_code' ) as $key ) { delete_post_meta( $id, '_swc_' . $key ); }
			update_post_meta( $id, '_swc_privacy_erased_at', WCA_Repository::now() );
			if ( absint( SWC_Helpers::meta( $id, 'patient_user_id', 0 ) ) === $user_id ) { update_post_meta( $id, '_swc_patient_user_id', 0 ); }
			if ( absint( SWC_Helpers::meta( $id, 'guardian_user_id', 0 ) ) === $user_id ) { update_post_meta( $id, '_swc_guardian_user_id', 0 ); }
			if ( absint( SWC_Helpers::meta( $id, 'doctor_id', 0 ) ) === $user_id ) { update_post_meta( $id, '_swc_doctor_id', 0 ); }
			if ( absint( get_post_field( 'post_author', $id ) ) === $user_id ) { wp_update_post( array( 'ID' => $id, 'post_author' => 0 ) ); }
			$removed = true;
		}
		if ( $last_id > $cursor ) { set_transient( $cursor_key, $last_id, self::CURSOR_TTL ); }
		if ( self::appointment_ids_after( $user_id, $last_id, 1 ) ) { $done = false; } else { delete_transient( $cursor_key ); }

		$table = self::future24_table();
		if ( $table ) {
			$cursor_key = $base . '_future24';
			$cursor = absint( get_transient( $cursor_key ) );
			$rows = (array) $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT %d",
					$user_id, $user_id, $cursor, self::ERASE_BATCH
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$last = $cursor;
			$subject_uuid = strtolower( sanitize_text_field( (string) get_user_meta( $user_id, '_smc_subject_uuid', true ) ) );
			foreach ( $rows as $row ) {
				$last = max( $last, absint( $row['id'] ) );
				if ( self::future24_legal_hold( $row ) ) { $retained = true; continue; }
				$payload = json_decode( (string) $row['payload_json'], true );
				$payload = is_array( $payload ) ? self::scrub_future24_payload( $payload, $subject_uuid ) : array();
				$updated = $wpdb->update(
					$table,
					array(
						'actor_user_id' => absint( $row['actor_user_id'] ) === $user_id ? 0 : absint( $row['actor_user_id'] ),
						'subject_user_id' => absint( $row['subject_user_id'] ) === $user_id ? 0 : absint( $row['subject_user_id'] ),
						'payload_json' => wp_json_encode( $payload, JSON_UNESCAPED_SLASHES ),
						'updated_at' => WCA_Repository::now(),
					),
					array( 'id' => absint( $row['id'] ) ),
					array( '%d','%d','%s','%s' ),
					array( '%d' )
				);
				if ( false !== $updated ) { $removed = true; }
			}
			if ( $last > $cursor ) { set_transient( $cursor_key, $last, self::CURSOR_TTL ); }
			$more = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT 1", $user_id, $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }
		}

		if ( $retained ) { $messages[] = __( 'Some clinic records are retained under an active legal, safety, or professional-record hold.', 'worldwide-clinic-appointments' ); }
		return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => array_unique( $messages ), 'done' => $done );
	}

	private static function appointment_ids_after( $user_id, $cursor, $limit ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$cursor = absint( $cursor );
		$limit = min( 500, max( 1, absint( $limit ) ) );
		$sql = $wpdb->prepare(
			"SELECT DISTINCT p.ID
			 FROM {$wpdb->posts} p
			 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID
			 WHERE p.post_type=%s
			   AND p.post_status IN ('private','publish','draft')
			   AND p.ID>%d
			   AND ((pm.meta_key='_swc_patient_user_id' AND pm.meta_value=%d) OR (pm.meta_key='_swc_guardian_user_id' AND pm.meta_value=%d) OR (pm.meta_key='_swc_doctor_id' AND pm.meta_value=%d))
			 ORDER BY p.ID ASC LIMIT %d",
			SWC_Helpers::TYPE,
			$cursor,
			$user_id,
			$user_id,
			$user_id,
			$limit
		);
		return array_map( 'absint', (array) $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	public static function legal_hold( $appointment_id ) {
		return (bool) apply_filters( 'wca_appointment_legal_hold', (bool) get_post_meta( $appointment_id, '_swc_legal_hold', true ), absint( $appointment_id ) );
	}

	public static function future24_legal_hold( $row ) {
		$row = is_array( $row ) ? $row : array();
		$default = ! empty( $row['appointment_id'] ) && self::legal_hold( absint( $row['appointment_id'] ) );
		return (bool) apply_filters( 'wca_future24_legal_hold', $default, $row );
	}

	private static function future24_table() {
		global $wpdb;
		if ( ! class_exists( 'WCA_Future24' ) ) { return ''; }
		$table = $wpdb->prefix . 'wca_future24_records';
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		return $exists === $table ? $table : '';
	}

	private static function scrub_future24_payload( $value, $subject_uuid ) {
		if ( ! is_array( $value ) ) { return $value; }
		$out = array();
		foreach ( $value as $key => $item ) {
			$key_string = is_string( $key ) ? sanitize_key( $key ) : $key;
			if ( is_string( $key_string ) && in_array( $key_string, array( 'subject_uuid','patient_user_id','guardian_user_id','recipient_user_id' ), true ) ) { continue; }
			if ( is_array( $item ) ) { $out[ $key ] = self::scrub_future24_payload( $item, $subject_uuid ); continue; }
			if ( $subject_uuid && is_string( $item ) && hash_equals( $subject_uuid, strtolower( sanitize_text_field( $item ) ) ) ) { continue; }
			$out[ $key ] = $item;
		}
		return $out;
	}

	public static function apply_retention() {
		global $wpdb;
		$policy = wp_parse_args( (array) get_option( self::RETENTION_OPTION, array() ), array( 'outbox_delivered_days' => 30, 'idempotency_days' => 7, 'metrics_days' => 395, 'future24_operational_days' => 395 ) );
		$tables = WCA_Schema::tables();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['outbox']} WHERE status='delivered' AND delivered_at < %s", gmdate( 'Y-m-d H:i:s', time() - absint( $policy['outbox_delivered_days'] ) * DAY_IN_SECONDS ) ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['idempotency']} WHERE expires_at < %s", WCA_Repository::now() ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['metrics']} WHERE metric_bucket < %s", gmdate( 'Y-m-d H:i:s', time() - absint( $policy['metrics_days'] ) * DAY_IN_SECONDS ) ) );

		$table = self::future24_table();
		if ( $table ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, absint( $policy['future24_operational_days'] ) ) * DAY_IN_SECONDS );
			$cursor = 0;
			$batch = 250;
			do {
				$rows = (array) $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM {$table} WHERE expires_at IS NOT NULL AND expires_at<%s AND updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d", WCA_Repository::now(), $cutoff, $cursor, $batch ),
					ARRAY_A
				); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				foreach ( $rows as $row ) {
					$cursor = max( $cursor, absint( $row['id'] ) );
					if ( self::future24_legal_hold( $row ) ) { continue; }
					$wpdb->delete( $table, array( 'id' => absint( $row['id'] ) ), array( '%d' ) );
				}
			} while ( count( $rows ) === $batch );
		}
	}
}
