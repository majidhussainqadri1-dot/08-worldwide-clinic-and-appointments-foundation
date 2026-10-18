<?php
/**
 * File 08 T27 release-currentness guard. This is source verification, never
 * staging/live proof. A runtime bump must update all current-facing surfaces.
 */
$root = dirname( __DIR__ );
$paths = array(
    'plugin' => 'worldwide-clinic.php',
    'contracts' => 'includes/class-wca-contracts.php',
    'readme' => 'README.md',
    'status' => 'STATUS.md',
    'release' => 'docs/RELEASE-STATUS-1.0.0.md',
    'changelog' => 'CHANGELOG.md',
    'package_readme' => 'readme.txt',
    'runner' => 'tests/run-all.php',
);
$sources = array();
foreach ( $paths as $name => $path ) {
    $text = @file_get_contents( $root . '/' . $path );
    if ( ! is_string( $text ) ) {
        fwrite( STDERR, "T27 release-currentness missing file: {$path}\n" );
        exit( 1 );
    }
    $sources[$name] = $text;
}
$checks = array(
    'plugin header' => strpos( $sources['plugin'], 'Version: 1.2.16' ) !== false,
    'plugin runtime' => strpos( $sources['plugin'], "define( 'WCA_VERSION', '1.2.16' )" ) !== false,
    'contract runtime' => strpos( $sources['contracts'], "RUNTIME_VERSION                 = '1.2.16'" ) !== false,
    'README current runtime' => strpos( $sources['readme'], '- Runtime candidate: **1.2.16**' ) !== false,
    'STATUS current runtime' => strpos( $sources['status'], '- Runtime candidate: **1.2.16**' ) !== false,
    'release status current runtime' => strpos( $sources['release'], '- Runtime candidate: **1.2.16**' ) !== false,
    'changelog current heading' => strpos( $sources['changelog'], '## 1.2.16 — current repository candidate' ) !== false,
    'public stable tag' => strpos( $sources['package_readme'], 'Stable tag: 1.2.16' ) !== false,
    'install instructions' => strpos( $sources['package_readme'], 'exact CI-generated File 08 v1.2.16 candidate' ) !== false,
    'public changelog current heading' => strpos( $sources['package_readme'], '= 1.2.16 =' ) !== false,
    'T27 current README' => strpos( $sources['readme'], '- Current review cycle: **T27' ) !== false,
    'T27 current STATUS' => strpos( $sources['status'], '- Current review phase: **T27' ) !== false,
    'T27 current release' => strpos( $sources['release'], '- Current review phase: **T27' ) !== false,
    'historical T26 README' => strpos( $sources['readme'], '- Historical review cycle: **T26' ) !== false,
    'historical T26 STATUS' => strpos( $sources['status'], '- Historical review discipline: **T26' ) !== false,
    'historical T26 release' => strpos( $sources['release'], '- Historical review cycle: **T26' ) !== false,
    'historical T26 packaged' => strpos( $sources['package_readme'], 'historical review sequence was **T26**' ) !== false,
    'no outdated T26 current README' => strpos( $sources['readme'], '- Current review cycle: **T26' ) === false,
    'no outdated T26 current STATUS' => strpos( $sources['status'], '- Current review discipline: **T26' ) === false,
    'no outdated T26 current release' => strpos( $sources['release'], '- Current review cycle: **T26' ) === false,
    'no old candidate install recommendation' => strpos( $sources['package_readme'], 'Download the exact CI-generated File 08 v1.2.15 candidate' ) === false,
    'new guard in aggregate runner' => strpos( $sources['runner'], "'t27-r2-release-identity-regressions.php'" ) !== false,
);
$bad = array();
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { $bad[] = $label; }
}
if ( $bad ) {
    fwrite( STDERR, "T27 release-currentness checks failed:\n- " . implode( "\n- ", $bad ) . "\n" );
    exit( 1 );
}
echo 'File08 T27 release-currentness checks passed: ' . count( $checks ) . '/' . count( $checks ) . ' (source only; staging/live unverified)' . PHP_EOL;
