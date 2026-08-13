from pathlib import Path
import re

ROOT = Path('.')

def read(path):
    return (ROOT / path).read_text()

def write(path, text):
    (ROOT / path).write_text(text)

def once(path, old, new):
    s = read(path)
    n = s.count(old)
    if n != 1:
        raise SystemExit(f'{path}: expected one exact match, got {n}: {old[:120]!r}')
    write(path, s.replace(old, new, 1))

def regex_once(path, pattern, repl):
    s = read(path)
    out, n = re.subn(pattern, repl, s, count=1, flags=re.S)
    if n != 1:
        raise SystemExit(f'{path}: expected one regex match, got {n}: {pattern[:120]!r}')
    write(path, out)

# R1-01: existing appointment conflict query must fail closed.
p = 'includes/class-swc-helpers.php'
once(p,
"\t\t$rows = $wpdb->get_results( $wpdb->prepare( $sql, absint( $doctor_id ), $from, $to, self::TYPE, absint( $exclude_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tforeach ( (array) $rows as $row ) {",
"\t\t$rows = $wpdb->get_results( $wpdb->prepare( $sql, absint( $doctor_id ), $from, $to, self::TYPE, absint( $exclude_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $rows || '' !== (string) $wpdb->last_error ) { return true; }\n\t\tforeach ( (array) $rows as $row ) {"
)

# R1-02: atomic rate counter readback must fail closed.
once(p,
"\t\tif ( false === $result ) { return true; }\n\t\t$hits = 1 === (int) $result ? 1 : (int) $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\treturn $hits > max( 1, $limit );",
"\t\tif ( false === $result ) { return true; }\n\t\tif ( 1 === (int) $result ) {\n\t\t\t$hits = 1;\n\t\t} else {\n\t\t\t$hits_raw = $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $hits_raw || '' !== (string) $wpdb->last_error ) { return true; }\n\t\t\t$hits = (int) $hits_raw;\n\t\t}\n\t\treturn $hits > max( 1, $limit );"
)

# R1-03: active hold lookup must fail closed.
p = 'includes/class-wca-service.php'
regex_once(p,
    r"\tprivate static function has_active_hold\( \$doctor_id, \$start_utc, \$end_utc, \$ignore_idempotency_key = '' \) \{.*?\n\t\}\n\n\t/\*\* Reproject",
    """\tprivate static function has_active_hold( $doctor_id, $start_utc, $end_utc, $ignore_idempotency_key = '' ) {
\t\tglobal $wpdb;
\t\t$table = WCA_Schema::tables()['slot_holds'];
\t\tif ( $ignore_idempotency_key ) {
\t\t\t$hold_id = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND idempotency_key<>%s AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\", absint( $doctor_id ), sanitize_text_field( $ignore_idempotency_key ), WCA_Repository::now(), $end_utc, $start_utc ) );
\t\t} else {
\t\t\t$hold_id = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\", absint( $doctor_id ), WCA_Repository::now(), $end_utc, $start_utc ) );
\t\t}
\t\tif ( '' !== (string) $wpdb->last_error ) { return true; }
\t\treturn (bool) $hold_id;
\t}

\t/** Reproject"""
)

# R1-04: slot-hold replay/conflict reads and expiry maintenance must not fail open.
p = 'includes/class-wca-repository.php'
once(p,
"\t\t$table = WCA_Schema::tables()['slot_holds'];\n\t\tself::expire_slot_holds();",
"\t\t$table = WCA_Schema::tables()['slot_holds'];\n\t\t$expired = self::expire_slot_holds();\n\t\tif ( false === $expired ) { return new WP_Error( 'wca_slot_hold_expiry_failed', __( 'Expired slot holds could not be reconciled safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }"
)
# All three idempotency-key reads in hold_slot use the same source statement.
s = read(p)
needle = "$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1\", $idempotency_key ), ARRAY_A );\n"
if s.count(needle) != 3:
    raise SystemExit(f'{p}: expected 3 slot-hold existing reads, got {s.count(needle)}')
