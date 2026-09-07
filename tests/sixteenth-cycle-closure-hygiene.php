<?php
$root = dirname( __DIR__ );
$fail = array();
$patterns = array(
    $root . '/tools/t16-*',
    $root . '/.github/workflows/t16-*',
    $root . '/review-evidence/t16-*',
);
foreach ( $patterns as $pattern ) {
    foreach ( glob( $pattern ) ?: array() as $path ) {
        $fail[] = str_replace( $root . '/', '', $path );
    }
}
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
if ( false === strpos( (string) $readme, 'Current sixteenth-cycle runtime alignment' ) ) {
    $fail[] = 'README missing current sixteenth-cycle evidence label';
}
if ( false === strpos( (string) $status, 'Sixteenth fresh 20-round sequential audit' ) ) {
    $fail[] = 'STATUS missing current sixteenth-cycle closure section';
}
if ( $fail ) {
    fwrite( STDERR, "Sixteenth-cycle closure hygiene failed:
- " . implode( "
- ", $fail ) . "
" );
    exit( 1 );
}
echo "Sixteenth-cycle closure hygiene: PASS
";
