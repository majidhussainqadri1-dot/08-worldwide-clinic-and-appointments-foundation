<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$ledger = file_get_contents( $root . '/docs/T22-R10-FROZEN-LEDGER.md' );
foreach ( array( $readme, $status, $release, $change, $ledger ) as $source ) {
    if ( ! is_string( $source ) ) {
        fwrite( STDERR, "T22 R10 source read failed\n" );
        exit( 1 );
    }
}
$checks = array(
    'README identifies T22 as current closure cycle' => false !== strpos( $readme, 'Current review cycle: **T22 closure cycle' ),
    'README keeps T21 historical' => false !== strpos( $readme, 'Historical T21:' ),
    'STATUS identifies T22 as current discipline' => false !== strpos( $status, 'Current review discipline: **T22 closure cycle' ),
    'STATUS keeps T21 historical' => false !== strpos( $status, '## Historical T21 result' ),
    'release status identifies T22 as current cycle' => false !== strpos( $release, 'Current review cycle: **T22 closure cycle**' ),
    'release status keeps T21 historical' => false !== strpos( $release, 'Historical T21: **R1–R10 closed**' ),
    'changelog records T22 current hardening' => false !== strpos( $change, 'T22' ) && false !== strpos( $change, 'audit-actor provenance' ) && false !== strpos( $change, 'File26' ) && false !== strpos( $change, 'localization/timezone' ),
    'changelog no longer calls T21 current' => false === strpos( $change, 'current T21 corrections' ),
    'R10 frozen ledger records review-before-fix discipline' => false !== strpos( $ledger, 'read-only closure/release-truth review' ) && false !== strpos( $ledger, 'No R10 correction was applied while the review was open' ),
    'repository truth remains separate from live truth' => false !== strpos( $status, 'Live-Deployed | **Unverified / not claimed.**' ) && false !== strpos( $release, 'Production release/deployment: **NO / UNVERIFIED**' ),
);
$failures = array();
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) { $failures[] = $name; }
}
if ( $failures ) {
    fwrite( STDERR, "T22 R10 release-currentness regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo 'T22 R10 release-currentness regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