s = s.replace(needle, needle + "\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n", 1)
s = s.replace(needle, needle + "\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n", 1)
s = s.replace(needle, needle + "\t\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n", 1)
write(p, s)
once(p,
"\t\t\t$conflict = $wpdb->get_var( $wpdb->prepare(\n\t\t\t\t\"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\",\n\t\t\t\t$doctor_id, self::now(), $end, $start\n\t\t\t) );\n\t\t\t$duration = max( 1, (int) round( ( strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) ) / 60 ) );",
"\t\t\t$conflict = $wpdb->get_var( $wpdb->prepare(\n\t\t\t\t\"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\",\n\t\t\t\t$doctor_id, self::now(), $end, $start\n\t\t\t) );\n\t\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_conflict_query_failed', __( 'Current slot conflicts could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$duration = max( 1, (int) round( ( strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) ) / 60 ) );"
)

# R1-05: command stale-idempotency and consent-existence reads must not be treated as no row.
p = 'includes/class-wca-appointment-command.php'
once(p,
"\t\t$row = $wpdb->get_row(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT id,status,updated_at FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1\",\n\t\t\t\t'request_appointment',\n\t\t\t\thash( 'sha256', $key ),\n\t\t\t\tabsint( $actor_user_id )\n\t\t\t),\n\t\t\tARRAY_A\n\t\t);\n\t\tif ( $row && 'processing' === (string) $row['status']",
"\t\t$row = $wpdb->get_row(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT id,status,updated_at FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1\",\n\t\t\t\t'request_appointment',\n\t\t\t\thash( 'sha256', $key ),\n\t\t\t\tabsint( $actor_user_id )\n\t\t\t),\n\t\t\tARRAY_A\n\t\t);\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_idempotency_read_failed', __( 'Current request replay state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( $row && 'processing' === (string) $row['status']"
)
once(p,
"\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( $exists ) { return true; }\n\t\t$claims = WCA_Authorization::claims( $actor_user_id );",
"\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $exists && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_consent_read_failed', __( 'Current consent state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( $exists ) { return true; }\n\t\t$claims = WCA_Authorization::claims( $actor_user_id );"
)

