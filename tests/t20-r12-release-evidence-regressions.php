<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );

function t20_r12_fail( $message ) {
	fwrite( STDERR, "T20 R12 release-evidence regression failed: {$message}\n" );
	exit( 1 );
}

if ( false === preg_match( '/Repository review branch:\s*`([^`]+)`/', $readme, $readme_match ) || empty( $readme_match[1] ) ) {
	t20_r12_fail( 'README.md does not expose a canonical repository review branch.' );
}
if ( false === preg_match( '/Working review branch:\s*`([^`]+)`/', $status, $status_match ) || empty( $status_match[1] ) ) {
	t20_r12_fail( 'STATUS.md does not expose a working review branch.' );
}
if ( $readme_match[1] !== $status_match[1] ) {
	t20_r12_fail( 'README.md and STATUS.md disagree about the current review branch.' );
}
if ( false !== strpos( $readme, 'current identity is the T19 section above' ) ) {
	t20_r12_fail( 'README.md still labels T19 as the current identity.' );
}
if ( false !== strpos( $status, 'Review discipline: **T19 ' ) ) {
	t20_r12_fail( 'STATUS.md still labels T19 as the current review discipline.' );
}

$ci_branch = getenv( 'GITHUB_HEAD_REF' );
if ( ! $ci_branch ) {
	$ci_branch = getenv( 'GITHUB_REF_NAME' );
}
if ( $ci_branch && 0 === strpos( $ci_branch, 'review/file08-t' ) && $readme_match[1] !== $ci_branch ) {
	t20_r12_fail( 'documented review branch does not match the active GitHub review branch.' );
}

echo "T20 R12 release-evidence identity regressions passed.\n";
