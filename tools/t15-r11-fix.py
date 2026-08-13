from pathlib import Path
import re
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
    s=rd(p); n=s.count(a)
    if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
    wr(p,s.replace(a,b,1))
def rx(p,pat,repl):
    s=rd(p); out,n=re.subn(pat,repl,s,count=1,flags=re.S)
    if n!=1: raise SystemExit(f'{p}: regex expected 1 got {n}: {pat[:140]!r}')
    wr(p,out)

# R11-A: nested owner transactions must join the outer transaction rather than issuing START TRANSACTION again.
p='includes/class-wca-repository.php'
once(p,"final class WCA_Repository {\n\tpublic static function uuid() {","final class WCA_Repository {\n\tprivate static $transaction_depth = 0;\n\n\tpublic static function uuid() {")
rx(p,r"\t/\*\* Execute one owner mutation and its required evidence/outbox writes atomically\. \*/\n\tpublic static function transaction\( \$callback, \$error_code = 'wca_transaction_failed' \) \{.*?\n\t\}\n\n\t/\*\* @return array<string,mixed>\|WP_Error \*/",'''\t/** Execute one owner mutation and its required evidence/outbox writes atomically. Nested calls join the outer owner transaction. */
\tpublic static function transaction( $callback, $error_code = 'wca_transaction_failed' ) {
\t\tglobal $wpdb;
\t\tif ( self::$transaction_depth > 0 ) {
\t\t\tself::$transaction_depth++;
\t\t\ttry {
\t\t\t\treturn call_user_func( $callback );
\t\t\t} catch ( Throwable $error ) {
\t\t\t\treturn new WP_Error( sanitize_key( $error_code ), __( 'The nested mutation could not be completed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t\t} finally {
\t\t\t\tself::$transaction_depth--;
\t\t\t}
\t\t}
\t\t$started = $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\tif ( false === $started ) {
\t\t\treturn new WP_Error( sanitize_key( $error_code . '_start' ), __( 'The mutation transaction could not be started safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t}
\t\tself::$transaction_depth = 1;
\t\ttry {
\t\t\t$result = call_user_func( $callback );
\t\t\tif ( is_wp_error( $result ) ) {
\t\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\t\t\treturn $result;
\t\t\t}
\t\t\t$committed = $wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\t\tif ( false === $committed ) {
\t\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\t\t\treturn new WP_Error( sanitize_key( $error_code . '_commit' ), __( 'The mutation transaction could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t\t}
\t\t\treturn $result;
\t\t} catch ( Throwable $error ) {
\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\t\treturn new WP_Error( sanitize_key( $error_code ), __( 'The mutation could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t} finally {
\t\t\tself::$transaction_depth = 0;
\t\t}
\t}

\t/** @return array<string,mixed>|WP_Error */''')

p='includes/class-wca-future24.php'
# R11-B: public_record/get_record must preserve read failures.
once(p,"\tprivate static function public_record( $row ) {\n\t\t$payload = json_decode( isset( $row['payload_json'] ) ? (string) $row['payload_json'] : '{}', true );","\tprivate static function public_record( $row ) {\n\t\tif ( is_wp_error( $row ) ) { return $row; }\n\t\tif ( ! is_array( $row ) ) { return new WP_Error( 'wca_future24_record_unavailable', __( 'The Future24 record could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$payload = json_decode( isset( $row['payload_json'] ) ? (string) $row['payload_json'] : '{}', true );")
rx(p,r"\tprivate static function get_record\( \$ref, \$feature_id = '' \) \{.*?\n\t\}\n\n\tprivate static function appointment_id",'''\tprivate static function get_record( $ref, $feature_id = '' ) {
\t\tglobal $wpdb;
\t\t$table = self::tables()['records'];
\t\t$ref = strtolower( sanitize_text_field( $ref ) );
\t\tif ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) { return null; }
\t\tif ( $feature_id ) {
\t\t\t$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s AND feature_id=%s LIMIT 1", $ref, $feature_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t} else {
\t\t\t$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", $ref ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t}
\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_future24_record_read_failed', __( 'The Future24 record could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn $row;
\t}

\tprivate static function appointment_id''')
# Propagate strict get_record errors before array access.
for old,new in [
("\t\t$resource = self::get_record( $resource_ref, 'F08-FUT-04' );\n\t\tif ( ! $resource || 'resource_active' !== $resource['status'] )", "\t\t$resource = self::get_record( $resource_ref, 'F08-FUT-04' );\n\t\tif ( is_wp_error( $resource ) ) { return $resource; }\n\t\tif ( ! $resource || 'resource_active' !== $resource['status'] )"),
("\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\tif ( ! $session || 'group_open' !== $session['status']", "\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\tif ( is_wp_error( $session ) ) { return $session; }\n\t\tif ( ! $session || 'group_open' !== $session['status']"),
("\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\tif ( ! $session || ! in_array", "\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\tif ( is_wp_error( $session ) ) { return $session; }\n\t\tif ( ! $session || ! in_array"),
("\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\tif ( ! $session ) { return new WP_Error( 'wca_group_missing'", "\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\tif ( is_wp_error( $session ) ) { return $session; }\n\t\tif ( ! $session ) { return new WP_Error( 'wca_group_missing'"),
("\t\t\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\t\t\tif ( ! $session || 'group_open'", "\t\t\t\t$session = self::get_record( $session_ref, 'F08-FUT-05' );\n\t\t\t\tif ( is_wp_error( $session ) ) { return $session; }\n\t\t\t\tif ( ! $session || 'group_open'"),
("\t\t\t\t$row = self::get_record( $participant_ref, 'F08-FUT-18' );\n\t\t\t\tif ( ! $row || absint", "\t\t\t\t$row = self::get_record( $participant_ref, 'F08-FUT-18' );\n\t\t\t\tif ( is_wp_error( $row ) ) { return $row; }\n\t\t\t\tif ( ! $row || absint"),
]: once(p,old,new)

