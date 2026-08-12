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

f24_has( 'runtime', $main, 'Version: 1.2.14' );
f24_has( 'runtime', $main, "define( 'WCA_VERSION', '1.2.14' )" );
f24_has( 'bootstrap', $main, 'class-wca-future24.php' );
f24_has( 'bootstrap', $main, "array( 'WCA_Future24', 'activate' )" );
f24_has( 'bootstrap', $main, 'WCA_Future24::boot()' );
f24_has( 'bootstrap', $main, 'class-wca-calendar-link.php' );
f24_has( 'bootstrap', $main, 'WCA_Calendar_Link::boot()' );
f24_has( 'contracts', $contracts, "const RUNTIME_VERSION                 = '1.2.14'" );
f24_has( 'contracts', $contracts, 'FUTURE24_CONTRACT_VERSION' );
f24_has( 'contracts', $contracts, 'future_requirements' );

for ( $i = 1; $i <= 24; $i++ ) {
	$id = sprintf( 'F08-FUT-%02d', $i );
	f24_has( 'future code ' . $id, $future, $id );
	f24_has( 'future document ' . $id, $doc, $id );
}
foreach ( array('smart_waitlist','flexible_request_windows','recurring_series','multi_resource_scheduling','group_capacity','safe_reschedule','smart_buffers','capacity_heatmap','schedule_advisor','aggregate_no_show_forecast','dynamic_previsit_questionnaire','readiness_center','prerequisite_rules','family_guardian_hub','digital_checkin_queue','privacy_safe_queue_position','disruption_recovery','support_interpreter_participant','virtual_room_context','fhir_adapter','smart_scheduling_links','external_calendar_reconciliation','clinical_episode_chain','governance_layer') as $slug ) { f24_has( 'capability slug', $future, $slug ); }
foreach ( array('wca_future24_records','SELECT GET_LOCK','SELECT RELEASE_LOCK','auto_book','aggregate_only','patient_scoring','WCA_Continuity','File17.VirtualRoomRequested.v1','File19.NotificationRequested.v1','HealthcareService','Appointment','smart-scheduling-links','provider_token_stored','automated_diagnosis','automated_prescribing','donor_or_paid_visibility') as $token ) { f24_has( 'future implementation', $future, $token ); }
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
foreach ( array('wca_windows_clinic',"'expires_at' => gmdate",'wca_resource_appointment_scope','cross_clinic_scope_checked','wca_group_window','apply_slot_policies_to_rest','enforcement','server_slot_projection','configured_capacity','free_estimate','guardian_recheck_runtime','remote_context_ready','count_only_evidence_is_not_sufficient','wca_arrival_state','wca_arrival_window',"'arrived'",'wca_disruption_window','affected_count','rest_participant_revoke','participant_revoked','wca_virtual_room_state','wca_virtual_room_consent','teleconsult_consent_verified','wca_external_busy_window','wca_episode_scope','same_patient_doctor_clinic','clinic_appointments_between') as $token ) { f24_has( 'fresh review hardening', $future, $token ); }
f24_has( 'queue expiry', $future, "status='arrived' AND (expires_at IS NULL OR expires_at>%s)" );
f24_has( 'maintenance expiry', $future, "'arrived','participant_active','room_requested','busy','disruption_active'" );
f24_has( 'resource auth', $future, 'self::require_appointment( $appointment_ref, $actor )' );
f24_has( 'virtual consent', $future, "consent['scopes']['teleconsult']" );
f24_has( 'prerequisite matching', $future, 'if(isset($evidence[$r])){continue;}' );
f24_has( 'prerequisite blocking/provisional split', $future, 'if(\'block\'===$behavior){$missing_blocking[]=$r;}else{$missing_provisional[]=$r;}' );
f24_lacks( 'readiness', $future, "'guardian_recheck_runtime' => true" );
f24_lacks( 'queue stale-count regression', $future, 'status=\'arrived\' AND created_at<%s", $clinic_id, $current[\'created_at\'] )' );
foreach ( array('enforce_slot_hold_policy_pre_dispatch','wca_future24_slot_policy','observe_outbox_event','AppointmentCancelled.v1','offer_waitlist_for_cancelled_appointment','offer_pending','confirmation_required','waitlist_offer_available','questionnaire_for_appointment','wca.dynamic-previsit-questionnaire','performed_for_each_returned_appointment','File17.AppointmentParticipantChanged.v1') as $token ) { f24_has( 'second review hardening', $future, $token ); }
f24_has( 'smart hold policy', $future, 'self::guard_slot_hold_data( $data )' );
f24_has( 'waitlist idempotency', $future, "status='offer_pending' AND starts_at=%s AND ends_at=%s" );
f24_has( 'family current guardian', $future, 'WCA_Central_Governance::validate_patient_guardian( $patient_id, $actor, $actor )' );
if ( $failures ) { fwrite( STDERR, "Future24 tests failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit(1); }
echo "Future Clinic Intelligence 24 source assertions passed: {$checks}/{$checks}.\n";
