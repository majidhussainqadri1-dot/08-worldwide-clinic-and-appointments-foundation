<?php
/** File 08 sixth fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t610src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t610has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t610lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$bootstrap=t610src('worldwide-clinic.php');
$contracts=t610src('includes/class-wca-contracts.php');
$auth=t610src('includes/class-wca-authorization.php');
$outbox=t610src('includes/class-wca-outbox.php');
$repo=t610src('includes/class-wca-repository.php');
$future=t610src('includes/class-wca-future24.php');
$continuity=t610src('includes/class-wca-continuity-secure.php');
$readme=t610src('readme.txt');
$repo_readme=t610src('README.md');
$status=t610src('STATUS.md');
$changelog=t610src('CHANGELOG.md');
t610has('doctor eligibility at serving root',$auth,'SWC_Doctor_Authority::is_eligible( $doctor_user_id )');
t610has('outbox dispatch stable message id argument',$outbox,'self::dispatch( (string) $item[\'message_id\']');
t610has('outbox envelope stable message id',$outbox,'\'message_id\'    => sanitize_text_field( $message_id )');
t610has('stale outbox recovery method',$repo,'function recover_stale_outbox');
t610has('stale processing selector',$repo,"WHERE status='processing' AND locked_at IS NOT NULL AND locked_at<%s");
t610has('stale retry progression',$repo,"status=CASE WHEN attempts>=7 THEN 'dead_letter' ELSE 'retry' END");
t610has('dispatcher invokes stale recovery',$outbox,'WCA_Repository::recover_stale_outbox( 300 )');
t610has('waitlist keyset iterator',$future,'function waitlist_candidates');
t610has('waitlist iterator consumption',$future,'foreach ( self::waitlist_candidates( $clinic_id ) as $wait )');
t610lacks('no waitlist first-50 ceiling',$future,"status='waiting' AND (expires_at IS NULL OR expires_at>%s) ORDER BY created_at ASC,id ASC LIMIT 50");
t610has('feature row keyset helper',$future,'function feature_rows_for_clinic');
t610has('questionnaire complete traversal',$future,'feature_rows_for_clinic( \'F08-FUT-11\', $clinic_id, \'template_active\', \'questionnaire\' )');
t610lacks('no questionnaire limit20',$future,"status='template_active' ORDER BY id DESC LIMIT 20");
t610has('prerequisite complete traversal',$future,'feature_rows_for_clinic( \'F08-FUT-13\', $clinic_id, \'rule_active\', \'payload\' )');
t610lacks('no prerequisite limit20',$future,"status='rule_active' ORDER BY id DESC LIMIT 20");
t610has('anchored monthly helper',$future,'function advance_months_anchored');
t610has('monthly recurrence uses anchor',$future,'self::advance_months_anchored( $cursor, $interval, $anchor_day )');
t610lacks('no raw monthly modify drift',$future,'$cursor = $cursor->modify( \'+\' . $interval . \' months\' )');
t610has('followup keyset cursor',$continuity,'WHERE appointment_id=%d AND id>%d ORDER BY id ASC LIMIT %d');
t610lacks('no followup limit100',$continuity,'WHERE appointment_id=%d ORDER BY due_at ASC,id ASC LIMIT 100');
t610has('plugin 1.2.8',$bootstrap,'Version: 1.2.8');
t610has('runtime 1.2.8',$contracts,"RUNTIME_VERSION                 = '1.2.8'");
t610has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
t610has('readme stable 1.2.8',$readme,'Stable tag: 1.2.8');
t610has('repository readme 1.2.8',$repo_readme,'Runtime candidate: **1.2.8**');
t610has('status 1.2.8',$status,'Runtime candidate: **1.2.8**');
t610has('changelog 1.2.8',$changelog,'## 1.2.7 — 2026-08-11');
t610has('zero commission',$contracts,"'commission_percent' => 0");
t610has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t610has('no automated prescribing',$contracts,"'automated_prescribing' => false");
$runtime=implode("\n",array($bootstrap,$contracts,$auth,$outbox,$repo,$future,$continuity));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t610lacks('forbidden runtime primitive',$runtime,$token); }
if($failures){ fwrite(STDERR,"File 08 sixth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'File 08 sixth fresh ten-round regression assertions passed: ' . $checks . '/' . $checks . ".\n";
