<?php
/**
 * File08 P08: READ-ONLY post-migration File00 native compatibility probe.
 *
 * This does NOT suppress initial File00 pre-migration warnings, fix its root
 * cause or establish deployed parity. It distinguishes later DB health in
 * disposable WordPress from an initial ordering problem.
 */
if ( getenv( 'FILE08_CI_EPHEMERAL_DB' ) !== 'yes' ||
    ! defined( 'ABSPATH' ) || ! function_exists( 'home_url' ) ||
    parse_url( home_url(), PHP_URL_HOST ) !== 'file08-ci.local' ||
    DB_NAME !== 'wordpress' ) {
    fwrite( STDERR, "REFUSED: only a private disposable File08 CI database.\n" );
    exit( 4 );
}
global $wpdb;
$checks = 0;
$check = static function ( $ok, $message ) use ( &$checks ) {
    $checks++;
    if ( ! $ok ) {
        fwrite( STDERR, "File08 File00 post-migration read-only FAIL {$checks}: {$message} :: " . $GLOBALS['wpdb']->last_error . "\n" );
        exit( 1 );
    }
};
$check( defined( 'SMC_DB_VERSION' ) &&
    (string) get_option( 'smc_db_version', '' ) === SMC_DB_VERSION,
    'File00 real migration marker matches loaded schema version' );
$role = $wpdb->prefix . 'smc_role_grants';
$app = $wpdb->prefix . 'smc_applications';
foreach ( array( $role, $app ) as $table ) {
    $wpdb->last_error = '';
    $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    $check( $exists === $table && '' === (string) $wpdb->last_error,
        'previously missing File00 table exists after migration: ' . $table );
}
wp_set_current_user( 1 );
$wpdb->last_error = '';
$granted = current_user_can( 'read' );
$check( is_bool( $granted ) && '' === (string) $wpdb->last_error,
    'real WordPress File00 capability resolver performs a diagnostic-clean DB read' );
if ( function_exists( 'smc_membership_state' ) ) {
    $wpdb->last_error = '';
    smc_membership_state( 1 );
    $check( '' === (string) $wpdb->last_error,
        'real File00 membership read has no DB error after migration' );
}
echo "PASS: {$checks} File00 post-migration database/capability probes on disposable WordPress.\n";
