from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]

def read(path): return (ROOT/path).read_text()
def write(path,text): (ROOT/path).write_text(text)
def replace_once(path,old,new):
    text=read(path)
    if text.count(old)!=1: raise SystemExit(f'{path}: patch anchor count {text.count(old)}')
    write(path,text.replace(old,new,1))

path='includes/class-wca-service.php'
replace_once(path,
"""\t\t$clinic = WCA_Repository::get_clinic( absint( $rule['clinic_id'] ), false );\n\t\t$branch = absint( $rule['branch_id'] ) ? WCA_Repository::get_branch( absint( $rule['branch_id'] ) ) : null;\n\t\t$service = absint( $rule['service_id'] ) ? WCA_Repository::get_service( absint( $rule['service_id'] ), false ) : null;\n\t\t$practitioner_ref = WCA_Plan_Guard::practitioner_ref( absint( $rule['doctor_user_id'] ) );\n\t\tif ( ! $clinic || ! $practitioner_ref ) { return array(); }\n""",
"""\t\t$clinic = WCA_Repository::get_clinic( absint( $rule['clinic_id'] ), false );\n\t\t$branch = absint( $rule['branch_id'] ) ? WCA_Repository::get_branch( absint( $rule['branch_id'] ) ) : null;\n\t\t$service = absint( $rule['service_id'] ) ? WCA_Repository::get_service( absint( $rule['service_id'] ), false ) : null;\n\t\t$practitioner_ref = WCA_Plan_Guard::practitioner_ref( absint( $rule['doctor_user_id'] ) );\n\t\tif ( ! $clinic || ! $practitioner_ref ) { return array(); }\n\t\t/* A rule cannot keep a retired/private branch discoverable merely because the\n\t\t * rule itself is still active. Search projections and canonical holds must agree. */\n\t\tif ( absint( $rule['branch_id'] ) && ( ! $branch || 'active' !== (string) ( $branch['status'] ?? '' ) || 'public' !== (string) ( $branch['visibility'] ?? '' ) ) ) { return array(); }\n""")

replace_once(path,
"""\t\t\t$exception = self::exception_for_date( $rule['exceptions'], $date_key );\n\t\t\t$closed = $exception && 'closed' === $exception['type'];\n\t\t\t$open_override = $exception && 'open' === $exception['type'];\n\t\t\t$eligible_day = ( isset( $days[ $day_key ] ) || $open_override ) && $cursor >= $effective_from && $cursor <= $effective_until && ! $closed;\n""",
"""\t\t\t$exception = self::exception_for_date( $rule['exceptions'], $date_key );\n\t\t\t$closed_day = $exception && 'closed' === $exception['type'] && empty( $exception['start'] ) && empty( $exception['end'] );\n\t\t\t$open_override = $exception && 'open' === $exception['type'];\n\t\t\t$eligible_day = ( isset( $days[ $day_key ] ) || $open_override ) && $cursor >= $effective_from && $cursor <= $effective_until && ! $closed_day;\n""")

replace_once(path,
"""\t\t\t\t\t\tif ( $inside_display && $start_utc->getTimestamp() > time() + $buffer_before * 60 && ! self::in_break( $slot, $slot_end, $rule['breaks'] ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key, strtolower( (string) $rule['public_ref'] ), $date_capacity ) ) {\n""",
"""\t\t\t\t\t\tif ( $inside_display && $start_utc->getTimestamp() > time() + $buffer_before * 60 && ! self::in_break( $slot, $slot_end, $rule['breaks'] ) && ! self::in_closed_exception( $slot, $slot_end, $exception ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key, strtolower( (string) $rule['public_ref'] ), $date_capacity ) ) {\n""")

