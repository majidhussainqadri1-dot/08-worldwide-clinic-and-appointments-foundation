<?php
require __DIR__ . '/bootstrap.php';
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function f24_has( $label, $text, $needle ) { global $failures,$checks; $checks++; if ( false === strpos( $text, $needle ) ) { $failures[] = $label . ' missing: ' . $needle; } }
function f24_lacks( $label, $text, $needle ) { global $failures,$checks; $checks++; if ( false !== strpos( $text, $needle ) ) { $failures[] = $label . ' forbidden: ' . $needle; } }
function f24_file( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } return (string) file_get_contents($file); }

$main = f24_file( 'worldwide-clinic.php' );
$contracts = f24_file( 'includes/class-wca-contracts.php' );
$future = f24_file( 'includes/class-wca-future24.php' );
$calendar = f24_file( 'includes/class-wca-calendar-link.php' );
$js = f24_file( 'assets/js/future24.js' );
$css = f24_file( 'assets/css/future24.css' );
$doc = f24_file( 'docs/FUTURE-CLINIC-INTELLIGENCE-24-2026.md' );

f24_has( 'runtime', $main, 'Version: 1.2.0' );
f24_has( 'runtime', $main, "define( 'WCA_VERSION', '1.2.0' )" );
f24_has( 'bootstrap', $main, 'class-wca-future24.php' );
f24_has( 'bootstrap', $main, "array( 'WCA_Future24', 'activate' )" );
f24_has( 'bootstrap', $main, 'WCA_Future24::boot()' );
f24_has( 'bootstrap', $main, 'class-wca-calendar-link.php' );
f24_has( 'bootstrap', $main, 'WCA_Calendar_Link::boot()' );
f24_has( 'contracts', $contracts, "const RUNTIME_VERSION                 = '1.2.0'" );
f24_has( 'contracts', $contracts, 'FUTURE24_CONTRACT_VERSION' );
f24_has( 'contracts', $contracts, 'future_requirements' );

for ( $i = 1; $i <= 24; $i++ ) {
	$id = sprintf( 'F08-FUT-%02d', $i );
	f24_has( 'future code ' . $id, $future, $id );
	f24_has( 'future document ' . $id, $doc, $id );
}

foreach ( array(
	'smart_waitlist','flexible_request_windows','recurring_series','multi_resource_scheduling','group_capacity','safe_reschedule','smart_buffers','capacity_heatmap','schedule_advisor','aggregate_no_show_forecast','dynamic_previsit_questionnaire','readiness_center','prerequisite_rules','family_guardian_hub','digital_checkin_queue','privacy_safe_queue_position','disruption_recovery','support_interpreter_participant','virtual_room_context','fhir_adapter','smart_scheduling_links','external_calendar_reconciliation','clinical_episode_chain','governance_layer'
) as $slug ) { f24_has( 'capability slug', $future, $slug ); }

foreach ( array(
	'wca_future24_records','SELECT GET_LOCK','SELECT RELEASE_LOCK','auto_book','aggregate_only','patient_scoring','WCA_Continuity','File17.VirtualRoomRequested.v1','File19.NotificationRequested.v1','HealthcareService','Appointment','smart-scheduling-links','provider_token_stored','automated_diagnosis','automated_prescribing','donor_or_paid_visibility'
) as $token ) { f24_has( 'future implementation', $future, $token ); }

f24_lacks( 'future operational storage', $future, "'diagnosis' =>" );
f24_lacks( 'future operational storage', $future, "'prescription' =>" );
f24_lacks( 'future governance', $future, "'auto_book' => true" );
f24_has( 'calendar signer', $calendar, '/calendar-links/(?P<ref>' );
f24_has( 'calendar signer', $calendar, 'wca.signed-calendar-link' );
f24_has( 'calendar privacy', $calendar, 'private, no-store' );
f24_has( 'calendar privacy', $calendar, 'Referrer-Policy' );
f24_has( 'calendar client', $js, 'calendar-links/' );
f24_has( 'calendar client', $js, 'aria-busy' );
f24_has( 'a11y', $css, 'prefers-reduced-motion' );
f24_has( 'a11y', $css, 'forced-colors' );
f24_has( 'rtl', $css, '[dir="rtl"]' );


f24_has( 'future24', $future, 'WCA_Authorization::claims( $actor_user_id )' );
f24_has( 'future24', $future, 'occurrence_dates_utc' );
f24_has( 'future24', $future, 'wca_series_origin' );
f24_has( 'future24', $future, 'subject_user_id( $subject )' );
f24_has( 'future24', $future, 'wca_fhir_time' );
f24_lacks( 'future24', $future, "gmdate( 'c', strtotime( SWC_Helpers::meta( $id, 'preferred_at_utc', '' )" );

if ( $failures ) { fwrite( STDERR, "Future24 tests failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit(1); }
echo "Future Clinic Intelligence 24 source assertions passed: {$checks}/{$checks}.\n";
