<?php
$root = dirname( __DIR__ );
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
$trace = file_get_contents( $root . '/docs/MASTER-PLAN-TRACEABILITY-1.0.0.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$manifest = file_get_contents( $root . '/MANIFEST.md' );
$build = file_get_contents( $root . '/tools/build-candidate.php' );
$verify = file_get_contents( $root . '/tools/verify-candidate.php' );
$current_branch = '';
if ( preg_match( '/Working review branch:\s*`([^`]+)`/', $status, $branch_match ) ) {
	$current_branch = $branch_match[1];
}
$checks = array(
	'current README core schema' => false !== strpos( $readme, 'Core schema: **3.4.0**' ),
	'current README Future24 schema contract' => false !== strpos( $readme, 'Future24 additive schema/contract: **1.1.0**' ),
	'current status branch is T19 or a later review cycle' => '' !== $current_branch && 1 === preg_match( '/^review\/file08-t(?:19|[2-9][0-9]+)-/', $current_branch ),
	'current status schema truth' => false !== strpos( $status, 'Core File 08 schema: **3.4.0**' ) && false !== strpos( $status, 'Future24 additive operational schema/contract: **1.1.0**' ),
	'traceability distinguishes document and runtime versions' => false !== strpos( $trace, 'Current repository runtime candidate:** 1.2.15' ) && false !== strpos( $trace, 'Core schema:** 3.4.0' ),
	'release status distinguishes document and runtime versions' => false !== strpos( $release, 'Runtime candidate: **1.2.15**' ) && false !== strpos( $release, 'Core schema: **3.4.0**' ),
	'original manifest is explicitly historical' => false !== strpos( $manifest, 'historical original-archive provenance' ) && false !== strpos( $manifest, 'not** the current release/candidate manifest' ),
	'candidate builder records plan identity' => false !== strpos( $build, "'plan_id' => wca_build_class_constant" ),
	'candidate builder records core schema' => false !== strpos( $build, "'core_schema_version' => wca_build_class_constant" ),
	'candidate builder records continuity schema' => false !== strpos( $build, "'continuity_schema_version' => wca_build_class_constant" ),
	'candidate builder records Future24 schema' => false !== strpos( $build, "'future24_schema_version' => wca_build_class_constant" ),
	'candidate verifier checks plan id' => false !== strpos( $verify, "'SSH-F08-PLAN-2026-v1.0' !== ( \$manifest['plan_id']" ),
	'candidate verifier re-reads source contract parity' => false !== strpos( $verify, 'Manifest/runtime source parity failed' ) && false !== strpos( $verify, "'core_schema_version' => wca_verify_constant" ),
);
foreach ( $checks as $name => $ok ) {
	if ( ! $ok ) {
		fwrite( STDERR, "T19 R3 FAIL: {$name}\n" );
		exit( 1 );
	}
}
echo "T19 R3 release identity regressions: PASS\n";
