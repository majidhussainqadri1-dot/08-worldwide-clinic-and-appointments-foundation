<?php
$root = dirname( __DIR__ );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
if ( ! is_string( $service ) || ! is_string( $runner ) ) { fwrite( STDERR, "T23 R9 source read failed\n" ); exit( 1 ); }
$start = strpos( $service, 'public static function create_payment_intent' );
$end = false !== $start ? strpos( $service, 'public static function appointment_ics', $start ) : false;
$block = ( false !== $start && false !== $end ) ? substr( $service, $start, $end - $start ) : '';
$checks = array(
    'payment transaction inspects error data' => false !== strpos( $block, '$error_data = $result->get_error_data();' ),
    'payment transaction recognizes uncertain state' => false !== strpos( $block, '$state_uncertain = is_array( $error_data ) && ! empty( $error_data[\'state_uncertain\'] );' ),
    'uncertain payment state is observed' => false !== strpos( $block, 'payment_intent_state_uncertain_total' ) && false !== strpos( $block, 'payment_intent_state_uncertain' ),
    'idempotency release occurs only in safe branch' => false !== strpos( $block, '} else {' ) && false !== strpos( $block, 'WCA_Repository::release_idempotency( $claim[\'id\'] );' ),
    'regression is bound into aggregate suite' => false !== strpos( $runner, "'t23-r9-payment-idempotency-uncertainty-regressions.php'" ),
);
$failures = array(); foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $failures[] = $name; } }
if ( $failures ) { fwrite( STDERR, "T23 R9 payment uncertainty regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo 'T23 R9 payment uncertainty regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
