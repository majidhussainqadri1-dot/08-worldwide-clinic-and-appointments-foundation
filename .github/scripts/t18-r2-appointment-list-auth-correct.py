from pathlib import Path

p = Path('includes/class-wca-frontend.php')
s = p.read_text()

old = """\tprivate static function appointments() {\n\t\tif ( ! is_user_logged_in() ) { return self::notice( __( 'Sign in to view appointments.', 'worldwide-clinic-appointments' ), 'warning' ); }\n\t\t$user_id  = get_current_user_id();"""
new = """\tprivate static function appointments() {\n\t\tif ( ! is_user_logged_in() ) { return self::notice( __( 'Sign in to view appointments.', 'worldwide-clinic-appointments' ), 'warning' ); }\n\t\t$user_id  = get_current_user_id();\n\t\t$claims = WCA_Authorization::claims( $user_id );\n\t\tif ( is_wp_error( $claims ) ) { return self::notice( __( 'Current account eligibility is required to view appointments.', 'worldwide-clinic-appointments' ), 'error' ); }"""
if s.count(old) != 1:
    raise SystemExit('R2 appointments claims anchor not unique')
s = s.replace(old, new, 1)

old = """\t\t$ids = (array) $query->posts;\n\t\tob_start(); ?>"""
new = """\t\t$ids = array();\n\t\tforeach ( (array) $query->posts as $candidate_id ) {\n\t\t\t$candidate_id = absint( $candidate_id );\n\t\t\tif ( ! $candidate_id ) { continue; }\n\t\t\t$current_access = WCA_Authorization::can_view_appointment( $candidate_id, $user_id );\n\t\t\tif ( is_wp_error( $current_access ) ) { continue; }\n\t\t\t$ids[] = $candidate_id;\n\t\t}\n\t\tob_start(); ?>"""
if s.count(old) != 1:
    raise SystemExit('R2 appointment candidate filter anchor not unique')
s = s.replace(old, new, 1)

p.write_text(s)

Path('tests/t18-r2-appointment-list-authorization-regressions.php').write_text(r'''<?php
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
''')

runall = Path('tests/run-all.php')
r = runall.read_text()
needle = "'t18-r1-frontend-read-failure-regressions.php',"
if needle in r and 't18-r2-appointment-list-authorization-regressions.php' not in r:
    r = r.replace(needle, needle + "\n    't18-r2-appointment-list-authorization-regressions.php',", 1)
    runall.write_text(r)

print('T18 R2 frozen ledger correction applied')