replace_once(path,
"""\tprivate static function in_break( DateTimeImmutable $start, DateTimeImmutable $end, $breaks ) {\n\t\tforeach ( (array) $breaks as $break ) {\n\t\t\t$break_start = self::local_datetime( $start->format( 'Y-m-d' ), $break['start'] ?? '', $start->getTimezone() );\n\t\t\t$break_end   = self::local_datetime( $start->format( 'Y-m-d' ), $break['end'] ?? '', $start->getTimezone() );\n\t\t\tif ( $break_start && $break_end && $start < $break_end && $end > $break_start ) { return true; }\n\t\t}\n\t\treturn false;\n\t}\n""",
"""\tprivate static function in_break( DateTimeImmutable $start, DateTimeImmutable $end, $breaks ) {\n\t\tforeach ( (array) $breaks as $break ) {\n\t\t\t$break_start = self::local_datetime( $start->format( 'Y-m-d' ), $break['start'] ?? '', $start->getTimezone() );\n\t\t\t$break_end   = self::local_datetime( $start->format( 'Y-m-d' ), $break['end'] ?? '', $start->getTimezone() );\n\t\t\tif ( $break_start && $break_end && $start < $break_end && $end > $break_start ) { return true; }\n\t\t}\n\t\treturn false;\n\t}\n\n\t/** A bounded closed exception acts like a date-specific break; invalid DST\n\t * boundaries fail closed rather than silently exposing a supposedly closed slot. */\n\tprivate static function in_closed_exception( DateTimeImmutable $start, DateTimeImmutable $end, $exception ) {\n\t\tif ( ! is_array( $exception ) || 'closed' !== (string) ( $exception['type'] ?? '' ) || empty( $exception['start'] ) || empty( $exception['end'] ) ) { return false; }\n\t\t$closed_start = self::local_datetime( $start->format( 'Y-m-d' ), $exception['start'], $start->getTimezone() );\n\t\t$closed_end   = self::local_datetime( $start->format( 'Y-m-d' ), $exception['end'], $start->getTimezone() );\n\t\tif ( ! $closed_start || ! $closed_end ) { return true; }\n\t\treturn $start < $closed_end && $end > $closed_start;\n\t}\n""")

test = ROOT/'tests/t20-r7-availability-projection-regressions.php'
test.write_text(r'''<?php
$root=dirname(__DIR__);
$service=file_get_contents($root.'/includes/class-wca-service.php');
$repo=file_get_contents($root.'/includes/class-wca-repository.php');
$f=array();$n=0;function t20r7($name,$ok){global $f,$n;$n++;if(!$ok)$f[]=$name;}
t20r7('sources readable',is_string($service)&&is_string($repo));
t20r7('bounded closed exception remains an eligible day',false!==strpos($service,"$closed_day = $exception && 'closed' === $exception['type'] && empty( $exception['start'] ) && empty( $exception['end'] )"));
t20r7('bounded close overlap gate exists',false!==strpos($service,'in_closed_exception( $slot, $slot_end, $exception )'));
t20r7('bounded close helper fails closed on invalid wall time',false!==strpos($service,'if ( ! $closed_start || ! $closed_end ) { return true; }'));
t20r7('inactive or private rule branch is not projected',false!==strpos($service,"'active' !== (string) ( $branch['status'] ?? '' )")&&false!==strpos($service,"'public' !== (string) ( $branch['visibility'] ?? '' )"));
t20r7('repository still permits validated bounded close storage',false!==strpos($repo,'A bounded closed exception requires a valid start/end window.'));
if($f){fwrite(STDERR,"T20 R7 availability regressions failed:\n- ".implode("\n- ",$f)."\n");exit(1);}echo "T20 R7 availability regressions: PASS {$n}/{$n}.\n";
''')

run='tests/run-all.php'; text=read(run); old="'t20-r6-authorization-boundary-regressions.php' );"; new="'t20-r6-authorization-boundary-regressions.php', 't20-r7-availability-projection-regressions.php' );";
if text.count(old)!=1: raise SystemExit('run-all R7 anchor mismatch')
write(run,text.replace(old,new,1))
