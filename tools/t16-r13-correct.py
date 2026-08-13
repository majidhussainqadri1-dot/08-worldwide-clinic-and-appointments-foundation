from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]

def rep(path,old,new,count=1):
 p=ROOT/path; s=p.read_text(); n=s.count(old)
 if n!=count: raise SystemExit(f'{path}: expected {count}, got {n}: {old[:100]!r}')
 p.write_text(s.replace(old,new,count))

# DST-safe slot construction: use the existing strict/ambiguous-aware local->UTC conversion.
rep('includes/class-wca-service.php',"""\tprivate static function local_datetime( $date, $time, DateTimeZone $zone ) {
\t\t$value = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, $zone );
\t\t$errors = DateTimeImmutable::getLastErrors();
\t\tif ( ! $value || ( is_array( $errors ) && ( $errors['warning_count'] || $errors['error_count'] ) ) || $value->format( 'Y-m-d H:i' ) !== $date . ' ' . $time ) {
\t\t\treturn null;
\t\t}
\t\treturn $value;
\t}
""","""\tprivate static function local_datetime( $date, $time, DateTimeZone $zone ) {
\t\t/* Reuse the canonical ambiguity-aware converter: nonexistent and repeated DST wall times fail closed. */
\t\t$utc = SWC_Helpers::to_utc( (string) $date, (string) $time, $zone->getName() );
\t\tif ( ! $utc ) { return null; }
\t\t$value = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $utc, new DateTimeZone( 'UTC' ) );
\t\treturn $value ? $value->setTimezone( $zone ) : null;
\t}
""")

# Core availability before/after buffers must expand the conflict window, not merely exist as stored fields.
rep('includes/class-wca-service.php',"""\t\t\t\t\t\t$display_date = $start_utc->setTimezone( $display_zone )->format( 'Y-m-d' );
\t\t\t\t\t\t$inside_display = ( ! $display_from || ( $display_date >= $display_from && $display_date <= $display_to ) );
\t\t\t\t\t\tif ( $inside_display && $start_utc->getTimestamp() > time() + max( 0, absint( $rule['buffer_before'] ) ) * 60 && ! self::in_break( $slot, $slot_end, $rule['breaks'] ) && ! SWC_Helpers::has_conflict( absint( $rule['doctor_user_id'] ), $start_utc->format( 'Y-m-d H:i:s' ), $duration, 0 ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $start_utc->format( 'Y-m-d H:i:s' ), $end_utc->format( 'Y-m-d H:i:s' ), $ignore_hold_key ) ) {
""","""\t\t\t\t\t\t$display_date = $start_utc->setTimezone( $display_zone )->format( 'Y-m-d' );
\t\t\t\t\t\t$inside_display = ( ! $display_from || ( $display_date >= $display_from && $display_date <= $display_to ) );
\t\t\t\t\t\t$buffer_before = max( 0, absint( $rule['buffer_before'] ?? 0 ) );
\t\t\t\t\t\t$buffer_after  = max( 0, absint( $rule['buffer_after'] ?? 0 ) );
\t\t\t\t\t\t$conflict_start = $start_utc->modify( '-' . $buffer_before . ' minutes' );
\t\t\t\t\t\t$conflict_end   = $end_utc->modify( '+' . $buffer_after . ' minutes' );
\t\t\t\t\t\t$conflict_minutes = max( 1, (int) ceil( ( $conflict_end->getTimestamp() - $conflict_start->getTimestamp() ) / 60 ) );
\t\t\t\t\t\tif ( $inside_display && $start_utc->getTimestamp() > time() + $buffer_before * 60 && ! self::in_break( $slot, $slot_end, $rule['breaks'] ) && ! SWC_Helpers::has_conflict( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_minutes, 0 ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key ) ) {
""")

# Carry authoritative rule buffers into the atomic hold guard.
p=ROOT/'includes/class-wca-plan-guard.php'; s=p.read_text()
needle="\t\t\t\t'end_utc'         => $end,\n"
if s.count(needle)!=1: raise SystemExit('plan guard end_utc insertion point mismatch')
s=s.replace(needle,needle+"\t\t\t\t'buffer_before'   => min( 240, absint( $rule['buffer_before'] ?? 0 ) ),\n\t\t\t\t'buffer_after'    => min( 240, absint( $rule['buffer_after'] ?? 0 ) ),\n",1); p.write_text(s)

