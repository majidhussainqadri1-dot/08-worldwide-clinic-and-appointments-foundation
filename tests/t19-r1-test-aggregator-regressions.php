<?php
$root = dirname( __DIR__ );
$runner = file_get_contents( $root . '/tests/run-all.php' );
if ( ! is_string( $runner ) ) {
	fwrite( STDERR, "T19 R1 FAIL: run-all.php could not be read\n" );
	exit( 1 );
}
$required = array(
	't18-r1-frontend-read-failure-regressions.php',
	't18-r2-appointment-list-authorization-regressions.php',
	't18-r3-booking-timezone-regressions.php',
	't18-r4-currency-display-regressions.php',
	't18-r5-currency-validation-regressions.php',
	't18-r6-financial-readside-regressions.php',
	't18-r7-appointment-lifecycle-regressions.php',
	't18-r8-external-calendar-degraded-regressions.php',
	't18-r9-privacy-subject-boundary-regressions.php',
	't18-r10-release-truth-regressions.php',
	't18-post-r10-future24-contract-regressions.php',
);
foreach ( $required as $test ) {
	if ( false === strpos( $runner, "'{$test}'" ) ) {
		fwrite( STDERR, "T19 R1 FAIL: master QA omits {$test}\n" );
		exit( 1 );
	}
}
echo "T19 R1 master QA aggregation regressions: PASS\n";
