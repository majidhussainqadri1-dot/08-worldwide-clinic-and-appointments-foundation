<?php
/**
 * Cross-plan invariants that must remain true at every public scheduling edge.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Plan_Guard {
	const REVIEW_ELIGIBILITY_DAYS = 180;

	public static function practitioner_ref( $user_id ) {
		global $wpdb;
		$user_id = absint( $user_id );
		if ( ! $user_id || ! SWC_Doctor_Authority::is_eligible( $user_id ) ) { return ''; }
		$ref = strtolower( (string) get_user_meta( $user_id, '_wca_practitioner_ref', true ) );
		if ( preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) { return $ref; }
		$lock = 'wca-practitioner-ref-' . $user_id;
		$lock_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock ) );
		if ( null === $lock_raw && '' !== (string) $wpdb->last_error ) { return ''; }
		if ( 1 !== (int) $lock_raw ) { return ''; }
		try {
			$ref = strtolower( (string) get_user_meta( $user_id, '_wca_practitioner_ref', true ) );
			if ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) {
				$candidate = WCA_Repository::uuid();
				$written = update_user_meta( $user_id, '_wca_practitioner_ref', $candidate );
				if ( false === $written ) { return ''; }
				$ref = strtolower( (string) get_user_meta( $user_id, '_wca_practitioner_ref', true ) );
			}
			return preg_match( '/^[0-9a-f-]{36}$/', $ref ) ? $ref : '';
		} finally {
			$released_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
			if ( 1 !== (int) $released_raw ) {
				WCA_Observability::metric( 'practitioner_ref_lock_release_failed_total', 1 );
				WCA_Observability::log( 'error', 'practitioner_ref_lock_release_failed', array( 'user_id' => $user_id, 'db_error' => '' !== (string) $wpdb->last_error ) );
			}
		}
	}

	public static function practitioner_id( $ref ) {
		$ref = strtolower( sanitize_text_field( $ref ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) {
			return 0;
		}
		$users = get_users(
			array(
				'meta_key'   => '_wca_practitioner_ref',
				'meta_value' => $ref,
				'number'     => 2,
				'fields'     => 'ID',
			)
		);
		if ( 1 !== count( $users ) ) {
			return 0;
		}
		$user_id = absint( $users[0] );
		return SWC_Doctor_Authority::is_eligible( $user_id ) ? $user_id : 0;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function resolve_public_slot_query( $args ) {
		$clinic = WCA_Repository::get_clinic( sanitize_text_field( $args['clinic_ref'] ?? '' ), true );
		$service = WCA_Repository::get_service_by_ref( sanitize_text_field( $args['service_ref'] ?? '' ), true );
		$doctor_id = self::practitioner_id( $args['practitioner_ref'] ?? '' );
		if ( ! $clinic || ! $service || ! $doctor_id ) {
			return new WP_Error( 'wca_slot_reference', __( 'A valid public clinic, service, and practitioner reference is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		if ( absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) ) {
			return new WP_Error( 'wca_slot_scope', __( 'The selected service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		if ( ! WCA_Authorization::doctor_can_serve_clinic( $clinic, $doctor_id ) ) {
			return new WP_Error( 'wca_slot_doctor_scope', __( 'The selected practitioner no longer has authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$assigned = absint( $service['doctor_user_id'] );
		if ( $assigned && $assigned !== $doctor_id ) {
			return new WP_Error( 'wca_slot_practitioner', __( 'The selected practitioner is not assigned to this service.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		if ( ! $assigned && absint( $clinic['owner_user_id'] ) !== $doctor_id ) {
			return new WP_Error( 'wca_slot_owner', __( 'The selected practitioner is not authorized for this clinic service.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		return array(
			'clinic_id'      => absint( $clinic['id'] ),
			'service_id'     => absint( $service['id'] ),
			'doctor_user_id' => $doctor_id,
			'date_from'      => sanitize_text_field( $args['date_from'] ?? '' ),
			'date_to'        => sanitize_text_field( $args['date_to'] ?? '' ),
			'timezone'       => sanitize_text_field( $args['timezone'] ?? 'UTC' ),
			'limit'          => min( 500, max( 1, absint( $args['limit'] ?? 100 ) ) ),
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function canonical_slot_hold( $data, $patient_user_id ) {
		$patient_user_id = absint( $patient_user_id );
		$requested_patient_id = absint( $data['patient_user_id'] ?? 0 );
		if ( $requested_patient_id && $requested_patient_id !== $patient_user_id ) {
			$guardian_user_id = absint( $data['guardian_user_id'] ?? 0 );
			if ( ! $guardian_user_id || $guardian_user_id !== $patient_user_id ) {
				return new WP_Error( 'wca_slot_patient_owner_mismatch', __( 'The requested patient does not match the authenticated slot owner.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
			}
			$guardian = WCA_Authorization::guardian_context( $requested_patient_id, $guardian_user_id, $patient_user_id );
			if ( is_wp_error( $guardian ) ) { return $guardian; }
			$patient_user_id = $requested_patient_id;
		}
		if ( ! $patient_user_id ) {
			return new WP_Error( 'wca_slot_patient_required', __( 'A valid patient is required to hold a slot.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$query = self::resolve_public_slot_query( $data );
		if ( is_wp_error( $query ) ) {
			return $query;
		}
		$rule = WCA_Repository::get_availability_rule_by_ref( sanitize_text_field( $data['rule_ref'] ?? '' ), true );
		$slot_ref = sanitize_text_field( $data['slot_ref'] ?? '' );
		$freshness = absint( $data['freshness_version'] ?? 0 );
		if ( ! $rule || ! $slot_ref || ! $freshness ) {
			return new WP_Error( 'wca_slot_evidence', __( 'Current server-issued slot evidence is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		if ( absint( $rule['clinic_id'] ) !== $query['clinic_id'] || absint( $rule['doctor_user_id'] ) !== $query['doctor_user_id'] || absint( $rule['version'] ) !== $freshness ) {
			return new WP_Error( 'wca_slot_stale', __( 'The selected availability rule changed. Search again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		if ( absint( $rule['service_id'] ) && absint( $rule['service_id'] ) !== $query['service_id'] ) {
			return new WP_Error( 'wca_slot_service', __( 'The selected slot does not belong to this service.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$start = self::strict_utc( $data['start_utc'] ?? '' );
		$end   = self::strict_utc( $data['end_utc'] ?? '' );
		if ( ! $start || ! $end || strtotime( $end . ' UTC' ) <= strtotime( $start . ' UTC' ) ) {
			return new WP_Error( 'wca_slot_time', __( 'The selected slot time is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$client_key = sanitize_text_field( $data['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $client_key ) ) {
			return new WP_Error( 'wca_idempotency_required', __( 'A valid idempotency key is required to hold a slot.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		/* Namespace the replay identity before re-projection. The exact-slot validator may
		 * ignore only the hold created by this same patient/key, allowing a true replay
		 * to reach repository idempotency while every other overlapping hold still blocks. */
		$repository_key = 'p' . absint( $patient_user_id ) . ':' . hash( 'sha256', $client_key );
		$service = $query['service_id'] ? WCA_Repository::get_service( $query['service_id'], true ) : null;
		$duration = $service ? absint( $service['duration_minutes'] ) : max( 10, (int) round( ( strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) ) / 60 ) );
		$matched = WCA_Service::project_rule_slot( $rule, $start, $end, $duration, 'UTC', $repository_key );
		if ( ! $matched
			|| ! hash_equals( $slot_ref, (string) ( $matched['slot_ref'] ?? '' ) )
			|| ! hash_equals( (string) $rule['public_ref'], (string) ( $matched['rule_ref'] ?? '' ) )
			|| $freshness !== absint( $matched['freshness_version'] ?? 0 ) ) {
			return new WP_Error( 'wca_slot_not_available', __( 'The selected slot is not in the current server availability projection.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$data['idempotency_key'] = $repository_key;
		return array_merge(
			$data,
			array(
				'clinic_id'       => $query['clinic_id'],
				'service_id'      => $query['service_id'],
				'branch_id'       => absint( $rule['branch_id'] ),
				'doctor_user_id'  => $query['doctor_user_id'],
				'patient_user_id' => absint( $patient_user_id ),
				'start_utc'       => $start,
				'end_utc'         => $end,
				'buffer_before'   => min( 240, absint( $rule['buffer_before'] ?? 0 ) ),
				'buffer_after'    => min( 240, absint( $rule['buffer_after'] ?? 0 ) ),
			)
		);
	}

	/** @return true|WP_Error */
	public static function validate_bookable_hold( $hold, $patient_user_id ) {
		if ( ! is_array( $hold ) || 'held' !== (string) ( $hold['status'] ?? '' ) || strtotime( (string) ( $hold['expires_at'] ?? '' ) . ' UTC' ) <= time() ) {
			return new WP_Error( 'wca_hold_invalid', __( 'A current slot hold is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		if ( absint( $hold['patient_user_id'] ?? 0 ) !== absint( $patient_user_id ) || absint( $hold['appointment_id'] ?? 0 ) ) {
			return new WP_Error( 'wca_hold_owner', __( 'The slot hold is not owned by this patient request.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$clinic = WCA_Repository::get_clinic( absint( $hold['clinic_id'] ?? 0 ), true );
		$service = WCA_Repository::get_service( absint( $hold['service_id'] ?? 0 ), true );
		if ( ! $clinic || ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) || ! SWC_Doctor_Authority::is_eligible( absint( $hold['doctor_user_id'] ?? 0 ) ) ) {
			return new WP_Error( 'wca_hold_scope', __( 'The clinic, service, or practitioner is no longer eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		if ( ! WCA_Authorization::doctor_can_serve_clinic( $clinic, absint( $hold['doctor_user_id'] ?? 0 ) ) ) {
			return new WP_Error( 'wca_hold_doctor_scope', __( 'The slot practitioner no longer has authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$branch_id = absint( $hold['branch_id'] ?? 0 );
		if ( $branch_id ) {
			$branch = WCA_Repository::get_branch( $branch_id );
			if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) || 'active' !== (string) $branch['status'] ) {
				return new WP_Error( 'wca_hold_branch_scope', __( 'The slot branch is no longer active for this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
		}
		return true;
	}

	/** @return true|WP_Error */
	public static function validate_reschedule_hold( $hold, $appointment_id, $actor_user_id ) {
		$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$valid = self::validate_bookable_hold( $hold, $patient_id );
		if ( is_wp_error( $valid ) ) {
			return $valid;
		}
		if ( absint( $hold['doctor_user_id'] ) !== absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) )
			|| absint( $hold['clinic_id'] ) !== absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) )
			|| absint( $hold['service_id'] ) !== absint( SWC_Helpers::meta( $appointment_id, 'service_id' ) ) ) {
			return new WP_Error( 'wca_reschedule_scope', __( 'The replacement slot does not belong to this appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
		if ( ! in_array( $actor, array( 'patient', 'guardian', 'doctor', 'clinic_staff', 'admin' ), true ) ) {
			return new WP_Error( 'wca_reschedule_actor', __( 'This actor cannot propose a replacement slot.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		return true;
	}

	public static function strict_utc( $value ) {
		$value = trim( (string) $value );
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $value, new DateTimeZone( 'UTC' ) );
		$errors = DateTimeImmutable::getLastErrors();
		return $date && ( ! is_array( $errors ) || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $date->format( 'Y-m-d H:i:s' ) === $value ? $value : '';
	}
}
