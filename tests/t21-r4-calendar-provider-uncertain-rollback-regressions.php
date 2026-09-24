<?php
$root = dirname( __DIR__ );
$calendar = file_get_contents( $root . '/includes/class-wca-calendar-link.php' );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
$failures = array();
$checks = 0;
function t21r4_check( $name, $condition ) { global $failures, $checks; $checks++; if ( ! $condition ) { $failures[] = $name; } }

t21r4_check( 'calendar source readable', is_string( $calendar ) );
t21r4_check( 'repository source readable', is_string( $repo ) );
t21r4_check( 'calendar webhook uses owner transaction', false !== strpos( $calendar, "'wca_calendar_provider_webhook_transaction'" ) );
t21r4_check( 'transaction exposes uncertain-state evidence', false !== strpos( $repo, "'state_uncertain' => true" ) );
t21r4_check( 'calendar webhook inspects error data', false !== strpos( $calendar, '$error_data = $result->get_error_data();' ) );
t21r4_check( 'calendar webhook detects uncertain rollback', false !== strpos( $calendar, '! empty( $error_data[\'state_uncertain\'] )' ) );
t21r4_check( 'calendar webhook releases claim only on verified-safe rollback', false !== strpos( $calendar, 'if ( ! $state_uncertain )' ) && false !== strpos( $calendar, 'WCA_Repository::release_idempotency( $claim[\'id\'] );' ) );
t21r4_check( 'uncertain rollback retains idempotency evidence metric', false !== strpos( $calendar, 'calendar_provider_webhook_uncertain_idempotency_retained_total' ) );
t21r4_check( 'uncertain rollback emits reconciliation log', false !== strpos( $calendar, 'calendar_provider_webhook_transaction_state_uncertain' ) );
t21r4_check( 'legacy unconditional release removed', false === strpos( $calendar, 'if ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim[\'id\'] ); return $result; }' ) );

if ( $failures ) {
    fwrite( STDERR, "T21 R4 calendar-provider uncertain rollback regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo "T21 R4 calendar-provider uncertain rollback regressions: PASS {$checks}/{$checks}.\n";
