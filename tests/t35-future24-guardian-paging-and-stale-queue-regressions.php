<?php
/**
 * T35: execute actual Future24::queue_position() and family_hub() against
 * deterministic WP_Query and DB fixtures. Not installed MySQL/browser testing.
 * PHP 7.4 and 8.3 compatible.
 */
require __DIR__ . '/bootstrap.php';
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $v ) { return trim( strip_tags( (string) $v ) ); }
}
if ( ! function_exists( 'get_current_user_id' ) ) {
    function get_current_user_id() { return 77; }
}
if ( ! function_exists( 'get_posts' ) ) {
    function get_posts( $args ) { return array( 51 ); }
}
if ( ! function_exists( 'get_post_field' ) ) {
    function get_post_field( $name, $id ) { return 23; }
}
final class T35_WPDB {
    public $prefix = 'wp_';
    public $last_error = '';
    public $reads = 0;
    public $queue_reads = 0;
    public $arrival = array( 'id' => 1, 'appointment_id' => 51, 'created_at' => '2026-10-14 00:10:00' );
    public $ahead = '2';
    public $last_ahead_sql = '';
    public $last_arrival_sql = '';
    public $fail_arrival = false;
    public $fail_ahead = false;
    public function prepare( $sql ) { return $sql; }
    public function get_row( $sql, $shape = null ) {
        $this->last_arrival_sql = $sql;
        $this->reads++; $this->queue_reads++;
        $this->last_error = $this->fail_arrival ? 'injected arrival SQL error' : '';
        return $this->fail_arrival ? null : $this->arrival;
    }
    public function get_var( $sql ) {
        $this->last_ahead_sql = $sql;
        $this->reads++; $this->queue_reads++;
        $this->last_error = $this->fail_ahead ? 'injected queue SQL error' : '';
        return $this->fail_ahead ? null : $this->ahead;
    }
}
$GLOBALS['wpdb'] = new T35_WPDB();
final class WCA_Repository {
    public static function now() { return '2026-10-14 00:20:00'; }
}
final class WCA_Authorization {
    public static $is_guardian = true;
    public static $deny = false;
    public static function claims( $actor ) { return array( 'guardian' => self::$is_guardian ); }
    public static function can_view_appointment( $appointment_id, $actor ) {
        return self::$deny ? new WP_Error( 'not_authorized', 'Restricted', array( 'status' => 403 ) ) : true;
    }
}
final class WCA_Central_Governance {
    public static $checked = array();
    public static $revoked = array();
    public static function validate_patient_guardian( $patient, $guardian, $actor ) {
        self::$checked[] = $patient;
        return in_array( $patient, self::$revoked, true )
            ? new WP_Error( 'guardian_revoked', 'Relation no longer current' ) : true;
    }
}
final class SWC_Helpers {
    const TYPE = 'wca_appointment';
    public static $status = 'confirmed';
    public static function status( $id ) { return self::$status; }
    public static function meta( $id, $name, $default = '' ) {
        if ( 'clinic_id' === $name ) { return 3; }
        if ( 'patient_user_id' === $name ) { return $id; }
        if ( 'public_ref' === $name ) { return sprintf( '00000000-0000-4000-8000-%012d', $id ); }
        if ( 'preferred_at_utc' === $name ) { return '2026-10-14 10:00:00'; }
        return $default;
    }
}
final class WP_Query {
    public $posts;
    public static $pages = array();
    public static $fail_page = 0;
    public static $bad_shape_page = 0;
    public static $requests = 0;
    public function __construct( $args ) {
        self::$requests++;
        $page = isset( $args['paged'] ) ? (int) $args['paged'] : 1;
        $GLOBALS['wpdb']->last_error = self::$fail_page === $page
            ? 'injected guardian page read error' : '';
        $this->posts = self::$bad_shape_page === $page
            ? null : ( isset( self::$pages[ $page ] ) ? self::$pages[ $page ] : array() );
    }
}
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';
$count = 0;
$test = static function( $ok, $label ) use ( &$count ) {
    if ( ! $ok ) { fwrite( STDERR, 'T35 FAIL: ' . $label . "\n" ); exit( 1 ); }
    $count++;
};
$ref = '00000000-0000-4000-8000-000000000051';
// FUT16: live appointment truth must precede any persisted arrival read.
foreach ( array( 'cancelled', 'completed', 'no_show', 'declined', 'requested', 'checked_in' ) as $status ) {
    SWC_Helpers::$status = $status;
    $before = $GLOBALS['wpdb']->reads;
    $result = WCA_Future24::queue_position( $ref, 77 );
    $test( is_wp_error( $result ) && $result->code === 'wca_queue_appointment_state',
        'stale lifecycle ' . $status . ' incorrectly returned live queue' );
    $test( $GLOBALS['wpdb']->reads === $before,
        'stale appointment accessed old arrival DB before lifecycle recheck' );
}
WCA_Authorization::$deny = true;
SWC_Helpers::$status = 'confirmed';
$before = $GLOBALS['wpdb']->reads;
$denied = WCA_Future24::queue_position( $ref, 77 );
$test( is_wp_error( $denied ) && $denied->code === 'not_authorized',
    'private queue returned unauthorized record' );
