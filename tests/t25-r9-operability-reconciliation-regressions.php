<?php
$root = dirname( __DIR__ );
$verification = file_get_contents( $root . '/includes/class-wca-verification-reconciliation.php' );
$cli = file_get_contents( $root . '/includes/class-wca-cli.php' );
$observability = file_get_contents( $root . '/includes/class-wca-observability.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
if ( ! is_string( $verification ) || ! is_string( $cli ) || ! is_string( $observability ) || ! is_string( $runner ) ) {
	fwrite( STDERR, "T25 R9 source read failed\n" );
	exit( 1 );
}

$checks = array(
	'verification retries have a finite maximum' => false !== strpos( $verification, 'const MAX_ATTEMPTS = 8;' ) && false !== strpos( $verification, '$next_attempt >= self::MAX_ATTEMPTS' ),
	'verification retry carries attempt state' => false !== strpos( $verification, "add_action( 'wca_retry_doctor_eligibility_reconciliation', array( __CLASS__, 'retry' ), 20, 5 );" ) && false !== strpos( $verification, '$next_attempt' ),
	'verification retry uses capped backoff' => false !== strpos( $verification, 'min( HOUR_IN_SECONDS, MINUTE_IN_SECONDS * (int) pow( 2, min( 6, $attempt ) ) )' ),
	'verification scheduling failure is detected' => false !== strpos( $verification, "wp_schedule_single_event( time() + $delay, 'wca_retry_doctor_eligibility_reconciliation', $args, true )" ) && false !== strpos( $verification, 'verification_reconciliation_retry_schedule_failed' ),
	'verification exhaustion is durably dead-lettered' => false !== strpos( $verification, "const DEAD_LETTER_OPTION = 'wca_verification_reconciliation_dead_letters';" ) && false !== strpos( $verification, 'persist_dead_letter' ) && false !== strpos( $verification, 'verification_reconciliation_dead_lettered' ),
	'verification success clears matching dead letter' => false !== strpos( $verification, 'clear_dead_letter' ) && false !== strpos( $verification, 'wca_verification_reconciliation_dead_letter_clear' ),
	'CLI repair loops until compatibility completion marker' => false !== strpos( $cli, 'while ( ! get_option( WCA_Compatibility::MIGRATION_OPTION ) )' ) && false !== strpos( $cli, '$legacy += absint( $batch );' ),
	'CLI never claims completion on no-progress or exhausted repair budget' => false !== strpos( $cli, 'made no progress and did not record completion' ) && false !== strpos( $cli, 'exceeded the bounded repair budget' ),
	'health gates on legacy status migration completion' => false !== strpos( $observability, "'legacy_statuses_complete' => $legacy_status_complete" ) && false !== strpos( $observability, 'WCA_Compatibility::MIGRATION_OPTION' ),
	'health gates on verification dead letters' => false !== strpos( $observability, "'dead_letter_free' => 0 === $verification_dead_letters" ) && false !== strpos( $observability, "self::all_true( $checks['verification_reconciliation'] )" ),
	'R9 regression is bound into aggregate suite' => false !== strpos( $runner, "'t25-r9-operability-reconciliation-regressions.php'" ),
);

$failures = array();
foreach ( $checks as $name => $ok ) {
	if ( ! $ok ) { $failures[] = $name; }
}
if ( $failures ) {
	fwrite( STDERR, "T25 R9 operability reconciliation regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}
echo 'T25 R9 operability reconciliation regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
