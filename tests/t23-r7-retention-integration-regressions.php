<?php
$root = dirname( __DIR__ );
$entry = file_get_contents( $root . '/worldwide-clinic.php' );
$retention = file_get_contents( $root . '/includes/class-wca-retention-policy.php' );
$legacy = file_get_contents( $root . '/includes/class-swc-privacy.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
if ( ! is_string( $entry ) || ! is_string( $retention ) || ! is_string( $legacy ) || ! is_string( $runner ) ) {
    fwrite( STDERR, "T23 R7 source read failed\n" );
    exit( 1 );
}
$plugin_boot = strpos( $entry, 'WCA_Plugin::boot();' );
$retention_boot = strpos( $entry, 'WCA_Retention_Policy::boot();' );
$checks = array(
    'retention policy class is loaded by canonical entrypoint' => false !== strpos( $entry, "'includes/class-wca-retention-policy.php'" ),
    'retention normalizer is booted after canonical privacy hooks register' => false !== $plugin_boot && false !== $retention_boot && $retention_boot > $plugin_boot,
    'normalizer removes legacy privacy policy callback' => false !== strpos( $retention, "remove_action( 'admin_init', array( 'WCA_Privacy', 'register_policy' ) );" ),
    'normalizer installs replacement callback' => false !== strpos( $retention, "add_action( 'admin_init', array( __CLASS__, 'normalize' ), 10 );" ),
    'unsupported appointment and event day keys are removed' => false !== strpos( $retention, "unset( \$policy['completed_appointments_days'], \$policy['cancelled_appointments_days'], \$policy['events_days'] );" ),
    'automatic appointment purge is explicitly disabled' => false !== strpos( $retention, "'automatic_appointment_purge' => false" ) && false !== strpos( $retention, "\$policy['automatic_appointment_purge'] = false;" ),
    'automatic event purge is explicitly disabled' => false !== strpos( $retention, "'automatic_event_purge'       => false" ) && false !== strpos( $retention, "\$policy['automatic_event_purge'] = false;" ),
    'legacy eraser does not directly write lifecycle status' => false === strpos( $legacy, "update_meta_strict( \$appointment_id, '_swc_status'" ),
    'this permanent regression is bound into aggregate suite' => false !== strpos( $runner, "'t23-r7-retention-integration-regressions.php'" ),
);
$failures = array();
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) { $failures[] = $name; }
}
if ( $failures ) {
    fwrite( STDERR, "T23 R7 retention integration regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo 'T23 R7 retention integration regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
