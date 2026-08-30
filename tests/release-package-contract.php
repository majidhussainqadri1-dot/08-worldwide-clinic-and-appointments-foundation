<?php
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function f08pkg_file( $root, $path ) { global $failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); if(!is_string($data)){ $failures[]='Unreadable '.$path; return ''; } return $data; }
function f08pkg_has( $label, $source, $needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function f08pkg_lacks( $label, $source, $needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$plugin=f08pkg_file($root,'worldwide-clinic.php'); $builder=f08pkg_file($root,'tools/build-candidate.php'); $verifier=f08pkg_file($root,'tools/verify-candidate.php'); $workflow=f08pkg_file($root,'.github/workflows/file08-complete-quality.yml');
f08pkg_has('runtime source',$plugin,'Version: 1.2.15');
f08pkg_has('runtime source',$plugin,"define( 'WCA_VERSION', '1.2.15' )");
f08pkg_has('builder',$builder,'wca_build_runtime_version');
f08pkg_has('builder',$builder,'Plugin header/runtime version mismatch.');
f08pkg_has('builder',$builder,"preg_match( '/^[0-9a-f]{40}$/'");
f08pkg_has('builder',$builder,"'version' => \$version");
f08pkg_has('builder',$builder,"'runtime_version_source' => 'worldwide-clinic.php'");
f08pkg_lacks('builder',$builder,"\$version = '1.0.1';");
f08pkg_lacks('builder',$builder,"preg_replace( '/[^0-9a-f]/i'");
f08pkg_has('verifier',$verifier,"array( 'artifact:', 'commit:' )");
f08pkg_has('verifier',$verifier,'Manifest policy/commit mismatch.');
f08pkg_has('verifier',$verifier,'Artifact filename/runtime version mismatch.');
f08pkg_has('verifier',$verifier,'Manifest/plugin runtime version mismatch.');
f08pkg_has('verifier',$verifier,'Unsafe or duplicate manifest path.');
f08pkg_has('verifier',$verifier,'ZIP contains missing or unmanifested payload entries.');
f08pkg_lacks('verifier',$verifier,"'1.0.1'!==");
f08pkg_has('workflow',$workflow,'version=$(php -r');
f08pkg_has('workflow',$workflow,"--commit='\${{ steps.source.outputs.sha }}'");
f08pkg_has('workflow',$workflow,'file-08-${{ steps.source.outputs.version }}-candidate-${{ steps.source.outputs.sha }}');
if($failures){ fwrite(STDERR,"Release-package contract failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo "Release-package runtime/commit parity assertions passed: {$checks}/{$checks}.\n";