# R1-06..R1-14 Future24 mutation/concurrency reads.
p = 'includes/class-wca-future24.php'
once(p,
"\t\t\t\t\t$duplicate = $wpdb->get_var( $wpdb->prepare( \"SELECT public_ref FROM {$table} WHERE feature_id='F08-FUT-01' AND parent_ref=%s AND status='offer_pending' AND starts_at=%s AND ends_at=%s AND (expires_at IS NULL OR expires_at>%s) LIMIT 1\", (string) $wait['public_ref'], $start, $end, WCA_Repository::now() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( $duplicate ) { return false; }",
"\t\t\t\t\t$duplicate = $wpdb->get_var( $wpdb->prepare( \"SELECT public_ref FROM {$table} WHERE feature_id='F08-FUT-01' AND parent_ref=%s AND status='offer_pending' AND starts_at=%s AND ends_at=%s AND (expires_at IS NULL OR expires_at>%s) LIMIT 1\", (string) $wait['public_ref'], $start, $end, WCA_Repository::now() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( null === $duplicate && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_waitlist_offer_read_failed', __( 'Current waitlist offers could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t\t\tif ( $duplicate ) { return false; }"
)
once(p,
"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND subject_user_id=%d AND parent_ref=%s AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $clinic_id, $context['patient_user_id'], $fingerprint, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $existing ) { return self::public_record( $existing ); }",
"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND subject_user_id=%d AND parent_ref=%s AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $clinic_id, $context['patient_user_id'], $fingerprint, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_waitlist_dedupe_read_failed', __( 'Current waitlist state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( $existing ) { return self::public_record( $existing ); }"
)
once(p,
"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-02' AND clinic_id=%d AND subject_user_id=%d AND parent_ref=%s AND status='open' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $clinic_id, $context['patient_user_id'], $fingerprint, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $existing ) { return self::public_record( $existing ); }",
"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-02' AND clinic_id=%d AND subject_user_id=%d AND parent_ref=%s AND status='open' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $clinic_id, $context['patient_user_id'], $fingerprint, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_windows_dedupe_read_failed', __( 'Current flexible-window state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( $existing ) { return self::public_record( $existing ); }"
)
once(p,
"\t\t\t$count = (int) $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-04' AND parent_ref=%s AND status='reserved' AND (expires_at IS NULL OR expires_at>%s) AND starts_at<%s AND ends_at>%s\", strtolower( $resource_ref ), WCA_Repository::now(), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $count >= max( 1, absint( $resource['capacity'] ) ) )",
"\t\t\t$count_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-04' AND parent_ref=%s AND status='reserved' AND (expires_at IS NULL OR expires_at>%s) AND starts_at<%s AND ends_at>%s\", strtolower( $resource_ref ), WCA_Repository::now(), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $count_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_resource_capacity_read_failed', __( 'Current resource capacity could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$count = (int) $count_raw;\n\t\t\tif ( $count >= max( 1, absint( $resource['capacity'] ) ) )"
)
once(p,
"\t\t\t$exists = $wpdb->get_var( $wpdb->prepare( \"SELECT public_ref FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status='group_member' LIMIT 1\", strtolower( $session_ref ), $context['patient_user_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $exists ) { return self::public_record( self::get_record( $exists, 'F08-FUT-05' ) ); }\n\t\t\t$count = (int) $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND status='group_member'\", strtolower( $session_ref ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $count >= absint( $session['capacity'] ) )",
"\t\t\t$existing_member = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status='group_member' LIMIT 1\", strtolower( $session_ref ), $context['patient_user_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $existing_member && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_group_membership_read_failed', __( 'Current group membership could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( $existing_member ) { return self::public_record( $existing_member ); }\n\t\t\t$count_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND status='group_member'\", strtolower( $session_ref ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $count_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_group_capacity_read_failed', __( 'Current group capacity could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\t$count = (int) $count_raw;\n\t\t\tif ( $count >= absint( $session['capacity'] ) )"
)
once(p,
"\t\t\t$member = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status IN ('group_member','group_left','group_cancelled') ORDER BY id DESC LIMIT 1\", strtolower( $session_ref ), $context['patient_user_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( ! $member ) { return array( 'session_ref' => strtolower( $session_ref ), 'left' => true, 'already_absent' => true ); }",
"\t\t\t$member = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-05' AND parent_ref=%s AND subject_user_id=%d AND status IN ('group_member','group_left','group_cancelled') ORDER BY id DESC LIMIT 1\", strtolower( $session_ref ), $context['patient_user_id'] ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $member && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_group_leave_read_failed', __( 'Current group membership could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( ! $member ) { return array( 'session_ref' => strtolower( $session_ref ), 'left' => true, 'already_absent' => true ); }"
)
once(p,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT payload_json FROM {$table} WHERE feature_id='F08-FUT-07' AND clinic_id=%d AND status='policy_active' ORDER BY id DESC LIMIT 1\", absint( $clinic['id'] ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( ! $row ) { return true; }",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT payload_json FROM {$table} WHERE feature_id='F08-FUT-07' AND clinic_id=%d AND status='policy_active' ORDER BY id DESC LIMIT 1\", absint( $clinic['id'] ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return false; }\n\t\tif ( ! $row ) { return true; }"
)
once(p,
"\t\t\t\t\t$appointment_branch = $branch_id ? strtolower( (string) $wpdb->get_var( $wpdb->prepare( \"SELECT public_ref FROM {$branches_table} WHERE id=%d LIMIT 1\", $branch_id ) ) ) : ''; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( $appointment_branch && $slot_branch && ! hash_equals( $appointment_branch, $slot_branch ) ) {",
"\t\t\t\t\t$appointment_branch = '';\n\t\t\t\t\tif ( $branch_id ) {\n\t\t\t\t\t\t$branch_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT public_ref FROM {$branches_table} WHERE id=%d LIMIT 1\", $branch_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\t\tif ( '' !== (string) $wpdb->last_error ) { return false; }\n\t\t\t\t\t\t$appointment_branch = strtolower( (string) $branch_raw );\n\t\t\t\t\t}\n\t\t\t\t\tif ( $appointment_branch && $slot_branch && ! hash_equals( $appointment_branch, $slot_branch ) ) {"
)
once(p,
"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND subject_user_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, $actor_id, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $existing ) { return self::public_record( $existing ); }",
"\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND subject_user_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, $actor_id, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_arrival_dedupe_read_failed', __( 'Current arrival state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( $existing ) { return self::public_record( $existing ); }"
)
once(p,
"\t\t$current = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( ! $current ) { return new WP_Error( 'wca_queue_not_arrived'",
"\t\t$current = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, $now ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $current && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_queue_read_failed', __( 'Current queue state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( ! $current ) { return new WP_Error( 'wca_queue_not_arrived'"
)
once(p,
"\t\t$ahead = (int) $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-15' AND clinic_id=%d AND status='arrived' AND created_at<%s AND (expires_at IS NULL OR expires_at>%s)\", $clinic_id, $current['created_at'], $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\treturn array( 'contract' => 'wca.private-queue-position'",
"\t\t$ahead_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE feature_id='F08-FUT-15' AND clinic_id=%d AND status='arrived' AND created_at<%s AND (expires_at IS NULL OR expires_at>%s)\", $clinic_id, $current['created_at'], $now ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $ahead_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_queue_position_read_failed', __( 'Current queue position could not be calculated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t$ahead = (int) $ahead_raw;\n\t\treturn array( 'contract' => 'wca.private-queue-position'"
)
once(p,
"\t\t\t$existing=$wpdb->get_row($wpdb->prepare(\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-18' AND appointment_id=%d AND subject_user_id=%d AND status='participant_active' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\",$id,$subject_user_id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif($existing){",
"\t\t\t$existing=$wpdb->get_row($wpdb->prepare(\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-18' AND appointment_id=%d AND subject_user_id=%d AND status='participant_active' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\",$id,$subject_user_id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif(null===$existing && ''!==(string)$wpdb->last_error){return new WP_Error('wca_support_participant_read_failed',__('Current support-participant state could not be verified safely.','worldwide-clinic-appointments'),array('status'=>503));}\n\t\t\tif($existing){"
)
once(p,
"\t\t\t\t$existing=$wpdb->get_row($wpdb->prepare(\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-19' AND appointment_id=%d AND status='room_requested' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\",$id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\tif($existing){return self::public_record($existing);}",
"\t\t\t\t$existing=$wpdb->get_row($wpdb->prepare(\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-19' AND appointment_id=%d AND status='room_requested' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\",$id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\tif(null===$existing && ''!==(string)$wpdb->last_error){return new WP_Error('wca_virtual_room_read_failed',__('Current virtual-room request state could not be verified safely.','worldwide-clinic-appointments'),array('status'=>503));}\n\t\t\t\tif($existing){return self::public_record($existing);}"
)
regex_once(p,
    r"\tprivate static function external_busy_conflict_ref\( \$practitioner_ref, \$start, \$end \) \{.*?\n\t\}\n\n\t/\* FUT-23 \*/",
    """\tprivate static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) {
\t\tglobal $wpdb; $start=self::utc($start); $end=self::utc($end); $practitioner_ref=sanitize_text_field($practitioner_ref); if(!$practitioner_ref||!$start||!$end){return false;} $table=self::tables()['records'];
\t\t$busy = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND parent_ref=%s AND status='busy' AND (expires_at IS NULL OR expires_at>%s) AND starts_at<%s AND ends_at>%s LIMIT 1\", $practitioner_ref, WCA_Repository::now(), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( '' !== (string) $wpdb->last_error ) { return true; }
\t\treturn (bool) $busy;
\t}

\t/* FUT-23 */"""
)

