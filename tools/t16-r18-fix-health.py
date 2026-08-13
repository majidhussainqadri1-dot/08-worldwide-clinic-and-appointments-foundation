from pathlib import Path

# Continuity: make schema-current a boolean health invariant.
p=Path('includes/class-wca-continuity-secure.php'); s=p.read_text()
old="\t\t\t'schema_version' => (string) get_option( self::SCHEMA_OPTION, '' ),\n\t\t\t'tables'         => array(),"
new="\t\t\t'schema_version' => (string) get_option( self::SCHEMA_OPTION, '' ),\n\t\t\t'schema_current' => self::SCHEMA_VERSION === (string) get_option( self::SCHEMA_OPTION, '' ),\n\t\t\t'tables'         => array(),"
if s.count(old)!=1: raise SystemExit('continuity health anchor mismatch')
p.write_text(s.replace(old,new,1))

# Future24: add explicit schema/table health.
p=Path('includes/class-wca-future24.php'); s=p.read_text(); anchor="\tpublic static function register_assets() {"
if s.count(anchor)!=1: raise SystemExit('future24 health anchor mismatch')
health="""\t/** @return array<string,mixed> */
\tpublic static function health() {
\t\tglobal $wpdb;
\t\t$table = self::tables()['records'];
\t\t$wpdb->last_error = '';
\t\t$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
\t\t$read_ok = '' === (string) $wpdb->last_error;
\t\treturn array(
\t\t\t'schema_version' => (string) get_option( self::SCHEMA_OPTION, '' ),
\t\t\t'schema_current' => self::SCHEMA_VERSION === (string) get_option( self::SCHEMA_OPTION, '' ),
\t\t\t'table_records'  => $read_ok && $exists === $table,
\t\t\t'db_read_ok'     => $read_ok,
\t\t);
\t}

"""
p.write_text(s.replace(anchor,health+anchor,1))

# Overall health must include owned subschemas and runtime migration-failure state.
p=Path('includes/class-wca-observability.php'); s=p.read_text()
start=s.index("\t/** @return array<string,mixed> */\n\tpublic static function health() {")
end=s.index("\n\tprivate static function all_true", start)
new="""\t/** @return array<string,mixed> */
\tpublic static function health() {
\t\t$runtime_failure = get_option( 'wca_runtime_migration_failure', false );
\t\t$checks = array(
\t\t\t'runtime_version' => defined( 'WCA_VERSION' ) ? WCA_VERSION : '',
\t\t\t'schema'          => class_exists( 'WCA_Schema' ) ? WCA_Schema::health() : array( 'available' => false ),
\t\t\t'continuity'      => class_exists( 'WCA_Continuity' ) ? WCA_Continuity::health() : array( 'available' => false ),
\t\t\t'future24'        => class_exists( 'WCA_Future24' ) ? WCA_Future24::health() : array( 'available' => false ),
\t\t\t'migration'       => array( 'runtime_failure_absent' => false === $runtime_failure || empty( $runtime_failure ) ),
\t\t\t'dependencies'    => class_exists( 'SWC_Activator' ) ? SWC_Activator::dependencies_ready() : false,
\t\t\t'legacy_checks'   => class_exists( 'SWC_Activator' ) ? SWC_Activator::system_checks() : array(),
\t\t\t'cron'            => array(
\t\t\t\t'outbox'      => (bool) wp_next_scheduled( WCA_Outbox::CRON_HOOK ),
\t\t\t\t'maintenance' => (bool) wp_next_scheduled( WCA_Outbox::MAINTENANCE_HOOK ),
\t\t\t),
\t\t\t'circuit_breakers' => (array) get_option( 'wca_circuit_breakers', array() ),
\t\t\t'trace_id'         => self::trace_id(),
\t\t\t'generated_at_utc' => gmdate( 'c' ),
\t\t);
\t\t$continuity_ok = isset( $checks['continuity']['status'] ) && 'ok' === $checks['continuity']['status'] && ! empty( $checks['continuity']['schema_current'] );
\t\t$checks['ok'] = self::all_true( $checks['schema'] ) && $continuity_ok && self::all_true( $checks['future24'] ) && self::all_true( $checks['migration'] ) && (bool) $checks['dependencies'] && self::all_true( $checks['legacy_checks'] ) && self::all_true( $checks['cron'] );
\t\treturn $checks;
\t}
"""
p.write_text(s[:start]+new+s[end:])
