from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]


def replace_once(path: str, old: str, new: str) -> None:
    p = ROOT / path
    text = p.read_text(encoding="utf-8")
    count = text.count(old)
    if count != 1:
        raise SystemExit(f"{path}: expected exactly one match, found {count}")
    p.write_text(text.replace(old, new, 1), encoding="utf-8")


# R8-D1 + R8-D3: public service relationship freshness and lossless partial service updates.
service_path = "includes/class-wca-service.php"
old = """\t\tif ( $service_id ) {\n\t\t\t$current = self::repository_read( static function () use ( $service_id ) { return WCA_Repository::get_service( $service_id, false ); } );\n\t\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t}\n\t\t$consultation_type = sanitize_key( $data['consultation_type'] ?? ( $current['consultation_type'] ?? '' ) );\n"""
new = """\t\tif ( $service_id ) {\n\t\t\t$current = self::repository_read( static function () use ( $service_id ) { return WCA_Repository::get_service( $service_id, false ); } );\n\t\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t\t// Versioned service updates are partial by contract. Omitted mutable fields\n\t\t\t// must retain their current values rather than being reset by repository defaults.\n\t\t\tforeach ( array( 'name', 'branch_id', 'doctor_user_id', 'tax_policy', 'refund_policy', 'cancellation_policy', 'status' ) as $preserve_field ) {\n\t\t\t\tif ( ! array_key_exists( $preserve_field, $data ) ) {\n\t\t\t\t\t$data[ $preserve_field ] = array_key_exists( $preserve_field, $current ) ? $current[ $preserve_field ] : ( in_array( $preserve_field, array( 'branch_id', 'doctor_user_id' ), true ) ? 0 : '' );\n\t\t\t\t}\n\t\t\t}\n\t\t}\n\t\t$consultation_type = sanitize_key( $data['consultation_type'] ?? ( $current['consultation_type'] ?? '' ) );\n"""
replace_once(service_path, old, new)

old = """\t\tif ( ! $clinic ) { return array(); }\n\t\t$projection = array(\n"""
new = """\t\tif ( ! $clinic ) { return array(); }\n\t\t// A service is public only while its assigned practitioner has a current\n\t\t// clinic-serving relationship. Global File 09 eligibility alone is insufficient.\n\t\t$eligible_service_refs = array();\n\t\tforeach ( (array) ( $private['services'] ?? array() ) as $private_service ) {\n\t\t\tif ( 'active' !== (string) ( $private_service['status'] ?? '' ) ) { continue; }\n\t\t\t$service_doctor_id = absint( $private_service['doctor_user_id'] ?? 0 ) ?: $owner_id;\n\t\t\tif ( ! $service_doctor_id || ! SWC_Doctor_Authority::is_eligible( $service_doctor_id ) ) { continue; }\n\t\t\tif ( ! WCA_Authorization::doctor_can_serve_clinic( $private, $service_doctor_id ) ) { continue; }\n\t\t\t$service_ref = strtolower( (string) ( $private_service['public_ref'] ?? '' ) );\n\t\t\tif ( preg_match( '/^[0-9a-f-]{36}$/', $service_ref ) ) { $eligible_service_refs[ $service_ref ] = true; }\n\t\t}\n\t\t$public_services = array_values( array_filter( (array) ( $clinic['services'] ?? array() ), static function ( $service ) use ( $eligible_service_refs ) {\n\t\t\t$service_ref = strtolower( (string) ( $service['public_ref'] ?? '' ) );\n\t\t\treturn isset( $eligible_service_refs[ $service_ref ] );\n\t\t} ) );\n\t\t$projection = array(\n"""
replace_once(service_path, old, new)
replace_once(service_path, "\t\t\t'services'       => $clinic['services'],\n", "\t\t\t'services'       => $public_services,\n")

# R8-D2: verification reconciliation must refresh every clinic whose public scheduling
# projection references the doctor, not only clinics owned by that doctor.
recon = ROOT / "includes/class-wca-verification-reconciliation.php"
text = recon.read_text(encoding="utf-8")
marker = "\tprivate static function publish_clinic_eligibility( $doctor_user_id, $eligible, $reason ) {"
start = text.find(marker)
if start < 0:
    raise SystemExit("verification reconciliation method marker missing")
class_close = text.rfind("\n}\n")
if class_close < start:
    raise SystemExit("verification reconciliation class close missing")
