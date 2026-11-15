<?php
/**
 * File08 V12.3 actual SWC_Helpers strict legacy audit read SQL integrity.
 * Synthetic WPDB adapter only; no personal data, Staging or Live.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
function __( $s, $domain = '' ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
final class WP_Error {
    public $code; public $data;
    public function __construct( $code, $message = '', $data = null ) { $this->code = $code; $this->data = $data; }
}
final class V123_Legacy_Audit_DB {
    public $prefix = 'wp_'; public $last_error = '';
    public static $mode = 'valid';
    public function prepare( $sql, $id ) { return (string) $sql; }
    public function get_results( $sql ) {
        if ( self::$mode === 'sql_error' ) { $this->last_error = 'simulated private legacy audit read failure'; return array(); }
        if ( self::$mode === 'invalid' ) { return false; }
        if ( self::$mode === 'rows' ) { return array( (object) array( 'id' => 15, 'event' => 'requested' ) ); }
        return array();
    }
}
$GLOBALS['wpdb'] = new V123_Legacy_Audit_DB();
require dirname( __DIR__ ) . '/includes/class-swc-helpers.php';
$checks = 0;
$check = static function ( $pass, $description ) use ( &$checks ) {
    $checks++;
    if ( ! $pass ) { fwrite( STDERR, "V12.3 legacy audit read FAIL {$checks}: {$description}\n" ); exit( 1 ); }
};
foreach ( array( 'valid', 'stale', 'sql_error', 'invalid', 'rows' ) as $case ) {
    V123_Legacy_Audit_DB::$mode = $case;
    $GLOBALS['wpdb']->last_error = $case === 'stale' ? 'unrelated previous query error' : '';
    $result = SWC_Helpers::audit_rows_strict( 77 );
    if ( $case === 'sql_error' || $case === 'invalid' ) {
        $check( $result instanceof WP_Error && $result->code === 'swc_audit_read_failed',
            'unreadable audit history must be a typed failure, never an empty export: ' . $case );
        $check( is_array( $result->data ) && (int) ( $result->data['status'] ?? 0 ) === 500,
            'invalid audit read must preserve source error status: ' . $case );
    } elseif ( $case === 'rows' ) {
        $check( is_array( $result ) && count( $result ) === 1
            && $result[0] instanceof stdClass && $result[0]->event === 'requested',
            'valid legacy audit object survives strict read' );
    } else {
        $check( is_array( $result ) && count( $result ) === 0,
            'valid empty/stale marker does not create false failure: ' . $case );
    }
}
$privacy = file_get_contents( dirname( __DIR__ ) . '/includes/class-swc-privacy.php' );
$check( is_string( $privacy ) && false !== strpos( $privacy, 'SWC_Helpers::audit_rows_strict( $appointment_id )' )
    && false !== strpos( $privacy, 'if ( is_wp_error( $audit_rows ) ) { return $audit_rows; }' ),
    'private export requires strict audit reader and propagates storage failure' );
echo "PASS: {$checks} actual File08 private legacy audit integrity checks; no Staging/Live.\n";
