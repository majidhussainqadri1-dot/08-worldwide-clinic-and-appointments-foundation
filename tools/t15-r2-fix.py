from pathlib import Path
import re
ROOT=Path('.')
def read(p): return (ROOT/p).read_text()
def write(p,s): (ROOT/p).write_text(s)
def once(p,a,b):
 s=read(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:100]!r}')
 write(p,s.replace(a,b,1))
def rx(p,pat,repl):
 s=read(p); out,n=re.subn(pat,repl,s,count=1,flags=re.S)
 if n!=1: raise SystemExit(f'{p}: regex expected 1 got {n}: {pat[:100]!r}')
 write(p,out)

# --- WCA_Privacy: erasure/retention reads must never imply false completion. ---
p='includes/class-wca-privacy.php'
once(p,"\t\t$ids = self::appointment_ids_after( $user_id, $cursor, self::ERASE_BATCH );\n\t\t$last_id = $cursor;","\t\t$ids = self::appointment_ids_after( $user_id, $cursor, self::ERASE_BATCH );\n\t\tif ( is_wp_error( $ids ) ) { $messages[] = __( 'Appointment privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; $ids = array(); }\n\t\t$last_id = $cursor;")
once(p,"\t\tif ( self::appointment_ids_after( $user_id, $last_id, 1 ) ) { $done = false; } else { delete_transient( $cursor_key ); }","\t\t$appointment_more = self::appointment_ids_after( $user_id, $last_id, 1 );\n\t\tif ( is_wp_error( $appointment_more ) ) { $messages[] = __( 'Appointment privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; } elseif ( $appointment_more ) { $done = false; } else { delete_transient( $cursor_key ); }")
once(p,"\t\t\t$rows = (array) $wpdb->get_results(\n\t\t\t\t$wpdb->prepare(\n\t\t\t\t\t\"SELECT * FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t\t$user_id, $user_id, $cursor, self::ERASE_BATCH\n\t\t\t\t),\n\t\t\t\tARRAY_A\n\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared","\t\t\t$rows_raw = $wpdb->get_results(\n\t\t\t\t$wpdb->prepare(\n\t\t\t\t\t\"SELECT * FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t\t$user_id, $user_id, $cursor, self::ERASE_BATCH\n\t\t\t\t),\n\t\t\t\tARRAY_A\n\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Future24 privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; $rows_raw = array(); }\n\t\t\t$rows = (array) $rows_raw;")
once(p,"\t\t\t$more = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT 1\", $user_id, $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }","\t\t\t$more = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d) AND id>%d ORDER BY id ASC LIMIT 1\", $user_id, $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $more && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Future24 privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; } elseif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }")
rx(p,r"\tprivate static function appointment_ids_after\( \$user_id, \$cursor, \$limit \) \{.*?\n\t\}\n\n\tpublic static function legal_hold",'''\tprivate static function appointment_ids_after( $user_id, $cursor, $limit ) {
\t\tglobal $wpdb;
\t\t$user_id = absint( $user_id );
\t\t$cursor = absint( $cursor );
\t\t$limit = min( 500, max( 1, absint( $limit ) ) );
\t\t$sql = $wpdb->prepare(
\t\t\t"SELECT DISTINCT p.ID
\t\t\t FROM {$wpdb->posts} p
\t\t\t INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID
\t\t\t WHERE p.post_type=%s
\t\t\t   AND p.post_status IN ('private','publish','draft')
\t\t\t   AND p.ID>%d
\t\t\t   AND ((pm.meta_key='_swc_patient_user_id' AND pm.meta_value=%d) OR (pm.meta_key='_swc_guardian_user_id' AND pm.meta_value=%d) OR (pm.meta_key='_swc_doctor_id' AND pm.meta_value=%d))
\t\t\t ORDER BY p.ID ASC LIMIT %d",
\t\t\tSWC_Helpers::TYPE, $cursor, $user_id, $user_id, $user_id, $limit
\t\t);
\t\t$raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_privacy_appointment_read_failed', __( 'Appointment privacy records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\treturn array_map( 'absint', (array) $raw );
\t}

\tpublic static function legal_hold''')
once(p,"\t\t\t\t$rows = (array) $wpdb->get_results(\n\t\t\t\t\t$wpdb->prepare( \"SELECT * FROM {$table} WHERE expires_at IS NOT NULL AND expires_at<%s AND updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d\", WCA_Repository::now(), $cutoff, $cursor, $batch ),\n\t\t\t\t\tARRAY_A\n\t\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared","\t\t\t\t$rows_raw = $wpdb->get_results(\n\t\t\t\t\t$wpdb->prepare( \"SELECT * FROM {$table} WHERE expires_at IS NOT NULL AND expires_at<%s AND updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d\", WCA_Repository::now(), $cutoff, $cursor, $batch ),\n\t\t\t\t\tARRAY_A\n\t\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_retention_future24_read_failed', __( 'Future24 retention cleanup could not read expired records safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\t\t\t$rows = (array) $rows_raw;")

