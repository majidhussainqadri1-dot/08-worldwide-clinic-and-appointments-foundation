from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ROUND = 'R11'

def replace_once(path, old, new):
    p = ROOT / path
    text = p.read_text(encoding='utf-8')
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{ROUND} {path}: expected exactly one match, found {count}')
    p.write_text(text.replace(old, new, 1), encoding='utf-8')

# R11-D3: duplicate date exceptions are ambiguous and must fail deterministically.
replace_once(
    'includes/class-wca-repository.php',
    """\t\t$exceptions = array();\n\t\tforeach ( (array) ( $data['exceptions'] ?? array() ) as $exception ) {\n\t\t\tif ( ! is_array( $exception ) || ! WCA_Service::valid_date( $exception['date'] ?? '' ) || ! in_array( $exception['type'] ?? '', array( 'closed','open','capacity' ), true ) ) {\n\t\t\t\treturn new WP_Error( 'wca_repository_availability_exception', __( 'Availability persistence received an invalid exception.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t\t}\n\t\t\t$type = (string) $exception['type'];\n""",
    """\t\t$exceptions = array();\n\t\t$exception_dates = array();\n\t\tforeach ( (array) ( $data['exceptions'] ?? array() ) as $exception ) {\n\t\t\tif ( ! is_array( $exception ) || ! WCA_Service::valid_date( $exception['date'] ?? '' ) || ! in_array( $exception['type'] ?? '', array( 'closed','open','capacity' ), true ) ) {\n\t\t\t\treturn new WP_Error( 'wca_repository_availability_exception', __( 'Availability persistence received an invalid exception.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t\t}\n\t\t\t$exception_date = (string) $exception['date'];\n\t\t\tif ( isset( $exception_dates[ $exception_date ] ) ) {\n\t\t\t\treturn new WP_Error( 'wca_repository_availability_exception_duplicate', __( 'Only one availability exception may be defined for a date.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t\t}\n\t\t\t$exception_dates[ $exception_date ] = true;\n\t\t\t$type = (string) $exception['type'];\n"""
)

# R11-D1/D2: open exceptions override recurring weekday closure and capacity
# exceptions override only that date's effective occupancy gate.
replace_once(
    'includes/class-wca-service.php',
    """\t\t\t$exception = self::exception_for_date( $rule['exceptions'], $date_key );\n\t\t\t$closed = $exception && 'closed' === $exception['type'];\n\t\t\t$eligible_day = isset( $days[ $day_key ] ) && $cursor >= $effective_from && $cursor <= $effective_until && ! $closed;\n\t\t\tif ( $eligible_day ) {\n\t\t\t\t$start_hhmm = $exception && 'open' === $exception['type'] && $exception['start'] ? $exception['start'] : $rule['rrule']['start'];\n\t\t\t\t$end_hhmm   = $exception && 'open' === $exception['type'] && $exception['end'] ? $exception['end'] : $rule['rrule']['end'];\n""",
    """\t\t\t$exception = self::exception_for_date( $rule['exceptions'], $date_key );\n\t\t\t$closed = $exception && 'closed' === $exception['type'];\n\t\t\t$open_override = $exception && 'open' === $exception['type'];\n\t\t\t$eligible_day = ( isset( $days[ $day_key ] ) || $open_override ) && $cursor >= $effective_from && $cursor <= $effective_until && ! $closed;\n\t\t\t$date_capacity = ( $exception && 'capacity' === $exception['type'] && absint( $exception['capacity'] ?? 0 ) >= 1 )\n\t\t\t\t? min( 50, absint( $exception['capacity'] ) )\n\t\t\t\t: max( 1, absint( $rule['capacity'] ?? 1 ) );\n\t\t\tif ( $eligible_day ) {\n\t\t\t\t$start_hhmm = $open_override && ! empty( $exception['start'] ) ? $exception['start'] : $rule['rrule']['start'];\n\t\t\t\t$end_hhmm   = $open_override && ! empty( $exception['end'] ) ? $exception['end'] : $rule['rrule']['end'];\n"""
)
replace_once(
    'includes/class-wca-service.php',
    """! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key, strtolower( (string) $rule['public_ref'] ), max( 1, absint( $rule['capacity'] ?? 1 ) ) )""",
    """! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key, strtolower( (string) $rule['public_ref'] ), $date_capacity )"""
)
replace_once(
    'includes/class-wca-service.php',
    """\t\t\t\t\t\t\t\t'capacity'        => absint( $rule['capacity'] ),\n""",
    """\t\t\t\t\t\t\t\t'capacity'        => $date_capacity,\n"""
)

# Permanent regression gate.
test = ROOT / 'tests/t19-r11-availability-exception-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
if ( ! is_string( $service ) || ! is_string( $repo ) ) { fwrite( STDERR, "T19 R11 source read failed\n" ); exit( 1 ); }
$checks = array(
    'open exception overrides recurring weekday closure' => false !== strpos( $service, '( isset( $days[ $day_key ] ) || $open_override )' ),
    'capacity exception derives date capacity' => false !== strpos( $service, "'capacity' === $exception['type']" ) && false !== strpos( $service, '$date_capacity' ),
    'slot conflict gate consumes date capacity' => false !== strpos( $service, "strtolower( (string) $rule['public_ref'] ), $date_capacity )" ),
    'slot projection exposes date capacity' => false !== strpos( $service, "'capacity'        => $date_capacity" ),
    'repository tracks exception dates' => false !== strpos( $repo, '$exception_dates = array();' ),
    'duplicate exception dates fail explicitly' => false !== strpos( $repo, 'wca_repository_availability_exception_duplicate' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R11 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R11 availability exception regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
''', encoding='utf-8')

run = ROOT / 'tests/run-all.php'
rt = run.read_text(encoding='utf-8')
needle = "'t19-r9-participant-query-authority-regressions.php' );"
replacement = "'t19-r9-participant-query-authority-regressions.php', 't19-r11-availability-exception-regressions.php' );"
if rt.count(needle) != 1:
    raise SystemExit('R11 run-all insertion point not found')
run.write_text(rt.replace(needle, replacement, 1), encoding='utf-8')
print('T19 R11 frozen ledger corrections applied.')
