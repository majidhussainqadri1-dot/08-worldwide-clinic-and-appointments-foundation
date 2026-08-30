from pathlib import Path
import re

# R15 frozen defect ledger:
# 1) core availability advertises capacity >1 but projection/hold logic rejects after one occupant;
# 2) projection replay ignore compares plain repository key against stored SHA-256 key;
# 3) later holds do not respect buffers owned by already-existing holds.

contracts = Path('includes/class-wca-contracts.php')
s = contracts.read_text()
s2 = s.replace("const SCHEMA_VERSION                  = '3.2.0';", "const SCHEMA_VERSION                  = '3.3.0';")
if s2 == s and "const SCHEMA_VERSION                  = '3.3.0';" not in s:
    raise SystemExit('R15 canonical schema-version anchor not found')
contracts.write_text(s2)

schema = Path('includes/class-wca-schema.php')
s = schema.read_text()
old = """\t\t\t\tidempotency_key char(64) NOT NULL,\n\t\t\t\tclinic_id bigint(20) unsigned NOT NULL,"""
new = """\t\t\t\tidempotency_key char(64) NOT NULL,\n\t\t\t\trule_ref char(36) NOT NULL DEFAULT '',\n\t\t\t\tclinic_id bigint(20) unsigned NOT NULL,"""
if 'rule_ref char(36)' not in s:
    if old not in s: raise SystemExit('R15 slot schema rule_ref anchor not found')
    s = s.replace(old, new, 1)
old = """\t\t\t\tpatient_user_id bigint(20) unsigned NOT NULL,\n\t\t\t\tstart_utc datetime NOT NULL,"""
new = """\t\t\t\tpatient_user_id bigint(20) unsigned NOT NULL,\n\t\t\t\tcapacity smallint(5) unsigned NOT NULL DEFAULT 1,\n\t\t\t\tbuffer_before smallint(5) unsigned NOT NULL DEFAULT 0,\n\t\t\t\tbuffer_after smallint(5) unsigned NOT NULL DEFAULT 0,\n\t\t\t\tstart_utc datetime NOT NULL,"""
if 'buffer_before smallint(5) unsigned NOT NULL DEFAULT 0' not in s:
    if old not in s: raise SystemExit('R15 slot schema capacity/buffer anchor not found')
    s = s.replace(old, new, 1)
old = """\t\t\t\tUNIQUE KEY idempotency_key (idempotency_key),\n\t\t\t\tKEY resource_window (doctor_user_id,start_utc,end_utc,status),"""
new = """\t\t\t\tUNIQUE KEY idempotency_key (idempotency_key),\n\t\t\t\tKEY rule_window (rule_ref,start_utc,end_utc,status),\n\t\t\t\tKEY resource_window (doctor_user_id,start_utc,end_utc,status),"""
if 'KEY rule_window (rule_ref,start_utc,end_utc,status)' not in s:
    if old not in s: raise SystemExit('R15 slot schema index anchor not found')
    s = s.replace(old, new, 1)
schema.write_text(s)

plan = Path('includes/class-wca-plan-guard.php')
s = plan.read_text()
old = """\t\t\t\t'doctor_user_id'  => $query['doctor_user_id'],\n\t\t\t\t'patient_user_id' => absint( $patient_user_id ),\n\t\t\t\t'start_utc'       => $start,"""
new = """\t\t\t\t'doctor_user_id'  => $query['doctor_user_id'],\n\t\t\t\t'patient_user_id' => absint( $patient_user_id ),\n\t\t\t\t'rule_ref'        => strtolower( (string) $rule['public_ref'] ),\n\t\t\t\t'capacity'        => min( 50, max( 1, absint( $rule['capacity'] ?? 1 ) ) ),\n\t\t\t\t'start_utc'       => $start,"""
if "'rule_ref'        => strtolower( (string) $rule['public_ref'] )" not in s:
    if old not in s: raise SystemExit('R15 canonical slot rule/capacity anchor not found')
    s = s.replace(old, new, 1)
plan.write_text(s)