# --- WCA_Continuity_Guards: optimistic version/consent/eraser reads. ---
p='includes/class-wca-continuity-guards.php'
once(p,"\t\t$current = $wpdb->get_row( $wpdb->prepare( \"SELECT id,version FROM {$table} WHERE appointment_id=%d LIMIT 1\", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( ! $current ) {","\t\t$current = $wpdb->get_row( $wpdb->prepare( \"SELECT id,version FROM {$table} WHERE appointment_id=%d LIMIT 1\", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $current && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_intake_version_read_failed', __( 'Current pre-visit version could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( ! $current ) {")
once(p,"\t\t$rows = (array) $wpdb->get_results(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT scope,status,terms_version,granted_at,revoked_at FROM {$table} WHERE appointment_id=%d ORDER BY id ASC\",\n\t\t\t\t$appointment_id\n\t\t\t),\n\t\t\tARRAY_A\n\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared","\t\t$rows_raw = $wpdb->get_results(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT scope,status,terms_version,granted_at,revoked_at FROM {$table} WHERE appointment_id=%d ORDER BY id ASC\",\n\t\t\t\t$appointment_id\n\t\t\t),\n\t\t\tARRAY_A\n\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_consent_state_read_failed', __( 'Current consent state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$rows = (array) $rows_raw;")
once(p,"\t\t\t$rows = (array) $wpdb->get_results(\n\t\t\t\t$wpdb->prepare(\n\t\t\t\t\t\"SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t\t$user_id,\n\t\t\t\t\t$cursor,\n\t\t\t\t\tself::ERASE_BATCH\n\t\t\t\t),\n\t\t\t\tARRAY_A\n\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared","\t\t\t$rows_raw = $wpdb->get_results(\n\t\t\t\t$wpdb->prepare(\n\t\t\t\t\t\"SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t\t$user_id, $cursor, self::ERASE_BATCH\n\t\t\t\t), ARRAY_A\n\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Continuity privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; $rows_raw = array(); }\n\t\t\t$rows = (array) $rows_raw;")
once(p,"\t\t\t\t\t$still_exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE id=%d AND {$field}=%d\", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( $still_exists )","\t\t\t\t\t$still_exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE id=%d AND {$field}=%d\", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( null === $still_exists && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Continuity privacy erasure could not verify a concurrent delete safely.', 'worldwide-clinic-appointments' ); $done = false; break; }\n\t\t\t\t\tif ( $still_exists )")
once(p,"\t\t\tif ( $more ) {\n\t\t\t\t$done = false;\n\t\t\t} else {","\t\t\tif ( null === $more && '' !== (string) $wpdb->last_error ) {\n\t\t\t\t$messages[] = __( 'Continuity privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' );\n\t\t\t\t$done = false;\n\t\t\t} elseif ( $more ) {\n\t\t\t\t$done = false;\n\t\t\t} else {")
once(p,"\t\t\t\t$guardian_remaining = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$intake_table} WHERE guardian_user_id=%d LIMIT 1\", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\tif ( $guardian_remaining )","\t\t\t\t$guardian_remaining = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$intake_table} WHERE guardian_user_id=%d LIMIT 1\", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\tif ( null === $guardian_remaining && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Guardian continuity references could not be verified safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; } elseif ( $guardian_remaining )")