# R11-C: cancellation waitlist scan/delivery must be retryable, not silently acknowledged.
once(p,"\tpublic static function observe_outbox_event( $envelope ) {\n\t\tif ( ! is_array( $envelope ) || 'AppointmentCancelled.v1' !== (string) ( isset( $envelope['topic'] ) ? $envelope['topic'] : '' ) ) { return; }\n\t\tself::offer_waitlist_for_cancelled_appointment( sanitize_text_field( isset( $envelope['aggregate_ref'] ) ? $envelope['aggregate_ref'] : '' ) );\n\t}","\tpublic static function observe_outbox_event( $envelope ) {\n\t\tif ( ! is_array( $envelope ) || 'AppointmentCancelled.v1' !== (string) ( isset( $envelope['topic'] ) ? $envelope['topic'] : '' ) ) { return; }\n\t\t$outcome = self::offer_waitlist_for_cancelled_appointment( sanitize_text_field( isset( $envelope['aggregate_ref'] ) ? $envelope['aggregate_ref'] : '' ) );\n\t\tif ( is_wp_error( $outcome ) ) { throw new RuntimeException( 'wca_waitlist_offer_delivery_failed: ' . $outcome->get_error_message() ); }\n\t}")
once(p,"\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare(\n\t\t\t\t\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\tabsint( $clinic_id ), WCA_Repository::now(), $cursor, $batch\n\t\t\t), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) {","\t\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare(\n\t\t\t\t\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\tabsint( $clinic_id ), WCA_Repository::now(), $cursor, $batch\n\t\t\t), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { throw new RuntimeException( 'wca_waitlist_candidate_read_failed' ); }\n\t\t\t$rows = (array) $rows_raw;\n\t\t\tforeach ( $rows as $row ) {")
once(p,"\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\n\t\t$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';\n\t\t$table = self::tables()['records'];","\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\n\t\tif ( $service_id && ! $service && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_waitlist_service_read_failed', __( 'The cancelled appointment service could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( $service_id && ! $service ) { return 0; }\n\t\t$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';\n\t\t$table = self::tables()['records'];")
once(p,"\t\t\tif ( $wanted_service && $service_ref && ! hash_equals( $wanted_service, $service_ref ) ) { continue; }","\t\t\tif ( $wanted_service && ( ! $service_ref || ! hash_equals( $wanted_service, $service_ref ) ) ) { continue; }")
once(p,"\t\t\t\tif ( true === $outcome ) { $offered++; }","\t\t\t\tif ( is_wp_error( $outcome ) ) { return $outcome; }\n\t\t\t\tif ( true === $outcome ) { $offered++; }")

