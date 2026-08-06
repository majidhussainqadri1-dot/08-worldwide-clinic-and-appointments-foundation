<?php
/**
 * Privacy lifecycle for File 08 canonical stores.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Privacy {
	const RETENTION_OPTION = 'wca_retention_policy';

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
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array( 'data' => array(), 'done' => true );
		}
		$data = array();
		$appointments = get_posts( array(
			'post_type'      => SWC_Helpers::TYPE,
			'post_status'    => array( 'private', 'publish', 'draft' ),
			'posts_per_page' => 50,
			'paged'          => max( 1, absint( $page ) ),
			'fields'         => 'ids',
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
		return array( 'data' => $data, 'done' => count( $appointments ) < 50 );
	}

	public static function erase( $email, $page = 1 ) {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		$ids = get_posts( array(
			'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish','draft' ), 'posts_per_page' => 50,
			'paged' => max( 1, absint( $page ) ), 'fields' => 'ids',
			'meta_query' => array( 'relation' => 'OR', array( 'key' => '_swc_patient_user_id', 'value' => $user->ID ), array( 'key' => '_swc_guardian_user_id', 'value' => $user->ID ) ),
		) );
		$removed = false;
		$retained = false;
		$messages = array();
		foreach ( $ids as $id ) {
			if ( self::legal_hold( $id ) ) {
				$retained = true;
				$messages[] = __( 'An appointment was retained under a documented legal or safety hold.', 'worldwide-clinic-appointments' );
				continue;
			}
			foreach ( array( 'reason','patient_message','phone','whatsapp','country','city','doctor_private_note','transition_reason_code' ) as $key ) {
				delete_post_meta( $id, '_swc_' . $key );
			}
			update_post_meta( $id, '_swc_privacy_erased_at', WCA_Repository::now() );
			update_post_meta( $id, '_swc_patient_user_id', 0 );
			if ( absint( get_post_field( 'post_author', $id ) ) === absint( $user->ID ) ) { wp_update_post( array( 'ID' => $id, 'post_author' => 0 ) ); }
			$removed = true;
		}
		return array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => array_unique( $messages ), 'done' => count( $ids ) < 50 );
	}

	public static function legal_hold( $appointment_id ) {
		return (bool) apply_filters( 'wca_appointment_legal_hold', (bool) get_post_meta( $appointment_id, '_swc_legal_hold', true ), absint( $appointment_id ) );
	}

	public static function apply_retention() {
		global $wpdb;
		$policy = wp_parse_args( (array) get_option( self::RETENTION_OPTION, array() ), array( 'outbox_delivered_days' => 30, 'idempotency_days' => 7, 'metrics_days' => 395 ) );
		$tables = WCA_Schema::tables();
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['outbox']} WHERE status='delivered' AND delivered_at < %s", gmdate( 'Y-m-d H:i:s', time() - absint( $policy['outbox_delivered_days'] ) * DAY_IN_SECONDS ) ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['idempotency']} WHERE expires_at < %s", WCA_Repository::now() ) );
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$tables['metrics']} WHERE bucket_at < %s", gmdate( 'Y-m-d H:i:s', time() - absint( $policy['metrics_days'] ) * DAY_IN_SECONDS ) ) );
	}
}
