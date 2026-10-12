<?php
/**
 * Versioned legacy compatibility and non-destructive migration adapters.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Compatibility {
	const MIGRATION_OPTION = 'wca_legacy_migration_1_0_0';

	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'maybe_migrate_legacy_statuses' ), 8 );
		add_filter( 'swc_statuses', array( __CLASS__, 'statuses' ) );
		add_filter( 'swc_status_transition_matrix', array( __CLASS__, 'transition_matrix' ) );
	}

	public static function statuses() {
		return WCA_Contracts::appointment_statuses();
	}

	public static function transition_matrix() {
		return WCA_Contracts::transition_matrix();
	}

	public static function maybe_migrate_legacy_statuses() {
		if ( get_option( self::MIGRATION_OPTION ) ) { return; }
		$result = self::migrate_legacy_statuses( 500 );
		if ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'legacy_status_migration_failed', array( 'code' => $result->get_error_code() ) ); }
	}

	public static function migrate_legacy_statuses( $limit = 500 ) {
		global $wpdb;
		$legacy = array_keys( WCA_Contracts::legacy_status_map() );
		$batch_limit = min( 5000, max( 1, absint( $limit ) ) );
		$wpdb->last_error = '';
		$ids = get_posts( array(
			'post_type'      => SWC_Helpers::TYPE,
			'post_status'    => array( 'private', 'publish', 'draft' ),
			'posts_per_page' => $batch_limit,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array( array( 'key' => '_swc_status', 'value' => $legacy, 'compare' => 'IN' ) ),
		) );
		if ( $wpdb->last_error ) { return new WP_Error( 'wca_legacy_status_query', __( 'Legacy appointment status migration could not read its source batch safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$migrated = 0;
		foreach ( $ids as $id ) {
			$id = absint( $id );
			$old = (string) get_post_meta( $id, '_swc_status', true );
			$new = WCA_Contracts::normalize_appointment_status( $old );
			$result = WCA_Repository::transaction( function () use ( $id, $old, $new ) {
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_status', $new, 'wca_legacy_status_write' );
				if ( is_wp_error( $written ) ) { return $written; }
				$written = SWC_Helpers::update_meta_strict( $id, '_swc_migrated_from_status', sanitize_key( $old ), 'wca_legacy_status_provenance_write' );
				if ( is_wp_error( $written ) ) { return $written; }
				if ( ! SWC_Helpers::audit( $id, 'legacy-status-migrated', array( 'old_status' => $old, 'new_status' => $new ) ) ) {
					return new WP_Error( 'wca_legacy_status_audit', __( 'Legacy appointment status migration could not persist audit evidence.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
				}
				return true;
			}, 'wca_legacy_status_transaction' );
			if ( is_wp_error( $result ) ) { wp_cache_delete( $id, 'post_meta' ); return $result; }
			$migrated++;
		}
		if ( count( $ids ) < $batch_limit ) {
			$wpdb->last_error = '';
			$remaining = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish','draft' ), 'posts_per_page' => 1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => '_swc_status', 'value' => $legacy, 'compare' => 'IN' ) ) ) );
			if ( $wpdb->last_error ) { return new WP_Error( 'wca_legacy_status_verify_query', __( 'Legacy appointment status migration could not verify completion safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			if ( ! $remaining ) {
				$written = SWC_Helpers::update_option_strict( self::MIGRATION_OPTION, array( 'completed_at' => WCA_Repository::now(), 'migrated' => $migrated ), 'wca_legacy_status_completion_write' );
				if ( is_wp_error( $written ) ) { return $written; }
			}
		}
		return $migrated;
	}

	/** @return array<string,mixed> */
	public static function dependency_health() {
		return array(
			'file00' => array( 'ready' => class_exists( 'SMC_Contracts' ) && method_exists( 'SMC_Contracts', 'assertions' ) && class_exists( 'SMC_CF01_Contract' ) && method_exists( 'SMC_CF01_Contract', 'ensure_subject_uuid' ), 'required' => true ),
			'file03' => array( 'ready' => class_exists( 'SPD_Helpers' ) && method_exists( 'SPD_Helpers', 'canonical_profile_url' ), 'required' => false ),
			'file07' => array( 'ready' => class_exists( 'SDD_Helpers' ) && method_exists( 'SDD_Helpers', 'public_clinic' ), 'required' => false ),
			'file09' => array( 'ready' => function_exists( 'gdo_user_is_verified' ), 'required' => true ),
			'file17' => array( 'ready' => class_exists( 'SN_Context_Adapters' ) && method_exists( 'SN_Context_Adapters', 'attach_context' ), 'required' => false ),
			'file19' => array( 'ready' => function_exists( 'sun_register_notification_producer' ) && function_exists( 'sun_ingest_domain_event' ), 'required' => false ),
			'file24' => array( 'ready' => function_exists( 'sabri_assurance_record_evidence' ), 'required' => false ),
			'cf01'   => array( 'ready' => function_exists( 'cf01_accept_scheduling_context' ), 'required' => false ),
			'cf02'   => array( 'ready' => has_filter( 'wca_cf02_dispatch_result' ), 'required' => false ),
			'cf03'   => array( 'ready' => has_filter( 'wca_cf03_dispatch_result' ), 'required' => false ),
		);
	}

	public static function public_contract_manifest() {
		return WCA_Contracts::contract_manifest();
	}
}