# Initial permanent T15 regression gate.
test = r'''<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t15h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
t15h('R1 appointment conflicts fail closed','includes/class-swc-helpers.php',"null === $rows || '' !== (string) $wpdb->last_error");
t15h('R1 rate counter readback fails closed','includes/class-swc-helpers.php',"null === $hits_raw || '' !== (string) $wpdb->last_error");
t15h('R1 active hold read fails closed','includes/class-wca-service.php',"return (bool) $hold_id;");
t15h('R1 slot hold read failure explicit','includes/class-wca-repository.php','wca_slot_hold_read_failed');
t15h('R1 slot conflict query failure explicit','includes/class-wca-repository.php','wca_slot_conflict_query_failed');
t15h('R1 stale request replay read failure explicit','includes/class-wca-appointment-command.php','wca_idempotency_read_failed');
t15h('R1 consent read failure explicit','includes/class-wca-appointment-command.php','wca_consent_read_failed');
t15h('R1 waitlist offer read failure explicit','includes/class-wca-future24.php','wca_waitlist_offer_read_failed');
t15h('R1 waitlist dedupe read failure explicit','includes/class-wca-future24.php','wca_waitlist_dedupe_read_failed');
t15h('R1 windows dedupe read failure explicit','includes/class-wca-future24.php','wca_windows_dedupe_read_failed');
t15h('R1 resource capacity read failure explicit','includes/class-wca-future24.php','wca_resource_capacity_read_failed');
t15h('R1 group capacity read failure explicit','includes/class-wca-future24.php','wca_group_capacity_read_failed');
t15h('R1 group leave read failure explicit','includes/class-wca-future24.php','wca_group_leave_read_failed');
t15h('R1 queue position read failure explicit','includes/class-wca-future24.php','wca_queue_position_read_failed');
t15h('R1 support participant read failure explicit','includes/class-wca-future24.php','wca_support_participant_read_failed');
t15h('R1 virtual room read failure explicit','includes/class-wca-future24.php','wca_virtual_room_read_failed');
t15h('R1 external busy read fails closed','includes/class-wca-future24.php',"return (bool) $busy;");
if($fail){fwrite(STDERR,"T15 regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo 'T15 regression assertions passed: '.$pass.'/'.$pass."\n";
'''
write('tests/fifteenth-twenty-review-regressions.php', test)

