from pathlib import Path


def replace_once(path, old, new, label):
    p = Path(path)
    s = p.read_text()
    n = s.count(old)
    if n != 1:
        raise SystemExit(f"{label}: expected exactly 1 match, found {n}")
    p.write_text(s.replace(old, new, 1))


# R7-A: serialize slot holds by practitioner, not start-date.
replace_once(
    "includes/class-wca-repository.php",
    "$lock_name = 'wca-slot-' . substr( hash( 'sha256', $doctor_id . '|' . substr( $start, 0, 10 ) ), 0, 48 );",
    "$lock_name = 'wca-slot-doctor-' . substr( hash( 'sha256', (string) $doctor_id ), 0, 48 );",
    "doctor-wide slot lock",
)
replace_once(
    "includes/class-wca-repository.php",
    "\t\t} finally {\n\t\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );\n\t\t}\n\t}\n\n\t/** @return array<string,mixed>|null */\n\tpublic static function get_slot_hold",
    "\t\t} finally {\n\t\t\t$released_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );\n\t\t\tif ( 1 !== (int) $released_raw ) {\n\t\t\t\tWCA_Observability::metric( 'slot_lock_release_failed_total', 1 );\n\t\t\t\tWCA_Observability::log( 'error', 'slot_lock_release_failed', array( 'db_error' => '' !== (string) $wpdb->last_error ) );\n\t\t\t}\n\t\t}\n\t}\n\n\t/** @return array<string,mixed>|null */\n\tpublic static function get_slot_hold",
    "slot lock release observability",
)

# R7-B/C: centralize Future24 advisory lock acquisition and release.
replace_once(
    "includes/class-wca-future24.php",
    "\tprivate static function semantic_lock( $scope, $identity ) {\n\t\tglobal $wpdb;\n\t\t$lock = 'wca-f24-' . sanitize_key( $scope ) . '-' . substr( hash( 'sha256', (string) $identity ), 0, 32 );\n\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) {\n\t\t\treturn new WP_Error( 'wca_future24_busy', __( 'This scheduling operation is already being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\treturn $lock;\n\t}\n\n\tprivate static function release_semantic_lock( $lock ) {\n\t\tglobal $wpdb;\n\t\tif ( is_string( $lock ) && '' !== $lock ) { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }\n\t}",
    "\tprivate static function semantic_lock( $scope, $identity ) {\n\t\tglobal $wpdb;\n\t\t$lock = 'wca-f24-' . sanitize_key( $scope ) . '-' . substr( hash( 'sha256', (string) $identity ), 0, 32 );\n\t\t$locked_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) );\n\t\tif ( null === $locked_raw && '' !== (string) $wpdb->last_error ) {\n\t\t\treturn new WP_Error( 'wca_future24_lock_read_failed', __( 'The scheduling lock could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );\n\t\t}\n\t\tif ( 1 !== (int) $locked_raw ) {\n\t\t\treturn new WP_Error( 'wca_future24_busy', __( 'This scheduling operation is already being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\treturn $lock;\n\t}\n\n\tprivate static function release_semantic_lock( $lock ) {\n\t\tglobal $wpdb;\n\t\tif ( ! is_string( $lock ) || '' === $lock ) { return; }\n\t\t$released_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );\n\t\tif ( 1 !== (int) $released_raw ) {\n\t\t\tWCA_Observability::metric( 'future24_lock_release_failed_total', 1 );\n\t\t\tWCA_Observability::log( 'error', 'future24_lock_release_failed', array( 'db_error' => '' !== (string) $wpdb->last_error ) );\n\t\t}\n\t}",
    "Future24 semantic lock hardening",
)

p = Path("includes/class-wca-future24.php")
s = p.read_text()
old = "$lock = 'wca-f24-resource-' . substr( hash( 'sha256', strtolower( $resource_ref ) ), 0, 32 );\n\t\t$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) );\n\t\tif ( 1 !== $locked ) { return new WP_Error( 'wca_resource_busy', __( 'The resource is being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }"
new = "$lock = self::semantic_lock( 'resource', strtolower( $resource_ref ) );\n\t\tif ( is_wp_error( $lock ) ) { return $lock; }"
if s.count(old) != 1:
    raise SystemExit(f"resource lock: expected 1, found {s.count(old)}")
