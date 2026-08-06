<?php
require __DIR__ . '/bootstrap.php';
$root = dirname( __DIR__ );
$php = '';
foreach ( new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/includes', FilesystemIterator::SKIP_DOTS ) ) as $file ) { if ( $file->isFile() && 'php' === $file->getExtension() ) { $php .= "\n" . file_get_contents( $file->getPathname() ); } }
$public = file_get_contents( $root . '/includes/class-wca-service.php' );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$outbox = file_get_contents( $root . '/includes/class-wca-outbox.php' );

foreach ( array( 'eval(', 'base64_decode(', 'shell_exec(', 'passthru(', 'proc_open(' ) as $danger ) { wca_test_assert( false === strpos( $php, $danger ), "dangerous primitive {$danger} is absent" ); }
wca_test_assert( 0 === preg_match( '/wp_ajax_nopriv_[^\n]+(?:create|save|update|delete|appointment|hold)/i', $php ), 'no unauthenticated write AJAX action exists' );
wca_test_assert( false !== strpos( $rest, 'permission_callback' ), 'REST authorization is explicit' );
wca_test_assert( false !== strpos( $rest, 'rate_limit' ), 'REST abuse controls are present' );
wca_test_assert( false !== strpos( $public, 'prohibited_public_fields' ), 'public projection applies a deny list' );
wca_test_assert( false !== strpos( $outbox, 'Privacy-minimal fallback' ), 'notification fallback is privacy-minimal' );
wca_test_assert( false === strpos( $outbox, "payload['reason']" ) && false === strpos( $outbox, "payload['note']" ), 'outbox fallback never emails clinical narrative' );
wca_test_assert( false !== strpos( $php, 'expected_version' ) && false !== strpos( $php, 'record_version' ), 'optimistic concurrency is enforced' );
wca_test_assert( false !== strpos( $php, 'idempotency_key' ), 'idempotent write protection is implemented' );
wca_test_assert( false !== strpos( $php, 'legal_hold' ), 'legal-hold-aware privacy lifecycle exists' );
echo "Security static tests complete.\n";
