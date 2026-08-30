<?php
$contracts = file_get_contents( __DIR__ . '/../includes/class-wca-contracts.php' );
$schema = file_get_contents( __DIR__ . '/../includes/class-wca-schema.php' );
$repo = file_get_contents( __DIR__ . '/../includes/class-wca-repository.php' );
$service = file_get_contents( __DIR__ . '/../includes/class-wca-service.php' );
if ( false === $contracts || false === $schema || false === $repo || false === $service ) { fwrite( STDERR, "T17 R16 source missing.\n" ); exit( 1 ); }
if ( false === strpos( $contracts, "SCHEMA_VERSION                  = '3.4.0'" ) ) { fwrite( STDERR, "T17 R16 corrective schema version is not active.\n" ); exit( 1 ); }
if ( ! preg_match( "/CREATE TABLE \\{\\\$tables\\['slot_holds'\\]\\} \\((.*?)\\) \\{\\\$collate\\};/s", $schema, $slot ) ) { fwrite( STDERR, "T17 R16 slot_holds schema block unavailable.\n" ); exit( 1 ); }
foreach ( array( 'rule_ref char(36)', 'capacity smallint(5) unsigned NOT NULL DEFAULT 1', 'buffer_before smallint(5) unsigned NOT NULL DEFAULT 0', 'buffer_after smallint(5) unsigned NOT NULL DEFAULT 0', 'KEY rule_window (rule_ref,start_utc,end_utc,status)' ) as $needle ) {
    if ( false === strpos( $slot[1], $needle ) ) { fwrite( STDERR, "T17 R16 slot_holds column/index missing: {$needle}\n" ); exit( 1 ); }
}
if ( ! preg_match( "/CREATE TABLE \\{\\\$tables\\['payment_intents'\\]\\} \\((.*?)\\) \\{\\\$collate\\};/s", $schema, $payment ) ) { fwrite( STDERR, "T17 R16 payment_intents schema block unavailable.\n" ); exit( 1 ); }
foreach ( array( 'source_version bigint(20) unsigned NOT NULL DEFAULT 0', "source_event_id varchar(191) NOT NULL DEFAULT ''", 'source_occurred_at datetime NULL', 'KEY source_order (source_version,source_occurred_at)' ) as $needle ) {
    if ( false === strpos( $payment[1], $needle ) ) { fwrite( STDERR, "T17 R16 payment ordering column/index missing: {$needle}\n" ); exit( 1 ); }
}
$required_repo = array(
    'public static function project_payment_status( $payment_ref, $status, $provider_ref = \'\', $source_version = 0, $source_event_id = \'\', $source_occurred_at = \'\' )',
    'if ( $source_version < $current_source_version )',
    "'_projection_action'] = 'stale_ignored'",
    "'wca_payment_source_version_conflict'",
    "'source_version' => $current_source_version",
);
foreach ( $required_repo as $needle ) { if ( false === strpos( $repo, $needle ) ) { fwrite( STDERR, "T17 R16 repository ordering invariant missing: {$needle}\n" ); exit( 1 ); } }
$required_service = array( "'source_version'", "'occurred_at'", 'PaymentStatusStaleIgnored.v1', 'PaymentStatusEquivalentReplay.v1', "'projection_action'" );
foreach ( $required_service as $needle ) { if ( false === strpos( $service, $needle ) ) { fwrite( STDERR, "T17 R16 service reconciliation invariant missing: {$needle}\n" ); exit( 1 ); } }
echo "T17 R16 finance/schema reconciliation regressions passed.\n";
