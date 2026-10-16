<?php
/**
 * T38: 40 plan-derived cross-owner code-path and corrective-guard screens.
 * Sources: user's attached amended File08 / Three Central Plans / File07,
 * File09, File14, File25; unit/cross-repo tests are separate acceptance gates.
 *
 * Each of 40 rounds opens the exact current candidate source, checks its own
 * plan-defined code path and records the result. This is **source traceability**,
 * not 40 independent semantic, integration, MySQL, browser or live proofs.
 */
require __DIR__ . '/bootstrap.php';
$base = dirname( __DIR__ );
$checks = array(
    array( "FR001", "includes/class-wca-service.php", array( "function create_clinic(", "function create_branch(" ) ),
    array( "FR002", "includes/class-wca-service.php", array( "function save_service(", "function valid_currency(" ) ),
    array( "FR003", "includes/class-wca-service.php", array( "function generate_rule_slots(", "function set_availability(" ) ),
    array( "FR004", "includes/class-wca-service.php", array( "function search_slots(", "function hold_slot(" ) ),
    array( "FR005", "includes/class-wca-service.php", array( "function request_appointment(", "appointment_request_fingerprint(" ) ),
    array( "FR006", "includes/class-wca-service.php", array( "function transition_appointment(", "function event_for_transition(" ) ),
    array( "FR007", "includes/class-wca-service.php", array( "function hold_reschedule_slot(", "reschedule" ) ),
    array( "FR008", "includes/class-wca-repository.php", array( "function release_appointment_slot(", "function release_slot_hold(" ) ),
    array( "FR009", "includes/class-wca-repository.php", array( "function grant_review_eligibility(", "function consume_review_eligibility(" ) ),
    array( "FR010", "includes/class-wca-query-api.php", array( "function list_patient_appointments(", "function list_clinic_schedule(" ) ),
    array( "FR011", "includes/class-wca-service.php", array( "function emergency_red_flag(", "emergency" ) ),
    array( "FR012", "includes/class-wca-continuity-secure.php", array( "function grant_context_consent(", "function revoke_context_consent(" ) ),
    array( "FR013", "includes/class-wca-service.php", array( "function file17_context_payload(", "File17" ) ),
    array( "FR014", "includes/class-wca-repository.php", array( "function grant_review_eligibility(", "review" ) ),
    array( "FR015", "includes/class-wca-service.php", array( "function appointment_ics(", "calendar" ) ),
    array( "FR016", "includes/class-wca-service.php", array( "function create_payment_intent(", "payment" ) ),
    array( "FR017", "includes/class-wca-continuity-secure.php", array( "function seal(", "function open(" ) ),
    array( "FR018", "includes/class-wca-service.php", array( "function create_complaint(", "function appeal_complaint(" ) ),
    array( "NFR001", "includes/class-wca-authorization.php", array( "function can_view_appointment(", "function require_step_up(" ) ),
    array( "NFR002", "includes/class-wca-continuity-secure.php", array( "function privacy_eraser(", "function apply_retention(" ) ),
    array( "NFR003", "includes/class-wca-outbox.php", array( "function require_owner_receipt(", "function process(" ) ),
    array( "NFR004", "includes/class-wca-query-api.php", array( "function encode_cursor(", "function decode_cursor(" ) ),
    array( "NFR005", "assets/css/clinic.css", array( "wca-", "@media" ) ),
    array( "NFR006", "includes/class-wca-observability.php", array( "function log(", "function metric(" ) ),
    array( "NFR007", "includes/class-wca-schema.php", array( "function maybe_upgrade(", "function rollback_to_snapshot(" ) ),
    array( "NFR008", "includes/class-wca-schema.php", array( "function health(", "function install(" ) ),
    array( "NFR009", "worldwide-clinic.php", array( "Version: 1.2.16", "WCA" ) ),
    array( "NFR010", "includes/class-wca-service.php", array( "function valid_timezone(", "function valid_currency(" ) ),
    array( "FUT01+02", "includes/class-wca-future24.php", array( "function join_waitlist(", "function save_windows(" ) ),
    array( "FUT03+04", "includes/class-wca-future24.php", array( "function create_series(", "verified_resource_reservation_scope(" ) ),
    array( "FUT05+06", "includes/class-wca-future24.php", array( "function join_group_session(", "function safe_reschedule(" ) ),
    array( "FUT07+08", "includes/class-wca-future24.php", array( "function set_buffers(", "function heatmap(" ) ),
    array( "FUT09+10", "includes/class-wca-future24.php", array( "function advisor(", "function no_show_forecast(" ) ),
    array( "FUT11+12", "includes/class-wca-future24.php", array( "function save_questionnaire(", "function readiness(" ) ),
    array( "FUT13+14", "includes/class-wca-future24.php", array( "function verified_prerequisite_rules(", "function family_hub(" ) ),
    array( "FUT15+16", "includes/class-wca-future24.php", array( "function arrive(", "function queue_position(" ) ),
    array( "FUT17+18", "includes/class-wca-future24.php", array( "function create_disruption(", 'verified_support_participant_payload( $existing )', 'hash_equals( $current_subject, $subject )' ) ),
    array( "FUT19+20", "includes/class-wca-future24.php", array( "function request_virtual_room(", "function fhir_projection(" ) ),
    array( "FUT21+22", "includes/class-wca-future24.php", array( "function smart_find(", "function external_busy_conflict(" ) ),
    array( "FUT23+24", "includes/class-wca-future24.php", array( "function mutate_episode(", "function governance_log(", "wca_episode_payload_corrupt" ) ),
);
if ( count( $checks ) !== 40 ) { fwrite(STDERR,"T38 review catalogue is not 40\n");exit(1); }
$out = array();
$failed = array();
foreach ( $checks as $index => $check ) {
    list( $id, $path, $tokens ) = $check;
    $source = is_file( $base.'/'.$path ) ? file_get_contents( $base.'/'.$path ) : false;
    $missing = array();
    if ( ! is_string( $source ) ) { $missing[] = 'SOURCE_NOT_READABLE'; }
    else { foreach ( $tokens as $token ) { if ( strpos( $source, $token ) === false ) { $missing[] = $token; } } }
    $result = array( 'round' => $index+1, 'requirement' => 'F08-'.$id,
        'source' => $path, 'required_source_anchors' => $tokens,
        'missing' => $missing, 'passed_source_presence_only' => ! $missing );
    $out[] = $result;
    if ( $missing ) { $failed[] = $result; }
    echo 'T38 PLAN '.( $index+1 ).'/40 F08-'.$id.' '.( $missing ? 'GAP' : 'SOURCE-PASS' )."\n";
}
if ( ! is_dir( $base.'/build' ) ) { mkdir( $base.'/build',0775,true ); }
file_put_contents( $base.'/build/t38-forty-crossplan-review.json',
    json_encode( array( 'classification' => '40 exact-head plan-derived code-path screens only; not independent E2E proof',
        'passed' => 40-count($failed),'failures' => count($failed),
        'companion_runtime_parity' => 'unverified',
        'staging_deployed_db_migrations' => 'unverified',
        'items' => $out ), JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES ) );
if ( $failed ) {
    foreach ($failed as $f) { fwrite(STDERR,'T38 missing '.$f['requirement'].': '.implode(',',$f['missing'])."\n");}
    exit(1);
}
echo "T38 40/40 source trace screens passed; runtime and deployment verification remain separate.\n";
