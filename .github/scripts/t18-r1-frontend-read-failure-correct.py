from pathlib import Path

p = Path('includes/class-wca-frontend.php')
s = p.read_text()

old = """\t\t$clinic = WCA_Service::public_clinic_projection( $ref );\n\t\tif ( ! $clinic ) { return self::notice( __( 'Clinic was not found or is not publicly available.', 'worldwide-clinic-appointments' ), 'error' ); }"""
new = """\t\t$clinic = WCA_Service::public_clinic_projection( $ref );\n\t\tif ( is_wp_error( $clinic ) ) { return self::notice( __( 'Clinic information is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }\n\t\tif ( ! $clinic ) { return self::notice( __( 'Clinic was not found or is not publicly available.', 'worldwide-clinic-appointments' ), 'error' ); }"""
if s.count(old) != 1:
    raise SystemExit('R1 clinic projection anchor not unique')
s = s.replace(old, new, 1)

old = """\t\t$clinic = WCA_Service::public_clinic_projection( $clinic_ref );\n\t\tif ( ! $clinic ) { return self::notice( __( 'Clinic is unavailable.', 'worldwide-clinic-appointments' ), 'error' ); }"""
new = """\t\t$clinic = WCA_Service::public_clinic_projection( $clinic_ref );\n\t\tif ( is_wp_error( $clinic ) ) { return self::notice( __( 'Clinic information is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }\n\t\tif ( ! $clinic ) { return self::notice( __( 'Clinic is unavailable.', 'worldwide-clinic-appointments' ), 'error' ); }"""
if s.count(old) != 1:
    raise SystemExit('R1 booking projection anchor not unique')
s = s.replace(old, new, 1)

old = """\t\t$user_id = get_current_user_id();\n\t\t$clinics = WCA_Repository::list_clinics( array( 'owner_user_id' => $user_id, 'status' => '', 'per_page' => 50 ) );\n\t\t$seen = array();"""
new = """\t\t$user_id = get_current_user_id();\n\t\tWCA_Repository::clear_read_error();\n\t\t$clinics = WCA_Repository::list_clinics( array( 'owner_user_id' => $user_id, 'status' => '', 'per_page' => 50 ) );\n\t\t$clinic_list_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $clinic_list_error ) ) { return self::notice( __( 'Clinic dashboard data is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }\n\t\t$seen = array();"""
if s.count(old) != 1:
    raise SystemExit('R1 dashboard list anchor not unique')
s = s.replace(old, new, 1)

old = """\t\t\t$clinic = WCA_Repository::get_clinic( $clinic_id, false );\n\t\t\tif ( ! $clinic || is_wp_error( WCA_Authorization::can_manage_clinic( $clinic, $user_id ) ) ) { continue; }"""
new = """\t\t\tWCA_Repository::clear_read_error();\n\t\t\t$clinic = WCA_Repository::get_clinic( $clinic_id, false );\n\t\t\t$clinic_read_error = WCA_Repository::consume_read_error();\n\t\t\tif ( is_wp_error( $clinic_read_error ) ) { return self::notice( __( 'Clinic dashboard data is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }\n\t\t\tif ( ! $clinic || is_wp_error( WCA_Authorization::can_manage_clinic( $clinic, $user_id ) ) ) { continue; }"""
if s.count(old) != 1:
    raise SystemExit('R1 delegated clinic anchor not unique')
s = s.replace(old, new, 1)

p.write_text(s)

Path('tests/t18-r1-frontend-read-failure-regressions.php').write_text(r'''<?php
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
''')

runall = Path('tests/run-all.php')
r = runall.read_text()
needle = "'seventeenth-r20-warning-clean-regressions.php',"
if needle in r and 't18-r1-frontend-read-failure-regressions.php' not in r:
    r = r.replace(needle, needle + "\n    't18-r1-frontend-read-failure-regressions.php',", 1)
    runall.write_text(r)

print('T18 R1 frozen ledger correction applied')