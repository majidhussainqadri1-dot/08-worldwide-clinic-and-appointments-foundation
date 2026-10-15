<?php
/**
 * T37 two native File08 Future24 method tests. Executes current PHP methods,
 * not installed WordPress/MySQL, browser or third-party File17/19 services.
 */
require __DIR__ . '/bootstrap.php';
if(!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text) { return trim(strip_tags((string)$text)); }
}
final class T37_DB {
    public $last_error = '';
}
$GLOBALS['wpdb'] = new T37_DB();
final class SWC_Helpers { const TYPE = 'wca_appointment'; }
final class WP_Query {
    public $posts;
    public static $mode = 'valid';
    public static $reads = 0;
    public static $fail_page = 0;
    public static $bad_shape_page = 0;
    public static $invalid_page = 0;
    public static $full_until = 0;
    public function __construct($query) {
        self::$reads++;
        $page = (int)($query['paged'] ?? 1);
        $GLOBALS['wpdb']->last_error = self::$fail_page === $page
            ? 'injected actual WP query SQL failure' : '';
        if(self::$bad_shape_page === $page) { $this->posts = null; return; }
        if(self::$invalid_page === $page) { $this->posts = array(0, 'broken'); return; }
        if(self::$full_until && $page <= self::$full_until) {
            $this->posts = range(1, 200);
            return;
        }
        if(self::$mode === 'page2' && $page === 1) {
            $this->posts = range(1, 200);
        } elseif(self::$mode === 'page2' && $page === 2) {
            $this->posts = array(201,202,203);
        } else {
            $this->posts = ($page === 1) ? array(17,18,19) : array();
        }
    }
}
require dirname(__DIR__) . '/includes/class-wca-future24.php';
$checks=0;
$test = static function($bool, $message) use (&$checks) {
    $checks++;
    if(!$bool) { fwrite(STDERR, 'T37 RUNTIME FAIL '.$message."\n"); exit(1); }
};
$scan = new ReflectionMethod('WCA_Future24','clinic_appointments_between_all');
$scan->setAccessible(true);
$time_from='2026-10-14 00:00:00';
$time_to='2026-10-16 23:59:59';
$invoke = static function() use ($scan,$time_from,$time_to) {
    return $scan->invoke(null,3,$time_from,$time_to);
};
// Baseline success, validates real implementation returns all ids.
WP_Query::$mode = 'valid';
$result=$invoke();
$test($result===array(17,18,19),'complete first-page scan');
$test(WP_Query::$reads>=1,'at least one real WP_Query call');
// First page SQL error, even with a plausible posts list, MUST NOT return success.
WP_Query::$fail_page=1;
$result=$invoke();
$test(is_wp_error($result) && $result->code==='wca_disruption_appointment_page_read_failed',
    'page1 SQL failure fails closed');
WP_Query::$fail_page=0;
// Page2 failure must not return first 200 as complete.
WP_Query::$mode='page2';WP_Query::$fail_page=2;
$result=$invoke();
$test(is_wp_error($result) && $result->code==='wca_disruption_appointment_page_read_failed',
    'later page SQL failure must not silently truncate');
WP_Query::$fail_page=0;WP_Query::$mode='valid';
// WP_Query property returned in invalid shape.
WP_Query::$bad_shape_page=1;
$result=$invoke();
$test(is_wp_error($result) && $result->code==='wca_disruption_appointment_page_read_failed',
    'broken WordPress query shape fails closed');
WP_Query::$bad_shape_page=0;
// Invalid post id in a supposedly successful query.
WP_Query::$invalid_page=1;
$result=$invoke();
$test(is_wp_error($result) && $result->code==='wca_disruption_appointment_page_invalid',
    'invalid appointment ID fails closed');
WP_Query::$invalid_page=0;
// A real complete second page still works.
WP_Query::$mode='page2';
$result=$invoke();
$test(is_array($result) && count($result)===203,
    'valid two-page enumeration remains complete');
WP_Query::$mode='valid';
// Guard against unbounded queries and silent fixed-count truncation.
WP_Query::$full_until=251;
$result=$invoke();
$test(is_wp_error($result) && $result->code==='wca_disruption_appointment_page_limit',
    'excessive page count aborts explicitly instead of returning partial');
WP_Query::$full_until=0;

// FUT18 persisted participant JSON must be strict BEFORE any database UPDATE.
$validator = new ReflectionMethod('WCA_Future24','verified_support_participant_payload');
$validator->setAccessible(true);
$ref='00000000-0000-4000-8000-000000000077';
$valid = array('subject_uuid'=>$ref,'role'=>'interpreter','appointment_bound'=>true,
    'revocable'=>true,'clinical_write_authority'=>false);
$row=array('subject_user_id'=>77,'payload_json'=>json_encode($valid));
$ok=$validator->invoke(null,$row);
$test(is_array($ok) && $ok['subject_uuid']===$ref && $ok['role']==='interpreter',
    'valid support/interpreter payload retained');
foreach(array(
    'broken JSON'=>'{broken',
    'array root'=>'[]',
    'missing subject'=>json_encode(array_diff_key($valid,array('subject_uuid'=>1))),
    'unknown role'=>json_encode(array_merge($valid,array('role'=>'admin'))),
    'unbound'=>json_encode(array_merge($valid,array('appointment_bound'=>false))),
    'not revocable'=>json_encode(array_merge($valid,array('revocable'=>false))),
    'clinical write escalation'=>json_encode(array_merge($valid,array('clinical_write_authority'=>true))),
    'wrong subject shape'=>json_encode(array_merge($valid,array('subject_uuid'=>'not-a-uuid')))
) as $label=>$malformed) {
    $bad=$validator->invoke(null,array('subject_user_id'=>77,'payload_json'=>$malformed));
    $test(is_wp_error($bad) && $bad->code==='wca_support_participant_payload_corrupt',
        'fail-closed persisted role: '.$label);
}
$missing=$validator->invoke(null,array('subject_user_id'=>0,'payload_json'=>json_encode($valid)));
$test(is_wp_error($missing),'missing canonical subject ID fails closed');

// Source-order contract: no SQL UPDATE, File17 event or audit before guard.
$src=file_get_contents(dirname(__DIR__).'/includes/class-wca-future24.php');
$first=strpos($src,'public static function revoke_participant(');
$end=strpos($src,'/* FUT-19 */',$first);
$method=substr($src,$first,$end-$first);
$validate=strpos($method,'$payload = self::verified_support_participant_payload( $row );');
$sql=strpos($method,"UPDATE {\$table}");
$event=strpos($method,'File17.AppointmentParticipantChanged.v1');
$test($validate!==false && $sql!==false && $event!==false &&
    $validate<$sql && $validate<$event,
    'FUT18 stored role validated before database update and File17 outbox');
foreach(array('heatmap( ','no_show_forecast( ','create_disruption(') as $fn) {
    $start=strpos($src,'function '.$fn);
    $next=strpos($src,"\n\tpublic static function ",$start+1);
    $section=substr($src,$start,($next===false?strlen($src):$next)-$start);
    $test(strpos($section,'clinic_appointments_between_all')!==false &&
        strpos($section,'is_wp_error')!==false,
        'FUT08/10/17 caller propagates incomplete scan failure: '.$fn);
}
echo 'T37 native paging/participant regressions PASS '.$checks.'/'.$checks.
    " (unit fixtures only; staging/live not verified)\n";
