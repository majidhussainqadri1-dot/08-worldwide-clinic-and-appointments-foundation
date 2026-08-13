<?php
/** File 08 fourth fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t410src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t410has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t410lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$bootstrap=t410src('worldwide-clinic.php');
$contracts=t410src('includes/class-wca-contracts.php');
$schema=t410src('includes/class-wca-schema.php');
$repo=t410src('includes/class-wca-repository.php');
$guard=t410src('includes/class-wca-plan-guard.php');
$service=t410src('includes/class-wca-service.php');
$rest=t410src('includes/class-wca-rest.php');
$calendar=t410src('includes/class-wca-calendar-link.php');
$future=t410src('includes/class-wca-future24.php');
$readme=t410src('readme.txt');
$repo_readme=t410src('README.md');
$status=t410src('STATUS.md');
$changelog=t410src('CHANGELOG.md');

// F4-R1 branch truth survives hold -> appointment -> reschedule.
t410has('slot hold branch column',$schema,'branch_id bigint(20) unsigned NOT NULL DEFAULT 0');
t410has('hold branch scope',$repo,'wca_slot_branch_scope');
t410has('hold replay branch',$repo,"absint( \$row['branch_id'] ?? 0 ) === \$branch_id");
t410has('bookable branch recheck',$guard,'wca_hold_branch_scope');
t410has('appointment branch from hold',$service,"absint( \$hold['branch_id'] ?? 0 )");
t410has('reschedule proposed branch',$service,"'proposed_branch_id' => absint( \$hold['branch_id'] ?? 0 )");
t410has('reschedule confirmed branch',$service,"'branch_id' => absint( \$hold['branch_id'] ?? 0 )");

// F4-R2 public slot discovery is clinic-isolated.
t410has('availability clinic arg',$repo,'list_availability_rules( $doctor_user_id, $service_id = 0, $clinic_id = 0 )');
t410has('availability clinic filter',$repo,"\$where .= ' AND clinic_id=%d'");
t410has('slot service scope',$service,'wca_slot_service_scope');
t410has('slot rules include clinic',$service,'list_availability_rules( $doctor_id, $service_id, $clinic_id )');

// F4-R3 group appointment cancellation/leave semantics.
t410has('group leave route',$future,'/leave');
t410has('group cancel route',$future,'/cancel');
t410has('group leave command',$future,'function leave_group_session');
t410has('group cancel command',$future,'function cancel_group_session');
t410has('group left state',$future,'group_left');
t410has('group cancelled state',$future,'group_cancelled');

// F4-R4 signed calendar links strictly validate persisted UTC.
t410has('signed calendar strict parser',$calendar,'strict_utc_timestamp');
t410has('signed calendar invalid error',$calendar,'wca_calendar_time_invalid');
t410lacks('signed calendar no permissive start parse',$calendar,"strtotime( \$start . ' UTC' )");
t410lacks('signed calendar no permissive end parse',$calendar,"strtotime( \$end . ' UTC' )");

// F4-R5 payment intent uniqueness/idempotency is migration-safe.
t410has('payment nullable provider ref',$schema,'provider_ref varchar(191) NULL DEFAULT NULL');
t410has('payment nullable request key',$schema,'request_key char(64) NULL DEFAULT NULL');
t410has('payment request unique',$schema,'UNIQUE KEY appointment_request (appointment_id,provider,request_key)');
t410has('payment service key error',$service,'wca_payment_idempotency_required');
t410has('payment canonical claim',$service,"claim_idempotency( 'payment_intent'");
t410has('REST payment passes header',$rest,"get_header( 'Idempotency-Key' )");

// F4-R6 direct/internal appointment service callers cannot bypass consent.
t410has('root privacy consent',$service,'wca_privacy_consent_required');
t410has('root emergency acknowledgement',$service,'wca_emergency_ack_required');
t410has('root teleconsult consent',$service,'wca_teleconsult_consent_required');
t410has('root affirmative helper',$service,'private static function affirmative');

// F4-R7 idempotency fingerprint binds the actual request semantics.
t410has('fingerprint reason',$service,"'reason'");
t410has('fingerprint privacy',$service,"'privacy_consent'");
t410has('fingerprint emergency',$service,"'emergency_acknowledged'");
t410has('fingerprint telehealth',$service,"'telehealth_consent'");

// F4-R8 activation rechecks high-risk authority and publishable inventory.
t410has('activation step-up',$service,"require_step_up( 'activate_clinic'");
t410has('activation owner eligibility',$service,'wca_clinic_owner_ineligible');
t410has('activation public branches',$service,"list_branches( \$clinic['id'], true )");
t410has('activation active services',$service,"list_services( \$clinic['id'], true )");

// F4-R9 group joins recheck current clinic/service and time state.
t410has('group stale scope',$future,'wca_group_scope_stale');
t410has('group started guard',$future,'wca_group_started');
t410has('group current clinic',$future,"get_clinic( absint( \$session['clinic_id'] ), true )");

// F4-R10 doctor suspension is not silently truncated at 500.
t410has('suspension bounded batches',$service,"'posts_per_page' => 200");
t410has('suspension paged traversal',$service,"'paged' => \$page");
t410has('suspension scan metric',$service,'doctor_suspension_appointments_scanned');
t410lacks('no hard 500 truncation',$service,"'posts_per_page' => 500");

// Release identity / schema law.
t410has('plugin 1.2.13',$bootstrap,'Version: 1.2.15');
t410has('runtime 1.2.13',$contracts,"RUNTIME_VERSION                 = '1.2.15'");
t410has('core schema 3.2.0',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
t410has('readme 1.2.7',$readme,'Stable tag: 1.2.15');
t410has('repo readme 1.2.7',$repo_readme,'Runtime candidate: **1.2.15**');
t410has('status 1.2.13',$status,'Runtime candidate: **1.2.15**');
t410has('changelog 1.2.13',$changelog,'## 1.2.7 — 2026-08-11');

$runtime=implode("\n",array($bootstrap,$contracts,$schema,$repo,$guard,$service,$rest,$calendar,$future));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t410lacks('forbidden runtime primitive',$runtime,$token); }
t410has('zero commission',$contracts,"'commission_percent' => 0");
t410has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t410has('no automated prescribing',$contracts,"'automated_prescribing' => false");
if($failures){ fwrite(STDERR,"File 08 fourth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'File 08 fourth fresh ten-round regression assertions passed: ' . $checks . '/' . $checks . ".\n";