p='tests/run-all.php'
s=read(p)
needle="'fourteenth-twenty-review-regressions.php' );"
if needle not in s:
    raise SystemExit('run-all insertion point missing')
write(p, s.replace(needle, "'fourteenth-twenty-review-regressions.php', 'fifteenth-twenty-review-regressions.php' );", 1))

# Round evidence is written only after review completion and after the corrective batch is prepared.
evidence = '''# File 08 — Fifteenth Fresh 20-Round Review / Fix / Retest Evidence\n\n## Governing method\n\nEach round is completed as a review first. Findings are collected without code correction during the review. Only after that review closes are all supported findings from that round corrected together, followed by full retest; only a green corrected state may become the next round baseline. Repository evidence is not staging/live evidence.\n\n## R1 — complete mutation-safety / scheduling-concurrency review\n\nFrozen reviewed baseline: `46ec33f82e732d6b5dfdab0cd88b6d53be9da620` (runtime 1.2.14).\n\nThe complete R1 review found a shared root defect class: several database reads on authoritative conflict, hold, replay, capacity, semantic-de-duplication, buffer/travel-policy, arrival/queue, support-participant, virtual-room and external-busy paths treated an SQL read failure like an empty result. This could permit duplicate or conflicting scheduling state, bypass a current policy, under-enforce rate limiting, or report a false successful absence. No correction was started until the R1 review was closed. The post-review corrective batch makes these paths fail closed or return explicit retryable errors and adds the permanent T15 regression gate.\n\nR1 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R2.**\n'''
write('FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md', evidence)
