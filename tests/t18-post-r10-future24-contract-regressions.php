<?php
$root=dirname(__DIR__);
$c=file_get_contents($root.'/includes/class-wca-contracts.php');
$f=file_get_contents($root.'/includes/class-wca-future24.php');
$s=file_get_contents($root.'/STATUS.md');
$checks=array(
 'canonical contract registry exposes Future24 1.1.0'=>strpos($c,"FUTURE24_CONTRACT_VERSION       = '1.1.0'")!==false,
 'Future24 implementation contract is 1.1.0'=>strpos($f,"CONTRACT_VERSION = '1.1.0'")!==false,
 'Future24 implementation schema is 1.1.0'=>strpos($f,"SCHEMA_VERSION   = '1.1.0'")!==false,
 'current status records Future24 contract 1.1.0'=>strpos($s,'Future24 amendment contract: **1.1.0**')!==false,
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"POST-R10 CONTRACT FAIL: {$name}\n");exit(1);}}
echo "Post-R10 Future24 contract-version regressions: PASS\n";
