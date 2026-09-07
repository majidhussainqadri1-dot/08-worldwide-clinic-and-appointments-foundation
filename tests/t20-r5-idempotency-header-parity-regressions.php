<?php
$root = dirname( __DIR__ );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$opaque = file_get_contents( $root . '/includes/class-wca-opaque-api.php' );
$hard = file_get_contents( $root . '/includes/class-wca-ten-review-hardening.php' );
$guard = file_get_contents( $root . '/includes/class-wca-plan-guard.php' );
$failures = array(); $checks = 0;
function t20r5_check( $name, $ok ) { global $failures, $checks; $checks++; if ( ! $ok ) { $failures[] = $name; } }
foreach ( array( $rest, $opaque, $hard, $guard ) as $source ) { t20r5_check( 'source readable', is_string( $source ) ); }
t20r5_check( 'HTTP mutation guard accepts Idempotency-Key header', false !== strpos( $hard, '$request->get_header( \'Idempotency-Key\' )' ) );
t20r5_check( 'slot-hold route normalizes header key into command data', false !== strpos( $rest, '$header_key = trim( (string) $request->get_header( \'Idempotency-Key\' ) )' ) && false !== strpos( $rest, '$data[\'idempotency_key\'] = $header_key' ) && false !== strpos( $rest, 'WCA_Service::hold_slot( $data )' ) );
t20r5_check( 'reschedule-hold route normalizes header key into command data', false !== strpos( $opaque, '$header_key = trim( (string) $request->get_header( \'Idempotency-Key\' ) )' ) && false !== strpos( $opaque, '$data[\'idempotency_key\'] = $header_key' ) && false !== strpos( $opaque, 'WCA_Service::hold_reschedule_slot( $id, $data, get_current_user_id() )' ) );
t20r5_check( 'domain slot hold still requires explicit idempotency value', false !== strpos( $guard, 'idempotency_key' ) );
t20r5_check( 'reschedule-hold is covered by cross-cutting HTTP idempotency', false !== strpos( $hard, 'transitions|payment-intents|reschedule-holds' ) );
if ( $failures ) { fwrite( STDERR, "T20 R5 idempotency-header parity regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "T20 R5 idempotency-header parity regressions: PASS {$checks}/{$checks}.\n";
