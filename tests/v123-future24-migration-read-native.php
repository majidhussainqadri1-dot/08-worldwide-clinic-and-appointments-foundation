<?php
/**
 * File08 V12.3 FUT23 actual install_schema() fail-closed migration tests.
 * Uses fake DB transport and verified-schema stub; source QA, NOT host staging.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $level, $msg ) {
    if ( error_reporting() & $level ) { throw new ErrorException( $msg, 0, $level ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/fixtures/v123-future24-upgrade/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
function __( $text, $domain = '' ) { return $text; }
function absint( $v ) { return abs( (int) $v ); }
function is_wp_error( $v ) { return $v instanceof WP_Error; }
function get_post_field( $field, $id ) { return 7; }
function get_option( $key, $default = '' ) { return $default; }
function dbDelta( $sql ) { return array(); }
final class WP_Error {
    public $code; public $message; public $data;
    public function __construct( $code = '', $message = '', $data = null ) {
        $this->code = $code; $this->message = $message; $this->data = $data;
    }
}
final class WCA_Schema {
    public static function verify_definition_sql( $sql ) { return true; }
}
final class SWC_Helpers {
    public static $option_writes = 0;
    public static function meta( $id, $key, $fallback = null ) { return 7; }
    public static function update_option_strict( $key, $value, $error_code = '' ) {
        self::$option_writes++;
        return true;
    }
}
final class V123_Migration_DB {
    public $prefix = 'wp_';
    public $last_error = '';
    public $updates = 0;
    public static $scenario = 'empty';
    public static $reads = 0;
    public function get_charset_collate() { return 'DEFAULT CHARSET=utf8mb4'; }
    public function esc_like( $value ) { return $value; }
    public function prepare( $sql, ...$args ) {
        if ( preg_match( '/id>%d/', $sql ) ) {
            return str_replace( 'id>%d', 'id>' . (int) ( $args[0] ?? 0 ), $sql );
        }
        return (string) $sql;
    }
    public function get_var( $sql ) { return 'wp_wca_future24_records'; }
    public function get_results( $sql, $format = null ) {
        self::$reads++;
        if ( self::$scenario === 'sql_first' ||
            ( self::$scenario === 'sql_second' && self::$reads === 2 ) ) {
            $this->last_error = 'simulated Future24 episode table read fault';
            return array();
        }
        if ( self::$scenario === 'non_array' ) { return false; }
        if ( self::$scenario === 'sql_second' && self::$reads === 1 ) {
            $rows = array();
            for ( $i = 1; $i <= 200; $i++ ) {
                $rows[] = array( 'id' => $i, 'appointment_id' => $i );
            }
            return $rows;
        }
        return array();
    }
    public function update( $table, $data, $where, $format = null, $where_format = null ) {
        $this->updates++;
        return 1;
    }
}
$GLOBALS['wpdb'] = new V123_Migration_DB();
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';
$check_count = 0;
$check = static function ( $ok, $label ) use ( &$check_count ) {
    $check_count++;
    if ( ! $ok ) {
        fwrite( STDERR, "V12.3 migration FAIL {$check_count}: {$label}\n" ); exit( 1 );
    }
};
foreach ( array( 'empty', 'stale', 'sql_first', 'sql_second', 'non_array' ) as $scenario ) {
    V123_Migration_DB::$scenario = $scenario;
    V123_Migration_DB::$reads = 0;
    SWC_Helpers::$option_writes = 0;
    $GLOBALS['wpdb']->updates = 0;
    $GLOBALS['wpdb']->last_error = 'stale' === $scenario ? 'old previous SQL error' : '';
    $threw = false;
    try { WCA_Future24::install_schema(); } catch ( RuntimeException $error ) { $threw = true; }
    if ( in_array( $scenario, array( 'sql_first', 'sql_second', 'non_array' ), true ) ) {
        $check( $threw, $scenario . ' must throw, never silently commit migration' );
        $check( 0 === SWC_Helpers::$option_writes, $scenario . ' must not mark schema current' );
    } else {
        $check( ! $threw, $scenario . ' actual empty migration succeeds' );
        $check( 1 === SWC_Helpers::$option_writes, $scenario . ' commits version exactly once' );
    }
    if ( 'sql_second' === $scenario ) {
        $check( V123_Migration_DB::$reads === 2, 'second-page fault reached after 200 legacy episodes' );
        $check( $GLOBALS['wpdb']->updates === 200, 'first page processes all 200 records, then blocks version commit' );
    }
}
echo "PASS: {$check_count} actual Future24 migration read/marker safety assertions; source fixtures, NOT host.\n";
