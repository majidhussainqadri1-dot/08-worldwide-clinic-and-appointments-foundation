<?php
/**
 * V12.3 FUT07 native method regression — REAL WCA_Future24 runtime code.
 * WordPress WP_Query/DB edge behavior is simulated; NOT Staging/Live.
 */
// Declare native test WP_Error fields explicitly for PHP 8.2+ diagnostic-clean CI.
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $code; public $message; public $data;
        public function __construct( $code = '', $message = '', $data = null ) {
            $this->code = $code; $this->message = $message; $this->data = $data;
        }
    }
}
require __DIR__ . '/bootstrap.php';
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
if ( ! defined( 'DAY_IN_SECONDS' ) ) { define( 'DAY_IN_SECONDS', 86400 ); }
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
}
final class WCA_Repository {
    public static function get_clinic( $ref, $by_ref = false ) { return array( 'id' => 77, 'public_ref' => 'clinic-ref' ); }
}
final class WCA_Plan_Guard {
    public static function practitioner_id( $ref ) { return 123; }
}
final class WCA_Service {
    public static function strict_int( $value, $min, $max ) {
        if ( ! is_int( $value ) && ( ! is_string( $value ) || ! preg_match( '/^-?\d+$/D', $value ) ) ) { return null; }
        $n = filter_var( $value, FILTER_VALIDATE_INT );
        return false !== $n && $n >= $min && $n <= $max ? $n : null;
    }
}
final class SWC_Helpers {
    const TYPE = 'swc_appointment';
    public static function status( $id ) { return 'cancelled'; }
    public static function meta( $id, $key, $default = '' ) {
        if ( 'preferred_at_utc' === $key ) { return '2026-11-25 07:00:00'; }
        if ( 'appointment_end_utc' === $key ) { return '2026-11-25 07:30:00'; }
        return $default;
    }
}
final class V123_Buffer_DB {
    public $last_error = '';
    public $prefix = 'wp_';
    public static $policy = 'valid';
    public function prepare( $sql ) { return $sql; }
    public function get_row( $query, $format = null ) {
        $this->last_error = '';
        if ( self::$policy === 'db-error' ) { $this->last_error = 'policy table unreachable'; return null; }
        if ( self::$policy === 'absent' ) { return null; }
        if ( self::$policy === 'malformed-json' ) { return array( 'payload_json' => '{bad json' ); }
        if ( self::$policy === 'invalid-buffer' ) { return array( 'payload_json' => '{"buffer_before":"not-a-number"}' ); }
        return array( 'payload_json' => '{"buffer_before":30,"buffer_after":0,"travel_gap_minutes":0,"max_continuous_consultations":0}' );
    }
}
$GLOBALS['wpdb'] = new V123_Buffer_DB();
final class WP_Query {
    public $posts;
    public static $scenario = 'valid-empty';
    public static $calls = 0;
    public function __construct( $args ) {
        $page = (int) ( $args['paged'] ?? 1 );
        self::$calls++;
        if ( self::$scenario === 'first-page-sql-error' ) {
            $this->posts = array(); $GLOBALS['wpdb']->last_error = 'query failed';
        } elseif ( self::$scenario === 'second-page-sql-error' ) {
            $this->posts = 1 === $page ? range(1,200) : array();
            if ( 2 === $page ) { $GLOBALS['wpdb']->last_error = 'second page failed'; }
        } elseif ( self::$scenario === 'malformed-posts' ) {
            $this->posts = false;
        } elseif ( self::$scenario === 'malformed-id' ) {
            $this->posts = array( 1, 'oops' );
        } elseif ( self::$scenario === 'many-pages' ) {
            $this->posts = range( 1 + ( $page - 1 ) * 200, $page * 200 );
        } else {
            $this->posts = array();
        }
    }
}
final class WP_REST_Request {
    public function get_route() { return '/wca/v1/slots'; }
}
final class V123_Slot_Response {
    public $data;
    public function __construct( $slots ) { $this->data = array( 'slots' => $slots ); }
    public function get_data() { return $this->data; }
    public function set_data( $data ) { $this->data = $data; }
}
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';

$m = new ReflectionMethod( 'WCA_Future24', 'slot_allowed_by_policy' );
$m->setAccessible( true );
$guard = new ReflectionMethod( 'WCA_Future24', 'guard_slot_hold_data' );
$guard->setAccessible( true );
$slot = array(
    'clinic_ref' => 'clinic-ref', 'practitioner_ref' => 'doc-ref', 'branch_ref' => 'branch-ref',
    'start_utc' => '2026-11-25 10:00:00', 'end_utc' => '2026-11-25 10:30:00'
);
$checks = 0;
$check = static function ( $truth, $label ) use ( &$checks ) {
    $checks++;
    if ( ! $truth ) { fwrite( STDERR, "V12.3 FUT07 FAIL {$checks}: {$label}\n" ); exit( 1 ); }
};
$run = static function ( $policy, $scenario ) use ( $m, $slot ) {
    V123_Buffer_DB::$policy = $policy; WP_Query::$scenario = $scenario; WP_Query::$calls = 0;
    $GLOBALS['wpdb']->last_error = '';
    return $m->invoke( null, $slot );
};
$check( true === $run( 'absent', 'valid-empty' ), 'no policy is not a failure' );
$check( true === $run( 'valid', 'valid-empty' ), 'valid zero-conflict policy stays usable' );
$x = $run( 'db-error', 'valid-empty' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_read_failed' === $x->code, 'failed policy read has typed 503, not false-zero slot' );
$x = $run( 'malformed-json', 'valid-empty' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_corrupt' === $x->code, 'malformed active JSON never removes all restrictions' );
$x = $run( 'invalid-buffer', 'valid-empty' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_corrupt' === $x->code, 'malformed integer buffer never disables a policy' );
$x = $run( 'valid', 'first-page-sql-error' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_appointment_read_failed' === $x->code, 'first SQL page failure not treated as safe' );
$x = $run( 'valid', 'second-page-sql-error' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_appointment_read_failed' === $x->code && WP_Query::$calls === 2, 'second SQL page failure not treated as safe' );
$x = $run( 'valid', 'malformed-posts' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_appointment_read_failed' === $x->code, 'invalid posts return not silently empty' );
$x = $run( 'valid', 'malformed-id' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_appointment_invalid_id' === $x->code, 'malformed appointment ID is not ignored' );
$x = $run( 'valid', 'many-pages' );
$check( is_wp_error( $x ) && 'wca_buffer_policy_appointment_page_limit' === $x->code && WP_Query::$calls === 250, '250-page ceiling explicit, not unbounded' );

V123_Buffer_DB::$policy = 'valid'; WP_Query::$scenario = 'first-page-sql-error';
$blocked = $guard->invoke( null, $slot );
$check( is_wp_error( $blocked ) && 'wca_buffer_policy_appointment_read_failed' === $blocked->code, 'hold guard preserves typed SQL fault' );
$response = new V123_Slot_Response( array( $slot ) );
$blocked = WCA_Future24::apply_slot_policies_to_rest( $response, null, new WP_REST_Request() );
$check( is_wp_error( $blocked ) && 'wca_buffer_policy_appointment_read_failed' === $blocked->code, 'search REST remains typed unavailable rather than empty fabricated slots' );
echo "PASS: V12.3 FUT07 native policy integrity {$checks}/{$checks}, source-only fixtures.\n";
