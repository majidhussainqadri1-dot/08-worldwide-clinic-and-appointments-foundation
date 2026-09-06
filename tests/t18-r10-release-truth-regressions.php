<?php
$root=dirname(__DIR__);
$readme=file_get_contents($root.'/readme.txt');
$status=file_get_contents($root.'/STATUS.md');
$contracts=file_get_contents($root.'/includes/class-wca-contracts.php');
$future=file_get_contents($root.'/includes/class-wca-future24.php');
$checks=array(
 'runtime contract remains 1.2.15'=>strpos($contracts,"RUNTIME_VERSION                 = '1.2.15'")!==false,
 'core runtime schema is 3.4.0'=>strpos($contracts,"SCHEMA_VERSION                  = '3.4.0'")!==false,
 'future runtime schema is 1.1.0'=>strpos($future,"SCHEMA_VERSION   = '1.1.0'")!==false,
 'readme current release records core 3.4.0'=>strpos($readme,'current core schema is 3.4.0')!==false,
 'readme current release records Future24 1.1.0'=>strpos($readme,'Future24 schema/contract 1.1.0')!==false,
 'readme preserves historical T15 completion fact'=>strpos($readme,'All 20 fifteenth-cycle main reviews are complete')!==false,
 'status current branch is T18 review branch'=>strpos($status,'Working review branch: `review/file08-t18-ten-round-2026-09-06`')!==false,
 'status current core schema is 3.4.0'=>strpos($status,'Core File 08 schema: **3.4.0**')!==false,
 'status current Future24 schema is 1.1.0'=>strpos($status,'Future24 additive operational schema: **1.1.0**')!==false,
 'status current Future24 contract is 1.1.0'=>strpos($status,'Future24 amendment contract: **1.1.0**')!==false,
 'current evidence does not claim old exact-head package as current'=>strpos(substr($status,0,strpos($status,'## Mandatory staging / production gates')),'56a1ee4a59722e6574c4722e2c6bda791b15be39')===false,
 'current evidence does not claim old CI as current'=>strpos(substr($status,0,strpos($status,'## Mandatory staging / production gates')),'31741321738')===false,
 'current package evidence is pending'=>strpos($status,'| Packaged | **Pending for current T18 final corrected HEAD.**')!==false,
 'current automated QA evidence is pending'=>strpos($status,'| Automated-QA Green | **Pending for current T18 final corrected HEAD.**')!==false,
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"T18 R10 FAIL: {$name}\n");exit(1);}}
echo "T18 R10 release-truth regressions: PASS\n";
