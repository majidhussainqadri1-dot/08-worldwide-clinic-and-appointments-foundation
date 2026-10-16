<?php
/**
 * T40 FUT22 native method negative tests: no fabricated "free" on unresolved
 * practitioner identity; no empty verified webhook provenance at the
 * File08 import root. Tests execute the actual WCA_Future24 class, not E2E.
 */
require __DIR__.'/bootstrap.php';
if(!function_exists('sanitize_text_field')) { function sanitize_text_field($v){return trim(strip_tags((string)$v));} }
if(!function_exists('sanitize_key')) { function sanitize_key($v){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',(string)$v));} }
final class WCA_Plan_Guard {
    public static $practitioner_id=0;
    public static function practitioner_id($ref){return self::$practitioner_id;}
    public static function practitioner_ref($id){return '00000000-0000-4000-8000-000000000077';}
}
final class SWC_Doctor_Authority { public static function is_eligible($id){return (int)$id===77;} }
final class WCA_Repository { public static function now(){return gmdate('Y-m-d H:i:s');} }
final class T40_DB {
    public $prefix='wp_';public $last_error='';public $reads=0;public $fail=false;
    public function prepare($query){return $query;}
    public function get_var($query){$this->reads++;$this->last_error=$this->fail?'injected database failure':'';return $this->fail?null:null;}
}
$GLOBALS['wpdb']=new T40_DB();
require dirname(__DIR__).'/includes/class-wca-future24.php';
$n=0;
$check=static function($ok,$label)use(&$n){$n++;if(!$ok){fwrite(STDERR,"T40 FUT22 FAIL $label\n");exit(1);}};
$ref='00000000-0000-4000-8000-000000000077';
$from=gmdate('Y-m-d H:i:s',time()+3600);$to=gmdate('Y-m-d H:i:s',time()+7200);
$unresolved=WCA_Future24::external_busy_conflict($ref,$from,$to);
$check(is_wp_error($unresolved)&&$unresolved->code==='wca_external_busy_practitioner_unavailable',
    'unresolved File09 doctor identity cannot be treated as conflict=false');
$check($GLOBALS['wpdb']->reads===0,'unresolved practitioner must fail before querying calendar table');
WCA_Plan_Guard::$practitioner_id=77;
$valid=WCA_Future24::external_busy_conflict($ref,$from,$to);
$check($valid===false && $GLOBALS['wpdb']->reads===1,'verified practitioner with no busy records may be free');
$GLOBALS['wpdb']->fail=true;
$fail=WCA_Future24::external_busy_conflict($ref,$from,$to);
$check(is_wp_error($fail)&&$fail->code==='wca_external_busy_read_failed',
    'calendar DB failure must fail closed rather than show free');
$GLOBALS['wpdb']->fail=false;
// Native import root must require provider AND event identity to be present.
$window=array('start_utc'=>$from,'end_utc'=>$to,'calendar_ref'=>'calendar-1');
$invalidProvider=WCA_Future24::save_verified_external_busy(77,'','evt-a',$window);
$check(is_wp_error($invalidProvider)&&$invalidProvider->code==='wca_external_busy_provider_provenance',
    'empty provider must be rejected before put_record');
$invalidEvent=WCA_Future24::save_verified_external_busy(77,'caldav','',$window);
$check(is_wp_error($invalidEvent)&&$invalidEvent->code==='wca_external_busy_provider_provenance',
    'empty calendar event id must be rejected before put_record');
$whitespace=WCA_Future24::save_verified_external_busy(77,'caldav','  ',$window);
$check(is_wp_error($whitespace)&&$whitespace->code==='wca_external_busy_provider_provenance',
    'sanitized whitespace event id must be rejected');
// Negative scope: doctor not verified remains blocked.
$ineligible=WCA_Future24::save_verified_external_busy(19,'caldav','evt-a',$window);
$check(is_wp_error($ineligible)&&$ineligible->code==='wca_external_busy_verified_scope',
    'missing File09 eligibility must not create operational calendar state');
echo "T40 FUT22 native no-fail-open and provenance tests PASS {$n}/{$n} (PHP fixtures only)\n";
