<?php
/**
 * Appointment submission and role-specific lifecycle actions.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Appointments {
	public function hooks() {
		add_action( 'init', array( 'SWC_Activator', 'register_type' ) );
		add_action( 'admin_post_swc_submit_appointment', array( $this, 'submit' ) );
		add_action( 'admin_post_swc_patient_cancel', array( $this, 'patient_cancel' ) );
		add_action( 'admin_post_swc_patient_accept_reschedule', array( $this, 'patient_accept_reschedule' ) );
		add_action( 'admin_post_swc_patient_accept_reassignment', array( $this, 'patient_accept_reassignment' ) );
		add_action( 'admin_post_swc_patient_decline_reassignment', array( $this, 'patient_decline_reassignment' ) );
		add_action( 'admin_post_swc_doctor_update', array( $this, 'doctor_update' ) );
		add_action( 'admin_post_swc_save_availability', array( $this, 'save_availability' ) );
	}

	public function submit() {
		$this->require_login( __( 'Log in to request an appointment.', 'worldwide-clinic-appointments' ) );
		check_admin_referer( 'swc_submit_appointment', 'swc_nonce' );

		$user_id = get_current_user_id();
		if ( SWC_Helpers::rate_limit_hit( $user_id, 5, HOUR_IN_SECONDS ) ) {
			wp_die( esc_html__( 'Please wait before submitting another request.', 'worldwide-clinic-appointments' ), '', array( 'response' => 429 ) );
		}

		$doctor_ref = isset( $_POST['doctor_ref'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['doctor_ref'] ) ) ) : '';
		$doctor     = SWC_Helpers::practitioner_id( $doctor_ref );
		if ( ! $doctor || ! in_array( $doctor, SWC_Helpers::requestable_doctor_ids(), true ) ) {
			wp_die( esc_html__( 'Choose an eligible verified doctor.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		$mode = isset( $_POST['consultation_type'] ) ? sanitize_key( wp_unslash( $_POST['consultation_type'] ) ) : '';
		if ( ! in_array( $mode, array( 'online', 'in-person' ), true ) || ! SWC_Helpers::doctor_accepts_mode( $doctor, $mode ) ) {
			wp_die( esc_html__( 'Choose a consultation type enabled by the selected doctor.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		$date = isset( $_POST['preferred_date'] ) ? SWC_Helpers::limit_text( $_POST['preferred_date'], 10 ) : '';
		$time = isset( $_POST['preferred_time'] ) ? SWC_Helpers::limit_text( $_POST['preferred_time'], 5 ) : '';
		$zone = isset( $_POST['patient_timezone'] ) ? SWC_Helpers::limit_text( $_POST['patient_timezone'], 100 ) : 'UTC';
		$utc  = SWC_Helpers::to_utc( $date, $time, $zone );
		if ( ! $utc || strtotime( $utc . ' UTC' ) <= time() ) {
			wp_die( esc_html__( 'Choose a valid, unambiguous future date and time.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		$availability = SWC_Helpers::availability( $doctor );
		$duration     = absint( $availability['duration'] );
		if ( ! SWC_Helpers::slot_is_available( $doctor, $utc, $duration ) ) {
			wp_die( esc_html__( 'The requested time is outside the doctor’s published schedule or conflicts with an confirmed appointment.', 'worldwide-clinic-appointments' ), '', array( 'response' => 409 ) );
		}

		$phone    = isset( $_POST['phone'] ) ? SWC_Helpers::phone( wp_unslash( $_POST['phone'] ) ) : '';
		$whatsapp = isset( $_POST['whatsapp'] ) ? SWC_Helpers::phone( wp_unslash( $_POST['whatsapp'] ) ) : '';
		$country  = isset( $_POST['country'] ) ? SWC_Helpers::limit_text( $_POST['country'], 100 ) : '';
		$city     = isset( $_POST['city'] ) ? SWC_Helpers::limit_text( $_POST['city'], 100 ) : '';
		$reason   = isset( $_POST['reason'] ) ? SWC_Helpers::limit_text( $_POST['reason'], 1500, true ) : '';
		$concern  = isset( $_POST['concern_duration'] ) ? SWC_Helpers::limit_text( $_POST['concern_duration'], 120 ) : '';
		if ( ! $phone || ! $whatsapp || '' === trim( $country ) || '' === trim( $reason ) || empty( $_POST['consent'] ) || empty( $_POST['emergency_confirm'] ) ) {
			wp_die( esc_html__( 'Complete the required contact, location, reason, emergency acknowledgment, and consent fields.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		$id = wp_insert_post(
			array(
				'post_type'   => SWC_Helpers::TYPE,
				'post_status' => 'private',
				'post_title'  => sprintf( __( 'Appointment Request — %s', 'worldwide-clinic-appointments' ), current_time( 'Y-m-d H:i' ) ),
				'post_author' => $user_id,
			),
			true
		);
		if ( is_wp_error( $id ) ) {
			wp_die( esc_html__( 'The request could not be saved.', 'worldwide-clinic-appointments' ), '', array( 'response' => 500 ) );
		}

		$meta = array(
			'patient_user_id'      => $user_id,
			'doctor_id'            => $doctor,
			'status'               => 'requested',
			'consultation_type'    => $mode,
			'preferred_at_utc'     => $utc,
			'patient_timezone'     => $zone,
			'country'              => $country,
			'city'                 => $city,
			'phone'                => $phone,
			'whatsapp'             => $whatsapp,
			'reason'               => $reason,
			'concern_duration'     => $concern,
			'appointment_duration' => $duration,
			'consent_at'           => current_time( 'mysql', true ),
			'consent_version'      => '2026-07-30',
			'record_version'       => 1,
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, '_swc_' . $key, $value );
		}
		update_user_meta( $user_id, '_swc_patient_timezone', $zone );

		if ( ! SWC_Helpers::audit(
			$id,
			'appointment-requested',
			array(
				'old_status'    => '',
				'new_status'    => 'requested',
				'new_doctor_id' => $doctor,
				'details'       => array( 'consultation_type' => $mode, 'appointment_duration' => $duration ),
			)
		) ) {
			wp_delete_post( $id, true );
			wp_die( esc_html__( 'The request could not be safely audited. Nothing was submitted.', 'worldwide-clinic-appointments' ), '', array( 'response' => 500 ) );
		}

		$this->notify_participants( $id, 'appointment-requested', __( 'New appointment request', 'worldwide-clinic-appointments' ), __( 'A new appointment request was received.', 'worldwide-clinic-appointments' ), 'normal' );
		$this->redirect( 'patient', array( 'requested' => '1' ) );
	}

	public function patient_cancel() {
		$this->require_login( __( 'Access denied.', 'worldwide-clinic-appointments' ) );
		$id = isset( $_POST['appointment_id'] ) ? absint( $_POST['appointment_id'] ) : 0;
		check_admin_referer( 'swc_patient_cancel_' . $id );
		if ( ! SWC_Helpers::can_patient_manage( $id ) ) {
			wp_die( esc_html__( 'You cannot manage this appointment.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );
		}
		$this->process_locked_transition( $id, 'patient', 'cancelled', 'patient-cancelled', __( 'Cancelled by patient', 'worldwide-clinic-appointments' ) );
		$this->notify_participants( $id, 'appointment-cancelled', __( 'Appointment cancelled', 'worldwide-clinic-appointments' ), __( 'The appointment request was cancelled.', 'worldwide-clinic-appointments' ), 'high' );
		$this->redirect( 'patient' );
	}

	public function patient_accept_reschedule() {
		$this->require_login( __( 'Access denied.', 'worldwide-clinic-appointments' ) );
		$id = isset( $_POST['appointment_id'] ) ? absint( $_POST['appointment_id'] ) : 0;
		check_admin_referer( 'swc_patient_accept_' . $id );
		if ( ! SWC_Helpers::can_patient_manage( $id ) ) {
			wp_die( esc_html__( 'You cannot manage this appointment.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );
		}
		$expected_status  = isset( $_POST['expected_status'] ) ? sanitize_key( wp_unslash( $_POST['expected_status'] ) ) : '';
		$expected_version = isset( $_POST['expected_version'] ) ? absint( $_POST['expected_version'] ) : 0;
		$result = SWC_Helpers::with_lock(
			$id,
			function () use ( $id, $expected_status, $expected_version ) {
				$check = SWC_Helpers::assert_expected( $id, $expected_status, $expected_version );
				if ( is_wp_error( $check ) ) {
					return $check;
				}
				$current = SWC_Helpers::status( $id );
				if ( ! SWC_Helpers::can_transition( 'patient', $current, 'confirmed' ) ) {
					return new WP_Error( 'swc_transition', __( 'This reschedule can no longer be accepted.', 'worldwide-clinic-appointments' ) );
				}
				$proposed = (string) SWC_Helpers::meta( $id, 'proposed_at_utc' );
				$expires  = (string) SWC_Helpers::meta( $id, 'proposed_expires_at' );
				$doctor   = absint( SWC_Helpers::meta( $id, 'doctor_id' ) );
				$duration = absint( SWC_Helpers::meta( $id, 'appointment_duration', 30 ) );
				if ( ! $proposed || strtotime( $proposed . ' UTC' ) <= time() || ( $expires && strtotime( $expires . ' UTC' ) <= time() ) ) {
					return new WP_Error( 'swc_expired', __( 'The proposed time has expired.', 'worldwide-clinic-appointments' ) );
				}
				if ( ! SWC_Helpers::slot_is_available( $doctor, $proposed, $duration, $id ) ) {
					return new WP_Error( 'swc_conflict', __( 'The proposed time is no longer available.', 'worldwide-clinic-appointments' ) );
				}
				update_post_meta( $id, '_swc_preferred_at_utc', $proposed );
				update_post_meta( $id, '_swc_status', 'confirmed' );
				delete_post_meta( $id, '_swc_proposed_at_utc' );
				delete_post_meta( $id, '_swc_proposed_timezone' );
				delete_post_meta( $id, '_swc_proposed_expires_at' );
				SWC_Helpers::bump_version( $id );
				if ( ! SWC_Helpers::audit( $id, 'reschedule-accepted', array( 'old_status' => $current, 'new_status' => 'confirmed', 'reason' => __( 'Confirmed by patient', 'worldwide-clinic-appointments' ) ) ) ) {
					return new WP_Error( 'swc_audit', __( 'The update could not be audited.', 'worldwide-clinic-appointments' ) );
				}
				return true;
			}
		);
		$this->die_on_error( $result );
		$this->notify_participants( $id, 'reschedule-accepted', __( 'Proposed time accepted', 'worldwide-clinic-appointments' ), __( 'The patient accepted the proposed appointment time.', 'worldwide-clinic-appointments' ), 'high' );
		$this->redirect( 'patient' );
	}

	public function patient_accept_reassignment() {
		$this->patient_reassignment_action( true );
	}

	public function patient_decline_reassignment() {
		$this->patient_reassignment_action( false );
	}

	private function patient_reassignment_action( $accept ) {
		$this->require_login( __( 'Access denied.', 'worldwide-clinic-appointments' ) );
		$id = isset( $_POST['appointment_id'] ) ? absint( $_POST['appointment_id'] ) : 0;
		check_admin_referer( ( $accept ? 'swc_patient_accept_reassignment_' : 'swc_patient_decline_reassignment_' ) . $id );
		if ( ! SWC_Helpers::can_patient_manage( $id ) ) {
			wp_die( esc_html__( 'You cannot manage this appointment.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );
		}
		$expected_version = isset( $_POST['expected_version'] ) ? absint( $_POST['expected_version'] ) : 0;
		$result = SWC_Helpers::with_lock(
			$id,
			function () use ( $id, $accept, $expected_version ) {
				if ( SWC_Helpers::record_version( $id ) !== $expected_version ) {
					return new WP_Error( 'swc_stale', __( 'This appointment changed. Refresh before responding.', 'worldwide-clinic-appointments' ) );
				}
				$new_doctor = absint( SWC_Helpers::meta( $id, 'proposed_doctor_id' ) );
				$expires    = (string) SWC_Helpers::meta( $id, 'reassignment_expires_at' );
				if ( ! $new_doctor || ( $expires && strtotime( $expires . ' UTC' ) <= time() ) ) {
					return new WP_Error( 'swc_expired', __( 'The reassignment proposal has expired.', 'worldwide-clinic-appointments' ) );
				}
				$old_doctor = absint( SWC_Helpers::meta( $id, 'doctor_id' ) );
				if ( $accept ) {
					if ( ! in_array( $new_doctor, SWC_Helpers::doctor_ids(), true ) ) {
						return new WP_Error( 'swc_doctor', __( 'The proposed doctor is no longer eligible.', 'worldwide-clinic-appointments' ) );
					}
					$mode = (string) SWC_Helpers::meta( $id, 'consultation_type' );
					if ( ! SWC_Helpers::doctor_accepts_mode( $new_doctor, $mode ) ) {
						return new WP_Error( 'swc_mode', __( 'The proposed doctor does not offer the selected consultation type.', 'worldwide-clinic-appointments' ) );
					}
					$utc      = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
					$duration = absint( SWC_Helpers::availability( $new_doctor )['duration'] );
					if ( ! SWC_Helpers::slot_is_available( $new_doctor, $utc, $duration, $id ) ) {
						return new WP_Error( 'swc_slot', __( 'The current time is not available with the proposed doctor.', 'worldwide-clinic-appointments' ) );
					}
					update_post_meta( $id, '_swc_doctor_id', $new_doctor );
					update_post_meta( $id, '_swc_appointment_duration', $duration );
				}
				delete_post_meta( $id, '_swc_proposed_doctor_id' );
				delete_post_meta( $id, '_swc_reassignment_reason' );
				delete_post_meta( $id, '_swc_reassignment_expires_at' );
				SWC_Helpers::bump_version( $id );
				$event = $accept ? 'reassignment-accepted' : 'reassignment-declined';
				if ( ! SWC_Helpers::audit(
					$id,
					$event,
					array(
						'old_doctor_id' => $old_doctor,
						'new_doctor_id' => $accept ? $new_doctor : $old_doctor,
						'reason'        => $accept ? __( 'Confirmed by patient', 'worldwide-clinic-appointments' ) : __( 'Declined by patient', 'worldwide-clinic-appointments' ),
					)
				) ) {
					return new WP_Error( 'swc_audit', __( 'The response could not be audited.', 'worldwide-clinic-appointments' ) );
				}
				return array( 'old_doctor' => $old_doctor, 'new_doctor' => $new_doctor );
			}
		);
		$this->die_on_error( $result );
		$title = $accept ? __( 'Doctor reassignment accepted', 'worldwide-clinic-appointments' ) : __( 'Doctor reassignment declined', 'worldwide-clinic-appointments' );
		$body  = $accept ? __( 'The patient accepted the proposed doctor reassignment.', 'worldwide-clinic-appointments' ) : __( 'The patient declined the proposed doctor reassignment.', 'worldwide-clinic-appointments' );
		$this->notify_participants( $id, $accept ? 'reassignment-accepted' : 'reassignment-declined', $title, $body, 'high', array( absint( $result['old_doctor'] ), absint( $result['new_doctor'] ) ) );
		$this->redirect( 'patient' );
	}

	public function doctor_update() {
		$this->require_login( __( 'Access denied.', 'worldwide-clinic-appointments' ) );
		$id = isset( $_POST['appointment_id'] ) ? absint( $_POST['appointment_id'] ) : 0;
		check_admin_referer( 'swc_doctor_update_' . $id );
		if ( ! SWC_Helpers::can_doctor_manage( $id ) ) {
			wp_die( esc_html__( 'You cannot manage this appointment.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );
		}

		$next             = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$expected_status  = isset( $_POST['expected_status'] ) ? sanitize_key( wp_unslash( $_POST['expected_status'] ) ) : '';
		$expected_version = isset( $_POST['expected_version'] ) ? absint( $_POST['expected_version'] ) : 0;
		$private_note     = isset( $_POST['doctor_private_note'] ) ? SWC_Helpers::limit_text( $_POST['doctor_private_note'], 1000, true ) : '';
		$patient_message  = isset( $_POST['patient_message'] ) ? SWC_Helpers::limit_text( $_POST['patient_message'], 1000, true ) : '';

		$result = SWC_Helpers::with_lock(
			$id,
			function () use ( $id, $next, $expected_status, $expected_version, $private_note, $patient_message ) {
				$check = SWC_Helpers::assert_expected( $id, $expected_status, $expected_version );
				if ( is_wp_error( $check ) ) {
					return $check;
				}
				$current = SWC_Helpers::status( $id );
				if ( ! isset( SWC_Helpers::statuses()[ $next ] ) ) {
					return new WP_Error( 'swc_status', __( 'Invalid appointment status.', 'worldwide-clinic-appointments' ) );
				}
				$status_changed = $next !== $current;
				if ( $status_changed && ! SWC_Helpers::can_transition( 'doctor', $current, $next ) ) {
					return new WP_Error( 'swc_transition', __( 'That status transition is not permitted.', 'worldwide-clinic-appointments' ) );
				}
				$doctor   = get_current_user_id();
				$duration = absint( SWC_Helpers::meta( $id, 'appointment_duration', SWC_Helpers::availability( $doctor )['duration'] ) );
				$details  = array();

				if ( 'confirmed' === $next && $status_changed ) {
					$utc = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
					if ( ! SWC_Helpers::slot_is_available( $doctor, $utc, $duration, $id ) ) {
						return new WP_Error( 'swc_conflict', __( 'This time is outside your published schedule or conflicts with another confirmed appointment.', 'worldwide-clinic-appointments' ) );
					}
				}
				if ( 'completed' === $next && $status_changed ) {
					$utc = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
					if ( ! $utc || strtotime( $utc . ' UTC' ) > time() ) {
						return new WP_Error( 'swc_future', __( 'A future appointment cannot be marked completed.', 'worldwide-clinic-appointments' ) );
					}
				}
				if ( 'reschedule_pending' === $next ) {
					$date = isset( $_POST['new_date'] ) ? SWC_Helpers::limit_text( $_POST['new_date'], 10 ) : '';
					$time = isset( $_POST['new_time'] ) ? SWC_Helpers::limit_text( $_POST['new_time'], 5 ) : '';
					$zone = isset( $_POST['new_timezone'] ) ? SWC_Helpers::limit_text( $_POST['new_timezone'], 100 ) : 'UTC';
					$new  = SWC_Helpers::to_utc( $date, $time, $zone );
					if ( ! $new || strtotime( $new . ' UTC' ) <= time() || ! SWC_Helpers::slot_is_available( $doctor, $new, $duration, $id ) ) {
						return new WP_Error( 'swc_reschedule', __( 'Provide a future, unambiguous, available reschedule time.', 'worldwide-clinic-appointments' ) );
					}
					$expiry = min( strtotime( '+48 hours' ), strtotime( $new . ' UTC' ) - HOUR_IN_SECONDS );
					if ( $expiry <= time() ) {
						return new WP_Error( 'swc_expiry', __( 'The proposed time is too soon for patient confirmation.', 'worldwide-clinic-appointments' ) );
					}
					update_post_meta( $id, '_swc_proposed_at_utc', $new );
					update_post_meta( $id, '_swc_proposed_timezone', $zone );
					update_post_meta( $id, '_swc_proposed_expires_at', gmdate( 'Y-m-d H:i:s', $expiry ) );
					$details['proposed_at_utc'] = $new;
				}

				update_post_meta( $id, '_swc_doctor_private_note', $private_note );
				update_post_meta( $id, '_swc_patient_message', $patient_message );
				if ( $status_changed ) {
					update_post_meta( $id, '_swc_status', $next );
					if ( 'reschedule_pending' !== $next ) {
						delete_post_meta( $id, '_swc_proposed_at_utc' );
						delete_post_meta( $id, '_swc_proposed_timezone' );
						delete_post_meta( $id, '_swc_proposed_expires_at' );
					}
				}
				SWC_Helpers::bump_version( $id );
				$event = $status_changed ? 'doctor-status-updated' : 'doctor-notes-updated';
				if ( ! SWC_Helpers::audit(
					$id,
					$event,
					array(
						'old_status' => $current,
						'new_status' => $next,
						'reason'     => $patient_message,
						'details'    => $details,
					)
				) ) {
					return new WP_Error( 'swc_audit', __( 'The update could not be audited.', 'worldwide-clinic-appointments' ) );
				}
				return array( 'status_changed' => $status_changed, 'status' => $next );
			}
		);
		$this->die_on_error( $result );
		$label = SWC_Helpers::statuses()[ $result['status'] ];
		$this->notify_participants(
			$id,
			$result['status_changed'] ? 'appointment-status-updated' : 'appointment-message-updated',
			__( 'Appointment updated', 'worldwide-clinic-appointments' ),
			$result['status_changed'] ? sprintf( __( 'The appointment status is now %s.', 'worldwide-clinic-appointments' ), $label ) : __( 'The doctor updated the appointment message.', 'worldwide-clinic-appointments' ),
			$result['status_changed'] ? 'high' : 'normal'
		);
		$this->redirect( 'doctor' );
	}

	public function save_availability() {
		$this->require_login( __( 'Verified doctor access is required.', 'worldwide-clinic-appointments' ) );
		$id = get_current_user_id();
		if ( ! SWC_Helpers::is_verified_doctor( $id ) ) {
			wp_die( esc_html__( 'Verified doctor access is required.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'swc_save_availability', 'swc_nonce' );

		$days = isset( $_POST['days'] ) ? array_values( array_intersect( array_map( 'sanitize_key', (array) wp_unslash( $_POST['days'] ) ), SWC_Helpers::weekdays() ) ) : array();
		$zone = isset( $_POST['timezone'] ) ? SWC_Helpers::limit_text( $_POST['timezone'], 100 ) : 'UTC';
		$start = isset( $_POST['start_time'] ) ? SWC_Helpers::limit_text( $_POST['start_time'], 5 ) : '';
		$end   = isset( $_POST['end_time'] ) ? SWC_Helpers::limit_text( $_POST['end_time'], 5 ) : '';
		$data  = array(
			'days'        => $days,
			'start'       => $start,
			'end'         => $end,
			'timezone'    => $zone,
			'duration'    => min( 180, max( 10, isset( $_POST['duration'] ) ? absint( $_POST['duration'] ) : 30 ) ),
			'online'      => isset( $_POST['online'] ),
			'in_person'   => isset( $_POST['in_person'] ),
			'accepting'   => isset( $_POST['accepting'] ),
			'unavailable' => isset( $_POST['unavailable'] ),
		);
		if ( ! SWC_Helpers::availability_is_valid( $data ) ) {
			wp_die( esc_html__( 'Publish at least one day, a valid start/end window, a valid time zone, and one consultation type. Accepting and unavailable cannot both be enabled.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		update_user_meta( $id, '_swc_available_days', $data['days'] );
		update_user_meta( $id, '_swc_start_time', $data['start'] );
		update_user_meta( $id, '_swc_end_time', $data['end'] );
		update_user_meta( $id, '_swc_timezone', $data['timezone'] );
		update_user_meta( $id, '_swc_duration', $data['duration'] );
		foreach ( array( 'online', 'in_person', 'accepting', 'unavailable' ) as $key ) {
			update_user_meta( $id, '_swc_' . $key, $data[ $key ] ? '1' : '0' );
		}
		$this->redirect( 'availability', array( 'updated' => '1' ) );
	}

	private function process_locked_transition( $id, $actor, $next, $event, $reason ) {
		$expected_status  = isset( $_POST['expected_status'] ) ? sanitize_key( wp_unslash( $_POST['expected_status'] ) ) : '';
		$expected_version = isset( $_POST['expected_version'] ) ? absint( $_POST['expected_version'] ) : 0;
		$result = SWC_Helpers::with_lock(
			$id,
			function () use ( $id, $actor, $next, $event, $reason, $expected_status, $expected_version ) {
				$check = SWC_Helpers::assert_expected( $id, $expected_status, $expected_version );
				if ( is_wp_error( $check ) ) {
					return $check;
				}
				$current = SWC_Helpers::status( $id );
				if ( ! SWC_Helpers::can_transition( $actor, $current, $next ) ) {
					return new WP_Error( 'swc_transition', __( 'That status transition is not permitted.', 'worldwide-clinic-appointments' ) );
				}
				update_post_meta( $id, '_swc_status', $next );
				delete_post_meta( $id, '_swc_proposed_at_utc' );
				delete_post_meta( $id, '_swc_proposed_timezone' );
				delete_post_meta( $id, '_swc_proposed_expires_at' );
				SWC_Helpers::bump_version( $id );
				if ( ! SWC_Helpers::audit( $id, $event, array( 'old_status' => $current, 'new_status' => $next, 'reason' => $reason ) ) ) {
					return new WP_Error( 'swc_audit', __( 'The update could not be audited.', 'worldwide-clinic-appointments' ) );
				}
				return true;
			}
		);
		$this->die_on_error( $result );
	}

	private function notify_participants( $id, $event, $title, $body, $priority = 'normal', $extra_user_ids = array() ) {
		$patient = absint( get_post_field( 'post_author', $id ) );
		$doctor  = absint( SWC_Helpers::meta( $id, 'doctor_id' ) );
		$pages   = SWC_Helpers::pages();
		$targets = array_unique( array_filter( array_merge( array( $patient, $doctor ), array_map( 'absint', (array) $extra_user_ids ) ) ) );
		foreach ( $targets as $user_id ) {
			$link = $user_id === $doctor && ! empty( $pages['doctor'] ) ? get_permalink( $pages['doctor'] ) : ( ! empty( $pages['patient'] ) ? get_permalink( $pages['patient'] ) : home_url( '/' ) );
			SWC_Helpers::notify_user( $user_id, $event, $title, $body, $id, $link, $priority );
		}
	}

	private function require_login( $message ) {
		if ( ! is_user_logged_in() ) {
			wp_die( esc_html( $message ), '', array( 'response' => 403 ) );
		}
	}

	private function die_on_error( $result ) {
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 409 ) );
		}
	}

	private function redirect( $page_key, $args = array() ) {
		$pages = SWC_Helpers::pages();
		$url   = ! empty( $pages[ $page_key ] ) ? get_permalink( $pages[ $page_key ] ) : home_url( '/' );
		if ( $args ) {
			$url = add_query_arg( $args, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}
}
