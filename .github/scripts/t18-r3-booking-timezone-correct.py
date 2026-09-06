from pathlib import Path

front = Path('includes/class-wca-frontend.php')
s = front.read_text()
old = """\t\t$service_ref = sanitize_text_field( wp_unslash( $_GET['service'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only route choice.\n\t\tob_start();"""
new = """\t\t$service_ref = sanitize_text_field( wp_unslash( $_GET['service'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only route choice.\n\t\t$default_timezone = wp_timezone_string();\n\t\tif ( ! WCA_Service::valid_timezone( $default_timezone ) ) { $default_timezone = 'UTC'; }\n\t\tob_start();"""
if s.count(old) != 1: raise SystemExit('R3 frontend timezone anchor not unique')
s = s.replace(old,new,1)
old = """<input name=\"timezone\" required value=\"<?php echo esc_attr( wp_timezone_string() ); ?>\">"""
new = """<input name=\"timezone\" required value=\"<?php echo esc_attr( $default_timezone ); ?>\">"""
if s.count(old) != 1: raise SystemExit('R3 frontend timezone input anchor not unique')
s = s.replace(old,new,1)
front.write_text(s)

plugin = Path('includes/class-wca-plugin.php')
s = plugin.read_text()
old = """\t\twp_localize_script( 'wca-clinic', 'wcaRuntime', array(\n\t\t\t'restUrl'   => esc_url_raw( rest_url( 'wca/v1/' ) ),\n\t\t\t'nonce'     => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',\n\t\t\t'timezone'  => wp_timezone_string(),"""
new = """\t\t$runtime_timezone = wp_timezone_string();\n\t\tif ( ! WCA_Service::valid_timezone( $runtime_timezone ) ) { $runtime_timezone = 'UTC'; }\n\t\twp_localize_script( 'wca-clinic', 'wcaRuntime', array(\n\t\t\t'restUrl'   => esc_url_raw( rest_url( 'wca/v1/' ) ),\n\t\t\t'nonce'     => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',\n\t\t\t'timezone'  => $runtime_timezone,"""
if s.count(old) != 1: raise SystemExit('R3 runtime timezone anchor not unique')
s = s.replace(old,new,1)
plugin.write_text(s)

js = Path('assets/js/clinic.js')
s = js.read_text()
old = """\t\tvar tz = form.elements.timezone;\n\t\tif (tz && (!tz.value || tz.value === 'UTC')) {\n\t\t\ttry { tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone || tz.value; } catch (e) {}\n\t\t}"""
new = """\t\tvar tz = form.elements.timezone;\n\t\tif (tz) {\n\t\t\ttry {\n\t\t\t\tvar browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';\n\t\t\t\tif (browserTimezone) tz.value = browserTimezone;\n\t\t\t} catch (e) {}\n\t\t}"""
if s.count(old) != 1: raise SystemExit('R3 browser timezone anchor not unique')
s = s.replace(old,new,1)
js.write_text(s)

Path('tests/t18-r3-booking-timezone-regressions.php').write_text(r'''<?php
$root = dirname(__DIR__);
$front = file_get_contents($root . '/includes/class-wca-frontend.php');
$plugin = file_get_contents($root . '/includes/class-wca-plugin.php');
$js = file_get_contents($root . '/assets/js/clinic.js');
$checks = array(
    'server booking timezone falls back to UTC if invalid' => strpos($front, "if ( ! WCA_Service::valid_timezone( $default_timezone ) ) { $default_timezone = 'UTC'; }") !== false,
    'booking field uses validated default timezone' => strpos($front, 'esc_attr( $default_timezone )') !== false,
    'localized runtime timezone is validated' => strpos($plugin, "if ( ! WCA_Service::valid_timezone( $runtime_timezone ) ) { $runtime_timezone = 'UTC'; }") !== false,
    'browser timezone preferred for patient-local scheduling' => strpos($js, 'var browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone') !== false && strpos($js, 'if (browserTimezone) tz.value = browserTimezone;') !== false,
    'old UTC-only browser override removed' => strpos($js, "(!tz.value || tz.value === 'UTC')") === false,
);
foreach ($checks as $name=>$ok) { if (!$ok) { fwrite(STDERR,"T18 R3 FAIL: {$name}\n"); exit(1); } }
echo "T18 R3 booking timezone regressions: PASS\n";
''')
runall=Path('tests/run-all.php'); r=runall.read_text(); needle="'t18-r2-appointment-list-authorization-regressions.php',"
if needle in r and 't18-r3-booking-timezone-regressions.php' not in r:
    runall.write_text(r.replace(needle,needle+"\n    't18-r3-booking-timezone-regressions.php',",1))
print('T18 R3 frozen ledger correction applied')