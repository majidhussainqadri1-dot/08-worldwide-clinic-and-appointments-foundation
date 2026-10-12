<?php
/**
 * T27-R1: real source-method regression for signed File08 appointment-list
 * and delegated clinic-schedule cursors. Isolated PHP, not site E2E.
 */
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value ) { return json_encode( $value ); }
}
if ( ! function_exists( 'wp_salt' ) ) {
	function wp_salt( $scope = '' ) { return 't27-source-fixture-private-hmac-' . $scope; }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
}
require dirname( __DIR__ ) . '/includes/class-wca-query-api.php';
$encode = new ReflectionMethod( 'WCA_Query_API', 'encode_cursor' );
$decode = new ReflectionMethod( 'WCA_Query_API', 'decode_cursor' );
$valid_time = new ReflectionMethod( 'WCA_Query_API', 'cursor_time_valid' );
$encode->setAccessible( true );
$decode->setAccessible( true );
$valid_time->setAccessible( true );
$passes = 0;
$check = static function( $passed, $label ) use ( &$passes ) {
	if ( ! $passed ) { fwrite( STDERR, 'T27-R1 FAIL: ' . $label . "\n" ); exit( 1 ); }
	$passes++;
};
$scope = 'appointment_list';
$actor = 77;
$filter = hash( 'sha256', 'File08|patient|77|20' );
$when = '2026-10-12 14:25:39';
$valid = array( 'v' => 1, 's' => $scope, 'a' => $actor, 'f' => $filter, 't' => $when, 'i' => 285 );
$trusted_signed = static function( $payload ) {
	$hex = bin2hex( json_encode( $payload ) );
	return $hex . '.' . hash_hmac( 'sha256', $hex, wp_salt( 'nonce' ) );
};
$generated = $encode->invoke( null, $scope, $actor, $filter, array( 't' => $when, 'i' => 285 ) );
$decoded = $decode->invoke( null, $generated, $scope, $actor, $filter );
$check( is_array( $decoded ) && $decoded['t'] === $when && $decoded['i'] === 285, 'native patient cursor round-trip' );
$check( null === $decode->invoke( null, '', $scope, $actor, $filter ), 'initial empty cursor works' );
$clinic_scope = 'clinic_schedule';
$clinic_filter = hash( 'sha256', 'File08|clinic|123|77|20' );
$clinic_token = $encode->invoke( null, $clinic_scope, $actor, $clinic_filter, array( 't' => '2024-02-29 00:59:59', 'i' => 1 ) );
$check( is_array( $decode->invoke( null, $clinic_token, $clinic_scope, $actor, $clinic_filter ) ), 'native clinic schedule cursor round-trip' );
$check( is_wp_error( $decode->invoke( null, $generated, $clinic_scope, $actor, $filter ) ), 'patient cursor not reusable as clinic schedule cursor' );
$check( is_wp_error( $decode->invoke( null, $generated, $scope, 88, $filter ) ), 'cursor not reusable by another user' );
$check( is_wp_error( $decode->invoke( null, $generated, $scope, $actor, hash( 'sha256', 'new-filters' ) ) ), 'cursor bound to original filter' );
foreach ( array(
	'negative version' => array( 'v' => -1 ),
	'string version' => array( 'v' => '1' ),
	'boolean version' => array( 'v' => true ),
	'floating version' => array( 'v' => 1.0 ),
	'negative signed actor ID' => array( 'a' => -77 ),
	'string actor ID' => array( 'a' => '77' ),
	'float actor ID' => array( 'a' => 77.0 ),
	'boolean actor ID' => array( 'a' => true ),
	'negative last row ID' => array( 'i' => -285 ),
	'string last row ID' => array( 'i' => '285' ),
	'float last row ID' => array( 'i' => 285.1 ),
	'boolean row ID' => array( 'i' => true ),
	'array row ID' => array( 'i' => array( 285 ) ),
	'wrong scope type' => array( 's' => array( $scope ) ),
	'wrong filter type' => array( 'f' => array( $filter ) ),
	'wrong actor binding' => array( 'a' => 88 ),
	'changed filter' => array( 'f' => hash( 'sha256', 'unexpected' ) ),
	'empty timestamp' => array( 't' => '' ),
	'non-time timestamp' => array( 't' => 'arbitrary invalid position' ),
	'markup sort timestamp' => array( 't' => '<b>' . $when . '</b>' ),
	'trailing date whitespace' => array( 't' => $when . ' ' ),
	'wrong time type' => array( 't' => array( $when ) ),
	'invalid leap day' => array( 't' => '2026-02-29 14:25:39' ),
	'wrong month' => array( 't' => '2026-13-12 14:25:39' ),
	'wrong hour' => array( 't' => '2026-10-12 24:25:39' ),
	'ISO without SQL canonical representation' => array( 't' => '2026-10-12T14:25:39Z' ),
	'huge time field' => array( 't' => str_repeat( '2', 2100 ) ),
) as $name => $replacement ) {
	$broken = array_replace( $valid, $replacement );
	$answer = $decode->invoke( null, $trusted_signed( $broken ), $scope, $actor, $filter );
	$check( is_wp_error( $answer ) && $answer->code === 'wca_cursor_invalid', 'deny trusted-but-malformed ' . $name );
}
foreach ( array(
	'list root' => array( 1, $scope, $actor, $filter, $when, 285 ),
	'unknown surplus' => array_merge( $valid, array( 'secret' => 'not a cursor field' ) ),
	'missing last row' => array_diff_key( $valid, array( 'i' => true ) ),
	'missing version' => array_diff_key( $valid, array( 'v' => true ) ),
) as $name => $wrong ) {
	$answer = $decode->invoke( null, $trusted_signed( $wrong ), $scope, $actor, $filter );
	$check( is_wp_error( $answer ) && $answer->code === 'wca_cursor_invalid', 'deny cursor shape ' . $name );
}
$unsigned = $generated;
$unsigned[9] = 'f' === $unsigned[9] ? 'e' : 'f';
$check( is_wp_error( $decode->invoke( null, $unsigned, $scope, $actor, $filter ) ), 'HMAC tampering rejected' );
$check( is_wp_error( $decode->invoke( null, strtoupper( $generated ), $scope, $actor, $filter ) ), 'alternate uppercase token encoding rejected' );
$check( is_wp_error( $decode->invoke( null, str_repeat( 'a', 2100 ) . '.' . str_repeat( 'a', 64 ), $scope, $actor, $filter ) ), 'unbounded cursor rejected before processing' );
foreach ( array( '2026-10-12 00:00:00', '2024-02-29 23:59:59', '1999-12-31 23:59:59' ) as $timestamp ) {
	$check( true === $valid_time->invoke( null, $timestamp ), 'valid canonical UTC sort boundary ' . $timestamp );
}
foreach ( array( '2026-02-29 23:59:59', '2026-02-31 00:00:00', '2026-00-01 00:00:00', '2026-11-31 00:00:00', $when . 'X' ) as $timestamp ) {
	$check( false === $valid_time->invoke( null, $timestamp ), 'invalid sort boundary ' . $timestamp );
}
echo 'T27-R1 native File08 cursor integrity regression: PASS ' . $passes . '/' . $passes . "\n";
