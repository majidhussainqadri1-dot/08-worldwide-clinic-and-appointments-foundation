<?php
$root = dirname(__DIR__);
$src = file_get_contents($root . '/includes/class-wca-frontend.php');
if (!is_string($src)) { fwrite(STDERR, "T18 R2 source read failed\n"); exit(1); }
$checks = array(
    'appointment list revalidates current claims' => strpos($src, '$claims = WCA_Authorization::claims( $user_id );') !== false,
    'ineligible account list fails closed' => strpos($src, 'Current account eligibility is required to view appointments.') !== false,
    'each candidate appointment is reauthorized' => strpos($src, 'WCA_Authorization::can_view_appointment( $candidate_id, $user_id )') !== false,
    'stale candidate is skipped' => strpos($src, 'if ( is_wp_error( $current_access ) ) { continue; }') !== false,
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "T18 R2 FAIL: {$name}\n"); exit(1); } }
echo "T18 R2 appointment-list authorization regressions: PASS\n";