repo = Path('includes/class-wca-repository.php')
s = repo.read_text()
method_marker = "\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function hold_slot( $data ) {"
if 'public static function slot_capacity_available(' not in s:
    if method_marker not in s: raise SystemExit('R15 hold_slot insertion anchor not found')
    method = r'''	/**
	 * Capacity-aware canonical availability gate.
	 *
	 * Same-rule occupants may share a slot up to the versioned rule capacity. Any
	 * overlapping hold from another/legacy rule remains exclusive. Existing hold
	 * buffers are expanded as well as the candidate window, so a later request
	 * cannot silently violate a buffer that was established by the earlier hold.
	 * Legacy appointments that are not represented by a canonical booked hold are
	 * also treated as exclusive compatibility conflicts.
	 *
	 * @return true|false|WP_Error
	 */
	public static function slot_capacity_available( $doctor_id, $rule_ref, $start_utc, $end_utc, $capacity = 1, $ignore_idempotency_key = '' ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		$doctor_id = absint( $doctor_id );
		$rule_ref = strtolower( sanitize_text_field( $rule_ref ) );
		$capacity = WCA_Service::strict_int( $capacity, 1, 50 );
		$start_utc = WCA_Plan_Guard::strict_utc( $start_utc );
		$end_utc = WCA_Plan_Guard::strict_utc( $end_utc );
		if ( ! $doctor_id || ! preg_match( '/^[0-9a-f-]{36}$/', $rule_ref ) || null === $capacity || ! $start_utc || ! $end_utc || strtotime( $end_utc . ' UTC' ) <= strtotime( $start_utc . ' UTC' ) ) {
			return new WP_Error( 'wca_slot_capacity_input', __( 'Canonical slot-capacity evidence is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$ignore_hash = '' !== (string) $ignore_idempotency_key ? hash( 'sha256', (string) $ignore_idempotency_key ) : '';
		$ignore_sql = $ignore_hash ? ' AND idempotency_key<>%s' : '';
		$overlap_sql = "doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND DATE_SUB(start_utc, INTERVAL buffer_before MINUTE)<%s AND DATE_ADD(end_utc, INTERVAL buffer_after MINUTE)>%s";

		$params = array( $doctor_id, self::now(), $end_utc, $start_utc );
		if ( $ignore_hash ) { $params[] = $ignore_hash; }
		$params[] = $rule_ref;
		$foreign = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE {$overlap_sql}{$ignore_sql} AND (rule_ref='' OR rule_ref<>%s) LIMIT 1", $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $foreign && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_capacity_foreign_read_failed', __( 'Cross-rule slot occupancy could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( $foreign ) { return false; }

		$params = array( $doctor_id, self::now(), $end_utc, $start_utc );
		if ( $ignore_hash ) { $params[] = $ignore_hash; }
		$params[] = $rule_ref;
		$count_raw = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$overlap_sql}{$ignore_sql} AND rule_ref=%s", $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $count_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_capacity_count_failed', __( 'Current slot capacity could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( (int) $count_raw >= $capacity ) { return false; }

		/* Compatibility gate: an older active appointment without a canonical booked
		 * hold has no rule/capacity evidence, so it remains exclusive rather than being
		 * silently admitted into a multi-capacity session. */
		$from = gmdate( 'Y-m-d H:i:s', strtotime( $start_utc . ' UTC' ) - 480 * MINUTE_IN_SECONDS );
		$sql = "SELECT p.ID,t.meta_value AS appointment_time,d.meta_value AS appointment_duration
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} st ON st.post_id=p.ID AND st.meta_key='_swc_status' AND st.meta_value IN ('requested','confirmed','checked_in','reschedule_pending')
			INNER JOIN {$wpdb->postmeta} doc ON doc.post_id=p.ID AND doc.meta_key='_swc_doctor_id' AND doc.meta_value=%d
			INNER JOIN {$wpdb->postmeta} t ON t.post_id=p.ID AND t.meta_key='_swc_preferred_at_utc' AND t.meta_value BETWEEN %s AND %s
			LEFT JOIN {$wpdb->postmeta} d ON d.post_id=p.ID AND d.meta_key='_swc_appointment_duration'
			LEFT JOIN {$table} h ON h.appointment_id=p.ID AND h.status='booked' AND h.expires_at>%s
			WHERE p.post_type=%s AND h.id IS NULL";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $doctor_id, $from, $end_utc, self::now(), SWC_Helpers::TYPE ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $rows || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_slot_capacity_legacy_read_failed', __( 'Legacy appointment occupancy could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$candidate_start = strtotime( $start_utc . ' UTC' );
		$candidate_end = strtotime( $end_utc . ' UTC' );
		foreach ( (array) $rows as $row ) {
			$other_start = strtotime( (string) $row->appointment_time . ' UTC' );
			$other_duration = min( 480, max( 10, absint( $row->appointment_duration ) ) );
			$other_end = $other_start + $other_duration * MINUTE_IN_SECONDS;
			if ( $candidate_start < $other_end && $candidate_end > $other_start ) { return false; }
		}
		return true;
	}

'''
    s = s.replace(method_marker, method + method_marker, 1)

