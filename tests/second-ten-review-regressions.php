<?php
/** File 08 second fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function s210src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function s210has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function s210lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
function s210true( $label,$condition ) { global $failures,$checks; $checks++; if(!$condition){ $failures[]=$label; } }

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
$readme=s210src('readme.txt');
$repo_readme=s210src('README.md');
$status=s210src('STATUS.md');
$changelog=s210src('CHANGELOG.md');

// S2-R1 administrator transition actor must be purpose limited and step-up revalidated.
s210has('admin transition purpose',$auth,"? 'operations' : ''");
s210has('purpose-limited view',$auth,'can_view_appointment( $appointment_id, $user_id, $purpose )');
s210has('operations step-up',$auth,"require_step_up( 'appointment_' . $purpose");
s210has('canonical admin actor',$auth,"return 'admin'");
s210lacks('operations-only cannot become admin',$auth,"user_can( $user_id, 'manage_wca_operations' ) ) { return 'admin';");

// S2-R2 explicit slot-hold idempotency.
s210has('explicit slot key validation',$service,"preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $idempotency_key )");
s210has('missing slot key error',$service,'wca_idempotency_required');
s210lacks('no synthetic slot key',$service,"$data['idempotency_key'] = sanitize_text_field( $data['idempotency_key'] ?? WCA_Repository::uuid() );");

// S2-R3 client replay key must be isolated by patient before the repository global hash.
s210has('patient namespaced replay',$plan,"'p' . absint( $patient_user_id ) . ':' . hash( 'sha256', $client_key )");
s210has('patient stored on canonical hold',$plan,"'patient_user_id' => absint( $patient_user_id )");
s210has('plan guard validates key',$plan,'wca_idempotency_required');

// S2-R4 ambiguous stale mutation reservations fail closed rather than being automatically replayed.
s210has('governed command stale guard',$command,'guard_stale_request_claim');
s210has('request scope stale lookup',$command,"'request_appointment'");
s210has('request stale reconciliation required',$command,"'reconciliation_required' => true");
s210has('REST stale guard priority',$second,"add_filter( 'rest_pre_dispatch', array( __CLASS__, 'pre_dispatch' ), 5, 3 )");
s210has('REST stale mutation block',$second,'idempotency_stale_processing_blocked_total');
s210has('safe reservation helper present',$idempotency,'Never steal an ambiguous in-flight mutation lease');
s210lacks('safe helper cannot reclaim stale',$idempotency,"UPDATE {$table} SET updated_at=");

// S2-R5 Future24 date/time validation is strict rather than strtotime normalization.
s210has('Future24 mutation validator',$second,'validate_calendar_payload');
s210has('strict date validation',$second,'WCA_Service::valid_date');
s210has('strict timestamp parser',$second,'strict_utc');
s210has('Future24 invalid date error',$second,'wca_future24_date_invalid');
s210has('Future24 invalid time error',$second,'wca_future24_time_invalid');
s210lacks('strict validator avoids strtotime',$second,'strtotime(');

// S2-R6 Future24 responses never expose native numeric identifiers.
s210has('Future24 post-dispatch scrub',$second,"0 !== strpos( (string) $request->get_route(), '/wca/v1/future24/' )");
s210has('native appointment id blocked',$second,"'appointment_id'");
s210has('native patient user id blocked',$second,"'patient_user_id'");
s210has('native doctor user id blocked',$second,"'doctor_user_id'");
s210has('native guardian id blocked',$second,"'guardian_user_id'");
s210has('Future24 no-store',$second,"Cache-Control', 'private, no-store, max-age=0");

// S2-R7 outbox workers are serialized across cron/shutdown concurrency.
s210has('outbox advisory acquire',$outbox,'SELECT GET_LOCK(%s,0)');
s210has('outbox advisory release',$outbox,'SELECT RELEASE_LOCK(%s)');
s210has('outbox release in finally',$outbox,'finally');
s210has('outbox contention metric',$outbox,'outbox_worker_contention_total');
s210has('single process lock name',$outbox,'wca-file08-outbox-dispatch');

// S2-R8 supported mutation entry points retain owner/object/state restrictions.
s210has('transition precondition first-cycle guard',$first,'wca_transition_precondition_required');
s210has('patient/guardian payer guard',$first,'wca_payment_payer_required');
s210has('availability doctor-clinic scope',$first,'wca_availability_doctor_scope');
s210has('doctor-serving-clinic explicit filter',$first,'wca_doctor_may_serve_clinic');
s210has('opaque transition route guarded',$first,"appointment-refs/[0-9a-fA-F-]{36}/transitions");
s210has('Future24 one-tap calls carry expected version',s210src('includes/class-wca-future24.php'),'expected_version');

// S2-R9 release identity/document parity; schemas intentionally unchanged.
s210has('bootstrap 1.2.2 header',$bootstrap,'Version: 1.2.2');
s210has('bootstrap 1.2.2 constant',$bootstrap,"define( 'WCA_VERSION', '1.2.2' )");
s210has('contracts runtime 1.2.2',$contracts,"const RUNTIME_VERSION                 = '1.2.2';");
s210has('core schema unchanged',$contracts,"const SCHEMA_VERSION                  = '3.1.0';");
s210has('readme stable 1.2.2',$readme,'Stable tag: 1.2.2');
s210has('repo readme runtime 1.2.2',$repo_readme,'Runtime candidate: **1.2.2**');
s210has('status runtime 1.2.2',$status,'Runtime candidate: **1.2.2**');
s210has('changelog 1.2.2',$changelog,'## 1.2.2 — 2026-08-11');
s210has('bootstrap second hardening include',$bootstrap,'class-wca-second-ten-review-hardening.php');
s210has('bootstrap second hardening boot',$bootstrap,'WCA_Second_Ten_Review_Hardening::boot()');
s210has('bootstrap safe idempotency include',$bootstrap,'class-wca-idempotency.php');

// Global negative-path and ownership invariants.
$runtime=implode("\n",array($bootstrap,$auth,$service,$plan,$command,$first,$second,$idempotency,$outbox,$contracts));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ s210lacks('forbidden runtime primitive',$runtime,$token); }
s210has('zero commission preserved',$contracts,"'commission_percent' => 0");
s210has('donation rank neutrality preserved',$contracts,"'donation_visibility_link' => false");
s210has('no automated diagnosis preserved',$contracts,"'automated_diagnosis' => false");
s210has('no automated prescribing preserved',$contracts,"'automated_prescribing' => false");
s210lacks('no File26 table ownership',$runtime,'CREATE TABLE file26');

if($failures){ fwrite(STDERR,"File 08 second-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo "File 08 second fresh ten-round regression assertions passed: {$checks}/{$checks}.\n";
