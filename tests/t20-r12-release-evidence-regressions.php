<?php
/**
 * Historical T20 release evidence guard, updated for main exact-HEAD identity.
 * A transient working review branch is not a canonical release identity.
 */
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
if ( ! is_string( $readme ) || ! is_string( $status ) ) {
    fwrite( STDERR, "T20 R12 release-evidence files unreadable\n" );
    exit( 1 );
}
$checks = array(
    'README canonical exact main source' => strpos( $readme, 'Canonical source: `main` at its verified exact HEAD' ) !== false,
    'STATUS canonical exact main source' => strpos( $status, 'Canonical source: repository `main` at its verified exact HEAD' ) !== false,
    'T19 is not current in README' => strpos( $readme, 'current identity is the T19 section above' ) === false,
    'T19 is not current in STATUS' => strpos( $status, 'Review discipline: **T19 ' ) === false,
    'T26 is recorded historically in README' => strpos( $readme, 'Historical review cycle: **T26' ) !== false,
    'T26 is recorded historically in STATUS' => strpos( $status, 'Historical review discipline: **T26' ) !== false,
);
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) {
        fwrite( STDERR, "T20 R12 release-evidence regression failed: {$label}\n" );
        exit( 1 );
    }
}
echo "T20 R12 historical release-evidence regression: PASS\n";
