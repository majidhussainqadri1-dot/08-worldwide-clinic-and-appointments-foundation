<?php
function luminance( $hex ) {
	$hex = ltrim( $hex, '#' );
	$parts = array_map( 'hexdec', str_split( $hex, 2 ) );
	$linear = array_map(
		function ( $value ) {
			$value /= 255;
			return $value <= 0.03928 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
		},
		$parts
	);
	return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}
function contrast( $a, $b ) {
	$l1 = luminance( $a );
	$l2 = luminance( $b );
	return ( max( $l1, $l2 ) + 0.05 ) / ( min( $l1, $l2 ) + 0.05 );
}
$ratio = contrast( '#FF8A1F', '#172033' );
if ( $ratio < 4.5 ) {
	fwrite( STDERR, sprintf( "FAIL: contrast ratio %.3f:1\n", $ratio ) );
	exit( 1 );
}
printf( "PASS: #FF8A1F against #172033 = %.3f:1\n", $ratio );
