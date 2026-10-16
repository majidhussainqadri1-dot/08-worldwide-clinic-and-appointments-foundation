<?php
/**
 * T39: execute actual add_participant() with current identity, appointment
 * and an existing supported role, then require explicit invalid role input
 * to fail before any scheduling DB lock, mutation or File17 event.
 * PHP fixtures: NOT installed WordPress/MySQL.
 */
require __DIR__ . '/bootstrap.php';
if(!defined('ARRAY_A')){define('ARRAY_A','ARRAY_A');}
if(!function_exists('get_current_user_id')){function get_current_user_id(){return 77;}}
if(!function_exists('get_posts')){function get_posts($arg){return array(51);}}
if(!function_exists('sanitize_text_field')){function sanitize_text_field($v){return trim(strip_tags((string)$v));}}
if(!function_exists('sanitize_key')){function sanitize_key($v){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',(string)$v));}}
function smc_get_user_id_by_subject_uuid($uuid){return 88;}
final class WCA_Authorization{
    public static function can_view_appointment($id,$actor){return true;}
    public static function appointment_actor($id,$actor){return 'patient';}
    public static function claims($user){return array('approved'=>true,'suspended'=>false);}
    public static function subject_uuid($id){return $id===88?'00000000-0000-4000-8000-000000000088':'';}
}
final class SWC_Helpers{
    const TYPE='wca_appointment';
    public static function status($id){return 'confirmed';}
    public static function meta($id,$k,$d=''){return $k==='clinic_id'?3:$d;}
}
final class WCA_Repository{public static function now(){return '2026-10-16 00:00:00';}}
final class T39_DB {
    public $prefix='wp_';public $last_error='';public $locks=0;public $lookups=0;
    public function prepare($sql){return $sql;}
    public function get_var($sql){$this->locks++;return 1;}
    public function get_row($sql,$shape=null){
        $this->lookups++;
        return array('id'=>1,'public_ref'=>'00000000-0000-4000-8000-000000000088',
            'feature_id'=>'F08-FUT-18','clinic_id'=>3,'appointment_id'=>51,
            'subject_user_id'=>88,'actor_user_id'=>77,'parent_ref'=>'',
            'status'=>'participant_active','starts_at'=>'','ends_at'=>'',
            'capacity'=>0,'version'=>1,'updated_at'=>'2026-10-16 00:00:00',
            'expires_at'=>'2026-10-20 00:00:00','created_at'=>'2026-10-16 00:00:00',
            'payload_json'=>json_encode(array('subject_uuid'=>'00000000-0000-4000-8000-000000000088',
                'role'=>'support','appointment_bound'=>true,'revocable'=>true,
                'clinical_write_authority'=>false)));
    }
}
$GLOBALS['wpdb']=new T39_DB();
require dirname(__DIR__).'/includes/class-wca-future24.php';
$ref='00000000-0000-4000-8000-000000000051';
$subject='00000000-0000-4000-8000-000000000088';
$n=0;
$test=static function($yes,$label)use(&$n){$n++;if(!$yes){fwrite(STDERR,"T39 FUT18 role FAIL ".$label."\n");exit(1);}};
foreach(array('doctor','admin','', 'prescriber') as $invalid){
    $before=$GLOBALS['wpdb']->locks;
    $result=WCA_Future24::add_participant($ref,array('subject_uuid'=>$subject,'role'=>$invalid),77);
    $test(is_wp_error($result) && $result->code==='wca_support_role_invalid',
        'explicit unsupported role must return controlled validation failure: '.$invalid);
    $test($GLOBALS['wpdb']->locks===$before,'invalid role must be rejected before DB lock');
}
$good=WCA_Future24::add_participant($ref,array('subject_uuid'=>$subject,'role'=>'support'),77);
$test(is_array($good)&&($good['status']??'')==='participant_active','approved role must still support true idempotent replay');
$default=WCA_Future24::add_participant($ref,array('subject_uuid'=>$subject),77);
$test(is_array($default)&&($default['status']??'')==='participant_active','truly omitted role keeps legacy support default');
echo "T39 FUT18 strict explicit role regression PASS {$n}/{$n} (PHP fixture only)\n";
