<?php
$root = dirname( __DIR__ );
$admin = file_get_contents( $root . '/assets/css/admin.css' );
$clinic = file_get_contents( $root . '/assets/css/clinic.css' );
$governance = file_get_contents( $root . '/includes/class-wca-central-governance.php' );
$readme = file_get_contents( $root . '/readme.txt' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
foreach ( array( $admin, $clinic, $governance, $readme, $runner ) as $source ) {
    if ( ! is_string( $source ) ) { fwrite( STDERR, "T25 R1 source read failed\n" ); exit( 1 ); }
}
$checks = array(
    'governance exact Sabri Green fallback' => false !== strpos( $governance, "const SABRI_GREEN = '#087A4E'" ) && false !== strpos( $governance, "'visual_token_owner'      => 'File 25'" ),
    'public clinic exact Sabri Green fallback' => false !== strpos( $clinic, '--wca-green:#087A4E' ),
    'admin primary exact Sabri Green fallback' => false !== strpos( $admin, '.button-primary{background:#087A4E;border-color:#087A4E}' ),
    'admin old divergent primary retired' => false === strpos( $admin, 'background:#166534' ),
    'packaged readme identifies T25 current sequence' => false !== strpos( $readme, 'current repository review sequence is **T25**' ),
    'packaged readme no longer identifies T21 as current' => false === strpos( $readme, 'current repository review identity is the resumed **T21** cycle' ),
    'packaged readme preserves exact-head evidence law' => false !== strpos( $readme, 'older round label or CI run never substitutes for exact-head evidence after a later commit' ),
    'regression bound into aggregate suite' => false !== strpos( $runner, 't25-r1-governing-brand-release-regressions.php' ),
);
$failures = array(); foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $failures[] = $name; } }
if ( $failures ) { fwrite( STDERR, "T25 R1 governing/brand/release regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo 'T25 R1 governing/brand/release regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
