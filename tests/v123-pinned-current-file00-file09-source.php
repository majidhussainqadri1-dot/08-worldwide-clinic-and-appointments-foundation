<?php
/**
 * V12.3 P05: check authentic current File00 and File09 git source declarations,
 * not merely version values copied into a synthetic consumer fixture.
 *
 * GitHub CI must checkout EXACT_PINNED owner HEADs under .external/ first.
 * This is source parity, not installed WordPress, DB or live acceptance.
 */
error_reporting(E_ALL);
define('ABSPATH',__DIR__.'/');
function contract_check($ok,$name){
 if(!$ok){fwrite(STDERR,"FAIL: $name\n");exit(1);}
 echo "PASS: $name\n";
}
$root=dirname(__DIR__);
$smc=$root.'/.external/file00/source/sabri-membership-core/sabri-membership-core.php';
$gdo=$root.'/.external/file09/global-doctor-onboarding.php';
$integ=$root.'/.external/file09/includes/class-gdo-integration-contracts.php';
foreach(array($smc,$gdo,$integ) as $file){
 contract_check(is_file($file),'pinned owner source exists: '.basename($file));
}
function source_constant($content,$name){
 $name=preg_quote($name,'/');
 if(preg_match('/define\(\s*[\'"]'.$name.'[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)/',$content,$m)){
  return $m[1];
 }
 return null;
}
$smcText=file_get_contents($smc);
$gdoText=file_get_contents($gdo);
$intText=file_get_contents($integ);
contract_check(is_string($smcText)&&is_string($gdoText)&&is_string($intText),'owner source readable');
contract_check(source_constant($smcText,'SMC_VERSION')==='1.2.44','File00 pinned plugin runtime 1.2.44');
contract_check(source_constant($smcText,'SMC_CONTRACT_VERSION')==='1.2.3','File00 pinned assertions contract 1.2.3');
contract_check(source_constant($gdoText,'GDO_VERSION')==='1.3.0','File09 pinned plugin runtime 1.3.0');
contract_check(
 preg_match('/const\s+VERSION\s*=\s*[\'"]1\.1\.0[\'"]\s*;/',$intText)===1,
 'File09 owner publishes professional public contract 1.1.0');
contract_check(
 strpos($intText,"'file08' => self::FILE08")!==false,
 'File09 explicitly authorizes File08 projection consumer');
contract_check(
 strpos($intText,"'source_of_truth'")!==false
 && strpos($intText,"'authorization_rechecked'")!==false
 && strpos($intText,"'claim_status'")!==false
 && strpos($intText,"'verified_until'")!==false,
 'File09 owner emits mandatory current verification projection fields');
contract_check(
 strpos($smcText,"define( 'SMC_CONTRACT_VERSION', '1.2.3' )")!==false,
 'File00 runtime contract matches authoritative owner constant');
require_once $root.'/includes/class-swc-doctor-authority.php';
contract_check(SWC_Doctor_Authority::FILE_00_CONTRACT===source_constant($smcText,'SMC_CONTRACT_VERSION'),
 'File08 exact File00 assertions contract parity');
contract_check(
 version_compare(source_constant($gdoText,'GDO_VERSION'),SWC_Doctor_Authority::FILE_09_MIN_VERSION,'>=')
 && version_compare(source_constant($gdoText,'GDO_VERSION'),SWC_Doctor_Authority::FILE_09_MAX_VERSION,'<'),
 'File08 accepts pinned File09 runtime within approved range');
contract_check(
 SWC_Doctor_Authority::FILE_09_PUBLIC_CONTRACT==='1.1.0',
 'File08 requires exact current File09 public contract floor');
echo "PASS File08 V12.3 pinned File00+File09 source compatibility (not installed WP)\n";
