<?php
$root = dirname(__DIR__);
$src = file_get_contents($root . '/includes/class-wca-frontend.php');
if (!is_string($src)) { fwrite(STDERR, "T18 R1 source read failed\n"); exit(1); }
$checks = array(
    'public clinic projection WP_Error guarded' => substr_count($src, 'if ( is_wp_error( $clinic ) )') >= 2,
    'dashboard list read error consumed' => strpos($src, '$clinic_list_error = WCA_Repository::consume_read_error();') !== false,
    'delegated clinic read error consumed' => strpos($src, '$clinic_read_error = WCA_Repository::consume_read_error();') !== false,
    'dashboard storage failure message present' => strpos($src, 'Clinic dashboard data is temporarily unavailable. Please try again.') !== false,
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "T18 R1 FAIL: {$name}\n"); exit(1); } }
echo "T18 R1 frontend read-failure regressions: PASS\n";
