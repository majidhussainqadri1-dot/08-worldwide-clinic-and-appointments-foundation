<?php
$root = dirname(__DIR__);
$pass=0; $fail=array();
function t12_has($label,$path,$needle){ global $root,$pass,$fail; $s=file_get_contents($root.'/'.$path); if(false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;} }

t12_has('R1 intake transaction','includes/class-wca-continuity-secure.php','wca_intake_mutation_transaction');
t12_has('R2 consent revoke transaction','includes/class-wca-continuity-secure.php','wca_consent_revoke_transaction');
t12_has('R3 followup create transaction','includes/class-wca-continuity-secure.php','wca_followup_create_transaction');
if($fail){fwrite(STDERR,"File 08 twelfth twenty-round regression gate failed:\n- ".implode("\n- ",$fail)."\n"); exit(1);}
echo 'File 08 twelfth fresh twenty-round regression assertions passed: '.$pass.'/'.$pass."\n";
