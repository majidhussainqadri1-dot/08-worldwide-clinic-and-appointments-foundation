from pathlib import Path
ROOT=Path('.')
def read(p): return (ROOT/p).read_text()
def write(p,s): (ROOT/p).write_text(s)
def once(p,old,new):
    s=read(p); n=s.count(old)
    if n!=1: raise SystemExit(f'{p}: expected 1 match, got {n}: {old[:140]!r}')
    write(p,s.replace(old,new,1))

# R6-A: fenced outbox claim must distinguish storage failure from ordinary contention.
p='includes/class-wca-repository.php'
once(p,
"\t\t\t$ok = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='processing',locked_at=%s,locked_by=%s,updated_at=%s WHERE id=%d AND status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s)\", $claimed_at, $worker, $claimed_at, absint( $id ), $claimed_at, $stale_before ) );\n\t\t\tif ( 1 === (int) $ok ) {",
"\t\t\t$ok = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='processing',locked_at=%s,locked_by=%s,updated_at=%s WHERE id=%d AND status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s)\", $claimed_at, $worker, $claimed_at, absint( $id ), $claimed_at, $stale_before ) );\n\t\t\tif ( false === $ok ) { return new WP_Error( 'wca_outbox_claim_write_failed', __( 'Pending outbox work could not be claimed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( 1 === (int) $ok ) {"
)
once(p,
"\t\t\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_outbox_claim_readback_failed', __( 'Claimed outbox work could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t\tif ( $row ) { $row['payload'] = self::decode( $row['payload_json'] ); unset( $row['payload_json'] ); $claimed[] = $row; }",
"\t\t\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_outbox_claim_readback_failed', __( 'Claimed outbox work could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t\tif ( ! $row ) { return new WP_Error( 'wca_outbox_claim_readback_missing', __( 'The outbox claim succeeded but its fenced row could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t\t$row['payload'] = self::decode( $row['payload_json'] ); unset( $row['payload_json'] ); $claimed[] = $row;"
)

# R6-B: distinguish DB failure from a legitimate CAS miss in finalization/release operations.
once(p,
"\t\t$updated = $wpdb->update( $table, array( 'status' => 'delivered', 'delivered_at' => self::now(), 'updated_at' => self::now(), 'locked_at' => null, 'locked_by' => '' ), array( 'id' => absint( $id ), 'status' => 'processing', 'locked_by' => $worker ) );\n\t\treturn 1 === (int) $updated;",
"\t\t$updated = $wpdb->update( $table, array( 'status' => 'delivered', 'delivered_at' => self::now(), 'updated_at' => self::now(), 'locked_at' => null, 'locked_by' => '' ), array( 'id' => absint( $id ), 'status' => 'processing', 'locked_by' => $worker ) );\n\t\tif ( false === $updated && class_exists( 'WCA_Observability' ) ) { WCA_Observability::metric( 'outbox_complete_db_failed_total', 1 ); WCA_Observability::log( 'error', 'outbox_complete_db_failed', array( 'outbox_id' => absint( $id ) ) ); }\n\t\treturn 1 === (int) $updated;"
)
once(p,
"\t\t$updated = $wpdb->update( $table, array( 'status' => $status, 'attempts' => $attempts, 'last_error' => substr( sanitize_text_field( $error ), 0, 500 ), 'next_attempt_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ), 'updated_at' => self::now(), 'locked_at' => null, 'locked_by' => '' ), array( 'id' => absint( $id ), 'status' => 'processing', 'locked_by' => $worker ) );\n\t\treturn 1 === (int) $updated;",
"\t\t$updated = $wpdb->update( $table, array( 'status' => $status, 'attempts' => $attempts, 'last_error' => substr( sanitize_text_field( $error ), 0, 500 ), 'next_attempt_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ), 'updated_at' => self::now(), 'locked_at' => null, 'locked_by' => '' ), array( 'id' => absint( $id ), 'status' => 'processing', 'locked_by' => $worker ) );\n\t\tif ( false === $updated && class_exists( 'WCA_Observability' ) ) { WCA_Observability::metric( 'outbox_fail_db_failed_total', 1 ); WCA_Observability::log( 'error', 'outbox_fail_db_failed', array( 'outbox_id' => absint( $id ) ) ); }\n\t\treturn 1 === (int) $updated;"
)
once(p,
"\t\t$deleted = $wpdb->delete( $table, array( 'id' => absint( $id ), 'status' => 'processing' ) );\n\t\treturn 1 === (int) $deleted;",
"\t\t$deleted = $wpdb->delete( $table, array( 'id' => absint( $id ), 'status' => 'processing' ) );\n\t\tif ( false === $deleted && class_exists( 'WCA_Observability' ) ) { WCA_Observability::metric( 'idempotency_release_db_failed_total', 1 ); WCA_Observability::log( 'error', 'idempotency_release_db_failed', array( 'reservation_id' => absint( $id ) ) ); }\n\t\treturn 1 === (int) $deleted;"
)

