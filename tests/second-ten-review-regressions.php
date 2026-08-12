<?php
/** File 08 second fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function s210src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function s210has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function s210lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }

$bootstrap=s210src('worldwide-clinic.php');
$auth=s210src('includes/class-wca-authorization.php');
$service=s210src('includes/class-wca-service.php');
$plan=s210src('includes/class-wca-plan-guard.php');
$command=s210src('includes/class-wca-appointment-command.php');
$first=s210src('includes/class-wca-ten-review-hardening.php');
$second=s210src('includes/class-wca-second-ten-review-hardening.php');
$idempotency=s210src('includes/class-wca-idempotency.php');
$outbox=s210src('includes/class-wca-outbox.php');
$contracts=s210src('includes/class-wca-contracts.php');
$future=s210src('includes/class-wca-future24.php');
$readme=s210src('readme.txt');
$repo_readme=s210src('README.md');
$status=s210src('STATUS.md');
$changelog=s210src('CHANGELOG.md');

// S2-R1: administrator transition access is purpose-limited and step-up checked.
s210has('admin transition operations purpose',$auth,'operations');
s210has('purpose-limited appointment view',$auth,'can_view_appointment');
s210has('administrator step-up',$auth,'appointment_');
s210has('step-up enforcement',$auth,'require_step_up');
s210has('canonical admin actor',$auth,"return 'admin'");

// S2-R2: slot holds require an explicit client replay key.
s210has('slot key regex',$service,'A-Za-z0-9._:-');
s210has('slot key required',$service,'wca_idempotency_required');
s210lacks('no synthetic slot key',$service,"idempotency_key'] ?? WCA_Repository::uuid");

// S2-R3: replay identity is isolated by authorized patient.
s210has('patient namespace marker',$plan,"'p' . absint");
s210has('patient replay hash',$plan,'hash( \'sha256\', $client_key )');
s210has('patient binding',$plan,'patient_user_id');

// S2-R4: ambiguous stale mutation reservations fail closed.
s210has('governed stale request guard',$command,'guard_stale_request_claim');
s210has('governed reconciliation required',$command,'reconciliation_required');
s210has('REST stale guard',$second,'idempotency_stale_processing_blocked_total');
s210has('REST reconciliation required',$second,'reconciliation_required');
s210has('safe reservation helper',$idempotency,'stale_processing');
s210lacks('safe helper has no stale takeover SQL',$idempotency,'SET updated_at=');

// S2-R5: Future24 calendar values are strict.
s210has('Future24 calendar validator',$second,'validate_calendar_payload');
s210has('Future24 date validation',$second,'wca_future24_date_invalid');
s210has('Future24 timestamp validation',$second,'wca_future24_time_invalid');
s210has('strict UTC parser',$second,'strict_utc');
s210has('strict UTC round-trip parsing',$second,'DateTimeImmutable::createFromFormat');

// S2-R6: Future24 REST DTOs scrub native numeric IDs.
s210has('Future24 post-dispatch',$second,'post_dispatch');
s210has('appointment native id blocked',$second,"'appointment_id'");
s210has('patient native id blocked',$second,"'patient_user_id'");
s210has('doctor native id blocked',$second,"'doctor_user_id'");
s210has('guardian native id blocked',$second,"'guardian_user_id'");
s210has('Future24 no-store',$second,'private, no-store, max-age=0');

// S2-R7: outbox concurrency is serialized.
s210has('outbox GET_LOCK',$outbox,'GET_LOCK');
s210has('outbox RELEASE_LOCK',$outbox,'RELEASE_LOCK');
s210has('outbox finally release',$outbox,'finally');
s210has('outbox contention metric',$outbox,'outbox_worker_contention_total');
s210has('outbox stable lock name',$outbox,'wca-file08-outbox-dispatch');

// S2-R8: supported mutation entry points retain scoped authority/preconditions.
s210has('transition precondition',$first,'wca_transition_precondition_required');
s210has('payer authority',$first,'wca_payment_payer_required');
s210has('availability scope',$first,'wca_availability_doctor_scope');
s210has('doctor clinic serving filter',$first,'wca_doctor_may_serve_clinic');
s210has('Future24 expected version',$future,'expected_version');

// S2-R9: release/document identity is aligned without schema inflation.
s210has('plugin header 1.2.14',$bootstrap,'Version: 1.2.14');
s210has('runtime constant 1.2.14',$bootstrap,"WCA_VERSION', '1.2.14");
s210has('contract runtime 1.2.14',$contracts,"RUNTIME_VERSION                 = '1.2.14'");
s210has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
s210has('readme stable 1.2.14',$readme,'Stable tag: 1.2.14');
s210has('repository readme 1.2.14',$repo_readme,'Runtime candidate: **1.2.14**');
s210has('status 1.2.14',$status,'Runtime candidate: **1.2.14**');
s210has('changelog 1.2.13 historical record',$changelog,'## 1.2.13 — 2026-08-11');
s210has('second hardening loaded',$bootstrap,'class-wca-second-ten-review-hardening.php');
s210has('second hardening booted',$bootstrap,'WCA_Second_Ten_Review_Hardening::boot()');
s210has('safe idempotency helper loaded',$bootstrap,'class-wca-idempotency.php');

// Global negative-path and ownership invariants.
$runtime=implode("\n",array($bootstrap,$auth,$service,$plan,$command,$first,$second,$idempotency,$outbox,$contracts));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ s210lacks('forbidden runtime primitive',$runtime,$token); }
s210has('zero commission preserved',$contracts,"'commission_percent' => 0");
s210has('donation neutrality preserved',$contracts,"'donation_visibility_link' => false");
s210has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
s210has('no automated prescribing',$contracts,"'automated_prescribing' => false");
s210lacks('no File26 table ownership',$runtime,'CREATE TABLE file26');

if($failures){ fwrite(STDERR,"File 08 second-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo "File 08 second fresh ten-round regression assertions passed: {$checks}/{$checks}.\n";
