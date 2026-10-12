<?php
/** T30: current cross-file runtime handshake, non-invented File 00 UUID and honest readiness. */
require_once __DIR__ . '/bootstrap.php';

class SMC_Contracts { public static function assertions( $id ) { return array(); } }
class SMC_CF01_Contract {
    public static $ref = '01234567-89AB-4CDE-8F01-23456789ABCD';
    public static function ensure_subject_uuid( $id ) {
        if ( 22 !== $id ) { return ''; }
        if ( 'throw' === self::$ref ) { throw new RuntimeException( 'provider unavailable' ); }
        return self::$ref;
    }
}
class SPD_Helpers { public static function canonical_profile_url( $id ) { return ''; } }
class SDD_Helpers { public static function public_clinic( $id ) { return array(); } }
class SN_Context_Adapters { public static function attach_context( $request ) { return true; } }

function sun_register_notification_producer( $key, $args ) { return true; }
function sun_ingest_domain_event( $event ) { return array( 'status' => 'processed' ); }
function gdo_user_is_verified( $id ) { return true; }
function has_filter( $name ) { return false; }

require_once dirname( __DIR__ ) . '/includes/class-wca-authorization.php';
require_once dirname( __DIR__ ) . '/includes/class-wca-compatibility.php';

$valid = WCA_Authorization::subject_uuid( 22 );
wca_test_assert( '01234567-89ab-4cde-8f01-23456789abcd' === $valid, 'File 00 owner-issued UUID is used in normalized form.' );
wca_test_assert( '' === WCA_Authorization::subject_uuid( 0 ), 'An absent actor cannot acquire a subject UUID.' );
SMC_CF01_Contract::$ref = 'native-id-22';
wca_test_assert( '' === WCA_Authorization::subject_uuid( 22 ), 'Native ID-shaped subject references are rejected.' );
SMC_CF01_Contract::$ref = new WP_Error( 'provider_failure' );
wca_test_assert( '' === WCA_Authorization::subject_uuid( 22 ), 'Provider WP_Error is fail-closed.' );
SMC_CF01_Contract::$ref = 'throw';
wca_test_assert( '' === WCA_Authorization::subject_uuid( 22 ), 'Provider exceptions are fail-closed.' );

$health = WCA_Compatibility::dependency_health();
foreach ( array( 'file00','file03','file07','file09','file17','file19' ) as $owner ) {
    wca_test_assert( true === $health[ $owner ]['ready'], 'Current owner contract is detected: ' . $owner );
}
wca_test_assert( false === $health['file24']['ready'], 'Own outbox observers do not fabricate File 24 readiness.' );
wca_test_assert( false === $health['cf02']['ready'] && false === $health['cf03']['ready'], 'Absent cross-file case/finance owner filters do not fabricate readiness.' );

$auth = file_get_contents( dirname(__DIR__) . '/includes/class-wca-authorization.php' );
$compat = file_get_contents( dirname(__DIR__) . '/includes/class-wca-compatibility.php' );
wca_test_assert( strpos( $auth, 'wca_canonical_subject_unavailable' ) !== false, 'Protected actor claims fail closed if the File 00 UUID cannot be resolved.' );
wca_test_assert( strpos( $auth, "get_user_meta( \$user_id, '_smc_subject_uuid'") === false, 'Retired local subject metadata is not a canonical fallback.' );
wca_test_assert( strpos( $compat, "has_action( 'wca_outbox_event' )" ) === false, 'Module-internal observers cannot establish external File 17/24 readiness.' );
wca_test_assert( strpos( $compat, 'sn_notify_users' ) === false, 'Deprecated File 19 notification helper is not the readiness source.' );

echo "T30 live-companion source contract and canonical subject regressions: PASS\n";
