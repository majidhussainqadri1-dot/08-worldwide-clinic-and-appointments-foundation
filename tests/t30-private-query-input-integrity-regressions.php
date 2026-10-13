<?php
/**
 * T30 actual native File08 private query input boundary.
 * Reuses tested T27-R2 isolated WordPress and SQL-query stub; this is NOT
 * deployed WP/Hostinger or real-user browser/IDOR evidence.
 */
require __DIR__ . '/t27-r2-cursor-producer-consumer-regressions.php';
$input = new ReflectionMethod( 'WCA_Query_API', 'cursor_input' );
$size = new ReflectionMethod( 'WCA_Query_API', 'page_size' );
$decode = new ReflectionMethod( 'WCA_Query_API', 'decode_cursor' );
$encode = new ReflectionMethod( 'WCA_Query_API', 'encode_cursor' );
foreach ( array( $input, $size, $decode, $encode ) as $r ) { $r->setAccessible( true ); }
$total = 0;
$assert = static function ( $good, $why ) use ( &$total ) {
	if ( ! $good ) { fwrite( STDERR, 'T30 FAIL: ' . $why . "\n" ); exit( 1 ); }
	$total++;
};
$actor = 77;
$scope = 'appointment_list';
$filter = hash( 'sha256', 't30-private-boundary' );
$valid_time = '2026-10-12 12:00:00';
$token = $encode->invoke( null, $scope, $actor, $filter, array( 't' => $valid_time, 'i' => 51 ) );
$assert( is_string( $token ) && false !== strpos( $token, '.' ), 'valid opaque cursor generated' );
$assert( $input->invoke( null, $token ) === $token, 'properly signed cursor changed at input boundary' );
$assert( $input->invoke( null, null ) === '', 'missing cursor did not return initial-page sentinel' );
$assert( $input->invoke( null, '' ) === '', 'empty initial cursor denied' );
foreach ( array(
	array( $token ),
	array( 'cursor' => $token ),
	(object) array( 'cursor' => $token ),
	true,
	false,
	0,
	42,
	12.5,
	' ' . $token,
	$token . ' ',
	"\n" . $token,
	"<b>" . $token . "</b>",
	str_repeat( 'f', 2100 )
) as $bad ) {
	$got = $input->invoke( null, $bad );
	$assert( is_wp_error( $got ) && $got->code === 'wca_cursor_invalid', 'malformed input was normalized or allowed' );
	$before = $GLOBALS['wpdb']->reads;
	$resp = WCA_Query_API::list_patient_appointments( $actor, array( 'cursor' => $bad, 'per_page' => 2 ) );
	$assert( is_wp_error( $resp ) && $resp->code === 'wca_cursor_invalid', 'native private patient query accepted malformed cursor' );
	$assert( $before === $GLOBALS['wpdb']->reads, 'invalid patient cursor executed an SQL list query' );
	$before = $GLOBALS['wpdb']->reads;
	$clinic = WCA_Query_API::list_clinic_schedule( '00000000-0000-4000-8000-000000000003', $actor, array( 'cursor' => $bad, 'per_page' => 2 ) );
	$assert( is_wp_error( $clinic ) && $clinic->code === 'wca_cursor_invalid', 'native private clinic schedule accepted malformed cursor' );
	$assert( $before === $GLOBALS['wpdb']->reads, 'invalid clinic cursor executed an SQL list query' );
}
foreach ( array(
	array( 2 ),
	array( '2' ),
	(object) array( 'per_page' => 2 ),
	false,
	true,
	-1,
	0,
	1.5,
	'1.0',
	'0',
	'-1',
	'02',
	' 2 ',
	'not-a-size',
	'1e2',
	str_repeat( '9', 500 ),
) as $invalid ) {
	$got = $size->invoke( null, $invalid );
	$assert( is_wp_error( $got ) && $got->code === 'wca_query_page_size_invalid', 'invalid page size silently became 20 or 1' );
	$before = $GLOBALS['wpdb']->reads;
	$resp = WCA_Query_API::list_patient_appointments( $actor, array( 'per_page' => $invalid ) );
	$assert( is_wp_error( $resp ) && $resp->code === 'wca_query_page_size_invalid', 'patient list accepted malformed page size' );
	$assert( $before === $GLOBALS['wpdb']->reads, 'invalid page size performed SQL read' );
}
foreach ( array(
	array( null, 20 ), array( '', 20 ),
	array( 1, 1 ), array( '1', 1 ), array( 20, 20 ),
	array( '20', 20 ), array( 50, 50 ), array( 51, 50 ),
	array( '100', 50 ), array( 100, 50 )
) as $case ) {
	$assert( $size->invoke( null, $case[0] ) === $case[1], 'valid/default/bounded page size regressed' );
}
$assert( is_wp_error( $decode->invoke( null, ' ' . $token, $scope, $actor, $filter ) ), 'signed cursor with leading whitespace accepted after trim' );
$assert( is_wp_error( $decode->invoke( null, $token . ' ', $scope, $actor, $filter ) ), 'signed cursor with trailing whitespace accepted after trim' );
$assert( is_wp_error( $decode->invoke( null, array( $token ), $scope, $actor, $filter ) ), 'decoder cast a list cursor into string' );
$assert( $decode->invoke( null, $token, $scope, $actor, $filter ) === array( 't' => $valid_time, 'i' => 51 ), 'properly signed cursor no longer verifies' );
// Source-route contract: actual route/dashboard callers must forward unchanged
// typed values to the same native fail-closed boundary, not strip HTML.
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-query-api.php' );
$assert( false === strpos( $source, "sanitize_text_field( (string) \$request->get_param( 'cursor' ) )" ), 'REST still mutates cursor before type validation' );
$assert( false !== strpos( $source, "if ( ! is_string( \$selected_param ) )" ), 'dashboard selected clinic ref has no structured-shape check' );
$assert( false !== strpos( $source, "if ( is_wp_error( \$cursor ) ) { return self::notice(" ), 'dashboard malformed cursor lacks safe error' );
$assert( false !== strpos( $source, "'cursor'   => \$request->get_param( 'cursor' )" ), 'REST does not pass raw cursor to native validation' );
echo 'T30 actual File08 private query input boundary: PASS ' . $total . '/' . $total . "\n";
