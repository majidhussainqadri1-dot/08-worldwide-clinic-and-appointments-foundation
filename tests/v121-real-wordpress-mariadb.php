<?php
/**
 * V12.1 disposable, ACTUAL WordPress + MariaDB + pinned File00/02/03/07/09/08.
 * This is NOT a Hostinger / staging / clinical workflow acceptance.
 * Strict target guard prevents use on any production or non-disposable site.
 */
if ( getenv( 'FILE08_CI_EPHEMERAL_DB' ) !== 'yes'
    || ! defined( 'ABSPATH' )
    || ! function_exists( 'home_url' )
    || parse_url( home_url(), PHP_URL_HOST ) !== 'file08-ci.local'
    || DB_NAME !== 'wordpress' ) {
    fwrite( STDERR, "REFUSED: only disposable file08-ci.local/wordpress with explicit CI marker\n" );
    exit( 4 );
}
require_once ABSPATH . 'wp-admin/includes/plugin.php';
global $wpdb;
$checks = 0;
$check = static function ( $ok, $message ) use ( &$checks ) {
    $checks++;
    if ( ! $ok ) {
        fwrite( STDERR, "File08 native WordPress/MariaDB FAIL {$checks}: {$message}\n" );
        exit( 1 );
    }
};
$plugins = array(
    'sabri-membership-core/sabri-membership-core.php',
    'sabri-authentication/sabri-authentication.php',
    'sabri-profiles-doctors/sabri-profiles-doctors.php',
    'doctors-directory/doctors-directory.php',
    'global-doctor-onboarding/global-doctor-onboarding.php',
    'worldwide-clinic-appointments/worldwide-clinic.php',
);
foreach ( $plugins as $plugin ) {
    $check( is_plugin_active( $plugin ), 'actual pinned plugin active: ' . $plugin );
}
$check( class_exists( 'WCA_Future24' ) && class_exists( 'SWC_Activator' ),
    'actual File08 plugin and private native query owner loaded' );
$check( SWC_Activator::dependencies_ready(), 'real File00/02/03/07/09 File08 dependencies ready' );
$check( ! get_option( 'wca_runtime_migration_failure', false ),
    'no current File08 runtime migration failure marker' );
$check( class_exists( 'WCA_Schema' ) && class_exists( 'WCA_Contracts' ),
    'real WCA schema/plan contract loaded' );
$tables = WCA_Schema::tables();
foreach ( array( 'clinics', 'branches', 'services', 'availability', 'outbox' ) as $name ) {
    $check( isset( $tables[ $name ] ), 'canonical table declared: ' . $name );
    $table = $tables[ $name ];
    $wpdb->last_error = '';
    $found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
    $check( $found === $table && $wpdb->last_error === '',
        'real MariaDB table exists: ' . $name );
}
$check( isset( WCA_Contracts::functional_requirements()['F08-FR-005'] ),
    'canonical owner contract includes booking demand requirement' );

$clinic_id = 777;
$when = '2026-11-20 12:00:00';
$seeded = array();
for ( $i = 0; $i < 201; $i++ ) {
    $ok = $wpdb->insert( $wpdb->posts, array(
        'post_author' => 1,
        'post_date' => '2026-11-20 12:00:00',
        'post_date_gmt' => '2026-11-20 12:00:00',
        'post_title' => 'CI ephemeral appointment',
        'post_name' => 'file08-ci-' . $i,
        'post_status' => 'private',
        'post_type' => SWC_Helpers::TYPE,
        'post_modified' => '2026-11-20 12:00:00',
        'post_modified_gmt' => '2026-11-20 12:00:00',
    ), array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ) );
    if ( 1 !== $ok ) { break; }
    $id = (int) $wpdb->insert_id;
    $seeded[] = $id;
    $a = $wpdb->insert( $wpdb->postmeta,
        array( 'post_id' => $id, 'meta_key' => '_swc_clinic_id', 'meta_value' => $clinic_id ),
        array( '%d', '%s', '%d' ) );
    $b = $wpdb->insert( $wpdb->postmeta,
        array( 'post_id' => $id, 'meta_key' => '_swc_preferred_at_utc', 'meta_value' => $when ),
        array( '%d', '%s', '%s' ) );
    if ( 1 !== $a || 1 !== $b ) { break; }
}
$check( count( $seeded ) === 201, '201 private appointment rows persisted in disposable database' );
$method = new ReflectionMethod( 'WCA_Future24', 'clinic_appointments_between_all' );
$method->setAccessible( true );
$args = array( $clinic_id, '2026-11-20 00:00:00', '2026-11-20 23:59:59' );
$found = $method->invokeArgs( null, $args );
$check( is_array( $found ) && count( $found ) === 201
    && count( array_diff( $seeded, $found ) ) === 0,
    'native WordPress WP_Query returns all 201 matching rows across two pages' );
$injected = 0;
$fault = static function ( $sql, $query ) use ( &$injected ) {
    if ( $query instanceof WP_Query
        && (int) $query->get( 'paged' ) === 2
        && $query->get( 'post_type' ) === SWC_Helpers::TYPE ) {
        $injected++;
        return 'SELECT * FROM ' . $GLOBALS['wpdb']->prefix . 'file08_v121_missing_table';
    }
    return $sql;
};
add_filter( 'posts_request', $fault, 100, 2 );
$wpdb->last_error = '';
$bad = $method->invokeArgs( null, $args );
remove_filter( 'posts_request', $fault, 100 );
$check( $injected === 1, 'SQL outage exercised on actual WP_Query page 2' );
$check( is_wp_error( $bad )
    && $bad->get_error_code() === 'wca_disruption_appointment_page_read_failed',
    'real SQL failure returns typed error, never incomplete success' );
$wpdb->last_error = '';
$again = $method->invokeArgs( null, $args );
$check( is_array( $again ) && count( $again ) === 201,
    'valid query recovers after removal of disposable injected fault' );
echo "PASS: {$checks} real WordPress/MariaDB integration checks; isolated CI only, no real patient data.\n";
