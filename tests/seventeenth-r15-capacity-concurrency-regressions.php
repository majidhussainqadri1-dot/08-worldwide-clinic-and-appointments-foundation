<?php
$contracts = file_get_contents( __DIR__ . '/../includes/class-wca-contracts.php' );
$schema = file_get_contents( __DIR__ . '/../includes/class-wca-schema.php' );
$guard = file_get_contents( __DIR__ . '/../includes/class-wca-plan-guard.php' );
$repo = file_get_contents( __DIR__ . '/../includes/class-wca-repository.php' );
$service = file_get_contents( __DIR__ . '/../includes/class-wca-service.php' );
$future = file_get_contents( __DIR__ . '/../includes/class-wca-future24.php' );
foreach ( array( $contracts,$schema,$guard,$repo,$service,$future ) as $source ) { if ( false === $source ) { fwrite( STDERR, "T17 R15 source missing.\n" ); exit( 1 ); } }
$required = array(
    array( $contracts, "SCHEMA_VERSION                  = '3.4.0'" ),
    array( $schema, 'rule_ref char(36) NOT NULL' ),
    array( $schema, 'capacity smallint(5) unsigned NOT NULL DEFAULT 1' ),
    array( $schema, 'buffer_before smallint(5) unsigned NOT NULL DEFAULT 0' ),
    array( $schema, 'buffer_after smallint(5) unsigned NOT NULL DEFAULT 0' ),
    array( $schema, 'KEY rule_window (rule_ref,start_utc,end_utc,status)' ),
    array( $guard, "'capacity'        => min( 50, max( 1, absint( \$rule['capacity'] ?? 1 ) ) )" ),
    array( $repo, 'public static function slot_capacity_available(' ),
    array( $repo, "hash( 'sha256', (string) \$ignore_idempotency_key )" ),
    array( $repo, 'DATE_SUB(start_utc, INTERVAL buffer_before MINUTE)' ),
    array( $repo, 'DATE_ADD(end_utc, INTERVAL buffer_after MINUTE)' ),
    array( $repo, "rule_ref='' OR rule_ref<>%s" ),
    array( $repo, 'if ( (int) $count_raw >= $capacity ) { return false; }' ),
    array( $repo, "h.status='booked'" ),
    array( $repo, "'rule_ref'        => \$rule_ref" ),
    array( $repo, "'buffer_before'   => \$buffer_before" ),
    array( $service, 'WCA_Repository::slot_capacity_available(' ),
    array( $service, "max( 1, absint( \$rule['capacity'] ?? 1 ) )" ),
    array( $future, "'F08-FUT-05' => array( 'slug' => 'group_capacity'" ),
    array( $future, "semantic_lock( 'group-session'" ),
);
foreach ( $required as $pair ) { if ( false === strpos( $pair[0], $pair[1] ) ) { fwrite( STDERR, "T17 R15 invariant missing: {$pair[1]}\n" ); exit( 1 ); } }
if ( false !== strpos( $service, "idempotency_key<>%s AND expires_at>%s" ) ) { fwrite( STDERR, "T17 R15 stale plain-vs-hash replay comparison remains.\n" ); exit( 1 ); }
if ( false !== strpos( $repo, "SELECT id FROM {\$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1" ) ) { fwrite( STDERR, "T17 R15 singular overlap gate remains in canonical hold path.\n" ); exit( 1 ); }
echo "T17 R15 capacity/concurrency regressions passed.\n";
