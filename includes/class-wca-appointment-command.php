<?php
/**
 * Governing appointment-request command boundary.
 *
 * This is the canonical HTTP/browser command for new appointments. It adds
 * server-side evidence checks that must never be inferred from checked boxes
 * or client JavaScript alone, then delegates the atomic appointment/slot work
 * to the existing File 08 service.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Appointment_Command {
	const CONTRACT_VERSION = '1.0.1';

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_route' ), 60 );
	}

	public static function register_route() {
		register_rest_route(
			'wca/v1',
			'/appointments',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'rest_request' ),
				'permission_callback' => array( 'WCA_REST', 'authenticated' ),
			),
			true
		);
	}

	public static function rest_request( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		if ( SWC_Helpers::rate_limit_hit( 'appointment_request_governed', $user_id, 20, HOUR_IN_SECONDS ) ) {
			return new WP_Error( 'wca_rate_limit', __( 'Too many appointment requests. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => HOUR_IN_SECONDS ) );
		}
		$data = $request->get_json_params();
		$data = is_array( $data ) ? $data : $request->get_params();
		return self::request( $data, $user_id );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function request( $data, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$data = is_array( $data ) ? $data : array();
		if ( ! self::affirmative( isset( $data['privacy_consent'] ) ? $data['privacy_consent'] : null ) ) {
			return new WP_Error( 'wca_privacy_consent_required', __( 'Current appointment-processing and privacy consent is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		if ( ! self::affirmative( isset( $data['emergency_acknowledged'] ) ? $data['emergency_acknowledged'] : null ) ) {
			return new WP_Error( 'wca_emergency_ack_required', __( 'You must acknowledge that this booking service is not emergency care.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$hold_token = sanitize_text_field( isset( $data['hold_token'] ) ? $data['hold_token'] : '' );
		$hold = $hold_token ? WCA_Repository::get_slot_hold( $hold_token ) : null;
		if ( ! $hold ) {
			return new WP_Error( 'wca_hold_missing', __( 'The selected appointment hold is unavailable or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$service = ! empty( $hold['service_id'] ) ? WCA_Repository::get_service( absint( $hold['service_id'] ), true ) : null;
		$type = sanitize_key( $service && isset( $service['consultation_type'] ) ? $service['consultation_type'] : '' );
		$remote = in_array( $type, array( 'online', 'hybrid' ), true );
		if ( $remote && ! self::affirmative( isset( $data['telehealth_consent'] ) ? $data['telehealth_consent'] : null ) ) {
			return new WP_Error( 'wca_teleconsult_consent_required', __( 'Explicit remote-consultation consent is required for the selected online or hybrid service.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$data['privacy_consent']        = true;
		$data['emergency_acknowledged'] = true;
		$data['telehealth_consent']     = $remote ? true : false;
		$result = WCA_Service::request_appointment( $data, $actor_user_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$result['command_contract'] = 'wca.appointment-request/' . self::CONTRACT_VERSION;
		$result['privacy_consent_verified'] = true;
		$result['emergency_ack_verified'] = true;
		$result['remote_consultation_consent_verified'] = $remote;
		$appointment_id = ! empty( $result['appointment_id'] ) ? absint( $result['appointment_id'] ) : 0;
		if ( $remote && $appointment_id ) {
			self::ensure_context_consent( $appointment_id, 'teleconsult', $actor_user_id );
		}
		if ( $appointment_id ) {
			self::ensure_context_consent( $appointment_id, 'privacy_notice', $actor_user_id );
		}
		// Public/cross-file command responses use the opaque appointment ref only.
		unset( $result['appointment_id'] );
		return $result;
	}

	private static function affirmative( $value ) {
		if ( true === $value || 1 === $value || '1' === $value ) {
			return true;
		}
		if ( is_string( $value ) ) {
			return in_array( strtolower( trim( $value ) ), array( 'true', 'yes', 'on' ), true );
		}
		return false;
	}

	private static function ensure_context_consent( $appointment_id, $scope, $actor_user_id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['consents'];
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL ORDER BY id DESC LIMIT 1",
				$appointment_id,
				$scope
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $exists ) { return; }
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return; }
		$guardian_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
		$record = WCA_Repository::record_consent( array(
			'appointment_id'     => $appointment_id,
			'actor_user_id'      => $actor_user_id,
			'actor_subject_uuid' => $claims['subject_uuid'],
			'guardian_user_id'   => $guardian_id,
			'scope'              => sanitize_key( $scope ),
			'terms_version'      => '2026-08-10.1',
			'terms_text'         => 'wca-context:' . sanitize_key( $scope ) . ':2026-08-10',
			'legal_basis'        => 'consent',
			'metadata'           => array( 'source' => 'governed_appointment_request', 'contract' => self::CONTRACT_VERSION ),
		) );
		if ( is_wp_error( $record ) ) {
			WCA_Observability::log( 'warning', 'context_consent_sync_pending', array( 'scope' => sanitize_key( $scope ), 'appointment_ref' => (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ) ) );
			WCA_Repository::enqueue( 'File24.AssuranceEvidenceRequested.v1', (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ), array( 'entity' => 'appointment_consent', 'entity_ref' => (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ), 'change' => 'consent_sync_pending', 'scope' => sanitize_key( $scope ) ), WCA_Observability::trace_id() );
		}
	}
}

/** Canonical PHP command helper for trusted File 08 callers. */
function wca_request_appointment_command( $data, $actor_user_id = 0 ) {
	return WCA_Appointment_Command::request( $data, $actor_user_id );
}