method = r'''\tprivate static function publish_clinic_eligibility( $doctor_user_id, $eligible, $reason ) {
\t\tglobal $wpdb;
\t\tif ( ! $doctor_user_id ) { return new WP_Error( 'wca_verification_reconciliation_doctor', __( 'A doctor identity is required for reconciliation.', 'worldwide-clinic-appointments' ) ); }
\t\t$tables = WCA_Schema::tables();
\t\t$clinic_table = $tables['clinics'];
\t\t$service_table = $tables['services'];
\t\t$availability_table = $tables['availability'];
\t\t$sql = $wpdb->prepare(
\t\t\t"SELECT DISTINCT c.id FROM {$clinic_table} c LEFT JOIN {$service_table} s ON s.clinic_id=c.id LEFT JOIN {$availability_table} a ON a.clinic_id=c.id WHERE c.owner_user_id=%d OR s.doctor_user_id=%d OR a.doctor_user_id=%d ORDER BY c.id ASC",
\t\t\t$doctor_user_id,
\t\t\t$doctor_user_id,
\t\t\t$doctor_user_id
\t\t);
\t\t$clinic_ids_raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $clinic_ids_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_verification_reconciliation_read', __( 'Affected clinics could not be read safely for verification reconciliation.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t$clinic_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $clinic_ids_raw ) ) ) );
\t\tforeach ( $clinic_ids as $clinic_id ) {
\t\t\tWCA_Repository::clear_read_error();
\t\t\t$clinic = WCA_Repository::get_clinic( $clinic_id, false );
\t\t\t$read_error = WCA_Repository::consume_read_error();
\t\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }
\t\t\tif ( ! $clinic ) { continue; }
\t\t\t$clinic_ref = sanitize_text_field( isset( $clinic['public_ref'] ) ? $clinic['public_ref'] : '' );
\t\t\tif ( ! preg_match( '/^[0-9a-f-]{36}$/i', $clinic_ref ) ) { continue; }
\t\t\t$trace = WCA_Observability::trace_id();
\t\t\t$payload = array(
\t\t\t\t'contract'    => 'wca.clinic-eligibility',
\t\t\t\t'version'     => self::CONTRACT_VERSION,
\t\t\t\t'clinic_ref'  => strtolower( $clinic_ref ),
\t\t\t\t'eligible'    => (bool) $eligible,
\t\t\t\t'reason'      => $reason,
\t\t\t\t'owner'       => 'File08',
\t\t\t\t'source_owner'=> 'File09',
\t\t\t\t'checked_at'  => gmdate( 'c' ),
\t\t\t);
\t\t\t$written = WCA_Repository::transaction( static function () use ( $clinic_ref, $payload, $trace, $eligible ) {
\t\t\t\t$event = WCA_Repository::append_event( 'ClinicEligibilityChanged.v1', 'clinic', $clinic_ref, $payload, 0, $trace );
\t\t\t\tif ( is_wp_error( $event ) ) { return $event; }
\t\t\t\t$outbox = WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(
\t\t\t\t\t'contract'      => 'wca.file26-clinic-projection',
\t\t\t\t\t'version'       => WCA_Central_Governance::FILE26_PROJECTION_VERSION,
\t\t\t\t\t'object_type'   => 'clinic',
\t\t\t\t\t'public_ref'    => strtolower( $clinic_ref ),
\t\t\t\t\t'eligible'      => (bool) $eligible,
\t\t\t\t\t'change_source' => 'ClinicEligibilityChanged.v1',
\t\t\t\t\t'owner'         => 'File08',
\t\t\t\t), $trace );
\t\t\t\treturn is_wp_error( $outbox ) ? $outbox : true;
\t\t\t}, 'wca_verification_reconciliation_write' );
\t\t\tif ( is_wp_error( $written ) ) { return $written; }
\t\t}
\t\tWCA_Observability::metric( 'verification_reconciliation_total', 1, array( 'eligible' => $eligible ? 'yes' : 'no' ) );
\t\treturn true;
\t}
'''.replace('\\t', '\t')
recon.write_text(text[:start] + method + text[class_close:], encoding="utf-8")

# Permanent R8 source-level regression gate.
test_path = ROOT / "tests/t19-r8-service-projection-reconciliation-regressions.php"
test_path.write_text(r'''<?php
$root = dirname( __DIR__ );
$checks = array(
    array( 'service update preserves omitted mutable state', 'includes/class-wca-service.php', "foreach ( array( 'name', 'branch_id', 'doctor_user_id', 'tax_policy', 'refund_policy', 'cancellation_policy', 'status' ) as $preserve_field" ),
    array( 'public services recheck clinic serving authority', 'includes/class-wca-service.php', 'WCA_Authorization::doctor_can_serve_clinic( $private, $service_doctor_id )' ),
    array( 'public projection uses filtered services', 'includes/class-wca-service.php', "'services'       => $public_services" ),
    array( 'verification reconciliation scans service assignments', 'includes/class-wca-verification-reconciliation.php', 's.doctor_user_id=%d' ),
    array( 'verification reconciliation scans availability assignments', 'includes/class-wca-verification-reconciliation.php', 'a.doctor_user_id=%d' ),
    array( 'verification reconciliation no longer owner-only pages', 'includes/class-wca-verification-reconciliation.php', 'SELECT DISTINCT c.id' ),
    array( 'reconciliation emits File26 refresh', 'includes/class-wca-verification-reconciliation.php', "File26.SearchProjectionChanged.v1" ),
);
$fail = array();
$pass = 0;
foreach ( $checks as $check ) {
    $text = file_get_contents( $root . '/' . $check[1] );
    if ( is_string( $text ) && false !== strpos( $text, $check[2] ) ) {
        echo 'PASS ' . (++$pass) . ': ' . $check[0] . "\n";
    } else {
        $fail[] = $check[0];
    }
}
if ( $fail ) {
    fwrite( STDERR, "T19 R8 regression gate failed:\n- " . implode( "\n- ", $fail ) . "\n" );
    exit( 1 );
}
echo 'T19 R8 service/projection/reconciliation regressions: PASS ' . $pass . '/' . $pass . "\n";
''', encoding="utf-8")

run_all = ROOT / "tests/run-all.php"
run_text = run_all.read_text(encoding="utf-8")
old_runner = "'t19-r7-practitioner-authority-regressions.php' );"
new_runner = "'t19-r7-practitioner-authority-regressions.php', 't19-r8-service-projection-reconciliation-regressions.php' );"
if run_text.count(old_runner) != 1:
    raise SystemExit("run-all R7 tail marker missing or duplicated")
run_all.write_text(run_text.replace(old_runner, new_runner, 1), encoding="utf-8")

print("R8 frozen ledger corrections applied.")