# Recheck buffered overlap while holding the doctor-wide scheduling lock.
p=ROOT/'includes/class-wca-repository.php'; s=p.read_text()
needle="\t\t$ttl = WCA_Service::strict_int( $data['ttl'] ?? 600, 300, 1800 );\n\t\tif ( null === $ttl ) { return new WP_Error( 'wca_slot_ttl_range', __( 'Slot-hold TTL must be an integer from 300 through 1800 seconds.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n"
add=needle+"\t\t$buffer_before = WCA_Service::strict_int( $data['buffer_before'] ?? 0, 0, 240 );\n\t\t$buffer_after  = WCA_Service::strict_int( $data['buffer_after'] ?? 0, 0, 240 );\n\t\tif ( null === $buffer_before || null === $buffer_after ) { return new WP_Error( 'wca_slot_buffer_range', __( 'Canonical slot buffers must be integers from 0 through 240 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n"
if s.count(needle)!=1: raise SystemExit('repo ttl point mismatch')
s=s.replace(needle,add,1)
old="""\t\t\t$conflict = $wpdb->get_var( $wpdb->prepare(
\t\t\t\t\"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\",
\t\t\t\t$doctor_id, self::now(), $end, $start
\t\t\t) );
\t\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_conflict_query_failed', __( 'Current slot conflicts could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t$duration = max( 1, (int) round( ( strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) ) / 60 ) );
\t\t\tif ( $conflict || SWC_Helpers::has_conflict( $doctor_id, $start, $duration, 0 ) ) {
"""
new="""\t\t\t$conflict_start = gmdate( 'Y-m-d H:i:s', strtotime( $start . ' UTC' ) - $buffer_before * 60 );
\t\t\t$conflict_end   = gmdate( 'Y-m-d H:i:s', strtotime( $end . ' UTC' ) + $buffer_after * 60 );
\t\t\t$conflict = $wpdb->get_var( $wpdb->prepare(
\t\t\t\t\"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\",
\t\t\t\t$doctor_id, self::now(), $conflict_end, $conflict_start
\t\t\t) );
\t\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_conflict_query_failed', __( 'Current slot conflicts could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t$conflict_duration = max( 1, (int) round( ( strtotime( $conflict_end . ' UTC' ) - strtotime( $conflict_start . ' UTC' ) ) / 60 ) );
\t\t\tif ( $conflict || SWC_Helpers::has_conflict( $doctor_id, $conflict_start, $conflict_duration, 0 ) ) {
"""
if s.count(old)!=1: raise SystemExit('repo conflict block mismatch')
s=s.replace(old,new,1); p.write_text(s)

# Permanent R13 regression evidence.
p=ROOT/'tests/sixteenth-twenty-review-regressions.php'; s=p.read_text(); marker='if($fail){fwrite(STDERR,'
if marker not in s: raise SystemExit('T16 marker missing')
checks="""t16h('R13 canonical slot wall-time conversion rejects DST ambiguity','includes/class-wca-service.php','SWC_Helpers::to_utc( (string) $date, (string) $time, $zone->getName() )');
t16h('R13 projected slot conflict expands buffer before','includes/class-wca-service.php','$conflict_start = $start_utc->modify');
t16h('R13 projected slot conflict expands buffer after','includes/class-wca-service.php','$conflict_end   = $end_utc->modify');
t16h('R13 canonical hold carries authoritative rule buffers','includes/class-wca-plan-guard.php',"'buffer_after'    => min( 240");
t16h('R13 atomic hold validates buffer range','includes/class-wca-repository.php','wca_slot_buffer_range');
t16h('R13 atomic hold rechecks buffered conflict window','includes/class-wca-repository.php','$conflict_duration = max');
"""
s=s.replace(marker,checks+marker,1); p.write_text(s)
print('R13 closed ledger applied')
