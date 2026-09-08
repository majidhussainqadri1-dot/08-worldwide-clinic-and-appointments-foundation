<?php
$root=dirname(__DIR__);
$readme=file_get_contents($root.'/readme.txt');
$status=file_get_contents($root.'/STATUS.md');
$contracts=file_get_contents($root.'/includes/class-wca-contracts.php');
$future=file_get_contents($root.'/includes/class-wca-future24.php');
$current_status = substr($status,0,strpos($status,'## Historical evidence note'));
$current_branch = '';
if ( preg_match( '/Working review branch:\s*`([^`]+)`/', $status, $branch_match ) ) {
 $current_branch = $branch_match[1];
}
$historical_note = '';
if ( preg_match( '/## Historical evidence note\s*(.+)$/s', $status, $historical_match ) ) {
 $historical_note = $historical_match[1];
}
$historical_t18_covered = strpos($historical_note,'T18')!==false
 || strpos($historical_note,'T13–T19')!==false
 || strpos($historical_note,'T13-T19')!==false
 || preg_match('/T(?:1[0-8]|[0-9])(?:\x{2013}|-)+T(?:1[8-9]|[2-9][0-9])/u', $historical_note)===1;
$checks=array(
 'runtime contract remains 1.2.15'=>strpos($contracts,"RUNTIME_VERSION                 = '1.2.15'")!==false,
 'core runtime schema is 3.4.0'=>strpos($contracts,"SCHEMA_VERSION                  = '3.4.0'")!==false,
 'future runtime schema is 1.1.0'=>strpos($future,"SCHEMA_VERSION   = '1.1.0'")!==false,
 'readme current release records core 3.4.0'=>strpos($readme,'current core schema is 3.4.0')!==false,
 'readme current release records Future24 1.1.0'=>strpos($readme,'Future24 schema/contract 1.1.0')!==false,
 'readme preserves historical T15 completion fact'=>strpos($readme,'All 20 fifteenth-cycle main reviews are complete')!==false,
 'status current branch advances beyond T18'=>$current_branch!=='' && $current_branch!=='review/file08-t18-ten-round-2026-09-06' && preg_match('/^review\/file08-t(?:19|[2-9][0-9]+)-/', $current_branch)===1,
 'status does not present T18 branch as current'=>strpos($current_status,'Working review branch: `review/file08-t18-ten-round-2026-09-06`')===false,
 'status current core schema is 3.4.0'=>strpos($status,'Core File 08 schema: **3.4.0**')!==false,
 'status current Future24 schema is 1.1.0'=>strpos($status,'Future24 additive operational schema/contract: **1.1.0**')!==false,
 'current evidence does not claim old exact-head package as current'=>strpos($current_status,'56a1ee4a59722e6574c4722e2c6bda791b15be39')===false,
 'current evidence does not claim old CI as current'=>strpos($current_status,'31741321738')===false,
 'current package evidence is exact-head-specific'=>strpos($status,'| Packaged | **Exact-head only**')!==false,
 'current automated QA evidence is exact-head-specific'=>strpos($status,'| Automated-QA Green | **Exact-head only**')!==false,
 'historical T18 evidence is explicitly non-current'=>(strpos($historical_note,'historical provenance only')!==false && $historical_t18_covered),
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"T18 R10 FAIL: {$name}\n");exit(1);}}
echo "T18 R10 release-truth regressions: PASS\n";
