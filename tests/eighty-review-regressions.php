<?php
/** File 08 eighty-round corrective source regression gate. */
$root = dirname( __DIR__ ); $failures = array(); $checks = 0;
function r80src( $p ) { global $root,$failures; $f=$root.'/'.$p; if(!is_file($f)){ $failures[]='Missing '.$p; return ''; } $s=file_get_contents($f); return is_string($s)?$s:''; }
function r80has( $l,$s,$n ) { global $failures,$checks; $checks++; if(false===strpos($s,$n)){ $failures[]=$l.' missing: '.$n; } }
function r80lacks( $l,$s,$n ) { global $failures,$checks; $checks++; if(false!==strpos($s,$n)){ $failures[]=$l.' forbidden: '.$n; } }
function r80true( $l,$c ) { global $failures,$checks; $checks++; if(!$c){ $failures[]=$l; } }
$bootstrap=r80src('worldwide-clinic.php'); $contracts=r80src('includes/class-wca-contracts.php'); $auth=r80src('includes/class-wca-authorization.php'); $repo=r80src('includes/class-wca-repository.php'); $service=r80src('includes/class-wca-service.php'); $future=r80src('includes/class-wca-future24.php'); $privacy=r80src('includes/class-wca-privacy.php'); $readme=r80src('readme.txt');

// R78 release identity.
foreach(array('Version: 1.2.7',"WCA_VERSION', '1.2.7'") as $t){ r80has('bootstrap runtime',$bootstrap,$t); }
r80has('contract runtime',$contracts,"RUNTIME_VERSION                 = '1.2.7'"); r80has('readme runtime',$readme,'Stable tag: 1.2.7');

// R08/R13/R14 current guardian truth and least-privilege delegation.
foreach(array('guardian_context','can_staff_access_appointment','clinic_manage','delegation_allows_scope') as $t){ r80has('authorization',$auth,$t); }
r80has('current guardian state',$future,'current_guardian_state'); r80has('patient context',$future,'patient_context'); r80has('booking guardian revalidation',$service,'WCA_Authorization::guardian_context');

// R10-R33 Future24 scope/state/safety corrections.
foreach(array('service_for_clinic','branch_for_clinic','create_resource','reserve_resource','create_group_session','join_group_session','save_questionnaire','readiness','save_prerequisites','create_disruption','add_participant','request_virtual_room','smart_find','save_external_busy','create_episode') as $t){ r80has('Future24 corrected path',$future,$t); }
foreach(array('wca_resource_appointment_scope','wca_group_window','wca_questionnaire_fields','count_only_evidence_is_not_sufficient','wca_disruption_window','wca_support_subject_ineligible','wca_virtual_room_consent','wca_smart_find_parameter','wca_external_busy_window','wca_episode_scope') as $t){ r80has('Future24 negative path',$future,$t); }
r80has('prerequisite evidence matching',$future,'if(isset($evidence[$r])){continue;}');
r80has('prerequisite provisional policy',$future,'provisional_missing_does_not_block');
r80has('group join keeps request body',$future,'rest_group_join( WP_REST_Request $r ){ $d=self::data($r);'); r80has('virtual room does not assume recording',$future,"'recording_assumed'=>false"); r80has('transport event recording false',$future,"'recording_allowed'=>false"); r80has('no patient scoring',$future,"'patient_scoring' => false");
foreach(array('fhir_projection','smart_find','save_external_busy','create_episode','request_virtual_room') as $t){ r80has('interoperability boundary',$future,$t); }
r80has('no automated diagnosis',$contracts,"'automated_diagnosis' => false"); r80has('no automated prescribing',$contracts,"'automated_prescribing' => false"); r80has('zero commission',$contracts,"'commission_percent' => 0"); r80has('no donor visibility advantage',$contracts,"'donation_visibility_link' => false");

// R43 idempotency ownership, stale lease, replay/release.
foreach(array('claimed_new','release_idempotency','2 * MINUTE_IN_SECONDS','idempotency_key') as $t){ r80has('repository idempotency',$repo,$t); }
r80has('appointment concurrent refusal',$service,'wca_idempotency_in_progress'); r80has('appointment claim ownership',$service,"empty( \$claim['claimed_new'] )"); r80has('appointment failure releases claim',$service,'WCA_Repository::release_idempotency'); r80has('Future24 mutation wrapper',$future,'private static function mutate'); r80has('Future24 concurrent refusal',$future,'wca_idempotency_in_progress'); r80has('Future24 request fingerprint',$future,'$fingerprint = array('); r80has('Future24 completion ledger',$future,'complete_idempotency');

// R44-R46 privacy graph, retention field, legal hold.
r80has('doctor erasure coverage',$privacy,"'_swc_doctor_id'"); r80has('Future24 erasure cursor',$privacy,"'_future24'"); r80has('metrics retention real column',$privacy,'metric_bucket < %s'); r80lacks('metrics stale column',$privacy,'bucket_at < %s'); r80has('Future24 legal hold',$privacy,'future24_legal_hold'); r80has('Future24 legal hold filter',$privacy,'wca_future24_legal_hold'); r80has('linked appointment hold inheritance',$privacy,"row['appointment_id']");

for($i=1;$i<=24;$i++){ r80has('Future24 capability '.$i,$future,sprintf('F08-FUT-%02d',$i)); }
r80true('Future24 manifest declares exactly 24 IDs',24===preg_match_all("/'F08-FUT-[0-9]{2}'\\s*=>/",$future));
$runtime=implode("\n",array($auth,$repo,$service,$future,$privacy,$bootstrap)); foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $t){ r80lacks('forbidden runtime primitive',$runtime,$t); }
// R79 repository/release hygiene: transient patch staging must never survive final source.
r80true('review80 staging fragments removed',!is_dir($root.'/.codex/review80'));
r80true('legacy forty staging fragments removed',!is_dir($root.'/.codex/forty'));
r80true('legacy forty-v102 staging fragments removed',!is_dir($root.'/.codex/forty-v102'));
r80true('legacy forty-v102 trigger removed',!is_file($root.'/.codex/forty-v102-trigger'));
r80true('one-shot correction workflow removed',!is_file($root.'/.github/workflows/apply-file08-forty-v102.yml'));
if($failures){ fwrite(STDERR,"File 08 eighty-round regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo "File 08 eighty-round corrective regression assertions passed: {$checks}/{$checks}.\n";
