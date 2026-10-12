<?php
/**
 * T27-R2: real File08 query methods + fixture DB prove the native cursor
 * producer/consumer invariant. This is NOT Hostinger or actual MySQL E2E.
 */
class WP_Error {
    public $code;
    public $message;
    public $data;
    public function __construct( $code = '', $message = '', $data = array() ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
    public function get_error_data() { return $this->data; }
}
require __DIR__ . '/bootstrap.php';
function wp_json_encode( $v ) { return json_encode( $v ); }
function wp_salt( $name = '' ) { return 't27-r2-private-test-' . $name; }
function sanitize_text_field( $text ) { return trim( strip_tags( (string) $text ) ); }
function get_current_user_id() { return 77; }
function user_can( $uid, $cap ) { return false; }
final class WCA_Authorization {
    public static function claims( $actor ) { return array( 'doctor' => true ); }
    public static function can_view_appointment( $id, $actor, $purpose = '' ) { return true; }
    public static function appointment_actor( $id, $actor ) { return 'patient'; }
    public static function delegated_clinic_ids( $actor, $scope ) { return array( 3 ); }
}
final class WCA_Repository {
    public static function clear_read_error() {}
    public static function consume_read_error() { return null; }
    public static function get_clinic( $ref, $public = false ) {
        return array( 'id' => 3, 'owner_user_id' => 77,
            'public_ref' => '00000000-0000-4000-8000-000000000003' );
    }
}
final class SWC_Helpers {
    const TYPE = 'wca_appointment';
    public static function meta( $id, $key, $default = '' ) {
        if ( 'public_ref' === $key ) {
            return sprintf( '00000000-0000-4000-8000-%012d', (int) $id );
        }
        return $default;
    }
    public static function status( $id ) { return 'confirmed'; }
    public static function record_version( $id ) { return 1; }
}
final class WCA_Contracts {
    public static function allowed_transitions( $actor, $status ) { return array(); }
}
final class WCA_Observability {
    public static function trace_id() { return 't27-r2-native-cursor'; }
}
final class T27_R2_DB {
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $last_error = '';
    public $rows = array();
    public $reads = 0;
    public function prepare( $sql, $args ) { return $sql; }
    public function get_results( $sql, $type ) {
        $this->reads++;
        return $this->rows;
    }
}
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
$GLOBALS['wpdb'] = new T27_R2_DB();
require dirname( __DIR__ ) . '/includes/class-wca-query-api.php';
$count = 0;
$check = static function( $ok, $reason ) use ( &$count ) {
    if ( ! $ok ) { fwrite( STDERR, 'T27-R2 FAIL: ' . $reason . "\n" ); exit( 1 ); }
    $count++;
};
$encode = new ReflectionMethod( 'WCA_Query_API', 'encode_cursor' );
$decode = new ReflectionMethod( 'WCA_Query_API', 'decode_cursor' );
$encode->setAccessible( true ); $decode->setAccessible( true );
$scope = 'appointment_list'; $actor = 77; $filter = hash( 'sha256', 'r2-producer' );
$valid = '2026-10-12 14:19:00';
$case = array(
    $valid, '2024-02-29 00:00:00', '1999-12-31 23:59:59'
);
foreach ( $case as $t ) {
    $token = $encode->invoke( null, $scope, $actor, $filter, array( 't' => $t, 'i' => 51 ) );
    $value = $decode->invoke( null, $token, $scope, $actor, $filter );
    $check( is_array( $value ) && $value['t'] === $t && $value['i'] === 51,
        'producer and consumer mismatch for valid canonical UTC sort' );
}
foreach ( array(
    '', 'unparseable', '2026-02-30 14:19:00',
    '2026-10-12T14:19:00Z', '2026-10-12 24:00:00',
    '2026-10-12 14:19:00<script>', str_repeat( 'x', 2300 )
) as $invalid ) {
    $token = $encode->invoke( null, $scope, $actor, $filter, array( 't' => $invalid, 'i' => 51 ) );
    $check( '' === $token, 'encoder must not sign invalid historical sort timestamp' );
}
$db = $GLOBALS['wpdb'];
$db->rows = array(
    array( 'ID' => 51, 'sort_time' => $valid ),
    array( 'ID' => 50, 'sort_time' => '2026-10-11 14:19:00' )
);
$good = WCA_Query_API::list_patient_appointments( 77, array( 'per_page' => 1 ) );
$check( is_array( $good ) && count( $good['items'] ) === 1 &&
    is_string( $good['next_cursor'] ) && '' !== $good['next_cursor'],
    'patient list with valid historical state yields consumable next cursor' );
$patient_filter = hash( 'sha256', wp_json_encode( array(
    'actor' => 77, 'scope' => 'own_appointments', 'per_page' => 1,
) ) );
$roundtrip = $decode->invoke( null, $good['next_cursor'], 'appointment_list', 77, $patient_filter );
$check( is_array( $roundtrip ) && $roundtrip['i'] === 51,
    'actual patient list next cursor was rejected by shared decoder' );
$db->rows[0]['sort_time'] = 'historical-non-calendar';
$bad = WCA_Query_API::list_patient_appointments( 77, array( 'per_page' => 1 ) );
$check( is_wp_error( $bad ) && 'wca_query_cursor_source_invalid' === $bad->code &&
    503 === $bad->data['status'], 'patient list silently returned unconsumable cursor/partial success' );
$db->rows = array( array( 'ID' => 0, 'sort_time' => $valid ), array( 'ID' => 0, 'sort_time' => $valid ) );
$bad = WCA_Query_API::list_patient_appointments( 77, array( 'per_page' => 1 ) );
$check( is_wp_error( $bad ) && 'wca_query_cursor_source_invalid' === $bad->code,
    'patient list declared no next cursor despite source rows due to missing identity' );
$db->rows = array(
    array( 'ID' => 51, 'sort_time' => $valid ),
    array( 'ID' => 50, 'sort_time' => '2026-10-11 14:19:00' )
);
$clinic = WCA_Query_API::list_clinic_schedule( '00000000-0000-4000-8000-000000000003',
    77, array( 'per_page' => 1 ) );
$check( is_array( $clinic ) && count( $clinic['items'] ) === 1 &&
    '' !== $clinic['next_cursor'], 'clinic schedule could not produce a valid signed page' );
$db->rows[0]['sort_time'] = '2026-13-12 14:19:00';
$bad = WCA_Query_API::list_clinic_schedule( '00000000-0000-4000-8000-000000000003',
    77, array( 'per_page' => 1 ) );
$check( is_wp_error( $bad ) && 'wca_query_cursor_source_invalid' === $bad->code,
    'clinic schedule produced a signed cursor from non-calendar source or partial success' );
$check( $db->reads >= 4, 'native SQL-backed source query fixture did not execute' );
echo 'T27-R2 native File08 cursor producer/consumer regressions: PASS ' .
    $count . '/' . $count . "\n";
