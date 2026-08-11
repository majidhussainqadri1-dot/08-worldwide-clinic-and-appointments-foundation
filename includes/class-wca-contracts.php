<?php
/**
 * Canonical File 08 contracts, state laws, requirement catalogue, and schemas.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Contracts {
	const PLAN_ID                         = 'SSH-F08-PLAN-2026-v1.0';
	const RUNTIME_VERSION                 = '1.2.8';
	const API_VERSION                     = '1.0.0';
	const PUBLIC_CLINIC_CONTRACT_VERSION  = '1.1.0';
	const CF01_CONTEXT_CONTRACT_VERSION   = '1.1.0';
	const FILE17_CONTEXT_CONTRACT_VERSION = '1.0.0';
	const FILE19_EVENT_CONTRACT_VERSION   = '1.0.0';
	const ASSURANCE_CONTRACT_VERSION      = '1.0.0';
	const FUTURE24_CONTRACT_VERSION       = '1.0.0';
	const SCHEMA_VERSION                  = '3.2.0';

	/** @return array<string,string> */
	public static function appointment_statuses() {
		return array(
			'requested'          => 'Requested',
			'confirmed'          => 'Confirmed',
			'declined'           => 'Declined',
			'reschedule_pending' => 'Reschedule Pending',
			'checked_in'         => 'Checked In',
			'completed'          => 'Completed',
			'cancelled'          => 'Cancelled',
			'no_show'            => 'No Show',
		);
	}

	/** @return array<string,string> */
	public static function legacy_status_map() {
		return array(
			'under-review'         => 'requested',
			'under_review'         => 'requested',
			'accepted'             => 'confirmed',
			'reschedule-requested' => 'reschedule_pending',
			'reschedule_requested' => 'reschedule_pending',
			'checked-in'           => 'checked_in',
			'no-show'              => 'no_show',
		);
	}

	public static function is_appointment_status( $status, $allow_legacy = false ) {
		$status = strtolower( trim( (string) $status ) );
		if ( isset( self::appointment_statuses()[ $status ] ) ) { return true; }
		return $allow_legacy && isset( self::legacy_status_map()[ $status ] );
	}

	public static function normalize_appointment_status( $status ) {
		$status = strtolower( trim( (string) $status ) );
		$map = self::legacy_status_map();
		if ( isset( $map[ $status ] ) ) { $status = $map[ $status ]; }
		return isset( self::appointment_statuses()[ $status ] ) ? $status : 'requested';
	}

	/** @return array<string,array<string,array<int,string>>> */
	public static function transition_matrix() {
		return array(
			'patient' => array(
				'requested' => array( 'cancelled' ),
				'confirmed' => array( 'cancelled', 'reschedule_pending' ),
				'reschedule_pending' => array( 'confirmed', 'cancelled' ),
			),
			'guardian' => array(
				'requested' => array( 'cancelled' ),
				'confirmed' => array( 'cancelled', 'reschedule_pending' ),
				'reschedule_pending' => array( 'confirmed', 'cancelled' ),
			),
			'doctor' => array(
				'requested' => array( 'confirmed', 'declined', 'reschedule_pending' ),
				'confirmed' => array( 'reschedule_pending', 'checked_in', 'cancelled', 'no_show' ),
				'reschedule_pending' => array( 'confirmed', 'declined', 'cancelled' ),
				'checked_in' => array( 'completed', 'no_show', 'cancelled' ),
			),
			'clinic_staff' => array(
				'requested' => array( 'confirmed', 'declined', 'reschedule_pending' ),
				'confirmed' => array( 'reschedule_pending', 'checked_in', 'cancelled', 'no_show' ),
				'reschedule_pending' => array( 'confirmed', 'declined', 'cancelled' ),
				'checked_in' => array( 'completed', 'no_show', 'cancelled' ),
			),
			'admin' => array(
				'requested' => array( 'confirmed', 'declined', 'reschedule_pending', 'cancelled' ),
				'confirmed' => array( 'reschedule_pending', 'checked_in', 'cancelled', 'no_show' ),
				'reschedule_pending' => array( 'confirmed', 'declined', 'cancelled' ),
				'checked_in' => array( 'completed', 'cancelled', 'no_show' ),
			),
			'clinical_governance' => array(
				'requested' => array( 'cancelled' ),
				'confirmed' => array( 'cancelled' ),
				'reschedule_pending' => array( 'cancelled' ),
				'checked_in' => array( 'cancelled' ),
			),
		);
	}

	/** @return array<int,string> */
	public static function allowed_transitions( $actor, $from ) {
		$actor = sanitize_key( (string) $actor );
		$from = self::normalize_appointment_status( $from );
		$matrix = self::transition_matrix();
		return isset( $matrix[ $actor ][ $from ] ) ? $matrix[ $actor ][ $from ] : array();
	}

	public static function can_transition( $actor, $from, $to ) {
		return in_array( self::normalize_appointment_status( $to ), self::allowed_transitions( $actor, $from ), true );
	}

	public static function is_terminal( $status ) {
		return in_array( self::normalize_appointment_status( $status ), array( 'declined', 'completed', 'cancelled', 'no_show' ), true );
	}

	/** @return array<string,array<int,string>> */
	public static function lifecycles() {
		return array(
			'clinic' => array( 'draft', 'review', 'active', 'paused', 'suspended', 'archived' ),
			'appointment' => array_keys( self::appointment_statuses() ),
			'slot' => array( 'available', 'held', 'booked', 'released', 'expired' ),
			'review_eligibility' => array( 'not_eligible', 'eligible', 'used', 'revoked' ),
			'complaint' => array( 'submitted', 'triaged', 'under_review', 'awaiting_evidence', 'resolved', 'dismissed', 'appealed', 'closed' ),
			'outbox' => array( 'pending', 'processing', 'delivered', 'retry', 'dead_letter', 'cancelled' ),
		);
	}

	/** @return array<string,array<string,mixed>> */
	public static function event_schemas() {
		return array(
			'ClinicActivated.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'clinic_ref', 'owner_subject_uuid', 'trace_id' ), 'class' => 'restricted' ),
			'ClinicBranchChanged.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'clinic_ref', 'branch_ref', 'change', 'trace_id' ), 'class' => 'restricted' ),
			'ClinicAvailabilityChanged.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'clinic_ref', 'doctor_subject_uuid', 'version', 'trace_id' ), 'class' => 'restricted' ),
			'AppointmentRequested.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'appointment_ref', 'patient_subject_uuid', 'doctor_subject_uuid', 'trace_id' ), 'class' => 'sensitive' ),
			'AppointmentConfirmed.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'appointment_ref', 'scheduled_at_utc', 'trace_id' ), 'class' => 'sensitive' ),
			'AppointmentCompleted.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'appointment_ref', 'completed_at_utc', 'trace_id' ), 'class' => 'sensitive' ),
			'ReviewEligibilityGranted.v1' => array( 'required' => array( 'event_id', 'occurred_at', 'eligibility_ref', 'appointment_ref', 'reviewer_subject_uuid', 'doctor_subject_uuid', 'trace_id' ), 'class' => 'restricted' ),
		);
	}

	/** @return array<int,string> */
	public static function published_events() { return array_keys( self::event_schemas() ); }

	/** @return array<int,string> */
	public static function consumed_events() { return array( 'DoctorVerified.v1', 'DoctorSuspended.v1', 'PaymentStatusChanged.v1', 'MessageReported.v1' ); }

	/** @return array<string,array<string,string>> */
	public static function routes() {
		return array(
			'clinic_detail' => array( 'pattern' => '/clinic/{clinic_slug}', 'access' => 'public', 'cache' => 'public', 'index' => 'index' ),
			'booking' => array( 'pattern' => '/appointments/book/{doctor_or_clinic}', 'access' => 'authenticated', 'cache' => 'no-store', 'index' => 'noindex' ),
			'appointments' => array( 'pattern' => '/appointments', 'access' => 'patient', 'cache' => 'no-store', 'index' => 'noindex' ),
			'dashboard' => array( 'pattern' => '/clinic/dashboard', 'access' => 'doctor_or_staff', 'cache' => 'no-store', 'index' => 'noindex' ),
			'appointment' => array( 'pattern' => '/appointment/{public_ref}', 'access' => 'participant', 'cache' => 'no-store', 'index' => 'noindex' ),
		);
	}

	/** @return array<int,string> */
	public static function public_clinic_fields() {
		return array( 'public_ref', 'slug', 'name', 'summary', 'languages', 'contacts', 'policies', 'status', 'branches', 'services', 'availability', 'verified_owner', 'updated_at', 'version' );
	}

	/** @return array<int,string> */
	public static function prohibited_public_fields() {
		return array( 'patient_user_id', 'patient_subject_uuid', 'appointment_id', 'appointment_ref', 'reason', 'clinical_note', 'private_note', 'phone_private', 'whatsapp_private', 'email_private', 'consent_evidence', 'guardian_evidence', 'payment_provider_token', 'calendar_provider_token', 'clinical_context_ref', 'audit_narrative', 'native_user_id' );
	}

	/** @return array<string,array<string,string>> */
	public static function privacy_classes() {
		return array(
			'clinic' => array( 'class' => 'public/restricted', 'retention' => 'active plus versioned history' ),
			'clinic_service' => array( 'class' => 'public', 'retention' => 'versioned' ),
			'availability_rule' => array( 'class' => 'restricted/public slots', 'retention' => 'operational plus history' ),
			'appointment' => array( 'class' => 'sensitive', 'retention' => 'jurisdiction and clinical policy' ),
			'appointment_consent' => array( 'class' => 'sensitive', 'retention' => 'with appointment or record policy' ),
			'appointment_event' => array( 'class' => 'sensitive audit', 'retention' => 'integrity policy' ),
			'review_eligibility' => array( 'class' => 'restricted', 'retention' => 'integrity policy' ),
			'clinical_context_ref' => array( 'class' => 'health-sensitive', 'retention' => 'policy and jurisdiction' ),
			'future24_operational' => array( 'class' => 'restricted operational', 'retention' => 'purpose-limited and expiring where applicable' ),
		);
	}

	/** @return array<string,array<string,string>> */
	public static function functional_requirements() {
		return array(
			'F08-FR-001' => array( 'capability' => 'Clinic identity', 'priority' => 'Must' ),
			'F08-FR-002' => array( 'capability' => 'Services and fees', 'priority' => 'Must' ),
			'F08-FR-003' => array( 'capability' => 'Availability rules', 'priority' => 'Must' ),
			'F08-FR-004' => array( 'capability' => 'Slot search', 'priority' => 'Must' ),
			'F08-FR-005' => array( 'capability' => 'Appointment request', 'priority' => 'Must' ),
			'F08-FR-006' => array( 'capability' => 'Doctor decision', 'priority' => 'Must' ),
			'F08-FR-007' => array( 'capability' => 'Reschedule', 'priority' => 'Must' ),
			'F08-FR-008' => array( 'capability' => 'Cancellation/no-show', 'priority' => 'Must' ),
			'F08-FR-009' => array( 'capability' => 'Check-in/completion', 'priority' => 'Must' ),
			'F08-FR-010' => array( 'capability' => 'Dashboards', 'priority' => 'Must' ),
			'F08-FR-011' => array( 'capability' => 'Emergency safety', 'priority' => 'Must' ),
			'F08-FR-012' => array( 'capability' => 'Consent', 'priority' => 'Must' ),
			'F08-FR-013' => array( 'capability' => 'Clinical relationship', 'priority' => 'Must' ),
			'F08-FR-014' => array( 'capability' => 'Review eligibility', 'priority' => 'Must' ),
			'F08-FR-015' => array( 'capability' => 'Calendar integration', 'priority' => 'Must' ),
			'F08-FR-016' => array( 'capability' => 'Fees/payment bridge', 'priority' => 'Must' ),
			'F08-FR-017' => array( 'capability' => 'Clinical record boundary', 'priority' => 'Must' ),
			'F08-FR-018' => array( 'capability' => 'Complaint/dispute', 'priority' => 'Must' ),
		);
	}

	/** @return array<string,array<string,string>> */
	public static function nonfunctional_requirements() {
		return array(
			'F08-NFR-001' => array( 'area' => 'Object/field authorization', 'gate' => 'Release gate' ),
			'F08-NFR-002' => array( 'area' => 'Privacy lifecycle', 'gate' => 'Release gate' ),
			'F08-NFR-003' => array( 'area' => 'Reliability', 'gate' => 'Release gate' ),
			'F08-NFR-004' => array( 'area' => 'Performance', 'gate' => 'Release gate' ),
			'F08-NFR-005' => array( 'area' => 'Accessibility', 'gate' => 'Release gate' ),
			'F08-NFR-006' => array( 'area' => 'Observability', 'gate' => 'Release gate' ),
			'F08-NFR-007' => array( 'area' => 'Migration/rollback', 'gate' => 'Release gate' ),
			'F08-NFR-008' => array( 'area' => 'Operability', 'gate' => 'Release gate' ),
			'F08-NFR-009' => array( 'area' => 'Compatibility', 'gate' => 'Release gate' ),
			'F08-NFR-010' => array( 'area' => 'Localization', 'gate' => 'Release gate' ),
		);
	}

	/** @return array<string,array<string,string>> */
	public static function future_requirements() {
		$out = array();
		if ( class_exists( 'WCA_Future24' ) ) {
			foreach ( WCA_Future24::capabilities() as $id => $row ) { $out[ $id ] = array( 'capability' => $row['title'], 'priority' => $row['priority'] ); }
			return $out;
		}
		$names = array(
			'Smart Cancellation Waitlist','Flexible Appointment Request Windows','Recurring / Series Appointments','Multi-Resource Scheduling','Capacity-Based / Group Appointment Mode','One-Tap Safe Reschedule','Smart Buffer & Transition Rules','Availability Capacity Heatmap','Schedule Optimization Advisor','Privacy-Safe No-Show Forecasting','Structured Dynamic Pre-Visit Questionnaire','Appointment Readiness Center','Prerequisite & Document Rules','Family / Guardian Appointment Hub','Digital Check-In & Arrival Queue','Privacy-Preserving Live Queue Position','Doctor Delay / Clinic Disruption State','Consultation Support Person / Interpreter Role','Secure Virtual-Room Provisioning Contract','FHIR Interoperability Adapter','SMART Scheduling Links Compatibility','External Calendar Two-Way Reconciliation','Clinical Episode / Follow-Up Chain','Appointment Intelligence & Interoperability Governance Layer'
		);
		foreach ( $names as $i => $name ) { $out[ sprintf( 'F08-FUT-%02d', $i + 1 ) ] = array( 'capability' => $name, 'priority' => in_array( $i + 1, array(1,2,3,4,6,7,11,12,14,17,19,24), true ) ? 'P0' : ( 10 === $i + 1 ? 'P2' : 'P1' ) ); }
		return $out;
	}

	/** @return array<string,array<string,mixed>> */
	public static function contract_manifest() {
		return array(
			'plan_id' => self::PLAN_ID,
			'runtime_version' => self::RUNTIME_VERSION,
			'api_version' => self::API_VERSION,
			'schema_version' => self::SCHEMA_VERSION,
			'public_clinic_contract' => self::PUBLIC_CLINIC_CONTRACT_VERSION,
			'cf01_context_contract' => self::CF01_CONTEXT_CONTRACT_VERSION,
			'file17_context_contract' => self::FILE17_CONTEXT_CONTRACT_VERSION,
			'file19_event_contract' => self::FILE19_EVENT_CONTRACT_VERSION,
			'assurance_contract' => self::ASSURANCE_CONTRACT_VERSION,
			'future24_contract' => self::FUTURE24_CONTRACT_VERSION,
			'functional_requirements' => array_keys( self::functional_requirements() ),
			'nonfunctional_requirements' => array_keys( self::nonfunctional_requirements() ),
			'future_requirements' => array_keys( self::future_requirements() ),
			'published_events' => self::published_events(),
			'consumed_events' => self::consumed_events(),
			'routes' => self::routes(),
			'commission_percent' => 0,
			'donation_visibility_link' => false,
			'automated_diagnosis' => false,
			'automated_prescribing' => false,
		);
	}
}
