<?php
$root = dirname( __DIR__ );
$helpers = file_get_contents( $root . '/includes/class-swc-helpers.php' );
$appointments = file_get_contents( $root . '/includes/class-swc-appointments.php' );
$legacy_admin = file_get_contents( $root . '/includes/class-swc-admin.php' );
$admin = file_get_contents( $root . '/includes/class-wca-admin.php' );
$continuity = file_get_contents( $root . '/includes/class-wca-continuity-secure.php' );
$assertions = array(
    'legacy doctor management revalidates central claims' => substr_count( $helpers, 'WCA_Authorization::claims( $user_id )' ) >= 2,
    'legacy patient management revalidates central claims' => false !== strpos( $helpers, 'public static function can_patient_manage' ) && false !== strpos( $helpers, 'return ! is_wp_error( WCA_Authorization::can_view_appointment' ),
    'legacy booking revalidates membership claims' => false !== strpos( $appointments, 'WCA_Authorization::claims( $user_id )' ),
    'legacy booking enforces patient guardian context' => false !== strpos( $appointments, 'WCA_Authorization::guardian_context( $user_id, 0, $user_id )' ),
    'legacy clinic admin revalidates File 00 claims' => false !== strpos( $legacy_admin, 'WCA_Authorization::claims( get_current_user_id() )' ),
    'legacy clinic admin requires step up' => false !== strpos( $legacy_admin, "require_step_up( 'appointment_operations'" ),
    'operations page revalidates File 00 claims' => false !== strpos( $admin, 'WCA_Authorization::claims( get_current_user_id() )' ),
    'operations mutation requires step up' => false !== strpos( $admin, "require_step_up( 'clinic_operations'" ),
    'continuity operations health revalidates File 00 claims' => false !== strpos( $continuity, 'wca_admin_membership' ) && false !== strpos( $continuity, 'WCA_Authorization::claims( get_current_user_id() )' ),
    'legacy can_view no longer trusts native capability alone' => false === strpos( $helpers, "self::can_patient_manage( \$id ) || self::can_doctor_manage( \$id ) || current_user_can( 'manage_worldwide_clinic' )" ),
);
$failed = array_keys( array_filter( $assertions, static function ( $ok ) { return ! $ok; } ) );
foreach ( $assertions as $name => $ok ) { echo ( $ok ? '[PASS] ' : '[FAIL] ' ) . $name . PHP_EOL; }
if ( $failed ) { fwrite( STDERR, 'R3 authorization regression failures: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'R3 authorization assertions: ' . count( $assertions ) . '/' . count( $assertions ) . ' PASS' . PHP_EOL;
