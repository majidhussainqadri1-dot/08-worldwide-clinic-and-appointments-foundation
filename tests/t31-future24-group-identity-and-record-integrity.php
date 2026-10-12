<?php
/**
 * T31: actual File08 Future24 public_record() and join_group_session()
 * methods. Isolated WordPress/DB fixture; not Hostinger/live parity.
 */
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() { return 77; }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $v ) { return json_encode( $v ); }
}
final class WCA_Authorization {
	public static $subject = '';
	public static function claims( $actor ) { return array( 'patient' => true ); }
	public static function guardian_context( $patient, $guardian, $actor ) { return array( 'ok' => true ); }
	public static function subject_uuid( $patient ) { return self::$subject; }
}
final class WCA_Observability {
	public static $events = array();
	public static function log( $level, $event, $context = array() ) {
		self::$events[] = array( $level, $event, $context );
	}
}
final class T31_DB {
	public $prefix = 'wp_';
	public $last_error = '';
	public $reads = 0;
	public $writes = 0;
	public function prepare( $sql ) { return $sql; }
	public function get_row( $sql, $type = null ) { $this->reads++; return null; }
	public function insert( $table, $payload ) { $this->writes++; return false; }
}
if ( ! defined( 'ARRAY_A' ) ) { define( 'ARRAY_A', 'ARRAY_A' ); }
$GLOBALS['wpdb'] = new T31_DB();
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';
$projection = new ReflectionMethod( 'WCA_Future24', 'public_record' );
$projection->setAccessible( true );
$checks = 0;
$check = static function( $ok, $reason ) use ( &$checks ) {
	if ( ! $ok ) { fwrite( STDERR, 'T31 FAIL: ' . $reason . "\n" ); exit( 1 ); }
	$checks++;
};
$base = array(
	'id' => 19, 'public_ref' => '00000000-0000-4000-8000-000000000019',
	'feature_id' => 'F08-FUT-05', 'parent_ref' => 'group',
	'status' => 'group_member', 'starts_at' => null, 'ends_at' => null,
	'capacity' => 1, 'version' => 7, 'expires_at' => null,
	'updated_at' => '2026-10-13 00:00:00', 'payload_json' => '{}',
);
$examples = array(
	'{"member_ref":"00000000-0000-4000-8000-000000000077","peer_identity_visible":false}',
	'{"guardian_user_id":0,"peer_identity_visible":false}',
	'{}',
	'[]',
);
foreach ( $examples as $raw ) {
	$row = $base; $row['payload_json'] = $raw;
	$result = $projection->invoke( null, $row );
	$check( is_array( $result ) && isset( $result['payload'] ), 'valid JSON object/empty legacy payload rejected' );
	$check( $result['version'] === 7 && $result['feature_id'] === 'F08-FUT-05', 'valid historical row identity changed' );
	$check( $result['payload'] === json_decode( $raw, true ), 'legitimate payload changed during projection' );
}
$corrupt = array( '', 'broken-json', 'null', 'false', 'true',
	'0', '42', '"scalar"', '[1]', '["member"]',
	'[{"member_ref":"invented"}]', '{"member_ref":', '["member","peer"]',
);
foreach ( $corrupt as $raw ) {
	$row = $base; $row['payload_json'] = $raw;
	$before = $GLOBALS['wpdb']->writes;
	$reply = $projection->invoke( null, $row );
	$check( is_wp_error( $reply ) && $reply->code === 'wca_future24_payload_corrupt',
		'corrupt stored Future24 payload projected as legitimate: ' . $raw );
	$check( $before === $GLOBALS['wpdb']->writes, 'corrupt projection modified stored state' );
}
$err = new WP_Error( 'original_error', 'original unavailable' );
$check( $projection->invoke( null, $err ) === $err, 'WP_Error response not preserved' );
$check( is_wp_error( $projection->invoke( null, false ) ), 'invalid stored row container silently projected' );
$check( count( WCA_Observability::$events ) >= count( $corrupt ), 'corrupt stored payload lacked privacy-safe audit/log event' );
$identity = array( '', 'not-a-uuid', false, 1,
	'zzzzzzzz-0000-0000-0000-000000000077',
	'00000000-0000-4000-8000-000000000077 ',
);
foreach ( $identity as $bad ) {
	WCA_Authorization::$subject = $bad;
	$db = $GLOBALS['wpdb']; $before_reads = $db->reads; $before_writes = $db->writes;
	$result = WCA_Future24::join_group_session( '00000000-0000-4000-8000-000000000019',
		array( 'patient_user_id' => 77 ), 77 );
	$check( is_wp_error( $result ) && $result->code === 'wca_group_subject_unavailable',
		'group creation accepted missing or fake File00 UUID' );
	$check( $db->reads === $before_reads && $db->writes === $before_writes,
		'unverified File00 identity reached group SQL read/write' );
}
WCA_Authorization::$subject = '00000000-0000-4000-8000-000000000077';
$db = $GLOBALS['wpdb']; $before_reads = $db->reads;
$result = WCA_Future24::join_group_session( '00000000-0000-4000-8000-000000000019',
	array( 'patient_user_id' => 77 ), 77 );
$check( is_wp_error( $result ) && $result->code === 'wca_group_missing',
	'valid canonical UUID failed to pass identity guard and reach session lookup' );
$check( $db->reads === $before_reads + 1, 'valid canonical UUID never reached native session reader' );
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-future24.php' );
$check( false === strpos( $source, "'member_ref' => WCA_Authorization::subject_uuid(" ),
	'group member still writes unverified inline subject_uuid' );
$check( false !== strpos( $source, "'member_ref' => $member_ref" ),
	'group member identity not reusing validated canonical reference' );
echo 'T31 real Future24 Group and persisted DTO PHP checks: PASS ' . $checks . '/' . $checks . "\n";
