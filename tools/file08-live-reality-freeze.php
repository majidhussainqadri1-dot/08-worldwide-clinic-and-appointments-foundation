<?php
/**
 * File08 V12.1 private read-only deployed-state freeze for a trusted WP-CLI operator.
 * No patient rows, options with secrets, logs, credentials, or file contents leave
 * the host. Does NOT assert that GitHub / staging equals production.
 *
 * Run: wp eval-file tools/file08-live-reality-freeze.php --path=/actual/site --allow-root
 * SECURITY: do not expose via REST or PHP web. Restrict file/output access.
 */
if ( PHP_SAPI !== 'cli' || ! defined( 'WP_CLI' ) || ! WP_CLI || ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "REFUSED: WordPress WP-CLI only.\n" );
    exit( 2 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $wpdb;
$rel = 'worldwide-clinic-appointments/worldwide-clinic.php';
$dir = WP_PLUGIN_DIR . '/worldwide-clinic-appointments';
$entry = WP_PLUGIN_DIR . '/' . $rel;
$installed = is_file( $entry ) && ! is_link( $entry );
$installed_plugins = get_plugins();
$dependency_slugs = array(
    'File00' => 'sabri-membership-core/sabri-membership-core.php',
    'File02' => 'sabri-authentication/sabri-authentication.php',
    'File03' => 'sabri-profiles-doctors/sabri-profiles-doctors.php',
    'File07' => 'doctors-directory/doctors-directory.php',
    'File09' => 'global-doctor-onboarding/global-doctor-onboarding.php',
    'File08' => $rel,
);
$deps = array();
foreach ( $dependency_slugs as $id => $slug ) {
    $entry_metadata = isset( $installed_plugins[ $slug ] ) ? $installed_plugins[ $slug ] : array();
    $deps[ $id ] = array(
        'installed' => ! empty( $entry_metadata ),
        'active' => is_plugin_active( $slug ),
        'version' => isset( $entry_metadata['Version'] ) ? (string) $entry_metadata['Version'] : null,
    );
}
$tree = array( 'complete' => false, 'sha256' => null, 'file_count' => 0,
    'failure' => $installed ? 'unscanned' : 'entry_missing' );
if ( $installed && is_dir( $dir ) && ! is_link( $dir ) ) {
    $files = array();
    $failure = null;
    $bytes = 0;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
        );
        foreach ( $it as $file ) {
            if ( $file->isLink() ) { $failure = 'symlink'; break; }
            if ( ! $file->isFile() ) { continue; }
            $name = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $dir ) + 1 ) );
            $bytes += $file->getSize();
            if ( count( $files ) >= 3000 || $bytes > 200 * 1024 * 1024 ) {
                $failure = 'bounded_inventory_limit'; break;
            }
            $hash = hash_file( 'sha256', $file->getPathname() );
            if ( ! is_string( $hash ) || 64 !== strlen( $hash ) ) {
                $failure = 'unreadable_file'; break;
            }
            $files[$name] = $file->getSize() . ':' . $hash;
        }
        if ( null === $failure ) {
            ksort( $files, SORT_STRING );
            $ctx = hash_init( 'sha256' );
            foreach ( $files as $name => $value ) { hash_update( $ctx, $name . "\0" . $value . "\n" ); }
            $tree['sha256'] = hash_final( $ctx );
            $tree['complete'] = true;
        }
    } catch ( Throwable $e ) { $failure = 'inventory_exception'; }
    $tree['failure'] = $failure;
    $tree['file_count'] = count( $files );
}
$options = array();
foreach ( array( 'wca_db_version', 'swc_db_version', 'swc_version',
    'wca_continuity_schema_version', 'wca_future24_schema_version' ) as $name ) {
    $value = get_option( $name, null );
    $options[$name] = is_scalar( $value ) && strlen( (string) $value ) < 80
        ? (string) $value : null;
}
$migration = get_option( 'wca_migration_state', false );
$migration_state = is_array( $migration ) && isset( $migration['status'] ) && is_scalar( $migration['status'] )
    ? sanitize_key( (string) $migration['status'] ) : 'unverified';
$schema = array();
if ( class_exists( 'WCA_Schema' ) ) {
    foreach ( WCA_Schema::tables() as $key => $table ) {
        $wpdb->last_error = '';
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
        $error = '' !== (string) $wpdb->last_error;
        $columns = null;
        if ( ! $error && $exists === $table ) {
            $quoted = '`' . str_replace( '`', '``', $table ) . '`';
            $wpdb->last_error = '';
            $columns = $wpdb->get_col( 'SHOW COLUMNS FROM ' . $quoted ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $error = '' !== (string) $wpdb->last_error || ! is_array( $columns );
        }
        $schema[$key] = array( 'exists' => $error ? null : $exists === $table,
            'columns' => $error ? null : $columns, 'metadata_read_failed' => $error );
    }
}
$report = array(
    'audit_contract' => 'sabri.file08.live-reality-freeze.v1',
    'collected_utc' => gmdate( 'c' ), 'read_only' => true,
    'patient_rows_collected' => false, 'secrets_collected' => false,
    'site_host' => (string) parse_url( home_url(), PHP_URL_HOST ),
    'wp_core_version' => get_bloginfo( 'version' ),
    'php_version' => PHP_VERSION, 'db_server_version' => $wpdb->db_version(),
    'installed_entry_sha256' => $installed ? hash_file( 'sha256', $entry ) : null,
    'loaded_runtime_version' => defined( 'WCA_VERSION' ) ? WCA_VERSION : null,
    'declared_wca_schema' => class_exists( 'WCA_Contracts' ) ? WCA_Contracts::SCHEMA_VERSION : null,
    'plugin_tree' => $tree, 'plugins' => $deps,
    'db_schema_options' => $options,
    'migration_state' => $migration_state,
    'runtime_migration_failure_marker' => (bool) get_option( 'wca_runtime_migration_failure', false ),
    'wca_tables' => $schema,
    'runtime_logs' => 'NOT_READ: securely inspect host logs, redact private data',
    'deployment_parity_confirmed' => false,
    'live_retested' => false,
);
$json = wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
if ( ! is_string( $json ) ) { fwrite( STDERR, "Failed to serialize File08 freeze.\n" ); exit( 3 ); }
echo $json, "\n";
