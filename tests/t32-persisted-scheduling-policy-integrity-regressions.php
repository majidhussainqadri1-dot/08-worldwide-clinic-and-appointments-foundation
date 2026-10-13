<?php
/** T32: execute actual Future24 policy/group code with adversarial persisted records. */
if ( ! defined('ABSPATH') ) { define('ABSPATH', __DIR__ . '/'); }
if ( ! defined('ARRAY_A') ) { define('ARRAY_A', 'ARRAY_A'); }
function __( $s, $domain = '' ) { return $s; }
function absint( $x ) { return abs((int)$x); }
function sanitize_key( $x ) { return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$x)); }
function sanitize_text_field( $x ) { return trim((string)$x); }
function get_current_user_id() { return 23; }
class WP_Error { private $code; public function __construct($code, $message='', $data=array()) { $this->code=$code; } public function get_error_code() { return $this->code; } }
function is_wp_error($x) { return $x instanceof WP_Error; }
class T32DB {
    public $prefix='wp_'; public $last_error=''; public $policy_row=null; public $session_row=null; public $calls=0;
    public function prepare($query, ...$args) { return (string)$query; }
    public function get_row($sql, $type=ARRAY_A) {
        $this->calls++;
        return strpos($sql,"feature_id='F08-FUT-07'")!==false ? $this->policy_row : $this->session_row;
    }
}
$GLOBALS['wpdb']=new T32DB();
class WCA_Repository { public static function get_clinic($ref,$public_only=false){return array('id'=>7,'status'=>'active');} }
class WCA_Authorization {
    public static function claims($id){return array('approved'=>true);}
    public static function guardian_context($patient,$guardian,$actor){return true;}
    public static function subject_uuid($id){return '11111111-2222-4333-8444-555555555555';}
}
class WCA_Observability { public static $errors=array(); public static function log($level,$code,$details=array()){self::$errors[]=$code;} }
require dirname(__DIR__) . '/includes/class-wca-future24.php';
function t32_check($condition,$name) {
    if(!$condition) {fwrite(STDERR,"T32 FAIL: $name\n"); exit(1);}
    echo "T32 PASS: $name\n";
}
$method=new ReflectionMethod('WCA_Future24','slot_allowed_by_policy'); $method->setAccessible(true);
$slot=array('clinic_ref'=>'clinic-7');
$GLOBALS['wpdb']->policy_row=null;
t32_check(true===$method->invoke(null,$slot),'Missing optional policy keeps base scheduling available.');
$GLOBALS['wpdb']->policy_row=array('payload_json'=>'{truncated');
t32_check(false===$method->invoke(null,$slot),'Corrupt active buffer policy must block slot.');
$GLOBALS['wpdb']->policy_row=array('payload_json'=>'[]');
t32_check(false===$method->invoke(null,$slot),'JSON list cannot bypass configured buffer.');
$GLOBALS['wpdb']->policy_row=array('payload_json'=>'{}');
t32_check(false===$method->invoke(null,$slot),'Missing persisted buffer fields must fail closed.');
$GLOBALS['wpdb']->policy_row=array('payload_json'=>json_encode(array('enforcement'=>'server_slot_projection_and_hold_guard','buffer_before'=>0,'buffer_after'=>0,'travel_gap_minutes'=>0,'max_continuous_consultations'=>0)));
t32_check(true===$method->invoke(null,$slot),'Valid explicit zero-buffer policy remains available.');
$GLOBALS['wpdb']->policy_row=array('payload_json'=>json_encode(array('enforcement'=>'server_slot_projection_and_hold_guard','buffer_before'=>-1,'buffer_after'=>0,'travel_gap_minutes'=>0,'max_continuous_consultations'=>0)));
t32_check(false===$method->invoke(null,$slot),'Invalid negative buffer is not silently clamped.');
$GLOBALS['wpdb']->session_row=array('public_ref'=>'aabbccdd-aabb-4abb-8abb-aabbccddeeff','feature_id'=>'F08-FUT-05','status'=>'group_open','clinic_id'=>7,'expires_at'=>null,'payload_json'=>'{broken');
$before=$GLOBALS['wpdb']->calls;
$bad=WCA_Future24::join_group_session('aabbccdd-aabb-4abb-8abb-aabbccddeeff',array(),23);
t32_check(is_wp_error($bad) && 'wca_group_session_payload_corrupt'===$bad->get_error_code(),'Malformed group service/privacy scope is rejected before joining.');
t32_check($GLOBALS['wpdb']->calls===$before+1,'Corrupt group record stops before membership storage.');
$source=file_get_contents(dirname(__DIR__).'/includes/class-wca-future24.php');
t32_check(strpos($source,'future24_waitlist_filter_corrupt')!==false && strpos($source,'! is_object( $wait_root )')!==false,'Malformed waitlist filter cannot broaden into an unrestricted offer.');
echo "T32 persisted operational policy/state regressions: PASS\n";
