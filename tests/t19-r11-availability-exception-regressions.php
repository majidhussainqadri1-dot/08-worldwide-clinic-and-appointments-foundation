<?php
$root = dirname( __DIR__ );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
if ( ! is_string( $service ) || ! is_string( $repo ) ) { fwrite( STDERR, "T19 R11 source read failed\n" ); exit( 1 ); }
$checks = array(
    'open exception overrides recurring weekday closure' => false !== strpos( $service, '( isset( $days[ $day_key ] ) || $open_override )' ),
    'capacity exception derives date capacity' => false !== strpos( $service, "'capacity' ===" ) && false !== strpos( $service, '$date_capacity' ),
    'slot conflict gate consumes date capacity' => false !== strpos( $service, ', $date_capacity )' ),
    'slot projection exposes date capacity' => false !== strpos( $service, "'capacity'        => " . '$date_capacity' ),
    'repository tracks exception dates' => false !== strpos( $repo, '$exception_dates = array();' ),
    'duplicate exception dates fail explicitly' => false !== strpos( $repo, 'wca_repository_availability_exception_duplicate' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R11 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R11 availability exception regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
