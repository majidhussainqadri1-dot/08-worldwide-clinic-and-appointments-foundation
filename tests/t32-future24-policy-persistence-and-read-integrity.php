<?php
/**
 * T32 native Future24 private policy integration fixture:
 * actual put_record/save_prerequisites/prerequisite_state,
 * actual feature_rows_for_clinic and questionnaire_for_appointment.
 * PHP 7.4/8.3 WordPress mocks, NOT physical MySQL or installed runtime.
 */
require __DIR__ . '/bootstrap.php';
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $v, $opts = 0 ) { return json_encode( $v, $opts ); }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() { return 77; }
}
if ( ! function_exists( 'get_posts' ) ) {
    function get_posts( $args ) { return array( 99 ); }
}
final class SWC_Helpers {
    const TYPE = 'wca_appointment';
    public static function meta( $id, $key, $default = '' ) {
        if ( 'clinic_id' === $key ) { return 3; }
        if ( 'service_id' === $key ) { return 0; }
        if ( 'prerequisite_evidence_refs' === $key ) { return array(); }
        return $default;
    }
}
final class WCA_Service {
    public static function strict_id( $v, $allow_zero = false ) {
        if ( ! is_int( $v ) && ! ( is_string( $v ) && ctype_digit( $v ) ) ) { return null; }
        $id = (int) $v;
        return $id > 0 || ( $allow_zero && $id === 0 ) ? $id : null;
    }
}
final class WCA_Authorization {
    public static function claims( $id ) { return array( 'doctor' => true ); }
    public static function can_manage_clinic( $clinic, $actor ) { return true; }
    public static function can_view_appointment( $id, $actor ) { return true; }
}
final class WCA_Observability {
    public static function trace_id() { return 't32-isolated-php'; }
    public static function log( $level, $message, $data = array() ) {}
}
final class WCA_Repository {
    public static function uuid() { return '00000000-0000-4000-8000-000000000042'; }
    public static function now() { return '2026-10-13 00:00:00'; }
    public static function get_clinic( $id, $public = false ) { return array( 'id' => 3, 'public_ref' => '00000000-0000-4000-8000-000000000003' ); }
    public static function transaction( $callback, $purpose = '' ) { return $callback(); }
    public static function append_event( $event, $aggregate, $ref, $payload, $actor, $trace ) { return true; }
}
final class T32_DB {
    public $prefix = 'wp_';
    public $last_error = '';
    public $insert_id = 42;
    public $writes = array();
    public $batches = array();
    public $reads = 0;
    public function insert( $table, $row ) { $this->writes[] = $row; return 1; }
    public function prepare( $sql ) { return $sql; }
    public function get_results( $sql, $type ) {
        $this->reads++;
        if ( $this->reads > 12 ) { return array(); }
        return $this->batches ? array_shift( $this->batches ) : array();
    }
}
$GLOBALS['wpdb'] = new T32_DB();
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';
$n = 0;
$check = static function( $yes, $label ) use ( &$n ) {
    if ( ! $yes ) { fwrite( STDERR, 'T32 FAIL: ' . $label . "\n" ); exit( 1 ); }
    $n++;
};
$rules = new ReflectionMethod( 'WCA_Future24', 'verified_prerequisite_rules' );
$scan = new ReflectionMethod( 'WCA_Future24', 'feature_rows_for_clinic' );
$state = new ReflectionMethod( 'WCA_Future24', 'prerequisite_state' );
$rules->setAccessible( true );
$scan->setAccessible( true );
$state->setAccessible( true );
$blocking = array( 'type' => 'document', 'label' => 'Identity check',
    'key' => 'document:Identity check', 'missing_behavior' => 'block' );
$provisional = array( 'type' => 'document', 'label' => 'Optional reading',
    'key' => 'document:Optional reading', 'missing_behavior' => 'provisional' );
$check( $rules->invoke( null, array( $blocking, $provisional ) ) === array( $blocking, $provisional ),
    'verified structured FUT13 rule fields mutated' );
