<?php
$root = dirname( __DIR__ );
$front = file_get_contents( $root . '/includes/class-wca-frontend.php' );
$clinic_js = file_get_contents( $root . '/assets/js/clinic.js' );
$continuity_js = file_get_contents( $root . '/assets/js/continuity.js' );
if ( ! is_string( $front ) || ! is_string( $clinic_js ) || ! is_string( $continuity_js ) ) {
    fwrite( STDERR, "T22 R9 source read failed\n" );
    exit( 1 );
}

$checks = array(
    'appointment labels use explicit translation map' => false !== strpos( $front, 'private static function appointment_status_label' ) && false !== strpos( $front, "__( 'Reschedule pending', 'worldwide-clinic-appointments' )" ),
    'consultation labels use explicit translation map' => false !== strpos( $front, 'private static function consultation_type_label' ) && false !== strpos( $front, "__( 'In person', 'worldwide-clinic-appointments' )" ),
    'clinic labels use explicit translation map' => false !== strpos( $front, 'private static function clinic_status_label' ) && false !== strpos( $front, "__( 'Suspended', 'worldwide-clinic-appointments' )" ),
    'raw appointment status presentation retired' => false === strpos( $front, "ucfirst( str_replace( '_', ' ', \$status ) )" ),
    'raw service consultation presentation retired' => false === strpos( $front, "ucfirst( \$service['consultation_type'] )" ),
    'appointment time uses locale-aware wp_date in patient timezone' => false !== strpos( $front, "wp_date( 'F j, Y g:i a', \$moment->getTimestamp(), \$target )" ),
    'browser local calendar date helper exists' => false !== strpos( $clinic_js, 'function localDateValue(date)' ),
    'browser timezone adoption also sets booking date' => false !== strpos( $clinic_js, 'dateFrom.value = browserDate' ) && false !== strpos( $clinic_js, 'dateFrom.min = browserDate' ),
    'client transition feedback does not expose raw state key' => false === strpos( $clinic_js, 'next.replace(/_/g' ) && false === strpos( $clinic_js, 'result.status.replace(/_/g' ),
    'client transition feedback uses localized visible button label' => false !== strpos( $clinic_js, "var actionLabel = String(button.textContent || '').trim()" ) && false !== strpos( $clinic_js, "setStatus(card, 'Appointment updated successfully.', false)" ),
    'continuity generated form labels pass through translation helper' => false !== strpos( $continuity_js, 'document.createTextNode(tr(spec[1]))' ),
    'continuity read-only payload uses translation-safe field label map' => false !== strpos( $continuity_js, 'function intakeFieldLabel(name)' ) && false !== strpos( $continuity_js, "strong.textContent = intakeFieldLabel(name) + ': '" ),
    'raw continuity payload keys are not shown as labels' => false === strpos( $continuity_js, "name.replace(/_/g, ' ') + ': '" ),
);

$failures = array();
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) { $failures[] = $name; }
}
if ( $failures ) {
    fwrite( STDERR, "T22 R9 localization/timezone regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}

echo 'T22 R9 localization/timezone regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
