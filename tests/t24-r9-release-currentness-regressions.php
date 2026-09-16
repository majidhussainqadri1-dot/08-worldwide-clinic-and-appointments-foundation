<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$ledger = file_get_contents( $root . '/docs/T24-R9-FROZEN-LEDGER.md' );
foreach ( array( $readme, $status, $release, $change, $ledger ) as $source ) {
    if ( ! is_string( $source ) ) { fwrite( STDERR, "T24 R9 source read failed\n" ); exit( 1 ); }
}
$checks = array(
    'README identifies T24 current cycle' => false !== strpos( $readme, 'Current review cycle: **T24 closure cycle' ),
    'STATUS identifies T24 current discipline' => false !== strpos( $status, 'Current review discipline: **T24 closure cycle' ),
    'release status identifies T24 current cycle' => false !== strpos( $release, 'Current review cycle: **T24 closure cycle**' ),
    'changelog identifies T24 current cycle' => false !== strpos( $change, '**T24 closure cycle is the current repository review sequence.**' ),
    'old T22 current-cycle assertion retired' => false === strpos( $change, '**T22 closure cycle is the current repository review sequence.**' ),
    'old T22 exact-head run retired from current docs' => false === strpos( $readme, '34838735323' ) && false === strpos( $status, '34838735323' ) && false === strpos( $release, '34838735323' ) && false === strpos( $change, '34838735323' ),
    'static docs avoid future CI success claim' => false !== strpos( $status, 'Exact-head CI/package status is determined from the canonical workflow on the exact HEAD' ) && false !== strpos( $release, 'does not hard-code a future CI success' ),
    'live truth remains unclaimed' => false !== strpos( $status, 'Live-Deployed | **Unverified / not claimed.**' ) && false !== strpos( $release, 'Production release/deployment: **NO / UNVERIFIED**' ),
    'R9 ledger proves review before correction' => false !== strpos( $ledger, 'No R9 correction was applied while the review remained open' ) && false !== strpos( $ledger, 'R9-D1' ),
);
$failures = array(); foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $failures[] = $name; } }
if ( $failures ) { fwrite( STDERR, "T24 R9 release-currentness regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo 'T24 R9 release-currentness regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