# hold_slot canonical fields
old = """\t\t$service_id = absint( $data['service_id'] ?? 0 );\n\t\t$idempotency_plain = sanitize_text_field( $data['idempotency_key'] ?? '' );"""
new = """\t\t$service_id = absint( $data['service_id'] ?? 0 );\n\t\t$rule_ref = strtolower( sanitize_text_field( $data['rule_ref'] ?? '' ) );\n\t\t$capacity = WCA_Service::strict_int( $data['capacity'] ?? 1, 1, 50 );\n\t\tif ( ! preg_match( '/^[0-9a-f-]{36}$/', $rule_ref ) || null === $capacity ) { return new WP_Error( 'wca_slot_capacity_evidence', __( 'A valid availability rule and capacity are required to hold a slot.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t$idempotency_plain = sanitize_text_field( $data['idempotency_key'] ?? '' );"""
if "'wca_slot_capacity_evidence'" not in s:
    if old not in s: raise SystemExit('R15 hold_slot field anchor not found')
    s = s.replace(old, new, 1)

# replay identity includes rule/capacity/buffers
old = """\t\t$replay = static function ( $row ) use ( $clinic_id, $branch_id, $service_id, $doctor_id, $patient_id, $start, $end ) {"""
new = """\t\t$replay = static function ( $row ) use ( $clinic_id, $branch_id, $service_id, $doctor_id, $patient_id, $rule_ref, $capacity, $buffer_before, $buffer_after, $start, $end ) {"""
if new not in s:
    if old not in s: raise SystemExit('R15 replay closure anchor not found')
    s = s.replace(old, new, 1)
old = """\t\t\t\t&& absint( $row['patient_user_id'] ) === $patient_id\n\t\t\t\t&& (string) $row['start_utc'] === $start"""
new = """\t\t\t\t&& absint( $row['patient_user_id'] ) === $patient_id\n\t\t\t\t&& strtolower( (string) ( $row['rule_ref'] ?? '' ) ) === $rule_ref\n\t\t\t\t&& absint( $row['capacity'] ?? 1 ) === $capacity\n\t\t\t\t&& absint( $row['buffer_before'] ?? 0 ) === $buffer_before\n\t\t\t\t&& absint( $row['buffer_after'] ?? 0 ) === $buffer_after\n\t\t\t\t&& (string) $row['start_utc'] === $start"""
if "absint( $row['capacity'] ?? 1 ) === $capacity" not in s:
    if old not in s: raise SystemExit('R15 replay comparison anchor not found')
    s = s.replace(old, new, 1)

