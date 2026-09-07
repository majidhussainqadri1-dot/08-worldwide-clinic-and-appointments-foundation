<?php
$root = dirname( __DIR__ );
$source = file_get_contents( $root . '/includes/class-wca-appointment-command.php' );
$failures = array();
$checks = 0;
function t20r3_check( $name, $condition ) { global $failures, $checks; $checks++; if ( ! $condition ) { $failures[] = $name; } }
t20r3_check( 'appointment command source readable', is_string( $source ) );
if ( is_string( $source ) ) {
    t20r3_check( 'canonical appointment route reads Idempotency-Key header', false !== strpos( $source, '$request->get_header( \'Idempotency-Key\' )' ) );
    t20r3_check( 'header key is normalized into domain idempotency field', false !== strpos( $source, '$data[\'idempotency_key\'] = $header_key' ) );
    t20r3_check( 'domain service still receives normalized command data', false !== strpos( $source, 'WCA_Service::request_appointment( $data, $user_id )' ) );
}
if ( $failures ) { fwrite( STDERR, "T20 R3 appointment idempotency-header regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "T20 R3 appointment idempotency-header regressions: PASS {$checks}/{$checks}.\n";
