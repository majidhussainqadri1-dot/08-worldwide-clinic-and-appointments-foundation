<?php
/** T33: current 1.2.16 cross-file owner reconciliation and File14 conservation. */
$root=dirname(__DIR__);
$paths=array(
 'plugin'=>'worldwide-clinic.php',
 'contracts'=>'includes/class-wca-contracts.php',
 'auth'=>'includes/class-wca-authorization.php',
 'doctor'=>'includes/class-swc-doctor-authority.php',
 'cf01'=>'includes/class-swc-cf01-care-context.php',
 'service'=>'includes/class-wca-service.php',
 'outbox'=>'includes/class-wca-outbox.php',
 'future'=>'includes/class-wca-future24.php',
 'query'=>'includes/class-wca-query-api.php',
 'builder'=>'tools/build-candidate.php',
 'verifier'=>'tools/verify-candidate.php',
 'file14test'=>'tests/t27-r1-file14-owner-outcome-regressions.php'
);
$src=array();
foreach($paths as $key=>$path){$data=@file_get_contents($root.'/'.$path);if(!is_string($data)){fwrite(STDERR,"T33 missing source: {$key}\n");exit(1);} $src[$key]=$data;}
$checks=array(
 '1.2.16 runtime identity'=>strpos($src['plugin'],'Version: 1.2.16')!==false && strpos($src['contracts'],"RUNTIME_VERSION                 = '1.2.16'")!==false,
 'File14 postcommit outcome preserved'=>strpos($src['service'],'wca_appointment_requested_committed_v1')!==false && strpos($src['file14test'],'wca_appointment_requested_committed_v1')!==false,
 'Canonical File00 subject'=>strpos($src['auth'],'SMC_CF01_Contract::ensure_subject_uuid')!==false && strpos($src['auth'],"get_user_meta( \$user_id, '_smc_subject_uuid'")===false,
 'File09 1.3 bounded authority'=>strpos($src['doctor'],"FILE_09_MAX_VERSION = '1.4.0'")!==false && strpos($src['doctor'],'renewal_due')!==false,
 'File00 1.2.3 membership'=>strpos($src['doctor'],"FILE_00_CONTRACT    = '1.2.3'")!==false,
 'CF01 1.1.0 live interface'=>strpos($src['cf01'],"CONTRACT_VERSION    = '1.1.0'")!==false,
 'File19 producer/ingest, no direct mail'=>strpos($src['outbox'],'sun_ingest_domain_event')!==false && strpos($src['outbox'],'wp_mail(')===false && strpos($src['outbox'],'sn_notify_users(')===false,
 'External owner receipt enforced'=>strpos($src['outbox'],'require_owner_receipt')!==false && strpos($src['outbox'],'true !== $result')!==false,
 'Opaque File17 and calendar identities'=>strpos($src['service'],'required_public_appointment_ref')!==false && strpos($src['service'],"'appointment-' . \$appointment_id")===false,
 'Query cursor validated'=>strpos($src['query'],'cursor_input')!==false,
 'FUT01/05/07 stored scope fail-closed'=>strpos($src['future'],'future24_waitlist_filter_corrupt')!==false && strpos($src['future'],'wca_group_session_payload_corrupt')!==false && strpos($src['future'],'future24_active_buffer_policy_corrupt')!==false,
 'FUT13 nested blocking rules'=>strpos($src['future'],'verified_prerequisite_rules')!==false && strpos($src['future'],'wca_prerequisite_policy_corrupt')!==false,
 'FUT13 keyset and questionnaire'=>strpos($src['future'],'wca_future24_policy_cursor_corrupt')!==false && strpos($src['future'],'wca_questionnaire_policy_corrupt')!==false,
 'Packaging/independent CF01 parity'=>strpos($src['builder'],'CF-01 care-context runtime/canonical contract version mismatch.')!==false && strpos($src['verifier'],'Manifest/CF-01 care-context runtime contract parity failed.')!==false
);
$n=0;
foreach($checks as $label=>$ok){if(!$ok){fwrite(STDERR,"T33 FAIL: {$label}\n");exit(1);} ++$n;}
echo "T33 current-main cross-file parity regressions: PASS {$n}/{$n}\n";
