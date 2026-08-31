<?php
/** R20 closure: the complete source suite must fail on PHP diagnostics. */
$run_all = file_get_contents( __DIR__ . '/run-all.php' );
$r16 = file_get_contents( __DIR__ . '/seventeenth-r16-finance-schema-reconciliation-regressions.php' );
if ( false === $run_all || false === $r16 ) { fwrite( STDERR, "R20 warning-clean regression source missing.\n" ); exit( 1 ); }
$checks = array(
	'complete suite captures stderr' => false !== strpos( $run_all, "2>&1" ),
	'complete suite enables E_ALL' => false !== strpos( $run_all, 'error_reporting=E_ALL' ),
	'complete suite scans diagnostics' => false !== strpos( $run_all, '$diagnostic_pattern' ) && false !== strpos( $run_all, 'preg_match( $diagnostic_pattern, $text )' ),
	'complete suite rejects diagnostics' => false !== strpos( $run_all, 'source-level test suite emitted a PHP diagnostic' ),
	'R16 current source version needle is literal' => false !== strpos( $r16, '\'source_version\' => $current_source_version' ),
);
foreach ( $checks as $label => $ok ) {
	if ( ! $ok ) { fwrite( STDERR, "R20 warning-clean regression failed: {$label}\n" ); exit( 1 ); }
}
echo "T17 R20 warning-clean source-suite regressions passed.\n";
