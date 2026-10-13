<?php
/** T30: external File17/File26/CF02/CF03 events require durable owner acknowledgement. */
require_once __DIR__ . '/bootstrap.php';
class WCA_Contracts { const FILE19_EVENT_CONTRACT_VERSION = '1.0.0'; }
function sanitize_text_field( $text ) { return trim( (string) $text ); }
function do_action( $hook, $arg ) { $GLOBALS['t30_fired'][] = $hook; }
function apply_filters( $hook, $default, $envelope ) {
    $GLOBALS['t30_envelopes'][] = array( $hook, $envelope );
    return isset( $GLOBALS['t30_ack'] ) && array_key_exists( $hook, $GLOBALS['t30_ack'] ) ? $GLOBALS['t30_ack'][ $hook ] : $default;
}
require_once dirname(__DIR__) . '/includes/class-wca-outbox.php';
$method = new ReflectionMethod( 'WCA_Outbox', 'dispatch' );
$method->setAccessible( true );
$cases = array(
    'File17.AppointmentContextChanged.v1' => 'wca_file17_dispatch_result',
    'File17.VirtualRoomRequested.v1' => 'wca_file17_dispatch_result',
    'File26.SearchProjectionChanged.v1' => 'wca_file26_dispatch_result',
    'CF02.AppointmentComplaintOpened.v1' => 'wca_cf02_dispatch_result',
    'CF03.AppointmentFeePolicyReviewRequested.v1' => 'wca_cf03_dispatch_result',
);
foreach ($cases as $topic => $hook) {
    $GLOBALS['t30_ack'] = array();
    $missing = $method->invoke( null, 'durable-message-1', $topic, 'opaque-ref', array( 'appointment_ref' => 'opaque-ref' ), 'trace1' );
    wca_test_assert( is_wp_error( $missing ) && $missing->code === 'wca_outbox_owner_not_acknowledged', 'Without owner receipt the ' . $topic . ' event stays retryable.' );
    $GLOBALS['t30_ack'][ $hook ] = false;
    $denied = $method->invoke( null, 'durable-message-1', $topic, 'opaque-ref', array(), 'trace1' );
    wca_test_assert( is_wp_error( $denied ), 'Explicit consumer denial is not accepted as delivery.' );
    $GLOBALS['t30_ack'][ $hook ] = true;
    $accepted = $method->invoke( null, 'durable-message-1', $topic, 'opaque-ref', array(), 'trace1' );
    wca_test_assert( true === $accepted, 'Owner durable acknowledgement permits completion: ' . $topic );
}
$source = file_get_contents( dirname(__DIR__) . '/includes/class-wca-outbox.php' );
wca_test_assert( false !== strpos( $source, 'is_wp_error( $result ) || true !== $result' ), 'Worker fences every failed/false dispatch before finalizing outbox.' );
wca_test_assert( false === strpos( $source, "apply_filters( 'wca_cf03_dispatch_result', true" ), 'Finance event default success is forbidden.' );
wca_test_assert( false === strpos( $source, "apply_filters( 'wca_cf02_dispatch_result', true" ), 'Complaint event default success is forbidden.' );
echo "T30 external owner durable-ack regressions: PASS\n";
