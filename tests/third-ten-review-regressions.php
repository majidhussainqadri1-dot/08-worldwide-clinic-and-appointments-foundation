<?php
/** File 08 third fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t310src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t310has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t310lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }

$bootstrap=t310src('worldwide-clinic.php');
$contracts=t310src('includes/class-wca-contracts.php');
$continuity=t310src('includes/class-wca-continuity-secure.php');
$future=t310src('includes/class-wca-future24.php');
$repo=t310src('includes/class-wca-repository.php');
$second=t310src('includes/class-wca-second-ten-review-hardening.php');
$service=t310src('includes/class-wca-service.php');
$auth=t310src('includes/class-wca-authorization.php');
$rest=t310src('includes/class-wca-rest.php');
$readme=t310src('readme.txt');
$repo_readme=t310src('README.md');
$status=t310src('STATUS.md');
$changelog=t310src('CHANGELOG.md');

// T3-R1: canonical idempotency repository fails closed on ambiguous stale processing.
t310has('stale marker',$repo,"existing['stale_processing'] = true");
t310has('stale metric',$repo,'idempotency_stale_processing_total');
t310lacks('no stale auto takeover comment',$repo,'Reclaim a stale processing lease atomically');
t310lacks('no stale takeover SQL',$repo,'SET updated_at=%s,expires_at=%s WHERE id=%d AND status=\'processing\'');

// T3-R2: HTTP stale guard queries the same canonical scope used by HTTP reservations.
t310has('HTTP stale scope',$second,'$scope = \'http_\' . substr( hash( \'sha256\', $route ), 0, 24 );');
t310lacks('no stale scope mismatch',$second,'$scope = \'tenreview_\' . substr( hash( \'sha256\', $route ), 0, 24 );');

// T3-R3: payment authority is enforced at the canonical service root.
t310has('payer error',$service,'wca_payment_payer_required');
t310has('patient binding',$service,'SWC_Helpers::meta( $appointment_id, \'patient_user_id\'');
t310has('guardian binding',$service,'SWC_Helpers::meta( $appointment_id, \'guardian_user_id\'');
t310has('guardian live recheck',$service,'WCA_Central_Governance::validate_patient_guardian');

// T3-R4: direct/internal transition callers cannot omit optimistic-concurrency preconditions.
t310has('transition root precondition',$service,'wca_transition_precondition_required');
t310lacks('no expected status fallback',$service,"expected_status'] ?? SWC_Helpers::status");
t310lacks('no expected version fallback',$service,"expected_version'] ?? SWC_Helpers::record_version");

// T3-R5: every protected core mutation is no-store/noindex.
t310has('protected mutation cache gate',$rest,'$protected_mutation');
t310has('safe read methods',$rest,"array( 'GET', 'HEAD', 'OPTIONS' )");
t310has('no-store core responses',$rest,'private, no-store, max-age=0');
t310has('pragma no-cache',$rest,"'Pragma', 'no-cache'");

// T3-R6: ICS generation strictly validates persisted UTC timestamps.
t310has('strict UTC calendar parser',$service,'strict_utc_timestamp');
t310has('invalid stored calendar error',$service,'wca_calendar_time_invalid');
t310has('calendar chronological guard',$service,'$end_ts <= $start_ts');
t310has('validated DTSTART',$service,'gmdate( \'Ymd\\THis\\Z\', $start_ts )');
t310has('validated DTEND',$service,'gmdate( \'Ymd\\THis\\Z\', $end_ts )');
t310lacks('no permissive DTSTART parse',$service,'strtotime( $start . \' UTC\' )');
t310lacks('no permissive DTEND parse',$service,'strtotime( $end . \' UTC\' )');

// T3-R7: outbox row claims re-check eligibility atomically at UPDATE time.
t310has('conditional outbox claim',$repo,"WHERE id=%d AND status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s)");
t310has('single-row claim requirement',$repo,'1 === (int) $ok');
t310has('worker ownership readback',$repo,"status='processing' AND locked_by=%s");

// T3-R8: verified/eligible doctor identity is not enough; current clinic-serving authority is required.
t310has('root clinic service helper',$service,'private static function doctor_may_serve_clinic');
t310has('service doctor scope error',$service,'wca_service_doctor_scope');
t310has('availability doctor scope error',$service,'wca_availability_doctor_scope');
t310has('doctor clinic integration filter',$auth,'wca_doctor_may_serve_clinic');
t310has('doctor scheduling delegation',$auth,'delegated_clinic_ids( $doctor_user_id, \'schedule\' )');

// T3-R9: release identity advances without schema inflation.
t310has('plugin header 1.2.15',$bootstrap,'Version: 1.2.15');
t310has('runtime constant 1.2.15',$bootstrap,"WCA_VERSION', '1.2.15");
t310has('contract runtime 1.2.15',$contracts,"RUNTIME_VERSION                 = '1.2.15'");
t310has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.4.0'");
t310has('continuity schema unchanged',$continuity,"const SCHEMA_VERSION = '1.1.0'");
t310has('future schema migrated for R17 privacy lifecycle',$future,"const SCHEMA_VERSION   = '1.1.0'");
t310has('readme stable 1.2.15',$readme,'Stable tag: 1.2.15');
t310has('repository readme 1.2.15',$repo_readme,'Runtime candidate: **1.2.15**');
t310has('status 1.2.15',$status,'Runtime candidate: **1.2.15**');
t310has('changelog 1.2.13 historical record',$changelog,'## 1.2.13 — 2026-08-11');

// Cross-cycle safety/ownership invariants.
$runtime=implode("\n",array($bootstrap,$repo,$second,$service,$rest,$contracts));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t310lacks('forbidden runtime primitive',$runtime,$token); }
t310has('zero commission',$contracts,"'commission_percent' => 0");
t310has('donation neutrality',$contracts,"'donation_visibility_link' => false");
t310has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t310has('no automated prescribing',$contracts,"'automated_prescribing' => false");
t310lacks('no File26 table ownership',$runtime,'CREATE TABLE file26');

if($failures){ fwrite(STDERR,"File 08 third-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo "File 08 third fresh ten-round regression assertions passed: {$checks}/{$checks}.\n";
