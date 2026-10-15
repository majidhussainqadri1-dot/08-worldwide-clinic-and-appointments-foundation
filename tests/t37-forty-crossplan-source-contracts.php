<?php
/** T37: forty distinct plan-to-native-code anchors and two explicit negative source guards.
 * Covers user-attached File08 FR001–18 / NFR001–10 / FUT01–24, central three plans,
 * owner contracts File00/03/07/09/14/17/19/20/24/25/26/CF01.
 *
 * NOT 40 full semantic proofs, clinical E2E tests, or deployed-state validation.
 * Do not stop at first failure: complete ALL 40 inspections, freeze diagnostic output,
 * then require corrections and exact-head CI before closure.
 */
require __DIR__ . '/bootstrap.php';
$root=dirname(__DIR__);
$checks=array(
    array( "F08-FR-001", "Clinic/branch canonical owner", array( array( "includes/class-wca-service.php", "create_clinic", "create_branch" ) ) ),
    array( "F08-FR-002", "Service, fee and currency policy", array( array( "includes/class-wca-service.php", "save_service", "valid_currency" ) ) ),
    array( "F08-FR-003", "Availability timezone and DST", array( array( "includes/class-wca-service.php", "set_availability", "generate_rule_slots" ) ) ),
    array( "F08-FR-004", "Fresh search and slot hold", array( array( "includes/class-wca-service.php", "search_slots", "hold_slot" ) ) ),
    array( "F08-FR-005", "Booked appointment & idempotency", array( array( "includes/class-wca-service.php", "request_appointment" ), array( "includes/class-wca-repository.php", "claim_idempotency" ) ) ),
    array( "F08-FR-006", "Doctor decision events", array( array( "includes/class-wca-service.php", "transition_appointment", "event_for_transition" ) ) ),
    array( "F08-FR-007", "Reschedule locked slot", array( array( "includes/class-wca-service.php", "hold_reschedule_slot", "transition_appointment" ) ) ),
    array( "F08-FR-008", "Cancellation/no-show slot release", array( array( "includes/class-wca-repository.php", "release_appointment_slot" ), array( "includes/class-wca-service.php", "transition_appointment" ) ) ),
    array( "F08-FR-009", "Completion and controlled review eligibility", array( array( "includes/class-wca-service.php", "transition_appointment" ), array( "includes/class-wca-repository.php", "grant_review_eligibility" ) ) ),
    array( "F08-FR-010", "Patient and clinic scoped dashboards", array( array( "includes/class-wca-query-api.php", "list_patient_appointments", "list_clinic_schedule" ) ) ),
    array( "F08-FR-011", "Emergency red-flag diversion", array( array( "includes/class-wca-service.php", "emergency_red_flag" ) ) ),
    array( "F08-FR-012", "Consent versioning and revocation", array( array( "includes/class-wca-continuity-secure.php", "grant_context_consent", "revoke_context_consent" ) ) ),
    array( "F08-FR-013", "File17 context-only clinical relationship", array( array( "includes/class-wca-continuity-secure.php", "file17_context" ), array( "includes/class-wca-service.php", "file17_context_payload" ) ) ),
    array( "F08-FR-014", "One real completion review token", array( array( "includes/class-wca-repository.php", "grant_review_eligibility", "consume_review_eligibility" ) ) ),
    array( "F08-FR-015", "ICS and external calendar mapping", array( array( "includes/class-wca-service.php", "appointment_ics" ), array( "includes/class-wca-repository.php", "upsert_calendar_mapping_from_provider" ) ) ),
    array( "F08-FR-016", "Payment provider and ledger projection", array( array( "includes/class-wca-service.php", "create_payment_intent" ), array( "includes/class-wca-repository.php", "project_payment_status" ) ) ),
    array( "F08-FR-017", "Restricted encryption and record boundary", array( array( "includes/class-wca-continuity-secure.php", "seal", "open" ) ) ),
    array( "F08-FR-018", "Complaint with appeal/CF02 owner", array( array( "includes/class-wca-service.php", "create_complaint", "appeal_complaint" ) ) ),
    array( "F08-NFR-001", "File00/09 claims and IDOR", array( array( "includes/class-wca-authorization.php", "can_view_appointment", "doctor_can_serve_clinic" ) ) ),
    array( "F08-NFR-002", "Privacy export/eraser and retention", array( array( "includes/class-wca-continuity-secure.php", "privacy_exporter", "privacy_eraser" ) ) ),
    array( "F08-NFR-003", "Durable outbox owner receipt", array( array( "includes/class-wca-outbox.php", "process", "require_owner_receipt" ) ) ),
    array( "F08-NFR-004", "Signed/strict bounded cursors", array( array( "includes/class-wca-query-api.php", "encode_cursor", "decode_cursor" ) ) ),
    array( "F08-NFR-005", "Responsive accessible interface", array( array( "assets/css/clinic.css", "#@media" ), array( "assets/js/continuity.js", "#aria|focus|role" ) ) ),
    array( "F08-NFR-006", "Privacy-safe health and logging", array( array( "includes/class-wca-observability.php", "log", "metric" ) ) ),
    array( "F08-NFR-007", "Schema upgrade and rollback", array( array( "includes/class-wca-schema.php", "maybe_upgrade", "rollback_to_snapshot" ) ) ),
    array( "F08-NFR-008", "Runbook/health CLI", array( array( "includes/class-wca-cli.php", "register" ), array( "includes/class-wca-schema.php", "health" ) ) ),
    array( "F08-NFR-009", "Exact versioned runtime contracts", array( array( "worldwide-clinic.php", "#WCA_VERSION" ), array( "includes/class-wca-contracts.php", "#RUNTIME_VERSION" ) ) ),
    array( "F08-NFR-010", "Localized time zones and pricing", array( array( "includes/class-wca-service.php", "valid_timezone", "valid_currency" ) ) ),
    array( "F08-FUT-01+02", "Waitlist and bounded windows", array( array( "includes/class-wca-future24.php", "join_waitlist", "save_windows" ) ) ),
    array( "F08-FUT-03+04", "Series and resource reservation", array( array( "includes/class-wca-future24.php", "create_series", "verified_resource_reservation_scope", "reserve_resource" ) ) ),
    array( "F08-FUT-05+06", "Group scheduling and safe reschedule", array( array( "includes/class-wca-future24.php", "join_group_session", "safe_reschedule" ) ) ),
    array( "F08-FUT-07+08", "Buffer and capacity heatmap", array( array( "includes/class-wca-future24.php", "set_buffers", "heatmap" ) ) ),
    array( "F08-FUT-09+10", "No individual hidden score", array( array( "includes/class-wca-future24.php", "advisor", "no_show_forecast" ) ) ),
    array( "F08-FUT-11+12", "Previsit questionnaire and readiness", array( array( "includes/class-wca-future24.php", "save_questionnaire", "readiness" ) ) ),
    array( "F08-FUT-13+14", "Blocking prerequisites/current guardian", array( array( "includes/class-wca-future24.php", "verified_prerequisite_rules", "prerequisite_state", "family_hub" ) ) ),
    array( "F08-FUT-15+16", "Arrival and private queue are distinct", array( array( "includes/class-wca-future24.php", "arrive", "queue_position" ) ) ),
    array( "F08-FUT-17+18", "Complete disruption and safe revocation", array( array( "includes/class-wca-future24.php", "create_disruption", "revoke_participant", "clinic_appointments_between_all", "#wca_disruption_appointment_page_read_failed", "#verified_support_participant_payload" ) ) ),
    array( "F08-FUT-19+20", "File17 room transport and FHIR projection", array( array( "includes/class-wca-future24.php", "request_virtual_room", "fhir_projection" ) ) ),
    array( "F08-FUT-21+22", "SMART and bounded external calendar", array( array( "includes/class-wca-future24.php", "smart_book", "save_external_busy" ) ) ),
    array( "F08-FUT-23+24", "Private episode and no auto-clinical governance", array( array( "includes/class-wca-future24.php", "mutate_episode", "governance_log" ) ) ),
);
if(count($checks)!==40){fwrite(STDERR,"T37: missing planned review passes\n");exit(1);}
$results=array();$failures=array();$sha=(string)(getenv('GITHUB_SHA')?:'');
foreach($checks as $i=>$check){
    list($id,$purpose,$files)=$check;
    $missing=array();$evidence=array();$visited=0;
    foreach($files as $rule){
        $path=array_shift($rule);
        $absolute=$root.'/'.$path;
        $src=is_file($absolute)?file_get_contents($absolute):false;
        $visited++;
        if(!is_string($src)){ $missing[]=$path.' cannot be read';continue; }
        foreach($rule as $anchor){
            if(substr($anchor,0,1)==='#'){
                $pattern=substr($anchor,1);
                $found=@preg_match('/'.str_replace('/','\\/',$pattern).'/i',$src)===1;
            } else {
                $found=preg_match('/\\bfunction\\s+'.preg_quote($anchor,'/').'\\s*\\(/i',$src)===1;
            }
            if(!$found){$missing[]=$path.':'.$anchor;}
            else{$evidence[]=$path.':'.$anchor;}
        }
    }
    $ok=!$missing;
    $row=array('round'=>$i+1,'requirement'=>$id,'focus'=>$purpose,
        'source_files_checked'=>$visited,'evidence'=>$evidence,'missing'=>$missing,
        'source_only'=>true,'passed'=>$ok);
    $results[]=$row;
    echo 'T37 '.str_pad((string)($i+1),2,'0',STR_PAD_LEFT).'/40 '.$id.' '.($ok?'PASS':'DEFECT').
         ' anchored='.count($evidence).' missing='.count($missing)."\n";
    if(!$ok){$failures[]=$row;}
}
if(!is_dir($root.'/build')){mkdir($root.'/build',0775,true);}
file_put_contents($root.'/build/t37-plan-code-40-reviews.json',
    json_encode(array('exact_ci_sha'=>$sha,'plan_paired_reviews'=>40,
        'scope'=>'source path and method/guard presence; not full semantic or deployed proof',
        'passed'=>40-count($failures),'defect_or_gap'=>count($failures),
        'rounds'=>$results),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
echo 'T37 TOTAL 40; anchored='. (40-count($failures)) .' missing='.count($failures)."\n";
if($failures){
    foreach($failures as $row){fwrite(STDERR, 'T37 source mismatch R'.$row['round'].' '.implode(', ',$row['missing'])."\n");}
    exit(1);
}
echo "T37 exact-source review screens green. Staging/live still unverified.\n";