# R11-D: complete policy/template traversal must surface DB failure.
once(p,"\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare(\n\t\t\t\t\"SELECT {$columns} FROM {$table} WHERE feature_id=%s AND clinic_id=%d AND status=%s AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t$feature_id, absint( $clinic_id ), $status, $cursor, $batch\n\t\t\t), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) { $cursor = max( $cursor, absint( $row['id'] ?? 0 ) ); $out[] = $row; }","\t\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare(\n\t\t\t\t\"SELECT {$columns} FROM {$table} WHERE feature_id=%s AND clinic_id=%d AND status=%s AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t$feature_id, absint( $clinic_id ), $status, $cursor, $batch\n\t\t\t), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_future24_policy_read_failed', __( 'Current Future24 policy data could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$rows = (array) $rows_raw;\n\t\t\tforeach ( $rows as $row ) { $cursor = max( $cursor, absint( $row['id'] ?? 0 ) ); $out[] = $row; }")
once(p,"\t\t$rows = array_reverse( self::feature_rows_for_clinic( 'F08-FUT-11', $clinic_id, 'template_active', 'questionnaire' ) );","\t\t$rows = self::feature_rows_for_clinic( 'F08-FUT-11', $clinic_id, 'template_active', 'questionnaire' );\n\t\tif ( is_wp_error( $rows ) ) { return $rows; }\n\t\t$rows = array_reverse( $rows );")
once(p,"\t\t$rules = array_reverse( self::feature_rows_for_clinic( 'F08-FUT-13', $clinic_id, 'rule_active', 'payload' ) );","\t\t$rules = self::feature_rows_for_clinic( 'F08-FUT-13', $clinic_id, 'rule_active', 'payload' );\n\t\tif ( is_wp_error( $rules ) ) { return $rules; }\n\t\t$rules = array_reverse( $rules );")
# Service-scoped questionnaire must not match a missing/unknown service.
once(p,"\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\n\t\t$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';\n\t\t$table = self::tables()['records'];\n\t\t$rows = self::feature_rows_for_clinic( 'F08-FUT-11'","\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\n\t\tif ( $service_id && ! $service && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_questionnaire_service_read_failed', __( 'The appointment service could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';\n\t\t$table = self::tables()['records'];\n\t\t$rows = self::feature_rows_for_clinic( 'F08-FUT-11'")
once(p,"\t\t\tif ( $template_service && $service_ref && ! hash_equals( $template_service, $service_ref ) ) { continue; }","\t\t\tif ( $template_service && ( ! $service_ref || ! hash_equals( $template_service, $service_ref ) ) ) { continue; }")
# Prerequisite service read and readiness reads must be authoritative.
once(p,"\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\n\t\t$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';\n\t\t$rules = self::feature_rows_for_clinic( 'F08-FUT-13'","\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\n\t\tif ( $service_id && ! $service && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_prerequisite_service_read_failed', __( 'The appointment service could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$service_ref = $service && ! empty( $service['public_ref'] ) ? strtolower( (string) $service['public_ref'] ) : '';\n\t\t$rules = self::feature_rows_for_clinic( 'F08-FUT-13'")
once(p,"\t\t$intake = WCA_Continuity::tables()['intake'];\n\t\t$intake_status = (string) $wpdb->get_var( $wpdb->prepare( \"SELECT status FROM {$intake} WHERE appointment_id=%d LIMIT 1\", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t$prereq = self::prerequisite_state( $id );","\t\t$intake = WCA_Continuity::tables()['intake'];\n\t\t$intake_status_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT status FROM {$intake} WHERE appointment_id=%d LIMIT 1\", $id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $intake_status_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_readiness_intake_read_failed', __( 'Current pre-visit readiness state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$intake_status = (string) $intake_status_raw;\n\t\t$prereq = self::prerequisite_state( $id );\n\t\tif ( is_wp_error( $prereq ) ) { return $prereq; }")

# R11-E: a deliberately service-less group session is valid only if join treats service as optional too.
once(p,"\t\tif ( ! $clinic || is_wp_error( $service ) || ! $service ) { return new WP_Error( 'wca_group_scope_stale'","\t\tif ( ! $clinic || is_wp_error( $service ) || ( $service_ref && ! $service ) ) { return new WP_Error( 'wca_group_scope_stale'")

# R11-F: one appointment may have only one active arrival signal, whether patient or guardian sends it; legacy duplicates cannot inflate queue position.
once(p,"\t\t$lock = self::semantic_lock( 'arrival', $id . '|' . $actor_id );","\t\t$lock = self::semantic_lock( 'arrival', $id );")
once(p,"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND subject_user_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, $actor_id, WCA_Repository::now() ), ARRAY_A );","\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, WCA_Repository::now() ), ARRAY_A );")
once(p,"\t\t$ahead_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-15' AND clinic_id=%d AND status='arrived' AND created_at<%s AND (expires_at IS NULL OR expires_at>%s)\", $clinic_id, $current['created_at'], $now ) );","\t\t$ahead_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(DISTINCT appointment_id) FROM {$table} WHERE feature_id='F08-FUT-15' AND clinic_id=%d AND status='arrived' AND created_at<%s AND (expires_at IS NULL OR expires_at>%s)\", $clinic_id, $current['created_at'], $now ) );")