# Replace singular conflict with canonical capacity gate
pattern = re.compile(r"\t\t\t\$conflict = \$wpdb->get_var\( \$wpdb->prepare\(\n\t\t\t\t\"SELECT id FROM \{\$table\} WHERE doctor_user_id=%d AND status IN \('held','booked'\) AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1\",\n\t\t\t\t\$doctor_id, self::now\(\), \$conflict_end, \$conflict_start\n\t\t\t\) \);\n\t\t\tif \( '' !== \(string\) \$wpdb->last_error \) \{ return new WP_Error\( 'wca_slot_conflict_query_failed'.*?\n\t\t\t\$conflict_duration = .*?;\n\t\t\tif \( \$conflict \|\| SWC_Helpers::has_conflict\( \$doctor_id, \$conflict_start, \$conflict_duration, 0 \) \) \{\n\t\t\t\treturn new WP_Error\( 'wca_slot_conflict'.*?\n\t\t\t\}\n", re.S)
if "slot_capacity_available( $doctor_id, $rule_ref, $conflict_start" not in s:
    replacement = """\t\t\t$available = self::slot_capacity_available( $doctor_id, $rule_ref, $conflict_start, $conflict_end, $capacity, $idempotency_plain );\n\t\t\tif ( is_wp_error( $available ) ) { return $available; }\n\t\t\tif ( ! $available ) {\n\t\t\t\treturn new WP_Error( 'wca_slot_conflict', __( 'The selected slot has reached capacity or conflicts with another scheduling rule.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t\t}\n"""
    s2, n = pattern.subn(replacement, s, count=1)
    if n != 1: raise SystemExit(f'R15 singular conflict block replacement count={n}')
    s = s2

# Persist capacity evidence and existing-buffer ownership
old = """\t\t\t\t'idempotency_key' => $idempotency_key,\n\t\t\t\t'clinic_id'       => $clinic_id,"""
new = """\t\t\t\t'idempotency_key' => $idempotency_key,\n\t\t\t\t'rule_ref'        => $rule_ref,\n\t\t\t\t'clinic_id'       => $clinic_id,"""
if "'rule_ref'        => $rule_ref" not in s:
    if old not in s: raise SystemExit('R15 persisted rule_ref anchor not found')
    s = s.replace(old, new, 1)
old = """\t\t\t\t'patient_user_id' => $patient_id,\n\t\t\t\t'start_utc'       => $start,"""
new = """\t\t\t\t'patient_user_id' => $patient_id,\n\t\t\t\t'capacity'        => $capacity,\n\t\t\t\t'buffer_before'   => $buffer_before,\n\t\t\t\t'buffer_after'    => $buffer_after,\n\t\t\t\t'start_utc'       => $start,"""
if "'capacity'        => $capacity" not in s:
    if old not in s: raise SystemExit('R15 persisted capacity anchor not found')
    s = s.replace(old, new, 1)
repo.write_text(s)

service = Path('includes/class-wca-service.php')
s = service.read_text()
# Delegate active-hold check to canonical capacity gate, hashing replay key correctly inside repository.
pattern = re.compile(r"\tprivate static function has_active_hold\( \$doctor_id, \$start_utc, \$end_utc, \$ignore_idempotency_key = '' \) \{.*?\n\t\}\n\n\t/\*\* Reproject one exact rule/day slot", re.S)
if "slot_capacity_available( $doctor_id, $rule_ref" not in s:
    repl = r'''	private static function has_active_hold( $doctor_id, $start_utc, $end_utc, $ignore_idempotency_key = '', $rule_ref = '', $capacity = 1 ) {
		$available = WCA_Repository::slot_capacity_available( $doctor_id, $rule_ref, $start_utc, $end_utc, $capacity, $ignore_idempotency_key );
		return is_wp_error( $available ) || ! $available;
	}

	/** Reproject one exact rule/day slot'''
    s2, n = pattern.subn(repl, s, count=1)
    if n != 1: raise SystemExit(f'R15 service has_active_hold replacement count={n}')
    s = s2

old = """! SWC_Helpers::has_conflict( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_minutes, 0 ) && ! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key )"""
new = """! self::has_active_hold( absint( $rule['doctor_user_id'] ), $conflict_start->format( 'Y-m-d H:i:s' ), $conflict_end->format( 'Y-m-d H:i:s' ), $ignore_hold_key, strtolower( (string) $rule['public_ref'] ), max( 1, absint( $rule['capacity'] ?? 1 ) ) )"""
if new not in s:
    if old not in s: raise SystemExit('R15 generated-slot capacity anchor not found')
    s = s.replace(old, new, 1)
