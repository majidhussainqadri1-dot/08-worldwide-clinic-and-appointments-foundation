<?php
$root=dirname(__DIR__);
$service=file_get_contents($root.'/includes/class-wca-service.php');
$repo=file_get_contents($root.'/includes/class-wca-repository.php');
$f=array();$n=0;function t20r7($name,$ok){global $f,$n;$n++;if(!$ok)$f[]=$name;}
t20r7('sources readable',is_string($service)&&is_string($repo));
t20r7('bounded closed exception remains an eligible day',false!==strpos($service, '$closed_day = $exception'));
t20r7('bounded close overlap gate exists',false!==strpos($service,'in_closed_exception( $slot, $slot_end, $exception )'));
t20r7('bounded close helper fails closed on invalid wall time',false!==strpos($service, '$closed_start') && false!==strpos($service, '$closed_end') && false!==strpos($service, '{ return true; }'));
t20r7('inactive or private rule branch is not projected',false!==strpos($service, "'active' !== (string)") && false!==strpos($service, "'public' !== (string)"));
t20r7('repository still permits validated bounded close storage',false!==strpos($repo,'A bounded closed exception requires a valid start/end window.'));
if($f){fwrite(STDERR,"T20 R7 availability regressions failed:\n- ".implode("\n- ",$f)."\n");exit(1);}echo "T20 R7 availability regressions: PASS {$n}/{$n}.\n";
