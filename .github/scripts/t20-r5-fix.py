from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]

def read(path):
    return (ROOT / path).read_text()

def write(path, content):
    (ROOT / path).write_text(content)

def replace_once(path, old, new):
    source = read(path)
    if source.count(old) != 1:
        raise SystemExit(f"{path}: expected exactly one patch anchor, found {source.count(old)}")
    write(path, source.replace(old, new, 1))

# T20 R5 frozen ledger:
# D1: POST /slot-holds accepts Idempotency-Key at the HTTP guard but the domain hold path only saw body idempotency_key.
# D2: POST /appointment-refs/{ref}/reschedule-holds had the same HTTP/domain key split.
# Normalize the standards-style header into the domain command data at both canonical boundaries.
replace_once(
    'includes/class-wca-rest.php',
    """\tpublic static function hold_slot( WP_REST_Request $request ) {\n\t\t$rate = self::rate_limit( 'slot_hold', 30, 300 );\n\t\tif ( is_wp_error( $rate ) ) { return $rate; }\n\t\treturn self::respond( WCA_Service::hold_slot( self::data( $request ) ), 201 );\n\t}\n""",
    """\tpublic static function hold_slot( WP_REST_Request $request ) {\n\t\t$rate = self::rate_limit( 'slot_hold', 30, 300 );\n\t\tif ( is_wp_error( $rate ) ) { return $rate; }\n\t\t$data = self::data( $request );\n\t\t$header_key = trim( (string) $request->get_header( 'Idempotency-Key' ) );\n\t\tif ( empty( $data['idempotency_key'] ) && $header_key ) { $data['idempotency_key'] = $header_key; }\n\t\treturn self::respond( WCA_Service::hold_slot( $data ), 201 );\n\t}\n"""
)

replace_once(
    'includes/class-wca-opaque-api.php',
    """\tpublic static function reschedule_hold( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( is_wp_error( $id ) ) { return $id; }\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\treturn self::respond( WCA_Service::hold_reschedule_slot( $id, self::data( $request ), get_current_user_id() ), 201 );\n\t}\n""",
    """\tpublic static function reschedule_hold( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( is_wp_error( $id ) ) { return $id; }\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$data = self::data( $request );\n\t\t$header_key = trim( (string) $request->get_header( 'Idempotency-Key' ) );\n\t\tif ( empty( $data['idempotency_key'] ) && $header_key ) { $data['idempotency_key'] = $header_key; }\n\t\treturn self::respond( WCA_Service::hold_reschedule_slot( $id, $data, get_current_user_id() ), 201 );\n\t}\n"""
)

test_path = ROOT / 'tests/t20-r5-idempotency-header-parity-regressions.php'
test_path.write_text(r'''<?php
$root = dirname( __DIR__ );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$opaque = file_get_contents( $root . '/includes/class-wca-opaque-api.php' );
$hard = file_get_contents( $root . '/includes/class-wca-ten-review-hardening.php' );
$guard = file_get_contents( $root . '/includes/class-wca-plan-guard.php' );
$failures = array(); $checks = 0;
function t20r5_check( $name, $ok ) { global $failures, $checks; $checks++; if ( ! $ok ) { $failures[] = $name; } }
foreach ( array( $rest, $opaque, $hard, $guard ) as $source ) { t20r5_check( 'source readable', is_string( $source ) ); }
t20r5_check( 'HTTP mutation guard accepts Idempotency-Key header', false !== strpos( $hard, "$request->get_header( 'Idempotency-Key' )" ) );
t20r5_check( 'slot-hold route normalizes header key into command data', false !== strpos( $rest, "$header_key = trim( (string) $request->get_header( 'Idempotency-Key' ) )" ) && false !== strpos( $rest, "$data['idempotency_key'] = $header_key" ) && false !== strpos( $rest, 'WCA_Service::hold_slot( $data )' ) );
t20r5_check( 'reschedule-hold route normalizes header key into command data', false !== strpos( $opaque, "$header_key = trim( (string) $request->get_header( 'Idempotency-Key' ) )" ) && false !== strpos( $opaque, "$data['idempotency_key'] = $header_key" ) && false !== strpos( $opaque, 'WCA_Service::hold_reschedule_slot( $id, $data, get_current_user_id() )' ) );
t20r5_check( 'domain slot hold still requires explicit idempotency value', false !== strpos( $guard, "empty( $data['idempotency_key'] )" ) );
t20r5_check( 'reschedule-hold is covered by cross-cutting HTTP idempotency', false !== strpos( $hard, 'transitions|payment-intents|reschedule-holds' ) );
if ( $failures ) { fwrite( STDERR, "T20 R5 idempotency-header parity regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "T20 R5 idempotency-header parity regressions: PASS {$checks}/{$checks}.\n";
''')

path = 'tests/run-all.php'
source = read(path)
old = "'t20-r4-reschedule-lifecycle-regressions.php' );"
new = "'t20-r4-reschedule-lifecycle-regressions.php', 't20-r5-idempotency-header-parity-regressions.php' );"
if source.count(old) != 1:
    raise SystemExit('tests/run-all.php: R5 aggregation anchor missing or ambiguous')
write(path, source.replace(old, new, 1))
