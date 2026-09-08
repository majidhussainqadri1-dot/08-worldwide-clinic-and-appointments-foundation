<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$plugin_readme = file_get_contents( $root . '/readme.txt' );
$historical = file_get_contents( $root . '/tests/t19-r20-closure-hygiene-regressions.php' );
$failures = array();
$checks = 0;
function t21r1_check( $label, $ok ) { global $failures, $checks; $checks++; if ( ! $ok ) { $failures[] = $label; } }
foreach ( array( $readme, $status, $release, $change, $plugin_readme, $historical ) as $source ) {
    t21r1_check( 'required release-evidence source is readable', is_string( $source ) );
}
$branch = 'review/file08-t21-ten-round-2026-09-09';
t21r1_check( 'README identifies T21 current branch', false !== strpos( $readme, 'Repository review branch: `' . $branch . '`' ) );
t21r1_check( 'STATUS identifies T21 current branch', false !== strpos( $status, 'Working review branch: `' . $branch . '`' ) );
t21r1_check( 'release status identifies T21 current branch', false !== strpos( $release, 'Current review branch: `' . $branch . '`' ) );
t21r1_check( 'STATUS records completed T20 closure', false !== strpos( $status, 'T20 closure: **R1–R20 complete**' ) );
t21r1_check( 'STATUS records T20 80 percent latest-ten result', false !== strpos( $status, '8/10 clean = 80%' ) );
t21r1_check( 'CHANGELOG records T20 material hardening', false !== strpos( $change, 'T20 materially hardened' ) );
t21r1_check( 'CHANGELOG records T21 resumed state', false !== strpos( $change, 'T21 review resumed on 2026-09-09' ) );
t21r1_check( 'packaged readme records T21 resumed identity', false !== strpos( $plugin_readme, 'current repository review identity is the resumed **T21** cycle' ) );
t21r1_check( 'packaged readme no longer calls T19 current', false === strpos( $plugin_readme, 'current T19 R1–R20 sequential source review is complete' ) );
t21r1_check( 'T19 historical regression no longer forces T19 currentness', false !== strpos( $historical, 'without requiring T19 currentness' ) );
t21r1_check( 'STATUS does not hard-code transient R1 correction-in-progress state', false === strpos( $status, 'R1 correction batch in progress' ) );
t21r1_check( 'release status does not hard-code transient R1 correction-in-progress state', false === strpos( $release, 'R1 correction is repository/release-evidence work' ) );
t21r1_check( 'STATUS makes package evidence exact-head only', false !== strpos( $status, '| Packaged | **Exact-head only**' ) );
t21r1_check( 'STATUS makes QA evidence exact-head only', false !== strpos( $status, '| Automated-QA Green | **Exact-head only**' ) );
t21r1_check( 'release status delegates dynamic closure to PR and workflow evidence', false !== strpos( $release, 'Exact numbered-round closure is tracked in PR #10 and its canonical exact-head workflow' ) );
$ci_branch = getenv( 'GITHUB_HEAD_REF' );
if ( ! $ci_branch ) { $ci_branch = getenv( 'GITHUB_REF_NAME' ); }
if ( $ci_branch && 0 === strpos( $ci_branch, 'review/file08-t' ) ) {
    t21r1_check( 'documented T21 branch matches active review branch', $branch === $ci_branch );
}
if ( $failures ) {
    fwrite( STDERR, "T21 R1 release-evidence regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo "T21 R1 release-evidence regressions: PASS {$checks}/{$checks}.\n";
