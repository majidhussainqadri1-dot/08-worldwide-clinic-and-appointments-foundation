<?php
/** Independent candidate verifier. */
if ( ! class_exists( 'ZipArchive' ) ) { fwrite( STDERR, "ZipArchive is required.\n" ); exit( 2 ); }
$options = getopt( '', array( 'artifact:', 'commit:' ) );
$dir = realpath( $options['artifact'] ?? '' );
$expectedCommit = strtolower( trim( (string) ( $options['commit'] ?? '' ) ) );
if ( ! $dir || ! preg_match( '/^[0-9a-f]{40}$/', $expectedCommit ) ) { fwrite( STDERR, "Artifact directory and exact 40-hex commit are required.\n" ); exit( 2 ); }
$zips = glob( $dir . '/*-candidate.zip' );
$manifests = glob( $dir . '/*-candidate-manifest.json' );
$checksums = glob( $dir . '/*-candidate.sha256' );
if ( 1 !== count( $zips ) || 1 !== count( $manifests ) || 1 !== count( $checksums ) ) { fwrite( STDERR, "Artifact set is incomplete or ambiguous.\n" ); exit( 3 ); }
$zipPath = $zips[0];
$manifestPath = $manifests[0];
$checksumPath = $checksums[0];
$line = trim( (string) file_get_contents( $checksumPath ) );
$parts = preg_split( '/\s+/', $line, 2 );
$expected = $parts[0] ?? '';
$name = trim( $parts[1] ?? '' );
if ( ! preg_match( '/^[0-9a-f]{64}$/i', $expected ) || basename( $zipPath ) !== $name || ! hash_equals( strtolower( $expected ), hash_file( 'sha256', $zipPath ) ) ) {
	fwrite( STDERR, "Checksum mismatch.\n" ); exit( 4 );
}
$manifest = json_decode( (string) file_get_contents( $manifestPath ), true );
$version = is_array( $manifest ) ? (string) ( $manifest['version'] ?? '' ) : '';
$manifestCommit = is_array( $manifest ) ? strtolower( (string) ( $manifest['commit'] ?? '' ) ) : '';
$versionKeys = array(
	'runtime_contract_version',
	'core_schema_version',
	'public_clinic_contract_version',
	'cf01_context_contract_version',
	'continuity_schema_version',
	'continuity_contract_version',
	'future24_schema_version',
	'future24_contract_version',
);
$versionsValid = true;
if ( is_array( $manifest ) ) {
	foreach ( $versionKeys as $key ) {
		if ( ! preg_match( '/^\d+\.\d+\.\d+$/', (string) ( $manifest[ $key ] ?? '' ) ) ) {
			$versionsValid = false;
			break;
		}
	}
}
if (
	! is_array( $manifest ) ||
	'wca-candidate-manifest-1' !== ( $manifest['format'] ?? '' ) ||
	'08-worldwide-clinic-and-appointments' !== ( $manifest['plugin'] ?? '' ) ||
	'worldwide-clinic.php' !== ( $manifest['runtime_version_source'] ?? '' ) ||
	'SSH-F08-PLAN-2026-v1.0' !== ( $manifest['plan_id'] ?? '' ) ||
	! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version ) ||
	! $versionsValid ||
	! hash_equals( $version, (string) ( $manifest['runtime_contract_version'] ?? '' ) ) ||
	! preg_match( '/^[0-9a-f]{40}$/', $manifestCommit ) ||
	! hash_equals( $expectedCommit, $manifestCommit ) ||
	(int) ( $manifest['source_date_epoch'] ?? 0 ) < 1 ||
	0 !== ( $manifest['commission_percent'] ?? -1 ) ||
	! empty( $manifest['staging_accepted'] ) ||
	! empty( $manifest['production_accepted'] )
) {
	fwrite( STDERR, "Manifest policy/commit/schema mismatch.\n" ); exit( 5 );
}
$base = '08-worldwide-clinic-and-appointments-' . $version . '-candidate';
if ( basename( $zipPath ) !== $base . '.zip' || basename( $manifestPath ) !== $base . '-manifest.json' || basename( $checksumPath ) !== $base . '.sha256' ) {
	fwrite( STDERR, "Artifact filename/runtime version mismatch.\n" ); exit( 5 );
}
$zip = new ZipArchive();
if ( true !== $zip->open( $zipPath ) ) { fwrite( STDERR, "ZIP open failed.\n" ); exit( 6 ); }
$prefix = '08-worldwide-clinic-and-appointments/';
$seen = array();
for ( $i = 0; $i < $zip->numFiles; $i++ ) {
	$stat = $zip->statIndex( $i );
	$entry = (string) $stat['name'];
	if ( 0 !== strpos( $entry, $prefix ) || false !== strpos( $entry, '../' ) || isset( $seen[ $entry ] ) ) {
		fwrite( STDERR, "Unsafe or duplicate entry: {$entry}\n" ); exit( 7 );
	}
	$seen[ $entry ] = true;
}
$embedded = $zip->getFromName( $prefix . 'WCA-CANDIDATE-MANIFEST.json' );
if ( ! is_string( $embedded ) || ! hash_equals( hash( 'sha256', (string) file_get_contents( $manifestPath ) ), hash( 'sha256', $embedded ) ) ) {
	fwrite( STDERR, "Manifest parity failed.\n" ); exit( 8 );
}
$expectedEntries = array( $prefix . 'WCA-CANDIDATE-MANIFEST.json' => true );
$manifestPaths = array();
foreach ( (array) ( $manifest['files'] ?? array() ) as $file ) {
	$path = (string) ( $file['path'] ?? '' );
	if ( ! $path || 0 === strpos( $path, '/' ) || false !== strpos( $path, '../' ) || false !== strpos( $path, "\0" ) || isset( $manifestPaths[ $path ] ) ) {
		fwrite( STDERR, "Unsafe or duplicate manifest path.\n" ); exit( 9 );
	}
	$manifestPaths[ $path ] = true;
	$entry = $prefix . $path;
	$expectedEntries[ $entry ] = true;
	$data = $zip->getFromName( $entry );
	if ( ! is_string( $data ) || strlen( $data ) !== (int) ( $file['bytes'] ?? -1 ) || ! hash_equals( (string) ( $file['sha256'] ?? '' ), hash( 'sha256', $data ) ) ) {
		fwrite( STDERR, "Payload verification failed: {$path}\n" ); exit( 9 );
	}
}
ksort( $expectedEntries, SORT_STRING );
ksort( $seen, SORT_STRING );
if ( array_keys( $expectedEntries ) !== array_keys( $seen ) ) {
	fwrite( STDERR, "ZIP contains missing or unmanifested payload entries.\n" ); exit( 10 );
}
$plugin = $zip->getFromName( $prefix . 'worldwide-clinic.php' );
$contracts = $zip->getFromName( $prefix . 'includes/class-wca-contracts.php' );
$continuity = $zip->getFromName( $prefix . 'includes/class-wca-continuity-secure.php' );
$future24 = $zip->getFromName( $prefix . 'includes/class-wca-future24.php' );
if ( ! is_string( $plugin ) || ! is_string( $contracts ) || ! is_string( $continuity ) || ! is_string( $future24 ) ) { fwrite( STDERR, "Runtime contract payload is missing.\n" ); exit( 11 ); }
$header = '';
$constant = '';
if ( preg_match( '/^\s*\*\s*Version:\s*([^\s]+)/m', $plugin, $match ) ) { $header = trim( $match[1] ); }
if ( preg_match( "/define\(\s*'WCA_VERSION'\s*,\s*'([^']+)'\s*\)/", $plugin, $match ) ) { $constant = trim( $match[1] ); }
if ( ! $header || ! $constant || ! hash_equals( $version, $header ) || ! hash_equals( $version, $constant ) ) {
	fwrite( STDERR, "Manifest/plugin runtime version mismatch.\n" ); exit( 12 );
}
function wca_verify_constant( $source, $constant ) {
	$pattern = '/\bconst\s+' . preg_quote( $constant, '/' ) . '\s*=\s*[\'\"]([^\'\"]+)[\'\"]\s*;/';
	return preg_match( $pattern, $source, $match ) ? trim( $match[1] ) : '';
}
$sourceParity = array(
	'plan_id' => wca_verify_constant( $contracts, 'PLAN_ID' ),
	'runtime_contract_version' => wca_verify_constant( $contracts, 'RUNTIME_VERSION' ),
	'core_schema_version' => wca_verify_constant( $contracts, 'SCHEMA_VERSION' ),
	'public_clinic_contract_version' => wca_verify_constant( $contracts, 'PUBLIC_CLINIC_CONTRACT_VERSION' ),
	'cf01_context_contract_version' => wca_verify_constant( $contracts, 'CF01_CONTEXT_CONTRACT_VERSION' ),
	'continuity_schema_version' => wca_verify_constant( $continuity, 'SCHEMA_VERSION' ),
	'continuity_contract_version' => wca_verify_constant( $continuity, 'CONTRACT_VERSION' ),
	'future24_schema_version' => wca_verify_constant( $future24, 'SCHEMA_VERSION' ),
	'future24_contract_version' => wca_verify_constant( $future24, 'CONTRACT_VERSION' ),
);
foreach ( $sourceParity as $key => $value ) {
	if ( '' === $value || ! hash_equals( (string) ( $manifest[ $key ] ?? '' ), $value ) ) {
		fwrite( STDERR, "Manifest/runtime source parity failed: {$key}\n" );
		exit( 13 );
	}
}
$zip->close();
echo "Candidate verified: " . basename( $zipPath ) . " (runtime {$version}, core schema " . $manifest['core_schema_version'] . ", commit {$manifestCommit})\n";