# R11-G: canonical subject resolver helper is authoritative and error-safe.
rx(p,r"\tprivate static function subject_user_id\( \$subject \) \{.*?\n\t\}\n\n\t/\* FUT-20 \*/",'''\tprivate static function subject_user_id( $subject ) {
\t\t$subject = strtolower( sanitize_text_field( $subject ) );
\t\tif ( function_exists( 'smc_get_user_id_by_subject_uuid' ) ) {
\t\t\ttry { $raw = smc_get_user_id_by_subject_uuid( $subject ); } catch ( Throwable $error ) { return 0; }
\t\t\tif ( is_wp_error( $raw ) || ! is_scalar( $raw ) ) { return 0; }
\t\t\treturn absint( $raw );
\t\t}
\t\t$users = get_users( array( 'fields' => 'ids', 'number' => 2, 'meta_key' => '_smc_subject_uuid', 'meta_value' => $subject ) );
\t\treturn 1 === count( $users ) ? absint( $users[0] ) : 0;
\t}

\t/* FUT-20 */''')

# R11-H: completed safe-reschedule must not advertise success when required Future24 governance evidence failed.
once(p,"\t\tself::audit( 'F08-FUT-06', 'safe_reschedule_completed', $appointment_ref, array( 'compensation_safe' => true ), $actor, false );\n\t\treturn $confirm;","\t\t$audit = self::audit( 'F08-FUT-06', 'safe_reschedule_completed', $appointment_ref, array( 'compensation_safe' => true ), $actor, false );\n\t\tif ( is_wp_error( $audit ) ) { return new WP_Error( 'wca_safe_reschedule_audit_failed', __( 'The appointment was rescheduled, but Future24 governance evidence could not be finalized. Reconciliation is required.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'reconciliation_required' => true, 'appointment_changed' => true ) ); }\n\t\treturn $confirm;")

# Permanent R11 regression evidence.
p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R11 nested transactions join outer transaction','includes/class-wca-repository.php','private static $transaction_depth = 0');
t15h('R11 nested transaction guard','includes/class-wca-repository.php','if ( self::$transaction_depth > 0 )');
t15h('R11 waitlist candidate read failure','includes/class-wca-future24.php','wca_waitlist_candidate_read_failed');
t15h('R11 waitlist delivery failure propagates','includes/class-wca-future24.php','wca_waitlist_offer_delivery_failed');
t15h('R11 Future24 record read failure explicit','includes/class-wca-future24.php','wca_future24_record_read_failed');
t15h('R11 policy traversal read failure explicit','includes/class-wca-future24.php','wca_future24_policy_read_failed');
t15h('R11 readiness intake read failure explicit','includes/class-wca-future24.php','wca_readiness_intake_read_failed');
t15h('R11 service-specific questionnaire fails closed','includes/class-wca-future24.php',"$template_service && ( ! $service_ref || ! hash_equals");
t15h('R11 optional group service remains joinable','includes/class-wca-future24.php','( $service_ref && ! $service )');
t15h('R11 arrival serialized by appointment','includes/class-wca-future24.php',"semantic_lock( 'arrival', $id )");
t15h('R11 queue counts distinct appointments','includes/class-wca-future24.php','COUNT(DISTINCT appointment_id)');
t15h('R11 subject resolver helper is authoritative','includes/class-wca-future24.php',"if ( is_wp_error( $raw ) || ! is_scalar( $raw ) )");
t15h('R11 completed reschedule audit failure explicit','includes/class-wca-future24.php','wca_safe_reschedule_audit_failed');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
wr(p,s.replace(mark,ins+mark,1))

p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R11 — complete Future24 F08-FUT-01…24 functional / safety review

R11 was completed against the R10-corrected state before any R11 source change. Supported findings covered: nested Future24 owner transactions that could issue a second START TRANSACTION inside an outer atomic mutation; cancellation-waitlist traversal/delivery failures that could be acknowledged silently; SQL failure collapsed into Future24 record-not-found; policy/template traversal false-empty behavior; readiness intake false-state behavior; service-scoped waitlist/questionnaire mismatch when service truth was unavailable; optional group-session creation versus mandatory-service join inconsistency; cross-actor duplicate arrival rows and queue inflation; non-strict external subject resolution; and successful safe-reschedule returning success despite missing Future24 governance evidence. The correction batch joins nested transactions to the outer transaction, makes read/retry state authoritative, repairs scope semantics, deduplicates arrival per appointment and makes audit-finalization ambiguity explicit.

R11 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R12.**
"""; wr(p,s)
