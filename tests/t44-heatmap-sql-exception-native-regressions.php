<?php
/**
 * T44 native FUT08 heatmap source-integrity fixtures:
 * simulate actual class method behavior with DB errors, malformed override
 * and valid projected capacity. Not real WordPress/MySQL E2E or live evidence.
 */
require __DIR__ . '/bootstrap.php';
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
if ( ! function_exists( 'sanitize_text_field' ) ) { function sanitize_text_field( $v ) { return trim(strip_tags((string)$v)); } }
if ( ! function_exists( 'apply_filters' ) ) { function apply_filters($name,$value) { return $value; } }
if ( ! function_exists( 'get_current_user_id' ) ) { function get_current_user_id() { return 77; } }
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }
if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
final class T44_Query {
    public $posts=array();
    public function __construct($params){$this->posts=array();}
}
class_alias('T44_Query', 'WP_Query');
final class WCA_Schema {
    public static function tables(){return array('availability'=>'wp_wca_availability');}
}
// Use File08's canonical real timezone parser; incomplete mocks hide DST drift.
require dirname(__DIR__).'/includes/class-swc-helpers.php';
final class WCA_Repository {
    public static function get_clinic($id,$public=false){return array('id'=>77,'public_ref'=>'00000000-0000-4000-8000-000000000077');}
}
final class WCA_Authorization {
    public static function can_manage_clinic($clinic,$actor){return true;}
}
final class WCA_Service {
    public static function strict_int($v,$min,$max){
        $n=filter_var($v,FILTER_VALIDATE_INT);
        return false!==$n&&$n>=$min&&$n<=$max?$n:null;
    }
    public static function valid_timezone($tz){
        return is_string($tz)&&in_array($tz, timezone_identifiers_list(),true);
    }
    public static function valid_hhmm($v){return is_string($v)&&preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$v);}
    public static function valid_date($v){return is_string($v)&&preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/D',$v);}
}
final class T44_DB {
    public $prefix='wp_';
    public $last_error='';
    public $mode='zero';
    public $calls=0;
    public $rule=array();
    public function prepare($sql){return $sql;}
    public function get_results($sql,$as=ARRAY_A){
        $this->calls++;
        if('error_empty'===$this->mode){$this->last_error='simulated failed availability query';return array();}
        if('error_false'===$this->mode){$this->last_error='simulated malformed result';return false;}
        if('zero'===$this->mode){return array();}
        return array($this->rule);
    }
}
$GLOBALS['wpdb']=new T44_DB();
require dirname(__DIR__).'/includes/class-wca-future24.php';
$assertions=0;
$verify=static function($ok,$msg)use(&$assertions){
    $assertions++;
    if(!$ok){fwrite(STDERR,"T44 heatmap FAIL {$assertions}: {$msg}\n");exit(1);}
};
$db=$GLOBALS['wpdb'];
$db->mode='error_empty';
$bad=WCA_Future24::heatmap(77,7,77);
$verify(is_wp_error($bad)&&$bad->code==='wca_heatmap_rules_read_failed',
    'WordPress get_results() [] with SQL last_error must fail, not project 0 capacity');
$verify($db->calls===1,'availability query executed once on injected DB error');
$db->mode='error_false';
$bad=WCA_Future24::heatmap(77,7,77);
$verify(is_wp_error($bad)&&$bad->code==='wca_heatmap_rules_read_failed',
    'WordPress non-array result never returned as empty heatmap');
$db->mode='zero';
$healthy=WCA_Future24::heatmap(77,7,77);
$verify(is_array($healthy)&&count($healthy['days']??array())===7,
    'real successful empty availability rule list still returns seven days');
$verify(isset($healthy['days'][gmdate('Y-m-d')])&&$healthy['days'][gmdate('Y-m-d')]['configured_capacity']===0,
    'successful actual no-rules day has correctly zero configured capacity');
$weekday=strtolower(gmdate('l'));
$today=gmdate('Y-m-d');
$build=static function($capacity)use($weekday,$today){
    return array(
        'id'=>1,'capacity'=>2,'status'=>'active','timezone'=>'UTC',
        'rrule_json'=>json_encode(array('start'=>'09:00','end'=>'10:00','interval_minutes'=>60,'days'=>array($weekday))),
        'breaks_json'=>'[]','exceptions_json'=>json_encode(array(array('date'=>$today,'type'=>'capacity','capacity'=>$capacity))),
    );
};
$db->mode='one';
$db->rule=$build('invalid');
$bad=WCA_Future24::heatmap(77,7,77);
$verify(is_wp_error($bad)&&$bad->code==='wca_heatmap_exception_capacity_corrupt',
    'stored malformed matched-day capacity override cannot silently use baseline capacity');
$db->rule=$build(-1);
$bad=WCA_Future24::heatmap(77,7,77);
$verify(is_wp_error($bad)&&$bad->code==='wca_heatmap_exception_capacity_corrupt',
    'negative stored capacity cannot be silently ignored');
$db->rule=$build(51);
$bad=WCA_Future24::heatmap(77,7,77);
$verify(is_wp_error($bad)&&$bad->code==='wca_heatmap_exception_capacity_corrupt',
    'out-of-range stored capacity cannot silently fall back');
$db->rule=$build(3);
$valid=WCA_Future24::heatmap(77,7,77);
$verify(is_array($valid)&&($valid['days'][$today]['configured_capacity']??-1)===3,
    'valid matched-day exception correctly overrides configured capacity');
$db->rule=$build(0);
$valid=WCA_Future24::heatmap(77,7,77);
$verify(is_array($valid)&&($valid['days'][$today]['configured_capacity']??-1)===0,
    'valid zero-capacity closure exception still projects true closed capacity');
$db->rule=$build(3);
$db->last_error='stale error before valid query';
$valid=WCA_Future24::heatmap(77,7,77);
$verify(is_array($valid)&&($valid['days'][$today]['configured_capacity']??-1)===3,
    'per-read stale error is cleared before a later healthy availability query');
echo "T44 FUT08 native heatmap SQL/capacity integrity PASS {$assertions}/{$assertions} (PHP fixtures, not live DB)\n";
