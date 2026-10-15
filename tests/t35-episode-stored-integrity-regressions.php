<?php
/** T35: native FUT23 corrupted stored episode must fail BEFORE archive/unlink DB mutation. */
require __DIR__ . '/bootstrap.php';
if (!defined('ARRAY_A')) { define('ARRAY_A', 'ARRAY_A'); }
function sanitize_text_field($s){return trim((string)$s);}
function get_current_user_id(){return 77;}
function get_posts($args){return array(99);}
function wp_json_encode($data,$flags=0){return json_encode($data,$flags);}
final class SWC_Helpers {
 const TYPE='wca_appointment';
 public static function meta($id,$key,$default=''){
  return 'public_ref'===$key?'eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee':$default;
 }
}
final class WCA_Service { public static function strict_id($value,$allow_zero=false){return is_int($value)?$value:null;} }
final class WCA_Authorization { public static function can_view_appointment($id,$actor){return true;} }
final class WCA_Repository {
 public static function transaction($callback,$name=''){return $callback();}
 public static function now(){return '2026-10-14 12:00:00';}
 public static function append_event($a,$b,$c,$d,$e,$f){return true;}
}
final class T35EpisodeDB {
 public $prefix='wp_',$last_error='',$updates=0,$row;
 public function prepare($sql,...$args){return $sql;}
 public function get_row($sql,$type=ARRAY_A){return $this->row;}
 public function update($table,$data,$where,$formats=null,$where_formats=null){$this->updates++;return false;}
}
$GLOBALS['wpdb']=new T35EpisodeDB();
require dirname(__DIR__) . '/includes/class-wca-future24.php';
$ref='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$linked='eeeeeeee-eeee-4eee-8eee-eeeeeeeeeeee';
$record=array('id'=>44,'public_ref'=>$ref,'appointment_id'=>99,'version'=>1,'status'=>'episode_open','expires_at'=>null);
$valid=array('appointment_refs'=>array($linked),'clinical_narrative_stored'=>false,'public_timeline'=>false,'scope_consistency'=>'same_patient_doctor_clinic','unlink_supported'=>true,'archive_supported'=>true);
$invoke=function($raw,$action='archive')use($record,$ref,$linked){
 $GLOBALS['wpdb']->updates=0;
 $GLOBALS['wpdb']->row=array_merge($record,array('payload_json'=>$raw));
 if('archive'===$action){return WCA_Future24::archive_episode($ref,array('expected_version'=>1),77);}
 return WCA_Future24::unlink_episode_appointment($ref,array('expected_version'=>1,'appointment_ref'=>$linked),77);
};
$cases=array('{broken','[]','{}','{"appointment_refs":[]}','{"appointment_refs":"oops"}');
foreach($cases as $case){
 $got=$invoke($case);
 wca_test_assert(is_wp_error($got)&&'wca_episode_payload_corrupt'===$got->code,'Broken stored episode fails closed');
 wca_test_assert(0===$GLOBALS['wpdb']->updates,'Corrupted episode performs no database mutation');
}
foreach(array(
 array_merge($valid,array('appointment_refs'=>array('native-id-99'))),
 array_merge($valid,array('appointment_refs'=>array($linked,$linked))),
 array_merge($valid,array('clinical_narrative_stored'=>true)),
 array_merge($valid,array('public_timeline'=>true)),
 array_merge($valid,array('scope_consistency'=>'cross_patient')),
) as $bad) {
 $got=$invoke(json_encode($bad));
 wca_test_assert(is_wp_error($got)&&'wca_episode_payload_corrupt'===$got->code,'Invalid link or privacy scope denied');
 wca_test_assert(0===$GLOBALS['wpdb']->updates,'Invalid scope cannot be archived');
}
$good=$invoke(json_encode($valid));
wca_test_assert(is_wp_error($good)&&'wca_episode_update_failed'===$good->code&&1===$GLOBALS['wpdb']->updates,'Valid original episode may proceed to protected database write');
$good=$invoke(json_encode($valid),'unlink');
wca_test_assert(is_wp_error($good)&&'wca_episode_update_failed'===$good->code&&1===$GLOBALS['wpdb']->updates,'Valid authorized unlink preserves transaction flow');
echo "T35 FUT23 episode stored-integrity regression: PASS\n";