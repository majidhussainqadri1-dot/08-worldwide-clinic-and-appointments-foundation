<?php
$root = dirname( __DIR__ );
$canonical = file_get_contents( $root . '/includes/class-wca-contracts.php' );
$projection = file_get_contents( $root . '/includes/class-swc-public-clinic.php' );
$doc = file_get_contents( $root . '/PUBLIC-CLINIC-PROJECTION-CONTRACT.md' );
$builder = file_get_contents( $root . '/tools/build-candidate.php' );
$verifier = file_get_contents( $root . '/tools/verify-candidate.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
$ledger = file_get_contents( $root . '/docs/T26-R1-FROZEN-LEDGER.md' );
foreach ( array( $canonical, $projection, $doc, $builder, $verifier, $runner, $ledger ) as $source ) {
    if ( ! is_string( $source ) ) { fwrite( STDERR, "T26 R1 source read failed
" ); exit( 1 ); }
}
$checks = array(
    'canonical Public Clinic contract is 1.1.0' => false !== strpos( $canonical, "PUBLIC_CLINIC_CONTRACT_VERSION  = '1.1.0'" ),
    'runtime public projection contract is 1.1.0' => false !== strpos( $projection, "const CONTRACT_VERSION = '1.1.0';" ),
    'dedicated contract document is 1.1.0' => false !== strpos( $doc, '# File 08 Public Clinic Projection Contract 1.1.0' ) && false !== strpos( $doc, "'contract_version' => '1.1.0'" ),
    'builder checks runtime projection parity' => false !== strpos( $builder, "class-swc-public-clinic.php', 'CONTRACT_VERSION'" ) && false !== strpos( $builder, 'Public Clinic runtime/canonical contract version mismatch.' ),
    'verifier opens packaged projection contract' => false !== strpos( $verifier, "includes/class-swc-public-clinic.php" ) && false !== strpos( $verifier, 'Manifest/Public Clinic runtime contract parity failed.' ),
    'R1 ledger proves review-first discipline' => false !== strpos( $ledger, 'No T26 R1 correction was started while the review remained open' ) && false !== strpos( $ledger, 'R1-D1' ),
    'T26 R1 regression bound into aggregate runner' => false !== strpos( $runner, 't26-r1-public-clinic-contract-parity-regressions.php' ),
);
$bad = array(); foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $bad[] = $name; } }
if ( $bad ) { fwrite( STDERR, "T26 R1 public-clinic contract parity regressions failed:
- " . implode( "
- ", $bad ) . "
" ); exit( 1 ); }
echo 'T26 R1 public-clinic contract parity regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "
";
