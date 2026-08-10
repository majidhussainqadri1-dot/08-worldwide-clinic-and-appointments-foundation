<?php
/**
 * Plugin Name: Worldwide Clinic and Appointments
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Canonical worldwide clinic identity, branches, services, fees, availability, slot holds, appointments, consent, lifecycle, notifications, calendar, complaints, review eligibility, privacy, observability, migration, secure clinical boundaries, pre-visit intake and follow-up continuity for the Sabri Social Homeopathy Platform.
 * Version: 1.0.1
 * Requires at least: 6.6
 * Requires PHP: 7.4
 * Author: Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * License: GPL-2.0-or-later
 * Text Domain: worldwide-clinic-appointments
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'WCA_VERSION', '1.0.1' );
define( 'WCA_FILE', __FILE__ );
define( 'WCA_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCA_URL', plugin_dir_url( __FILE__ ) );

// Stable compatibility aliases for the audited 0.2.x foundation.
define( 'SWC_VERSION', WCA_VERSION );
define( 'SWC_PUBLIC_CLINIC_CONTRACT_VERSION', '1.1.0' );
define( 'SWC_CF01_CARE_CONTEXT_VERSION', '1.1.0' );
define( 'SWC_FILE', WCA_FILE );
define( 'SWC_DIR', WCA_DIR );
define( 'SWC_URL', WCA_URL );

$wca_files = array(
	'includes/class-wca-contracts.php',
	'includes/class-wca-schema.php',
	'includes/class-wca-observability.php',
	'includes/class-swc-helpers.php',
	'includes/class-swc-doctor-authority.php',
	'includes/class-wca-authorization.php',
	'includes/class-wca-central-governance.php',
	'includes/class-wca-repository.php',
	'includes/class-wca-plan-guard.php',
	'includes/class-wca-service.php',
	'includes/class-wca-compatibility.php',
	'includes/class-wca-outbox.php',
	'includes/class-wca-continuity-secure.php',
	'includes/class-wca-continuity-guards.php',
	'includes/class-wca-privacy.php',
	'includes/class-wca-rest.php',
	'includes/class-wca-opaque-api.php',
	'includes/class-wca-routes.php',
	'includes/class-wca-frontend.php',
	'includes/class-wca-admin.php',
	'includes/class-wca-cli.php',
	'includes/class-wca-plugin.php',
	'includes/class-swc-public-clinic.php',
	'includes/class-swc-cf01-care-context.php',
	'includes/class-swc-activator.php',
	'includes/class-swc-appointments.php',
	'includes/class-swc-frontend.php',
	'includes/class-swc-admin.php',
	'includes/class-swc-privacy.php',
	'includes/class-swc-plugin.php',
);
foreach ( $wca_files as $wca_relative_file ) {
	require_once WCA_DIR . $wca_relative_file;
}
unset( $wca_files, $wca_relative_file );

register_activation_hook( WCA_FILE, array( 'SWC_Activator', 'activate' ) );
register_activation_hook( WCA_FILE, array( 'WCA_Continuity', 'activate' ) );
register_deactivation_hook( WCA_FILE, array( 'SWC_Activator', 'deactivate' ) );

function wca_start_plugin() {
	if ( ! SWC_Activator::dependencies_ready() ) {
		add_action( 'admin_notices', function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Worldwide Clinic:', 'worldwide-clinic-appointments' ) . '</strong> ' . esc_html( SWC_Activator::dependency_message() ) . '</p></div>';
			}
		} );
		return;
	}
	SWC_Activator::maybe_upgrade();
	WCA_Plugin::boot();
	WCA_Central_Governance::boot();
	WCA_Continuity::boot();
	WCA_Continuity_Guards::boot();
	WCA_Opaque_API::boot();
	( new SWC_Plugin() )->run();
}
add_action( 'plugins_loaded', 'wca_start_plugin', 30 );

/** Canonical public contract helpers for cross-file consumers. */
function wca_contract_manifest() { return WCA_Contracts::contract_manifest(); }
function wca_get_public_clinic_projection( $id_or_slug ) { return WCA_Service::public_clinic_projection( $id_or_slug ); }
function wca_get_cf01_scheduling_context( $appointment_id, $actor_user_id = 0 ) { return swc_get_cf01_care_context( $appointment_id, $actor_user_id ); }
function wca_get_central_governance_manifest() { return WCA_Central_Governance::manifest(); }
function wca_get_file26_clinic_projection( $clinic_ref ) { return WCA_Central_Governance::file26_clinic_projection( $clinic_ref ); }
