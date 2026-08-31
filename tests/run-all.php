<?php
$tests = array( 'contracts.php', 'master-plan-contract.php', 'security-static.php', 'four-review-regressions.php', 'central-plan-2026.php', 'new-plan-hardening.php', 'future24.php', 'release-package-contract.php', 'eighty-review-regressions.php', 'ten-review-regressions.php', 'second-ten-review-regressions.php', 'third-ten-review-regressions.php', 'fourth-ten-review-regressions.php', 'fifth-ten-review-regressions.php', 'sixth-ten-review-regressions.php', 'seventh-ten-review-regressions.php', 'eighth-ten-review-regressions.php', 'ninth-ten-review-regressions.php', 'tenth-ten-review-regressions.php', 'eleventh-twenty-review-regressions.php', 'twelfth-twenty-review-regressions.php', 'thirteenth-twenty-review-regressions.php', 'fourteenth-twenty-review-regressions.php', 'fifteenth-twenty-review-regressions.php', 'sixteenth-twenty-review-regressions.php', 'sixteenth-cycle-closure-hygiene.php', 'seventeenth-twenty-review-regressions.php', 'seventeenth-r3-authorization-regressions.php', 'seventeenth-r7-delegation-consent-regressions.php', 'seventeenth-r8-privacy-legal-hold-regressions.php', 'seventeenth-r10-slot-guardian-regressions.php', 'seventeenth-r12-idempotency-uncertainty-regressions.php', 'seventeenth-r15-capacity-concurrency-regressions.php', 'seventeenth-r16-finance-schema-reconciliation-regressions.php', 'seventeenth-r20-warning-clean-regressions.php' );
$diagnostic_pattern = '/(?:PHP\s+)?(?:Warning|Notice|Deprecated|Fatal error|Parse error)\s*:/i';
foreach ( $tests as $test ) {
	$command = escapeshellarg( PHP_BINARY ) . ' -d display_errors=1 -d error_reporting=E_ALL ' . escapeshellarg( __DIR__ . '/' . $test ) . ' 2>&1';
	$output = array();
	$code = 0;
	exec( $command, $output, $code );
	$text = implode( "\n", $output );
	if ( '' !== $text ) { echo $text . "\n"; }
	if ( 0 !== $code ) { exit( $code ); }
	if ( preg_match( $diagnostic_pattern, $text ) ) {
		fwrite( STDERR, "File 08 source-level test suite emitted a PHP diagnostic: {$test}\n" );
		exit( 1 );
	}
}
echo "All File 08 source-level test suites passed without PHP diagnostics.\n";
