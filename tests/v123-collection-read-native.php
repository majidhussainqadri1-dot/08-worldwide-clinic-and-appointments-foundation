<?php
/**
 * V12.3 real WCA_Repository four collection-read fail-closed regressions.
 * WordPress SQL transport is stubbed; this is SOURCE CI, not Hostinger staging.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
function __( $s, $domain = '' ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
final class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $message = '', $data = null ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
final class WCA_Schema {
    public static function tables() {
        return array( 'clinics' => 'wp_wca_clinics', 'branches' => 'wp_wca_branches',
            'services' => 'wp_wca_services', 'availability' => 'wp_wca_availability' );
    }
}
final class V123_Collection_DB {
    public $last_error = '';
    public static $mode = 'valid_empty';
    public function prepare( $sql, $params = array() ) { return (string) $sql; }
    public function esc_like( $s ) { return $s; }
    public function get_results( $sql, $format = null ) {
        if ( 'sql_empty' === self::$mode ) {
            $this->last_error = 'simulated database table read failure';
            return array();
        }
        if ( 'non_array' === self::$mode ) { return false; }
        // For stale_error, a valid new SQL read should clear any prior diagnostic
        // BEFORE get_results() is invoked. WordPress can leave $last_error stale.
        return array();
    }
}
$GLOBALS['wpdb'] = new V123_Collection_DB();
require dirname( __DIR__ ) . '/includes/class-wca-repository.php';
$cases = array(
    'clinic discovery' => array( 'method' => 'list_clinics', 'args' => array( array( 'hydrate_children' => false ) ), 'code' => 'wca_clinic_list_read_failed' ),
    'clinic branches' => array( 'method' => 'list_branches', 'args' => array( 77, false ), 'code' => 'wca_branch_list_read_failed' ),
    'clinic services' => array( 'method' => 'list_services', 'args' => array( 77, false ), 'code' => 'wca_service_list_read_failed' ),
    'doctor availability' => array( 'method' => 'list_availability_rules', 'args' => array( 13, 0, 77 ), 'code' => 'wca_availability_list_read_failed' ),
);
$checks = 0;
$assert = static function ( $ok, $name ) use ( &$checks ) {
    $checks++;
    if ( ! $ok ) { fwrite( STDERR, "V12.3 collection read FAIL {$checks}: {$name}\n" ); exit( 1 ); }
};
foreach ( $cases as $label => $case ) {
    foreach ( array( 'valid_empty', 'stale_error', 'sql_empty', 'non_array' ) as $mode ) {
        WCA_Repository::clear_read_error();
        V123_Collection_DB::$mode = $mode;
        $GLOBALS['wpdb']->last_error = 'stale error from a previous read';
        $result = call_user_func_array( array( 'WCA_Repository', $case['method'] ), $case['args'] );
        $found = WCA_Repository::consume_read_error();
        $assert( is_array( $result ) && 0 === count( $result ), $label . ' retains collection return type: ' . $mode );
        if ( 'sql_empty' === $mode || 'non_array' === $mode ) {
            $assert( $found instanceof WP_Error && $case['code'] === $found->code,
                $label . ' must produce typed 503 source read failure: ' . $mode );
            $assert( is_array( $found->data ) && 503 === (int) $found->data['status'],
                $label . ' read fault must be retriable server-side 503' );
        } else {
            $assert( null === $found, $label . ' legitimate empty result cannot be a SQL failure: ' . $mode );
        }
    }
}
echo "PASS: {$checks} V12.3 actual repository collection-read source assertions; NOT staging or Live.\n";
