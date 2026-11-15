<?php
/**
 * File08 V12.3 F08-FR-002: REAL disposable WordPress/MariaDB 3-service enumeration.
 * Only runs on file08-ci.local; no patient/production data, no migration writes.
 */
if ( getenv( 'FILE08_CI_EPHEMERAL_DB' ) !== 'yes'
    || ! defined( 'ABSPATH' )
    || ! function_exists( 'home_url' )
    || parse_url( home_url(), PHP_URL_HOST ) !== 'file08-ci.local'
    || DB_NAME !== 'wordpress' ) {
    fwrite( STDERR, "REFUSED: this test runs only on disposable CI WordPress.\n" );
    exit( 4 );
}
global $wpdb;
$tables = WCA_Schema::tables();
$check = static function ( $ok, $msg ) {
    if ( ! $ok ) { throw new RuntimeException( $msg . ' :: ' . (string) $GLOBALS['wpdb']->last_error ); }
};
$clinic_id = 0;
$service_ids = array();
$failed = '';
$now = gmdate( 'Y-m-d H:i:s' );
try {
    $ok = $wpdb->insert( $tables['clinics'], array(
        'public_ref' => wp_generate_uuid4(),
        'slug' => 'file08-v123-ci-' . wp_generate_password( 12, false ),
        'owner_user_id' => 1,
        'name' => 'File08 disposable public pricing test',
        'summary' => 'Isolated data, not a real clinic',
        'languages_json' => '["en-US"]',
        'contacts_json' => '{}',
        'policies_json' => '{"platform_commission_percent":0}',
        'status' => 'draft',
        'version' => 1,
        'created_at' => $now,
        'updated_at' => $now,
    ) );
    $check( 1 === $ok, 'could not seed disposable clinic' );
    $clinic_id = (int) $wpdb->insert_id;
    for ( $i = 0; $i < 3; $i++ ) {
        $ok = $wpdb->insert( $tables['services'], array(
            'public_ref' => wp_generate_uuid4(),
            'clinic_id' => $clinic_id,
            'branch_id' => 0,
            'doctor_user_id' => 1,
            'name' => 'CI Service ' . ( $i + 1 ),
            'consultation_type' => $i === 0 ? 'home_visit' : 'online',
            'duration_minutes' => 30,
            'currency' => 'PKR',
            'fee_minor' => ( $i + 1 ) * 2500,
            'fee_max_minor' => $i === 0 ? 5000 : 0,
            'tax_policy' => 'tax is defined',
            'refund_policy' => 'refund is defined',
            'cancellation_policy' => 'cancellation is defined',
            'platform_commission_bps' => 0,
            'status' => 'active',
            'version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ) );
        $check( 1 === $ok, 'could not seed disposable service ' . $i );
        $service_ids[] = (int) $wpdb->insert_id;
    }
    $wpdb->last_error = '';
    $actual = WCA_Repository::list_services( $clinic_id, false );
    $check( is_array( $actual ) && count( $actual ) === 3, 'actual repository returned fewer than 3 active services' );
    $check( '' === (string) $wpdb->last_error, 'real service query returned a database error' );
    $names = array_column( $actual, 'name' );
    sort( $names );
    $check( $names === array( 'CI Service 1', 'CI Service 2', 'CI Service 3' ),
        'enumeration omitted or mixed service names' );
    $check( array_sum( array_map( static function ( $row ) { return (int) $row['platform_commission_bps']; }, $actual ) ) === 0,
        'disposable services must preserve zero platform commission' );
} catch ( Throwable $e ) {
    $failed = $e->getMessage();
} finally {
    foreach ( $service_ids as $id ) { $wpdb->delete( $tables['services'], array( 'id' => $id ), array( '%d' ) ); }
    if ( $clinic_id ) { $wpdb->delete( $tables['clinics'], array( 'id' => $clinic_id ), array( '%d' ) ); }
}
if ( '' !== $failed ) { fwrite( STDERR, "FAIL: disposable WordPress/MariaDB File08 three-service test: {$failed}\n" ); exit( 1 ); }
echo "PASS: real disposable WordPress/MariaDB File08 three-service query, fee/commission columns and cleanup.\n";
