<?php
$root=dirname(__DIR__);$readme=file_get_contents($root.'/README.md');$status=file_get_contents($root.'/STATUS.md');$release=file_get_contents($root.'/docs/RELEASE-STATUS-1.0.0.md');$change=file_get_contents($root.'/CHANGELOG.md');$package=file_get_contents($root.'/readme.txt');$contracts=file_get_contents($root.'/includes/class-wca-contracts.php');$workflow=file_get_contents($root.'/.github/workflows/file08-complete-quality.yml');$runner=file_get_contents(__DIR__.'/run-all.php');$ledger=file_get_contents($root.'/docs/T25-R10-FROZEN-LEDGER.md');
foreach(array($readme,$status,$release,$change,$package,$contracts,$workflow,$runner,$ledger) as $s){if(!is_string($s)){fwrite(STDERR,"Historical T25 R10 source read failed\n");exit(1);}}
$checks=array(
'historical T25 R10 ledger retained'=>false!==strpos($ledger,'R10-D1')&&false!==strpos($ledger,'R10-D2')&&false!==strpos($ledger,'No R10 correction was started while the review remained open'),
'T25 no longer current in README'=>false===strpos($readme,'Current review cycle: **T25 closure cycle'),
'T25 no longer current in STATUS'=>false===strpos($status,'Current review discipline: **T25 closure cycle'),
'T25 no longer current in release status'=>false===strpos($release,'Current review cycle: **T25 closure cycle**'),
'T25 no longer current in changelog'=>false===strpos($change,'**T25 closure cycle is the current repository review sequence.**'),
'T25 no longer current in packaged readme'=>false===strpos($package,'current repository review sequence is **T25**'),
'historical T25 result remains preserved'=>false!==strpos($status,'## Historical T25 result')&&false!==strpos($change,'Historical T25 R1–R10 is closed'),
'governing booking route documented'=>false!==strpos($readme,'/appointments/book/{doctor_or_clinic}')&&false!==strpos($contracts,"'pattern' => '/appointments/book/{doctor_or_clinic}'"),
'old practitioner-only route documentation retired'=>false===strpos($readme,'/appointments/book/{opaque_practitioner_ref}'),
'canonical workflow exact-head package gate retained'=>false!==strpos($workflow,"php: ['7.4', '8.3']")&&false!==strpos($workflow,'Build twice')&&false!==strpos($workflow,'Independent verification'),
'live truth remains unclaimed'=>false!==strpos($status,'Live-Deployed | **Unverified / not claimed.**')&&false!==strpos($release,'Production release/deployment: **NO / UNVERIFIED**'),
'historical T25 R10 regression remains bound'=>false!==strpos($runner,"'t25-r10-release-closure-regressions.php'"));
$bad=array();foreach($checks as $n=>$ok){if(!$ok)$bad[]=$n;}if($bad){fwrite(STDERR,"Historical T25 R10 release closure regressions failed:\n- ".implode("\n- ",$bad)."\n");exit(1);}echo 'Historical T25 R10 release closure regressions: PASS '.count($checks).'/'.count($checks)."\n";
