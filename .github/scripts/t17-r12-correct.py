from pathlib import Path

repo = Path('includes/class-wca-repository.php')
s = repo.read_text()

if 'private static $transaction_state_uncertain = false;' not in s:
    old = "\tprivate static $transaction_depth = 0;\n\tprivate static $read_error = null;"
    new = "\tprivate static $transaction_depth = 0;\n\tprivate static $transaction_state_uncertain = false;\n\tprivate static $read_error = null;"
    if s.count(old) != 1:
        raise SystemExit('R12 transaction state anchor is not unique')
    s = s.replace(old, new, 1)

if "self::$transaction_state_uncertain = false;\n\t\t$started = $wpdb->query( 'START TRANSACTION' )" not in s:
    old = "\t\t$started = $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery"
    new = "\t\tself::$transaction_state_uncertain = false;\n\t\t$started = $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery"
    if s.count(old) != 1:
        raise SystemExit('R12 transaction start anchor is not unique')
    s = s.replace(old, new, 1)

old = "if ( false === $rolled_back ) { return new WP_Error( 'wca_transaction_rollback_failed'"
new = "if ( false === $rolled_back ) { self::$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_rollback_failed'"
if old in s:
    s = s.replace(old, new, 1)

old = "if ( false === $rolled_back ) { return new WP_Error( 'wca_transaction_commit_rollback_failed'"
new = "if ( false === $rolled_back ) { self::$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_commit_rollback_failed'"
if old in s:
    s = s.replace(old, new, 1)

old = "if ( false === $rolled_back ) { return new WP_Error( 'wca_transaction_exception_rollback_failed'"
new = "if ( false === $rolled_back ) { self::$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_exception_rollback_failed'"
if old in s:
    s = s.replace(old, new, 1)

old = "\tpublic static function release_idempotency( $id ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['idempotency'];"
new = "\tpublic static function release_idempotency( $id ) {\n\t\tglobal $wpdb;\n\t\tif ( self::$transaction_state_uncertain ) {\n\t\t\tif ( class_exists( 'WCA_Observability' ) ) { WCA_Observability::metric( 'idempotency_release_uncertain_transaction_blocked_total', 1 ); WCA_Observability::log( 'error', 'idempotency_release_uncertain_transaction_blocked', array( 'reservation_id' => absint( $id ) ) ); }\n\t\t\treturn false;\n\t\t}\n\t\t$table = WCA_Schema::tables()['idempotency'];"
if 'idempotency_release_uncertain_transaction_blocked_total' not in s:
    if s.count(old) != 1:
        raise SystemExit('R12 release anchor is not unique')
    s = s.replace(old, new, 1)

required = [
    'private static $transaction_state_uncertain = false;',
    "self::$transaction_state_uncertain = false;\n\t\t$started",
    "self::$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_rollback_failed'",
    "self::$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_commit_rollback_failed'",
    "self::$transaction_state_uncertain = true; return new WP_Error( 'wca_transaction_exception_rollback_failed'",
    'idempotency_release_uncertain_transaction_blocked_total',
]
for needle in required:
    if needle not in s:
        raise SystemExit('R12 required correction missing: ' + needle)
repo.write_text(s)

test = Path('tests/seventeenth-r12-idempotency-uncertainty-regressions.php')
test.write_text(r'''<?php
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
''')

run = Path('tests/run-all.php')
r = run.read_text()
marker = "'seventeenth-r10-slot-guardian-regressions.php'"
addition = marker + ", 'seventeenth-r12-idempotency-uncertainty-regressions.php'"
if 'seventeenth-r12-idempotency-uncertainty-regressions.php' not in r:
    if r.count(marker) != 1:
        raise SystemExit('R12 run-all anchor is not unique')
    r = r.replace(marker, addition, 1)
    run.write_text(r)