s = s.replace(old, new, 1)
old_group = "$lock = 'wca-f24-group-' . substr( hash( 'sha256', strtolower( $session_ref ) ), 0, 32 );\n\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) { return new WP_Error( 'wca_group_busy', __( 'The group session is being updated.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }"
if s.count(old_group) != 3:
    raise SystemExit(f"group lock: expected 3, found {s.count(old_group)}")
s = s.replace(old_group, "$lock = self::semantic_lock( 'group-session', strtolower( $session_ref ) );\n\t\tif ( is_wp_error( $lock ) ) { return $lock; }", 3)
direct_release = "$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );"
if s.count(direct_release) < 4:
    raise SystemExit(f"direct Future24 releases unexpectedly low: {s.count(direct_release)}")
s = s.replace(direct_release, "self::release_semantic_lock( $lock );")
p.write_text(s)

# R7-C: practitioner-ref release failure observability.
replace_once(
    "includes/class-wca-plan-guard.php",
    "\t\t} finally {\n\t\t\t$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );\n\t\t}\n\t}",
    "\t\t} finally {\n\t\t\t$released_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );\n\t\t\tif ( 1 !== (int) $released_raw ) {\n\t\t\t\tWCA_Observability::metric( 'practitioner_ref_lock_release_failed_total', 1 );\n\t\t\t\tWCA_Observability::log( 'error', 'practitioner_ref_lock_release_failed', array( 'user_id' => $user_id, 'db_error' => '' !== (string) $wpdb->last_error ) );\n\t\t\t}\n\t\t}\n\t}",
    "practitioner lock release observability",
)

# R7-D: rollback failure must surface state uncertainty.
p = Path("includes/class-wca-repository.php")
s = p.read_text()
repls = [
(
"\t\t\tif ( is_wp_error( $result ) ) {\n\t\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\treturn $result;\n\t\t\t}",
"\t\t\tif ( is_wp_error( $result ) ) {\n\t\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\tif ( false === $rolled_back ) { return new WP_Error( 'wca_transaction_rollback_failed', __( 'The mutation failed and rollback could not be verified; storage state is uncertain.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'state_uncertain' => true ) ); }\n\t\t\t\treturn $result;\n\t\t\t}"),
(
"\t\t\tif ( false === $committed ) {\n\t\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\treturn new WP_Error( sanitize_key( $error_code . '_commit' ), __( 'The mutation transaction could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );\n\t\t\t}",
"\t\t\tif ( false === $committed ) {\n\t\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\tif ( false === $rolled_back ) { return new WP_Error( 'wca_transaction_commit_rollback_failed', __( 'Commit failed and rollback could not be verified; storage state is uncertain.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'state_uncertain' => true ) ); }\n\t\t\t\treturn new WP_Error( sanitize_key( $error_code . '_commit' ), __( 'The mutation transaction could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );\n\t\t\t}"),
(
"\t\t} catch ( Throwable $error ) {\n\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\treturn new WP_Error( sanitize_key( $error_code ), __( 'The mutation could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );",
"\t\t} catch ( Throwable $error ) {\n\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\tif ( false === $rolled_back ) { return new WP_Error( 'wca_transaction_exception_rollback_failed', __( 'The mutation raised an error and rollback could not be verified; storage state is uncertain.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'state_uncertain' => true ) ); }\n\t\t\treturn new WP_Error( sanitize_key( $error_code ), __( 'The mutation could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );"),
]
for old,new in repls:
    if s.count(old) != 1:
        raise SystemExit(f"repository rollback pattern mismatch: {s.count(old)}")
    s = s.replace(old,new,1)
p.write_text(s)

