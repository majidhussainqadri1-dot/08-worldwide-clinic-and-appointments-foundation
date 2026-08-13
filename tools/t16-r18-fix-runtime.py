from pathlib import Path

def one(path, old, new):
    p=Path(path); s=p.read_text()
    if s.count(old)!=1: raise SystemExit(f'{path}: anchor count {s.count(old)} for {old[:100]!r}')
    p.write_text(s.replace(old,new,1))

# Unify activation under the rollback-aware activator.
one('worldwide-clinic.php', "register_activation_hook( WCA_FILE, array( 'SWC_Activator', 'activate' ) );\nregister_activation_hook( WCA_FILE, array( 'WCA_Continuity', 'activate' ) );\nregister_activation_hook( WCA_FILE, array( 'WCA_Future24', 'activate' ) );", "register_activation_hook( WCA_FILE, array( 'SWC_Activator', 'activate' ) );")

one('worldwide-clinic.php', "\t\tSWC_Helpers::update_option_strict( 'wca_runtime_migration_failure', $failure, 'wca_runtime_migration_failure_write' );", """\t\t$failure_written = SWC_Helpers::update_option_strict( 'wca_runtime_migration_failure', $failure, 'wca_runtime_migration_failure_write' );
\t\tif ( is_wp_error( $failure_written ) ) {
\t\t\tWCA_Observability::log( 'critical', 'runtime_migration_failure_state_persistence_failed', array( 'code' => $failure_written->get_error_code() ) );
\t\t}""")
one('worldwide-clinic.php', "\tSWC_Helpers::delete_option_strict( 'wca_runtime_migration_failure', 'wca_runtime_migration_failure_clear' );\n\tWCA_Plugin::boot();", """\t$failure_cleared = SWC_Helpers::delete_option_strict( 'wca_runtime_migration_failure', 'wca_runtime_migration_failure_clear' );
\tif ( is_wp_error( $failure_cleared ) ) {
\t\tWCA_Observability::log( 'critical', 'runtime_migration_failure_state_clear_failed', array( 'code' => $failure_cleared->get_error_code() ) );
\t\tadd_action( 'admin_notices', static function () { if ( current_user_can( 'activate_plugins' ) ) { echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Worldwide Clinic migration state is inconsistent:', 'worldwide-clinic-appointments' ) . '</strong> ' . esc_html__( 'The previous failure marker could not be cleared safely; File 08 remains paused.', 'worldwide-clinic-appointments' ) . '</p></div>'; } } );
\t\treturn;
\t}
\tWCA_Plugin::boot();""")

# Include every owned schema in the rollback-aware activation try/catch.
one('includes/class-swc-activator.php', "\t\t\tWCA_Schema::install();\n\t\t\tWCA_Routes::register();", """\t\t\tWCA_Schema::install();
\t\t\tWCA_Continuity::install_schema();
\t\t\tWCA_Future24::install_schema();
\t\t\tWCA_Routes::register();""")
one('includes/class-swc-activator.php', """\t\t} catch ( Throwable $e ) {
\t\t\tself::rollback_activation();
\t\t\tdeactivate_plugins( plugin_basename( SWC_FILE ) );
\t\t\twp_die(
\t\t\t\tesc_html( sprintf( __( 'Worldwide Clinic activation was rolled back: %s', 'worldwide-clinic-appointments' ), $e->getMessage() ) ),
\t\t\t\t'',
\t\t\t\tarray( 'back_link' => true )
\t\t\t);
\t\t}""", """\t\t} catch ( Throwable $e ) {
\t\t\t$rollback = self::rollback_activation();
\t\t\tdeactivate_plugins( plugin_basename( SWC_FILE ) );
\t\t\t$message = is_wp_error( $rollback )
\t\t\t\t? sprintf( __( 'Worldwide Clinic activation failed and rollback is incomplete (%1$s): %2$s', 'worldwide-clinic-appointments' ), $rollback->get_error_code(), $e->getMessage() )
\t\t\t\t: sprintf( __( 'Worldwide Clinic activation was rolled back: %s', 'worldwide-clinic-appointments' ), $e->getMessage() );
\t\t\twp_die( esc_html( $message ), '', array( 'back_link' => true ) );
\t\t}""")
one('includes/class-swc-activator.php', """\tpublic static function deactivate() {
\t\tWCA_Outbox::unschedule();
\t\tself::remove_capabilities();""", """\tpublic static function deactivate() {
\t\tWCA_Outbox::unschedule();
\t\twp_clear_scheduled_hook( 'wca_daily_health_snapshot' );
\t\tself::remove_capabilities();""")
one('includes/class-swc-activator.php', """\tprivate static function rollback_activation() {
\t\t$rolled_back = self::rollback_pages();
\t\tif ( is_wp_error( $rolled_back ) ) { WCA_Observability::log( 'error', 'activation_rollback_incomplete', array( 'code' => $rolled_back->get_error_code() ) ); }
\t\tself::remove_capabilities();
\t\treturn $rolled_back;
\t}""", """\tprivate static function rollback_activation() {
\t\tWCA_Outbox::unschedule();
\t\twp_clear_scheduled_hook( 'wca_daily_health_snapshot' );
\t\t$rolled_back = self::rollback_pages();
\t\tif ( is_wp_error( $rolled_back ) ) { WCA_Observability::log( 'error', 'activation_rollback_incomplete', array( 'code' => $rolled_back->get_error_code() ) ); }
\t\tself::remove_capabilities();
\t\treturn $rolled_back;
\t}""")
