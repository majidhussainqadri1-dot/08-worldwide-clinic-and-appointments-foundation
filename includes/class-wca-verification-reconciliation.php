<?php
/**
 * File 09 verification/delegation reconciliation for File 08 clinic discovery.
 *
 * Practitioner verification remains owned by File 09 and clinic delegation
 * remains File 08 authority. This adapter never copies either fact into a new
 * source of truth; it recomputes the current File 08 public projection and
 * emits a refresh so File 26 cannot retain stale discovery state.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Verification_Reconciliation {
	const CONTRACT_VERSION = '1.1.0';
	const DELEGATION_META_KEY = '_wca_clinic_delegations';

	public static function boot() {
		add_action( 'wca_doctor_suspended', array( __CLASS__, 'doctor_ineligible' ), 20, 2 );
		add_action( 'wca_doctor_revoked', array( __CLASS__, 'doctor_ineligible' ), 20, 2 );
		add_action( 'wca_doctor_verified', array( __CLASS__, 'doctor_reverified' ), 20, 1 );
		add_action( 'wca_retry_doctor_eligibility_reconciliation', array( __CLASS__, 'retry' ), 20, 4 );
		add_action( 'added_user_meta', array( __CLASS__, 'delegation_meta_changed' ), 20, 4 );
		add_action( 'updated_user_meta', array( __CLASS__, 'delegation_meta_changed' ), 20, 4 );
		add_action( 'deleted_user_meta', array( __CLASS__, 'delegation_meta_changed' ), 20, 4 );
	}

	public static function doctor_ineligible( $doctor_user_id, $reason = '' ) {
		return self::run_or_retry( absint( $doctor_user_id ), false, sanitize_text_field( $reason ), 'File09' );
	}

	public static function doctor_reverified( $doctor_user_id ) {
		return self::run_or_retry( absint( $doctor_user_id ), true, 'verification_restored', 'File09' );
	}

	public static function retry( $doctor_user_id, $eligible, $reason, $source_owner = 'File09' ) {
		return self::run_or_retry( absint( $doctor_user_id ), (bool) $eligible, sanitize_text_field( $reason ), self::source_owner( $source_owner ) );
	}

	/** Observe only the File 08 delegation authority meta; unrelated user-meta changes are ignored. */
	public static function delegation_meta_changed( $meta_id, $user_id, $meta_key, $meta_value = null ) {
		unset( $meta_id, $meta_value );
		if ( self::DELEGATION_META_KEY !== (string) $meta_key ) { return; }
		$user_id = absint( $user_id );
		if ( ! $user_id ) { return; }
		$eligible = class_exists( 'SWC_Doctor_Authority' ) && SWC_Doctor_Authority::is_eligible( $user_id );
		self::run_or_retry( $user_id, $eligible, 'delegation_changed', 'File08' );
	}

	private static function source_owner( $source_owner ) {
		return 'File08' === (string) $source_owner ? 'File08' : 'File09';
	}

	private static function run_or_retry( $doctor_user_id, $practitioner_eligible, $reason, $source_owner ) {
		$source_owner = self::source_owner( $source_owner );
		$result = self::publish_clinic_eligibility( $doctor_user_id, $practitioner_eligible, $reason, $source_owner );
		if ( ! is_wp_error( $result ) ) { return true; }
		WCA_Observability::log( 'error', 'verification_reconciliation_failed', array( 'doctor_user_id' => $doctor_user_id, 'eligible' => $practitioner_eligible ? 'yes' : 'no', 'source_owner' => $source_owner, 'error_code' => $result->get_error_code() ) );
		$args = array( $doctor_user_id, $practitioner_eligible ? 1 : 0, $reason, $source_owner );
		if ( ! wp_next_scheduled( 'wca_retry_doctor_eligibility_reconciliation', $args ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wca_retry_doctor_eligibility_reconciliation', $args );
		}
		return $result;
	}

	private static function publish_clinic_eligibility( $doctor_user_id, $practitioner_eligible, $reason, $source_owner ) {
		global $wpdb;
		if ( ! $doctor_user_id ) { return new WP_Error( 'wca_verification_reconciliation_doctor', __( 'A doctor identity is required for reconciliation.', 'worldwide-clinic-appointments' ) ); }
		$tables = WCA_Schema::tables();
		$clinic_table = $tables['clinics'];
		$service_table = $tables['services'];
		$availability_table = $tables['availability'];
		$wpdb->last_error = '';
		$sql = $wpdb->prepare(
			"SELECT DISTINCT c.id FROM {$clinic_table} c LEFT JOIN {$service_table} s ON s.clinic_id=c.id LEFT JOIN {$availability_table} a ON a.clinic_id=c.id WHERE c.owner_user_id=%d OR s.doctor_user_id=%d OR a.doctor_user_id=%d ORDER BY c.id ASC",
			$doctor_user_id,
			$doctor_user_id,
			$doctor_user_id
		);
		$clinic_ids_raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_verification_reconciliation_read', __( 'Affected clinics could not be read safely for verification reconciliation.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$clinic_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $clinic_ids_raw ) ) ) );
		foreach ( $clinic_ids as $clinic_id ) {
			WCA_Repository::clear_read_error();
			$clinic = WCA_Repository::get_clinic( $clinic_id, false );
			$read_error = WCA_Repository::consume_read_error();
			if ( is_wp_error( $read_error ) ) { return $read_error; }
			if ( ! $clinic ) { continue; }
			$clinic_ref = strtolower( sanitize_text_field( isset( $clinic['public_ref'] ) ? $clinic['public_ref'] : '' ) );
			if ( ! preg_match( '/^[0-9a-f-]{36}$/', $clinic_ref ) ) { continue; }

			/* File 26 eligibility is the current whole-clinic discoverability truth, not
			 * the affected practitioner's verification bit. The projection itself also
			 * removes services whose serving delegation is no longer current. */
			$projection = WCA_Service::public_clinic_projection( $clinic_ref );
			if ( is_wp_error( $projection ) ) { return $projection; }
			$clinic_eligible = is_array( $projection ) && ! empty( $projection['public_ref'] ) && ! empty( $projection['verified_owner'] );
			$is_owner = absint( $clinic['owner_user_id'] ?? 0 ) === absint( $doctor_user_id );
			$change_event = $is_owner ? 'ClinicEligibilityChanged.v1' : 'ClinicPractitionerEligibilityChanged.v1';
			$trace = WCA_Observability::trace_id();
			$payload = array(
				'contract'                          => 'wca.clinic-eligibility',
				'version'                           => self::CONTRACT_VERSION,
				'clinic_ref'                        => $clinic_ref,
				'eligible'                          => (bool) $clinic_eligible,
				'affected_practitioner_subject_uuid'=> WCA_Authorization::subject_uuid( $doctor_user_id ),
				'practitioner_eligible'              => (bool) $practitioner_eligible,
				'reason'                            => $reason,
				'owner'                             => 'File08',
				'source_owner'                      => self::source_owner( $source_owner ),
				'checked_at'                        => gmdate( 'c' ),
			);
			$written = WCA_Repository::transaction( static function () use ( $clinic_ref, $payload, $trace, $clinic_eligible, $change_event ) {
				$event = WCA_Repository::append_event( $change_event, 'clinic', $clinic_ref, $payload, 0, $trace );
				if ( is_wp_error( $event ) ) { return $event; }
				$outbox = WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(
					'contract'                          => 'wca.file26-clinic-projection',
					'version'                           => WCA_Central_Governance::FILE26_PROJECTION_VERSION,
					'object_type'                       => 'clinic',
					'public_ref'                        => $clinic_ref,
					'eligible'                          => (bool) $clinic_eligible,
					'affected_practitioner_subject_uuid'=> $payload['affected_practitioner_subject_uuid'],
					'practitioner_eligible'              => $payload['practitioner_eligible'],
					'change_source'                     => $change_event,
					'owner'                             => 'File08',
				), $trace );
				return is_wp_error( $outbox ) ? $outbox : true;
			}, 'wca_verification_reconciliation_write' );
			if ( is_wp_error( $written ) ) { return $written; }
		}
		WCA_Observability::metric( 'verification_reconciliation_total', 1, array( 'eligible' => $practitioner_eligible ? 'yes' : 'no', 'source_owner' => self::source_owner( $source_owner ) ) );
		return true;
	}
}
