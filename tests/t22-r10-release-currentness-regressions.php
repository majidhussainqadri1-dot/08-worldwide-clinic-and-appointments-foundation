<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$ledger = file_get_contents( $root . '/docs/T22-R10-FROZEN-LEDGER.md' );
foreach ( array( $readme, $status, $release, $change, $ledger ) as $source ) {
    if ( ! is_string( $source ) ) { fwrite( STDERR, "T22 R10 source read failed\n" ); exit( 1 ); }
}
$checks = array(
    'README keeps T22 historical' => false !== strpos( $readme, 'Historical T22:' ),
    'STATUS keeps T22 historical' => false !== strpos( $status, '## Historical T22 result' ),
    'release status keeps T22 historical' => false !== strpos( $release, 'Historical T22 and T21' ),
    'changelog keeps T22 historical' => false !== strpos( $change, 'Historical T22 R1–R10 is closed' ),
    'T22 frozen ledger remains preserved' => false !== strpos( $ledger, 'read-only closure/release-truth review' ) && false !== strpos( $ledger, 'No R10 correction was applied while the review was open' ),
    'repository truth remains separate from live truth' => false !== strpos( $status, 'Live-Deployed | **Unverified / not claimed.**' ) && false !== strpos( $release, 'Production release/deployment: **NO / UNVERIFIED**' ),
);
$failures = array(); foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $failures[] = $name; } }
if ( $failures ) { fwrite( STDERR, "T22 R10 historical-currentness regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo 'T22 R10 historical-currentness regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
