<?php
/**
 * File08 owner-stage source contract: do not claim live/staging acceptance.
 * An optional File14 observer must never change the committed appointment.
 */
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-service.php' );
$main = file_get_contents( dirname( __DIR__ ) . '/worldwide-clinic.php' );
$contracts = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-contracts.php' );
$checks = array(
    'candidate version' => strpos( $main, "Version: 1.2.16" ) !== false,
    'contract matches' => strpos( $contracts, "RUNTIME_VERSION                 = '1.2.16'" ) !== false,
    'owner event present' => strpos( $source, "do_action( 'wca_appointment_requested_committed_v1', \$result['public_ref'] )" ) !== false,
    'postcommit after owner request success' => strpos( $source, "if ( is_wp_error( \$result ) ) { return \$result; }" ) < strpos( $source, "do_action( 'wca_appointment_requested_committed_v1'" ),
    'no analytics mutation of owner outcome' => strpos( $source, "catch ( Throwable \$analytics_failure )" ) !== false,
    'opaque native reference' => strpos( $source, "wp_is_uuid( \$result['public_ref'] )" ) !== false,
    'no private attributes in action' => !preg_match( "/do_action\\( 'wca_appointment_requested_committed_v1'[^;]*(?:patient_id|reason|doctor_user_id|scheduled_at)/s", $source ),
);
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, 'File08/File14 owner event source contract failed: ' . $name . PHP_EOL ); exit( 1 ); }
}
echo "File08 postcommit owner-event source contract: PASS (real WordPress staging unverified)" . PHP_EOL;
