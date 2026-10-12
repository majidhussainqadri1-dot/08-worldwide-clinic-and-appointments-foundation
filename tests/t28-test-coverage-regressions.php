<?php
/**
 * T28: every executable test is part of canonical source QA, including late
 * cross-file additions. Static/isolated tests run as independent subprocesses.
 */
$root = __DIR__;
$runner = file_get_contents( $root . '/run-all.php' );
if ( ! is_string( $runner ) ) { fwrite( STDERR, "Unable to read canonical runner\n" ); exit( 1 ); }
preg_match_all( "/'([^']+\\.php)'/", $runner, $matches );
$included = $matches[1];
$exceptions = array( 'bootstrap.php', 'run.php', 'run-all.php' );
$unbound = array();
foreach ( new DirectoryIterator( $root ) as $file ) {
    if ( ! $file->isFile() || strtolower( $file->getExtension() ) !== 'php' ) { continue; }
    $name = $file->getFilename();
    if ( in_array( $name, $exceptions, true ) ) { continue; }
    if ( ! in_array( $name, $included, true ) ) { $unbound[] = $name; }
}
$nonexistent = array_filter( $included, static function ( $name ) use ( $root ) { return ! is_file( $root . '/' . $name ); } );
$duplicates = array_filter( array_count_values( $included ), static function ( $count ) { return $count !== 1; } );
if ( $unbound || $nonexistent || $duplicates ) {
    fwrite( STDERR, 'T28 orphan test coverage failed; unbound: ' . implode( ',', $unbound ) .
        '; missing: ' . implode( ',', $nonexistent ) . '; duplicates: ' . implode( ',', array_keys( $duplicates ) ) . "\n" );
    exit( 1 );
}
echo 'T28 executable test coverage: PASS ' . count( $included ) . ' registered; 0 orphaned\n';
