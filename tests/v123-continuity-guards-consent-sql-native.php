<?php
/** V12.3 actual WCA_Continuity_Guards consent and erasure source behaviors; WP DB transport stub, NOT Staging. */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'HOUR_IN_SECONDS' ) || define( 'HOUR_IN_SECONDS', 3600 );
function __( $s, $domain = '' ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_email( $v ) { return trim( (string) $v ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function get_posts( $args ) { return array( 12 ); }
function get_post_field( $field, $id ) { return 7; }
function get_user_by( $field, $email ) { return (object) array( 'ID' => 7 ); }
function get_transient( $key ) { return 0; }
function set_transient( $key, $val, $ttl ) { return true; }
function delete_transient( $key ) { return true; }
function apply_filters( $name, $value ) { return $value; }
final class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $message = '', $data = null ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
final class SWC_Helpers {
    const TYPE = 'swc_appointment';
    public static function meta( $id, $name, $default = null ) { return 7; }
    public static function status( $id ) { return 'requested'; }
}
final class WCA_Authorization {
    public static function can_view_appointment( $appointment, $user ) { return true; }
    public static function appointment_actor( $appointment, $user ) { return 'patient'; }
}
final class WCA_Central_Governance {
    public static function validate_patient_guardian( $patient, $guardian, $actor ) { return true; }
}
final class WCA_Schema {
    public static function tables() { return array( 'consents' => 'wp_wca_consents' ); }
}
final class WCA_Continuity {
    public static function tables() { return array( 'intake' => 'wp_wca_intake', 'followups' => 'wp_wca_followups' ); }
}
final class V123_Guards_DB {
    public $last_error = '';
    public static $mode = 'valid';
    public function prepare( $sql ) { return (string) $sql; }
    public function get_results( $sql, $format = null ) {
        if ( 'sql_empty' === self::$mode ) { $this->last_error = 'simulated SQL unavailable'; return array(); }
        if ( 'invalid' === self::$mode ) { return false; }
        return array();
    }
    public function delete( $table, $where, $format = array() ) { return 1; }
    public function update( $table, $values, $where, $formats = array(), $where_formats = array() ) { return 1; }
    public function get_var( $sql ) { $this->last_error = ''; return null; }
}
$GLOBALS['wpdb'] = new V123_Guards_DB();
require dirname( __DIR__ ) . '/includes/class-wca-continuity-guards.php';
$checks = 0;
$check = static function ( $ok, $name ) use ( &$checks ) {
    $checks++;
    if ( ! $ok ) { fwrite( STDERR, "V12.3 guard SQL FAIL {$checks}: {$name}\n" ); exit( 1 ); }
};
$ref = '11111111-1111-4111-8111-111111111111';
foreach ( array( 'valid', 'stale', 'sql_empty', 'invalid' ) as $mode ) {
    V123_Guards_DB::$mode = $mode;
    $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'previous unrelated SQL marker' : '';
    $result = WCA_Continuity_Guards::consent_state( $ref, 7 );
    if ( 'sql_empty' === $mode || 'invalid' === $mode ) {
        $check( $result instanceof WP_Error && 'wca_consent_state_read_failed' === $result->code,
            'consent state must return typed 503 on unreadable SQL ' . $mode );
    } else {
        $check( is_array( $result ) && 'not_granted' === $result['scopes']['appointment_processing']['status'],
            'legitimate empty scopes still readable ' . $mode );
    }

    V123_Guards_DB::$mode = $mode;
    $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'previous unrelated SQL marker' : '';
    $erasure = WCA_Continuity_Guards::privacy_eraser( 'synthetic@example.test' );
    if ( 'sql_empty' === $mode || 'invalid' === $mode ) {
        $check( is_array( $erasure ) && false === $erasure['done'] && ! empty( $erasure['messages'] ),
            'privacy erasure must not falsely report done=true on unreadable rows ' . $mode );
    } else {
        $check( is_array( $erasure ) && true === $erasure['done'],
            'legitimate empty erasure is complete ' . $mode );
    }
}
echo "PASS: {$checks} native restricted continuity guard read checks; NOT staging or Live.\n";