p = Path("includes/class-swc-helpers.php")
s = p.read_text()
repls = [
(
"\t\t\tif ( is_wp_error( $result ) ) {\n\t\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\twp_cache_delete( absint( $appointment_id ), 'post_meta' );\n\t\t\t\treturn $result;\n\t\t\t}",
"\t\t\tif ( is_wp_error( $result ) ) {\n\t\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\twp_cache_delete( absint( $appointment_id ), 'post_meta' );\n\t\t\t\tif ( false === $rolled_back ) { return new WP_Error( 'swc_transaction_rollback_failed', __( 'The appointment mutation failed and rollback could not be verified; storage state is uncertain.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'state_uncertain' => true ) ); }\n\t\t\t\treturn $result;\n\t\t\t}"),
(
"\t\t\tif ( false === $committed ) {\n\t\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\twp_cache_delete( absint( $appointment_id ), 'post_meta' );\n\t\t\t\treturn new WP_Error( 'swc_transaction_commit_failed', __( 'The appointment transaction could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );\n\t\t\t}",
"\t\t\tif ( false === $committed ) {\n\t\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\t\twp_cache_delete( absint( $appointment_id ), 'post_meta' );\n\t\t\t\tif ( false === $rolled_back ) { return new WP_Error( 'swc_transaction_commit_rollback_failed', __( 'Appointment commit failed and rollback could not be verified; storage state is uncertain.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'state_uncertain' => true ) ); }\n\t\t\t\treturn new WP_Error( 'swc_transaction_commit_failed', __( 'The appointment transaction could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );\n\t\t\t}"),
(
"\t\t} catch ( Throwable $error ) {\n\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\twp_cache_delete( absint( $appointment_id ), 'post_meta' );\n\t\t\treturn new WP_Error( 'swc_transaction_failed', __( 'The appointment update could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );",
"\t\t} catch ( Throwable $error ) {\n\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery\n\t\t\twp_cache_delete( absint( $appointment_id ), 'post_meta' );\n\t\t\tif ( false === $rolled_back ) { return new WP_Error( 'swc_transaction_exception_rollback_failed', __( 'The appointment update failed and rollback could not be verified; storage state is uncertain.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'state_uncertain' => true ) ); }\n\t\t\treturn new WP_Error( 'swc_transaction_failed', __( 'The appointment update could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );"),
]
for old,new in repls:
    if s.count(old) != 1:
        raise SystemExit(f"SWC rollback pattern mismatch: {s.count(old)}")
    s = s.replace(old,new,1)
p.write_text(s)

# Permanent R7 regression gates.
p = Path("tests/sixteenth-twenty-review-regressions.php")
s = p.read_text()
marker = 'if($fail){fwrite(STDERR,"T16 regression gate failed:\\n- ".implode("\\n- ",$fail)."\\n");exit(1);}'
additions = """t16h('R7 doctor-wide slot lock prevents cross-midnight lock split','includes/class-wca-repository.php',\"wca-slot-doctor-\");
t16h('R7 doctor lock identity is date-independent','includes/class-wca-repository.php',\"hash( 'sha256', (string) $doctor_id )\");
t16h('R7 slot lock release failure observable','includes/class-wca-repository.php','slot_lock_release_failed_total');
t16h('R7 Future24 lock SQL failure explicit','includes/class-wca-future24.php','wca_future24_lock_read_failed');
t16h('R7 Future24 lock release failure observable','includes/class-wca-future24.php','future24_lock_release_failed_total');
t16h('R7 practitioner lock release failure observable','includes/class-wca-plan-guard.php','practitioner_ref_lock_release_failed_total');
t16h('R7 repository rollback uncertainty explicit','includes/class-wca-repository.php','wca_transaction_rollback_failed');
t16h('R7 appointment rollback uncertainty explicit','includes/class-swc-helpers.php','swc_transaction_rollback_failed');
"""
if marker not in s:
    raise SystemExit("T16 marker missing")
p.write_text(s.replace(marker, additions + marker, 1))
