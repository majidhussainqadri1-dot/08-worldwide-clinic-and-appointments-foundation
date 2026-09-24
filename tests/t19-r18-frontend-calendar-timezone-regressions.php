<?php
$root = dirname( __DIR__ );
$front = file_get_contents( $root . '/includes/class-wca-frontend.php' );
$js = file_get_contents( $root . '/assets/js/clinic.js' );
if ( ! is_string( $front ) || ! is_string( $js ) ) { fwrite( STDERR, "T19 R18 source read failed\n" ); exit( 1 ); }
$checks = array(
    'plain nonce-protected calendar export anchors retired' => false === strpos( $front, "rest_url( 'wca/v1/appointment-refs/'" ),
    'calendar download controls use signer action' => 2 === substr_count( $front, 'data-wca-calendar-download' ),
    'calendar client requests signed link with nonce-aware API helper' => false !== strpos( $js, "api('calendar-links/' + encodeURIComponent(ref))" ),
    'calendar client navigates only after signed URL response' => false !== strpos( $js, 'window.location.assign(String(signed.url))' ),
    'patient projection consumes stored timezone' => false !== strpos( $front, "'timezone'] ?? 'UTC'" ) && false !== strpos( $front, 'appointment_time_label( ' . '$when, $timezone' . ' )' ),
    'legacy detail card consumes stored patient timezone' => false !== strpos( $front, "'patient_timezone', 'UTC'" ),
    'timezone projection validates IANA timezone' => false !== strpos( $front, 'WCA_Service::valid_timezone( ' . '$timezone' . ' )' ),
    'timezone projection converts canonical UTC using locale-aware target timezone' => false !== strpos( $front, "new DateTimeZone( 'UTC' )" ) && false !== strpos( $front, 'new DateTimeZone( $timezone )' ) && false !== strpos( $front, "wp_date( 'F j, Y g:i a', \$moment->getTimestamp(), \$target )" ),
    'site-timezone get_date_from_gmt appointment rendering retired' => false === strpos( $front, 'get_date_from_gmt( ' . '$when' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R18 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R18 frontend calendar/timezone regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
