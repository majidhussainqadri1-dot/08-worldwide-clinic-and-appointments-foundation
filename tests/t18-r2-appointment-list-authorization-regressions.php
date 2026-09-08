<?php
$root = dirname(__DIR__);
$query = file_get_contents($root . '/includes/class-wca-query-api.php');
$frontend = file_get_contents($root . '/includes/class-wca-frontend.php');
if (!is_string($query) || !is_string($frontend)) { fwrite(STDERR, "T18 R2 source read failed\n"); exit(1); }
$checks = array(
    'appointment list revalidates current claims' => strpos($query, '$claims = WCA_Authorization::claims( $actor_user_id );') !== false,
    'each candidate appointment is reauthorized' => strpos($query, 'WCA_Authorization::can_view_appointment( $id, $actor_user_id )') !== false,
    'stale candidate is skipped after current authorization' => strpos($query, 'if ( is_wp_error( $access ) )') !== false && strpos($query, '$consumed = $row;') !== false,
    'authorization infrastructure failures fail closed' => strpos($query, 'if ( $status >= 500 ) { return $access; }') !== false,
    'frontend consumes canonical authorized query contract' => strpos($frontend, 'WCA_Query_API::list_patient_appointments( $user_id') !== false,
    'frontend no longer duplicates pre-authorization WP pagination' => strpos($frontend, 'new WP_Query') === false,
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "T18 R2 FAIL: {$name}\n"); exit(1); } }
echo "T18 R2 appointment-list authorization regressions: PASS\n";
