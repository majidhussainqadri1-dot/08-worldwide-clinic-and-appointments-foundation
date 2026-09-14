<?php
/**
 * File 08 retention-policy truth normalization.
 *
 * Appointment and appointment-event retention are governed by clinical,
 * jurisdictional and audit policy. File 08 must not publish fixed automatic
 * purge periods for those records unless an approved retention owner actually
 * enforces them. Operational stores keep explicit automated windows here.
 *
 * @package Worldwide_Clinic_Appointments
 */
defined( 'ABSPATH' ) || exit;

final class WCA_Retention_Policy {
	const CONTRACT_VERSION = '1.0.0';

	public static function boot() {
		/* Replace the older seed callback so unsupported fixed appointment/event
		 * day values are never published as active policy. */
		remove_action( 'admin_init', array( 'WCA_Privacy', 'register_policy' ) );
		add_action( 'admin_init', array( __CLASS__, 'normalize' ), 10 );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function normalize() {
		$current = (array) get_option( WCA_Privacy::RETENTION_OPTION, array() );
		$policy = wp_parse_args(
			$current,
			array(
				'outbox_delivered_days'       => 30,
				'idempotency_days'            => 7,
				'metrics_days'                => 395,
				'future24_operational_days'   => 395,
				'appointment_retention_mode'  => 'clinical_jurisdiction_policy',
				'event_retention_mode'        => 'audit_retention_policy',
				'automatic_appointment_purge' => false,
				'automatic_event_purge'       => false,
				'legal_hold_respected'        => true,
			)
		);

		/* These legacy keys were seeded but never enforced by maintenance. Keeping
		 * them would misstate runtime behavior and could be mistaken for automatic
		 * destructive retention. */
		unset( $policy['completed_appointments_days'], $policy['cancelled_appointments_days'], $policy['events_days'] );

		$policy['outbox_delivered_days'] = max( 1, absint( $policy['outbox_delivered_days'] ) );
		$policy['idempotency_days'] = max( 1, absint( $policy['idempotency_days'] ) );
		$policy['metrics_days'] = max( 1, absint( $policy['metrics_days'] ) );
		$policy['future24_operational_days'] = max( 1, absint( $policy['future24_operational_days'] ) );
		$policy['appointment_retention_mode'] = 'clinical_jurisdiction_policy';
		$policy['event_retention_mode'] = 'audit_retention_policy';
		$policy['automatic_appointment_purge'] = false;
		$policy['automatic_event_purge'] = false;
		$policy['legal_hold_respected'] = true;
		$policy['contract_version'] = self::CONTRACT_VERSION;

		if ( $policy !== $current ) {
			$written = SWC_Helpers::update_option_strict( WCA_Privacy::RETENTION_OPTION, $policy, 'wca_retention_policy_normalize' );
			if ( is_wp_error( $written ) ) {
				WCA_Observability::log( 'error', 'retention_policy_normalize_failed', array( 'code' => $written->get_error_code() ) );
				return $written;
			}
			WCA_Observability::log( 'info', 'retention_policy_normalized', array( 'contract_version' => self::CONTRACT_VERSION ) );
		}
		return $policy;
	}
}
