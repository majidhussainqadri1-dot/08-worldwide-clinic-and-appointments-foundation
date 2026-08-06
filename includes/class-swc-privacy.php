<?php
/**
 * WordPress privacy export and erasure integration.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Privacy {
	const PAGE_SIZE = 50;

	public function hooks() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'erasers' ) );
		add_action( 'admin_init', array( $this, 'policy' ) );
	}

	public function exporters( $exporters ) {
		$exporters['worldwide-clinic-appointments'] = array(
			'exporter_friendly_name' => __( 'Worldwide Clinic Appointments', 'worldwide-clinic-appointments' ),
			'callback'               => array( $this, 'export' ),
		);
		return $exporters;
	}

	public function export( $email_address, $page = 1 ) {
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array( 'data' => array(), 'done' => true );
		}
		$page  = max( 1, absint( $page ) );
		$ids   = $this->related_ids( $user->ID, $page );
		$total = $this->related_count( $user->ID );
		$data  = array();
		foreach ( $ids as $appointment_id ) {
			$is_patient = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ) === $user->ID;
			$is_doctor  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) === $user->ID;
			$fields     = array(
				__( 'Appointment reference', 'worldwide-clinic-appointments' )      => $appointment_id,
				__( 'Relationship', 'worldwide-clinic-appointments' )               => $is_patient ? __( 'Patient', 'worldwide-clinic-appointments' ) : ( $is_doctor ? __( 'Doctor', 'worldwide-clinic-appointments' ) : __( 'Proposed doctor', 'worldwide-clinic-appointments' ) ),
				__( 'Status', 'worldwide-clinic-appointments' )                     => SWC_Helpers::statuses()[ SWC_Helpers::status( $appointment_id ) ],
				__( 'Patient user ID', 'worldwide-clinic-appointments' )            => SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ),
				__( 'Doctor user ID', 'worldwide-clinic-appointments' )             => SWC_Helpers::meta( $appointment_id, 'doctor_id' ),
				__( 'Proposed doctor user ID', 'worldwide-clinic-appointments' )    => SWC_Helpers::meta( $appointment_id, 'proposed_doctor_id' ),
				__( 'Consultation type', 'worldwide-clinic-appointments' )          => SWC_Helpers::meta( $appointment_id, 'consultation_type' ),
				__( 'Appointment time UTC', 'worldwide-clinic-appointments' )       => SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ),
				__( 'Patient time zone', 'worldwide-clinic-appointments' )          => SWC_Helpers::meta( $appointment_id, 'patient_timezone' ),
				__( 'Proposed time UTC', 'worldwide-clinic-appointments' )          => SWC_Helpers::meta( $appointment_id, 'proposed_at_utc' ),
				__( 'Proposed time zone', 'worldwide-clinic-appointments' )         => SWC_Helpers::meta( $appointment_id, 'proposed_timezone' ),
				__( 'Proposal expiry UTC', 'worldwide-clinic-appointments' )        => SWC_Helpers::meta( $appointment_id, 'proposed_expires_at' ),
				__( 'Reassignment expiry UTC', 'worldwide-clinic-appointments' )    => SWC_Helpers::meta( $appointment_id, 'reassignment_expires_at' ),
				__( 'Country', 'worldwide-clinic-appointments' )                    => SWC_Helpers::meta( $appointment_id, 'country' ),
				__( 'City', 'worldwide-clinic-appointments' )                       => SWC_Helpers::meta( $appointment_id, 'city' ),
				__( 'Phone', 'worldwide-clinic-appointments' )                      => SWC_Helpers::meta( $appointment_id, 'phone' ),
				__( 'WhatsApp', 'worldwide-clinic-appointments' )                   => SWC_Helpers::meta( $appointment_id, 'whatsapp' ),
				__( 'Reason', 'worldwide-clinic-appointments' )                     => SWC_Helpers::meta( $appointment_id, 'reason' ),
				__( 'Duration of concern', 'worldwide-clinic-appointments' )        => SWC_Helpers::meta( $appointment_id, 'concern_duration' ),
				__( 'Appointment duration minutes', 'worldwide-clinic-appointments' ) => SWC_Helpers::meta( $appointment_id, 'appointment_duration' ),
				__( 'Consent recorded UTC', 'worldwide-clinic-appointments' )       => SWC_Helpers::meta( $appointment_id, 'consent_at' ),
				__( 'Consent version', 'worldwide-clinic-appointments' )            => SWC_Helpers::meta( $appointment_id, 'consent_version' ),
				__( 'Patient-visible message', 'worldwide-clinic-appointments' )    => SWC_Helpers::meta( $appointment_id, 'patient_message' ),
				__( 'Reassignment reason', 'worldwide-clinic-appointments' )        => SWC_Helpers::meta( $appointment_id, 'reassignment_reason' ),
				__( 'Record version', 'worldwide-clinic-appointments' )             => SWC_Helpers::record_version( $appointment_id ),
			);
			if ( $is_doctor ) {
				$fields[ __( 'Private doctor/administrator note', 'worldwide-clinic-appointments' ) ] = SWC_Helpers::meta( $appointment_id, 'doctor_private_note' );
			}
			$audit = array();
			foreach ( SWC_Helpers::audit_rows( $appointment_id ) as $row ) {
				$audit[] = array(
					'event'          => $row->event ? $row->event : $row->action,
					'old_status'     => $row->old_status,
					'new_status'     => $row->new_status,
					'old_doctor_id'  => absint( $row->old_doctor_id ),
					'new_doctor_id'  => absint( $row->new_doctor_id ),
					'created_at_utc' => $row->created_at,
					'reason'         => absint( $row->actor_id ) === $user->ID ? $row->reason : '',
				);
			}
			$fields[ __( 'Audit history', 'worldwide-clinic-appointments' ) ] = wp_json_encode( $audit, JSON_PRETTY_PRINT );
			$rows = array();
			foreach ( $fields as $name => $value ) {
				$rows[] = array( 'name' => (string) $name, 'value' => is_scalar( $value ) ? (string) $value : wp_json_encode( $value ) );
			}
			$data[] = array(
				'group_id'    => 'worldwide-clinic-appointments',
				'group_label' => __( 'Worldwide Clinic Appointments', 'worldwide-clinic-appointments' ),
				'item_id'     => 'appointment-' . $appointment_id,
				'data'        => $rows,
			);
		}
		return array( 'data' => $data, 'done' => $page * self::PAGE_SIZE >= $total );
	}

	public function erasers( $erasers ) {
		$erasers['worldwide-clinic-appointments'] = array(
			'eraser_friendly_name' => __( 'Worldwide Clinic Appointments', 'worldwide-clinic-appointments' ),
			'callback'             => array( $this, 'erase' ),
		);
		return $erasers;
	}

	/**
	 * Erasure always processes the first remaining batch because each batch stops
	 * matching once identifiers are anonymized. This avoids page-offset skips.
	 */
	public function erase( $email_address, $page = 1 ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		global $wpdb;
		$user = get_user_by( 'email', $email_address );
		if ( ! $user ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		$ids      = $this->related_ids( $user->ID, 1 );
		$removed  = false;
		$retained = false;
		foreach ( $ids as $appointment_id ) {
			$is_patient = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ) === $user->ID;
			$is_doctor  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) === $user->ID;
			if ( $is_patient ) {
				foreach ( array( 'country', 'city', 'phone', 'whatsapp', 'reason', 'concern_duration', 'patient_message', 'consent_at', 'consent_version', 'patient_timezone', 'preferred_at_utc', 'proposed_at_utc', 'proposed_timezone', 'proposed_expires_at', 'reassignment_reason', 'reassignment_expires_at' ) as $key ) {
					delete_post_meta( $appointment_id, '_swc_' . $key );
				}
				update_post_meta( $appointment_id, '_swc_patient_user_id', 0 );
				wp_update_post( array( 'ID' => $appointment_id, 'post_author' => 0, 'post_title' => sprintf( 'Anonymized Appointment #%d', $appointment_id ) ) );
				if ( SWC_Helpers::can_transition( 'patient', SWC_Helpers::status( $appointment_id ), 'cancelled' ) ) {
					update_post_meta( $appointment_id, '_swc_status', 'cancelled' );
				}
				update_post_meta( $appointment_id, '_swc_erased', '1' );
				$removed  = true;
				$retained = true;
			}
			if ( $is_doctor ) {
				update_post_meta( $appointment_id, '_swc_doctor_id', 0 );
				delete_post_meta( $appointment_id, '_swc_doctor_private_note' );
				delete_post_meta( $appointment_id, '_swc_patient_message' );
				$removed  = true;
				$retained = true;
			}
			if ( absint( SWC_Helpers::meta( $appointment_id, 'proposed_doctor_id' ) ) === $user->ID ) {
				delete_post_meta( $appointment_id, '_swc_proposed_doctor_id' );
				delete_post_meta( $appointment_id, '_swc_reassignment_reason' );
				delete_post_meta( $appointment_id, '_swc_reassignment_expires_at' );
				$removed = true;
			}
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->prefix}swc_audit_log SET actor_id=0, note='', reason='', details_json='{}' WHERE appointment_id=%d AND actor_id=%d",
					$appointment_id,
					$user->ID
				)
			); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$done = 0 === $this->related_count( $user->ID );
		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => $retained ? array( __( 'A minimal anonymized appointment and status record was retained for integrity, security, and accountability. Direct identifiers, contact details, private notes, consent details, scheduling times, and user-linked audit content were removed.', 'worldwide-clinic-appointments' ) ) : array(),
			'done'           => $done,
		);
	}

	private function related_ids( $user_id, $page ) {
		global $wpdb;
		$offset = ( max( 1, absint( $page ) ) - 1 ) * self::PAGE_SIZE;
		$sql = "SELECT DISTINCT p.ID
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} patient ON patient.post_id=p.ID AND patient.meta_key='_swc_patient_user_id'
			LEFT JOIN {$wpdb->postmeta} doctor ON doctor.post_id=p.ID AND doctor.meta_key='_swc_doctor_id'
			LEFT JOIN {$wpdb->postmeta} proposed ON proposed.post_id=p.ID AND proposed.meta_key='_swc_proposed_doctor_id'
			WHERE p.post_type=%s AND p.post_status IN ('publish','private')
			AND (p.post_author=%d OR patient.meta_value=%s OR doctor.meta_value=%s OR proposed.meta_value=%s)
			ORDER BY p.ID ASC LIMIT %d OFFSET %d";
		return array_map(
			'absint',
			(array) $wpdb->get_col(
				$wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), self::PAGE_SIZE, $offset )
			) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	private function related_count( $user_id ) {
		global $wpdb;
		$sql = "SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} patient ON patient.post_id=p.ID AND patient.meta_key='_swc_patient_user_id'
			LEFT JOIN {$wpdb->postmeta} doctor ON doctor.post_id=p.ID AND doctor.meta_key='_swc_doctor_id'
			LEFT JOIN {$wpdb->postmeta} proposed ON proposed.post_id=p.ID AND proposed.meta_key='_swc_proposed_doctor_id'
			WHERE p.post_type=%s AND p.post_status IN ('publish','private')
			AND (p.post_author=%d OR patient.meta_value=%s OR doctor.meta_value=%s OR proposed.meta_value=%s)";
		return absint(
			$wpdb->get_var(
				$wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ) )
			) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	public function policy() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content(
				__( 'Worldwide Clinic', 'worldwide-clinic-appointments' ),
				'<p class="privacy-policy-tutorial">' . esc_html__( 'The clinic module processes a selected doctor, appointment time, time zone, consultation method, location, phone and WhatsApp details, a brief consultation reason, consent records, status transitions, patient-visible messages, private doctor/administrator notes, reassignment proposals, notification delivery state, and structured audit history. Access is limited by ownership and dedicated capabilities. Private notes are never shown to patients. Privacy requests remove direct identifiers and sensitive scheduling content while retaining only a minimal anonymized integrity record when required.', 'worldwide-clinic-appointments' ) . '</p>'
			);
		}
	}
}
