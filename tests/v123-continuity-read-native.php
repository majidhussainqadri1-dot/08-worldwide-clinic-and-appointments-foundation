<?php
/**
 * V12.3 actual WCA_Continuity methods: 8 read sites, 5 affected workflows.
 * Stubbed DB transport; source native QA, not WordPress staging/Hostinger Live.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
function __( $s, $domain = '' ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_email( $v ) { return trim( (string) $v ); }
function get_user_by( $field, $value ) { return (object) array( 'ID' => 7 ); }
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function get_current_user_id() { return 7; }
function get_posts( $args ) { return array( 12 ); }
function get_transient( $key ) { return 0; }
function set_transient( $key, $val, $ttl ) { return true; }
function delete_transient( $key ) { return true; }
function apply_filters( $name, $val ) { return $val; }
final class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $message = '', $data = null ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
final class SWC_Helpers { const TYPE = 'swc_appointment'; }
final class WCA_Authorization {
    public static function can_view_appointment( $appointment_id, $user ) { return true; }
    public static function appointment_actor( $appointment_id, $user ) { return 'patient'; }
}
final class WCA_Repository { public static function now() { return '2026-11-15 02:00:00'; } }
final class V123_Continuity_DB {
    public $last_error = '';
    public $prefix = 'wp_';
    public static $mode = 'valid';
    public static $fault_at = 0;
    public static $reads = 0;
    public static $guardian_only = false;
    public function prepare( $sql ) { return (string) $sql; }
    public function get_results( $sql, $format = null ) {
        self::$reads++;
        $fault = self::$fault_at === 0 || self::$fault_at === self::$reads;
        if ( self::$guardian_only ) { $fault = false !== strpos( $sql, 'guardian_user_id=%d' ); }
        if ( $fault && 'sql_empty' === self::$mode ) {
            $this->last_error = 'simulated SQL lost while reading private continuity records';
            return array();
        }
        if ( $fault && 'non_array' === self::$mode ) { return false; }
        return array();
    }
    public function get_var( $sql ) {
        $this->last_error = '';
        return false !== stripos( $sql, 'COUNT(*)' ) ? 0 : null;
    }
    public function delete( $table, $where, $format ) { return 1; }
}
$GLOBALS['wpdb'] = new V123_Continuity_DB();
require dirname( __DIR__ ) . '/includes/class-wca-continuity-secure.php';
$cases = array(
    array( 'privacy_exporter', 'wca_continuity_export_intake_query' ),
    array( 'privacy_eraser', 'retry' ),
    array( 'apply_retention', 'wca_intake_retention_read_failed' ),
    array( 'process_due_followups', 'wca_followup_reminder_scan_read_failed' ),
    array( 'list_followups', 'wca_followup_list_read_failed' ),
);
$checks = 0;
$check = static function ( $ok, $message ) use ( &$checks ) {
    $checks++;
    if ( ! $ok ) { fwrite( STDERR, "V12.3 continuity SQL FAIL {$checks}: {$message}\n" ); exit( 1 ); }
};
foreach ( array( 'valid', 'sql_empty', 'stale', 'non_array' ) as $mode ) {
    foreach ( $cases as $case ) {
        $method = $case[0];
        V123_Continuity_DB::$mode = $mode;
        V123_Continuity_DB::$fault_at = 0;
        V123_Continuity_DB::$reads = 0;
        V123_Continuity_DB::$guardian_only = false;
        $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'previous unrelated SQL failure' : '';
        if ( 'privacy_exporter' === $method || 'privacy_eraser' === $method ) {
            $value = WCA_Continuity::$method( 'synthetic@example.test' );
        } elseif ( 'list_followups' === $method ) {
            $value = WCA_Continuity::$method( '11111111-1111-4111-8111-111111111111' );
        } else { $value = WCA_Continuity::$method(); }
        if ( 'sql_empty' === $mode || 'non_array' === $mode ) {
            $ok = 'privacy_eraser' === $method
                ? is_array( $value ) && false === $value['done'] && ! empty( $value['messages'] )
                : $value instanceof WP_Error && $case[1] === $value->code;
            $check( $ok, $method . ' must fail safely under ' . $mode );
        } else {
            $ok = 'privacy_exporter' === $method || 'privacy_eraser' === $method
                ? is_array( $value ) && true === $value['done']
                : ( 'process_due_followups' === $method
                    ? 0 === $value : ( 'list_followups' === $method ? array() === $value : true === $value ) );
            $check( $ok, $method . ' valid empty must not fail under ' . $mode );
        }
    }
}
// Force the *second* private export query after a successful empty first query.
V123_Continuity_DB::$mode = 'sql_empty';
V123_Continuity_DB::$fault_at = 2;
V123_Continuity_DB::$reads = 0;
V123_Continuity_DB::$guardian_only = false;
$late = WCA_Continuity::privacy_exporter( 'synthetic@example.test' );
$check( $late instanceof WP_Error && 'wca_continuity_export_followup_query' === $late->code,
    'second followup-export query must not fabricate done=true' );

// Guardian-only SQL failure must prohibit false privacy erasure completion.
V123_Continuity_DB::$mode = 'sql_empty';
V123_Continuity_DB::$fault_at = 0;
V123_Continuity_DB::$reads = 0;
V123_Continuity_DB::$guardian_only = true;
$guardian = WCA_Continuity::privacy_eraser( 'synthetic@example.test' );
$check( is_array( $guardian ) && false === $guardian['done'] && ! empty( $guardian['messages'] ),
    'guardian reference SQL query fails erasure instead of clearing its cursor' );

echo "PASS: {$checks} native continuity read scenarios: five real methods, guardian and second-page export; NOT Live.\n";
