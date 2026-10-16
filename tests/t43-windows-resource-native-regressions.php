<?php
/**
 * T43 FUT02 persisted flexible windows and FUT04 stale resource windows.
 * Executes real File08 private validator via Reflection and public reserve()
 * against pure PHP/DB fixture; not WordPress/MySQL staging.
 */
require __DIR__ . '/bootstrap.php';
if ( ! defined('MINUTE_IN_SECONDS') ) { define('MINUTE_IN_SECONDS',60); }
if ( ! defined('HOUR_IN_SECONDS') ) { define('HOUR_IN_SECONDS',3600); }
if ( ! defined('DAY_IN_SECONDS') ) { define('DAY_IN_SECONDS',86400); }
if ( ! defined('ARRAY_A') ) { define('ARRAY_A','ARRAY_A'); }
if ( ! function_exists('sanitize_text_field') ) {
    function sanitize_text_field($v) { return trim(strip_tags((string)$v)); }
}
if ( ! function_exists('get_current_user_id') ) {
    function get_current_user_id() { return 77; }
}
final class WCA_Repository {
    public static function get_clinic($id,$public=false) { return (int)$id===51?array('id'=>51,'owner_user_id'=>77):null; }
    public static function now(){return gmdate('Y-m-d H:i:s');}
}
final class WCA_Authorization {
    public static function can_manage_clinic($clinic,$actor=0) { return true; }
}
final class T43_DB {
    public $prefix='wp_'; public $last_error=''; public $read_count=0;public $lock_count=0;public $write_count=0;
    public function prepare($q){return $q;}
    public function get_row($q,$type=null) {
        $this->read_count++;
        return array('id'=>1,'clinic_id'=>51,'status'=>'resource_active','capacity'=>2,
            'payload_json'=>json_encode(array('resource_type'=>'room','branch_ref'=>'')));
    }
    public function get_var($q){if(strpos($q,'GET_LOCK')!==false || strpos($q,'RELEASE_LOCK')!==false){$this->lock_count++;return 1;}return null;}
    public function insert($table,$data){$this->write_count++;return 1;}
    public function update($table,$data,$where){$this->write_count++;return 1;}
}
$GLOBALS['wpdb']=new T43_DB();
require dirname(__DIR__).'/includes/class-wca-future24.php';
$count=0;
$assert=static function($ok,$label)use(&$count){$count++;if(!$ok){fwrite(STDERR,"T43 FAIL {$count}: {$label}\n");exit(1);}};
$ref=new ReflectionMethod('WCA_Future24','verified_windows_replay_scope');
$ref->setAccessible(true);
$windows=array('2026-10-19 08:00:00/2026-10-19 08:30:00','2026-10-19 09:00:00/2026-10-19 09:30:00');
$ctx=array('patient_user_id'=>77,'guardian_user_id'=>88);
$fingerprint=str_repeat('a',64);
$payload=array('windows'=>$windows,'service_ref'=>'service-7','timezone'=>'Asia/Karachi','guardian_user_id'=>88,'auto_book'=>false);
$stored=array('clinic_id'=>51,'subject_user_id'=>77,'status'=>'open',
 'parent_ref'=>$fingerprint,'expires_at'=>gmdate('Y-m-d H:i:s',time()+86400),'payload_json'=>json_encode($payload));
