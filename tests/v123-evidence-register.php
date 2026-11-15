<?php
/**
 * V12.3 exact File08 54-clause source traceability gate.
 *
 * This checks document and source identifier coverage ONLY. It must never
 * be reported as proof of a working WordPress journey, staging or live.
 */
$root = dirname( __DIR__ );
$doc = file_get_contents( $root . '/docs/V123-20261114-F08-EVIDENCE-EXECUTION-REGISTER.md' );
$contracts = file_get_contents( $root . '/includes/class-wca-contracts.php' );
$future = file_get_contents( $root . '/includes/class-wca-future24.php' );
if ( ! is_string( $doc ) || ! is_string( $contracts ) || ! is_string( $future ) ) {
    fwrite( STDERR, "V12.3 traceability source unavailable\n" ); exit( 1 );
}
preg_match_all( '/^\|\s*(F08-(?:FR|NFR|CEN)-\d{2,3}|F08-FUT-\d{2})\b/m', $doc, $m );
$found = array_count_values( $m[1] );
$expected = array();
foreach ( array( 'FR' => 18, 'NFR' => 10, 'CEN' => 2, 'FUT' => 24 ) as $type => $max ) {
    for ( $n = 1; $n <= $max; $n++ ) {
        $id = sprintf( 'F08-%s-%0' . ( 'FUT' === $type || 'CEN' === $type ? '2' : '3' ) . 'd', $type, $n );
        $expected[] = $id;
        if ( ! isset( $found[ $id ] ) || 1 !== $found[ $id ] ) {
            fwrite( STDERR, "V12.3 register missing/duplicated: {$id}\n" ); exit( 1 );
        }
        if ( 'FR' === $type || 'NFR' === $type ) {
            if ( false === strpos( $contracts, $id ) ) { fwrite( STDERR, "Contract missing {$id}\n" ); exit( 1 ); }
        }
        if ( 'FUT' === $type ) {
            if ( false === strpos( $future, $id ) ) { fwrite( STDERR, "Future24 source missing {$id}\n" ); exit( 1 ); }
        }
    }
}
if ( count( $found ) !== count( $expected ) ) {
    fwrite( STDERR, "Register has unexpected File08 requirement IDs\n" ); exit( 1 );
}
foreach ( array( 'Live Verification Status', 'WP/LIVE pending', 'site_disconnected', 'HTTP 530', 'not functional acceptance' ) as $word ) {
    if ( false === stripos( $doc, $word ) ) { fwrite( STDERR, "Register evidence caveat missing: {$word}\n" ); exit( 1 ); }
}
echo 'PASS: V12.3 exact 54 requirement IDs traceable; this is NOT runtime acceptance.' . "\n";
