from pathlib import Path


def replace_once(path, old, new):
    p = Path(path)
    s = p.read_text()
    if old not in s:
        raise SystemExit(f"expected snippet not found in {path}: {old[:120]!r}")
    if s.count(old) != 1:
        raise SystemExit(f"expected exactly one snippet in {path}, found {s.count(old)}")
    p.write_text(s.replace(old, new, 1))

# R10-1: protected appointment projection must not flatten repository read failure.
replace_once(
    'includes/class-wca-rest.php',
    """\tprivate static function appointment_projection( $id ) {\n\t\t$clinic = WCA_Repository::get_clinic( absint( SWC_Helpers::meta( $id, 'clinic_id' ) ), false );\n\t\t$service = WCA_Repository::get_service( absint( SWC_Helpers::meta( $id, 'service_id' ) ), false );\n\t\treturn array(\n""",
    """\tprivate static function appointment_projection( $id ) {\n\t\tWCA_Repository::clear_read_error();\n\t\t$clinic = WCA_Repository::get_clinic( absint( SWC_Helpers::meta( $id, 'clinic_id' ) ), false );\n\t\t$clinic_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $clinic_read_error ) ) { return $clinic_read_error; }\n\t\tWCA_Repository::clear_read_error();\n\t\t$service = WCA_Repository::get_service( absint( SWC_Helpers::meta( $id, 'service_id' ) ), false );\n\t\t$service_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $service_read_error ) ) { return $service_read_error; }\n\t\treturn array(\n"""
)

# R10-2/R10-4: opaque-reference lookup must distinguish DB failure from not-found,
# and duplicate canonical refs must never fall through to a legacy unique match.
p = Path('includes/class-wca-opaque-api.php')
s = p.read_text()
marker = "\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }"
count = s.count(marker)
if count != 4:
    raise SystemExit(f'expected 4 opaque appointment lookup call sites, found {count}')
s = s.replace(
    marker,
    "\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( is_wp_error( $id ) ) { return $id; }\n\t\tif ( ! $id ) { return self::not_found(); }"
)

old = """\tpublic static function submit_clinic_review( WP_REST_Request $request ) {\n\t\t$clinic = WCA_Repository::get_clinic( sanitize_text_field( $request['ref'] ), false );\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n"""
new = """\tpublic static function submit_clinic_review( WP_REST_Request $request ) {\n\t\tWCA_Repository::clear_read_error();\n\t\t$clinic = WCA_Repository::get_clinic( sanitize_text_field( $request['ref'] ), false );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n"""
if s.count(old) != 1:
    raise SystemExit('submit clinic-ref snippet not found exactly once')
s = s.replace(old, new, 1)

old = """\tpublic static function activate_clinic( WP_REST_Request $request ) {\n\t\t$clinic = WCA_Repository::get_clinic( sanitize_text_field( $request['ref'] ), false );\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n"""
new = """\tpublic static function activate_clinic( WP_REST_Request $request ) {\n\t\tWCA_Repository::clear_read_error();\n\t\t$clinic = WCA_Repository::get_clinic( sanitize_text_field( $request['ref'] ), false );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n"""
if s.count(old) != 1:
    raise SystemExit('activate clinic-ref snippet not found exactly once')
s = s.replace(old, new, 1)

start = s.index("\tprivate static function appointment_id( $ref ) {")
end = s.index("\n\tprivate static function appointment_ref( $id ) {", start)
replacement = """\tprivate static function appointment_id( $ref ) {\n\t\tglobal $wpdb;\n\t\t$ref = strtolower( sanitize_text_field( $ref ) );\n\t\tif ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return 0; }\n\t\tforeach ( array( '_swc_public_ref', 'public_ref' ) as $meta_key ) {\n\t\t\t$sql = $wpdb->prepare(\n\t\t\t\t\"SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID WHERE p.post_type=%s AND p.post_status NOT IN ('trash','auto-draft','inherit') AND pm.meta_key=%s AND pm.meta_value=%s ORDER BY p.ID ASC LIMIT 2\",\n\t\t\t\tSWC_Helpers::TYPE,\n\t\t\t\t$meta_key,\n\t\t\t\t$ref\n\t\t\t);\n\t\t\t$ids_raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( '' !== (string) $wpdb->last_error ) {\n\t\t\t\treturn new WP_Error( 'wca_appointment_ref_read_failed', __( 'Appointment reference could not be resolved safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );\n\t\t\t}\n\t\t\t$ids = array_values( array_filter( array_map( 'absint', (array) $ids_raw ) ) );\n\t\t\tif ( 1 === count( $ids ) ) { return $ids[0]; }\n\t\t\tif ( count( $ids ) > 1 ) { return 0; }\n\t\t}\n\t\treturn 0;\n\t}\n"""
s = s[:start] + replacement + s[end:]
p.write_text(s)

# R10-3: public slot projection must propagate any per-rule clinic/branch/service read failure.
replace_once(
    'includes/class-wca-service.php',
    """\t\tforeach ( $rules as $rule ) {\n\t\t\t$versions[] = $rule['public_ref'] . ':' . $rule['version'];\n\t\t\t$slots = array_merge( $slots, self::generate_rule_slots( $rule, $from, $to, $duration, $timezone, $limit, $display_from, $display_to ) );\n\t\t}\n""",
    """\t\tforeach ( $rules as $rule ) {\n\t\t\t$versions[] = $rule['public_ref'] . ':' . $rule['version'];\n\t\t\tWCA_Repository::clear_read_error();\n\t\t\t$generated = self::generate_rule_slots( $rule, $from, $to, $duration, $timezone, $limit, $display_from, $display_to );\n\t\t\t$projection_read_error = WCA_Repository::consume_read_error();\n\t\t\tif ( is_wp_error( $projection_read_error ) ) { return $projection_read_error; }\n\t\t\t$slots = array_merge( $slots, $generated );\n\t\t}\n"""
)

# Permanent R10 regression gates.
p = Path('tests/sixteenth-twenty-review-regressions.php')
s = p.read_text()
needle = "if($fail){fwrite(STDERR,\"T16 regression gate failed:\\n- \".implode(\"\\n- \",$fail).\"\\n\");exit(1);} echo \"T16 regression assertions passed: {$pass}/{$pass}\\n\";"
if needle not in s:
    raise SystemExit('T16 test footer not found')
add = """t16h('R10 protected appointment clinic read failure propagates','includes/class-wca-rest.php','if ( is_wp_error( $clinic_read_error ) ) { return $clinic_read_error; }');\nt16h('R10 protected appointment service read failure propagates','includes/class-wca-rest.php','if ( is_wp_error( $service_read_error ) ) { return $service_read_error; }');\nt16h('R10 opaque appointment lookup DB failure is explicit','includes/class-wca-opaque-api.php','wca_appointment_ref_read_failed');\nt16h('R10 opaque clinic ref mutation consumes repository read errors','includes/class-wca-opaque-api.php','if ( is_wp_error( $read_error ) ) { return $read_error; }');\nt16h('R10 public per-rule slot projection propagates DB read errors','includes/class-wca-service.php','if ( is_wp_error( $projection_read_error ) ) { return $projection_read_error; }');\n"""
s = s.replace(needle, add + needle, 1)
p.write_text(s)

print('R10 closed defect ledger applied')
