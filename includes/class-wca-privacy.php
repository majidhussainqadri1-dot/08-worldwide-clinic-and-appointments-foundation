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
			$future_rows_raw = $wpdb->get_results(
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
			if ( null === $future_rows_raw && '' !== (string) $wpdb->last_error ) {
				return new WP_Error( 'wca_privacy_export_future24_query', __( 'Future24 privacy data could not be read safely for export.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
			}
			$future_rows = (array) $future_rows_raw;
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
		if ( is_wp_error( $ids ) ) { $messages[] = __( 'Appointment privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; $ids = array(); }
		$last_id = $cursor;
		foreach ( $ids as $id ) {
			if ( self::legal_hold( $id ) ) {
				$retained = true;
				$last_id = max( $last_id, $id );
				continue;
			}
			$erase_error = null;
			foreach ( array( 'reason','patient_message','phone','whatsapp','country','city','doctor_private_note','transition_reason_code' ) as $key ) {
				$deleted = SWC_Helpers::delete_meta_strict( $id, '_swc_' . $key, 'wca_privacy_meta_delete' );
				if ( is_wp_error( $deleted ) ) { $erase_error = $deleted; break; }
			}
			if ( ! $erase_error ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_privacy_erased_at', WCA_Repository::now(), 'wca_privacy_erased_marker' ); }
			if ( ! $erase_error && absint( SWC_Helpers::meta( $id, 'patient_user_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_patient_user_id', 0, 'wca_privacy_patient_anonymize' ); }
			if ( ! $erase_error && absint( SWC_Helpers::meta( $id, 'guardian_user_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_guardian_user_id', 0, 'wca_privacy_guardian_anonymize' ); }
			if ( ! $erase_error && absint( SWC_Helpers::meta( $id, 'doctor_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_doctor_id', 0, 'wca_privacy_doctor_anonymize' ); }
			if ( ! $erase_error && absint( get_post_field( 'post_author', $id ) ) === $user_id ) {
				$post_update = wp_update_post( array( 'ID' => $id, 'post_author' => 0 ), true );
				if ( is_wp_error( $post_update ) || ! $post_update || 0 !== absint( get_post_field( 'post_author', $id ) ) ) { $erase_error = new WP_Error( 'wca_privacy_author_anonymize', __( 'Appointment author identity could not be anonymized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			}
			if ( is_wp_error( $erase_error ) ) {
				$messages[] = __( 'Appointment privacy erasure encountered a storage failure and will retry without skipping the affected record.', 'worldwide-clinic-appointments' );
				$done = false;
				break;
			}
			$last_id = max( $last_id, $id );
			$removed = true;
		}
		if ( $last_id > $cursor ) { set_transient( $cursor_key, $last_id, self::CURSOR_TTL ); }
		$appointment_more = self::appointment_ids_after( $user_id, $last_id, 1 );
		if ( is_wp_error( $appointment_more ) ) { $messages[] = __( 'Appointment privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; } elseif ( $appointment_more ) { $done = false; } else { delete_transient( $cursor_key ); }

		$table = self::future24_table();
		if ( $table ) {
			$cursor_key = $base . '_future24';
			$cursor = absint( get_transient( $cursor_key ) );
			$rows_raw = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT %d",
					$user_id, $user_id, $cursor, self::ERASE_BATCH
				),
				ARRAY_A
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Future24 privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; $rows_raw = array(); }
			$rows = (array) $rows_raw;
			$last = $cursor;
			$subject_uuid = strtolower( sanitize_text_field( (string) get_user_meta( $user_id, '_smc_subject_uuid', true ) ) );
			foreach ( $rows as $row ) {
				$row_id = absint( $row['id'] );
				if ( self::future24_legal_hold( $row ) ) { $retained = true; $last = max( $last, $row_id ); continue; }
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
				if ( false === $updated ) {
					$messages[] = __( 'Future24 privacy erasure encountered a storage failure and will retry without skipping the affected record.', 'worldwide-clinic-appointments' );
					$done = false;
					break;
				}
				if ( 0 === (int) $updated ) {
					$current = $wpdb->get_row( $wpdb->prepare( "SELECT actor_user_id,subject_user_id FROM {$table} WHERE id=%d", $row_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					if ( null === $current && '' !== (string) $wpdb->last_error ) {
						$messages[] = __( 'Future24 privacy erasure could not verify a concurrent update safely.', 'worldwide-clinic-appointments' );
						$done = false;
						break;
					}
					if ( $current && ( absint( $current['actor_user_id'] ) === $user_id || absint( $current['subject_user_id'] ) === $user_id ) ) {
						$messages[] = __( 'Future24 privacy erasure did not remove the requested user linkage and will retry.', 'worldwide-clinic-appointments' );
						$done = false;
						break;
					}
				}
				$last = max( $last, $row_id );
				$removed = true;
			}
			if ( $last > $cursor ) { set_transient( $cursor_key, $last, self::CURSOR_TTL ); }
			$more = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT 1", $user_id, $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $more && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Future24 privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; } elseif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }
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
			SWC_Helpers::TYPE, $cursor, $user_id, $user_id, $user_id, $limit
		);
		$raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_privacy_appointment_read_failed', __( 'Appointment privacy records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		return array_map( 'absint', (array) $raw );
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
		$base_deletes = array(
			'outbox' => $wpdb->prepare( "DELETE FROM {$tables['outbox']} WHERE status='delivered' AND delivered_at < %s", gmdate( 'Y-m-d H:i:s', time() - absint( $policy['outbox_delivered_days'] ) * DAY_IN_SECONDS ) ),
			'idempotency' => $wpdb->prepare( "DELETE FROM {$tables['idempotency']} WHERE expires_at < %s", WCA_Repository::now() ),
			'metrics' => $wpdb->prepare( "DELETE FROM {$tables['metrics']} WHERE metric_bucket < %s", gmdate( 'Y-m-d H:i:s', time() - absint( $policy['metrics_days'] ) * DAY_IN_SECONDS ) ),
		);
		foreach ( $base_deletes as $scope => $statement ) {
			if ( false === $wpdb->query( $statement ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				WCA_Observability::log( 'error', 'retention_delete_failed', array( 'scope' => $scope ) );
				return new WP_Error( 'wca_retention_' . sanitize_key( $scope ), __( 'Retention maintenance could not complete safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
			}
		}

		$table = self::future24_table();
		if ( $table ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, absint( $policy['future24_operational_days'] ) ) * DAY_IN_SECONDS );
			$cursor = 0;
			$batch = 250;
			do {
				$rows_raw = $wpdb->get_results(
					$wpdb->prepare( "SELECT * FROM {$table} WHERE expires_at IS NOT NULL AND expires_at<%s AND updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d", WCA_Repository::now(), $cutoff, $cursor, $batch ),
					ARRAY_A
				); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_retention_future24_read_failed', __( 'Future24 retention cleanup could not read expired records safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				$rows = (array) $rows_raw;
				foreach ( $rows as $row ) {
					$row_id = absint( $row['id'] );
					if ( self::future24_legal_hold( $row ) ) { $cursor = max( $cursor, $row_id ); continue; }
					$deleted = $wpdb->delete( $table, array( 'id' => $row_id ), array( '%d' ) );
					if ( false === $deleted ) {
						WCA_Observability::log( 'error', 'future24_retention_delete_failed', array( 'record_id' => $row_id ) );
						return new WP_Error( 'wca_retention_future24', __( 'Future24 retention cleanup could not complete safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
					}
					$cursor = max( $cursor, $row_id );
				}
			} while ( count( $rows ) === $batch );
		}
		return true;
	}
}