service.write_text(s)

# Permanent regression gate.
test = Path('tests/seventeenth-r15-capacity-concurrency-regressions.php')
test.write_text(r'''<?php
$contracts = file_get_contents( __DIR__ . '/../includes/class-wca-contracts.php' );
$schema = file_get_contents( __DIR__ . '/../includes/class-wca-schema.php' );
$guard = file_get_contents( __DIR__ . '/../includes/class-wca-plan-guard.php' );
$repo = file_get_contents( __DIR__ . '/../includes/class-wca-repository.php' );
$service = file_get_contents( __DIR__ . '/../includes/class-wca-service.php' );
$future = file_get_contents( __DIR__ . '/../includes/class-wca-future24.php' );
foreach ( array( $contracts,$schema,$guard,$repo,$service,$future ) as $source ) { if ( false === $source ) { fwrite( STDERR, "T17 R15 source missing.\n" ); exit( 1 ); } }
$required = array(
    array( $contracts, "SCHEMA_VERSION                  = '3.3.0'" ),
    array( $schema, 'rule_ref char(36) NOT NULL' ),
    array( $schema, 'capacity smallint(5) unsigned NOT NULL DEFAULT 1' ),
    array( $schema, 'buffer_before smallint(5) unsigned NOT NULL DEFAULT 0' ),
    array( $schema, 'buffer_after smallint(5) unsigned NOT NULL DEFAULT 0' ),
    array( $schema, 'KEY rule_window (rule_ref,start_utc,end_utc,status)' ),
    array( $guard, "'capacity'        => min( 50, max( 1, absint( \$rule['capacity'] ?? 1 ) ) )" ),
    array( $repo, 'public static function slot_capacity_available(' ),
    array( $repo, "hash( 'sha256', (string) \$ignore_idempotency_key )" ),
    array( $repo, 'DATE_SUB(start_utc, INTERVAL buffer_before MINUTE)' ),
    array( $repo, 'DATE_ADD(end_utc, INTERVAL buffer_after MINUTE)' ),
    array( $repo, "rule_ref='' OR rule_ref<>%s" ),
    array( $repo, 'if ( (int) $count_raw >= $capacity ) { return false; }' ),
    array( $repo, "h.status='booked'" ),
    array( $repo, "'rule_ref'        => \$rule_ref" ),
    array( $repo, "'buffer_before'   => \$buffer_before" ),
    array( $service, 'WCA_Repository::slot_capacity_available(' ),
    array( $service, "max( 1, absint( \$rule['capacity'] ?? 1 ) )" ),
    array( $future, "'F08-FUT-05' => array( 'slug' => 'group_capacity'" ),
    array( $future, "semantic_lock( 'group-session'" ),
);
foreach ( $required as $pair ) { if ( false === strpos( $pair[0], $pair[1] ) ) { fwrite( STDERR, "T17 R15 invariant missing: {$pair[1]}\n" ); exit( 1 ); } }
if ( false !== strpos( $service, "idempotency_key<>%s AND expires_at>%s" ) ) { fwrite( STDERR, "T17 R15 stale plain-vs-hash replay comparison remains.\n" ); exit( 1 ); }
if ( false !== strpos( $repo, "SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1" ) ) { fwrite( STDERR, "T17 R15 singular overlap gate remains in canonical hold path.\n" ); exit( 1 ); }
echo "T17 R15 capacity/concurrency regressions passed.\n";
''')

run = Path('tests/run-all.php')
r = run.read_text()
marker = "'seventeenth-r12-idempotency-uncertainty-regressions.php'"
addition = marker + ", 'seventeenth-r15-capacity-concurrency-regressions.php'"
if 'seventeenth-r15-capacity-concurrency-regressions.php' not in r:
    if r.count(marker) != 1: raise SystemExit('R15 run-all anchor not unique')
    run.write_text(r.replace(marker, addition, 1))
