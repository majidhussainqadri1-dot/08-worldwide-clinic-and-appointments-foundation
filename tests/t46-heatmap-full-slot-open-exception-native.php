<?php
/**
 * T46 native FUT08 heatmap source-integrity fixtures:
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
final class T46_Query {
    public $posts=array();
    public function __construct($params){$this->posts=array();}
}
class_alias('T46_Query', 'WP_Query');
final class WCA_Schema {
    public static function tables(){return array('availability'=>'wp_wca_availability');}
}
final class SWC_Helpers {
    const TYPE='wca_appointment';
    public static function to_utc($date,$hm,$timezone) {
        $tz=new DateTimeZone($timezone);
        $local=DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date.' '.$hm, $tz);
        if(!$local || $local->format('Y-m-d H:i')!==$date.' '.$hm){return null;}
        return $local->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
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
final class T46_DB {
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
$GLOBALS['wpdb']=new T46_DB();
require dirname(__DIR__).'/includes/class-wca-future24.php';
$assertions=0;
$verify=static function($ok,$msg)use(&$assertions){
    $assertions++;
    if(!$ok){fwrite(STDERR,"T46 heatmap FAIL {$assertions}: {$msg}\n");exit(1);}
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

$ruleForIntervals=static function($breaks,$exceptions)use($weekday,$today){
    return array(
        'id'=>2,'capacity'=>2,'status'=>'active','timezone'=>'UTC',
        'rrule_json'=>json_encode(array('start'=>'09:00','end'=>'11:00','interval_minutes'=>60,'days'=>array($weekday))),
        'breaks_json'=>json_encode($breaks),'exceptions_json'=>json_encode($exceptions),
    );
};
$getCapacity=static function($breaks=array(),$exceptions=array())use($db,$ruleForIntervals,$today){
    $db->mode='one';$db->rule=$ruleForIntervals($breaks,$exceptions);
    $res=WCA_Future24::heatmap(77,7,77);
    return is_wp_error($res)?$res:($res['days'][$today]['configured_capacity']??-1);
};
$verify($getCapacity()===4,'baseline two one-hour slots at capacity 2 give 4');
$verify($getCapacity(array(array('start'=>'09:30','end'=>'10:00')))===2,
    'partial break intersects 09:00-10:00 slot and suppresses exactly that slot');
$verify($getCapacity(array(array('start'=>'09:30','end'=>'10:30')))===0,
    'break intersects both slots; heatmap must not overstate 4');
$verify($getCapacity(array(array('start'=>'10:00','end'=>'11:00')))===2,
    'exact break boundary leaves prior 09:00-10:00 slot available');
$verify($getCapacity(array(array('start'=>'12:00','end'=>'12:30')))===4,
    'disjoint break must not reduce configured capacity');
$partial=array(array('date'=>$today,'type'=>'closed','start'=>'09:30','end'=>'10:00'));
$verify($getCapacity(array(),$partial)===2,
    'bounded closure suppresses only its overlapping 09 slot, not entire day');
$partial=array(array('date'=>$today,'type'=>'closed','start'=>'09:30','end'=>'10:30'));
$verify($getCapacity(array(),$partial)===0,
    'bounded closure spanning both slots suppresses both');
$partial=array(array('date'=>$today,'type'=>'closed','start'=>'12:00','end'=>'12:30'));
$verify($getCapacity(array(),$partial)===4,
    'bounded closure outside schedule must not mark whole day closed');
$all=array(array('date'=>$today,'type'=>'closed','start'=>'','end'=>''));
$verify($getCapacity(array(),$all)===0,
    'explicit all-day closure must still remove all capacity');
$invalid=array(array('date'=>$today,'type'=>'closed','start'=>'09:30','end'=>''));
$invalidResult=$getCapacity(array(),$invalid);
$verify(is_wp_error($invalidResult)&&$invalidResult->code==='wca_heatmap_interval_corrupt',
    'incomplete closure bounds must fail closed, not fabricate a full-day closure');
$invalidResult=$getCapacity(array(array('start'=>'09:30','end'=>'bad')));
$verify(is_wp_error($invalidResult)&&$invalidResult->code==='wca_heatmap_interval_corrupt',
    'malformed stored break must fail closed, not count unsafe capacity');
$invalidResult=$getCapacity(array(array('start'=>'10:30','end'=>'09:30')));
$verify(is_wp_error($invalidResult)&&$invalidResult->code==='wca_heatmap_interval_corrupt',
    'reverse-order break boundaries fail closed');

// T46-D01: the actual slot generator admits only *complete* appointments.
$partialRule=$ruleForIntervals(array(),array());
$rrule=json_decode($partialRule['rrule_json'],true);
$rrule['end']='10:30';
$partialRule['rrule_json']=json_encode($rrule);
$db->mode='one';$db->rule=$partialRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$today]['configured_capacity']??-1)===2,
    '09:00-10:30 with 60min slot counts only 09:00-10:00; remaining 30min not a slot');
$rrule['end']='09:45';$partialRule['rrule_json']=json_encode($rrule);$db->rule=$partialRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$today]['configured_capacity']??-1)===0,
    '09:00-09:45 has no complete 60min availability slot');
$rrule['end']='11:00';$partialRule['rrule_json']=json_encode($rrule);$db->rule=$partialRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$today]['configured_capacity']??-1)===4,
    'two exact complete 60min slots remain available when end=11:00');
$rrule['start']='09:15';$rrule['end']='11:00';
$partialRule['rrule_json']=json_encode($rrule);$db->rule=$partialRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$today]['configured_capacity']??-1)===2,
    'misaligned 09:15-11:00 only fits one 60min slot');
// T46-D02: File08 canonical rule slot discovery honors an explicitly
// opened normally closed weekday; the heatmap must also honor it.
$tomorrow=gmdate('Y-m-d',time()+DAY_IN_SECONDS);
$openRule=$ruleForIntervals(array(),array());
$base=json_decode($openRule['rrule_json'],true);
$base['days']=array($weekday); // tomorrow is a different weekday.
$openRule['rrule_json']=json_encode($base);
$db->rule=$openRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$tomorrow]['configured_capacity']??-1)===0,
    'a normally closed weekday has no capacity without an open exception');
$openRule['exceptions_json']=json_encode(array(array('date'=>$tomorrow,'type'=>'open','start'=>'09:00','end'=>'11:00')));
$db->rule=$openRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$tomorrow]['configured_capacity']??-1)===4,
    'approved open exception on otherwise closed weekday exposes two complete slots');
$openRule['exceptions_json']=json_encode(array(array('date'=>$tomorrow,'type'=>'open','start'=>'09:00','end'=>'10:30')));
$db->rule=$openRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$tomorrow]['configured_capacity']??-1)===2,
    'open exception on closed weekday honors its shorter end boundary');
$openRule['exceptions_json']=json_encode(array(array('date'=>$tomorrow,'type'=>'open','start'=>'','end'=>'')));
$db->rule=$openRule;
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$tomorrow]['configured_capacity']??-1)===4,
    'approved open exception without explicit hours uses canonical base rule hours');
// Re-run existing complete-day close rule semantics on a normally open day.
$db->rule=$ruleForIntervals(array(),array(array('date'=>$today,'type'=>'closed','start'=>'','end'=>'')));
$result=WCA_Future24::heatmap(77,7,77);
$verify(is_array($result)&&($result['days'][$today]['configured_capacity']??-1)===0,
    'all-day closure remains closed after explicit opening parity fix');
echo "T46 FUT08 current-rule full-slot/open-day native tests PASS {$assertions}/{$assertions} (PHP fixtures, not WordPress/MySQL)\n";

