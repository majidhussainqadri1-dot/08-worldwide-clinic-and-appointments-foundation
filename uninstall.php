<?php
/**
 * Safe uninstall boundary.
 *
 * File 08 keeps appointments and audit history by default. Administrators may use
 * Clinic Management > System Check > Irreversible Data Purge only after a verified
 * backup and an explicit confirmation phrase.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$administrator = get_role( 'administrator' );
if ( $administrator ) {
	foreach ( array( 'manage_worldwide_clinic', 'manage_wca_clinics', 'manage_wca_complaints', 'manage_wca_operations', 'edit_swc_appointment', 'read_swc_appointment', 'delete_swc_appointment', 'edit_swc_appointments', 'edit_others_swc_appointments', 'publish_swc_appointments', 'read_private_swc_appointments', 'delete_swc_appointments', 'delete_private_swc_appointments', 'delete_published_swc_appointments', 'delete_others_swc_appointments', 'edit_private_swc_appointments', 'edit_published_swc_appointments' ) as $capability ) {
		$administrator->remove_cap( $capability );
	}
}

wp_clear_scheduled_hook( 'wca_process_outbox' );
wp_clear_scheduled_hook( 'wca_maintenance' );
wp_clear_scheduled_hook( 'wca_daily_health_snapshot' );

// No appointment, audit, page, option, or user-availability data is deleted here.
