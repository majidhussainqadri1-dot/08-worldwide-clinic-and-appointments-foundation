<?php
/**
 * File08 T29: behavioral storage-level completed booking replay tests.
 * The whole WordPress booking transaction still requires real staging tests.
 */
define( 'ABSPATH', __DIR__ . '/' );
define( 'ARRAY_A', 'ARRAY_A' );
define( 'MINUTE_IN_SECONDS', 60 );
function absint( $value ) { return abs( (int) $value ); }
function __( $text ) { return $text; }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ); }
function wp_json_encode( $value, $options = 0 ) { return json_encode( $value, $options ); }
function wp_is_uuid( $value ) { return is_string( $value ) && 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value ); }
class WP_Error {
    private $code;
    private $data;
    public function __construct( $code, $message = '', $data = array() ) { $this->code = $code; $this->data = $data; }
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
class WCA_Schema {
    public static function tables() { return array( 'idempotency' => 'wp_wca_idempotency' ); }
}
class T29ReplayDb {
    public $row = null;
    public $last_error = '';
    public $inject_error = false;
    public $args = array();
    public $query = '';
    public $actor = 37;
    public function prepare( $query, ...$args ) { $this->args = $args; $this->query = $query; return $query; }
    public function get_row( $query, $mode ) {
        if ( $this->inject_error ) { $this->last_error = 'read failed'; return null; }
        if ( absint( $this->args[2] ?? 0 ) !== $this->actor ) { return null; }
        return $this->row;
    }
}
$wpdb = new T29ReplayDb();
require __DIR__ . '/../includes/class-wca-repository.php';
$checks = 0;
function t29_check( $truth, $label ) {
    global $checks;
    $checks++;
    if ( ! $truth ) { fwrite( STDERR, "T29 completed replay FAILED: {$label}\n" ); exit( 1 ); }
}
$key = 'file08-replay-t29-001';
$fp = array( 'hold_token' => 'held-original', 'privacy_consent' => true, 'emergency_acknowledged' => true, 'telehealth_consent' => true );
$ref = '12345678-1234-4234-8234-123456789abc';
$response = array( 'appointment_id' => 432, 'public_ref' => $ref, 'status' => 'requested', 'trace_id' => 'x' );
$base = array(
    'request_hash' => hash( 'sha256', wp_json_encode( $fp, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ),
    'status' => 'completed', 'response_code' => 201, 'response_json' => wp_json_encode( $response ),
    'updated_at' => gmdate( 'Y-m-d H:i:s' ),
);
$wpdb->row = null;
t29_check( null === WCA_Repository::completed_appointment_replay( $key, 37, $fp ), 'unused key returns null (no new claim)' );
$wpdb->row = $base;
$result = WCA_Repository::completed_appointment_replay( $key, 37, $fp );
t29_check( is_array( $result ) && $ref === $result['public_ref'] && 432 === $result['appointment_id'], 'completed exact replay returns original owner result' );
t29_check( 3 === count( $wpdb->args ) && 'request_appointment' === $wpdb->args[0] && hash( 'sha256', $key ) === $wpdb->args[1] && 37 === $wpdb->args[2], 'query is scoped to owner key and actor' );
t29_check( 0 === strpos( trim( $wpdb->query ), 'SELECT ' ), 'replay storage query is read only' );
$result = WCA_Repository::completed_appointment_replay( $key, 38, $fp );
t29_check( null === $result, 'different actor cannot access completed result' );
$changed = $fp;
$changed['hold_token'] = 'different-original-hold';
$result = WCA_Repository::completed_appointment_replay( $key, 37, $changed );
t29_check( is_wp_error( $result ) && 'wca_idempotency_conflict' === $result->get_error_code(), 'changed request payload is a conflict' );
$wpdb->row = array_merge( $base, array( 'status' => 'processing' ) );
$result = WCA_Repository::completed_appointment_replay( $key, 37, $fp );
t29_check( is_wp_error( $result ) && 'wca_idempotency_in_progress' === $result->get_error_code(), 'processing state blocks duplicate side effect' );
$wpdb->row['updated_at'] = gmdate( 'Y-m-d H:i:s', time() - 600 );
$result = WCA_Repository::completed_appointment_replay( $key, 37, $fp );
t29_check( ! empty( $result->get_error_data()['reconciliation_required'] ), 'stale processing requires reconciliation' );
$wpdb->row = array_merge( $base, array( 'response_json' => '{}' ) );
$result = WCA_Repository::completed_appointment_replay( $key, 37, $fp );
t29_check( is_wp_error( $result ) && 'wca_replay_response_unverified' === $result->get_error_code(), 'incomplete outcome fails closed' );
$wpdb->row = array_merge( $base, array( 'response_code' => 500 ) );
$result = WCA_Repository::completed_appointment_replay( $key, 37, $fp );
t29_check( is_wp_error( $result ) && 'wca_replay_response_unverified' === $result->get_error_code(), 'non-201 outcome cannot be replayed as accepted' );
$wpdb->row = null;
$wpdb->inject_error = true;
$result = WCA_Repository::completed_appointment_replay( $key, 37, $fp );
t29_check( is_wp_error( $result ) && 'wca_replay_read_unverified' === $result->get_error_code(), 'read failure fails closed, never bypasses safety' );
$wpdb->inject_error = false;
$result = WCA_Repository::completed_appointment_replay( 'bad', 37, $fp );
t29_check( is_wp_error( $result ) && 'wca_replay_identifier_invalid' === $result->get_error_code(), 'malformed replay key rejected' );
// Guard against future refactors placing mutable hold/service validation first.
$service = file_get_contents( __DIR__ . '/../includes/class-wca-service.php' );
$command = file_get_contents( __DIR__ . '/../includes/class-wca-appointment-command.php' );
$service_root = strstr( $service, 'public static function request_appointment( ' );
$command_root = strstr( $command, 'public static function request( ' );
t29_check( is_string( $service_root ) && strpos( $service_root, 'WCA_Repository::completed_appointment_replay' ) < strpos( $service_root, 'WCA_Repository::get_slot_hold' ), 'service replays before mutable hold gate' );
t29_check( is_string( $command_root ) && strpos( $command_root, 'WCA_Service::completed_appointment_replay' ) < strpos( $command_root, 'WCA_Repository::get_slot_hold' ), 'governing command replays before mutable hold/service gate' );
t29_check( strpos( $command_root, "unset( \$prior['appointment_id'] )" ) !== false, 'HTTP command strips native appointment identifier on replay' );
t29_check( strpos( $service, 'WCA_Authorization::guardian_context' ) !== false, 'participant authority guard retained' );
echo "T29 completed owner replay behavioral/ordering guards: PASS {$checks}/{$checks}; WP staging remains unverified.\n";
