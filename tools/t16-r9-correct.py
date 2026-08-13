from pathlib import Path


def replace_once(path, old, new, label):
    p=Path(path); s=p.read_text(); n=s.count(old)
    if n!=1: raise SystemExit(f'{label}: expected 1, found {n}')
    p.write_text(s.replace(old,new,1))

# R9-A: verify every dbDelta definition's columns and indexes before persisting schema completion.
p=Path('includes/class-wca-schema.php'); s=p.read_text()
old="""\t\tforeach ( $definitions as $sql ) {
\t\t\tdbDelta( $sql );
\t\t}

\t\t$missing = array();
\t\tforeach ( $tables as $key => $table ) {
\t\t\tif ( ! self::table_exists( $table ) ) {
\t\t\t\t$missing[] = $key;
\t\t\t}
\t\t}
\t\tif ( $missing ) {
\t\t\tthrow new RuntimeException( 'File 08 canonical tables missing: ' . implode( ', ', $missing ) );
\t\t}
"""
new="""\t\tforeach ( $definitions as $sql ) {
\t\t\tdbDelta( $sql );
\t\t\t$verified = self::verify_definition_sql( $sql );
\t\t\tif ( is_wp_error( $verified ) ) { throw new RuntimeException( $verified->get_error_message() ); }
\t\t}

\t\t$missing = array();
\t\tforeach ( $tables as $key => $table ) {
\t\t\tif ( ! self::table_exists( $table ) ) {
\t\t\t\t$missing[] = $key;
\t\t\t}
\t\t}
\t\tif ( $missing ) {
\t\t\tthrow new RuntimeException( 'File 08 canonical tables missing: ' . implode( ', ', $missing ) );
\t\t}
"""
if s.count(old)!=1: raise SystemExit(f'canonical dbDelta block: {s.count(old)}')
s=s.replace(old,new,1)
insert="""
\t/** Verify a dbDelta CREATE TABLE definition before any schema-version marker is advanced. @return true|WP_Error */
\tpublic static function verify_definition_sql( $sql ) {
\t\tglobal $wpdb;
\t\tif ( ! preg_match( '/CREATE\\s+TABLE\\s+([^\\s(]+)\\s*\\((.*)\\)\\s*[^;]*;?$/is', trim( (string) $sql ), $match ) ) {
\t\t\treturn new WP_Error( 'wca_schema_definition_invalid', __( 'A File 08 schema definition could not be parsed for verification.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t}
\t\t$table = trim( $match[1], "` \\t\\r\\n" );
\t\t$body  = (string) $match[2];
\t\t$wpdb->last_error = '';
\t\t$columns_raw = $wpdb->get_col( 'SHOW COLUMNS FROM `' . esc_sql( $table ) . '`', 0 ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $columns_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_schema_columns_read_failed', __( 'File 08 schema columns could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'table' => sanitize_text_field( $table ) ) ); }
\t\t$columns = array_map( 'strtolower', array_map( 'strval', (array) $columns_raw ) );
\t\t$expected_columns = array(); $expected_indexes = array();
\t\tforeach ( preg_split( '/\\r?\\n/', $body ) as $line ) {
\t\t\t$line = trim( rtrim( trim( $line ), ',' ) );
\t\t\tif ( preg_match( '/^(PRIMARY|UNIQUE\\s+KEY|KEY)\\b/i', $line ) ) {
\t\t\t\tif ( preg_match( '/^PRIMARY\\s+KEY/i', $line ) ) { $expected_indexes[] = 'primary'; }
\t\t\t\telseif ( preg_match( '/^(?:UNIQUE\\s+KEY|KEY)\\s+`?([A-Za-z0-9_]+)`?/i', $line, $idx ) ) { $expected_indexes[] = strtolower( $idx[1] ); }
\t\t\t\tcontinue;
\t\t\t}
\t\t\tif ( preg_match( '/^`?([A-Za-z0-9_]+)`?\\s+[A-Za-z]/', $line, $col ) ) { $expected_columns[] = strtolower( $col[1] ); }
\t\t}
\t\t$missing_columns = array_values( array_diff( array_unique( $expected_columns ), $columns ) );
\t\tif ( $missing_columns ) { return new WP_Error( 'wca_schema_columns_missing', __( 'File 08 schema verification found missing columns.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'columns' => $missing_columns ) ); }
\t\t$wpdb->last_error = '';
\t\t$indexes_raw = $wpdb->get_results( 'SHOW INDEX FROM `' . esc_sql( $table ) . '`', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $indexes_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_schema_indexes_read_failed', __( 'File 08 schema indexes could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'table' => sanitize_text_field( $table ) ) ); }
\t\t$indexes = array(); foreach ( (array) $indexes_raw as $row ) { if ( isset( $row['Key_name'] ) ) { $indexes[] = strtolower( (string) $row['Key_name'] ); } }
\t\t$missing_indexes = array_values( array_diff( array_unique( $expected_indexes ), array_unique( $indexes ) ) );
\t\tif ( $missing_indexes ) { return new WP_Error( 'wca_schema_indexes_missing', __( 'File 08 schema verification found missing indexes.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'indexes' => $missing_indexes ) ); }
\t\treturn true;
\t}

"""
needle="\tpublic static function maybe_upgrade() {"
if s.count(needle)!=1: raise SystemExit('schema helper insertion point mismatch')
s=s.replace(needle,insert+needle,1); p.write_text(s)

