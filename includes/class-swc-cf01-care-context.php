<?php
/**
 * Privacy-minimal File 08 care-context provider for CF-01.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_CF01_Care_Context {
	const CONTRACT_NAME       = 'swc.cf01.care-context';
	const CONTRACT_VERSION    = '1.0.0';
	const FILE_00_MIN_VERSION = '1.2.7';
	const FILE_00_CONTRACT    = '1.0.0';
	const ASSERTION_TTL       = 60;

	private static $purposes = array(
		'appointment_context_read',
		'clinical_record_bootstrap',
		'treating_relationship_activate',
		'clinical_read',
		'clinical_write',
	);

	/**
	 * Return a purpose-bound care-context assertion.
	 *
	 * Appointment existence, acceptance or completion never establishes a
	 * treating relationship and never grants clinical chart authority.
	 *
	 * @param int    $appointment_id Appointment post ID.
	 * @param int    $actor_id Current acting platform user ID.
	 * @param string $purpose Approved purpose.
	 * @param int    $expected_version Optional optimistic record version.
	 * @return array<string,mixed>
	 */
	public static function assertion( $appointment_id, $actor_id, $purpose, $expected_version = 0 ) {
		$appointment_id = absint( $appointment_id );
		$actor_id       = absint( $actor_id );
		$purpose        = sanitize_key( $purpose );
		$now            = time();
		$envelope       = array(
			'contract'         => self::CONTRACT_NAME,
			'contract_version' => self::CONTRACT_VERSION,
			'producer_version' => defined( 'SWC_VERSION' ) ? SWC_VERSION : '',
			'issued_at'        => gmdate( 'c', $now ),
			'expires_at'       => gmdate( 'c', $now + self::ASSERTION_TTL ),
			'purpose'          => $purpose,
			'result'           => 'unknown',
			'reason_code'      => 'context_unresolved',
		);

		if ( ! in_array( $purpose, self::$purposes, true ) ) {
			$envelope['reason_code'] = 'unsupported_purpose';
			return $envelope;
		}
		if ( ! self::identity_provider_available() ) {
			$envelope['reason_code'] = 'identity_provider_unavailable';
			return $envelope;
		}
		if ( ! $appointment_id || SWC_Helpers::TYPE !== get_post_type( $appointment_id ) ) {
			$envelope['result'] = 'deny';
			$envelope['reason_code'] = 'context_not_available';
			return $envelope;
		}
		if ( ! self::actor_can_read( $appointment_id, $actor_id ) ) {
			$envelope['result'] = 'deny';
			$envelope['reason_code'] = 'context_not_available';
			return $envelope;
		}

		$record_version = SWC_Helpers::record_version( $appointment_id );
		if ( $expected_version && $record_version !== absint( $expected_version ) ) {
			$envelope['result'] = 'deny';
			$envelope['reason_code'] = 'stale_record_version';
			return $envelope;
		}

		$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$doctor_id  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) );
		$patient    = self::subject_assertion( $patient_id );
		$doctor     = self::subject_assertion( $doctor_id );
		if ( ! self::valid_subject_assertion( $patient ) || ! self::valid_subject_assertion( $doctor ) ) {
			$envelope['reason_code'] = 'identity_assertion_unavailable';
			return $envelope;
		}

		$status = SWC_Helpers::status( $appointment_id );
		$envelope['context'] = array(
			'source_owner'              => 'File 08',
			'appointment_reference'     => self::opaque_reference( 'appointment', $appointment_id ),
			'record_version'            => $record_version,
			'appointment_status'        => $status,
			'context_state'             => self::context_state( $status ),
			'patient_subject_uuid'      => (string) $patient['subject']['platform_uuid'],
			'practitioner_subject_uuid' => (string) $doctor['subject']['platform_uuid'],
			'clinic_reference'          => '',
			'location_reference'        => '',
			'clinic_location_modeled'   => false,
			'consultation_type'         => sanitize_key( (string) SWC_Helpers::meta( $appointment_id, 'consultation_type' ) ),
			'scheduled_at_utc'          => self::scheduled_time( $appointment_id, $status ),
		);
		$envelope['relationship'] = array(
			'source'                         => 'appointment',
			'type'                           => 'scheduling_only',
			'status'                         => self::relationship_state( $status ),
			'treating_relationship_asserted' => false,
			'sufficient_for_clinical_read'   => false,
			'sufficient_for_clinical_write'  => false,
			'sufficient_for_prescription'    => false,
			'sufficient_for_break_glass'     => false,
		);
		$envelope['consent'] = array(
			'appointment_processing_recorded' => '' !== (string) SWC_Helpers::meta( $appointment_id, 'consent_at' ),
			'appointment_consent_version'     => (string) SWC_Helpers::meta( $appointment_id, 'consent_version' ),
			'scope'                           => 'appointment_processing_only',
			'clinical_treatment_consent'      => false,
			'publication_consent'             => false,
		);

		if ( 'appointment_context_read' === $purpose ) {
			$envelope['result'] = 'allow';
			$envelope['reason_code'] = 'scheduling_context_available';
			return $envelope;
		}

		$envelope['result'] = 'deny';
		$envelope['reason_code'] = 'appointment_is_not_treating_relationship';
		return $envelope;
	}

	/** @return array<string,mixed> */
	public static function contract() {
		return array(
			'contract'                   => self::CONTRACT_NAME,
			'contract_version'           => self::CONTRACT_VERSION,
			'owner'                      => 'File 08',
			'appointment_is_relationship'=> false,
			'appointment_consent_scope'  => 'appointment_processing_only',
			'clinical_narrative_returned'=> false,
			'fail_closed'                => true,
		);
	}

	private static function identity_provider_available() {
		return defined( 'SMC_VERSION' )
			&& version_compare( (string) SMC_VERSION, self::FILE_00_MIN_VERSION, '>=' )
			&& defined( 'SMC_CF01_CONTRACT_VERSION' )
			&& version_compare( (string) SMC_CF01_CONTRACT_VERSION, self::FILE_00_CONTRACT, '>=' )
			&& class_exists( 'SMC_CF01_Contract' )
			&& is_callable( array( 'SMC_CF01_Contract', 'membership_assertion' ) );
	}

	private static function subject_assertion( $user_id ) {
		if ( ! $user_id ) {
			return array();
		}
		try {
			$result = SMC_CF01_Contract::membership_assertion(
				$user_id,
				array(
					'action'  => 'clinical_identity_link',
					'purpose' => 'care_context_reference',
				)
			);
		} catch ( Throwable $e ) {
			return array();
		}
		return is_array( $result ) ? $result : array();
	}

	private static function valid_subject_assertion( $assertion ) {
		return is_array( $assertion )
			&& 'smc.cf01.membership-assurance' === ( $assertion['contract'] ?? '' )
			&& version_compare( (string) ( $assertion['contract_version'] ?? '' ), self::FILE_00_CONTRACT, '>=' )
			&& in_array( $assertion['result'] ?? '', array( 'allow', 'deny' ), true )
			&& self::valid_uuid( $assertion['subject']['platform_uuid'] ?? '' );
	}

	private static function actor_can_read( $appointment_id, $actor_id ) {
		if ( ! $actor_id || get_current_user_id() !== $actor_id ) {
			return false;
		}
		if ( SWC_Helpers::can_patient_manage( $appointment_id, $actor_id ) ) {
			return true;
		}
		if ( SWC_Helpers::can_doctor_manage( $appointment_id, $actor_id ) ) {
			return true;
		}
		return user_can( $actor_id, 'manage_worldwide_clinic' );
	}

	private static function context_state( $status ) {
		if ( in_array( $status, array( 'requested', 'under-review', 'reschedule-requested' ), true ) ) {
			return 'proposed';
		}
		if ( 'accepted' === $status ) {
			return 'scheduled';
		}
		if ( 'completed' === $status ) {
			return 'appointment_completed';
		}
		return 'ended';
	}

	private static function relationship_state( $status ) {
		if ( 'accepted' === $status ) {
			return 'scheduled_contact';
		}
		if ( 'completed' === $status ) {
			return 'appointment_completed_without_relationship_assertion';
		}
		if ( in_array( $status, array( 'cancelled', 'declined' ), true ) ) {
			return 'ended';
		}
		return 'proposed_contact';
	}

	private static function scheduled_time( $appointment_id, $status ) {
		if ( ! in_array( $status, array( 'accepted', 'completed' ), true ) ) {
			return '';
		}
		$value = (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' );
		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ? $value : '';
	}

	private static function opaque_reference( $type, $id ) {
		return sanitize_key( $type ) . '_' . substr( hash_hmac( 'sha256', absint( $id ) . '|' . sanitize_key( $type ), wp_salt( 'nonce' ) ), 0, 40 );
	}

	private static function valid_uuid( $value ) {
		return is_string( $value ) && 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
	}
}

if ( ! function_exists( 'swc_cf01_care_context_contract' ) ) {
	function swc_cf01_care_context_contract() {
		return SWC_CF01_Care_Context::contract();
	}
}

if ( ! function_exists( 'swc_get_cf01_care_context' ) ) {
	function swc_get_cf01_care_context( $appointment_id, $actor_id, $purpose, $expected_version = 0 ) {
		return SWC_CF01_Care_Context::assertion( $appointment_id, $actor_id, $purpose, $expected_version );
	}
}
