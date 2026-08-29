<?php
$tests = array( 'contracts.php', 'master-plan-contract.php', 'security-static.php', 'four-review-regressions.php', 'central-plan-2026.php', 'new-plan-hardening.php', 'future24.php', 'release-package-contract.php', 'eighty-review-regressions.php', 'ten-review-regressions.php', 'second-ten-review-regressions.php', 'third-ten-review-regressions.php', 'fourth-ten-review-regressions.php', 'fifth-ten-review-regressions.php', 'sixth-ten-review-regressions.php', 'seventh-ten-review-regressions.php', 'eighth-ten-review-regressions.php', 'ninth-ten-review-regressions.php', 'tenth-ten-review-regressions.php', 'eleventh-twenty-review-regressions.php', 'twelfth-twenty-review-regressions.php', 'thirteenth-twenty-review-regressions.php', 'fourteenth-twenty-review-regressions.php', 'fifteenth-twenty-review-regressions.php', 'sixteenth-twenty-review-regressions.php', 'sixteenth-cycle-closure-hygiene.php', 'seventeenth-twenty-review-regressions.php', 'seventeenth-r3-authorization-regressions.php' );
foreach ( $tests as $test ) {
	$command = escapeshellarg( PHP_BINARY ) . ' ' . escapeshellarg( __DIR__ . '/' . $test );
	passthru( $command, $code );
	if ( 0 !== $code ) { exit( $code ); }
}
echo "All File 08 source-level test suites passed.\n";
