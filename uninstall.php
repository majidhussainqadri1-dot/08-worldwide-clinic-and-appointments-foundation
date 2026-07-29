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
	foreach ( array( 'manage_worldwide_clinic', 'read_swc_appointment', 'read_private_swc_appointments', 'edit_swc_appointments', 'edit_others_swc_appointments', 'publish_swc_appointments', 'delete_swc_appointments', 'delete_others_swc_appointments' ) as $capability ) {
		$administrator->remove_cap( $capability );
	}
}

// No appointment, audit, page, option, or user-availability data is deleted here.
