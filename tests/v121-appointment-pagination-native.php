<?php
/**
 * V12.1 fail-first native method regression for File08 FUT08/FUT10/FUT17.
 * Actual production WCA_Future24 private enumerator invoked by Reflection.
 * Simulates precise WordPress page shape/error semantics; NOT WordPress staging.
 */
if ( ! class_exists( 'WP_Error' ) ) {
    class WP_Error {
        public $code; public $message; public $data;
        public function __construct( $code = '', $message = '', $data = null ) {
            $this->code = $code; $this->message = $message; $this->data = $data;
        }
    }
}
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
}
final class SWC_Helpers { const TYPE = 'swc_appointment'; }
final class V121_Paging_DB { public $last_error = ''; }
$GLOBALS['wpdb'] = new V121_Paging_DB();
final class WP_Query {
    public $posts;
    public static $scenario = 'empty';
    public static $calls = 0;
    public function __construct( $args ) {
        $p = (int) ( $args['paged'] ?? 1 );
        self::$calls++;
        $GLOBALS['wpdb']->last_error = '';
        if ( self::$scenario === 'normal' ) {
            $this->posts = ( $p === 1 ) ? array( 1, 2 ) : array();
        } elseif ( self::$scenario === 'two-pages' ) {
            $this->posts = $p === 1 ? range( 1, 200 ) : ( $p === 2 ? array( 201, 202 ) : array() );
        } elseif ( self::$scenario === 'late-db-error' ) {
            if ( $p === 1 ) { $this->posts = range( 1, 200 ); }
            else { $this->posts = array(); $GLOBALS['wpdb']->last_error = 'SQL access lost after first page'; }
        } elseif ( self::$scenario === 'invalid-posts' ) {
            $this->posts = false;
        } elseif ( self::$scenario === 'invalid-id' ) {
            $this->posts = array( 17, 'not-an-id' );
        } elseif ( self::$scenario === 'max-pages' ) {
            $this->posts = range( 1 + ( $p - 1 ) * 200, $p * 200 );
        } else {
            $this->posts = array();
        }
    }
}
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';

$m = new ReflectionMethod( 'WCA_Future24', 'clinic_appointments_between_all' );
// PHP 7.4 requires explicit accessibility; PHP 8.3 accepts the same call.
$m->setAccessible( true );
$assertions = 0;
$check = static function ( $truth, $message ) use ( &$assertions ) {
    $assertions++;
    if ( ! $truth ) {
        fwrite( STDERR, "V12.1 paging FAIL {$assertions}: {$message}\n" ); exit( 1 );
    }
};
$enumerate = static function ( $scenario ) use ( $m ) {
    WP_Query::$scenario = $scenario;
    WP_Query::$calls = 0;
    return $m->invoke( null, 77, '2026-11-01 00:00:00', '2026-12-01 00:00:00' );
};
$zero = $enumerate( 'empty' );
$check( is_array( $zero ) && $zero === array(), 'valid empty appointment set remains empty' );
$normal = $enumerate( 'normal' );
$check( is_array( $normal ) && $normal === array( 1, 2 ), 'one valid appointment page' );
$two = $enumerate( 'two-pages' );
$check( is_array( $two ) && count( $two ) === 202 && $two[200] === 201 && $two[201] === 202,
    'last page is included in successful bounded multi-page result' );
$late = $enumerate( 'late-db-error' );
$check( is_wp_error( $late ) && $late->code === 'wca_disruption_appointment_page_read_failed',
    'failed later SQL page cannot be mistaken for fully enumerated appointments' );
$malformed = $enumerate( 'invalid-posts' );
$check( is_wp_error( $malformed ) && $malformed->code === 'wca_disruption_appointment_page_read_failed',
    'non-array WP_Query posts cannot become an empty success' );
$invalid = $enumerate( 'invalid-id' );
$check( is_wp_error( $invalid ) && $invalid->code === 'wca_disruption_appointment_page_invalid',
    'malformed or zero native appointment IDs must fail closed' );
$bounded = $enumerate( 'max-pages' );
$check( is_wp_error( $bounded ) && $bounded->code === 'wca_disruption_appointment_page_limit',
    'unbounded synchronous enumeration must fail explicitly instead of fabricating truncated set' );
$check( WP_Query::$calls === 250, 'large-page boundary is deterministic and bounded at 250 reads' );
echo "V12.1 real private enumerator page integrity PASS {$assertions}/{$assertions} (fixtures, not live)\n";