foreach ( array(
    null, array(), array( 'garbage' ), array( array( 'unexpected' => 'clinical details' ) ),
    array( array_merge( $blocking, array( 'patient_private_note' => 'secret' ) ) ),
    array( array_merge( $blocking, array( 'missing_behavior' => 'grant' ) ) ),
    array_fill( 0, 21, $blocking ),
) as $case ) {
    $got = $rules->invoke( null, $case );
    $check( is_wp_error( $got ) && $got->code === 'wca_prerequisite_rules_corrupt',
        'FUT13 approved structured nested rule validator accepted unsafe payload' );
}
$db = $GLOBALS['wpdb'];
$stored = WCA_Future24::save_prerequisites( array(
    'clinic_id' => 3, 'service_ref' => '',
    'requirements' => array(
        array( 'type' => 'document', 'label' => 'Identity check', 'missing_behavior' => 'block' ),
        array( 'type' => 'document', 'label' => 'Optional reading', 'missing_behavior' => 'provisional' ),
    ),
), 77 );
$check( is_array( $stored ) && isset( $stored['payload'] ), 'real FUT13 writer failed' );
$check( count( $db->writes ) === 1, 'one FUT13 rule save expected' );
$persisted = $db->writes[0];
$json = json_decode( $persisted['payload_json'], true );
$check( is_array( $json ) && isset( $json['requirements'] ), 'FUT13 persisted JSON missing structured rule map' );
$check( $json['requirements'] == array( $blocking, $provisional ), 'FUT13 native serializer erased/reordered blocking rules' );
$check( $json['blocking_is_explicit'] === true, 'source blocking policy flag lost' );
$check( ! isset( $json['clinical_note'] ), 'arbitrary clinical narrative persisted' );
$db->reads = 0;
$db->batches = array( array( array( 'id' => '42', 'payload_json' => $persisted['payload_json'] ) ) );
$state_result = $state->invoke( null, 99 );
$check( is_array( $state_result ), 'native prerequisite reader denied valid stored FUT13 JSON' );
$check( $state_result['required_count'] === 2 && $state_result['blocking_count'] === 1,
    'stored rules not observed by native appointment readiness prerequisite reader' );
$check( $state_result['complete'] === false && $state_result['missing_requirements'] === array( 'document:Identity check' ),
    'no evidence unexpectedly marked mandatory previsit prerequisites complete' );
$check( $state_result['provisional_missing_requirements'] === array( 'document:Optional reading' ),
    'optional requirement should be provisional only' );
foreach ( array(
    '', '{}', 'garbage', '[]', 'null', '{"requirements":[]}',
    '{"requirements":[{}]}', '{"requirements":[42]}', '{"requirements":[""]}',
) as $bad ) {
    $db->batches = array( array( array( 'id' => '42', 'payload_json' => $bad ) ) );
    $db->reads = 0;
    $response = $state->invoke( null, 99 );
    $check( is_wp_error( $response ) && $response->code === 'wca_prerequisite_policy_corrupt',
        'invalid active prerequisite JSON was treated as complete: ' . $bad );
}
foreach ( array(
    array( array( 'id' => '0', 'payload_json' => '{}' ) ),
    array( array( 'id' => '1', 'payload_json' => '{}' ), array( 'id' => '1', 'payload_json' => '{}' ) ),
    array( array( 'id' => '3', 'payload_json' => '{}' ), array( 'id' => '2', 'payload_json' => '{}' ) ),
    array( array( 'id' => '1.5', 'payload_json' => '{}' ) ),
    array( array( 'id' => array( 1 ), 'payload_json' => '{}' ) ),
) as $batch ) {
    $db->batches = array( $batch ); $db->reads = 0;
    $got = $scan->invoke( null, 'F08-FUT-13', 3, 'rule_active', 'payload' );
    $check( is_wp_error( $got ) && $got->code === 'wca_future24_policy_cursor_corrupt',
        'nonmonotonic/malformed DB keyset row accepted' );
    $check( $db->reads === 1, 'broken source keyset led to continued query/retry' );
}
$first = array();
for ( $i = 1; $i <= 100; $i++ ) { $first[] = array( 'id' => (string) $i, 'payload_json' => '{}' ); }
$db->batches = array( $first, array( array( 'id' => '101', 'payload_json' => '{}' ) ) ); $db->reads = 0;
$good = $scan->invoke( null, 'F08-FUT-13', 3, 'rule_active', 'payload' );
$check( is_array( $good ) && count( $good ) === 101, 'valid 100+1 keyset pagination truncated/degraded' );
$check( $db->reads === 2, 'full 100-page plus 1 lookahead pagination not performed' );
$db->reads = 0;
$db->batches = array( array( array( 'id' => '71', 'public_ref' => '00000000-0000-4000-8000-000000000071',
    'version' => '1', 'updated_at' => '2026-10-13 00:00:00',
    'payload_json' => '{"service_ref":"","fields":["reason","notes"]}' ) ) );
$q = WCA_Future24::questionnaire_for_appointment( '00000000-0000-4000-8000-000000000099', 77 );
$check( is_array( $q ) && $q['fields'] === array( 'reason', 'notes' ), 'real FUT11 questionnaire reader rejected valid active template' );
foreach ( array(
    '{}', '[]', 'null', 'garbage', '{"fields":[]}', '{"fields":["unexpected"]}',
    '{"fields":["reason","reason"]}', '{"fields":[1]}',
) as $bad ) {
    $db->reads = 0;
    $db->batches = array( array( array( 'id' => '71', 'public_ref' => '00000000-0000-4000-8000-000000000071',
        'version' => '1', 'updated_at' => '2026-10-13 00:00:00', 'payload_json' => $bad ) ) );
    $q = WCA_Future24::questionnaire_for_appointment( '00000000-0000-4000-8000-000000000099', 77 );
    $check( is_wp_error( $q ) && $q->code === 'wca_questionnaire_policy_corrupt',
        'corrupt active questionnaire incorrectly turned into valid empty form: ' . $bad );
}
echo 'T32 real Future24 prerequisites, templates, keyset: PASS ' . $n . '/' . $n . "\n";