# --- WCA_Continuity secure reads/maintenance. ---
p='includes/class-wca-continuity-secure.php'
once(p,"\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref FROM {$table} WHERE appointment_id=%d AND id>%d ORDER BY id ASC LIMIT %d\", $appointment_id, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) {","\t\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref FROM {$table} WHERE appointment_id=%d AND id>%d ORDER BY id ASC LIMIT %d\", $appointment_id, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_list_read_failed', __( 'Follow-up plans could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$rows = (array) $rows_raw;\n\t\t\tforeach ( $rows as $row ) {")
# get_followup + complete_followup both have same missing check statement but different methods.
s=read(p)
needle="\t\tif ( ! $row ) { return new WP_Error( 'wca_followup_missing', __( 'Follow-up plan was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }"
if s.count(needle)!=2: raise SystemExit(f'{p}: expected 2 followup missing checks got {s.count(needle)}')
s=s.replace(needle,"\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_read_failed', __( 'Follow-up plan state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n"+needle)
write(p,s)
once(p,"\tpublic static function maintenance() {\n\t\tself::process_due_followups();\n\t\t$retention = self::apply_retention();","\tpublic static function maintenance() {\n\t\t$reminders = self::process_due_followups();\n\t\tif ( is_wp_error( $reminders ) ) { WCA_Observability::log( 'error', 'continuity_reminder_scan_failed', array( 'error_code' => $reminders->get_error_code() ) ); return $reminders; }\n\t\t$retention = self::apply_retention();")
once(p,"\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT id FROM {$table} WHERE status='scheduled' AND reminder_sent_at IS NULL AND due_at<=%s AND due_at>=%s AND id>%d ORDER BY id ASC LIMIT %d\", $cutoff, $now, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $candidate ) {","\t\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT id FROM {$table} WHERE status='scheduled' AND reminder_sent_at IS NULL AND due_at<=%s AND due_at>=%s AND id>%d ORDER BY id ASC LIMIT %d\", $cutoff, $now, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_reminder_scan_read_failed', __( 'Due follow-up reminders could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$rows = (array) $rows_raw;\n\t\t\tforeach ( $rows as $candidate ) {")
once(p,"\t\t\t\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d AND status='scheduled' AND reminder_sent_at IS NULL FOR UPDATE\", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( ! $row ) { return false; }","\t\t\t\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d AND status='scheduled' AND reminder_sent_at IS NULL FOR UPDATE\", $id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_reminder_lock_read_failed', __( 'Follow-up reminder state could not be locked safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t\t\tif ( ! $row ) { return false; }")
once(p,"\t\t\t$intakes = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref,appointment_id FROM {$intake_table} WHERE updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d\", $intake_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $intakes as $row ) {","\t\t\t$intakes_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref,appointment_id FROM {$intake_table} WHERE updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d\", $intake_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $intakes_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_intake_retention_read_failed', __( 'Expired pre-visit records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\t\t$intakes = (array) $intakes_raw;\n\t\t\tforeach ( $intakes as $row ) {")
once(p,"\t\t\t$followups = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref,appointment_id,status FROM {$follow_table} WHERE updated_at<%s AND status IN ('completed','cancelled') AND id>%d ORDER BY id ASC LIMIT %d\", $follow_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $followups as $row ) {","\t\t\t$followups_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref,appointment_id,status FROM {$follow_table} WHERE updated_at<%s AND status IN ('completed','cancelled') AND id>%d ORDER BY id ASC LIMIT %d\", $follow_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $followups_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_followup_retention_read_failed', __( 'Expired follow-up records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\t\t$followups = (array) $followups_raw;\n\t\t\tforeach ( $followups as $row ) {")

