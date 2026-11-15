<?php
/**
 * V12.3 FR002 real WCA_Repository::list_services native test.
 * Stubs only WordPress DB transport; this is source QA, not staging.
 */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) {
        throw new ErrorException( $message, 0, $severity );
    }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
defined( 'ARRAY_A' ) || define( 'ARRAY_A', 'ARRAY_A' );
if ( ! function_exists( 'absint' ) ) {
    function absint( $v ) { return abs( (int) $v ); }
}
final class WCA_Schema {
    public static function tables() { return array( 'services' => 'wp_wca_services' ); }
}
final class V123_Services_DB {
    public $last_error = '';
    public $query = '';
    public $records = array();
    public function prepare( $sql, $values ) {
        return vsprintf( $sql, (array) $values );
    }
    public function get_results( $sql, $format = null ) {
        $this->query = $sql;
        if ( ! preg_match( '/\bLIMIT\s+(\d+)\s*$/', trim( $sql ), $m ) ) {
            throw new RuntimeException( 'Services query lacks bounded LIMIT' );
        }
        return array_slice( $this->records, 0, (int) $m[1] );
    }
}
$GLOBALS['wpdb'] = new V123_Services_DB();
$GLOBALS['wpdb']->records = array(
    array( 'id' => 10, 'name' => 'Constitutional visit', 'clinic_id' => 77 ),
    array( 'id' => 11, 'name' => 'Acute consultation', 'clinic_id' => 77 ),
    array( 'id' => 12, 'name' => 'Follow-up visit', 'clinic_id' => 77 ),
);
require dirname( __DIR__ ) . '/includes/class-wca-repository.php';
$rows = WCA_Repository::list_services( 77, false );
if ( ! is_array( $rows ) || count( $rows ) !== 3 ) {
    fwrite( STDERR, 'FAIL: three existing services must not be silently truncated; got ' . count( (array) $rows ) . "\n" );
    exit( 1 );
}
if ( false === strpos( $GLOBALS['wpdb']->query, 'LIMIT 100' ) ) {
    fwrite( STDERR, "FAIL: default service collection must use bounded 100, not 1\n" );
    exit( 1 );
}
if ( ! in_array( 'Follow-up visit', array_column( $rows, 'name' ), true ) ) {
    fwrite( STDERR, "FAIL: third service is missing\n" );
    exit( 1 );
}
echo "PASS: all 3 real repository services returned with bounded LIMIT 100 and no PHP diagnostics.\n";