$test( $GLOBALS['wpdb']->reads === $before,
    'unauthorized queue requested private arrival DB' );
WCA_Authorization::$deny = false;
foreach ( array( 'confirmed', 'reschedule_pending' ) as $status ) {
    SWC_Helpers::$status = $status;
    $result = WCA_Future24::queue_position( $ref, 77 );
    $test( is_array( $result ) && $result['appointments_ahead'] === 2 && $result['estimated_delay_minutes'] === 30,
        'current allowed live appointment no longer returns estimated queue' );
    $test( $result['other_patient_identity_exposed'] === false &&
        ! array_key_exists( 'other_patient_refs', $result ), 'queue exposed other patient identifiers' );
}
// T36: execute actual queue_position and verify that its aggregate counts only live peers.
$peer_sql = $GLOBALS['wpdb']->last_ahead_sql;
$test( false !== strpos( $peer_sql, 'JOIN wp_posts' ) && false !== strpos( $peer_sql, 'JOIN wp_postmeta' ),
    'queue aggregation still relies solely on stale operational arrivals' );
$test( false !== strpos( $peer_sql, "status_meta.meta_key='_swc_status'" ) &&
    false !== strpos( $peer_sql, "status_meta.meta_value IN ('confirmed','accepted','reschedule_pending','reschedule-requested','reschedule_requested')" ),
    'queue aggregation does not constrain peers to current canonical/legacy live lifecycle states' );
$test( false !== strpos( $peer_sql, 'COUNT(DISTINCT arrival.appointment_id)' ) &&
    false !== strpos( $peer_sql, 'arrival.clinic_id=%d' ) &&
    false !== strpos( $peer_sql, '(arrival.expires_at IS NULL OR arrival.expires_at>%s)' ),
    'queue projection lost DISTINCT, clinic isolation, or arrival expiry while filtering peers' );

// T39 R36: historical arrival clinic alone must never authorize the live queue.
// Verify that queue_position's actual SQL gates both subject and peers on the
// appointment's CURRENT canonical clinic metadata.
$test( false !== strpos( $peer_sql, "clinic_meta.meta_key='_swc_clinic_id'" ) &&
    false !== strpos( $peer_sql, 'clinic_meta.meta_value=%s' ),
    'peer queue can still count appointments moved to another clinic' );
$test( false !== strpos( $GLOBALS['wpdb']->last_arrival_sql, 'AND clinic_id=%d' ),
    'subject queue lookup still reuses an operational arrival from a prior clinic' );
$t39_src = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-future24.php' );
$t39_start = strpos( $t39_src, 'public static function arrive(' );
$t39_end = strpos( $t39_src, 'public static function queue_position(', $t39_start );
$t39_arrive = substr( $t39_src, $t39_start, $t39_end - $t39_start );
$test( false !== strpos( $t39_arrive, "AND clinic_id=%d" ),
    'arrival deduplication can replay an expired-clinic operational signal after clinic transfer' );

