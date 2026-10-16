<?php
/** T35: actual FUT04 reserve_resource rejects corrupted resource and cross-branch use before locking. */
require __DIR__ . '/bootstrap.php';
if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
if (!defined('HOUR_IN_SECONDS')) { define('HOUR_IN_SECONDS', 3600); }
if (!defined('DAY_IN_SECONDS')) { define('DAY_IN_SECONDS', 86400); }
if (!defined('MINUTE_IN_SECONDS')) { define('MINUTE_IN_SECONDS', 60); }
function sanitize_text_field($x){return trim((string)$x);}
function get_current_user_id(){return 77;}
function get_posts($args){return array(91);}
final class SWC_Helpers {
 const TYPE='wca_appointment';
 public static function meta($id,$key,$default=''){
  if('clinic_id'===$key){return 7;}
  if('branch_id'===$key){return $GLOBALS['t35_branch_id'];}
  return $default;
 }
}
final class WCA_Authorization {
 public static function can_manage_clinic($clinic,$actor){return true;}
 public static function can_view_appointment($id,$actor){return true;}
 public static function claims($actor){return new WP_Error('t35_stop_before_fixture_write');}
}
final class WCA_Repository {
 public static function get_clinic($id,$public=false){return array('id'=>7);}
 public static function get_branch($id){return array('id'=>$id,'clinic_id'=>7,'public_ref'=>$GLOBALS['t35_actual_branch']);}
 public static function clear_read_error(){}
 public static function consume_read_error(){return null;}
 public static function now(){return '2026-10-14 00:00:00';}
}
final class T35ResourceDB {
 public $prefix='wp_',$last_error='',$lock_calls=0,$resource;
 public function prepare($sql,...$values){return $sql;}
 public function get_row($sql,$type=ARRAY_A){return $this->resource;}
 public function get_var($sql){
  if(strpos($sql,'GET_LOCK')!==false){$this->lock_calls++;return 1;}
  if(strpos($sql,'RELEASE_LOCK')!==false){return 1;}
  return 0;
 }
}
$GLOBALS['wpdb']=new T35ResourceDB();
$GLOBALS['t35_branch_id']=0;
$GLOBALS['t35_actual_branch']='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
require dirname(__DIR__) . '/includes/class-wca-future24.php';
$resource_ref='cccccccc-cccc-4ccc-8ccc-cccccccccccc';
$base=array('clinic_id'=>7,'status'=>'resource_active','capacity'=>1);
$t35_start=time()+7*DAY_IN_SECONDS;
$input=array('start_utc'=>gmdate('Y-m-d H:i:s',$t35_start),'end_utc'=>gmdate('Y-m-d H:i:s',$t35_start+HOUR_IN_SECONDS));
$execute=function($payload,$appointment_ref='') use ($base,$resource_ref,$input){
 $GLOBALS['wpdb']->resource=array_merge($base,array('payload_json'=>$payload));
 $GLOBALS['wpdb']->lock_calls=0;
 $d=$input;
 if($appointment_ref){$d['appointment_ref']=$appointment_ref;}
 return WCA_Future24::reserve_resource($resource_ref,$d,77);
};
$validGlobal=json_encode(array('resource_type'=>'room','branch_ref'=>''));
$validBranch=json_encode(array('resource_type'=>'room','branch_ref'=>$GLOBALS['t35_actual_branch']));
foreach(array('{bad','[]','{}','{"resource_type":"laser","branch_ref":""}','{"resource_type":"room","branch_ref":[]}','{"resource_type":"room","branch_ref":"invalid"}') as $corrupt){
 $res=$execute($corrupt);
 wca_test_assert(is_wp_error($res) && 'wca_resource_payload_corrupt'===$res->code,'Resource malformed type/branch cannot be reserved');
 wca_test_assert($GLOBALS['wpdb']->lock_calls===0,'Corrupted resource rejected before lock/capacity query');
}
$res=$execute($validGlobal);
wca_test_assert(is_wp_error($res)&&'t35_stop_before_fixture_write'===$res->code && $GLOBALS['wpdb']->lock_calls===1,'Valid global resource retains protected reservation path');
$res=$execute($validBranch,'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
wca_test_assert(is_wp_error($res)&&'wca_resource_branch_scope'===$res->code && 0===$GLOBALS['wpdb']->lock_calls,'Branch resource requires appointment branch');
$GLOBALS['t35_branch_id']=5;
$GLOBALS['t35_actual_branch']='bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';
$res=$execute($validBranch,'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
wca_test_assert(is_wp_error($res)&&'wca_resource_branch_scope'===$res->code && 0===$GLOBALS['wpdb']->lock_calls,'Appointment from other branch denied');
$GLOBALS['t35_actual_branch']='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$res=$execute($validBranch,'dddddddd-dddd-4ddd-8ddd-dddddddddddd');
wca_test_assert(is_wp_error($res)&&'t35_stop_before_fixture_write'===$res->code && 1===$GLOBALS['wpdb']->lock_calls,'Same branch retains correct reservation path');
echo "T35 native FUT04 resource contract regressions: PASS\n";