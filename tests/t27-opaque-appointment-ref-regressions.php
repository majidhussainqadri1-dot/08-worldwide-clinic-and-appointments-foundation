<?php
/**
 * File 08: protect all appointment exports/events from native-ID fallback.
 *
 * This isolated contract test exercises the missing/corrupt/valid reference
 * paths without a live WordPress installation.
 */
require_once __DIR__ . '/bootstrap.php';

class SWC_Helpers {
    public static $reference = '';
    public static function meta( $id, $key, $default = '' ) {
        return 'public_ref' === $key ? self::$reference : $default;
    }
    public static function status( $id ) { return 'confirmed'; }
}
class WCA_Contracts {
    const FILE17_CONTEXT_CONTRACT_VERSION = '1.0.0';
}
class WCA_Authorization {
    public static function subject_uuid( $id ) { return 'subject-' . absint( $id ); }
}
class WCA_Observability {
    public static function trace_id() { return 'unit-test'; }
}
function get_post_field( $field, $id ) { return 12; }

require_once dirname( __DIR__ ) . '/includes/class-wca-service.php';

SWC_Helpers::$reference = '';
$missing = WCA_Service::file17_context_payload( 42 );
wca_test_assert( is_wp_error( $missing ), 'Missing canonical reference is rejected before File 17 event construction.' );

SWC_Helpers::$reference = 'appointment-42';
$native = WCA_Service::file17_context_payload( 42 );
wca_test_assert( is_wp_error( $native ), 'Native numeric appointment reference is not a valid public reference.' );

SWC_Helpers::$reference = '01234567-89AB-4CDE-8F01-23456789ABCD';
$valid = WCA_Service::file17_context_payload( 42 );
wca_test_assert( is_array( $valid ) && '01234567-89ab-4cde-8f01-23456789abcd' === $valid['appointment_ref'], 'Valid opaque reference survives in normalized form.' );
wca_test_assert( isset( $valid['clinical_authority'] ) && false === $valid['clinical_authority'], 'File 17 scheduling context never grants clinical authority.' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-service.php' );
wca_test_assert( is_string( $source ) && false === strpos( $source, "'appointment-' . \$appointment_id" ), 'No native numeric ID fallback survives in service event/calendar paths.' );
wca_test_assert( false !== strpos( $source, 'if ( is_wp_error( $file17_context ) ) { return $file17_context; }' ), 'Failed File 17 payload halts mutations before enqueue.' );
wca_test_assert( false !== strpos( $source, 'if ( is_wp_error( $public_ref ) ) { return $public_ref; }' ), 'Calendar and state-transition exports reject invalid references.' );

echo "File 08 opaque appointment reference regressions: PASS\n";
