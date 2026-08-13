<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t15h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
t15h('R1 appointment conflicts fail closed','includes/class-swc-helpers.php',"null === \$rows || '' !== (string) \$wpdb->last_error");
t15h('R1 rate counter readback fails closed','includes/class-swc-helpers.php',"null === \$hits_raw || '' !== (string) \$wpdb->last_error");
t15h('R1 active hold read fails closed','includes/class-wca-service.php',"return (bool) \$hold_id;");
t15h('R1 slot hold read failure explicit','includes/class-wca-repository.php','wca_slot_hold_read_failed');
t15h('R1 slot conflict query failure explicit','includes/class-wca-repository.php','wca_slot_conflict_query_failed');
t15h('R1 stale request replay read failure explicit','includes/class-wca-appointment-command.php','wca_idempotency_read_failed');
t15h('R1 consent read failure explicit','includes/class-wca-appointment-command.php','wca_consent_read_failed');
t15h('R1 waitlist offer read failure explicit','includes/class-wca-future24.php','wca_waitlist_offer_read_failed');
t15h('R1 waitlist dedupe read failure explicit','includes/class-wca-future24.php','wca_waitlist_dedupe_read_failed');
t15h('R1 windows dedupe read failure explicit','includes/class-wca-future24.php','wca_windows_dedupe_read_failed');
t15h('R1 resource capacity read failure explicit','includes/class-wca-future24.php','wca_resource_capacity_read_failed');
t15h('R1 group capacity read failure explicit','includes/class-wca-future24.php','wca_group_capacity_read_failed');
t15h('R1 group leave read failure explicit','includes/class-wca-future24.php','wca_group_leave_read_failed');
t15h('R1 queue position read failure explicit','includes/class-wca-future24.php','wca_queue_position_read_failed');
t15h('R1 support participant read failure explicit','includes/class-wca-future24.php','wca_support_participant_read_failed');
t15h('R1 virtual room read failure explicit','includes/class-wca-future24.php','wca_virtual_room_read_failed');
t15h('R1 external busy read fails closed','includes/class-wca-future24.php',"return (bool) \$busy;");
if($fail){fwrite(STDERR,"T15 regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo 'T15 regression assertions passed: '.$pass.'/'.$pass."\n";
