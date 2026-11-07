<?php
/**
 * Fail-first: File08 must honor the current File09 adapter instead of the
 * deleted legacy GDO_Helpers class. No WordPress DB or production call.
 */
define('ABSPATH', __DIR__.'/');
class SPD_Helpers{}
class SDD_Helpers{}
class GDO_Membership_Adapter {
    public static $active=true;
    public static $auth=true;
    public static function available(){return self::$active;}
    public static function authentication_available(){return self::$auth;}
}
function smc_user_status($user_id){return 'approved';}
function smc_is_founder($user_id){return false;}
require dirname(__DIR__).'/includes/class-swc-activator.php';
function check_f08(bool $got,string $what){if(!$got){fwrite(STDERR,"FAIL: {$what}\n");exit(1);}}
check_f08(SWC_Activator::dependencies_ready(),'current File09 adapter should allow File08 dependent activation');
GDO_Membership_Adapter::$active=false;
check_f08(!SWC_Activator::dependencies_ready(),'unavailable File09 owner must fail closed');
GDO_Membership_Adapter::$active=true;
GDO_Membership_Adapter::$auth=false;
check_f08(!SWC_Activator::dependencies_ready(),'File02 professional authentication unavailable must fail closed');
echo "PASS File08 current File09/02 owner dependency and fail-closed state\n";
