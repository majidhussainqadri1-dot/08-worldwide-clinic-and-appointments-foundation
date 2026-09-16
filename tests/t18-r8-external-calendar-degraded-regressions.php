<?php
$root=dirname(__DIR__);
$f=file_get_contents($root.'/includes/class-wca-future24.php');
$d=chr(36);
$checks=array(
 'SMART find propagates external-calendar read failure'=>strpos($f,'if(is_wp_error($external)){return $external;}')!==false,
 'external busy query clears stale DB error'=>strpos($f,$d."wpdb->last_error = '';")!==false && strpos($f,$d.'busy='.$d.'wpdb->get_var')!==false,
 'lossy conflict helper removed'=>strpos($f,'external_busy_conflict_ref')===false,
 'canonical external conflict remains fail closed'=>strpos($f,'wca_external_busy_read_failed')!==false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R8 FAIL: {$n}
");exit(1);}}
echo "T18 R8 external-calendar degradation regressions: PASS
";
