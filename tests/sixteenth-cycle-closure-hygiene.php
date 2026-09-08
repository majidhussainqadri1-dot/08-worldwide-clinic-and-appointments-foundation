<?php
$root = dirname( __DIR__ );
$fail = array();
$patterns = array(
    $root . '/tools/t16-*',
    $root . '/.github/workflows/t16-*',
    $root . '/review-evidence/t16-*',
);
foreach ( $patterns as $pattern ) {
    foreach ( glob( $pattern ) ?: array() as $path ) {
        $fail[] = str_replace( $root . '/', '', $path );
    }
}

/*
 * This is historical T16 closure hygiene. It must preserve the T16 permanent
 * regression and prove that temporary T16 correction surfaces remain retired,
 * but it must not force T16 to remain the current release/review identity.
 */
$run_all = file_get_contents( $root . '/tests/run-all.php' );
$t16_regression = $root . '/tests/sixteenth-twenty-review-regressions.php';
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );

if ( ! is_file( $t16_regression ) ) {
    $fail[] = 'permanent T16 regression evidence is missing';
}
if ( false === strpos( (string) $run_all, "'sixteenth-twenty-review-regressions.php'" ) ) {
    $fail[] = 'permanent T16 regression is not aggregated';
}
if ( false !== strpos( (string) $readme, 'Current sixteenth-cycle runtime alignment' ) ) {
    $fail[] = 'README incorrectly presents historical T16 as current';
}
if ( false !== strpos( (string) $status, 'Current sixteenth-cycle' ) ) {
    $fail[] = 'STATUS incorrectly presents historical T16 as current';
}
if ( false === strpos( (string) $status, 'Historical regression labels are retained only where old regression evidence requires them' ) ) {
    $fail[] = 'STATUS does not state the historical-evidence rule';
}

if ( $fail ) {
    fwrite( STDERR, "Sixteenth-cycle closure hygiene failed:\n- " . implode( "\n- ", $fail ) . "\n" );
    exit( 1 );
}
echo "Sixteenth-cycle historical closure hygiene: PASS\n";
