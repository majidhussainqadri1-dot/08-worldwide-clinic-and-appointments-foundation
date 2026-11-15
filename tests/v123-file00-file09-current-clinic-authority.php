<?php
/**
 * V12.3 P05 / File08 owner: current File00 1.2.3 + File09 1.3.0
 * use-time authority regression, using authentic current contract field names.
 *
 * Mock provider values are synthetic, never site/user data. This file checks
 * the actual SWC_Doctor_Authority class, not an abstract symbol scan.
 */
error_reporting(E_ALL);
define('ABSPATH', __DIR__.'/');
define('SMC_VERSION','1.2.44');
define('SMC_CONTRACT_VERSION','1.2.3');
define('GDO_VERSION','1.3.0');
function absint($value){return abs((int)$value);}
function sanitize_key($value){return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$value));}
function get_userdata($id){return absint($id)===7 ? (object)array('ID'=>7):false;}
function smc_is_founder($id){return !empty($GLOBALS['f08_founder']);}
function apply_filters($name,$value){return $value;}
class SMC_Contracts {
 public static function assertions($id) {
  return array(
   'contract_version'=>$GLOBALS['f08_file00_contract'] ?? SMC_CONTRACT_VERSION,
   'user_id'=>absint($id),
   'institutional_account'=>!empty($GLOBALS['f08_institutional']),
   'account_class'=>!empty($GLOBALS['f08_founder'])?'founder':'individual',
   'membership_type'=>'doctor',
   'approved_membership_types'=>array('doctor'),
   'status'=>!empty($GLOBALS['f08_suspended'])?'suspended':'approved',
   'approved'=>true,'eligible'=>true,
   'suspended'=>!empty($GLOBALS['f08_suspended']),
   'professional_verified'=>true,'can_practice'=>!empty($GLOBALS['f08_can_practice']),
  );
 }
}
class GDO_Integration_Contracts {
 const VERSION='1.1.0';
 public static $available=true;
 public static $claims=array();
 public static function projection($id,$consumer) {
  if(!self::$available)return new WP_Error('unavailable');
  $base=array(
   'contract'=>'gdo.file08.clinic-eligibility','version'=>'1.1.0',
   'consumer'=>$consumer,'source_of_truth'=>'file09',
   'user_id'=>absint($id),'state'=>'verified',
   'verified'=>true,'eligible'=>true,'limited'=>false,
   'authorization_rechecked'=>true,'claim_version'=>2,
   'claim_status'=>'accepted',
   'verified_until'=>gmdate('Y-m-d',time()+86400*60),
   'clinical_authorization'=>false,'evidence_exposed'=>false,
  );
  return array_merge($base,self::$claims);
 }
}
class WP_Error {public function __construct($code=''){}}
function is_wp_error($x){return $x instanceof WP_Error;}
class SPD_Helpers {
 public static function verification_status($id){return empty($GLOBALS['f08_profile_blocked'])?'verified':'revoked';}
}
$GLOBALS['f08_can_practice']=true;
require dirname(__DIR__).'/includes/class-swc-doctor-authority.php';
function check($ok,$desc){
 if(!$ok){fwrite(STDERR,"FAIL: $desc\n");exit(1);}
 echo "PASS: $desc\n";
}
function reset_state() {
 $GLOBALS['f08_founder']=false;
 $GLOBALS['f08_institutional']=false;
 $GLOBALS['f08_can_practice']=true;
 $GLOBALS['f08_suspended']=false;
 $GLOBALS['f08_profile_blocked']=false;
 $GLOBALS['f08_file00_contract']=SMC_CONTRACT_VERSION;
 GDO_Integration_Contracts::$available=true;
 GDO_Integration_Contracts::$claims=array();
}
reset_state();
check(SWC_Doctor_Authority::is_eligible(7),
 'current File00 1.2.3 and File09 1.3.0 allow genuinely current doctor');
reset_state();$GLOBALS['f08_can_practice']=false;
check(!SWC_Doctor_Authority::is_eligible(7),
 'File00 can_practice=false cannot be overridden by File09');
reset_state();$GLOBALS['f08_suspended']=true;
check(!SWC_Doctor_Authority::is_eligible(7),
 'File00 suspended account cannot become clinic practitioner');
reset_state();GDO_Integration_Contracts::$claims=array('verified'=>false,'eligible'=>false,'state'=>'revoked');
check(!SWC_Doctor_Authority::is_eligible(7),
 'File09 revocation denies practice');
reset_state();GDO_Integration_Contracts::$claims=array('claim_status'=>'pending');
check(!SWC_Doctor_Authority::is_eligible(7),
 'unacknowledged File00 claim denies practice');
reset_state();GDO_Integration_Contracts::$claims=array('verified_until'=>'2020-01-01');
check(!SWC_Doctor_Authority::is_eligible(7),
 'expired doctor credential never grants clinic authority');
reset_state();GDO_Integration_Contracts::$claims=array('consumer'=>'file07');
check(!SWC_Doctor_Authority::is_eligible(7),
 'wrong File09 consumer context cannot become clinic authority');
reset_state();GDO_Integration_Contracts::$claims=array('authorization_rechecked'=>false);
check(!SWC_Doctor_Authority::is_eligible(7),
 'stale File09 projection fails closed');
reset_state();GDO_Integration_Contracts::$claims=array('version'=>'0.9.0');
check(!SWC_Doctor_Authority::is_eligible(7),
 'obsolete File09 owner contract fails closed');
reset_state();GDO_Integration_Contracts::$available=false;
check(!SWC_Doctor_Authority::is_eligible(7),
 'unavailable File09 owner fails closed');
reset_state();$GLOBALS['f08_file00_contract']='1.1.2';
check(!SWC_Doctor_Authority::is_eligible(7),
 'old unverified File00 assertions contract is not silently accepted');
reset_state();$GLOBALS['f08_profile_blocked']=true;
check(!SWC_Doctor_Authority::is_eligible(7),
 'File03 current revoked status blocks practitioner');
reset_state();$GLOBALS['f08_founder']=true;$GLOBALS['f08_institutional']=true;
check(SWC_Doctor_Authority::is_eligible(7),
 'separately authorized institutional founder path remains available');
reset_state();$GLOBALS['f08_founder']=true;
check(!SWC_Doctor_Authority::is_eligible(7),
 'unverified individual cannot impersonate institutional founder');
echo "File08 V12.3 File00+File09 authority source contract: 14 PASS, 0 FAIL\n";
