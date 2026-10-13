<?php
/** File 08 ↔ File 19: test the actual 3.x PHP producer/event ingestion surface. */
require_once __DIR__ . '/bootstrap.php';

define( 'WCA_VERSION', '1.2.15' );
class WCA_Observability {
    public static $failures = 0;
    public static function circuit_open( $name ) { return false; }
    public static function circuit_failure( $name, $reason ) { ++self::$failures; }
    public static function circuit_success( $name ) {}
}
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sun_register_notification_producer( $key, array $config ) {
    $GLOBALS['file19_producer'] = array( $key, $config );
    return true;
}
function sun_ingest_domain_event( array $event ) {
    $GLOBALS['file19_received'][] = $event;
    return ! empty( $GLOBALS['file19_force_failure'] ) ? false : array( 'status' => 'processed', 'event_public_id' => 'opaque-event' );
}
require_once dirname( __DIR__ ) . '/includes/class-wca-outbox.php';

$method = new ReflectionMethod( 'WCA_Outbox', 'dispatch_notification' );
$method->setAccessible( true );
$payload = array( 'recipients' => array( 7, 8, 7 ), 'event' => 'appointment_requested', 'appointment_ref' => '01234567-89ab-4cde-8f01-23456789abcd' );
$result = $method->invoke( null, $payload, 'trace-008', 'outbox-0042' );
wca_test_assert( true === $result, 'File 19 confirms the canonical notification intake.' );
wca_test_assert( 'file08' === $GLOBALS['file19_producer'][0] && 'File 08' === $GLOBALS['file19_producer'][1]['owner'], 'Producer is registered under File 08 ownership.' );
$event = $GLOBALS['file19_received'][0];
wca_test_assert( 'Clinic.AppointmentUpdated' === $event['event_type'] && '1.0.0' === $event['schema_version'], 'Versioned File 19 event is valid.' );
wca_test_assert( array( 7, 8 ) === $event['recipients'], 'Explicit recipients are deduplicated.' );
wca_test_assert( 'wca-notification-outbox-0042' === $event['event_id'], 'Source outbox identifier is stable for idempotent retries.' );
wca_test_assert( ! array_key_exists( 'appointment_ref', $event['data'] ) && ! array_key_exists( 'reason', $event['data'] ), 'Event template data contains no patient details or appointment identifiers.' );

$method->invoke( null, $payload, 'trace-008', 'outbox-0042' );
wca_test_assert( $event['event_id'] === $GLOBALS['file19_received'][1]['event_id'], 'Retry preserves the exact producer-scoped event ID.' );
$GLOBALS['file19_force_failure'] = true;
$failed = $method->invoke( null, $payload, 'trace-009', 'outbox-0099' );
wca_test_assert( is_wp_error( $failed ) && WCA_Observability::$failures > 0, 'Provider failure cannot be treated as delivery success.' );

$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-outbox.php' );
wca_test_assert( false !== strpos( $source, 'sun_ingest_domain_event' ) && false !== strpos( $source, 'sun_register_notification_producer' ), 'File 08 binds to the current File 19 interface.' );
wca_test_assert( false === strpos( $source, 'sn_notify_users' ) && false === strpos( $source, 'wp_mail(' ), 'Legacy and direct-mail bypasses are absent.' );
wca_test_assert( false !== strpos( $source, "'wca_file19_contract_unavailable'" ), 'Missing File 19 API fails closed for safe retry.' );
echo "File 08 File 19 notification contract regressions: PASS\n";
