<?php
/**
 * File08 V12.3 actual SWC_Admin::audit_timeline states with synthetic private reader.
 * No WordPress installation / patient data / DB writes.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $msg ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $msg, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
function __( $s, $domain = '' ) { return $s; }
function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
function esc_html_e( $s, $domain = '' ) { echo esc_html( $s ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
final class WP_Error {
    public $code;
    public function __construct( $code ) { $this->code = $code; }
}
final class SWC_Helpers {
    public static $mode = 'error';
    public static function audit_rows_strict( $id ) {
        if ( self::$mode === 'error' ) { return new WP_Error( 'swc_audit_read_failed' ); }
        if ( self::$mode === 'event' ) {
            return array( (object) array(
                'event' => 'appointment_requested', 'action' => 'request', 'created_at' => '2026-11-15 09:00:00',
                'old_status' => '', 'new_status' => 'requested', 'reason' => 'Synthetic test event',
            ) );
        }
        return array();
    }
    public static function audit_rows( $id ) {
        $rows = self::audit_rows_strict( $id );
        return is_wp_error( $rows ) ? array() : $rows;
    }
}
require dirname( __DIR__ ) . '/includes/class-swc-admin.php';
$admin = new SWC_Admin();
$method = new ReflectionMethod( 'SWC_Admin', 'audit_timeline' );
$method->setAccessible( true );
$cases = array(
    'error' => array( 'Audit history is temporarily unavailable', false ),
    'empty' => array( 'No audit entries were found', true ),
    'event' => array( 'appointment_requested', true ),
);
$count = 0;
foreach ( $cases as $mode => $rule ) {
    SWC_Helpers::$mode = $mode;
    ob_start(); $method->invoke( $admin, 77 ); $html = ob_get_clean();
    if ( false === strpos( $html, $rule[0] ) ) {
        fwrite( STDERR, "FAIL: {$mode} admin audit timeline did not render expected truthful state\n" );
        exit( 1 );
    }
    $count++;
    if ( 'error' === $mode ) {
        if ( false === strpos( $html, 'role="alert"' )
            || false !== strpos( $html, 'No audit entries were found' )
            || false !== strpos( $html, 'swc_audit_read_failed' ) ) {
            fwrite( STDERR, "FAIL: admin audit SQL outage must show accessible non-leaking error\n" );
            exit( 1 );
        }
        $count++;
    } elseif ( 'event' === $mode ) {
        if ( false === strpos( $html, 'UTC' ) || false === strpos( $html, 'requested' ) ) {
            fwrite( STDERR, "FAIL: existing genuine audit event rendering changed\n" );
            exit( 1 );
        }
        $count++;
    }
}
echo "PASS: {$count} actual Admin audit-state safety checks; NOT Staging/Live.\n";
