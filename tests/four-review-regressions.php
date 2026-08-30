<?php
require __DIR__ . '/bootstrap.php';
$checks = 0;
function wca4_contains( $path, $needle, $label ) {
	global $checks;
	$text = file_get_contents( dirname( __DIR__ ) . '/' . $path );
	if ( false === strpos( $text, $needle ) ) { fwrite( STDERR, "FAIL {$label}\n" ); exit( 1 ); }
	$checks++;
}
function wca4_not_contains( $path, $needle, $label ) {
	global $checks;
	$text = file_get_contents( dirname( __DIR__ ) . '/' . $path );
	if ( false !== strpos( $text, $needle ) ) { fwrite( STDERR, "FAIL {$label}\n" ); exit( 1 ); }
	$checks++;
}
wca4_contains( 'worldwide-clinic.php', "define( 'WCA_VERSION', '1.2.15' )", 'runtime version' );
wca4_contains( 'includes/class-wca-service.php', 'submit_clinic_for_review', 'clinic review submission' );
wca4_contains( 'includes/class-wca-service.php', 'wca_clinic_reviewer_required', 'institutional activation gate' );
wca4_contains( 'includes/class-wca-authorization.php', 'wca_patient_actor_mismatch', 'patient actor binding' );
wca4_contains( 'includes/class-wca-authorization.php', 'wca_admin_purpose_required', 'purpose-limited admin access' );
wca4_contains( 'includes/class-wca-plan-guard.php', 'current server availability projection', 'server-authoritative slot' );
wca4_contains( 'includes/class-wca-plan-guard.php', 'practitioner_ref', 'opaque practitioner reference' );
wca4_contains( 'includes/class-wca-repository.php', 'SELECT GET_LOCK', 'atomic slot lock' );
wca4_contains( 'includes/class-wca-repository.php', 'expires_at', 'review expiry' );
wca4_contains( 'includes/class-wca-service.php', 'release_appointment_slot( $appointment_id, \'released\', $token )', 'compensation-safe reschedule' );
wca4_not_contains( 'includes/class-wca-service.php', "'id'             => absint(", 'no public clinic native ID' );
wca4_not_contains( 'includes/class-wca-frontend.php', 'name="doctor_user_id" type="number"', 'no public native doctor input' );
wca4_contains( 'assets/css/clinic.css', '--wca-green:#087A4E', 'Sabri Green identity' );
wca4_not_contains( 'assets/css/clinic.css', '--wca-green:#166534', 'superseded green removed' );
wca4_contains( 'assets/css/clinic.css', 'min-height:44px;min-width:44px', 'touch target' );
wca4_contains( 'worldwide-clinic.php', 'class-wca-calendar-link.php', 'calendar signer loaded' );
wca4_contains( 'worldwide-clinic.php', 'class-wca-future24.php', 'Future24 loaded' );
wca4_contains( 'includes/class-wca-future24.php', 'F08-FUT-24', 'Future24 governance capability' );
wca4_contains( 'includes/class-wca-future24.php', "'auto_book' => false", 'waitlist/series never auto-book' );
wca4_contains( 'includes/class-wca-future24.php', "'patient_scoring' => false", 'no hidden patient scoring' );
echo "Four-review regression contracts passed: {$checks}/{$checks}.\n";
