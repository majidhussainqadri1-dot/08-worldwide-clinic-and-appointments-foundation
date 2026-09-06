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
		add_action( 'wca_retry_doctor_eligibility_reconciliation', array( __CLASS__, 'retry' ), 20, 3 );
	}

	public static function doctor_ineligible( $doctor_user_id, $reason = '' ) {
		self::run_or_retry( absint( $doctor_user_id ), false, sanitize_text_field( $reason ) );
	}

	public static function doctor_reverified( $doctor_user_id ) {
		self::run_or_retry( absint( $doctor_user_id ), true, 'verification_restored' );
	}

	public static function retry( $doctor_user_id, $eligible, $reason ) {
		self::run_or_retry( absint( $doctor_user_id ), (bool) $eligible, sanitize_text_field( $reason ) );
	}

	private static function run_or_retry( $doctor_user_id, $eligible, $reason ) {
		$result = self::publish_clinic_eligibility( $doctor_user_id, $eligible, $reason );
		if ( ! is_wp_error( $result ) ) { return true; }
		WCA_Observability::log( 'error', 'verification_reconciliation_failed', array( 'doctor_user_id' => $doctor_user_id, 'eligible' => $eligible ? 'yes' : 'no', 'error_code' => $result->get_error_code() ) );
		$args = array( $doctor_user_id, $eligible ? 1 : 0, $reason );
		if ( ! wp_next_scheduled( 'wca_retry_doctor_eligibility_reconciliation', $args ) ) { wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wca_retry_doctor_eligibility_reconciliation', $args ); }
		return $result;
	}

	private static function publish_clinic_eligibility( $doctor_user_id, $eligible, $reason ) {
		global $wpdb;
		if ( ! $doctor_user_id ) { return new WP_Error( 'wca_verification_reconciliation_doctor', __( 'A doctor identity is required for reconciliation.', 'worldwide-clinic-appointments' ) ); }
		$tables = WCA_Schema::tables();
		$clinic_table = $tables['clinics'];
		$service_table = $tables['services'];
		$availability_table = $tables['availability'];
		$sql = $wpdb->prepare(
			"SELECT DISTINCT c.id FROM {$clinic_table} c LEFT JOIN {$service_table} s ON s.clinic_id=c.id LEFT JOIN {$availability_table} a ON a.clinic_id=c.id WHERE c.owner_user_id=%d OR s.doctor_user_id=%d OR a.doctor_user_id=%d ORDER BY c.id ASC",
			$doctor_user_id,
			$doctor_user_id,
			$doctor_user_id
		);
		$clinic_ids_raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $clinic_ids_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_verification_reconciliation_read', __( 'Affected clinics could not be read safely for verification reconciliation.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$clinic_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $clinic_ids_raw ) ) ) );
		foreach ( $clinic_ids as $clinic_id ) {
			WCA_Repository::clear_read_error();
			$clinic = WCA_Repository::get_clinic( $clinic_id, false );
			$read_error = WCA_Repository::consume_read_error();
			if ( is_wp_error( $read_error ) ) { return $read_error; }
			if ( ! $clinic ) { continue; }
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
			$written = WCA_Repository::transaction( static function () use ( $clinic_ref, $payload, $trace, $eligible ) {
				$event = WCA_Repository::append_event( 'ClinicEligibilityChanged.v1', 'clinic', $clinic_ref, $payload, 0, $trace );
				if ( is_wp_error( $event ) ) { return $event; }
				$outbox = WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(
					'contract'      => 'wca.file26-clinic-projection',
					'version'       => WCA_Central_Governance::FILE26_PROJECTION_VERSION,
					'object_type'   => 'clinic',
					'public_ref'    => strtolower( $clinic_ref ),
					'eligible'      => (bool) $eligible,
					'change_source' => 'ClinicEligibilityChanged.v1',
					'owner'         => 'File08',
				), $trace );
				return is_wp_error( $outbox ) ? $outbox : true;
			}, 'wca_verification_reconciliation_write' );
			if ( is_wp_error( $written ) ) { return $written; }
		}
		WCA_Observability::metric( 'verification_reconciliation_total', 1, array( 'eligible' => $eligible ? 'yes' : 'no' ) );
		return true;
	}

}
