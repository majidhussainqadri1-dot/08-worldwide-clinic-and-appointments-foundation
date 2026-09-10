<?php
$root = dirname( __DIR__ );
$path = $root . '/includes/class-wca-verification-reconciliation.php';
$source = is_file( $path ) ? file_get_contents( $path ) : '';
$failures = array();

$required = array(
    "const DELEGATION_META_KEY = '_wca_clinic_delegations'",
    "add_action( 'added_user_meta', array( __CLASS__, 'delegation_meta_changed' ), 20, 4 )",
    "add_action( 'updated_user_meta', array( __CLASS__, 'delegation_meta_changed' ), 20, 4 )",
    "add_action( 'deleted_user_meta', array( __CLASS__, 'delegation_meta_changed' ), 20, 4 )",
    "self::run_or_retry( \$user_id, \$eligible, 'delegation_changed', 'File08' )",
    's.doctor_user_id=%d',
    'a.doctor_user_id=%d',
    'WCA_Service::public_clinic_projection( $clinic_ref )',
    '$clinic_eligible = is_array( $projection )',
    "'eligible'                          => (bool) \$clinic_eligible",
    "'practitioner_eligible'              => (bool) \$practitioner_eligible",
    "'ClinicPractitionerEligibilityChanged.v1'",
    "'change_source'                     => \$change_event",
);

foreach ( $required as $token ) {
    if ( ! is_string( $source ) || false === strpos( $source, $token ) ) {
        $failures[] = 'Missing R8 discovery-reconciliation invariant: ' . $token;
    }
}

if ( is_string( $source ) && false !== strpos( $source, "'eligible'      => (bool) \$eligible" ) ) {
    $failures[] = 'File26 whole-clinic eligibility still aliases the affected practitioner verification bit.';
}
if ( is_string( $source ) && false !== strpos( $source, "'eligible'    => (bool) \$eligible" ) ) {
    $failures[] = 'Clinic eligibility payload still aliases the affected practitioner verification bit.';
}

if ( $failures ) {
    fwrite( STDERR, "T22 R8 discovery reconciliation regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}

echo "T22 R8 discovery reconciliation regressions: PASS\n";
