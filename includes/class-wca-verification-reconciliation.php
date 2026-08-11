<?php
/**
 * File 09 verification-state reconciliation for File 08 owned clinics.
 *
 * The doctor verification fact remains owned by File 09. File 08 does not
 * silently rewrite that fact; it invalidates/refreshes its clinic projection
 * and emits typed owner events so File 26 and other consumers cannot retain a
 * stale searchable clinic after suspension/revocation.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Verification_Reconciliation {
	const CONTRACT_VERSION = '1.0.0';

	public static function boot() {
		add_action( 'wca_doctor_suspended', array( __CLASS__, 'doctor_ineligible' ), 20, 2 );
		add_action( 'wca_doctor_revoked', array( __CLASS__, 'doctor_ineligible' ), 20, 2 );
		add_action( 'wca_doctor_verified', array( __CLASS__, 'doctor_reverified' ), 20, 1 );
	}

	public static function doctor_ineligible( $doctor_user_id, $reason = '' ) {
		self::publish_clinic_eligibility( absint( $doctor_user_id ), false, sanitize_text_field( $reason ) );
	}

	public static function doctor_reverified( $doctor_user_id ) {
		self::publish_clinic_eligibility( absint( $doctor_user_id ), true, 'verification_restored' );
	}

	private static function publish_clinic_eligibility( $doctor_user_id, $eligible, $reason ) {
		if ( ! $doctor_user_id ) { return; }
		$page = 1;
		do {
			$clinics = WCA_Repository::list_clinics( array(
				'owner_user_id' => $doctor_user_id,
				'status'        => '',
				'page'          => $page,
				'per_page'      => 100,
			) );
			foreach ( (array) $clinics as $clinic ) {
				$clinic_ref = sanitize_text_field( isset( $clinic['public_ref'] ) ? $clinic['public_ref'] : '' );
				if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $clinic_ref ) ) { continue; }
				$trace = WCA_Observability::trace_id();
				$payload = array(
					'contract'    => 'wca.clinic-eligibility',
					'version'     => self::CONTRACT_VERSION,
					'clinic_ref'  => strtolower( $clinic_ref ),
					'eligible'    => (bool) $eligible,
					'reason'      => $reason,
					'owner'       => 'File08',
					'source_owner'=> 'File09',
					'checked_at'  => gmdate( 'c' ),
				);
				WCA_Repository::append_event( 'ClinicEligibilityChanged.v1', 'clinic', $clinic_ref, $payload, 0, $trace );
				WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(
					'contract'      => 'wca.file26-clinic-projection',
					'version'       => WCA_Central_Governance::FILE26_PROJECTION_VERSION,
					'object_type'   => 'clinic',
					'public_ref'    => strtolower( $clinic_ref ),
					'eligible'      => (bool) $eligible,
					'change_source' => 'ClinicEligibilityChanged.v1',
					'owner'         => 'File08',
				), $trace );
			}
			$page++;
		} while ( count( (array) $clinics ) === 100 );
		WCA_Observability::metric( 'verification_reconciliation_total', 1, array( 'eligible' => $eligible ? 'yes' : 'no' ) );
	}
}
