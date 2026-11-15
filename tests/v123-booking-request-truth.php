<?php
/**
 * V12.3 UX-04 / F08-FR-005: booking request must never masquerade as confirmation.
 * Source-level content assertions, not a WordPress staging or live acceptance.
 */
$root = dirname( __DIR__ );
$front = file_get_contents( $root . '/includes/class-wca-frontend.php' );
$js = file_get_contents( $root . '/assets/js/clinic.js' );
$checks = array(
    'frontend source readable' => is_string( $front ),
    'booking client source readable' => is_string( $js ),
    'appointment form title is truthful' => false !== strpos( $front, "'Request an appointment with %s'" ),
    'explicit pending-request explanation' => false !== strpos( $front, 'This is a request, not a confirmed appointment.' ),
    'request submit label' => false !== strpos( $front, "'Request appointment'" ),
    'no false immediate booking title' => false === strpos( $front, "'Book with %s'" ),
    'client response acknowledges request rather than confirmation' => false !== strpos( $js, 'Appointment request submitted. Reference:' ),
    'no client claim of confirmed booking from POST /appointments' => false === strpos( $js, 'Your appointment has been confirmed.' ),
);
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "V12.3 booking truth FAIL: {$label}\n" ); exit( 1 ); }
}
echo 'V12.3 booking request truth assertions PASS: ' . count( $checks ) . '/' . count( $checks ) . "\n";
