<?php
$root=dirname(__DIR__); $fail=array(); $checks=0;
function r2src($p){global $root,$fail;$s=file_get_contents($root.'/'.$p);if(!is_string($s)){$fail[]='missing '.$p;return '';}return $s;}
function r2has($l,$s,$n){global $fail,$checks;$checks++;if(false===strpos($s,$n)){$fail[]=$l;}}
$h=r2src('includes/class-swc-helpers.php'); $s=r2src('includes/class-wca-service.php');
r2has('audit actor default',$h,"'actor_id'       => get_current_user_id()");
r2has('audit explicit actor persistence',$h,"'actor_id'       => absint( \$args['actor_id'] )");
r2has('transition canonical actor audit',$s,"'actor_id' => \$actor_user_id, 'actor_role' => \$actor, 'old_status' => \$current");
if($fail){foreach($fail as $f){fwrite(STDERR,"FAIL: $f\n");}exit(1);} echo "T22 R2 actor provenance regressions passed ($checks checks).\n";
