<?php
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function r20_check( $name, $condition ) { global $failures, $checks; $checks++; if ( ! $condition ) { $failures[] = $name; } }
$workflows = glob( $root . '/.github/workflows/*.yml' );
$workflow_names = is_array( $workflows ) ? array_map( 'basename', $workflows ) : array();
sort( $workflow_names, SORT_STRING );
r20_check( 'single canonical workflow', array( 'file08-complete-quality.yml' ) === $workflow_names );
$scripts = glob( $root . '/.github/scripts/*' );
r20_check( 'historical correction script surface retired', ! is_array( $scripts ) || 0 === count( $scripts ) );
$tool_corrections = glob( $root . '/tools/t*-correct*.py' );
r20_check( 'historical correction tools retired from active tools namespace', ! is_array( $tool_corrections ) || 0 === count( $tool_corrections ) );
r20_check( 'obsolete development builder retired', ! is_file( $root . '/tools/build-development-candidate.php' ) );
r20_check( 'obsolete development verifier retired', ! is_file( $root . '/tools/verify-development-candidate.php' ) );
r20_check( 'ambiguous root original checksums retired', ! is_file( $root . '/CHECKSUMS.sha256' ) );
r20_check( 'ambiguous root corrective checksums retired', ! is_file( $root . '/CORRECTIVE-CHECKSUMS.sha256' ) );
r20_check( 'historical original checksum evidence preserved', is_file( $root . '/docs/historical/ORIGINAL-0.1.0-CHECKSUMS.sha256' ) );
r20_check( 'historical corrective checksum evidence preserved', is_file( $root . '/docs/historical/CORRECTIVE-0.2.1-CHECKSUMS.sha256' ) );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$staging = file_get_contents( $root . '/STAGING-ACCEPTANCE.md' );
$audit = file_get_contents( $root . '/AUDIT-EVIDENCE.md' );
$readme = file_get_contents( $root . '/readme.txt' );
$workflow = file_get_contents( $root . '/.github/workflows/file08-complete-quality.yml' );
$ignore = file_get_contents( $root . '/.gitignore' );
foreach ( array( $status,$release,$change,$staging,$audit,$readme,$workflow,$ignore ) as $source ) { if ( ! is_string( $source ) ) { fwrite( STDERR, "T19 R20 source read failed\n" ); exit( 1 ); } }
r20_check(
    'status preserves historical T19 R1-R20 closure',
    false !== strpos( $status, '## Historical T19 twenty-round result' )
        && false !== strpos( $status, 'R20 was defect-bearing at closure/release-hygiene level' )
        && false !== strpos( $status, 'The final T19 R20 correction was release/repository hygiene and documentation truth' )
);
r20_check( 'status carries current core schema', false !== strpos( $status, 'Core File 08 schema: **3.4.0**' ) );
r20_check(
    'release status preserves T19 as history without requiring T19 currentness',
    false === strpos( $release, 'REVIEWABLE CANDIDATE UNDER T19 SEQUENTIAL AUDIT' )
        && false !== strpos( $release, 'T19 R1–R20 and earlier review cycles remain historical evidence' )
);
r20_check( 'current changelog carries core 3.4', false !== strpos( $change, 'Current core schema is **3.4.0**' ) );
r20_check( 'current changelog carries Future24 1.1', false !== strpos( $change, 'Future24 schema/contract **1.1.0**' ) );
r20_check( 'staging handoff carries core 3.4', false !== strpos( $staging, 'core schema `3.4.0`' ) );
r20_check( 'staging handoff carries Future24 1.1', false !== strpos( $staging, 'Future24 additive schema/contract `1.1.0`' ) );
r20_check( 'audit evidence explicitly historical', false !== strpos( $audit, 'Historical evidence only.' ) && false !== strpos( $audit, 'original 0.1.0 helper used' ) );
r20_check(
    'plugin readme preserves T19 closure as historical provenance',
    false !== strpos( $readme, 'Historical T19 R1–R20 source review/correction sequence completed' )
        && false === strpos( $readme, 'current T19 R1–R20 sequential source review is complete' )
);
r20_check( 'canonical workflow enforces one workflow', false !== strpos( $workflow, 'find .github/workflows -maxdepth 1' ) );
r20_check( 'canonical workflow rejects obsolete development builder', false !== strpos( $workflow, 'test ! -e tools/build-development-candidate.php' ) );
r20_check( 'canonical workflow rejects active correction tools', false !== strpos( $workflow, "find tools -maxdepth 1 -type f -name 't*-correct*.py'") );
r20_check( 'canonical workflow verifies main pushes', false !== strpos( $workflow, "branches:\n      - main") );
r20_check( 'canonical workflow no longer carries historical T19 push trigger', false === strpos( $workflow, 'review/file08-t19-twenty-round-2026-09-06' ) );
r20_check( 'local deterministic build output ignored', false !== strpos( $ignore, "build/\n" ) );
if ( $failures ) { fwrite( STDERR, "T19 R20 closure hygiene failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "T19 R20 closure-hygiene regressions: PASS {$checks}/{$checks}.\n";
