<?php
/**
 * Hardening assertions added after the fresh 2026 central/File 08 plan audit.
 * These tests are source gates; staging/browser/restore evidence remains a
 * separate acceptance layer.
 */

$root = dirname( __DIR__ );
$failures = array();
$checks = 0;

function f08h_source( $path ) {
	global $root, $failures;
	$file = $root . '/' . $path;
	if ( ! is_file( $file ) ) { $failures[] = 'Missing ' . $path; return ''; }
	$data = file_get_contents( $file );
	if ( ! is_string( $data ) ) { $failures[] = 'Unreadable ' . $path; return ''; }
	return $data;
}
function f08h_has( $label, $source, $needle ) {
	global $failures, $checks;
	$checks++;
	if ( false === strpos( $source, $needle ) ) { $failures[] = $label . ' missing: ' . $needle; }
}
function f08h_lacks( $label, $source, $needle ) {
	global $failures, $checks;
	$checks++;
	if ( false !== strpos( $source, $needle ) ) { $failures[] = $label . ' forbidden: ' . $needle; }
}

$bootstrap = f08h_source( 'worldwide-clinic.php' );
$guards = f08h_source( 'includes/class-wca-continuity-guards.php' );
$opaque = f08h_source( 'includes/class-wca-opaque-api.php' );
$command = f08h_source( 'includes/class-wca-appointment-command.php' );
$reconcile = f08h_source( 'includes/class-wca-verification-reconciliation.php' );
$frontend = f08h_source( 'includes/class-wca-frontend.php' );
$clinic_js = f08h_source( 'assets/js/clinic.js' );
$continuity_js = f08h_source( 'assets/js/continuity.js' );

foreach ( array(
	'class-wca-continuity-guards.php', 'class-wca-verification-reconciliation.php',
	'class-wca-opaque-api.php', 'class-wca-appointment-command.php',
	'WCA_Continuity_Guards::boot()', 'WCA_Verification_Reconciliation::boot()',
	'WCA_Opaque_API::boot()', 'WCA_Appointment_Command::boot()',
) as $token ) { f08h_has( 'bootstrap', $bootstrap, $token ); }

foreach ( array(
	'wca_intake_version_required', 'expected_version', 'rest_pre_dispatch',
	'wp_privacy_personal_data_erasers', 'ERASE_BATCH', 'CURSOR_TTL',
	'wca_continuity_legal_hold', 'can_manage_consents', 'can_edit_intake',
	'appointmentRef', '/consents',
) as $token ) { f08h_has( 'continuity guards', $guards, $token ); }

foreach ( array(
	'wca_legacy_numeric_route_disabled', 'wca_allow_legacy_numeric_rest_routes',
	'/appointment-refs/', '/clinic-refs/', 'strip_native_ids',
	'appointment_id', 'doctor_user_id', 'patient_user_id', 'owner_user_id',
	'wca.opaque-object-refs',
) as $token ) { f08h_has( 'opaque API', $opaque, $token ); }

foreach ( array(
	'wca_privacy_consent_required', 'wca_emergency_ack_required',
	'wca_teleconsult_consent_required', 'privacy_consent_verified',
	'emergency_ack_verified', 'remote_consultation_consent_verified',
	"'teleconsult'", "'privacy_notice'", 'register_rest_route', "'/appointments'",
	'WCA_Service::request_appointment', 'ensure_context_consent',
) as $token ) { f08h_has( 'appointment command', $command, $token ); }

foreach ( array(
	'ClinicEligibilityChanged.v1', 'File26.SearchProjectionChanged.v1',
	'wca_doctor_suspended', 'wca_doctor_revoked', 'wca_doctor_verified',
	'source_owner', "'File09'",
) as $token ) { f08h_has( 'verification reconciliation', $reconcile, $token ); }

foreach ( array(
	'data-wca-appointment-ref', '/appointment-refs/', 'telehealth_consent',
	'data-consultation-type', 'wca_page', 'View details',
) as $token ) { f08h_has( 'frontend', $frontend, $token ); }
f08h_lacks( 'frontend', $frontend, 'data-wca-appointment-id' );
f08h_lacks( 'frontend', $frontend, 'wca/v1/appointments/\' . $id' );

foreach ( array(
	'wcaAppointmentRef', 'appointment-refs/', 'telehealth_consent',
	'privacy_consent', 'emergency_acknowledged', 'selectedServiceType',
) as $token ) { f08h_has( 'clinic client', $clinic_js, $token ); }
f08h_lacks( 'clinic client', $clinic_js, 'wcaAppointmentId' );

foreach ( array(
	'expected_version', 'loadIntake', 'wireConsents', 'can_manage_consents',
	'can_create_followup', 'data-wca-auto-continuity', 'recording',
	'Recording is never assumed',
) as $token ) { f08h_has( 'continuity client', $continuity_js, $token ); }

if ( $failures ) {
	fwrite( STDERR, "File 08 new-plan hardening failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}
echo "File 08 new-plan hardening assertions passed: {$checks}/{$checks}.\n";
