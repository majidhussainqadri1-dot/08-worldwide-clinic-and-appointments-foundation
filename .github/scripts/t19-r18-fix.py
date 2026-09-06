from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ROUND = 'R18'

def replace_once(path, old, new):
    p = ROOT / path
    text = p.read_text(encoding='utf-8')
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{ROUND} {path}: expected exactly one match, found {count}')
    p.write_text(text.replace(old, new, 1), encoding='utf-8')

def replace_exact(path, old, new, expected):
    p = ROOT / path
    text = p.read_text(encoding='utf-8')
    count = text.count(old)
    if count != expected:
        raise SystemExit(f'{ROUND} {path}: expected {expected} matches, found {count}')
    p.write_text(text.replace(old, new), encoding='utf-8')

f = 'includes/class-wca-frontend.php'

# R18-D2: patient-facing appointment timestamps must use the appointment's
# stored patient timezone, not the WordPress site timezone.
replace_once(
    f,
    "\t\t$when = sanitize_text_field( (string) ( $item['scheduled_at_utc'] ?? '' ) );\n\t\t$version = absint( $item['record_version'] ?? 0 );\n",
    "\t\t$when = sanitize_text_field( (string) ( $item['scheduled_at_utc'] ?? '' ) );\n\t\t$timezone = sanitize_text_field( (string) ( $item['timezone'] ?? 'UTC' ) );\n\t\t$display_when = self::appointment_time_label( $when, $timezone );\n\t\t$version = absint( $item['record_version'] ?? 0 );\n"
)
replace_once(
    f,
    "\t\t$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );\n\t\tob_start(); ?>\n",
    "\t\t$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );\n\t\t$timezone = (string) SWC_Helpers::meta( $id, 'patient_timezone', 'UTC' );\n\t\t$display_when = self::appointment_time_label( $when, $timezone );\n\t\tob_start(); ?>\n"
)
replace_exact(
    f,
    "<?php echo esc_html( $when ? get_date_from_gmt( $when, 'F j, Y g:i a' ) : __( 'Time pending', 'worldwide-clinic-appointments' ) ); ?>",
    "<?php echo esc_html( $display_when ); ?>",
    2
)

# R18-D1: browser navigation must obtain a signed calendar link through the
# nonce-authenticated signer instead of navigating directly to a nonce-protected REST export.
replace_once(
    f,
    "<a class=\"wca-button wca-button-secondary\" href=\"<?php echo esc_url( rest_url( 'wca/v1/appointment-refs/' . rawurlencode( $ref ) . '/calendar.ics' ) ); ?>\"><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></a>",
    "<button type=\"button\" class=\"wca-button wca-button-secondary\" data-wca-calendar-download><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></button>"
)
replace_once(
    f,
    "<a class=\"wca-button wca-button-secondary\" href=\"<?php echo esc_url( rest_url( 'wca/v1/appointment-refs/' . rawurlencode( strtolower( $ref ) ) . '/calendar.ics' ) ); ?>\"><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></a>",
    "<button type=\"button\" class=\"wca-button wca-button-secondary\" data-wca-calendar-download><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></button>"
)

helper = r'''
	private static function appointment_time_label( $when, $timezone ) {
		$when = trim( (string) $when );
		$timezone = trim( (string) $timezone );
		if ( '' === $when ) { return __( 'Time pending', 'worldwide-clinic-appointments' ); }
		if ( ! WCA_Service::valid_timezone( $timezone ) ) { $timezone = 'UTC'; }
		try {
			$utc = new DateTimeZone( 'UTC' );
			$target = new DateTimeZone( $timezone );
			$moment = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $when, $utc );
			$errors = DateTimeImmutable::getLastErrors();
			if ( ! $moment || ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) ) {
				return __( 'Time pending', 'worldwide-clinic-appointments' );
			}
			return $moment->setTimezone( $target )->format( 'F j, Y g:i a' ) . ' ' . $timezone;
		} catch ( Exception $e ) {
			return __( 'Time pending', 'worldwide-clinic-appointments' );
		}
	}

'''
replace_once(
    f,
    "\tprivate static function currency_fraction_digits( $currency ) {\n",
    helper + "\tprivate static function currency_fraction_digits( $currency ) {\n"
)

j = 'assets/js/clinic.js'
calendar_js = r'''
		Array.prototype.forEach.call(card.querySelectorAll('[data-wca-calendar-download]'), function (button) {
			button.addEventListener('click', async function () {
				button.disabled = true;
				try {
					var signed = await api('calendar-links/' + encodeURIComponent(ref));
					if (!signed || !signed.url) throw new Error(tr('Calendar export is unavailable.'));
					window.location.assign(String(signed.url));
				} catch (error) {
					setStatus(card, error.message, true);
					button.disabled = false;
				}
			});
		});
'''
replace_once(
    j,
    "\t\t});\n\t}\n\n\tdocument.addEventListener('DOMContentLoaded', function () {\n",
    "\t\t});\n" + calendar_js + "\t}\n\n\tdocument.addEventListener('DOMContentLoaded', function () {\n"
)

# Permanent R18 regression gate.
test = ROOT / 'tests/t19-r18-frontend-calendar-timezone-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ );
$front = file_get_contents( $root . '/includes/class-wca-frontend.php' );
$js = file_get_contents( $root . '/assets/js/clinic.js' );
if ( ! is_string( $front ) || ! is_string( $js ) ) { fwrite( STDERR, "T19 R18 source read failed\n" ); exit( 1 ); }
$checks = array(
    'plain nonce-protected calendar export anchors retired' => false === strpos( $front, "rest_url( 'wca/v1/appointment-refs/'" ),
    'calendar download controls use signer action' => 2 === substr_count( $front, 'data-wca-calendar-download' ),
    'calendar client requests signed link with nonce-aware API helper' => false !== strpos( $js, "api('calendar-links/' + encodeURIComponent(ref))" ),
    'calendar client navigates only after signed URL response' => false !== strpos( $js, 'window.location.assign(String(signed.url))' ),
    'patient projection consumes stored timezone' => false !== strpos( $front, "'timezone'] ?? 'UTC'" ) && false !== strpos( $front, 'appointment_time_label( ' . '$when, $timezone' . ' )' ),
    'legacy detail card consumes stored patient timezone' => false !== strpos( $front, "'patient_timezone', 'UTC'" ),
    'timezone projection validates IANA timezone' => false !== strpos( $front, 'WCA_Service::valid_timezone( ' . '$timezone' . ' )' ),
    'timezone projection converts from canonical UTC' => false !== strpos( $front, "new DateTimeZone( 'UTC' )" ) && false !== strpos( $front, 'setTimezone( ' . '$target' . ' )'),
    'site-timezone get_date_from_gmt appointment rendering retired' => false === strpos( $front, 'get_date_from_gmt( ' . '$when' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R18 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R18 frontend calendar/timezone regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
''', encoding='utf-8')

run = ROOT / 'tests/run-all.php'
rt = run.read_text(encoding='utf-8')
needle = "'t19-r11-availability-exception-regressions.php' );"
replacement = "'t19-r11-availability-exception-regressions.php', 't19-r18-frontend-calendar-timezone-regressions.php' );"
if rt.count(needle) != 1:
    raise SystemExit('R18 run-all insertion point not found')
run.write_text(rt.replace(needle, replacement, 1), encoding='utf-8')
print('T19 R18 frozen ledger corrections applied.')
