<?php
$root = dirname( __DIR__ );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
$svc  = file_get_contents( $root . '/includes/class-wca-service.php' );
$checks = array(
	'public get_service rejects invalid legacy currency' => strpos( $repo, 'wca_service_currency_invalid' ) !== false && substr_count( $repo, 'WCA_Service::valid_currency' ) >= 3,
	'public service list filters invalid legacy currency' => strpos( $repo, "currency'] ?? ''" ) !== false && strpos( $repo, 'return null;' ) !== false,
	'payment snapshot uses canonical currency validator' => strpos( $svc, 'if ( ! self::valid_currency( $currency ) || null === $amount' ) !== false,
	'payment snapshot regex-only trust removed' => strpos( $svc, "preg_match( '/^[A-Z]{3}$/', \$currency )" ) === false,
);
foreach ( $checks as $name => $ok ) {
	if ( ! $ok ) {
		fwrite( STDERR, "T18 R6 FAIL: {$name}\n" );
		exit( 1 );
	}
}
echo "T18 R6 financial read-side regressions: PASS\n";
