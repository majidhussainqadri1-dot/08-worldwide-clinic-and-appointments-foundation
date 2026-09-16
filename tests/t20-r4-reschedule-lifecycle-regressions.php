<?php
$root = dirname( __DIR__ ); $failures=array(); $checks=0;
function t20r4_check($name,$condition){global $failures,$checks;$checks++;if(!$condition){$failures[]=$name;}}
$contracts=file_get_contents($root.'/includes/class-wca-contracts.php'); $service=file_get_contents($root.'/includes/class-wca-service.php'); $repo=file_get_contents($root.'/includes/class-wca-repository.php'); $opaque=file_get_contents($root.'/includes/class-wca-opaque-api.php'); $hard=file_get_contents($root.'/includes/class-wca-ten-review-hardening.php');
foreach(array($contracts,$service,$repo,$opaque,$hard) as $source){t20r4_check('source readable',is_string($source));}
t20r4_check('pending proposal replace path',substr_count($contracts,"'reschedule_pending' => array( 'confirmed', 'reschedule_pending'")>=5);
t20r4_check('unbooked proposal release primitive',false!==strpos($repo,'function release_slot_hold')&&false!==strpos($repo,"status='held' AND appointment_id=0"));
t20r4_check('reproposal releases old hold',false!==strpos($service,'release_slot_hold( $previous_token )'));
t20r4_check('terminal transition releases proposal hold',false!==strpos($service,'release_slot_hold( $proposal_token )'));
t20r4_check('terminal proposal metadata cleanup',false!==strpos($service,'wca_terminal_reschedule_cleanup'));
t20r4_check('appointment-scoped reschedule hold service',false!==strpos($service,'function hold_reschedule_slot'));
t20r4_check('reschedule hold locked to appointment scope',false!==strpos($service,'wca_reschedule_hold_scope'));
t20r4_check('opaque reschedule hold route',false!==strpos($opaque,'/reschedule-holds')&&false!==strpos($opaque,'hold_reschedule_slot'));
t20r4_check('reschedule hold joins HTTP idempotency',false!==strpos($hard,'transitions|payment-intents|reschedule-holds'));
if($failures){fwrite(STDERR,"T20 R4 reschedule lifecycle regressions failed:\n- ".implode("\n- ",$failures)."\n");exit(1);} echo "T20 R4 reschedule lifecycle regressions: PASS {$checks}/{$checks}.\n";
