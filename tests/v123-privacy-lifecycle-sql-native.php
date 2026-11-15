<?php
/**
 * V12.3 real WCA_Privacy export/erase/retention SQL integrity regression.
 * Actual production methods with fake WordPress DB transport; NOT staging/live.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
defined( 'DAY_IN_SECONDS' ) || define( 'DAY_IN_SECONDS', 86400 );
function __( $s, $domain = '' ) { return $s; }
function absint( $v ) { return abs( (int) $v ); }
function sanitize_key( $v ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $v ) ); }
function sanitize_email( $v ) { return trim( $v ); }
function sanitize_text_field( $v ) { return trim( (string) $v ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function get_user_by( $field, $value ) { return (object) array( 'ID' => 7 ); }
function get_user_meta( $user_id, $key, $single = true ) { return ''; }
function get_option( $name, $default = array() ) { return array(); }
function wp_parse_args( $args, $defaults ) { return array_merge( $defaults, $args ); }
function get_transient( $key ) { return 0; }
function delete_transient( $key ) { return true; }
function set_transient( $key, $value, $ttl ) { return true; }
final class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code, $message = '', $data = null ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
final class SWC_Helpers { const TYPE = 'swc_appointment'; }
final class WCA_Future24 {}
final class WCA_Repository { public static function now() { return '2026-11-15 02:00:00'; } }
final class WCA_Observability { public static function log( $level, $code, $meta = array() ) {} }
final class WCA_Schema {
    public static function tables() {
        return array( 'outbox' => 'wp_wca_outbox', 'idempotency' => 'wp_wca_idempotency', 'metrics' => 'wp_wca_metrics' );
    }
}
final class V123_Privacy_DB {
    public $last_error = '';
    public $prefix = 'wp_';
    public $posts = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public static $mode = 'valid';
    public function prepare( $sql ) { return (string) $sql; }
    public function query( $sql ) { return 0; }
    public function get_col( $sql ) { return array(); }
    public function get_var( $sql ) {
        return false !== stripos( $sql, 'SHOW TABLES' ) ? 'wp_wca_future24_records' : null;
    }
    public function get_results( $sql, $format = null ) {
        if ( 'sql_empty' === self::$mode ) { $this->last_error = 'simulated temporary SQL outage'; return array(); }
        if ( 'non_array' === self::$mode ) { return false; }
        return array();
    }
}
$GLOBALS['wpdb'] = new V123_Privacy_DB();
require dirname( __DIR__ ) . '/includes/class-wca-privacy.php';
$checks = 0;
$check = static function ( $ok, $name ) use ( &$checks ) {
    $checks++;
    if ( ! $ok ) { fwrite( STDERR, "V12.3 privacy SQL FAIL {$checks}: {$name}\n" ); exit( 1 ); }
};
foreach ( array( 'valid', 'sql_empty', 'non_array', 'stale' ) as $mode ) {
    V123_Privacy_DB::$mode = $mode;
    $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'previous unrelated query error' : '';
    $export = WCA_Privacy::export( 'patient@example.test' );
    if ( 'sql_empty' === $mode || 'non_array' === $mode ) {
        $check( $export instanceof WP_Error && 'wca_privacy_export_future24_query' === $export->code,
            $mode . ' privacy export must report a typed failure, never done=true' );
    } else {
        $check( is_array( $export ) && true === $export['done'], $mode . ' legitimate empty privacy export is complete' );
    }

    V123_Privacy_DB::$mode = $mode;
    $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'previous unrelated query error' : '';
    $erase = WCA_Privacy::erase( 'patient@example.test' );
    if ( 'sql_empty' === $mode || 'non_array' === $mode ) {
        $check( is_array( $erase ) && false === $erase['done']
            && ! empty( $erase['messages'] ), $mode . ' privacy erasure cannot silently declare complete' );
    } else {
        $check( is_array( $erase ) && true === $erase['done'], $mode . ' clean empty privacy erasure completes' );
    }

    V123_Privacy_DB::$mode = $mode;
    $GLOBALS['wpdb']->last_error = 'stale' === $mode ? 'previous unrelated query error' : '';
    $retention = WCA_Privacy::apply_retention();
    if ( 'sql_empty' === $mode || 'non_array' === $mode ) {
        $check( $retention instanceof WP_Error && 'wca_retention_future24_read_failed' === $retention->code,
            $mode . ' retention cannot declare success if affected rows are unreadable' );
    } else {
        $check( true === $retention, $mode . ' valid empty retention returns true' );
    }
}
echo "PASS: {$checks} WCA_Privacy actual method checks on V12.3; source-only fixtures.\n";