# --- Legacy SWC privacy exporter/eraser must surface read errors, not empty/complete. ---
p='includes/class-swc-privacy.php'
once(p,"\t\t$ids   = $this->related_ids( $user->ID, $page );\n\t\t$total = $this->related_count( $user->ID );\n\t\t$data  = array();","\t\t$ids   = $this->related_ids( $user->ID, $page );\n\t\tif ( is_wp_error( $ids ) ) { return $ids; }\n\t\t$total = $this->related_count( $user->ID );\n\t\tif ( is_wp_error( $total ) ) { return $total; }\n\t\t$data  = array();")
once(p,"\t\t$ids      = $this->related_ids( $user->ID, 1 );\n\t\t$removed  = false;","\t\t$ids      = $this->related_ids( $user->ID, 1 );\n\t\tif ( is_wp_error( $ids ) ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array( __( 'Appointment privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ) ), 'done' => false ); }\n\t\t$removed  = false;")
once(p,"\t\t$done = ! $failed && 0 === $this->related_count( $user->ID );","\t\t$remaining = $this->related_count( $user->ID );\n\t\tif ( is_wp_error( $remaining ) ) { $messages[] = __( 'Appointment privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $failed = true; $remaining = 1; }\n\t\t$done = ! $failed && 0 === $remaining;")
rx(p,r"\tprivate function related_ids\( \$user_id, \$page \) \{.*?\n\t\}\n\n\tprivate function related_count",'''\tprivate function related_ids( $user_id, $page ) {
\t\tglobal $wpdb;
\t\t$offset = ( max( 1, absint( $page ) ) - 1 ) * self::PAGE_SIZE;
\t\t$sql = "SELECT DISTINCT p.ID
\t\t\tFROM {$wpdb->posts} p
\t\t\tLEFT JOIN {$wpdb->postmeta} patient ON patient.post_id=p.ID AND patient.meta_key='_swc_patient_user_id'
\t\t\tLEFT JOIN {$wpdb->postmeta} doctor ON doctor.post_id=p.ID AND doctor.meta_key='_swc_doctor_id'
\t\t\tLEFT JOIN {$wpdb->postmeta} proposed ON proposed.post_id=p.ID AND proposed.meta_key='_swc_proposed_doctor_id'
\t\t\tWHERE p.post_type=%s AND p.post_status IN ('publish','private')
\t\t\tAND (p.post_author=%d OR patient.meta_value=%s OR doctor.meta_value=%s OR proposed.meta_value=%s)
\t\t\tORDER BY p.ID ASC LIMIT %d OFFSET %d";
\t\t$raw = $wpdb->get_col( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), self::PAGE_SIZE, $offset ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'swc_privacy_related_ids_read_failed', __( 'Appointment privacy records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\treturn array_map( 'absint', (array) $raw );
\t}

\tprivate function related_count''')
rx(p,r"\tprivate function related_count\( \$user_id \) \{.*?\n\t\}\n\n\tpublic function policy",'''\tprivate function related_count( $user_id ) {
\t\tglobal $wpdb;
\t\t$sql = "SELECT COUNT(DISTINCT p.ID)
\t\t\tFROM {$wpdb->posts} p
\t\t\tLEFT JOIN {$wpdb->postmeta} patient ON patient.post_id=p.ID AND patient.meta_key='_swc_patient_user_id'
\t\t\tLEFT JOIN {$wpdb->postmeta} doctor ON doctor.post_id=p.ID AND doctor.meta_key='_swc_doctor_id'
\t\t\tLEFT JOIN {$wpdb->postmeta} proposed ON proposed.post_id=p.ID AND proposed.meta_key='_swc_proposed_doctor_id'
\t\t\tWHERE p.post_type=%s AND p.post_status IN ('publish','private')
\t\t\tAND (p.post_author=%d OR patient.meta_value=%s OR doctor.meta_value=%s OR proposed.meta_value=%s)";
\t\t$raw = $wpdb->get_var( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'swc_privacy_related_count_read_failed', __( 'Appointment privacy record count could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\treturn absint( $raw );
\t}

\tpublic function policy''')

# Permanent R2 assertions appended to T15 gate.
p='tests/fifteenth-twenty-review-regressions.php'; s=read(p)
insert="""
t15h('R2 core erasure record read failure','includes/class-wca-privacy.php','wca_privacy_appointment_read_failed');
t15h('R2 future retention read failure','includes/class-wca-privacy.php','wca_retention_future24_read_failed');
t15h('R2 intake version read failure','includes/class-wca-continuity-guards.php','wca_intake_version_read_failed');
t15h('R2 consent state read failure','includes/class-wca-continuity-guards.php','wca_consent_state_read_failed');
t15h('R2 followup list read failure','includes/class-wca-continuity-secure.php','wca_followup_list_read_failed');
t15h('R2 reminder scan read failure','includes/class-wca-continuity-secure.php','wca_followup_reminder_scan_read_failed');
t15h('R2 intake retention read failure','includes/class-wca-continuity-secure.php','wca_intake_retention_read_failed');
t15h('R2 followup retention read failure','includes/class-wca-continuity-secure.php','wca_followup_retention_read_failed');
t15h('R2 legacy privacy ids read failure','includes/class-swc-privacy.php','swc_privacy_related_ids_read_failed');
t15h('R2 legacy privacy count read failure','includes/class-swc-privacy.php','swc_privacy_related_count_read_failed');
"""
marker="if($fail){fwrite(STDERR,\"T15 regression gate failed:"
if marker not in s: raise SystemExit('T15 gate marker missing')
s=s.replace(marker,insert+marker,1); write(p,s)

# Evidence append after the completed review, before correction commit.
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=read(p)
s += """

## R2 — complete privacy / export / erasure / retention review

R2 was completed against the R1-corrected state before any R2 source change. It found SQL-read failure paths that could be interpreted as an empty privacy set, a missing continuity row, or successful completion: canonical and legacy erasure cursors, Future24 retention, continuity optimistic-version/consent reads, follow-up list/reminder scans, and continuity retention. The post-review batch makes read failure explicit/retryable and prevents false completion.

R2 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R3.**
"""
write(p,s)
