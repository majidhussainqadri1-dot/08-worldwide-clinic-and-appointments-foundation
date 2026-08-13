from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:100]!r}')
 wr(p,s.replace(a,b,1))

p='includes/class-wca-repository.php'
# Exact currency: sanitize text, then reject anything other than exactly 3 ASCII letters.
once(p,"\t\t$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['currency'] ?? '' ) ) );","\t\t$currency_raw = trim( (string) ( $data['currency'] ?? '' ) );\n\t\t$currency = strtoupper( $currency_raw );")
# Replace malformed triple check with one correct pre-lock check.
old="""\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );
\t\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }"""
new="""\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );
\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }"""
once(p,old,new)
# Distinguish lock DB failure from legitimate contention.
once(p,"\t\t$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock_name ) );\n\t\tif ( 1 !== $locked ) {","\t\t$locked_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock_name ) );\n\t\tif ( null === $locked_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_lock_read_failed', __( 'The scheduling lock could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$locked = (int) $locked_raw;\n\t\tif ( 1 !== $locked ) {")
# Inside-lock reread.
once(p,"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );\n\t\t\t$existing = $replay( $existing );","\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );\n\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_locked_read_failed', __( 'Current slot-hold state could not be verified inside the scheduling lock.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$existing = $replay( $existing );")
# Insert race reread.
once(p,"\t\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );\n\t\t\t\t$existing = $replay( $existing );","\t\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );\n\t\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_race_read_failed', __( 'Concurrent slot-hold state could not be reconciled safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t\t$existing = $replay( $existing );")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R5 exact currency validation','includes/class-wca-repository.php','$currency_raw = trim');
t15h('R5 slot lock read failure','includes/class-wca-repository.php','wca_slot_lock_read_failed');
t15h('R5 inside-lock hold read failure','includes/class-wca-repository.php','wca_slot_hold_locked_read_failed');
t15h('R5 insert-race hold read failure','includes/class-wca-repository.php','wca_slot_hold_race_read_failed');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('gate marker')
wr(p,s.replace(mark,ins+mark,1))
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R4 — strict temporal / timezone / DST review

R4 completed against the R3-corrected state without source modification during review. Canonical UTC slot evidence, public date ranges, IANA timezone identifiers, DST local-time round trips and DOB round trips were re-traced. No new supported temporal product defect was proven.

R4 result: **CLEAN — no correction required.**

## R5 — numeric / money / bounds / code-integrity review

R5 completed before correction. It found that service currency silently stripped non-letters before validating; the R1 slot-hold read checks had been mechanically concentrated at one location instead of covering all three authoritative reads; and a DB failure acquiring the slot advisory lock was indistinguishable from normal contention. All R5 findings are corrected together after review completion.

R5 result: **SUPPORTED DEFECTS FOUND — full retest required before R6.**
"""; wr(p,s)