# R6-C: ambiguous server failures must retain the reservation for reconciliation;
# only definitive client failures release it for a corrected/new attempt.
p='includes/class-wca-ten-review-hardening.php'
old="""\t\tif ( is_wp_error( $response ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $response; }
\t\t$response = $response instanceof WP_REST_Response ? $response : rest_ensure_response( $response );
\t\tif ( ! ( $response instanceof WP_REST_Response ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $response; }
\t\t$status = absint( $response->get_status() );
\t\tif ( $status >= 200 && $status < 400 ) {
\t\t\tif ( ! WCA_Repository::complete_idempotency( $claim['id'], $status, $response->get_data() ) ) {
\t\t\t\tWCA_Observability::metric( 'http_idempotency_finalize_failed_total', 1, array( 'route_scope' => substr( hash( 'sha256', $claim['route'] ), 0, 12 ) ) );
\t\t\t\treturn new WP_Error( 'wca_idempotency_finalize_failed', __( 'The mutation may have completed, but replay evidence could not be finalized. Query mutation status before retrying.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'reconciliation_required' => true ) );
\t\t\t}
\t\t} else {
\t\t\tWCA_Repository::release_idempotency( $claim['id'] );
\t\t}
\t\t$response->header( 'X-WCA-Idempotency-Key', $claim['key'] );
\t\treturn $response;"""
new="""\t\tif ( is_wp_error( $response ) ) {
\t\t\t$error_data = $response->get_error_data();
\t\t\t$status = absint( is_array( $error_data ) ? ( $error_data['status'] ?? 0 ) : 0 );
\t\t\tif ( ! $status || $status >= 500 ) {
\t\t\t\tWCA_Observability::metric( 'http_idempotency_ambiguous_error_total', 1, array( 'route_scope' => substr( hash( 'sha256', $claim['route'] ), 0, 12 ) ) );
\t\t\t\t$data = is_array( $error_data ) ? $error_data : array();
\t\t\t\t$data['status'] = $status ?: 503;
\t\t\t\t$data['reconciliation_required'] = true;
\t\t\t\t$data['idempotency_key'] = $claim['key'];
\t\t\t\t$response->add_data( $data, $response->get_error_code() );
\t\t\t\treturn $response;
\t\t\t}
\t\t\tif ( ! WCA_Repository::release_idempotency( $claim['id'] ) ) { WCA_Observability::metric( 'http_idempotency_release_failed_total', 1 ); }
\t\t\treturn $response;
\t\t}
\t\t$response = $response instanceof WP_REST_Response ? $response : rest_ensure_response( $response );
\t\tif ( ! ( $response instanceof WP_REST_Response ) ) {
\t\t\tWCA_Observability::metric( 'http_idempotency_unverifiable_response_total', 1 );
\t\t\treturn new WP_Error( 'wca_idempotency_response_unverifiable', __( 'The mutation response could not be verified. Query mutation status before retrying.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'reconciliation_required' => true, 'idempotency_key' => $claim['key'] ) );
\t\t}
\t\t$status = absint( $response->get_status() );
\t\tif ( $status >= 200 && $status < 400 ) {
\t\t\tif ( ! WCA_Repository::complete_idempotency( $claim['id'], $status, $response->get_data() ) ) {
\t\t\t\tWCA_Observability::metric( 'http_idempotency_finalize_failed_total', 1, array( 'route_scope' => substr( hash( 'sha256', $claim['route'] ), 0, 12 ) ) );
\t\t\t\treturn new WP_Error( 'wca_idempotency_finalize_failed', __( 'The mutation may have completed, but replay evidence could not be finalized. Query mutation status before retrying.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'reconciliation_required' => true, 'idempotency_key' => $claim['key'] ) );
\t\t\t}
\t\t} elseif ( $status >= 500 || ! $status ) {
\t\t\tWCA_Observability::metric( 'http_idempotency_ambiguous_response_total', 1, array( 'route_scope' => substr( hash( 'sha256', $claim['route'] ), 0, 12 ) ) );
\t\t\t$response->header( 'X-WCA-Reconciliation-Required', '1' );
\t\t} elseif ( ! WCA_Repository::release_idempotency( $claim['id'] ) ) {
\t\t\tWCA_Observability::metric( 'http_idempotency_release_failed_total', 1 );
\t\t}
\t\t$response->header( 'X-WCA-Idempotency-Key', $claim['key'] );
\t\treturn $response;"""
once(p,old,new)

# R6-D: advisory-lock release failures are operationally visible even though the
# DB connection itself remains the ultimate lock-lifetime fence.
p='includes/class-wca-outbox.php'
once(p,
"\t\t} finally {\n\t\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );\n\t\t}",
"\t\t} finally {\n\t\t\t$released_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );\n\t\t\tif ( 1 !== (int) $released_raw ) { WCA_Observability::metric( 'outbox_lock_release_failed_total', 1 ); WCA_Observability::log( 'error', 'outbox_lock_release_failed', array( 'db_error' => '' !== (string) $wpdb->last_error ) ); }\n\t\t}"
)

# Permanent T16 regression assertions.
p='tests/sixteenth-twenty-review-regressions.php'
s=read(p); marker='if($fail){fwrite(STDERR,"T16 regression gate failed:'
if marker not in s: raise SystemExit('T16 assertion insertion marker missing')
checks="""t16h('R6 outbox claim write failure explicit','includes/class-wca-repository.php','wca_outbox_claim_write_failed');
t16h('R6 outbox successful claim requires readback','includes/class-wca-repository.php','wca_outbox_claim_readback_missing');
t16h('R6 ambiguous WP error retains reconciliation evidence','includes/class-wca-ten-review-hardening.php','http_idempotency_ambiguous_error_total');
t16h('R6 ambiguous response advertises reconciliation','includes/class-wca-ten-review-hardening.php','X-WCA-Reconciliation-Required');
t16h('R6 unverifiable response fails closed','includes/class-wca-ten-review-hardening.php','wca_idempotency_response_unverifiable');
t16h('R6 idempotency release DB failure observable','includes/class-wca-repository.php','idempotency_release_db_failed_total');
t16h('R6 outbox advisory release failure observable','includes/class-wca-outbox.php','outbox_lock_release_failed_total');
"""
write(p,s.replace(marker,checks+marker,1))
