<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$package = file_get_contents( $root . '/readme.txt' );
$contracts = file_get_contents( $root . '/includes/class-wca-contracts.php' );
$workflow = file_get_contents( $root . '/.github/workflows/file08-complete-quality.yml' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
$ledger = file_get_contents( $root . '/docs/T26-R10-FROZEN-LEDGER.md' );
$historical_t25_r1 = file_get_contents( __DIR__ . '/t25-r1-governing-brand-release-regressions.php' );
$historical_t25_r10 = file_get_contents( __DIR__ . '/t25-r10-release-closure-regressions.php' );
foreach ( array( $readme, $status, $release, $change, $package, $contracts, $workflow, $runner, $ledger, $historical_t25_r1, $historical_t25_r10 ) as $source ) {
    if ( ! is_string( $source ) ) { fwrite( STDERR, "T26 R10 source read failed\n" ); exit( 1 ); }
}
$round_truth = 'R1 defect-bearing/corrected; R2–R9 clean; R10 defect-bearing/corrected';
$checks = array(
    'README identifies T26 current' => false !== strpos( $readme, 'Current review cycle: **T26 closure cycle' ),
    'STATUS identifies T26 current' => false !== strpos( $status, 'Current review discipline: **T26 closure cycle' ),
    'release status identifies T26 current' => false !== strpos( $release, 'Current review cycle: **T26 closure cycle**' ),
    'changelog identifies T26 current' => false !== strpos( $change, '**T26 closure cycle is the current repository review sequence.**' ),
    'packaged readme identifies T26 current' => false !== strpos( $package, 'current repository review sequence is **T26**' ),
    'README carries T26 round truth' => false !== strpos( $readme, $round_truth ),
    'STATUS carries T26 round truth' => false !== strpos( $status, $round_truth ),
    'release carries T26 round truth' => false !== strpos( $release, $round_truth ),
    'T25 is historical rather than current' => false !== strpos( $status, '## Historical T25 result' ) && false !== strpos( $change, 'Historical T25 R1–R10 is closed' ) && false === strpos( $package, 'current repository review sequence is **T25**' ),
    'runtime identity remains 1.2.15' => false !== strpos( $readme, 'Runtime candidate: **1.2.15**' ) && false !== strpos( $release, 'Runtime candidate: **1.2.15**' ),
    'Public Clinic contract remains 1.1.0' => 1 === preg_match( "/PUBLIC_CLINIC_CONTRACT_VERSION\\s*=\\s*'1\\.1\\.0'/", $contracts ) && false !== strpos( $release, 'Public Clinic Contract: **1.1.0**' ),
    'governing booking route remains canonical' => false !== strpos( $readme, '/appointments/book/{doctor_or_clinic}' ) && false !== strpos( $contracts, "'pattern' => '/appointments/book/{doctor_or_clinic}'" ),
    'old practitioner-only booking route is absent' => false === strpos( $readme, '/appointments/book/{opaque_practitioner_ref}' ),
    'canonical exact-head workflow remains' => false !== strpos( $workflow, "php: ['7.4', '8.3']" ) && false !== strpos( $workflow, 'Build twice' ) && false !== strpos( $workflow, 'Independent verification' ),
    'live truth remains unclaimed' => false !== strpos( $status, 'Live-Deployed | **Unverified / not claimed.**' ) && false !== strpos( $release, 'Production release/deployment: **NO / UNVERIFIED**' ),
    'R10 ledger proves review before correction' => false !== strpos( $ledger, 'No R10 correction was started while the review remained open') && false !== strpos( $ledger, 'R10-D1') && false !== strpos( $ledger, 'R10-D2'),
    'historical T25 R1 no longer enforces T25 current' => false !== strpos( $historical_t25_r1, 'packaged readme identifies T26 current sequence' ) && false === strpos( $historical_t25_r1, "'packaged readme identifies T25 current sequence'" ),
    'historical T25 R10 no longer enforces T25 current' => false !== strpos( $historical_t25_r10, 'T25 no longer current in README' ) && false === strpos( $historical_t25_r10, "'README identifies T25 current'" ),
    'T26 R10 regression bound into aggregate suite' => false !== strpos( $runner, "'t26-r10-release-currentness-regressions.php'" ),
);
$failures = array();
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $failures[] = $name; } }
if ( $failures ) {
    fwrite( STDERR, "T26 R10 release-currentness regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo 'T26 R10 release-currentness regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
