<?php
/**
 * Dependency-free corrective regression checks.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

function __( $text ) { return $text; }
function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $key ) ); }

require dirname( __DIR__ ) . '/includes/class-swc-helpers.php';

$tests = 0;
function swc_assert( $condition, $message ) {
	global $tests;
	++$tests;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

swc_assert( 7 === count( SWC_Helpers::statuses() ), 'exactly seven appointment states' );
swc_assert( SWC_Helpers::can_transition( 'doctor', 'requested', 'accepted' ), 'doctor may accept a requested appointment' );
swc_assert( ! SWC_Helpers::can_transition( 'doctor', 'completed', 'accepted' ), 'completed appointment cannot be revived' );
swc_assert( ! SWC_Helpers::can_transition( 'admin', 'cancelled', 'accepted' ), 'administrator cannot revive a cancelled appointment' );
swc_assert( SWC_Helpers::can_transition( 'patient', 'reschedule-requested', 'accepted' ), 'patient may accept a reschedule proposal' );
swc_assert( ! SWC_Helpers::can_transition( 'patient', 'declined', 'cancelled' ), 'patient cannot rewrite a declined terminal record' );

$valid = SWC_Helpers::to_utc( '2026-08-03', '10:00', 'America/New_York' );
swc_assert( '2026-08-03 14:00:00' === $valid, 'valid local time converts exactly to UTC' );
swc_assert( '' === SWC_Helpers::to_utc( '2026-02-30', '10:00', 'America/New_York' ), 'invalid calendar date is rejected' );
swc_assert( '' === SWC_Helpers::to_utc( '2026-03-08', '02:30', 'America/New_York' ), 'nonexistent DST-gap time is rejected' );
swc_assert( '' === SWC_Helpers::to_utc( '2026-11-01', '01:30', 'America/New_York' ), 'ambiguous repeated-hour time is rejected' );
swc_assert( '' === SWC_Helpers::to_utc( '2026-08-03', '25:00', 'UTC' ), 'invalid clock time is rejected' );

$root = dirname( __DIR__ );
$frontend = file_get_contents( $root . '/includes/class-swc-frontend.php' );
$activator = file_get_contents( $root . '/includes/class-swc-activator.php' );
$plugin = file_get_contents( $root . '/includes/class-swc-plugin.php' );
$appointments = file_get_contents( $root . '/includes/class-swc-appointments.php' );
$css = file_get_contents( $root . '/assets/css/clinic.css' );
$main = file_get_contents( $root . '/worldwide-clinic.php' );

swc_assert( false === strpos( $frontend, 'SWC_Helpers::navigation' ), 'File 08 does not render File 20 global navigation' );
swc_assert( false === strpos( $activator, "_spd_verification_status" ), 'File 08 does not mutate File 03 verification status' );
swc_assert( false === strpos( $activator, "_spd_account_type" ), 'File 08 does not mutate File 03 account type' );
swc_assert( false === strpos( $activator, "add_role( 'sabri_doctor_verified'" ), 'File 08 does not create a verified-doctor role' );
swc_assert( false === strpos( $activator, "'capability_type'     => 'post'" ), 'appointments do not use ordinary post capabilities' );
swc_assert( false !== strpos( $frontend, "'doctor' === \$role && SWC_Helpers::meta( \$id, 'doctor_private_note' )" ), 'private doctor note has an explicit doctor-only rendering gate' );
swc_assert( false !== strpos( $appointments, "'patient_user_id'" ), 'appointment records preserve explicit patient ownership' );
swc_assert( false !== strpos( $plugin, 'DONOTCACHEPAGE' ) && false !== strpos( $plugin, 'litespeed_control_set_nocache' ), 'private cache exclusions include WordPress and LiteSpeed controls' );
swc_assert( false === strpos( $css, 'color:#fff!important' ), 'primary controls do not use failing white-on-orange text' );
swc_assert( false !== strpos( $main, 'Version: 0.2.0' ), 'corrective plugin version is 0.2.0' );

echo "All {$tests} corrective checks passed.\n";
