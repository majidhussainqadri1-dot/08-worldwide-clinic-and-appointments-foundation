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
	}

	public static function valid_timezone( $timezone ) {
		try {
			new DateTimeZone( (string) $timezone );
			return true;
		} catch ( Exception $e ) {
			return false;
		}
	}

	public static function valid_hhmm( $value ) {
		return 1 === preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', (string) $value );
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
		$data['status']             = 'draft';
		$data['owner_user_id']      = $actor_user_id;
		$data['owner_subject_uuid'] = is_wp_error( $claims ) ? '' : $claims['subject_uuid'];
		$clinic = WCA_Repository::create_clinic( $data );
		if ( is_wp_error( $clinic ) ) { return $clinic; }
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'ClinicCreated.v1', 'clinic', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'status' => $clinic['status'] ), $actor_user_id, $trace );
		WCA_Repository::enqueue( 'File24.AssuranceEvidenceRequested.v1', $clinic['public_ref'], array( 'entity' => 'clinic', 'entity_ref' => $clinic['public_ref'], 'change' => 'created' ), $trace );
		WCA_Observability::metric( 'clinic_created_total', 1 );
		return $clinic;
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
		$updated = WCA_Repository::update_clinic( $clinic['id'], $expected_version, array( 'status' => 'review' ) );
		if ( is_wp_error( $updated ) ) { return $updated; }
		$clinic = WCA_Repository::get_clinic( $clinic['id'], false );
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'ClinicReviewRequested.v1', 'clinic', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'trace_id' => $trace ), $actor_user_id, $trace );
		WCA_Repository::enqueue( 'ClinicReviewRequested.v1', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'] ), $trace );
		return $clinic;
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
		if ( ! in_array( $clinic['status'], array( 'review','paused' ), true ) ) {
			return new WP_Error( 'wca_clinic_transition', __( 'Clinic cannot be activated from its current state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$services = WCA_Repository::list_services( $clinic['id'], false );
		$branches = WCA_Repository::list_branches( $clinic['id'], false );
		if ( ! $services || ! $branches ) {
			return new WP_Error( 'wca_clinic_incomplete', __( 'At least one branch and one service are required before activation.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$updated = WCA_Repository::update_clinic( $clinic['id'], $expected_version, array( 'status' => 'active' ) );
		if ( is_wp_error( $updated ) ) { return $updated; }
		$clinic = WCA_Repository::get_clinic( $clinic['id'], false );
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'ClinicActivated.v1', 'clinic', $clinic['public_ref'], array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'clinic_ref' => $clinic['public_ref'], 'owner_subject_uuid' => $clinic['owner_subject_uuid'], 'trace_id' => $trace ), $actor_user_id, $trace );
		WCA_Repository::enqueue( 'ClinicActivated.v1', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'owner_subject_uuid' => $clinic['owner_subject_uuid'] ), $trace );
		return $clinic;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function save_service( $data, $service_id = 0, $expected_version = 0, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( absint( $data['clinic_id'] ?? 0 ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		if ( $service_id ) {
			$current = WCA_Repository::get_service( $service_id, false );
			if ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		}
		if ( ! empty( $data['branch_id'] ) ) { $branch = WCA_Repository::get_branch( absint( $data['branch_id'] ) ); if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_branch_scope', __( 'The branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }
		if ( ! empty( $data['doctor_user_id'] ) && ! SWC_Doctor_Authority::is_eligible( absint( $data['doctor_user_id'] ) ) ) { return new WP_Error( 'wca_service_doctor', __( 'The assigned practitioner is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$data['clinic_id'] = absint( $clinic['id'] );
		$data['platform_commission_bps'] = 0;
		$result = WCA_Repository::save_service( $data, $service_id, $expected_version );
		if ( is_wp_error( $result ) ) { return $result; }
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( $service_id ? 'ClinicServiceUpdated.v1' : 'ClinicServiceCreated.v1', 'clinic_service', $result['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'service_ref' => $result['public_ref'], 'commission_bps' => 0 ), $actor_user_id, $trace );
		WCA_Repository::enqueue( 'ClinicServiceChanged.v1', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'service_ref' => $result['public_ref'] ), $trace );
		return $result;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function set_availability( $data, $rule_id = 0, $expected_version = 0, $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$clinic = WCA_Repository::get_clinic( absint( $data['clinic_id'] ?? 0 ), false );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$auth = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		if ( is_wp_error( $auth ) ) { return $auth; }
		if ( $rule_id ) { $current = WCA_Repository::get_availability_rule( $rule_id ); if ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_scope', __( 'The availability rule does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); } }
		if ( ! empty( $data['service_id'] ) ) { $service = WCA_Repository::get_service( absint( $data['service_id'] ), false ); if ( ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_service', __( 'The availability service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }
		if ( ! empty( $data['branch_id'] ) ) { $branch = WCA_Repository::get_branch( absint( $data['branch_id'] ) ); if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_branch', __( 'The availability branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }
		$doctor_id = absint( $data['doctor_user_id'] ?? $actor_user_id );
		if ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
			return new WP_Error( 'wca_doctor_ineligible', __( 'The doctor is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		$data['doctor_user_id'] = $doctor_id;
		$result = WCA_Repository::save_availability_rule( $data, $rule_id, $expected_version );
		if ( is_wp_error( $result ) ) { return $result; }
		$trace = WCA_Observability::trace_id();
		$subject = WCA_Authorization::subject_uuid( $doctor_id );
		WCA_Repository::append_event( 'ClinicAvailabilityChanged.v1', 'availability_rule', $result['public_ref'], array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'clinic_ref' => $clinic['public_ref'], 'doctor_subject_uuid' => $subject, 'version' => absint( $result['version'] ), 'trace_id' => $trace ), $actor_user_id, $trace );
		WCA_Repository::enqueue( 'ClinicAvailabilityChanged.v1', $clinic['public_ref'], array( 'clinic_ref' => $clinic['public_ref'], 'doctor_subject_uuid' => $subject, 'version' => absint( $result['version'] ) ), $trace );
		return $result;
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
		$timezone = (string) ( $args['timezone'] ?? 'UTC' );
		if ( ! $doctor_id || ! self::valid_timezone( $timezone ) || ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
			return new WP_Error( 'wca_slot_query', __( 'Valid doctor and time zone are required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$from = self::valid_date( $args['date_from'] ?? '' ) ? (string) $args['date_from'] : gmdate( 'Y-m-d' );
		$to   = self::valid_date( $args['date_to'] ?? '' ) ? (string) $args['date_to'] : gmdate( 'Y-m-d', time() + 30 * DAY_IN_SECONDS );
		$from_date = new DateTimeImmutable( $from, new DateTimeZone( 'UTC' ) );
		$to_date   = new DateTimeImmutable( $to, new DateTimeZone( 'UTC' ) );
		if ( $to_date < $from_date || $to_date->diff( $from_date )->days > self::SLOT_HORIZON_DAYS ) {
			return new WP_Error( 'wca_slot_range', __( 'Slot search range is invalid or too large.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$rules = WCA_Repository::list_availability_rules( $doctor_id, $service_id );
		if ( ! $rules ) {
			return array( 'slots' => array(), 'freshness_version' => hash( 'sha256', 'none|' . $doctor_id ), 'generated_at_utc' => gmdate( 'c' ) );
		}
		$service = $service_id ? WCA_Repository::get_service( $service_id, true ) : null;
		$duration = $service ? absint( $service['duration_minutes'] ) : absint( $args['duration_minutes'] ?? 30 );
		$duration = min( 480, max( 10, $duration ) );
		$limit = min( 500, max( 1, absint( $args['limit'] ?? 100 ) ) );
		$slots = array();
		$versions = array();
		foreach ( $rules as $rule ) {
			$versions[] = $rule['public_ref'] . ':' . $rule['version'];
			$slots = array_merge( $slots, self::generate_rule_slots( $rule, $from, $to, $duration, $timezone, $limit - count( $slots ) ) );
			if ( count( $slots ) >= $limit ) { break; }
		}
		usort( $slots, static function ( $a, $b ) { return strcmp( $a['start_utc'], $b['start_utc'] ); } );
		WCA_Observability::metric( 'slot_search_total', 1, array( 'result_bucket' => count( $slots ) ? 'non_empty' : 'empty' ) );
		return array(
			'slots'             => array_slice( $slots, 0, $limit ),
			'freshness_version' => hash( 'sha256', implode( '|', $versions ) . '|' . WCA_Repository::now() . '|' . $doctor_id . '|' . $service_id ),
			'generated_at_utc'  => gmdate( 'c' ),
			'timezone'          => $timezone,
		);
	}

	/** @return array<int,array<string,mixed>> */
	private static function generate_rule_slots( $rule, $from, $to, $duration, $display_timezone, $limit ) {
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
						if ( $start_utc->getTimestamp() > time() + max( 0, absint( $rule['buffer_before'] ) ) * 60 && ! self::in_break( $slot, $slot_end, $rule['breaks'] ) && ! SWC_Helpers::has_conflict( absint( $rule['doctor_user_id'] ), $start_utc->format( 'Y-m-d H:i:s' ), $duration, 0 ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $start_utc->format( 'Y-m-d H:i:s' ), $end_utc->format( 'Y-m-d H:i:s' ) ) ) {
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
		$value = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, $zone );
		$errors = DateTimeImmutable::getLastErrors();
		if ( ! $value || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $value->format( 'Y-m-d H:i' ) !== $date . ' ' . $time ) {
			return null;
		}
		return $value;
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

	private static function has_active_hold( $doctor_id, $start_utc, $end_utc ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1", absint( $doctor_id ), WCA_Repository::now(), $end_utc, $start_utc ) );
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
		$data['idempotency_key'] = sanitize_text_field( $data['idempotency_key'] ?? WCA_Repository::uuid() );
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
		$red_flag = self::emergency_red_flag( (string) ( $data['reason'] ?? '' ), (string) ( $data['category'] ?? '' ) );
		if ( $red_flag ) {
			WCA_Observability::metric( 'emergency_diversion_total', 1, array( 'category' => $red_flag['category'] ) );
			return new WP_Error( 'wca_emergency_diversion', $red_flag['message'], array( 'status' => 422, 'emergency' => true, 'category' => $red_flag['category'] ) );
		}
		$idempotency_key = sanitize_text_field( $data['idempotency_key'] ?? '' );
		if ( ! $idempotency_key ) { return new WP_Error( 'wca_idempotency_required', __( 'An idempotency key is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$hold = WCA_Repository::get_slot_hold( (string) ( $data['hold_token'] ?? '' ) );
		$hold_check = WCA_Plan_Guard::validate_bookable_hold( $hold, $patient_user_id );
		if ( is_wp_error( $hold_check ) ) { return $hold_check; }
		$doctor_id = absint( $hold['doctor_user_id'] );
		if ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
			return new WP_Error( 'wca_doctor_ineligible', __( 'The selected doctor is no longer eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$service = $hold['service_id'] ? WCA_Repository::get_service( $hold['service_id'], true ) : null;
		$clinic  = WCA_Repository::get_clinic( $hold['clinic_id'], true );
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_unavailable', __( 'The clinic is not currently available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }

		$claim = WCA_Repository::claim_idempotency( 'request_appointment', $idempotency_key, $actor_user_id, self::appointment_request_fingerprint( $data ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === ( $claim['status'] ?? '' ) ) { return $claim['response']; }
		if ( empty( $claim['claimed_new'] ) ) {
			return new WP_Error( 'wca_idempotency_in_progress', __( 'This appointment request is already being processed. Retry with the same idempotency key shortly.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) );
		}

		$appointment_id = wp_insert_post(
			array(
				'post_type'   => SWC_Helpers::TYPE,
				'post_status' => 'private',
				'post_author' => $patient_user_id,
				'post_title'  => sprintf( 'Appointment %s', gmdate( 'Y-m-d H:i', strtotime( $hold['start_utc'] . ' UTC' ) ) ),
			),
			true
		);
		if ( is_wp_error( $appointment_id ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $appointment_id; }
		$public_ref = WCA_Repository::uuid();
		$meta = array(
			'public_ref'             => $public_ref,
			'patient_user_id'        => $patient_user_id,
			'guardian_user_id'       => $guardian_user_id,
			'doctor_id'              => $doctor_id,
			'clinic_id'              => absint( $hold['clinic_id'] ),
			'service_id'             => absint( $hold['service_id'] ),
			'branch_id'              => absint( $data['branch_id'] ?? 0 ),
			'status'                 => 'requested',
			'preferred_at_utc'       => $hold['start_utc'],
			'appointment_end_utc'    => $hold['end_utc'],
			'patient_timezone'       => self::valid_timezone( $data['timezone'] ?? '' ) ? (string) $data['timezone'] : 'UTC',
			'consultation_type'      => $service['consultation_type'] ?? sanitize_key( $data['consultation_type'] ?? 'online' ),
			'appointment_duration'   => $service['duration_minutes'] ?? absint( ( strtotime( $hold['end_utc'] ) - strtotime( $hold['start_utc'] ) ) / 60 ),
			'reason_category'        => sanitize_key( $data['category'] ?? 'general' ),
			'reason'                 => SWC_Helpers::limit_text( $data['reason'] ?? '', 500, true ),
			'consent_version'        => self::TERMS_VERSION,
			'consent_at'             => WCA_Repository::now(),
			'record_version'         => 1,
			'created_via'            => 'wca_command',
			'idempotency_key_hash'   => hash( 'sha256', $idempotency_key ),
		);
		foreach ( $meta as $key => $value ) { update_post_meta( $appointment_id, '_swc_' . $key, $value ); }
		$booked = WCA_Repository::book_slot( $hold['hold_token'], $appointment_id );
		if ( is_wp_error( $booked ) ) { wp_delete_post( $appointment_id, true ); WCA_Repository::release_idempotency( $claim['id'] ); return $booked; }

		$terms = self::appointment_terms_text();
		$consent = WCA_Repository::record_consent( array(
			'appointment_id'     => $appointment_id,
			'actor_user_id'      => $actor_user_id,
			'actor_subject_uuid' => $claims['subject_uuid'],
			'guardian_user_id'   => $guardian_user_id,
			'scope'              => 'appointment_processing',
			'terms_version'      => self::TERMS_VERSION,
			'terms_text'         => $terms,
			'legal_basis'        => 'consent',
			'metadata'           => array( 'telehealth' => ! empty( $data['telehealth_consent'] ), 'privacy' => true, 'emergency_acknowledged' => true ),
		) );
		if ( is_wp_error( $consent ) ) { WCA_Repository::release_appointment_slot( $appointment_id ); wp_delete_post( $appointment_id, true ); WCA_Repository::release_idempotency( $claim['id'] ); return $consent; }

		$trace = WCA_Observability::trace_id();
		$payload = array(
			'event_id'                => WCA_Repository::uuid(),
			'occurred_at'             => gmdate( 'c' ),
			'appointment_ref'         => $public_ref,
			'patient_subject_uuid'    => WCA_Authorization::subject_uuid( $patient_user_id ),
			'doctor_subject_uuid'     => WCA_Authorization::subject_uuid( $doctor_id ),
			'clinic_ref'              => $clinic['public_ref'],
			'scheduled_at_utc'        => $hold['start_utc'],
			'consultation_type'       => $meta['consultation_type'],
			'trace_id'                => $trace,
		);
		WCA_Repository::append_event( 'AppointmentRequested.v1', 'appointment', $public_ref, $payload, $actor_user_id, $trace );
		WCA_Repository::enqueue( 'AppointmentRequested.v1', $public_ref, $payload, $trace );
		WCA_Repository::enqueue( 'File19.NotificationRequested.v1', $public_ref, array( 'event' => 'appointment_requested', 'appointment_ref' => $public_ref, 'recipients' => array( $patient_user_id, $doctor_id ) ), $trace );
		WCA_Repository::enqueue( 'File17.AppointmentContextChanged.v1', $public_ref, self::file17_context_payload( $appointment_id ), $trace );
		SWC_Helpers::audit( $appointment_id, 'appointment-requested', array( 'new_status' => 'requested', 'details' => array( 'public_ref' => $public_ref, 'clinic_id' => absint( $hold['clinic_id'] ), 'service_id' => absint( $hold['service_id'] ) ) ) );
		$response = array( 'appointment_id' => $appointment_id, 'public_ref' => $public_ref, 'status' => 'requested', 'trace_id' => $trace );
		WCA_Repository::complete_idempotency( $claim['id'], 201, $response );
		WCA_Observability::metric( 'appointment_requested_total', 1, array( 'mode' => $meta['consultation_type'] ) );
		return $response;
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
		$expected_status  = WCA_Contracts::normalize_appointment_status( $data['expected_status'] ?? SWC_Helpers::status( $appointment_id ) );
		$expected_version = absint( $data['expected_version'] ?? SWC_Helpers::record_version( $appointment_id ) );
		return SWC_Helpers::with_lock( $appointment_id, function () use ( $appointment_id, $next, $data, $actor_user_id, $expected_status, $expected_version ) {
			$check = SWC_Helpers::assert_expected( $appointment_id, $expected_status, $expected_version );
			if ( is_wp_error( $check ) ) { return $check; }
			$current = SWC_Helpers::status( $appointment_id );
			$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
			if ( ! WCA_Contracts::can_transition( $actor, $current, $next ) ) { return new WP_Error( 'wca_transition', __( 'That transition is not permitted.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			if ( 'reschedule_pending' === $next ) {
				$hold_token = sanitize_text_field( $data['hold_token'] ?? '' );
				$hold = WCA_Repository::get_slot_hold( $hold_token );
				$hold_check = WCA_Plan_Guard::validate_reschedule_hold( $hold, $appointment_id, $actor_user_id );
				if ( is_wp_error( $hold_check ) ) { return $hold_check; }
				update_post_meta( $appointment_id, '_swc_proposed_at_utc', $hold['start_utc'] );
				update_post_meta( $appointment_id, '_swc_proposed_end_utc', $hold['end_utc'] );
				update_post_meta( $appointment_id, '_swc_proposed_hold_token', $hold_token );
				update_post_meta( $appointment_id, '_swc_proposed_by_user_id', $actor_user_id );
				update_post_meta( $appointment_id, '_swc_proposed_expires_at', $hold['expires_at'] );
			}
			if ( 'confirmed' === $next && 'reschedule_pending' === $current ) {
				$token = (string) SWC_Helpers::meta( $appointment_id, 'proposed_hold_token' );
				$hold = WCA_Repository::get_slot_hold( $token );
				if ( ! $hold || 'held' !== $hold['status'] || strtotime( $hold['expires_at'] . ' UTC' ) <= time() ) { return new WP_Error( 'wca_reschedule_expired', __( 'The proposed time has expired.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
				$booked = WCA_Repository::book_slot( $token, $appointment_id );
				if ( is_wp_error( $booked ) ) { return $booked; }
				WCA_Repository::release_appointment_slot( $appointment_id, 'released', $token );
				update_post_meta( $appointment_id, '_swc_preferred_at_utc', $hold['start_utc'] );
				update_post_meta( $appointment_id, '_swc_appointment_end_utc', $hold['end_utc'] );
				foreach ( array( 'proposed_at_utc','proposed_end_utc','proposed_hold_token','proposed_by_user_id','proposed_expires_at' ) as $key ) { delete_post_meta( $appointment_id, '_swc_' . $key ); }
			}
			if ( 'checked_in' === $next ) { update_post_meta( $appointment_id, '_swc_checked_in_at_utc', WCA_Repository::now() ); update_post_meta( $appointment_id, '_swc_actual_mode', sanitize_key( $data['actual_mode'] ?? SWC_Helpers::meta( $appointment_id, 'consultation_type' ) ) ); }
			if ( 'completed' === $next ) {
				if ( ! SWC_Helpers::meta( $appointment_id, 'checked_in_at_utc' ) ) { return new WP_Error( 'wca_checkin_required', __( 'The appointment must be checked in before completion.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
				update_post_meta( $appointment_id, '_swc_completed_at_utc', WCA_Repository::now() );
			}
			if ( in_array( $next, array( 'cancelled','declined','no_show' ), true ) ) { WCA_Repository::release_appointment_slot( $appointment_id ); }
			update_post_meta( $appointment_id, '_swc_status', $next );
			if ( isset( $data['reason_code'] ) ) { update_post_meta( $appointment_id, '_swc_transition_reason_code', sanitize_key( $data['reason_code'] ) ); }
			SWC_Helpers::bump_version( $appointment_id );
			$trace = WCA_Observability::trace_id();
			$public_ref = (string) SWC_Helpers::meta( $appointment_id, 'public_ref', 'appointment-' . $appointment_id );
			$event_type = self::event_for_transition( $next );
			$payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'appointment_ref' => $public_ref, 'old_status' => $current, 'new_status' => $next, 'scheduled_at_utc' => SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ), 'completed_at_utc' => SWC_Helpers::meta( $appointment_id, 'completed_at_utc' ), 'trace_id' => $trace );
			WCA_Repository::append_event( $event_type, 'appointment', $public_ref, $payload, $actor_user_id, $trace );
			WCA_Repository::enqueue( $event_type, $public_ref, $payload, $trace );
			WCA_Repository::enqueue( 'File19.NotificationRequested.v1', $public_ref, array( 'event' => strtolower( str_replace( '.', '_', $event_type ) ), 'appointment_ref' => $public_ref, 'recipients' => array( absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ), absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) ) ), $trace );
			WCA_Repository::enqueue( 'File17.AppointmentContextChanged.v1', $public_ref, self::file17_context_payload( $appointment_id ), $trace );
			if ( 'completed' === $next ) {
				$eligibility = WCA_Repository::grant_review_eligibility( $appointment_id, absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ), absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ), absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) ) );
				if ( ! is_wp_error( $eligibility ) ) {
					$review_payload = array( 'event_id' => WCA_Repository::uuid(), 'occurred_at' => gmdate( 'c' ), 'eligibility_ref' => $eligibility['public_ref'], 'appointment_ref' => $public_ref, 'reviewer_subject_uuid' => WCA_Authorization::subject_uuid( $eligibility['reviewer_user_id'] ), 'doctor_subject_uuid' => WCA_Authorization::subject_uuid( $eligibility['doctor_user_id'] ), 'trace_id' => $trace );
					WCA_Repository::append_event( 'ReviewEligibilityGranted.v1', 'review_eligibility', $eligibility['public_ref'], $review_payload, $actor_user_id, $trace );
					WCA_Repository::enqueue( 'ReviewEligibilityGranted.v1', $eligibility['public_ref'], $review_payload, $trace );
				}
			}
			SWC_Helpers::audit( $appointment_id, 'wca-transition', array( 'old_status' => $current, 'new_status' => $next, 'reason' => sanitize_text_field( $data['reason_code'] ?? '' ), 'details' => array( 'trace_id' => $trace ) ) );
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
		$private = WCA_Repository::get_clinic( $id_or_slug, false );
		if ( ! $private || 'active' !== (string) $private['status'] ) { return array(); }
		$owner_id = absint( $private['owner_user_id'] ?? 0 );
		if ( ! $owner_id || ! SWC_Doctor_Authority::is_eligible( $owner_id ) ) { return array(); }
		$clinic = WCA_Repository::get_clinic( $private['id'], true );
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
		$result = WCA_Repository::create_complaint( $data );
		if ( is_wp_error( $result ) ) { return $result; }
		$trace = WCA_Observability::trace_id();
		WCA_Repository::append_event( 'AppointmentComplaintSubmitted.v1', 'complaint', $result['public_ref'], array( 'complaint_ref' => $result['public_ref'], 'appointment_ref' => $appointment_id ? SWC_Helpers::meta( $appointment_id, 'public_ref' ) : '', 'category' => $result['category'], 'trace_id' => $trace ), $actor_user_id, $trace );
		WCA_Repository::enqueue( 'CF02.CaseRequested.v1', $result['public_ref'], array( 'case_type' => 'appointment_complaint', 'complaint_ref' => $result['public_ref'], 'purpose_limit' => $result['purpose_limit'] ), $trace );
		WCA_Repository::enqueue( 'File19.NotificationRequested.v1', $result['public_ref'], array( 'event' => 'complaint_submitted', 'recipients' => array( $actor_user_id ) ), $trace );
		return $result;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_payment_intent( $appointment_id, $provider = 'manual', $actor_user_id = 0 ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$service_id = absint( SWC_Helpers::meta( $appointment_id, 'service_id' ) );
		$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;
		if ( ! $service ) { return new WP_Error( 'wca_service_missing', __( 'Appointment service is unavailable.', 'worldwide-clinic-appointments' ) ); }
		$result = WCA_Repository::create_payment_intent( array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'currency' => $service['currency'], 'amount_minor' => $service['fee_minor'], 'status' => 'pending', 'metadata' => array( 'service_ref' => $service['public_ref'], 'commission_percent' => 0 ) ) );
		if ( is_wp_error( $result ) ) { return $result; }
		WCA_Repository::enqueue( 'CF03.PaymentIntentRequested.v1', $result['public_ref'], array( 'payment_intent_ref' => $result['public_ref'], 'appointment_ref' => SWC_Helpers::meta( $appointment_id, 'public_ref' ), 'currency' => $result['currency'], 'amount_minor' => $result['amount_minor'], 'platform_commission_minor' => 0 ), WCA_Observability::trace_id() );
		return $result;
	}

	/** @return string|WP_Error */
	public static function appointment_ics( $appointment_id, $actor_user_id = 0 ) {
		$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
		if ( is_wp_error( $access ) ) { return $access; }
		$start = (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' );
		$end = (string) SWC_Helpers::meta( $appointment_id, 'appointment_end_utc' );
		if ( ! $start ) { return new WP_Error( 'wca_calendar_time', __( 'Appointment time is unavailable.', 'worldwide-clinic-appointments' ) ); }
		if ( ! $end ) { $end = gmdate( 'Y-m-d H:i:s', strtotime( $start . ' UTC' ) + max( 10, absint( SWC_Helpers::meta( $appointment_id, 'appointment_duration', 30 ) ) ) * 60 ); }
		$uid = (string) SWC_Helpers::meta( $appointment_id, 'public_ref', 'appointment-' . $appointment_id ) . '@sabrihomeopathy.com';
		$summary = 'Clinic Appointment';
		$lines = array(
			'BEGIN:VCALENDAR','VERSION:2.0','PRODID:-//Sabri Social Homeopathy Platform//File 08//EN','CALSCALE:GREGORIAN','METHOD:PUBLISH','BEGIN:VEVENT',
			'UID:' . self::ics_escape( $uid ),
			'DTSTAMP:' . gmdate( 'Ymd\THis\Z' ),
			'DTSTART:' . gmdate( 'Ymd\THis\Z', strtotime( $start . ' UTC' ) ),
			'DTEND:' . gmdate( 'Ymd\THis\Z', strtotime( $end . ' UTC' ) ),
			'SUMMARY:' . self::ics_escape( $summary ),
			'DESCRIPTION:' . self::ics_escape( 'Private appointment details are available only in the authenticated platform.' ),
			'CLASS:PRIVATE','TRANSP:OPAQUE','END:VEVENT','END:VCALENDAR',
		);
		return implode( "\r\n", $lines ) . "\r\n";
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

	private static function appointment_request_fingerprint( $data ) {
		return array(
			'hold_token'       => sanitize_text_field( $data['hold_token'] ?? '' ),
			'patient_user_id'  => absint( $data['patient_user_id'] ?? 0 ),
			'guardian_user_id' => absint( $data['guardian_user_id'] ?? 0 ),
			'category'         => sanitize_key( $data['category'] ?? '' ),
			'timezone'         => sanitize_text_field( $data['timezone'] ?? '' ),
		);
	}

	private static function appointment_terms_text() {
		return 'Appointment processing, privacy, telehealth where selected, guardian authority where applicable, emergency limitation, cancellation policy, and zero platform commission terms version ' . self::TERMS_VERSION;
	}

	public static function handle_doctor_suspended( $doctor_user_id, $reason = '' ) {
		$doctor_user_id = absint( $doctor_user_id );
		$appointments = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => 'private', 'posts_per_page' => 500, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_swc_doctor_id', 'value' => $doctor_user_id ) ) ) );
		foreach ( $appointments as $id ) {
			$status = SWC_Helpers::status( $id );
			if ( ! WCA_Contracts::is_terminal( $status ) ) {
				update_post_meta( $id, '_swc_doctor_authority_hold', '1' );
				update_post_meta( $id, '_swc_doctor_authority_hold_reason', sanitize_text_field( $reason ) );
				WCA_Repository::enqueue( 'File19.NotificationRequested.v1', (string) SWC_Helpers::meta( $id, 'public_ref' ), array( 'event' => 'doctor_authority_hold', 'recipients' => array( absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) ) ) ), WCA_Observability::trace_id() );
			}
		}
	}

	public static function handle_payment_status_changed( $payment_ref, $status ) {
		WCA_Observability::log( 'info', 'payment_status_changed', array( 'payment_ref' => $payment_ref, 'status' => sanitize_key( $status ) ) );
	}
}
