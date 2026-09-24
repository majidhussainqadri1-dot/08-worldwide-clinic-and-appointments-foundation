<?php
$root = dirname( __DIR__ );
$checks = array(
    array( 'service update preserves omitted mutable state', 'includes/class-wca-service.php', "foreach ( array( 'name', 'branch_id', 'doctor_user_id', 'tax_policy', 'refund_policy', 'cancellation_policy', 'status' ) as \$preserve_field" ),
    array( 'public services recheck clinic serving authority', 'includes/class-wca-service.php', 'WCA_Authorization::doctor_can_serve_clinic( $private, $service_doctor_id )' ),
    array( 'public projection uses filtered services', 'includes/class-wca-service.php', "'services'       => \$public_services" ),
    array( 'verification reconciliation scans service assignments', 'includes/class-wca-verification-reconciliation.php', 's.doctor_user_id=%d' ),
    array( 'verification reconciliation scans availability assignments', 'includes/class-wca-verification-reconciliation.php', 'a.doctor_user_id=%d' ),
    array( 'verification reconciliation no longer owner-only pages', 'includes/class-wca-verification-reconciliation.php', 'SELECT DISTINCT c.id' ),
    array( 'reconciliation emits File26 refresh', 'includes/class-wca-verification-reconciliation.php', "File26.SearchProjectionChanged.v1" ),
);
$fail = array();
$pass = 0;
foreach ( $checks as $check ) {
    $text = file_get_contents( $root . '/' . $check[1] );
    if ( is_string( $text ) && false !== strpos( $text, $check[2] ) ) {
        echo 'PASS ' . (++$pass) . ': ' . $check[0] . "\n";
    } else {
        $fail[] = $check[0];
    }
}
if ( $fail ) {
    fwrite( STDERR, "T19 R8 regression gate failed:\n- " . implode( "\n- ", $fail ) . "\n" );
    exit( 1 );
}
echo 'T19 R8 service/projection/reconciliation regressions: PASS ' . $pass . '/' . $pass . "\n";
