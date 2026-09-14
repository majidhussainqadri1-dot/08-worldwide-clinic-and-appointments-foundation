<?php
$root=dirname(__DIR__);
$cmd=file_get_contents($root.'/includes/class-wca-appointment-command.php');
$guard=file_get_contents($root.'/includes/class-wca-plan-guard.php');
$legacy=file_get_contents($root.'/includes/class-swc-appointments.php');
$front=file_get_contents($root.'/includes/class-swc-frontend.php');
$checks=array(
 'command propagates hold WP_Error'=>strpos($cmd,'if ( is_wp_error( $hold ) ) { return $hold; }')!==false,
 'command consumes service read error'=>strpos($cmd,'$service_read_error = WCA_Repository::consume_read_error();')!==false,
 'plan guard centralizes read failure propagation'=>strpos($guard,'private static function repository_read')!==false && substr_count($guard,'self::repository_read(')>=6,
 'legacy appointment mutation hooks quarantined'=>strpos($legacy,"admin_post_swc_submit_appointment")===false && strpos($legacy,"admin_post_swc_doctor_update")===false && strpos($legacy,"admin_post_swc_patient_cancel")===false,
 'legacy appointment shortcodes no longer render mutation forms'=>substr_count($front,'legacy_governed_notice')>=3,
 'canonical availability compatibility remains'=>strpos($legacy,"admin_post_swc_save_availability")!==false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R7 FAIL: {$n}\n");exit(1);}}
echo "T18 R7 appointment lifecycle regressions: PASS\n";
