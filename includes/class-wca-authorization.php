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

		$status = 'unknown';
		if ( function_exists( 'smc_user_status' ) ) {
			try { $status_raw = smc_user_status( $user_id ); $status = is_scalar( $status_raw ) ? (string) $status_raw : 'unknown'; } catch ( Throwable $e ) { $status = 'unknown'; }
		}
		$founder = false;
		if ( function_exists( 'smc_is_founder' ) ) {
			try { $founder_raw = smc_is_founder( $user_id ); $founder = true === $founder_raw || 1 === $founder_raw || '1' === $founder_raw; } catch ( Throwable $e ) { $founder = false; }
		}
		$eligible  = $founder || 'approved' === $status;
		$suspended = in_array( $status, array( 'suspended', 'revoked', 'rejected', 'expired', 'blocked' ), true );
		$doctor    = class_exists( 'SWC_Doctor_Authority' ) ? SWC_Doctor_Authority::is_eligible( $user_id ) : false;
		$staff     = self::has_active_clinic_delegation( $user_id );
		$role      = $founder ? 'founder' : ( $doctor ? 'doctor' : ( user_can( $user_id, 'manage_worldwide_clinic' ) ? 'administrator' : ( $staff ? 'clinic_staff' : 'member' ) ) );

		$claims = array(
			'contract'      => 'wca.identity-claims',
			'version'       => '1.1.0',
			'user_id'       => $user_id,
			'subject_uuid'  => self::subject_uuid( $user_id ),
			'approved'      => $eligible && ! $suspended,
			'founder'       => (bool) $founder,
			'doctor'        => (bool) $doctor,
			'clinic_staff'  => (bool) $staff,
			'role'          => $role,
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
			if ( user_can( $user_id, $cap ) ) { $caps[] = $cap; }
		}
		return $caps;
	}

	public static function is_guardian( $user_id ) {
		if ( function_exists( 'smc_user_is_verified_guardian' ) ) {
			try { $result = smc_user_is_verified_guardian( absint( $user_id ) ); return true === $result || 1 === $result || '1' === $result; } catch ( Throwable $e ) { return false; }
		}
		return true === apply_filters( 'wca_user_is_verified_guardian', false, absint( $user_id ) );
	}

	/** @return true|WP_Error */
	public static function require_step_up( $purpose, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( function_exists( 'smc_step_up_is_valid' ) ) {
			try { $step_result = smc_step_up_is_valid( $user_id, sanitize_key( $purpose ) ); if ( true === $step_result || 1 === $step_result || '1' === $step_result ) { return true; } } catch ( Throwable $e ) { /* fail closed below */ }
		}
		if ( true === apply_filters( 'wca_step_up_is_valid', false, $user_id, sanitize_key( $purpose ) ) ) { return true; }
		return new WP_Error( 'wca_step_up_required', __( 'A recent security verification is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403, 'purpose' => sanitize_key( $purpose ) ) );
	}

	/** @return true|WP_Error */
	public static function can_create_clinic( $user_id = 0 ) {
		$claims = self::claims( $user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		return ! empty( $claims['doctor'] ) || ! empty( $claims['founder'] ) ? true : new WP_Error( 'wca_doctor_required', __( 'Verified doctor authority is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public static function can_manage_clinic( $clinic, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		$claims = self::claims( $user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) || user_can( $user_id, 'manage_wca_clinics' ) ) { return true; }
		if ( absint( $clinic['owner_user_id'] ?? 0 ) === $user_id && ( ! empty( $claims['doctor'] ) || ! empty( $claims['founder'] ) ) ) { return true; }
		$entry = self::clinic_delegation( $user_id, absint( $clinic['id'] ?? 0 ) );
		if ( $entry && self::delegation_allows_scope( $entry, 'clinic_manage' ) ) { return true; }
		return new WP_Error( 'wca_clinic_forbidden', __( 'You cannot manage this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	/** @return true|WP_Error */
	public static function can_view_appointment( $appointment_id, $user_id = 0, $purpose = '' ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		$claims = self::claims( $user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		if ( SWC_Helpers::can_patient_manage( $appointment_id, $user_id ) || SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return true; }
		$guardian_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
		if ( $guardian_id === $user_id && ! empty( $claims['guardian'] ) ) {
			$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
			$guardian = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_id, $guardian_id, $user_id ) : true;
			return is_wp_error( $guardian ) ? $guardian : true;
		}
		if ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) {
			return true;
		}
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) || user_can( $user_id, 'manage_wca_operations' ) ) {
			$purpose = sanitize_key( $purpose );
			$allowed = array( 'operations', 'complaint', 'privacy_request', 'incident', 'support_case' );
			if ( ! in_array( $purpose, $allowed, true ) ) {
				return new WP_Error( 'wca_admin_purpose_required', __( 'A permitted administrative access purpose is required.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
			}
			$step = self::require_step_up( 'appointment_' . $purpose, $user_id );
			if ( is_wp_error( $step ) ) {
				return new WP_Error( 'wca_admin_step_up', __( 'Recent security verification is required for this purpose-limited access.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
			}
			if ( ! SWC_Helpers::audit( $appointment_id, 'purpose-limited-admin-access', array( 'reason' => $purpose, 'source' => 'authorization' ) ) ) {
				return new WP_Error( 'wca_admin_access_audit_failed', __( 'Purpose-limited administrative access could not be audited safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
			}
			return true;
		}
		return new WP_Error( 'wca_appointment_forbidden', __( 'You cannot access this appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
	}

	/** @return true|WP_Error */
	public static function can_transition_appointment( $appointment_id, $next, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		/* The transition matrix grants the canonical clinic administrator an explicit
		 * actor class. Purpose-limited appointment access must therefore be revalidated
		 * with the operations purpose (and its step-up requirement) before that actor
		 * can reach the matrix. Operations-only users are not promoted to admin here. */
		$purpose = user_can( $user_id, 'manage_worldwide_clinic' ) ? 'operations' : '';
		$view = self::can_view_appointment( $appointment_id, $user_id, $purpose );
		if ( is_wp_error( $view ) ) { return $view; }
		$actor = self::appointment_actor( $appointment_id, $user_id );
		$current = SWC_Helpers::status( $appointment_id );
		return WCA_Contracts::can_transition( $actor, $current, $next ) ? true : new WP_Error( 'wca_transition_forbidden', __( 'That appointment transition is not allowed.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'from' => $current, 'to' => WCA_Contracts::normalize_appointment_status( $next ) ) );
	}

	public static function appointment_actor( $appointment_id, $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return 'admin'; }
		if ( SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return 'doctor'; }
		if ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }
		if ( absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) ) === $user_id ) { return 'guardian'; }
		return 'patient';
	}

	/**
	 * Check explicit clinic delegation scope for an appointment. This does not
	 * infer clinical authority from a role label or a generic active session.
	 */
	public static function can_staff_access_appointment( $appointment_id, $user_id = 0, $scope = 'appointments' ) {
		$user_id   = absint( $user_id ?: get_current_user_id() );
		$clinic_id = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) );
		if ( ! $user_id || ! $clinic_id ) { return false; }
		$entry = self::clinic_delegation( $user_id, $clinic_id );
		return $entry ? self::delegation_allows_scope( $entry, sanitize_key( $scope ) ) : false;
	}

	/** @return array<int,int> */
	public static function delegated_clinic_ids( $user_id = 0, $scope = 'appointments' ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		$out = array();
		foreach ( self::delegations( $user_id ) as $clinic_id => $entry ) {
			$clinic_id = absint( $clinic_id );
			if ( $clinic_id && is_array( $entry ) && self::delegation_allows_scope( $entry, sanitize_key( $scope ) ) ) { $out[] = $clinic_id; }
		}
		return array_values( array_unique( $out ) );
	}

	/** Return whether a doctor has a current File 08 serving relationship with a clinic. */
	public static function doctor_can_serve_clinic( $clinic, $doctor_user_id, $actor_user_id = 0 ) {
		$clinic = is_array( $clinic ) ? $clinic : WCA_Repository::get_clinic( absint( $clinic ), false );
		$doctor_user_id = absint( $doctor_user_id );
		$actor_user_id  = absint( $actor_user_id );
		if ( ! $clinic || ! $doctor_user_id ) { return false; }
		/* Serving authority is never valid for a practitioner whose current File 09/doctor authority is no longer eligible.
		 * Keep this invariant at the canonical relationship root so direct/internal callers cannot bypass an edge-only check. */
		if ( ! class_exists( 'SWC_Doctor_Authority' ) || ! SWC_Doctor_Authority::is_eligible( $doctor_user_id ) ) { return false; }
		$clinic_id = absint( $clinic['id'] ?? 0 );
		if ( ! $clinic_id ) { return false; }
		if ( $doctor_user_id === absint( $clinic['owner_user_id'] ?? 0 ) ) { return true; }
		$delegated = array_merge(
			self::delegated_clinic_ids( $doctor_user_id, 'schedule' ),
			self::delegated_clinic_ids( $doctor_user_id, 'clinic_manage' )
		);
		$allowed = in_array( $clinic_id, array_map( 'absint', $delegated ), true );
		return (bool) apply_filters( 'wca_doctor_may_serve_clinic', $allowed, $doctor_user_id, $clinic_id, $actor_user_id );
	}

	public static function has_active_clinic_delegation( $user_id = 0 ) {
		$user_id = absint( $user_id ?: get_current_user_id() );
		foreach ( self::delegations( $user_id ) as $entry ) {
			if ( is_array( $entry ) && ! empty( $entry['active'] ) ) { return true; }
		}
		return false;
	}

	/** @return array<string,mixed> */
	private static function delegations( $user_id ) {
		$value = get_user_meta( absint( $user_id ), '_wca_clinic_delegations', true );
		return is_array( $value ) ? $value : array();
	}

	/** @return array<string,mixed> */
	private static function clinic_delegation( $user_id, $clinic_id ) {
		$all = self::delegations( absint( $user_id ) );
		$entry = isset( $all[ absint( $clinic_id ) ] ) && is_array( $all[ absint( $clinic_id ) ] ) ? $all[ absint( $clinic_id ) ] : array();
		return ! empty( $entry['active'] ) ? $entry : array();
	}

	private static function delegation_allows_scope( $entry, $scope ) {
		if ( ! is_array( $entry ) || empty( $entry['active'] ) ) { return false; }
		$scope = sanitize_key( $scope );
		$scopes = isset( $entry['scopes'] ) && is_array( $entry['scopes'] ) ? array_map( 'sanitize_key', $entry['scopes'] ) : array();
		$direct = ! empty( $entry[ $scope ] ) || in_array( $scope, $scopes, true );
		if ( 'appointments' === $scope ) {
			// Appointment visibility/operations require an explicit appointment grant.
			// Schedule-only or clinical-followup grants must never broaden into appointment access.
			$direct = $direct || ! empty( $entry['appointment_ops'] ) || in_array( 'appointment_ops', $scopes, true );
		} elseif ( 'clinical_followup' === $scope ) {
			$direct = $direct || ! empty( $entry['clinical'] ) || in_array( 'clinical', $scopes, true );
		} elseif ( 'clinic_manage' === $scope ) {
			// Management is explicit; schedule/appointments grants are narrower and cannot escalate.
			$direct = $direct || ! empty( $entry['manage'] ) || in_array( 'manage', $scopes, true );
		}
		return (bool) apply_filters( 'wca_clinic_delegation_allows_scope', $direct, $entry, $scope );
	}

	/** @return true|WP_Error */
	public static function guardian_context( $patient_user_id, $guardian_user_id, $actor_user_id = 0 ) {
		$patient_user_id  = absint( $patient_user_id );
		$guardian_user_id = absint( $guardian_user_id );
		$actor_user_id    = absint( $actor_user_id ?: get_current_user_id() );
		if ( ! $patient_user_id || ! $actor_user_id ) {
			return new WP_Error( 'wca_patient_actor', __( 'A valid patient and current actor are required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		if ( class_exists( 'WCA_Central_Governance' ) ) {
			return WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
		}
		if ( ! $guardian_user_id ) {
			return $patient_user_id === $actor_user_id ? true : new WP_Error( 'wca_patient_actor_mismatch', __( 'A user may only make a personal request or act through a verified guardian relationship.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		if ( $guardian_user_id !== $actor_user_id || ! self::is_guardian( $guardian_user_id ) ) {
			return new WP_Error( 'wca_guardian_unverified', __( 'The current actor must be the verified guardian.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$allowed = true === apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );
		if ( function_exists( 'smc_guardian_may_act_for' ) ) {
			try { $relationship = smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ); $allowed = true === $relationship || 1 === $relationship || '1' === $relationship; } catch ( Throwable $e ) { $allowed = false; }
		}
		return $allowed ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}
}
