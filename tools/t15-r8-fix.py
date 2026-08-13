from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:120]!r}')
 wr(p,s.replace(a,b,1))

p='includes/class-wca-opaque-api.php'
once(p,"\t\t$proxy = new WP_REST_Request( 'POST', '/wca/v1/appointments/' . $id . '/payment-intents' );\n\t\t$proxy->set_url_params( array( 'id' => $id ) );\n\t\t$proxy->set_body_params( self::data( $request ) );","\t\t$proxy = new WP_REST_Request( 'POST', '/wca/v1/appointments/' . $id . '/payment-intents' );\n\t\t$proxy->set_url_params( array( 'id' => $id ) );\n\t\t$proxy->set_body_params( self::data( $request ) );\n\t\t$proxy->set_header( 'Idempotency-Key', trim( (string) $request->get_header( 'Idempotency-Key' ) ) );")

p='includes/class-wca-second-ten-review-hardening.php'
once(p,"\t\t$row = $wpdb->get_row(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT id,status,updated_at FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1\",\n\t\t\t\t$scope,\n\t\t\t\thash( 'sha256', $key ),\n\t\t\t\t$actor\n\t\t\t),\n\t\t\tARRAY_A\n\t\t);\n\t\tif ( $row && 'processing' === (string) $row['status']","\t\t$row = $wpdb->get_row(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT id,status,updated_at FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1\",\n\t\t\t\t$scope,\n\t\t\t\thash( 'sha256', $key ),\n\t\t\t\t$actor\n\t\t\t),\n\t\t\tARRAY_A\n\t\t);\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_stale_idempotency_read_failed', __( 'Current mutation replay state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( $row && 'processing' === (string) $row['status']")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R8 opaque payment preserves idempotency header','includes/class-wca-opaque-api.php',"set_header( 'Idempotency-Key'");
t15h('R8 stale idempotency precheck read failure','includes/class-wca-second-ten-review-hardening.php','wca_stale_idempotency_read_failed');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('gate marker missing')
wr(p,s.replace(mark,ins+mark,1))

p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R8 — REST routes / permission / opaque-reference / cache review

R8 completed before correction. Numeric legacy protected endpoints remain disabled by default and external payload stripping/no-store layers were re-traced. Two supported defects remained: the canonical opaque payment-intent adapter did not copy the caller's Idempotency-Key into its internal proxy request, and the stale-idempotency precheck did not distinguish an SQL read failure from no existing replay row. Both are corrected together after R8 closure.

R8 result: **SUPPORTED DEFECTS FOUND — full retest required before R9.**
"""; wr(p,s)
