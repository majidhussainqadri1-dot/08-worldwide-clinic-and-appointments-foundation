<?php
$root = dirname( __DIR__ );
$builder = file_get_contents( $root . '/tools/build-candidate.php' );
$verifier = file_get_contents( $root . '/tools/verify-candidate.php' );
if ( ! is_string( $builder ) || ! is_string( $verifier ) ) {
	fwrite( STDERR, "T21 R10 candidate tooling is unavailable.\n" );
	exit( 1 );
}

$required = array( 'worldwide-clinic.php', 'readme.txt', 'uninstall.php' );
foreach ( $required as $file ) {
	if ( false === strpos( $builder, "'{$file}'" ) ) {
		fwrite( STDERR, "Builder no longer declares required candidate payload: {$file}\n" );
		exit( 1 );
	}
	if ( false === strpos( $verifier, "'{$file}'" ) ) {
		fwrite( STDERR, "Verifier no longer requires candidate payload: {$file}\n" );
		exit( 1 );
	}
}
if ( false === strpos( $builder, 'Required candidate file is missing:' ) || false === strpos( $builder, "if ( ! is_file( \$root . '/' . \$file ) )" ) ) {
	fwrite( STDERR, "Builder is not fail-closed for missing required root payload files.\n" );
	exit( 1 );
}
if ( false === strpos( $verifier, 'Required candidate payload is missing:' ) || false === strpos( $verifier, 'empty( $manifestPaths[ $requiredPath ] )' ) || false === strpos( $verifier, 'empty( $seen[ $prefix . $requiredPath ] )' ) ) {
	fwrite( STDERR, "Independent verifier is not fail-closed for missing required payloads.\n" );
	exit( 1 );
}

echo "T21 R10 candidate required-payload regressions passed.\n";
