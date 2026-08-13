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
		if ( is_wp_error( $ids ) ) { return $ids; }
		$total = $this->related_count( $user->ID );
		if ( is_wp_error( $total ) ) { return $total; }
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
		if ( is_wp_error( $ids ) ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array( __( 'Appointment privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ) ), 'done' => false ); }
		$removed  = false;
		$retained = false;
		$messages = array();
		$failed   = false;
		foreach ( $ids as $appointment_id ) {
			$result = WCA_Repository::transaction( function () use ( $appointment_id, $user, $wpdb ) {
				$is_patient = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ) === $user->ID;
				$is_doctor  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) === $user->ID;
				$changed = false;
				$retain = false;
				if ( $is_patient ) {
					foreach ( array( 'country', 'city', 'phone', 'whatsapp', 'reason', 'concern_duration', 'patient_message', 'consent_at', 'consent_version', 'patient_timezone', 'preferred_at_utc', 'proposed_at_utc', 'proposed_timezone', 'proposed_expires_at', 'reassignment_reason', 'reassignment_expires_at' ) as $key ) {
						$deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'swc_privacy_meta_delete' );
						if ( is_wp_error( $deleted ) ) { return $deleted; }
					}
					$patient_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_patient_user_id', 0, 'swc_privacy_patient_anonymize' );
					if ( is_wp_error( $patient_write ) ) { return $patient_write; }
					$post_update = wp_update_post( array( 'ID' => $appointment_id, 'post_author' => 0, 'post_title' => sprintf( 'Anonymized Appointment #%d', $appointment_id ) ), true );
					if ( is_wp_error( $post_update ) || ! $post_update || 0 !== absint( get_post_field( 'post_author', $appointment_id ) ) ) { return new WP_Error( 'swc_privacy_post_anonymize', __( 'The appointment post could not be anonymized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
					if ( SWC_Helpers::can_transition( 'patient', SWC_Helpers::status( $appointment_id ), 'cancelled' ) ) {
						$status_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_status', 'cancelled', 'swc_privacy_status_anonymize' );
						if ( is_wp_error( $status_write ) ) { return $status_write; }
					}
					$erased_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_erased', '1', 'swc_privacy_erased_marker' );
					if ( is_wp_error( $erased_write ) ) { return $erased_write; }
					$changed = true; $retain = true;
				}
				if ( $is_doctor ) {
					$doctor_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_doctor_id', 0, 'swc_privacy_doctor_anonymize' );
					if ( is_wp_error( $doctor_write ) ) { return $doctor_write; }
					foreach ( array( 'doctor_private_note', 'patient_message' ) as $key ) { $deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'swc_privacy_private_meta_delete' ); if ( is_wp_error( $deleted ) ) { return $deleted; } }
					$changed = true; $retain = true;
				}
				if ( absint( SWC_Helpers::meta( $appointment_id, 'proposed_doctor_id' ) ) === $user->ID ) {
					foreach ( array( 'proposed_doctor_id', 'reassignment_reason', 'reassignment_expires_at' ) as $key ) { $deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'swc_privacy_reassignment_delete' ); if ( is_wp_error( $deleted ) ) { return $deleted; } }
					$changed = true;
				}
				$audit_update = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}swc_audit_log SET actor_id=0, note='', reason='', details_json='{}' WHERE appointment_id=%d AND actor_id=%d", $appointment_id, $user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				if ( false === $audit_update ) { return new WP_Error( 'swc_privacy_audit_anonymize', __( 'Appointment audit identity could not be anonymized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
				return array( 'changed' => $changed, 'retained' => $retain );
			}, 'swc_privacy_erase_transaction' );
			if ( is_wp_error( $result ) ) {
				clean_post_cache( $appointment_id );
				$messages[] = __( 'Legacy appointment privacy erasure encountered a storage failure and will retry.', 'worldwide-clinic-appointments' );
				$failed = true;
				break;
			}
			$removed = $removed || ! empty( $result['changed'] );
			$retained = $retained || ! empty( $result['retained'] );
		}
		$remaining = $this->related_count( $user->ID );
		if ( is_wp_error( $remaining ) ) { $messages[] = __( 'Appointment privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $failed = true; $remaining = 1; }
		$done = ! $failed && 0 === $remaining;
		if ( $retained ) { $messages[] = __( 'A minimal anonymized appointment and status record was retained for integrity, security, and accountability. Direct identifiers, contact details, private notes, consent details, scheduling times, and user-linked audit content were removed.', 'worldwide-clinic-appointments' ); }
		return array(
			'items_removed'  => $removed,
			'items_retained' => $retained,
			'messages'       => array_unique( $messages ),
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
		$raw = $wpdb->get_col( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), self::PAGE_SIZE, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'swc_privacy_related_ids_read_failed', __( 'Appointment privacy records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		return array_map( 'absint', (array) $raw );
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
		$raw = $wpdb->get_var( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'swc_privacy_related_count_read_failed', __( 'Appointment privacy record count could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		return absint( $raw );
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
