<?php
/** Deterministic File 08 candidate builder. */
if ( ! class_exists( 'ZipArchive' ) ) { fwrite( STDERR, "ZipArchive is required.\n" ); exit( 2 ); }
$options = getopt( '', array( 'root::', 'output-dir::', 'commit:', 'source-date-epoch:' ) );
$root = realpath( $options['root'] ?? dirname( __DIR__ ) );
$out  = $options['output-dir'] ?? $root . '/build';
$commit = preg_replace( '/[^0-9a-f]/i', '', (string) ( $options['commit'] ?? '' ) );
$epoch = (int) ( $options['source-date-epoch'] ?? 0 );
if ( ! $root || strlen( $commit ) < 7 || $epoch < 1 ) { fwrite( STDERR, "Valid root, commit and source-date-epoch are required.\n" ); exit( 2 ); }

function wca_build_runtime_version( $root ) {
	$plugin = $root . '/worldwide-clinic.php';
	$source = is_file( $plugin ) ? file_get_contents( $plugin ) : false;
	if ( ! is_string( $source ) ) {
		fwrite( STDERR, "Runtime plugin source is unavailable.\n" );
		exit( 2 );
	}
	$header = '';
	$constant = '';
	if ( preg_match( '/^\s*\*\s*Version:\s*([^\s]+)/m', $source, $match ) ) {
		$header = trim( $match[1] );
	}
	if ( preg_match( "/define\(\s*'WCA_VERSION'\s*,\s*'([^']+)'\s*\)/", $source, $match ) ) {
		$constant = trim( $match[1] );
	}
	if ( ! $header || ! $constant || ! hash_equals( $header, $constant ) ) {
		fwrite( STDERR, "Plugin header/runtime version mismatch.\n" );
		exit( 2 );
	}
	if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $header ) ) {
		fwrite( STDERR, "Runtime version is not a supported semantic version.\n" );
		exit( 2 );
	}
	return $header;
}

@mkdir( $out, 0775, true );
$version = wca_build_runtime_version( $root );
$base = '08-worldwide-clinic-and-appointments-' . $version . '-candidate';
$zipPath = rtrim( $out, '/' ) . '/' . $base . '.zip';
$manifestPath = rtrim( $out, '/' ) . '/' . $base . '-manifest.json';
$checksumPath = rtrim( $out, '/' ) . '/' . $base . '.sha256';
$allowRoots = array( 'assets','includes','languages','templates' );
$allowFiles = array( 'worldwide-clinic.php','readme.txt','uninstall.php' );
$files = array();
foreach ( $allowFiles as $file ) { if ( is_file( $root . '/' . $file ) ) { $files[] = $file; } }
foreach ( $allowRoots as $dir ) {
	if ( ! is_dir( $root . '/' . $dir ) ) { continue; }
	$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root . '/' . $dir, FilesystemIterator::SKIP_DOTS ) );
	foreach ( $it as $file ) {
		if ( $file->isLink() || ! $file->isFile() ) { continue; }
		$relative = str_replace( '\\', '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
		if ( preg_match( '/(?:^|\/)\.(?:git|env)|\.(?:zip|pem|key|p12|log)$/i', $relative ) ) { fwrite( STDERR, "Forbidden file: {$relative}\n" ); exit( 3 ); }
		$files[] = $relative;
	}
}
sort( $files, SORT_STRING );
$entries = array();
foreach ( $files as $file ) { $entries[] = array( 'path' => $file, 'bytes' => filesize( $root . '/' . $file ), 'sha256' => hash_file( 'sha256', $root . '/' . $file ) ); }
$manifest = array(
	'format' => 'wca-candidate-manifest-1',
	'plugin' => '08-worldwide-clinic-and-appointments',
	'version' => $version,
	'runtime_version_source' => 'worldwide-clinic.php',
	'commit' => strtolower( $commit ),
	'source_date_epoch' => $epoch,
	'built_at_utc' => gmdate( 'c', $epoch ),
	'staging_accepted' => false,
	'production_accepted' => false,
	'commission_percent' => 0,
	'files' => $entries,
);
$json = json_encode( $manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n";
file_put_contents( $manifestPath, $json );
$zip = new ZipArchive();
if ( true !== $zip->open( $zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) { fwrite( STDERR, "Cannot create ZIP.\n" ); exit( 4 ); }
$prefix = '08-worldwide-clinic-and-appointments/';
foreach ( $files as $file ) {
	$entry = $prefix . $file;
	$zip->addFile( $root . '/' . $file, $entry );
	if ( method_exists( $zip, 'setMtimeName' ) ) { $zip->setMtimeName( $entry, $epoch ); }
	if ( method_exists( $zip, 'setCompressionName' ) ) { $zip->setCompressionName( $entry, ZipArchive::CM_DEFLATE, 9 ); }
}
$zip->addFromString( $prefix . 'WCA-CANDIDATE-MANIFEST.json', $json );
if ( method_exists( $zip, 'setMtimeName' ) ) { $zip->setMtimeName( $prefix . 'WCA-CANDIDATE-MANIFEST.json', $epoch ); }
$zip->close();
$checksum = hash_file( 'sha256', $zipPath ) . '  ' . basename( $zipPath ) . "\n";
file_put_contents( $checksumPath, $checksum );
echo json_encode( array( 'version' => $version, 'zip' => $zipPath, 'manifest' => $manifestPath, 'checksum' => $checksumPath, 'files' => count( $files ), 'bytes' => filesize( $zipPath ) ), JSON_PRETTY_PRINT ) . "\n";
