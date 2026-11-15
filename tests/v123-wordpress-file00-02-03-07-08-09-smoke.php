<?php
/**
 * File08 V12.3: ephemeral real WordPress/MariaDB 6-module consumer smoke.
 * Use only in the named GitHub CI disposable WordPress installation.
 * Not Hostinger staging/live acceptance. No real user/evidence data.
 */
defined('ABSPATH') || exit;
if (!defined('WP_CLI') || !WP_CLI
 || wp_get_environment_type()!=='staging'
 || (string)get_option('siteurl')!=='http://file08-p05.local') {
 fwrite(STDERR,"FAIL: cross-owner test refuses non-ephemeral WP-CLI environment\n");
 exit(90);
}
function file08_p05_check($ok,$label) {
 if(!$ok){fwrite(STDERR,"FAIL: ".$label."\n");exit(1);}
 echo "PASS: ".$label."\n";
}
$required=array(
 'sabri-membership-core/sabri-membership-core.php',
 '02-sabri-authentication/sabri-authentication.php',
 'sabri-profiles-doctors/sabri-profiles-doctors.php',
 'global-doctor-onboarding-09/global-doctor-onboarding.php',
 'doctors-directory/doctors-directory.php',
 'worldwide-clinic/worldwide-clinic.php',
);
foreach($required as $plugin){
 file08_p05_check(is_plugin_active($plugin),'installed active plugin '.$plugin);
}
file08_p05_check(defined('SMC_VERSION') && SMC_VERSION==='1.2.44'
 && defined('SMC_CONTRACT_VERSION') && SMC_CONTRACT_VERSION==='1.2.3',
 'actual File00 runtime and assertions contract in WordPress');
file08_p05_check(defined('GDO_VERSION') && GDO_VERSION==='1.3.0'
 && defined('GDO_Integration_Contracts::VERSION')
 && constant('GDO_Integration_Contracts::VERSION')==='1.1.0',
 'actual File09 runtime and public professional contract in WordPress');
file08_p05_check(defined('SPD_VERSION') && defined('DDD_VERSION')
 && class_exists('SPD_Helpers') && class_exists('SDD_Helpers'),
 'real File03 + File07 loaded before File08 authorization');
file08_p05_check(class_exists('SWC_Doctor_Authority')
 && SWC_Activator::dependencies_ready(),
 'File08 clinic authority and installed dependency health');
$contract=SWC_Doctor_Authority::contract();
file08_p05_check(is_array($contract)
 && ($contract['file_00_contract']??'')==='1.2.3'
 && strpos((string)($contract['file_09_runtime_range']??''),'>=1.3.0')!==false
 && strpos((string)($contract['file_09_public_contract']??''),'>=1.1.0')!==false,
 'File08 clinic authority matches actual owner contracts');
file08_p05_check((int)get_option('gdo_schema_version',0)===6
 && (int)get_option('gdo_advanced_trust_schema',0)===2,
 'File09 two canonical DB migration markers present');
$usr=wp_create_user('file08-p05-unverified-ci','Ephemeral-CI-Only-Not-Real-2026','file08-unverified@example.test');
file08_p05_check(!is_wp_error($usr),'created synthetic unverified practitioner candidate');
file08_p05_check(SWC_Doctor_Authority::is_eligible($usr)===false,
 'new unapproved synthetic candidate cannot become clinic doctor');
file08_p05_check(GDO_Integration_Contracts::projection($usr,'file08')['eligible']===false,
 'real File09 public projection independently denies unverified doctor');
$health=GDO_Membership_Adapter::available();
file08_p05_check((bool)$health,'real File09 to File00 owner adapter is available');
echo "PASS File08 installed File00/02/03/07/08/09 WordPress+MariaDB negative authority smoke\n";
