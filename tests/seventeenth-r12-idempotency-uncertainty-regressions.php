<?php
$repo = file_get_contents( __DIR__ . '/../includes/class-wca-repository.php' );
if ( false === $repo ) { fwrite( STDERR, "T17 R12 repository source missing.\n" ); exit( 1 ); }
$needles = array(
    'private static $transaction_state_uncertain = false;',
    "self::\$transaction_state_uncertain = false;\n\t\t\$started",
    "self::\$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_rollback_failed'",
    "self::\$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_commit_rollback_failed'",
    "self::\$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_exception_rollback_failed'",
    'idempotency_release_uncertain_transaction_blocked_total',
);
foreach ( $needles as $needle ) {
    if ( false === strpos( $repo, $needle ) ) { fwrite( STDERR, "T17 R12 invariant missing: {$needle}\n" ); exit( 1 ); }
}
$release = strstr( $repo, 'public static function release_idempotency' );
if ( false === $release || false === strpos( substr( $release, 0, 1200 ), 'if ( self::$transaction_state_uncertain )' ) ) {
    fwrite( STDERR, "T17 R12 release path does not fail closed on uncertain transaction state.\n" ); exit( 1 );
}
echo "T17 R12 idempotency uncertainty regressions passed.\n";