$GLOBALS['wpdb']->arrival = null;
$r = WCA_Future24::queue_position( $ref, 77 );
$test( is_wp_error( $r ) && $r->code === 'wca_queue_not_arrived',
    'missing/expired arrival silently returned successful live queue' );
$GLOBALS['wpdb']->arrival = array( 'id' => 1, 'appointment_id' => 51, 'created_at' => '2026-10-14 00:10:00' );
$GLOBALS['wpdb']->fail_ahead = true;
$r = WCA_Future24::queue_position( $ref, 77 );
$test( is_wp_error( $r ) && $r->code === 'wca_queue_position_read_failed',
    'queue SQL failure returned fabricated 0 appointments ahead' );
$GLOBALS['wpdb']->fail_ahead = false;

// FUT14: guardian hub does not produce a successful truncated list after
// any failed DB page; must retain real File00 recheck per returned dependent.
WP_Query::$requests = 0; WP_Query::$pages = array( 1 => array( 101, 102 ) );
WP_Query::$fail_page = 1;
$r = WCA_Future24::family_hub( 77 );
$test( is_wp_error( $r ) && $r->code === 'wca_family_hub_read_failed',
    'guardian hub silently claimed complete when first query failed' );
$test( WP_Query::$requests === 1, 'failed first page unexpectedly triggered extra reads' );
WP_Query::$fail_page = 0;
WP_Query::$bad_shape_page = 1;
$r = WCA_Future24::family_hub( 77 );
$test( is_wp_error( $r ) && $r->code === 'wca_family_hub_read_failed',
    'unexpected null posts silently returned empty complete guardian list' );
WP_Query::$bad_shape_page = 0;
WP_Query::$requests = 0;
WP_Query::$pages = array( 1 => range( 101, 300 ), 2 => array( 301, 302 ) );
WP_Query::$fail_page = 2;
WCA_Central_Governance::$checked = array();
$r = WCA_Future24::family_hub( 77 );
$test( is_wp_error( $r ) && $r->code === 'wca_family_hub_read_failed',
    'second guardian page failure leaked a partial successful 200-row projection' );
$test( WP_Query::$requests === 2 && count( WCA_Central_Governance::$checked ) === 200,
    'the fixture did not actually exercise a late-page failure' );
WP_Query::$fail_page = 0;
WP_Query::$requests = 0;
WCA_Central_Governance::$checked = array();
WCA_Central_Governance::$revoked = array( 301 );
$r = WCA_Future24::family_hub( 77 );
$test( is_array( $r ) && $r['pagination_complete'] === true && count( $r['appointments'] ) === 201,
    'legitimate 200+2 paged guardian hub did not return complete filtered list' );
$test( WP_Query::$requests === 2 &&
    count( WCA_Central_Governance::$checked ) === 202, 'not every dependent passed live File00 guardian check' );
$test( ! in_array( '00000000-0000-4000-8000-000000000301',
    array_column( $r['appointments'], 'appointment_ref' ), true ),
    'revoked dependant leaked in guardian hub result' );
$test( isset( $r['appointments'][0]['appointment_ref'] ) &&
    ! array_key_exists( 'patient_user_id', $r['appointments'][0] ),
    'guardian hub projection disclosed native patient identity' );
WCA_Authorization::$is_guardian = false;
$before = WP_Query::$requests;
$r = WCA_Future24::family_hub( 77 );
$test( is_array( $r ) && $r['guardian'] === false && $r['appointments'] === array(),
    'non-guardian accessed guardian scheduling list' );
$test( WP_Query::$requests === $before,
    'non-guardian made a private dependents query' );

echo 'T35 actual Future24 queue lifecycle and guardian-hub SQL paging: PASS ' .
    $count . '/' . $count . "\n";
