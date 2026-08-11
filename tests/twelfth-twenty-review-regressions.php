<?php
$root = dirname(__DIR__);
$pass=0; $fail=array();
function t12_has($label,$path,$needle){ global $root,$pass,$fail; $s=file_get_contents($root.'/'.$path); if(false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;} }

t12_has('R1 intake transaction','includes/class-wca-continuity-secure.php','wca_intake_mutation_transaction');
t12_has('R2 consent revoke transaction','includes/class-wca-continuity-secure.php','wca_consent_revoke_transaction');
t12_has('R3 followup create transaction','includes/class-wca-continuity-secure.php','wca_followup_create_transaction');
t12_has('R4 followup complete transaction','includes/class-wca-continuity-secure.php','wca_followup_complete_transaction');
t12_has('R5 reminder transaction','includes/class-wca-continuity-secure.php','wca_followup_reminder_transaction');
t12_has('R6 waitlist offer transaction','includes/class-wca-future24.php','wca_waitlist_offer_transaction');
t12_has('R7 group leave transaction','includes/class-wca-future24.php','wca_group_leave_transaction');
t12_has('R8 group cancel transaction','includes/class-wca-future24.php','wca_group_cancel_transaction');
t12_has('R9 participant add transaction','includes/class-wca-future24.php','wca_support_add_transaction');
t12_has('R10 participant revoke transaction','includes/class-wca-future24.php','wca_support_revoke_transaction');
t12_has('R11 virtual room transaction','includes/class-wca-future24.php','wca_virtual_room_transaction');
t12_has('R12 protected mutation rate limits','includes/class-wca-rest.php',"'clinic_review', 20, HOUR_IN_SECONDS");
t12_has('R13 sensitive read rate limits','includes/class-wca-rest.php',"'appointment_read', 120, 60");
t12_has('R14 idempotency release CAS','includes/class-wca-repository.php','return 1 === (int) $deleted;');
t12_has('R15 cursor persistence fail closed','includes/class-wca-rest.php','wca_cursor_store_failed');
if($fail){fwrite(STDERR,"File 08 twelfth twenty-round regression gate failed:\n- ".implode("\n- ",$fail)."\n"); exit(1);}
echo 'File 08 twelfth fresh twenty-round regression assertions passed: '.$pass.'/'.$pass."\n";