$check=static function($row,$context=null)use($ref,$ctx,$fingerprint,$windows){
    return $ref->invoke(null,$row,51,$context===null?$ctx:$context,$fingerprint,$windows,'service-7','Asia/Karachi');
};
$reject=static function($r,$msg)use($assert){
    $assert(is_wp_error($r)&&$r->code==='wca_windows_replay_scope_corrupt',$msg);
};
$assert($check($stored)===true,'valid normalized flexible windows replay');
$x=$stored;$x['payload_json']='{bad';$reject($check($x),'malformed JSON fails closed');
$x=$stored;$x['payload_json']='[]';$reject($check($x),'array JSON root rejected');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('auto_book'=>true)));$reject($check($x),'unsafe auto-book flag');
$x=$stored;$bad=$payload;unset($bad['auto_book']);$x['payload_json']=json_encode($bad);$reject($check($x),'missing no-auto-book flag');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('guardian_user_id'=>99)));$reject($check($x),'different historical guardian ID');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('timezone'=>'UTC')));$reject($check($x),'timezone changed');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('service_ref'=>'service-8')));$reject($check($x),'service changed');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('windows'=>array_reverse($windows))));$reject($check($x),'stored windows unsorted');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('windows'=>array($windows[0]))));$reject($check($x),'lost window');
$x=$stored;$x['payload_json']=json_encode(array_merge($payload,array('windows'=>array($windows[0],$windows[0]))));$reject($check($x),'duplicated stored window');
$x=$stored;$x['clinic_id']=52;$reject($check($x),'wrong clinic');
$x=$stored;$x['subject_user_id']=90;$reject($check($x),'wrong patient');
$x=$stored;$x['parent_ref']=str_repeat('b',64);$reject($check($x),'fingerprint mismatch');
$x=$stored;$x['expires_at']=gmdate('Y-m-d H:i:s',time()-60);$reject($check($x),'expired preference');
$x=$stored;$x['expires_at']=gmdate('Y-m-d H:i:s',time()+185*DAY_IN_SECONDS);$reject($check($x),'unbounded preference expiry');
$reject($check($stored,array('patient_user_id'=>77,'guardian_user_id'=>99)),'guardian revoked/reassigned');
$resource_ref='00000000-0000-4000-8000-000000000004';
$now=time();
$call=static function($from,$to)use($resource_ref) {
    return WCA_Future24::reserve_resource($resource_ref,array('start_utc'=>gmdate('Y-m-d H:i:s',$from),
        'end_utc'=>gmdate('Y-m-d H:i:s',$to)),77);
};
$stale=$call($now-7200,$now-3600);
$assert(is_wp_error($stale)&&$stale->code==='wca_resource_window_stale','entirely past reservation denied');
$assert($GLOBALS['wpdb']->lock_count===0&&$GLOBALS['wpdb']->write_count===0,'past reservation denied before locks/writes');
$oldStart=$call($now-3600,$now+3600);
$assert(is_wp_error($oldStart)&&$oldStart->code==='wca_resource_window_stale','old started booking denied');
$beyond=$call($now+181*DAY_IN_SECONDS,$now+181*DAY_IN_SECONDS+3600);
$assert(is_wp_error($beyond)&&$beyond->code==='wca_resource_window_stale','beyond 180-day resource horizon denied');
$assert($GLOBALS['wpdb']->lock_count===0&&$GLOBALS['wpdb']->write_count===0,'stale future reservation denied before locks/writes');
$valid=$call($now+3600,$now+7200);
$assert(!is_wp_error($valid)||$valid->code!=='wca_resource_window_stale',
  'legitimate future resource window must reach ordinary reservation path');
// Verify source order: FUT02 existing replay validated first; FUT04 stale window before lock.
$source=file_get_contents(dirname(__DIR__).'/includes/class-wca-future24.php');
$beg=strpos($source,'public static function save_windows(');$end=strpos($source,'private static function advance_months_anchored(',$beg);
if($end===false){$end=strpos($source,'/** Advance a monthly recurrence',$beg);}
$sec=substr($source,$beg,$end-$beg);
$existing=strpos($sec,'if ( $existing )');$guard=strpos($sec,'verified_windows_replay_scope( $existing');$success=strpos($sec,'return self::public_record( $existing );');
$assert($existing!==false&&$guard!==false&&$success!==false&&$existing<$guard&&$guard<$success,
  'FUT02 duplicate response after persisted scope verification');
$beg=strpos($source,'public static function reserve_resource(');$end=strpos($source,'/* FUT-05 */',$beg);
$sec=substr($source,$beg,$end-$beg);
$guard=strpos($sec,'wca_resource_window_stale');$lock=strpos($sec,"self::semantic_lock( 'resource'");
$assert($guard!==false&&$lock!==false&&$guard<$lock,'FUT04 stale clock guard before resource lock');
echo "T43 FUT02/FUT04 actual-method PHP fixtures PASS {$count}/{$count} (no real MySQL E2E)\n";
