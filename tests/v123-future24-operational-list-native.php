<?php
/** File08 V12.3 real Future24 waitlist and clinic-policy list DB failures; no WordPress host state. */
error_reporting( E_ALL );
set_error_handler( static function ( $level, $message ) {
    if ( error_reporting() & $level ) { throw new ErrorException( $message, 0, $level ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
function __( $v, $domain = '' ) { return $v; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function sanitize_key( $v ) { return strtolower( trim( (string) $v ) ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
final class WP_Error {
    public $code; public $data; public $message;
    public function __construct( $code, $message = '', $data = null ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
final class WCA_Repository { public static function now() { return '2026-11-15 03:00:00'; } }
final class V123_Future_List_DB {
    public $prefix = 'wp_';
    public $last_error = '';
    public static $scenario = 'valid';
    public static $reads = 0;
    public function prepare( $sql ) { return (string) $sql; }
    public function get_results( $sql, $mode = null ) {
        self::$reads++;
        if ( 'first_error' === self::$scenario ||
            ( 'second_error' === self::$scenario && self::$reads === 2 ) ) {
            $this->last_error = 'simulated SQL timeout';
            return array();
        }
        if ( 'non_array' === self::$scenario ) { return false; }
        if ( 'bad_row' === self::$scenario ) { return array( false ); }
        if ( 'second_error' === self::$scenario && self::$reads === 1 ) {
            $rows = array();
            for ( $i = 1; $i <= 100; $i++ ) {
                $rows[] = array( 'id' => $i, 'payload_json' => '{}' );
            }
            return $rows;
        }
        return array();
    }
}
$GLOBALS['wpdb'] = new V123_Future_List_DB();
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';
$waitlist = new ReflectionMethod( 'WCA_Future24', 'waitlist_candidates' );
$waitlist->setAccessible( true );
$policy = new ReflectionMethod( 'WCA_Future24', 'feature_rows_for_clinic' );
$policy->setAccessible( true );
$checks = 0;
$check = static function ( $pass, $label ) use ( &$checks ) {
    $checks++;
    if ( ! $pass ) { fwrite( STDERR, "File08 FUT list FAIL {$checks}: {$label}\n" ); exit( 1 ); }
};
foreach ( array( 'valid', 'stale', 'first_error', 'second_error', 'non_array', 'bad_row' ) as $scenario ) {
    foreach ( array( 'waitlist', 'policy' ) as $kind ) {
        V123_Future_List_DB::$scenario = $scenario;
        V123_Future_List_DB::$reads = 0;
        $GLOBALS['wpdb']->last_error = 'stale' === $scenario ? 'unrelated previous SQL error' : '';
        if ( 'waitlist' === $kind ) {
            $threw = false; $rows = array();
            try {
                $generator = $waitlist->invoke( null, 77 );
                foreach ( $generator as $row ) { $rows[] = $row; }
            } catch ( RuntimeException $error ) { $threw = 'wca_waitlist_candidate_read_failed' === $error->getMessage(); }
            $want_error = ! in_array( $scenario, array( 'valid', 'stale' ), true );
            $check( $want_error ? $threw : ( ! $threw && array() === $rows ),
                'waitlist ' . $scenario . ' must not falsely return an empty/partial successful collection' );
        } else {
            $result = $policy->invoke( null, 'F08-FUT-11', 77, 'template_active' );
            $want_error = ! in_array( $scenario, array( 'valid', 'stale' ), true );
            $check( $want_error
                ? $result instanceof WP_Error && 'wca_future24_policy_read_failed' === $result->code
                : is_array( $result ) && array() === $result,
                'clinic policy ' . $scenario . ' must propagate typed source-read failures' );
        }
        if ( 'second_error' === $scenario ) {
            $check( 2 === V123_Future_List_DB::$reads, $kind . ' second-page error must actually be exercised' );
        }
    }
}
echo "PASS: {$checks} real Future24 paged waitlist/policy source checks; NOT Hostinger staging/live.\n";
