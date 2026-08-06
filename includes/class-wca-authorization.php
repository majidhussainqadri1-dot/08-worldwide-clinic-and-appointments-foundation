<?php
/**
 * Versioned identity claims and object/field/state authorization.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Authorization {
	/** @return array<string,mixed>|WP_Error */
	public static function claims( $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return new WP_Error( 'wca_auth_required', __( 'Authentication is required.', 'worldwide-clinic-appointments' ), array( 'status' => 401 ) );
		}

		$status = function_exists( 'smc_user_status' ) ? (string) smc_user_status( $user_id ) : 'unknown';
		$founder = function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );
		$eligible = $founder || 'approved' === $status;
		$suspended = in_array( $status, array( 'suspended', 'revoked', 'rejected', 'expired', 'blocked' ), true );
		$doctor = class_exists( 'SWC_Doctor_Authority' ) ? SWC_Doctor_Authority::is_eligible( $user_id ) : false;

		$claims = array(
			'contract'      => 'wca.identity-claims',
			'version'       => '1.0.0',
			'user_id'       => $user_id,
			'subject_uuid'  => self::subject_uuid( $user_id ),
			'approved'      => $eligible && ! $suspended,
			'founder'       => (bool) $founder,
			'doctor'        => (bool) $doctor,
			'suspended'     => (bool) $suspended,
			'guardian'      => self::is_guardian( $user_id ),
			'capabilities'  => self::capabilities( $user_id ),
			'issued_at_utc' => gmdate( 'c' ),
			'expires_at_utc'=> gmdate( 'c', time() + 300 ),
		);
		$claims = apply_filters( 'wca_identity_claims', $claims, $user_id );
		if ( empty( $claims['approved'] ) || ! empty( $claims['suspended'] ) ) {
			return new WP_Error( 'wca_account_ineligible', __( 'The account is not eligible for this protected action.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		return $claims;
	}

	public static function subject_uuid( $user_id ) {
		$user_id = absint( $user_id );
		if ( function_exists( 'smc_get_subject_uuid' ) ) {
			$value = smc_get_subject_uuid( $user_id );
			if ( is_string( $value ) && preg_match( '/^[0-9a-f-]{36}$/i', $value ) ) {
				return strtolower( $value );
			}
		}
		$value = (string) get_user_meta( $user_id, '_smc_subject_uuid', true );
		return preg_match( '/^[0-9a-f-]{36}$/i', $value ) ? strtolower( $value ) : '';
	}

	/** @return array<int,string> */
	private static function capabilities( $user_id ) {
		$caps = array();
		foreach ( array( 'manage_worldwide_clinic', 'manage_wca_clinics', 'manage_wca_complaints', 'manage_wca_operations', 'read', 'upload_files' ) as $cap ) {
			if ( user_can( $user_id, $cap ) ) {
				$caps[] = $cap;
			}
		}
		return $caps;
	}

	public static function is_guardian( $user_id ) {
		if ( function_exists( 'smc_user_is_verified_guardian' ) ) {
			return (bool) smc_user_is_verified_guardian( absint( $user_id ) );
		}
		return (bool) apply_filters( 'wca_user_is_verified_guardian', false, absint( $user_id ) );
	}

	/** @return true|WP_Error */
	public static function require_step_up( $purpose, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( function_exists( 'smc_step_up_is_valid' ) && smc_step_up_is_valid( $user_id, sanitize_key( $purpose ) ) ) {
			return true;
		}
		if ( (bool) apply_filters( 'wca_step_up_is_valid', false, $user_id, sanitize_key( $purpose ) ) ) {
			return true;
		}
		return new WP_Error( 'wca_step_up_required', __( 'A recent security verification is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403, 'purpose' => sanitize_key( $purpose ) ) );
	}

	/** @return true|WP_Error */
	public static function can_create_clinic( $user_id = 0 ) {
		$claims = self::claims( $user_id );
		if ( is_wp_error( $claims ) ) {
			return $claims;
		}
		return ! empty( $claims['doctor'] ) || ! empty( $claims['founder'] ) ? true : new WP_Error( 'wca_doctor_required', __( 'Verified doctor authority is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public static function can_manage_clinic( $clinic, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		$claims = self::claims( $user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) || user_can( $user_id, 'manage_wca_clinics' ) ) { return true; }
		if ( absint( $clinic['owner_user_id'] ?? 0 ) === $user_id && ( ! empty( $claims['doctor'] ) || ! empty( $claims['founder'] ) ) ) { return true; }
		$delegated = (array) get_user_meta( $user_id, '_wca_clinic_delegations', true );
		$clinic_id = absint( $clinic['id'] ?? 0 );
		if ( isset( $delegated[ $clinic_id ] ) && is_array( $delegated[ $clinic_id ] ) && ! empty( $delegated[ $clinic_id ]['active'] ) ) { return true; }
		return new WP_Error( 'wca_clinic_forbidden', __( 'You cannot manage this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public static function can_view_appointment( $appointment_id, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		$claims = self::claims( $user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return true; }
		if ( SWC_Helpers::can_patient_manage( $appointment_id, $user_id ) || SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return true; }
		$guardian_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
		if ( $guardian_id === $user_id && ! empty( $claims['guardian'] ) ) { return true; }
		return new WP_Error( 'wca_appointment_forbidden', __( 'You cannot access this appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
	}

	/** @return true|WP_Error */
	public static function can_transition_appointment( $appointment_id, $next, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		$view = self::can_view_appointment( $appointment_id, $user_id );
		if ( is_wp_error( $view ) ) { return $view; }
		$actor = self::appointment_actor( $appointment_id, $user_id );
		$current = SWC_Helpers::status( $appointment_id );
		return WCA_Contracts::can_transition( $actor, $current, $next ) ? true : new WP_Error( 'wca_transition_forbidden', __( 'That appointment transition is not allowed.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'from' => $current, 'to' => WCA_Contracts::normalize_appointment_status( $next ) ) );
	}

	public static function appointment_actor( $appointment_id, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return 'admin'; }
		if ( SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return 'doctor'; }
		if ( absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) ) === $user_id ) { return 'guardian'; }
		return 'patient';
	}

	/** @return true|WP_Error */
	public static function guardian_context( $patient_user_id, $guardian_user_id ) {
		$patient_user_id  = absint( $patient_user_id );
		$guardian_user_id = absint( $guardian_user_id );
		if ( ! $guardian_user_id ) {
			return true;
		}
		if ( ! self::is_guardian( $guardian_user_id ) ) {
			return new WP_Error( 'wca_guardian_unverified', __( 'Verified guardian authority is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$allowed = (bool) apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );
		if ( function_exists( 'smc_guardian_may_act_for' ) ) {
			$allowed = (bool) smc_guardian_may_act_for( $guardian_user_id, $patient_user_id );
		}
		return $allowed ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}
}
