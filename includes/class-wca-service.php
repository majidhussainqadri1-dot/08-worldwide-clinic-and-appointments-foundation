<?php
/**
 * File 08 command/query application service.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Service {
	const SLOT_HORIZON_DAYS = 180;
	const TERMS_VERSION      = '2026-08-06.1';

	public static function hooks() {
		add_action( 'wca_doctor_suspended', array( __CLASS__, 'handle_doctor_suspended' ), 10, 2 );
		add_action( 'wca_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed' ), 10, 2 );
		add_action( 'cf03_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed_event' ), 10, 1 );
	}

	public static function valid_timezone( $timezone ) {
		if ( ! is_string( $timezone ) ) { return false; }
		$timezone = trim( $timezone );
		if ( '' === $timezone ) { return false; }
		return 'UTC' === $timezone || in_array( $timezone, timezone_identifiers_list(), true );
	}

	public static function valid_hhmm( $value ) {
		return 1 === preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $value );
	}

	/** Strict integer validator used by canonical persistence roots; never silently clamps caller intent. */
	public static function strict_int( $value, $min, $max ) {
		if ( ! is_int( $value ) && ! is_string( $value ) ) { return null; }
		$validated = filter_var( $value, FILTER_VALIDATE_INT );
		if ( false === $validated || $validated < $min || $validated > $max ) { return null; }
		return (int) $validated;
	}

	/** @return array<string,mixed> */
	public static function sanitize_rrule( $rule ) {
		$weekdays = array( 'monday','tuesday','wednesday','thursday','friday','saturday','sunday' );
		$days = array_values( array_intersect( array_map( 'sanitize_key', (array) ( $rule['days'] ?? array() ) ), $weekdays ) );
		$start = self::valid_hhmm( $rule['start'] ?? '' ) ? (string) $rule['start'] : '09:00';
		$end   = self::valid_hhmm( $rule['end'] ?? '' ) ? (string) $rule['end'] : '17:00';
		$interval = min( 1440, max( 10, absint( $rule['interval_minutes'] ?? 30 ) ) );
		return array(
			'days'             => $days,
			'start'            => $start,
			'end'              => $end,
			'interval_minutes' => $interval,
			'effective_from'   => self::valid_date( $rule['effective_from'] ?? '' ) ? (string) $rule['effective_from'] : gmdate( 'Y-m-d' ),
			'effective_until'  => self::valid_date( $rule['effective_until'] ?? '' ) ? (string) $rule['effective_until'] : gmdate( 'Y-m-d', time() + self::SLOT_HORIZON_DAYS * DAY_IN_SECONDS ),
		);
	}

	/** @return array<int,array<string,string>> */
	public static function sanitize_time_ranges( $ranges ) {
		$out = array();
		foreach ( $ranges as $range ) {
			if ( ! is_array( $range ) || ! self::valid_hhmm( $range['start'] ?? '' ) || ! self::valid_hhmm( $range['end'] ?? '' ) || $range['end'] <= $range['start'] ) {
				continue;
			}
			$out[] = array( 'start' => (string) $range['start'], 'end' => (string) $range['end'] );
		}
		return $out;
	}

	/** @return array<int,array<string,mixed>> */
	public static function sanitize_exceptions( $exceptions ) {
		$out = array();
		foreach ( $exceptions as $exception ) {
			if ( ! is_array( $exception ) || ! self::valid_date( $exception['date'] ?? '' ) ) {
				continue;
			}
			$type = in_array( $exception['type'] ?? '', array( 'closed', 'open', 'capacity' ), true ) ? $exception['type'] : 'closed';
			$out[] = array(
				'date'     => (string) $exception['date'],
				'type'     => $type,
				'start'    => self::valid_hhmm( $exception['start'] ?? '' ) ? (string) $exception['start'] : '',
				'end'      => self::valid_hhmm( $exception['end'] ?? '' ) ? (string) $exception['end'] : '',
				'capacity' => min( 50, max( 0, absint( $exception['capacity'] ?? 0 ) ) ),
				'reason'   => sanitize_text_field( $exception['reason'] ?? '' ),
			);
		}
		return $out;
	}

	public static function valid_date( $value ) {
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d', (string) $value, new DateTimeZone( 'UTC' ) );
		return $date && $date->format( 'Y-m-d' ) === (string) $value;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_clinic( $data, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$auth = WCA_Authorization::can_create_clinic( $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		$step = WCA_Authorization::require_step_up( 'create_clinic', $actor_user_id );
		if ( is_wp_error( $step ) ) { return $step; }
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$data['status']             = 'draft';
		$data['owner_user_id']      = $actor_user_id;
		$data['owner_subject_uuid'] = $claims['subject_uuid'];
		$result = WCA_Repository::transaction( function () use ( $data, $actor_user_id ) {
			$clinic = WCA_Repository::create_clinic( $data );
			if ( is_wp_error( $clinic ) ) { return $clinic; }
			$trace = WCA_Observability::trace_id();
			$event = WCA_Repository::append_event( 'ClinicCreated.v1', 'clinic', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'status' => $clinic['status'] ), $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			$queued = WCA_Repository::enqueue( 'File24.AssuranceEvidenceRequested.v1', $clinic['public_ref'], array( 'entity' => 'clinic', 'entity_ref' => $clinic['public_ref'], 'change' => 'created' ), $trace );
			return is_wp_error( $queued ) ? $queued : $clinic;
		}, 'wca_clinic_create_transaction' );
		if ( ! is_wp_error( $result ) ) { WCA_Observability::metric( 'clinic_created_total', 1 ); }
		return $result;
	}



	/** @return array<string,mixed>|WP_Error */
	public static function submit_clinic_for_review( $clinic_id, $expected_version, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( $clinic_id, false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		if ( 'draft' !== (string) $clinic['status'] ) { return new WP_Error( 'wca_clinic_review_state', __( 'Only a draft clinic may be submitted for institutional review.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! WCA_Repository::list_services( $clinic['id'], false ) || ! WCA_Repository::list_branches( $clinic['id'], false ) ) { return new WP_Error( 'wca_clinic_incomplete', __( 'At least one branch and one service are required before review.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		return WCA_Repository::transaction( function () use ( $clinic_id, $expected_version, $actor_user_id ) {
			$updated = WCA_Repository::update_clinic( $clinic_id, $expected_version, array( 'status' => 'review' ) );
			if ( is_wp_error( $updated ) ) { return $updated; }
			$current = WCA_Repository::get_clinic( $clinic_id, false );
			if ( ! $current ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found after update.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$trace = WCA_Observability::trace_id();
			$event = WCA_Repository::append_event( 'ClinicReviewRequested.v1', 'clinic', $current['public_ref'], array( 'clinic_ref' => $current['public_ref'], 'trace_id' => $trace ), $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			$queued = WCA_Repository::enqueue( 'ClinicReviewRequested.v1', $current['public_ref'], array( 'clinic_ref' => $current['public_ref'] ), $trace );
			return is_wp_error( $queued ) ? $queued : $current;
		}, 'wca_clinic_review_transaction' );
	}


	/** @return array<string,mixed>|WP_Error */
	public static function activate_clinic( $clinic_id, $expected_version, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( $clinic_id, false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$reviewer = ! empty( $claims['founder'] ) || user_can( $actor_user_id, 'manage_wca_clinics' ) || user_can( $actor_user_id, 'manage_worldwide_clinic' );
		if ( ! $reviewer ) { return new WP_Error( 'wca_clinic_reviewer_required', __( 'Institutional reviewer authority is required to activate a clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$step = WCA_Authorization::require_step_up( 'activate_clinic', $actor_user_id );
		if ( is_wp_error( $step ) ) { return $step; }
		if ( ! SWC_Doctor_Authority::is_eligible( absint( $clinic['owner_user_id'] ?? 0 ) ) ) { return new WP_Error( 'wca_clinic_owner_ineligible', __( 'The clinic owner is not currently eligible for activation.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! in_array( $clinic['status'], array( 'review','paused' ), true ) ) { return new WP_Error( 'wca_clinic_transition', __( 'Clinic cannot be activated from its current state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! WCA_Repository::list_services( $clinic['id'], true ) || ! WCA_Repository::list_branches( $clinic['id'], true ) ) { return new WP_Error( 'wca_clinic_incomplete', __( 'At least one active public branch and one active eligible service are required before activation.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		return WCA_Repository::transaction( function () use ( $clinic_id, $expected_version, $actor_user_id ) {
			$updated = WCA_Repository::update_clinic( $clinic_id, $expected_version, array( 'status' => 'active' ) );
			if ( is_wp_error( $updated ) ) { return $updated; }
			$current = WCA_Repository::get_clinic( $clinic_id, false );
			if ( ! $current ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found after activation.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			$trace = WCA_Observability::trace_id();
			$payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'clinic_ref' => $current['public_ref'], 'owner_subject_uuid' => $current['owner_subject_uuid'], 'trace_id' => $trace );
			$event = WCA_Repository::append_event( 'ClinicActivated.v1', 'clinic', $current['public_ref'], $payload, $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			$queued = WCA_Repository::enqueue( 'ClinicActivated.v1', $current['public_ref'], array( 'clinic_ref' => $current['public_ref'], 'owner_subject_uuid' => $current['owner_subject_uuid'] ), $trace );
			return is_wp_error( $queued ) ? $queued : $current;
		}, 'wca_clinic_activate_transaction' );
	}


	/** A globally eligible doctor still requires a current clinic-serving relationship. */
	private static function doctor_may_serve_clinic( $clinic, $doctor_id, $actor_user_id ) {
		return WCA_Authorization::doctor_can_serve_clinic( $clinic, $doctor_id, $actor_user_id );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_branch( $data, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( absint( $data['clinic_id'] ?? 0 ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		$timezone = (string) ( $data['timezone'] ?? '' );
		if ( ! self::valid_timezone( $timezone ) ) { return new WP_Error( 'wca_branch_timezone', __( 'A valid IANA time zone is required for the branch.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$data['timezone'] = $timezone;
		return WCA_Repository::transaction( function () use ( $data, $clinic, $actor_user_id ) {
			$branch = WCA_Repository::create_branch( $data );
			if ( is_wp_error( $branch ) ) { return $branch; }
			$trace = WCA_Observability::trace_id();
			$payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'clinic_ref' => (string) $clinic['public_ref'], 'branch_ref' => (string) $branch['public_ref'], 'change' => 'branch_created', 'trace_id' => $trace );
			$event = WCA_Repository::append_event( 'ClinicBranchChanged.v1', 'branch', $branch['public_ref'], $payload, $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			foreach ( array(
				array( 'ClinicBranchChanged.v1', $branch['public_ref'], $payload ),
				array( 'File26.SearchProjectionChanged.v1', $clinic['public_ref'], array( 'entity' => 'clinic', 'entity_ref' => (string) $clinic['public_ref'], 'change' => 'branch_created', 'trace_id' => $trace ) ),
			) as $outbox ) {
				$queued = WCA_Repository::enqueue( $outbox[0], $outbox[1], $outbox[2], $trace );
				if ( is_wp_error( $queued ) ) { return $queued; }
			}
			return $branch;
		}, 'wca_branch_create_transaction' );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function save_service( $data, $service_id = 0, $expected_version = 0, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( absint( $data['clinic_id'] ?? 0 ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		$current = null;
		if ( $service_id ) {
			$current = WCA_Repository::get_service( $service_id, false );
			if ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		}
		$consultation_type = sanitize_key( $data['consultation_type'] ?? ( $current['consultation_type'] ?? '' ) );
		if ( ! in_array( $consultation_type, array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ) { return new WP_Error( 'wca_service_consultation_type', __( 'A valid consultation type is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$currency_raw = trim( (string) ( $data['currency'] ?? ( $current['currency'] ?? '' ) ) );
		$currency = strtoupper( $currency_raw );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { return new WP_Error( 'wca_service_currency', __( 'A valid three-letter currency code is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$duration = self::strict_int( array_key_exists( 'duration_minutes', $data ) ? $data['duration_minutes'] : ( $current['duration_minutes'] ?? 30 ), 10, 480 );
		$fee_minor = self::strict_int( array_key_exists( 'fee_minor', $data ) ? $data['fee_minor'] : ( $current['fee_minor'] ?? 0 ), 0, PHP_INT_MAX );
		$fee_max_minor = self::strict_int( array_key_exists( 'fee_max_minor', $data ) ? $data['fee_max_minor'] : ( $current['fee_max_minor'] ?? 0 ), 0, PHP_INT_MAX );
		if ( null === $duration ) { return new WP_Error( 'wca_service_duration_range', __( 'Service duration must be an integer from 10 through 480 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( null === $fee_minor || null === $fee_max_minor || ( $fee_max_minor && $fee_max_minor < $fee_minor ) ) { return new WP_Error( 'wca_service_fee_range', __( 'Service fees must be non-negative integers and the maximum fee cannot be below the minimum fee.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$data['consultation_type'] = $consultation_type;
		$data['currency'] = $currency;
		$data['duration_minutes'] = $duration;
		$data['fee_minor'] = $fee_minor;
		$data['fee_max_minor'] = $fee_max_minor;
		if ( ! empty( $data['branch_id'] ) ) { $branch = WCA_Repository::get_branch( absint( $data['branch_id'] ) ); if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_branch_scope', __( 'The branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }
		if ( ! empty( $data['doctor_user_id'] ) ) {
			$service_doctor_id = absint( $data['doctor_user_id'] );
			if ( ! SWC_Doctor_Authority::is_eligible( $service_doctor_id ) ) { return new WP_Error( 'wca_service_doctor', __( 'The assigned practitioner is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			if ( ! self::doctor_may_serve_clinic( $clinic, $service_doctor_id, $actor_user_id ) ) { return new WP_Error( 'wca_service_doctor_scope', __( 'The selected doctor has no current authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		}
		$data['clinic_id'] = absint( $clinic['id'] );
		$data['platform_commission_bps'] = 0;
		return WCA_Repository::transaction( function () use ( $data, $service_id, $expected_version, $clinic, $actor_user_id ) {
			$result = WCA_Repository::save_service( $data, $service_id, $expected_version );
			if ( is_wp_error( $result ) ) { return $result; }
			$trace = WCA_Observability::trace_id();
			$event = WCA_Repository::append_event( $service_id ? 'ClinicServiceUpdated.v1' : 'ClinicServiceCreated.v1', 'clinic_service', $result['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'service_ref' => $result['public_ref'], 'commission_bps' => 0 ), $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			$queued = WCA_Repository::enqueue( 'ClinicServiceChanged.v1', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'service_ref' => $result['public_ref'] ), $trace );
			return is_wp_error( $queued ) ? $queued : $result;
		}, 'wca_service_mutation_transaction' );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function set_availability( $data, $rule_id = 0, $expected_version = 0, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( absint( $data['clinic_id'] ?? 0 ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		$timezone = (string) ( $data['timezone'] ?? '' );
		if ( ! self::valid_timezone( $timezone ) ) { return new WP_Error( 'wca_availability_timezone', __( 'A valid IANA time zone is required for availability.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$rrule = (array) ( $data['rrule'] ?? array() );
		if ( ! self::valid_hhmm( $rrule['start'] ?? '' ) || ! self::valid_hhmm( $rrule['end'] ?? '' ) || (string) $rrule['end'] <= (string) $rrule['start'] ) { return new WP_Error( 'wca_availability_window', __( 'Availability requires a valid daily start and end time.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( isset( $rrule['effective_from'] ) && ! self::valid_date( $rrule['effective_from'] ) ) { return new WP_Error( 'wca_availability_effective_from', __( 'Availability effective-from date is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( isset( $rrule['effective_until'] ) && ! self::valid_date( $rrule['effective_until'] ) ) { return new WP_Error( 'wca_availability_effective_until', __( 'Availability effective-until date is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( ! empty( $rrule['effective_from'] ) && ! empty( $rrule['effective_until'] ) && (string) $rrule['effective_until'] < (string) $rrule['effective_from'] ) { return new WP_Error( 'wca_availability_effective_range', __( 'Availability effective dates are reversed.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$interval = self::strict_int( $rrule['interval_minutes'] ?? 30, 10, 1440 );
		$buffer_before = self::strict_int( $data['buffer_before'] ?? 0, 0, 240 );
		$buffer_after = self::strict_int( $data['buffer_after'] ?? 0, 0, 240 );
		$capacity = self::strict_int( $data['capacity'] ?? 1, 1, 50 );
		if ( null === $interval ) { return new WP_Error( 'wca_availability_interval_range', __( 'Availability interval must be an integer from 10 through 1440 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( null === $buffer_before || null === $buffer_after ) { return new WP_Error( 'wca_availability_buffer_range', __( 'Availability buffers must be integers from 0 through 240 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( null === $capacity ) { return new WP_Error( 'wca_availability_capacity_range', __( 'Availability capacity must be an integer from 1 through 50.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$rrule['interval_minutes'] = $interval;
		$data['rrule'] = $rrule;
		$data['buffer_before'] = $buffer_before;
		$data['buffer_after'] = $buffer_after;
		$data['capacity'] = $capacity;
		$data['timezone'] = $timezone;
		if ( $rule_id ) { $current = WCA_Repository::get_availability_rule( $rule_id ); if ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_scope', __( 'The availability rule does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); } }
		if ( ! empty( $data['service_id'] ) ) { $service = WCA_Repository::get_service( absint( $data['service_id'] ), false ); if ( ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_service', __( 'The availability service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }
		if ( ! empty( $data['branch_id'] ) ) { $branch = WCA_Repository::get_branch( absint( $data['branch_id'] ) ); if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_branch', __( 'The availability branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }
		$doctor_id = absint( $data['doctor_user_id'] ?? $actor_user_id );
		if ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
			return new WP_Error( 'wca_doctor_ineligible', __( 'The doctor is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		if ( ! self::doctor_may_serve_clinic( $clinic, $doctor_id, $actor_user_id ) ) {
			return new WP_Error( 'wca_availability_doctor_scope', __( 'The selected doctor has no current authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$data['doctor_user_id'] = $doctor_id;
		return WCA_Repository::transaction( function () use ( $data, $rule_id, $expected_version, $clinic, $doctor_id, $actor_user_id ) {
			$result = WCA_Repository::save_availability_rule( $data, $rule_id, $expected_version );
			if ( is_wp_error( $result ) ) { return $result; }
			$trace = WCA_Observability::trace_id();
			$subject = WCA_Authorization::subject_uuid( $doctor_id );
			$payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'clinic_ref' => $clinic['public_ref'], 'doctor_subject_uuid' => $subject, 'version' => absint( $result['version'] ), 'trace_id' => $trace );
			$event = WCA_Repository::append_event( 'ClinicAvailabilityChanged.v1', 'availability_rule', $result['public_ref'], $payload, $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			$queued = WCA_Repository::enqueue( 'ClinicAvailabilityChanged.v1', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'doctor_subject_uuid' => $subject, 'version' => absint( $result['version'] ) ), $trace );
			return is_wp_error( $queued ) ? $queued : $result;
		}, 'wca_availability_mutation_transaction' );
	}

	/**
	 * Generate fresh bounded slots. Slots are projections; canonical truth remains
	 * availability rules + appointments + atomic holds.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function search_slots( $args ) {
		$doctor_id = absint( $args['doctor_user_id'] ?? 0 );
		$service_id = absint( $args['service_id'] ?? 0 );
		$clinic_id = absint( $args['clinic_id'] ?? 0 );
		$timezone = (string) ( $args['timezone'] ?? 'UTC' );
		if ( ! $doctor_id || ! self::valid_timezone( $timezone ) || ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
			return new WP_Error( 'wca_slot_query', __( 'Valid doctor and time zone are required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		if ( isset( $args['date_from'] ) && '' !== (string) $args['date_from'] && ! self::valid_date( $args['date_from'] ) ) { return new WP_Error( 'wca_slot_date_from_invalid', __( 'The slot-search start date is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( isset( $args['date_to'] ) && '' !== (string) $args['date_to'] && ! self::valid_date( $args['date_to'] ) ) { return new WP_Error( 'wca_slot_date_to_invalid', __( 'The slot-search end date is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$from = ! empty( $args['date_from'] ) ? (string) $args['date_from'] : gmdate( 'Y-m-d' );
		$to   = ! empty( $args['date_to'] ) ? (string) $args['date_to'] : gmdate( 'Y-m-d', time() + 30 * DAY_IN_SECONDS );
		$from_date = new DateTimeImmutable( $from, new DateTimeZone( 'UTC' ) );
		$to_date   = new DateTimeImmutable( $to, new DateTimeZone( 'UTC' ) );
		if ( $to_date < $from_date || $to_date->diff( $from_date )->days > self::SLOT_HORIZON_DAYS ) {
			return new WP_Error( 'wca_slot_range', __( 'Slot search range is invalid or too large.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		WCA_Repository::clear_read_error();
		$service = $service_id ? WCA_Repository::get_service( $service_id, true ) : null;
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( $service_id && ( ! $service || ( $clinic_id && absint( $service['clinic_id'] ) !== $clinic_id ) ) ) {
			return new WP_Error( 'wca_slot_service_scope', __( 'The requested service is not active for this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		WCA_Repository::clear_read_error();
		$rules = WCA_Repository::list_availability_rules( $doctor_id, $service_id, $clinic_id );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( ! $rules ) {
			return array( 'slots' => array(), 'freshness_version' => hash( 'sha256', 'none|' . $doctor_id . '|' . $clinic_id . '|' . $service_id ), 'generated_at_utc' => gmdate( 'c' ) );
		}
		$duration = $service ? absint( $service['duration_minutes'] ) : absint( $args['duration_minutes'] ?? 30 );
		$duration = min( 480, max( 10, $duration ) );
		$limit = min( 500, max( 1, absint( $args['limit'] ?? 100 ) ) );
		$display_from = self::valid_date( $args['display_date_from'] ?? '' ) ? (string) $args['display_date_from'] : '';
		$display_to   = self::valid_date( $args['display_date_to'] ?? '' ) ? (string) $args['display_date_to'] : '';
		if ( ( $display_from && ! $display_to ) || ( $display_to && ! $display_from ) || ( $display_from && $display_to < $display_from ) ) {
			return new WP_Error( 'wca_slot_display_range', __( 'The display-local slot range is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$slots = array();
		$versions = array();
		foreach ( $rules as $rule ) {
			$versions[] = $rule['public_ref'] . ':' . $rule['version'];
			WCA_Repository::clear_read_error();
			$generated = self::generate_rule_slots( $rule, $from, $to, $duration, $timezone, $limit, $display_from, $display_to );
			$projection_read_error = WCA_Repository::consume_read_error();
			if ( is_wp_error( $projection_read_error ) ) { return $projection_read_error; }
			$slots = array_merge( $slots, $generated );
		}
		usort( $slots, static function ( $a, $b ) { return strcmp( $a['start_utc'], $b['start_utc'] ); } );
		WCA_Observability::metric( 'slot_search_total', 1, array( 'result_bucket' => count( $slots ) ? 'non_empty' : 'empty' ) );
		return array(
			'slots'             => array_slice( $slots, 0, $limit ),
			'freshness_version' => hash( 'sha256', implode( '|', $versions ) . '|' . WCA_Repository::now() . '|' . $doctor_id . '|' . $clinic_id . '|' . $service_id ),
			'generated_at_utc'  => gmdate( 'c' ),
			'timezone'          => $timezone,
		);
	}

	/** @return array<int,array<string,mixed>> */
	private static function generate_rule_slots( $rule, $from, $to, $duration, $display_timezone, $limit, $display_from = '', $display_to = '', $ignore_hold_key = '' ) {
		$slots = array();
		if ( $limit <= 0 || empty( $rule['rrule']['days'] ) ) { return $slots; }
		$rule_zone = new DateTimeZone( $rule['timezone'] );
		$display_zone = new DateTimeZone( $display_timezone );
		$cursor = new DateTimeImmutable( $from . ' 00:00:00', $rule_zone );
		$end_date = new DateTimeImmutable( $to . ' 23:59:59', $rule_zone );
		$effective_from = new DateTimeImmutable( $rule['rrule']['effective_from'] . ' 00:00:00', $rule_zone );
		$effective_until= new DateTimeImmutable( $rule['rrule']['effective_until'] . ' 23:59:59', $rule_zone );
		$days = array_flip( $rule['rrule']['days'] );
		$interval = max( 10, absint( $rule['rrule']['interval_minutes'] ?? $duration ) );
		$clinic = WCA_Repository::get_clinic( absint( $rule['clinic_id'] ), false );
		$branch = absint( $rule['branch_id'] ) ? WCA_Repository::get_branch( absint( $rule['branch_id'] ) ) : null;
		$service = absint( $rule['service_id'] ) ? WCA_Repository::get_service( absint( $rule['service_id'] ), false ) : null;
		$practitioner_ref = WCA_Plan_Guard::practitioner_ref( absint( $rule['doctor_user_id'] ) );
		if ( ! $clinic || ! $practitioner_ref ) { return array(); }
		while ( $cursor <= $end_date && count( $slots ) < $limit ) {
			$day_key = strtolower( $cursor->format( 'l' ) );
			$date_key = $cursor->format( 'Y-m-d' );
			$exception = self::exception_for_date( $rule['exceptions'], $date_key );
			$closed = $exception && 'closed' === $exception['type'];
			$eligible_day = isset( $days[ $day_key ] ) && $cursor >= $effective_from && $cursor <= $effective_until && ! $closed;
			if ( $eligible_day ) {
				$start_hhmm = $exception && 'open' === $exception['type'] && $exception['start'] ? $exception['start'] : $rule['rrule']['start'];
				$end_hhmm   = $exception && 'open' === $exception['type'] && $exception['end'] ? $exception['end'] : $rule['rrule']['end'];
				$slot = self::local_datetime( $date_key, $start_hhmm, $rule_zone );
				$day_end = self::local_datetime( $date_key, $end_hhmm, $rule_zone );
				if ( $slot && $day_end ) {
					while ( $slot->modify( '+' . $duration . ' minutes' ) <= $day_end && count( $slots ) < $limit ) {
						$slot_end = $slot->modify( '+' . $duration . ' minutes' );
						$start_utc = $slot->setTimezone( new DateTimeZone( 'UTC' ) );
						$end_utc = $slot_end->setTimezone( new DateTimeZone( 'UTC' ) );
						$display_date = $start_utc->setTimezone( $display_zone )->format( 'Y-m-d' );
						$inside_display = ( ! $display_from || ( $display_date >= $display_from && $display_date <= $display_to ) );
						$buffer_before = max( 0, absint( $rule['buffer_before'] ?? 0 ) );
						$buffer_after  = max( 0, absint( $rule['buffer_after'] ?? 0 ) );
						$conflict_start = $start_utc->modify( '-' . $buffer_before . ' minutes' );
						$conflict_end   = $end_utc->modify( '+' . $buffer_after . ' minutes' );
						$conflict_minutes = max( 1, (int) ceil( ( $conflict_end->getTimestamp() - $conflict_start->getTimestamp() ) / 60 ) );
						if ( $inside_display && $start_utc->getTimestamp() > time() + $buffer_before * 60 && ! self::in_break( $slot, $slot_end, $rule['breaks'] ) && ! SWC_Helpers::has_conflict( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_minutes, 0 ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key ) ) {
							$slots[] = array(
								'slot_ref'       => hash( 'sha256', $rule['public_ref'] . '|' . $start_utc->format( 'c' ) . '|' . $duration ),
								'rule_ref'       => $rule['public_ref'],
								'clinic_ref'      => (string) $clinic['public_ref'],
								'branch_ref'      => $branch ? (string) $branch['public_ref'] : '',
								'service_ref'     => $service ? (string) $service['public_ref'] : '',
								'practitioner_ref'=> $practitioner_ref,
								'start_utc'      => $start_utc->format( 'Y-m-d H:i:s' ),
								'end_utc'        => $end_utc->format( 'Y-m-d H:i:s' ),
								'start_local'    => $start_utc->setTimezone( $display_zone )->format( 'Y-m-d H:i' ),
								'end_local'      => $end_utc->setTimezone( $display_zone )->format( 'Y-m-d H:i' ),
								'timezone'       => $display_timezone,
								'duration_minutes'=> $duration,
								'capacity'        => absint( $rule['capacity'] ),
								'freshness_version'=> absint( $rule['version'] ),
							);
						}
						$slot = $slot->modify( '+' . $interval . ' minutes' );
					}
				}
			}
			$cursor = $cursor->modify( '+1 day' );
		}
		return $slots;
	}

	private static function local_datetime( $date, $time, DateTimeZone $zone ) {
		/* Reuse the canonical ambiguity-aware converter: nonexistent and repeated DST wall times fail closed. */
		$utc = SWC_Helpers::to_utc( (string) $date, (string) $time, $zone->getName() );
		if ( ! $utc ) { return null; }
		$value = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc, new DateTimeZone( 'UTC' ) );
		return $value ? $value->setTimezone( $zone ) : null;
	}

	private static function exception_for_date( $exceptions, $date ) {
		foreach ( (array) $exceptions as $exception ) {
			if ( ( $exception['date'] ?? '' ) === $date ) { return $exception; }
		}
		return null;
	}

	private static function in_break( DateTimeImmutable $start, DateTimeImmutable $end, $breaks ) {
		foreach ( (array) $breaks as $break ) {
			$break_start = self::local_datetime( $start->format( 'Y-m-d' ), $break['start'] ?? '', $start->getTimezone() );
			$break_end   = self::local_datetime( $start->format( 'Y-m-d' ), $break['end'] ?? '', $start->getTimezone() );
			if ( $break_start && $break_end && $start < $break_end && $end > $break_start ) { return true; }
		}
		return false;
	}

	private static function has_active_hold( $doctor_id, $start_utc, $end_utc, $ignore_idempotency_key = '' ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		if ( $ignore_idempotency_key ) {
			$hold_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND idempotency_key<>%s AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1", absint( $doctor_id ), sanitize_text_field( $ignore_idempotency_key ), WCA_Repository::now(), $end_utc, $start_utc ) );
		} else {
			$hold_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1", absint( $doctor_id ), WCA_Repository::now(), $end_utc, $start_utc ) );
		}
		if ( '' !== (string) $wpdb->last_error ) { return true; }
		return (bool) $hold_id;
	}

	/** Reproject one exact rule/day slot without enumerating an arbitrary first-N global projection. */
	public static function project_rule_slot( $rule, $start_utc, $end_utc, $duration, $display_timezone = 'UTC', $ignore_idempotency_key = '' ) {
		if ( ! is_array( $rule ) || ! self::valid_timezone( $rule['timezone'] ?? '' ) || ! self::valid_timezone( $display_timezone ) ) { return null; }
		$duration = min( 480, max( 10, absint( $duration ) ) );
		try {
			$start = new DateTimeImmutable( (string) $start_utc, new DateTimeZone( 'UTC' ) );
			$local_date = $start->setTimezone( new DateTimeZone( (string) $rule['timezone'] ) )->format( 'Y-m-d' );
		} catch ( Exception $e ) { return null; }
		$slots = self::generate_rule_slots( $rule, $local_date, $local_date, $duration, $display_timezone, 200, '', '', $ignore_idempotency_key );
		foreach ( $slots as $slot ) {
			if ( (string) ( $slot['start_utc'] ?? '' ) === (string) $start_utc && (string) ( $slot['end_utc'] ?? '' ) === (string) $end_utc ) { return $slot; }
		}
		return null;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function hold_slot( $data, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$patient_user_id = absint( $data['patient_user_id'] ?? $actor_user_id );
		$guardian_user_id = absint( $data['guardian_user_id'] ?? 0 );
		$guardian = WCA_Authorization::guardian_context( $patient_user_id, $guardian_user_id, $actor_user_id );
		if ( is_wp_error( $guardian ) ) { return $guardian; }
		$idempotency_key = sanitize_text_field( $data['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $idempotency_key ) ) {
			return new WP_Error( 'wca_idempotency_required', __( 'A valid idempotency key is required to hold a slot.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$data['idempotency_key'] = $idempotency_key;
		$canonical = WCA_Plan_Guard::canonical_slot_hold( $data, $patient_user_id );
		if ( is_wp_error( $canonical ) ) { return $canonical; }
		return WCA_Repository::hold_slot( $canonical );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function request_appointment( $data, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$patient_user_id = absint( $data['patient_user_id'] ?? $actor_user_id );
		$guardian_user_id= absint( $data['guardian_user_id'] ?? 0 );
		$guardian = WCA_Authorization::guardian_context( $patient_user_id, $guardian_user_id, $actor_user_id );
		if ( is_wp_error( $guardian ) ) { return $guardian; }
		$patient_timezone = isset( $data['timezone'] ) && '' !== trim( (string) $data['timezone'] ) ? (string) $data['timezone'] : SWC_Helpers::user_timezone( $patient_user_id );
		if ( ! self::valid_timezone( $patient_timezone ) ) { return new WP_Error( 'wca_patient_timezone_invalid', __( 'A valid IANA time zone is required when a patient time zone is supplied.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$red_flag = self::emergency_red_flag( (string) ( $data['reason'] ?? '' ), (string) ( $data['category'] ?? '' ) );
		if ( $red_flag ) {
			WCA_Observability::metric( 'emergency_diversion_total', 1, array( 'category' => $red_flag['category'] ) );
			return new WP_Error( 'wca_emergency_diversion', $red_flag['message'], array( 'status' => 422, 'emergency' => true, 'category' => $red_flag['category'] ) );
		}
		if ( ! self::affirmative( $data['privacy_consent'] ?? null ) ) { return new WP_Error( 'wca_privacy_consent_required', __( 'Current appointment-processing and privacy consent is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		if ( ! self::affirmative( $data['emergency_acknowledged'] ?? null ) ) { return new WP_Error( 'wca_emergency_ack_required', __( 'You must acknowledge that this booking service is not emergency care.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$idempotency_key = sanitize_text_field( $data['idempotency_key'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $idempotency_key ) ) { return new WP_Error( 'wca_idempotency_required', __( 'A valid 8-128 character idempotency key is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$hold = WCA_Repository::get_slot_hold( (string) ( $data['hold_token'] ?? '' ) );
		if ( is_wp_error( $hold ) ) { return $hold; }
		$hold_check = WCA_Plan_Guard::validate_bookable_hold( $hold, $patient_user_id );
		if ( is_wp_error( $hold_check ) ) { return $hold_check; }
		$doctor_id = absint( $hold['doctor_user_id'] );
		if ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
			return new WP_Error( 'wca_doctor_ineligible', __( 'The selected doctor is no longer eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		WCA_Repository::clear_read_error();
		$service = $hold['service_id'] ? WCA_Repository::get_service( $hold['service_id'], true ) : null;
		$service_read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $service_read_error ) ) { return $service_read_error; }
		WCA_Repository::clear_read_error();
		$clinic  = WCA_Repository::get_clinic( $hold['clinic_id'], true );
		$clinic_read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $clinic_read_error ) ) { return $clinic_read_error; }
		if ( ! $service ) { return new WP_Error( 'wca_service_unavailable', __( 'The appointment service is no longer available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_unavailable', __( 'The clinic is not currently available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$type = sanitize_key( $service['consultation_type'] ?? '' );
		$remote = in_array( $type, array( 'online', 'hybrid' ), true );
		if ( $remote && ! self::affirmative( $data['telehealth_consent'] ?? null ) ) { return new WP_Error( 'wca_teleconsult_consent_required', __( 'Explicit remote-consultation consent is required for the selected online or hybrid service.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }

		$claim = WCA_Repository::claim_idempotency( 'request_appointment', $idempotency_key, $actor_user_id, self::appointment_request_fingerprint( $data ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === ( $claim['status'] ?? '' ) ) { return $claim['response']; }
		if ( empty( $claim['claimed_new'] ) ) {
			return new WP_Error( 'wca_idempotency_in_progress', __( 'This appointment request is already being processed. Retry with the same idempotency key shortly.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) );
		}


		$created_appointment_id = 0;
		$result = WCA_Repository::transaction( function () use ( $patient_user_id, $guardian_user_id, $actor_user_id, $claims, $hold, $service, $clinic, $remote, $data, $idempotency_key, $claim, $patient_timezone, &$created_appointment_id ) {
			$appointment_id = wp_insert_post(
				array(
					'post_type'   => SWC_Helpers::TYPE,
					'post_status' => 'private',
					'post_author' => $patient_user_id,
					'post_title'  => sprintf( 'Appointment %s', gmdate( 'Y-m-d H:i', strtotime( $hold['start_utc'] . ' UTC' ) ) ),
				),
				true
			);
			if ( is_wp_error( $appointment_id ) ) { return $appointment_id; }
			$created_appointment_id = absint( $appointment_id );
			$public_ref = WCA_Repository::uuid();
			$meta = array(
				'public_ref'             => $public_ref,
				'patient_user_id'        => $patient_user_id,
				'guardian_user_id'       => $guardian_user_id,
				'doctor_id'              => absint( $hold['doctor_user_id'] ),
				'clinic_id'              => absint( $hold['clinic_id'] ),
				'service_id'             => absint( $hold['service_id'] ),
				'branch_id'              => absint( $hold['branch_id'] ?? 0 ),
				'status'                 => 'requested',
				'preferred_at_utc'       => $hold['start_utc'],
				'appointment_end_utc'    => $hold['end_utc'],
				'patient_timezone'       => $patient_timezone,
				'consultation_type'      => $service['consultation_type'] ?? sanitize_key( $data['consultation_type'] ?? 'online' ),
				'appointment_duration'   => $service['duration_minutes'] ?? absint( ( strtotime( $hold['end_utc'] ) - strtotime( $hold['start_utc'] ) ) / 60 ),
				'service_public_ref_snapshot' => (string) $service['public_ref'],
				'service_version_snapshot'    => absint( $service['version'] ),
				'fee_currency_snapshot'       => (string) $service['currency'],
				'fee_amount_minor_snapshot'   => absint( $service['fee_minor'] ),
				'fee_max_minor_snapshot'       => absint( $service['fee_max_minor'] ),
				'tax_policy_snapshot'          => (string) $service['tax_policy'],
				'refund_policy_snapshot'       => (string) $service['refund_policy'],
				'cancellation_policy_snapshot' => (string) $service['cancellation_policy'],
				'platform_commission_bps_snapshot' => 0,
				'reason_category'        => sanitize_key( $data['category'] ?? 'general' ),
				'reason'                 => SWC_Helpers::limit_text( $data['reason'] ?? '', 500, true ),
				'consent_version'        => self::TERMS_VERSION,
				'consent_at'             => WCA_Repository::now(),
				'record_version'         => 1,
				'created_via'            => 'wca_command',
				'idempotency_key_hash'   => hash( 'sha256', $idempotency_key ),
			);
			foreach ( $meta as $key => $value ) {
				$meta_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_' . $key, $value, 'wca_appointment_meta_write' );
				if ( is_wp_error( $meta_write ) ) { return $meta_write; }
			}
			$booked = WCA_Repository::book_slot( $hold['hold_token'], $appointment_id );
			if ( is_wp_error( $booked ) ) { return $booked; }
			$consent = WCA_Repository::record_consent( array(
				'appointment_id'     => $appointment_id,
				'actor_user_id'      => $actor_user_id,
				'actor_subject_uuid' => $claims['subject_uuid'],
				'guardian_user_id'   => $guardian_user_id,
				'scope'              => 'appointment_processing',
				'terms_version'      => self::TERMS_VERSION,
				'terms_text'         => self::appointment_terms_text(),
				'legal_basis'        => 'consent',
				'metadata'           => array( 'telehealth' => $remote ? true : false, 'privacy' => true, 'emergency_acknowledged' => true ),
			) );
			if ( is_wp_error( $consent ) ) { return $consent; }
			$context_scopes = array( 'privacy_notice' );
			if ( $remote ) { $context_scopes[] = 'teleconsult'; }
			foreach ( $context_scopes as $context_scope ) {
				$context_consent = WCA_Repository::record_consent( array(
					'appointment_id'     => $appointment_id,
					'actor_user_id'      => $actor_user_id,
					'actor_subject_uuid' => $claims['subject_uuid'],
					'guardian_user_id'   => $guardian_user_id,
					'scope'              => $context_scope,
					'terms_version'      => self::TERMS_VERSION,
					'terms_text'         => 'wca-context:' . $context_scope . ':' . self::TERMS_VERSION,
					'legal_basis'        => 'consent',
					'metadata'           => array( 'source' => 'appointment_owner_transaction', 'privacy' => true, 'telehealth' => $remote ),
				) );
				if ( is_wp_error( $context_consent ) ) { return $context_consent; }
			}
			$trace = WCA_Observability::trace_id();
			$payload = array(
				'event_id'             => WCA_Repository::uuid(),
				'occurred_at'          => gmdate( 'c' ),
				'appointment_ref'      => $public_ref,
				'patient_subject_uuid' => WCA_Authorization::subject_uuid( $patient_user_id ),
				'doctor_subject_uuid'  => WCA_Authorization::subject_uuid( absint( $hold['doctor_user_id'] ) ),
				'clinic_ref'           => $clinic['public_ref'],
				'scheduled_at_utc'     => $hold['start_utc'],
				'consultation_type'    => $meta['consultation_type'],
				'trace_id'             => $trace,
			);
			$event = WCA_Repository::append_event( 'AppointmentRequested.v1', 'appointment', $public_ref, $payload, $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			foreach ( array(
				array( 'AppointmentRequested.v1', $payload ),
				array( 'File19.NotificationRequested.v1', array( 'event' => 'appointment_requested', 'appointment_ref' => $public_ref, 'recipients' => array( $patient_user_id, absint( $hold['doctor_user_id'] ) ) ) ),
				array( 'File17.AppointmentContextChanged.v1', self::file17_context_payload( $appointment_id ) ),
			) as $outbox ) {
				$queued = WCA_Repository::enqueue( $outbox[0], $public_ref, $outbox[1], $trace );
				if ( is_wp_error( $queued ) ) { return $queued; }
			}
			if ( ! SWC_Helpers::audit( $appointment_id, 'appointment-requested', array( 'new_status' => 'requested', 'details' => array( 'public_ref' => $public_ref, 'clinic_id' => absint( $hold['clinic_id'] ), 'service_id' => absint( $hold['service_id'] ) ) ) ) ) {
				return new WP_Error( 'wca_appointment_request_audit', __( 'The appointment request could not be audited safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
			}
			$response = array( 'appointment_id' => $appointment_id, 'public_ref' => $public_ref, 'status' => 'requested', 'trace_id' => $trace );
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], 201, $response ) ) {
				return new WP_Error( 'wca_appointment_idempotency_complete', __( 'The appointment request could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
			}
			return $response;
		}, 'wca_appointment_request_transaction' );
		if ( is_wp_error( $result ) ) {
			WCA_Repository::release_idempotency( $claim['id'] );
			if ( $created_appointment_id ) { clean_post_cache( $created_appointment_id ); }
			return $result;
		}
		WCA_Observability::metric( 'appointment_requested_total', 1, array( 'mode' => $service['consultation_type'] ?? 'unknown' ) );
		return $result;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function transition_appointment( $appointment_id, $next, $data = array(), $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		if ( ! WCA_Contracts::is_appointment_status( $next, true ) ) {
			return new WP_Error( 'wca_invalid_status', __( 'A valid target status is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$next = WCA_Contracts::normalize_appointment_status( $next );
		$auth = WCA_Authorization::can_transition_appointment( $appointment_id, $next, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		if ( empty( $data['expected_status'] ) || ! isset( $data['expected_version'] ) || absint( $data['expected_version'] ) < 1 ) {
			return new WP_Error( 'wca_transition_precondition_required', __( 'Current appointment status and positive record version are required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$raw_expected_status = strtolower( trim( (string) $data['expected_status'] ) );
		if ( ! WCA_Contracts::is_appointment_status( $raw_expected_status, true ) ) {
			return new WP_Error( 'wca_transition_expected_status_invalid', __( 'A recognized current appointment status is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$expected_status  = WCA_Contracts::normalize_appointment_status( $raw_expected_status );
		$expected_version = absint( $data['expected_version'] );
		return SWC_Helpers::with_lock( $appointment_id, function () use ( $appointment_id, $next, $data, $actor_user_id, $expected_status, $expected_version ) {
			$check = SWC_Helpers::assert_expected( $appointment_id, $expected_status, $expected_version );
			if ( is_wp_error( $check ) ) { return $check; }
			$current_auth = WCA_Authorization::can_transition_appointment( $appointment_id, $next, $actor_user_id );
			if ( is_wp_error( $current_auth ) ) { return $current_auth; }
			$current = SWC_Helpers::status( $appointment_id );
			$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
			if ( ! WCA_Contracts::can_transition( $actor, $current, $next ) ) { return new WP_Error( 'wca_transition', __( 'That transition is not permitted.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			if ( 'reschedule_pending' === $next ) {
				$hold_token = sanitize_text_field( $data['hold_token'] ?? '' );
				$hold = WCA_Repository::get_slot_hold( $hold_token );
				if ( is_wp_error( $hold ) ) { return $hold; }
				$hold_check = WCA_Plan_Guard::validate_reschedule_hold( $hold, $appointment_id, $actor_user_id );
				if ( is_wp_error( $hold_check ) ) { return $hold_check; }
				foreach ( array(
					'proposed_at_utc' => $hold['start_utc'],
					'proposed_end_utc' => $hold['end_utc'],
					'proposed_branch_id' => absint( $hold['branch_id'] ?? 0 ),
					'proposed_hold_token' => $hold_token,
					'proposed_by_user_id' => $actor_user_id,
					'proposed_expires_at' => $hold['expires_at'],
				) as $proposal_key => $proposal_value ) {
					$proposal_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_' . $proposal_key, $proposal_value, 'wca_reschedule_proposal_write' );
					if ( is_wp_error( $proposal_write ) ) { return $proposal_write; }
				}
			}
			if ( 'confirmed' === $next && 'reschedule_pending' === $current ) {
				$token = (string) SWC_Helpers::meta( $appointment_id, 'proposed_hold_token' );
				$hold = WCA_Repository::get_slot_hold( $token );
				if ( is_wp_error( $hold ) ) { return $hold; }
				if ( ! $hold || 'held' !== $hold['status'] || strtotime( $hold['expires_at'] . ' UTC' ) <= time() ) { return new WP_Error( 'wca_reschedule_expired', __( 'The proposed time has expired.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
				$booked = WCA_Repository::book_slot( $token, $appointment_id );
				if ( is_wp_error( $booked ) ) { return $booked; }
				$released_old_slot = WCA_Repository::release_appointment_slot( $appointment_id, 'released', $token );
				if ( false === $released_old_slot ) { return new WP_Error( 'wca_reschedule_old_slot_release', __( 'The previous appointment slot could not be released safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				foreach ( array( 'preferred_at_utc' => $hold['start_utc'], 'appointment_end_utc' => $hold['end_utc'], 'branch_id' => absint( $hold['branch_id'] ?? 0 ) ) as $accepted_key => $accepted_value ) {
					$accepted_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_' . $accepted_key, $accepted_value, 'wca_reschedule_accept_write' );
					if ( is_wp_error( $accepted_write ) ) { return $accepted_write; }
				}
				foreach ( array( 'proposed_at_utc','proposed_end_utc','proposed_branch_id','proposed_hold_token','proposed_by_user_id','proposed_expires_at' ) as $key ) {
					$proposal_delete = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'wca_reschedule_proposal_delete' );
					if ( is_wp_error( $proposal_delete ) ) { return $proposal_delete; }
				}
			}
			if ( 'checked_in' === $next ) {
				$actual_mode = sanitize_key( $data['actual_mode'] ?? SWC_Helpers::meta( $appointment_id, 'consultation_type' ) );
				if ( ! in_array( $actual_mode, array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ) { return new WP_Error( 'wca_actual_mode_invalid', __( 'A valid consultation mode is required for check-in.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
				$checkin_time = WCA_Repository::now();
				$checkin_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_checked_in_at_utc', $checkin_time, 'wca_checkin_write' );
				if ( is_wp_error( $checkin_write ) ) { return $checkin_write; }
				$mode_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_actual_mode', $actual_mode, 'wca_checkin_mode_write' );
				if ( is_wp_error( $mode_write ) ) { return $mode_write; }
			}
			if ( 'completed' === $next ) {
				if ( ! SWC_Helpers::meta( $appointment_id, 'checked_in_at_utc' ) ) { return new WP_Error( 'wca_checkin_required', __( 'The appointment must be checked in before completion.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
				$completion_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_completed_at_utc', WCA_Repository::now(), 'wca_completion_write' );
				if ( is_wp_error( $completion_write ) ) { return $completion_write; }
			}
			if ( in_array( $next, array( 'cancelled','declined','no_show' ), true ) ) {
				$released_slot = WCA_Repository::release_appointment_slot( $appointment_id );
				if ( false === $released_slot ) { return new WP_Error( 'wca_terminal_slot_release', __( 'The appointment slot could not be released safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			}
			$status_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_status', $next, 'wca_transition_status_write' );
			if ( is_wp_error( $status_write ) ) { return $status_write; }
			if ( isset( $data['reason_code'] ) ) {
				$reason_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_transition_reason_code', sanitize_key( $data['reason_code'] ), 'wca_transition_reason_write' );
				if ( is_wp_error( $reason_write ) ) { return $reason_write; }
			}
			$version_write = SWC_Helpers::bump_version_strict( $appointment_id );
			if ( is_wp_error( $version_write ) ) { return $version_write; }
			$trace = WCA_Observability::trace_id();
			$public_ref = (string) SWC_Helpers::meta( $appointment_id, 'public_ref', 'appointment-' . $appointment_id );
			$event_type = self::event_for_transition( $next );
			$payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'appointment_ref' => $public_ref, 'old_status' => $current, 'new_status' => $next, 'scheduled_at_utc' => SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ), 'completed_at_utc' => SWC_Helpers::meta( $appointment_id, 'completed_at_utc' ), 'trace_id' => $trace );
			$event_record = WCA_Repository::append_event( $event_type, 'appointment', $public_ref, $payload, $actor_user_id, $trace );
			if ( is_wp_error( $event_record ) ) { return $event_record; }
			$outbox_event = WCA_Repository::enqueue( $event_type, $public_ref, $payload, $trace );
			if ( is_wp_error( $outbox_event ) ) { return $outbox_event; }
			$notification = WCA_Repository::enqueue( 'File19.NotificationRequested.v1', $public_ref, array( 'event' => strtolower( str_replace( '.', '_', $event_type ) ), 'appointment_ref' => $public_ref, 'recipients' => array( absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ), absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) ) ), $trace );
			if ( is_wp_error( $notification ) ) { return $notification; }
			$communication = WCA_Repository::enqueue( 'File17.AppointmentContextChanged.v1', $public_ref, self::file17_context_payload( $appointment_id ), $trace );
			if ( is_wp_error( $communication ) ) { return $communication; }
			if ( in_array( $next, array( 'declined','cancelled','no_show' ), true ) ) {
				$fee_snapshot = self::appointment_fee_snapshot( $appointment_id );
				$fee_payload = array(
					'appointment_ref' => $public_ref,
					'appointment_status' => $next,
					'reason_code' => sanitize_key( $data['reason_code'] ?? '' ),
					'scheduled_at_utc' => (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ),
					'platform_commission_minor' => 0,
					'action' => 'evaluate_fee_refund_or_void_policy',
					'trace_id' => $trace,
				);
				if ( is_wp_error( $fee_snapshot ) ) {
					$fee_payload['snapshot_status'] = 'legacy_missing_reconciliation_required';
				} else {
					$fee_payload['snapshot_status'] = 'booked_snapshot';
					$fee_payload['fee'] = $fee_snapshot;
				}
				$fee_review = WCA_Repository::enqueue( 'CF03.AppointmentFeePolicyReviewRequested.v1', $public_ref, $fee_payload, $trace );
				if ( is_wp_error( $fee_review ) ) { return $fee_review; }
			}
			if ( 'completed' === $next ) {
				$eligibility = WCA_Repository::grant_review_eligibility( $appointment_id, absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ), absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ), absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) ) );
				if ( is_wp_error( $eligibility ) ) { return $eligibility; }
				$review_payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'eligibility_ref' => $eligibility['public_ref'], 'appointment_ref' => $public_ref, 'reviewer_subject_uuid' => WCA_Authorization::subject_uuid( $eligibility['reviewer_user_id'] ), 'doctor_subject_uuid' => WCA_Authorization::subject_uuid( $eligibility['doctor_user_id'] ), 'trace_id' => $trace );
				$review_event = WCA_Repository::append_event( 'ReviewEligibilityGranted.v1', 'review_eligibility', $eligibility['public_ref'], $review_payload, $actor_user_id, $trace );
				if ( is_wp_error( $review_event ) ) { return $review_event; }
				$review_outbox = WCA_Repository::enqueue( 'ReviewEligibilityGranted.v1', $eligibility['public_ref'], $review_payload, $trace );
				if ( is_wp_error( $review_outbox ) ) { return $review_outbox; }
			}
			if ( ! SWC_Helpers::audit( $appointment_id, 'wca-transition', array( 'old_status' => $current, 'new_status' => $next, 'reason' => sanitize_text_field( $data['reason_code'] ?? '' ), 'details' => array( 'trace_id' => $trace ) ) ) ) {
				return new WP_Error( 'wca_transition_audit', __( 'The appointment transition could not be audited safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
			}
			WCA_Observability::metric( 'appointment_transition_total', 1, array( 'from' => $current, 'to' => $next, 'actor' => $actor ) );
			return array( 'appointment_id' => $appointment_id, 'public_ref' => $public_ref, 'status' => $next, 'version' => SWC_Helpers::record_version( $appointment_id ), 'trace_id' => $trace );
		} );
	}

	private static function event_for_transition( $status ) {
		$map = array(
			'confirmed'          => 'AppointmentConfirmed.v1',
			'declined'           => 'AppointmentDeclined.v1',
			'reschedule_pending' => 'AppointmentRescheduleProposed.v1',
			'checked_in'         => 'AppointmentCheckedIn.v1',
			'completed'          => 'AppointmentCompleted.v1',
			'cancelled'          => 'AppointmentCancelled.v1',
			'no_show'            => 'AppointmentNoShow.v1',
		);
		return $map[ $status ] ?? 'AppointmentChanged.v1';
	}

	/** @return array<string,mixed> */
	public static function public_clinic_projection( $id_or_slug ) {
		WCA_Repository::clear_read_error();
		$private = WCA_Repository::get_clinic( $id_or_slug, false );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( ! $private || 'active' !== (string) $private['status'] ) { return array(); }
		$owner_id = absint( $private['owner_user_id'] ?? 0 );
		if ( ! $owner_id || ! SWC_Doctor_Authority::is_eligible( $owner_id ) ) { return array(); }
		WCA_Repository::clear_read_error();
		$clinic = WCA_Repository::get_clinic( $private['id'], true );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( ! $clinic ) { return array(); }
		$projection = array(
			'contract'       => 'wca.public-clinic',
			'contract_version'=> WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION,
			'public_ref'     => $clinic['public_ref'],
			'slug'           => $clinic['slug'],
			'name'           => $clinic['name'],
			'summary'        => $clinic['summary'],
			'languages'      => $clinic['languages'],
			'contacts'       => $clinic['contacts'],
			'policies'       => $clinic['policies'],
			'status'         => $clinic['status'],
			'branches'       => $clinic['branches'],
			'services'       => $clinic['services'],
			'verified_owner' => true,
			'updated_at'     => $clinic['updated_at'],
			'record_version' => absint( $clinic['version'] ),
		);
		foreach ( WCA_Contracts::prohibited_public_fields() as $field ) { unset( $projection[ $field ] ); }
		return $projection;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_complaint( $data, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$appointment_id = absint( $data['appointment_id'] ?? 0 );
		if ( $appointment_id ) {
			$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
			if ( is_wp_error( $access ) ) { return $access; }
			$data['clinic_id'] = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) );
		}
		$data['complainant_user_id'] = $actor_user_id;
		return WCA_Repository::transaction( function () use ( $data, $actor_user_id, $appointment_id ) {
			$result = WCA_Repository::create_complaint( $data );
			if ( is_wp_error( $result ) ) { return $result; }
			$trace = WCA_Observability::trace_id();
			$event = WCA_Repository::append_event( 'AppointmentComplaintSubmitted.v1', 'complaint', $result['public_ref'], array( 'complaint_ref' => $result['public_ref'], 'appointment_ref' => $appointment_id ? SWC_Helpers::meta( $appointment_id, 'public_ref' ) : '', 'category' => $result['category'], 'trace_id' => $trace ), $actor_user_id, $trace );
			if ( is_wp_error( $event ) ) { return $event; }
			foreach ( array(
				array( 'CF02.CaseRequested.v1', array( 'case_type' => 'appointment_complaint', 'complaint_ref' => $result['public_ref'], 'purpose_limit' => $result['purpose_limit'] ) ),
				array( 'File19.NotificationRequested.v1', array( 'event' => 'complaint_submitted', 'recipients' => array( $actor_user_id ) ) ),
			) as $outbox ) {
				$queued = WCA_Repository::enqueue( $outbox[0], $result['public_ref'], $outbox[1], $trace );
				if ( is_wp_error( $queued ) ) { return $queued; }
			}
			return $result;
		}, 'wca_complaint_transaction' );
	}

	private static function approved_payment_provider( $provider ) {
		$provider = sanitize_key( (string) $provider );
		$approved = apply_filters( 'wca_cf03_approved_payment_providers', array( 'manual' ) );
		if ( ! is_array( $approved ) ) { return ''; }
		$approved = array_values( array_unique( array_filter( array_map( 'sanitize_key', $approved ) ) ) );
		return $provider && in_array( $provider, $approved, true ) ? $provider : '';
	}

	private static function appointment_fee_snapshot( $appointment_id ) {
		$currency = strtoupper( trim( (string) SWC_Helpers::meta( $appointment_id, 'fee_currency_snapshot' ) ) );
		$amount_raw = SWC_Helpers::meta( $appointment_id, 'fee_amount_minor_snapshot', null );
		$amount = self::strict_int( is_scalar( $amount_raw ) ? (string) $amount_raw : '', 0, PHP_INT_MAX );
		$service_ref = (string) SWC_Helpers::meta( $appointment_id, 'service_public_ref_snapshot' );
		$service_version = absint( SWC_Helpers::meta( $appointment_id, 'service_version_snapshot' ) );
		if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) || null === $amount || ! $service_ref || ! $service_version ) {
			return new WP_Error( 'wca_payment_snapshot_missing', __( 'The appointment does not have a trustworthy booked fee snapshot. Financial reconciliation is required before payment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		return array(
			'currency' => $currency,
			'amount_minor' => $amount,
			'fee_max_minor' => absint( SWC_Helpers::meta( $appointment_id, 'fee_max_minor_snapshot' ) ),
			'service_ref' => $service_ref,
			'service_version' => $service_version,
			'tax_policy' => (string) SWC_Helpers::meta( $appointment_id, 'tax_policy_snapshot' ),
			'refund_policy' => (string) SWC_Helpers::meta( $appointment_id, 'refund_policy_snapshot' ),
			'cancellation_policy' => (string) SWC_Helpers::meta( $appointment_id, 'cancellation_policy_snapshot' ),
			'platform_commission_bps' => 0,
		);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_payment_intent( $appointment_id, $provider = 'manual', $actor_user_id = 0, $idempotency_key = '' ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$idempotency_key = sanitize_text_field( $idempotency_key );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $idempotency_key ) ) { return new WP_Error( 'wca_payment_idempotency_required', __( 'A valid payment idempotency key is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$patient_user_id  = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
		$guardian_user_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
		if ( $actor_user_id !== $patient_user_id && ( ! $guardian_user_id || $actor_user_id !== $guardian_user_id ) ) {
			return new WP_Error( 'wca_payment_payer_required', __( 'Only the patient or currently authorized guardian may create a payment intent.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		if ( $guardian_user_id && $actor_user_id === $guardian_user_id ) {
			$guardian = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id ) : new WP_Error( 'wca_guardian_verification_unavailable', __( 'Guardian authority could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
			if ( is_wp_error( $guardian ) ) { return $guardian; }
		}
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$provider = self::approved_payment_provider( $provider );
		if ( ! $provider ) { return new WP_Error( 'wca_payment_provider_unapproved', __( 'The selected payment provider is not approved by the shared financial owner.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$snapshot = self::appointment_fee_snapshot( $appointment_id );
		if ( is_wp_error( $snapshot ) ) { return $snapshot; }
		$claim = WCA_Repository::claim_idempotency( 'payment_intent', $idempotency_key, $actor_user_id, array( 'appointment_id' => absint( $appointment_id ), 'provider' => $provider, 'service_ref' => $snapshot['service_ref'], 'service_version' => $snapshot['service_version'], 'currency' => $snapshot['currency'], 'amount_minor' => $snapshot['amount_minor'] ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return $claim['response']; }
		if ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_idempotency_in_progress', __( 'This payment request is already being processed.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) ); }
		$result = WCA_Repository::transaction( function () use ( $appointment_id, $provider, $idempotency_key, $snapshot, $claim ) {
			$payment = WCA_Repository::create_payment_intent( array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'request_key' => $idempotency_key, 'currency' => $snapshot['currency'], 'amount_minor' => $snapshot['amount_minor'], 'status' => 'pending', 'metadata' => array( 'service_ref' => $snapshot['service_ref'], 'service_version' => $snapshot['service_version'], 'fee_max_minor' => $snapshot['fee_max_minor'], 'tax_policy' => $snapshot['tax_policy'], 'refund_policy' => $snapshot['refund_policy'], 'cancellation_policy' => $snapshot['cancellation_policy'], 'commission_percent' => 0 ) ) );
			if ( is_wp_error( $payment ) ) { return $payment; }
			$queued = WCA_Repository::enqueue( 'CF03.PaymentIntentRequested.v1', $payment['public_ref'], array( 'payment_intent_ref' => $payment['public_ref'], 'appointment_ref' => SWC_Helpers::meta( $appointment_id, 'public_ref' ), 'provider' => $provider, 'service_ref' => $snapshot['service_ref'], 'service_version' => $snapshot['service_version'], 'currency' => $payment['currency'], 'amount_minor' => $payment['amount_minor'], 'platform_commission_minor' => 0 ), WCA_Observability::trace_id() );
			if ( is_wp_error( $queued ) ) { return $queued; }
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], 201, $payment ) ) { return new WP_Error( 'wca_payment_idempotency_complete', __( 'The payment request could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			return $payment;
		}, 'wca_payment_intent_transaction' );
		if ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim['id'] ); }
		return $result;
	}

	/** @return string|WP_Error */
	public static function appointment_ics( $appointment_id, $actor_user_id = 0 ) {
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$start = (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' );
		$end = (string) SWC_Helpers::meta( $appointment_id, 'appointment_end_utc' );
		if ( ! $start ) { return new WP_Error( 'wca_calendar_time', __( 'Appointment time is unavailable.', 'worldwide-clinic-appointments' ) ); }
		$start_ts = self::strict_utc_timestamp( $start );
		if ( false === $start_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment start time is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( $end ) {
			$end_ts = self::strict_utc_timestamp( $end );
			if ( false === $end_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment end time is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		} else {
			$end_ts = $start_ts + max( 10, absint( SWC_Helpers::meta( $appointment_id, 'appointment_duration', 30 ) ) ) * 60;
		}
		if ( $end_ts <= $start_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment end time must be after the start time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$uid = (string) SWC_Helpers::meta( $appointment_id, 'public_ref', 'appointment-' . $appointment_id ) . '@sabrihomeopathy.com';
		$summary = 'Clinic Appointment';
		$lines = array(
			'BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Sabri Social Homeopathy Platform//File 08//EN','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT',
			'UID:' . self::ics_escape( $uid ),
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART:' . gmdate( 'Ymd\THis\Z', $start_ts ),
			'DTEND:' . gmdate( 'Ymd\THis\Z', $end_ts ),
			'SUMMARY:' . self::ics_escape( $summary ),
			'DESCRIPTION:' . self::ics_escape( 'Private appointment details are available only in the authenticated platform.' ),
			'CLASS:PRIVATE','TRANSP:OPAQUE','END:VEVENT','END:VCALENDAR',
		);
		return implode( "\r\n", $lines ) . "\r\n";
	}

	private static function strict_utc_timestamp( $value ) {
		$value = trim( (string) $value );
		if ( '' === $value ) { return false; }
		$utc = new DateTimeZone( 'UTC' );
		foreach ( array( array( '!Y-m-d H:i:s', 'Y-m-d H:i:s' ), array( '!Y-m-d H:i', 'Y-m-d H:i' ), array( '!Y-m-d\TH:i:s\Z', 'Y-m-d\TH:i:s\Z' ), array( '!Y-m-d\TH:i\Z', 'Y-m-d\TH:i\Z' ) ) as $entry ) {
			$dt = DateTimeImmutable::createFromFormat( $entry[0], $value, $utc );
			$errors = DateTimeImmutable::getLastErrors();
			if ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) { return $dt->getTimestamp(); }
		}
		return false;
	}

	private static function ics_escape( $value ) {
		return str_replace( array( '\\', ';', ',', "\r\n", "\n", "\r" ), array( '\\\\', '\\;', '\\,', '\\n', '\\n', '\\n' ), (string) $value );
	}

	/** @return array<string,mixed> */
	public static function file17_context_payload( $appointment_id ) {
		return array(
			'contract'          => 'wca.file17.appointment-context',
			'version'           => WCA_Contracts::FILE17_CONTEXT_CONTRACT_VERSION,
			'appointment_ref'   => (string) SWC_Helpers::meta( $appointment_id, 'public_ref', 'appointment-' . $appointment_id ),
			'status'            => SWC_Helpers::status( $appointment_id ),
			'patient_subject_uuid'=> WCA_Authorization::subject_uuid( absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ) ),
			'doctor_subject_uuid' => WCA_Authorization::subject_uuid( absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) ),
			'scheduled_at_utc'  => (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ),
			'consultation_type' => (string) SWC_Helpers::meta( $appointment_id, 'consultation_type' ),
			'messaging_allowed' => in_array( SWC_Helpers::status( $appointment_id ), array( 'requested','confirmed','reschedule_pending','checked_in' ), true ),
			'clinical_authority'=> false,
			'trace_id'          => WCA_Observability::trace_id(),
		);
	}

	/** @return array<string,mixed>|null */
	public static function emergency_red_flag( $reason, $category = '' ) {
		$text = strtolower( wp_strip_all_tags( (string) $reason . ' ' . (string) $category ) );
		$groups = array(
			'life_threatening' => array( 'chest pain','difficulty breathing','cannot breathe','unconscious','severe bleeding','stroke','heart attack','suicide','self harm','poisoning','overdose','seizure','fits','anaphylaxis','emergency','شدید سینے کا درد','سانس نہیں','بے ہوش','خودکشی','زہر','فالج','دل کا دورہ','شدید خون' ),
			'pregnancy_emergency' => array( 'pregnancy bleeding','heavy bleeding pregnancy','eclampsia','labor emergency','حمل میں خون','زچگی ایمرجنسی' ),
			'child_emergency' => array( 'blue baby','not responding child','child cannot breathe','بچہ سانس نہیں','بچہ بے ہوش' ),
		);
		foreach ( $groups as $group => $terms ) {
			foreach ( $terms as $term ) {
				if ( false !== strpos( $text, strtolower( $term ) ) ) {
					return array( 'category' => $group, 'message' => __( 'This service is not emergency care. Contact local emergency services or go to the nearest emergency department immediately. No appointment or delayed support ticket has been created.', 'worldwide-clinic-appointments' ) );
				}
			}
		}
		return null;
	}

	private static function affirmative( $value ) {
		if ( true === $value || 1 === $value ) { return true; }
		$value = strtolower( trim( (string) $value ) );
		return in_array( $value, array( '1', 'true', 'yes', 'on' ), true );
	}

	private static function appointment_request_fingerprint( $data ) {
		return array(
			'hold_token'             => sanitize_text_field( $data['hold_token'] ?? '' ),
			'patient_user_id'        => absint( $data['patient_user_id'] ?? 0 ),
			'guardian_user_id'       => absint( $data['guardian_user_id'] ?? 0 ),
			'category'               => sanitize_key( $data['category'] ?? '' ),
			'reason'                 => SWC_Helpers::limit_text( $data['reason'] ?? '', 500, true ),
			'timezone'               => sanitize_text_field( $data['timezone'] ?? '' ),
			'privacy_consent'        => self::affirmative( $data['privacy_consent'] ?? null ),
			'emergency_acknowledged' => self::affirmative( $data['emergency_acknowledged'] ?? null ),
			'telehealth_consent'     => self::affirmative( $data['telehealth_consent'] ?? null ),
		);
	}

	private static function appointment_terms_text() {
		return 'Appointment processing, privacy, telehealth where selected, guardian authority where applicable, emergency limitation, cancellation policy, and zero platform commission terms version ' . self::TERMS_VERSION;
	}

	public static function handle_doctor_suspended( $doctor_user_id, $reason = '' ) {
		$doctor_user_id = absint( $doctor_user_id );
		$page = 1;
		$seen = 0;
		do {
			$appointments = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => 'private', 'posts_per_page' => 200, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => '_swc_doctor_id', 'value' => $doctor_user_id ) ) ) );
			foreach ( $appointments as $id ) {
				$status = SWC_Helpers::status( $id );
				if ( ! WCA_Contracts::is_terminal( $status ) ) {
					$reconciled = WCA_Repository::transaction( function () use ( $id, $reason ) {
						$hold_write = SWC_Helpers::apply_meta_mutations( $id, array( '_swc_doctor_authority_hold' => '1', '_swc_doctor_authority_hold_reason' => sanitize_text_field( $reason ) ), array(), 'wca_doctor_hold_persist' );
						if ( is_wp_error( $hold_write ) ) { return $hold_write; }
						$queued = WCA_Repository::enqueue(
							'File19.NotificationRequested.v1',
							(string) SWC_Helpers::meta( $id, 'public_ref' ),
							array(
								'event' => 'doctor_authority_hold',
								'recipients' => array( absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) ) ),
							),
							WCA_Observability::trace_id()
						);
						return is_wp_error( $queued ) ? $queued : true;
					}, 'wca_doctor_suspension_reconcile_transaction' );
					if ( is_wp_error( $reconciled ) ) {
						WCA_Observability::log( 'error', 'doctor_suspension_reconcile_failed', array( 'appointment_ref' => (string) SWC_Helpers::meta( $id, 'public_ref' ), 'error' => $reconciled->get_error_code() ) );
					}
				}
				$seen++;
			}
			$page++;
		} while ( 200 === count( $appointments ) );
		WCA_Observability::metric( 'doctor_suspension_appointments_scanned', $seen, array( 'doctor_scope' => 'suspended' ) );
	}

	public static function handle_payment_status_changed( $payment_ref, $status ) {
		WCA_Observability::metric( 'unverified_payment_status_ignored_total', 1 );
		WCA_Observability::log( 'warning', 'unverified_payment_status_ignored', array( 'payment_ref' => sanitize_text_field( $payment_ref ), 'status' => sanitize_key( $status ) ) );
		return new WP_Error( 'wca_payment_status_unverified', __( 'Unverified payment status input was ignored.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	public static function handle_payment_status_changed_event( $event ) {
		$result = self::consume_payment_status_event( $event );
		if ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'payment_status_projection_failed', array( 'code' => $result->get_error_code() ) ); }
		return $result;
	}

	public static function consume_payment_status_event( $event ) {
		if ( ! is_array( $event ) || true !== ( $event['verified'] ?? false ) || 'CF03' !== (string) ( $event['source'] ?? '' ) ) { return new WP_Error( 'wca_payment_status_unverified', __( 'Only a verified CF03 payment fact may update File 08 payment status.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$event_id = sanitize_text_field( $event['event_id'] ?? '' );
		$payment_ref = sanitize_text_field( $event['payment_intent_ref'] ?? '' );
		$status = sanitize_key( $event['status'] ?? '' );
		$provider_ref = sanitize_text_field( $event['provider_ref'] ?? '' );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $event_id ) || ! preg_match( '/^[0-9a-fA-F-]{36}$/', $payment_ref ) || ! in_array( $status, WCA_Contracts::payment_statuses(), true ) || 'pending' === $status ) { return new WP_Error( 'wca_payment_status_event_invalid', __( 'The verified financial event is malformed or unsupported.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$claim = WCA_Repository::claim_idempotency( 'payment_status_event', $event_id, 0, array( 'payment_intent_ref' => $payment_ref, 'status' => $status, 'provider_ref' => $provider_ref ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return $claim['response']; }
		if ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_payment_status_event_in_progress', __( 'This payment status fact is already being reconciled.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$result = WCA_Repository::transaction( function () use ( $payment_ref, $status, $provider_ref, $event_id, $claim ) {
			$projected = WCA_Repository::project_payment_status( $payment_ref, $status, $provider_ref );
			if ( is_wp_error( $projected ) ) { return $projected; }
			$trace = WCA_Observability::trace_id();
			$audit = WCA_Repository::append_event( 'PaymentStatusProjected.v1', 'payment_intent', $payment_ref, array( 'event_id' => WCA_Repository::uuid(), 'source_event_id' => $event_id, 'payment_intent_ref' => $payment_ref, 'status' => $status, 'trace_id' => $trace ), 0, $trace );
			if ( is_wp_error( $audit ) ) { return $audit; }
			$response = array_intersect_key( $projected, array_flip( array( 'public_ref','status','currency','amount_minor','platform_commission_minor','version','updated_at' ) ) );
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], 200, $response ) ) { return new WP_Error( 'wca_payment_status_idempotency_complete', __( 'Payment status reconciliation could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			return $response;
		}, 'wca_payment_status_projection_transaction' );
		if ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim['id'] ); }
		return $result;
	}
}
