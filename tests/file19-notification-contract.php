<?php
/** Current File 08 -> File 19 canonical notification contract regression. */
$root = dirname( __DIR__ );
$plugin = (string) file_get_contents( $root . '/includes/class-wca-plugin.php' );
$outbox = (string) file_get_contents( $root . '/includes/class-wca-outbox.php' );
$legacy = (string) file_get_contents( $root . '/includes/class-swc-helpers.php' );
$checks = array(
    'File 08 registers a bounded File 19 producer' =>
        false !== strpos( $plugin, "sun_register_notification_producer" )
        && false !== strpos( $plugin, "'file08-clinic'" )
        && false !== strpos( $plugin, "'owner' => 'File 08'" )
        && false !== strpos( $plugin, "'Clinic.*'" ),
    'canonical outbox invokes File 19 ingest API' =>
        false !== strpos( $outbox, "sun_ingest_domain_event" )
        && false !== strpos( $outbox, "'producer' => 'file08-clinic'" )
        && false !== strpos( $outbox, "'owner' => 'File 08'" ),
    'File 08 outbox no longer impersonates sn_notify_users as File 19' =>
        false === strpos( $outbox, "sn_notify_users" )
        && false === strpos( $outbox, "'fallback_mail'" ),
    'legacy appointment notification path no longer calls retired notification APIs' =>
        false === strpos( $legacy, "SUN_Core::create" )
        && false === strpos( $legacy, "sabri_notify" )
        && false !== strpos( $legacy, "sun_ingest_domain_event" ),
    'File 08 notification payload remains privacy-minimal' =>
        false !== strpos( $outbox, "'sensitivity' => 'sensitive'" )
        && false === strpos( $outbox, "'diagnosis'" )
        && false === strpos( $outbox, "'clinical_note'" ),
);
$failures = array();
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $failures[] = $name; } }
if ( $failures ) {
    fwrite( STDERR, "File 08/File 19 notification regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
    exit( 1 );
}
echo 'File 08/File 19 notification regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
