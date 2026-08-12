<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t14has($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
function t14lacks($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false===strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' forbidden: '.$needle;}}
t14has('R1 owner-transaction privacy consent','includes/class-wca-service.php','$context_scopes = array( \'privacy_notice\' )');
t14has('R1 command consent fail closed','includes/class-wca-appointment-command.php','context_consent_sync_failed');
t14has('R2 strict IANA identifiers','includes/class-wca-service.php','timezone_identifiers_list()');
t14has('R3 strict DOB roundtrip','includes/class-wca-central-governance.php',"createFromFormat( '!Y-m-d'");
t14has('R4 age assertion conflict','includes/class-wca-central-governance.php','wca_age_claim_conflict');
t14has('R5 exact heatmap windows','includes/class-wca-future24.php','wca_heatmap_window');
t14has('R6 completed heatmap count','includes/class-wca-future24.php',"'completed' => 0");
t14has('R6 cancelled heatmap count','includes/class-wca-future24.php',"'cancelled' => 0");
t14has('R6 no-show heatmap count','includes/class-wca-future24.php',"'no_show' => 0");
t14has('R7 opaque clinic read helper','includes/class-wca-future24.php','clinic_id_from_public_ref');
t14has('R8 advisor reason','includes/class-wca-future24.php',"'utilization_ratio'");
t14has('R8 advisor provenance','includes/class-wca-future24.php',"'source_contract' => 'wca.capacity-heatmap'");
t14has('R9 bounded doctor query','includes/class-swc-helpers.php','\'number\'   => $limit');
t14lacks('R9 no unbounded doctor load','includes/class-swc-helpers.php',"'number'   => -1");
t14has('R10 bounded request doctors','includes/class-swc-frontend.php','requestable_doctor_ids( 100, 0 )');
t14has('R11 secure UUID fallback','includes/class-wca-repository.php','random_bytes( 16 )');
t14lacks('R11 no mt_rand UUID fallback','includes/class-wca-repository.php','mt_rand( 0, 0xffff )');
t14has('R12 secure worker identity','includes/class-wca-repository.php',"'worker-' . str_replace( '-', '', self::uuid() )");
t14has('R13 branch visibility rejection','includes/class-wca-repository.php','wca_branch_visibility');
t14has('R13 branch status rejection','includes/class-wca-repository.php','wca_branch_status');
t14has('R14 country rejection','includes/class-wca-repository.php','wca_branch_country');
t14has('R15 clinic status rejection','includes/class-wca-repository.php',"return new WP_Error( 'wca_clinic_status'");
t14has('R16 numeric REST retirement','includes/class-wca-ten-review-hardening.php','wca_legacy_numeric_rest_disabled');
t14has('R17 public numeric clinic rejection','includes/class-wca-rest.php','wca_public_numeric_id_disabled');
t14has('R18 plugin 1.2.14','worldwide-clinic.php','Version: 1.2.14');
t14has('R18 runtime 1.2.14','includes/class-wca-contracts.php',"RUNTIME_VERSION                 = '1.2.14'");
t14has('post-main heatmap timezone projection','includes/class-wca-future24.php',"'time_basis' => 'UTC day; configured capacity is projected from each rule local timezone with DST and effective-range handling'");
t14has('post-main heatmap effective range','includes/class-wca-future24.php','$effective_from && $local_date < $effective_from');
t14has('post-main low-volume suppression','includes/class-wca-future24.php','wca_heatmap_outcome_privacy_threshold');
t14has('post-main signed stateless cursor','includes/class-wca-rest.php','hash_hmac( \'sha256\', $payload, wp_salt( \'nonce\' ) )');
t14has('post-main cursor filter binding','includes/class-wca-rest.php','\'f\'=>');
t14lacks('post-main no transient cursor state','includes/class-wca-rest.php',"set_transient( 'wca_clinic_cursor_'");
if($fail){fwrite(STDERR,"File 08 fourteenth twenty-round regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo 'File 08 fourteenth fresh twenty-round regression assertions passed: '.$pass.'/'.$pass."\n";