# Continuity and Future24 use the same full definition verifier before advancing their markers.
replace_once('includes/class-wca-continuity-secure.php',
"\t\tforeach ( $definitions as $sql ) { dbDelta( $sql ); }",
"\t\tforeach ( $definitions as $sql ) { dbDelta( $sql ); $verified = WCA_Schema::verify_definition_sql( $sql ); if ( is_wp_error( $verified ) ) { throw new RuntimeException( $verified->get_error_message() ); } }",
'continuity definition verification')
replace_once('includes/class-wca-future24.php',
"\t\tdbDelta( $sql );\n\t\t$exists = $wpdb->get_var",
"\t\tdbDelta( $sql );\n\t\t$verified = WCA_Schema::verify_definition_sql( $sql );\n\t\tif ( is_wp_error( $verified ) ) { throw new RuntimeException( $verified->get_error_message() ); }\n\t\t$exists = $wpdb->get_var",
'future24 definition verification')

# R9-B: contain runtime migration failures before any module hooks are registered.
p=Path('worldwide-clinic.php'); s=p.read_text()
old="""\tSWC_Activator::maybe_upgrade();
\tWCA_Plugin::boot();
"""
new="""\ttry {
\t\t$legacy_upgrade = SWC_Activator::maybe_upgrade();
\t\tif ( is_wp_error( $legacy_upgrade ) ) { throw new RuntimeException( $legacy_upgrade->get_error_message() ); }
\t\tWCA_Continuity::maybe_upgrade();
\t\tWCA_Future24::maybe_upgrade();
\t} catch ( Throwable $wca_migration_error ) {
\t\tWCA_Observability::log( 'error', 'runtime_migration_failed', array( 'message' => sanitize_text_field( $wca_migration_error->getMessage() ) ) );
\t\t$failure = array( 'status' => 'failed', 'failed_at' => current_time( 'mysql', true ), 'message' => sanitize_text_field( $wca_migration_error->getMessage() ), 'runtime_version' => WCA_VERSION );
\t\tSWC_Helpers::update_option_strict( 'wca_runtime_migration_failure', $failure, 'wca_runtime_migration_failure_write' );
\t\tadd_action( 'admin_notices', static function () use ( $wca_migration_error ) { if ( current_user_can( 'activate_plugins' ) ) { echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Worldwide Clinic migration paused:', 'worldwide-clinic-appointments' ) . '</strong> ' . esc_html( $wca_migration_error->getMessage() ) . '</p></div>'; } } );
\t\treturn;
\t}
\tSWC_Helpers::delete_option_strict( 'wca_runtime_migration_failure', 'wca_runtime_migration_failure_clear' );
\tWCA_Plugin::boot();
"""
if s.count(old)!=1: raise SystemExit(f'runtime preflight block {s.count(old)}')
s=s.replace(old,new,1); p.write_text(s)

