<?php
/** Independent candidate verifier. */
if ( ! class_exists( 'ZipArchive' ) ) { fwrite( STDERR, "ZipArchive is required.\n" ); exit( 2 ); }
$options = getopt( '', array( 'artifact:' ) );
$dir = realpath( $options['artifact'] ?? '' );
if ( ! $dir ) { fwrite( STDERR, "Artifact directory is required.\n" ); exit( 2 ); }
$zips = glob( $dir . '/*-candidate.zip' );
$manifests = glob( $dir . '/*-candidate-manifest.json' );
$checksums = glob( $dir . '/*-candidate.sha256' );
if ( 1 !== count( $zips ) || 1 !== count( $manifests ) || 1 !== count( $checksums ) ) { fwrite( STDERR, "Artifact set is incomplete or ambiguous.\n" ); exit( 3 ); }
$zipPath=$zips[0]; $manifestPath=$manifests[0]; $checksumPath=$checksums[0];
$line=trim(file_get_contents($checksumPath));
list($expected,$name)=preg_split('/\s+/', $line, 2);
if ( basename($zipPath)!==trim($name) || !hash_equals($expected,hash_file('sha256',$zipPath)) ) { fwrite(STDERR,"Checksum mismatch.\n"); exit(4); }
$manifest=json_decode(file_get_contents($manifestPath),true);
if(!is_array($manifest)||'1.0.1'!==($manifest['version']??'')||0!==($manifest['commission_percent']??-1)||!empty($manifest['staging_accepted'])||!empty($manifest['production_accepted'])){fwrite(STDERR,"Manifest policy mismatch.\n");exit(5);}
$zip=new ZipArchive(); if(true!==$zip->open($zipPath)){fwrite(STDERR,"ZIP open failed.\n");exit(6);}
$prefix='08-worldwide-clinic-and-appointments/'; $seen=array();
for($i=0;$i<$zip->numFiles;$i++){
	$stat=$zip->statIndex($i); $entry=$stat['name'];
	if(0!==strpos($entry,$prefix)||false!==strpos($entry,'../')||isset($seen[$entry])){fwrite(STDERR,"Unsafe or duplicate entry: {$entry}\n");exit(7);} $seen[$entry]=true;
}
$embedded=$zip->getFromName($prefix.'WCA-CANDIDATE-MANIFEST.json');
if(!is_string($embedded)||!hash_equals(hash('sha256',file_get_contents($manifestPath)),hash('sha256',$embedded))){fwrite(STDERR,"Manifest parity failed.\n");exit(8);}
foreach($manifest['files'] as $file){$entry=$prefix.$file['path'];$data=$zip->getFromName($entry);if(!is_string($data)||strlen($data)!==(int)$file['bytes']||!hash_equals($file['sha256'],hash('sha256',$data))){fwrite(STDERR,"Payload verification failed: {$file['path']}\n");exit(9);}}
$zip->close(); echo "Candidate verified: ".basename($zipPath)."\n";
