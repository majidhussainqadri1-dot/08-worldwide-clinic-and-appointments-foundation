<?php
/** V12.3 actual pre-purge legal-hold inventory rejection; SQL transport stub, no deletion. */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
function __( $s, $domain = '' ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function get_current_user_id() { return 5; }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function apply_filters( $name, $value, ...$args ) {
    if ( 'wca_purge_backup_verified' === $name ) { return V123_Purge_DB::$backup; }
    return $value;
}
final class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $msg = '', $data = null ) {
        $this->code = $code; $this->message = $msg; $this->data = $data;
    }
}
final class WCA_Future24 {}
final class SWC_Helpers { const TYPE = 'swc_appointment'; }
final class V123_Purge_DB {
    public $last_error = '';
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $prefix = 'wp_';
    public static $mode = 'valid';
    public static $backup = true;
    public static $reads = 0;
    public function prepare( $sql ) { return (string) $sql; }
    public function get_col( $sql ) {
        self::$reads++;
        if ( self::$mode === 'appointment_false' ) { return false; }
        if ( self::$mode === 'appointment_sql_error' ) { $this->last_error = 'SQL appointment inventory failed'; return array(); }
        if ( self::$mode === 'held' ) { return array( 23 ); }
        return array();
    }
    public function get_var( $sql ) {
        self::$reads++;
        if ( false !== strpos( $sql, 'SHOW TABLES' ) ) { return 'wp_wca_future24_records'; }
        return self::$mode === 'held' ? '1' : null;
    }
    public function get_results( $sql, $mode = null ) {
        self::$reads++;
        if ( self::$mode === 'future_false' ) { return false; }
        if ( self::$mode === 'future_sql_error' ) { $this->last_error = 'Future24 legal hold inventory lost'; return array(); }
        return array();
    }
}
$GLOBALS['wpdb'] = new V123_Purge_DB();
require dirname( __DIR__ ) . '/includes/class-wca-privacy.php';
$tests = array(
    array( 'valid', true, 'ok' ),
    array( 'stale', true, 'ok' ),
    array( 'appointment_false', true, 'wca_purge_hold_inventory_failed' ),
    array( 'future_false', true, 'wca_purge_future24_hold_inventory_failed' ),
    array( 'appointment_sql_error', true, 'wca_purge_hold_inventory_failed' ),
    array( 'future_sql_error', true, 'wca_purge_future24_hold_inventory_failed' ),
    array( 'held', true, 'wca_purge_legal_hold' ),
    array( 'valid', false, 'wca_purge_backup_unverified' ),
);
$checks = 0;
foreach ( $tests as $test ) {
    list( $mode, $backup, $expected ) = $test;
    V123_Purge_DB::$mode = $mode;
    V123_Purge_DB::$backup = $backup;
    V123_Purge_DB::$reads = 0;
    $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'unrelated stale SQL marker' : '';
    $actual = WCA_Privacy::assert_purge_allowed( 5 );
    $actual_code = is_wp_error( $actual ) ? $actual->code : ( true === $actual ? 'ok' : 'unexpected' );
    if ( $actual_code !== $expected ) {
        fwrite( STDERR, "FAIL: {$mode} backup=" . ( $backup ? 'yes' : 'no' ) . " expected {$expected}, got {$actual_code}\n" );
        exit( 1 );
    }
    $checks++;
    if ( ! $backup && V123_Purge_DB::$reads !== 0 ) {
        fwrite( STDERR, "FAIL: preflight read patient data before backup verification\n" );
        exit( 1 );
    }
    if ( is_wp_error( $actual ) && 'wca_purge_backup_unverified' !== $actual->code
        && ! is_array( $actual->data ) ) {
        fwrite( STDERR, "FAIL: typed purge inventory errors require status metadata\n" );
        exit( 1 );
    }
}
echo "PASS: {$checks} real File08 legal-hold preflight source cases; no DB mutation/staging/live.\n";