# R9-C: migration rollback failure must surface uncertain state.
p=Path('includes/class-swc-activator.php'); s=p.read_text()
old="""\t\t} catch ( Throwable $e ) {
\t\t\t$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\t\twp_cache_delete( absint( $id ), 'post_meta' );
\t\t\tthrow $e;
\t\t}
"""
new="""\t\t} catch ( Throwable $e ) {
\t\t\t$rolled_back = $wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
\t\t\twp_cache_delete( absint( $id ), 'post_meta' );
\t\t\tif ( false === $rolled_back ) { throw new RuntimeException( 'File 08 legacy migration failed and rollback could not be verified; storage state is uncertain.', 0, $e ); }
\t\t\tthrow $e;
\t\t}
"""
if s.count(old)!=1: raise SystemExit(f'migration rollback block {s.count(old)}')
s=s.replace(old,new,1); p.write_text(s)

# R9-D: uninstall must remove every capability granted by the activator.
p=Path('uninstall.php'); s=p.read_text()
old="array( 'manage_worldwide_clinic', 'manage_wca_clinics', 'manage_wca_complaints', 'manage_wca_operations', 'read_swc_appointment', 'read_private_swc_appointments', 'edit_swc_appointments', 'edit_others_swc_appointments', 'publish_swc_appointments', 'delete_swc_appointments', 'delete_others_swc_appointments' )"
new="array( 'manage_worldwide_clinic', 'manage_wca_clinics', 'manage_wca_complaints', 'manage_wca_operations', 'edit_swc_appointment', 'read_swc_appointment', 'delete_swc_appointment', 'edit_swc_appointments', 'edit_others_swc_appointments', 'publish_swc_appointments', 'read_private_swc_appointments', 'delete_swc_appointments', 'delete_private_swc_appointments', 'delete_published_swc_appointments', 'delete_others_swc_appointments', 'edit_private_swc_appointments', 'edit_published_swc_appointments' )"
if s.count(old)!=1: raise SystemExit(f'uninstall capability block {s.count(old)}')
p.write_text(s.replace(old,new,1))

# Permanent R9 regression gates.
p=Path('tests/sixteenth-twenty-review-regressions.php'); s=p.read_text(); marker='if($fail){fwrite(STDERR,"T16 regression gate failed:\\n- ".implode("\\n- ",$fail)."\\n");exit(1);}'
add="""t16h('R9 canonical dbDelta definitions receive column-index verification','includes/class-wca-schema.php','verify_definition_sql( $sql )');
t16h('R9 schema verifier checks columns','includes/class-wca-schema.php','wca_schema_columns_missing');
t16h('R9 schema verifier checks indexes','includes/class-wca-schema.php','wca_schema_indexes_missing');
t16h('R9 continuity schema uses full verifier','includes/class-wca-continuity-secure.php','WCA_Schema::verify_definition_sql( $sql )');
t16h('R9 Future24 schema uses full verifier','includes/class-wca-future24.php','WCA_Schema::verify_definition_sql( $sql )');
t16h('R9 runtime migration failures are contained','worldwide-clinic.php','runtime_migration_failed');
t16h('R9 runtime failure state is durable','worldwide-clinic.php','wca_runtime_migration_failure');
t16h('R9 legacy migration rollback uncertainty explicit','includes/class-swc-activator.php','rollback could not be verified; storage state is uncertain');
t16h('R9 uninstall removes primitive edit capability','uninstall.php','edit_swc_appointment');
t16h('R9 uninstall removes private delete capability','uninstall.php','delete_private_swc_appointments');
"""
if marker not in s: raise SystemExit('T16 marker missing')
p.write_text(s.replace(marker,add+marker,1))
