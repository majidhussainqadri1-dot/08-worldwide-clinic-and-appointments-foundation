from pathlib import Path


def replace_once(path, old, new):
    p = Path(path)
    s = p.read_text()
    if old not in s:
        raise SystemExit(f"missing expected text in {path}: {old[:120]}")
    p.write_text(s.replace(old, new, 1))


replace_once(
    'includes/class-swc-public-clinic.php',
    "const CONTRACT_VERSION = '1.0.0';",
    "const CONTRACT_VERSION = '1.1.0';"
)

p = Path('PUBLIC-CLINIC-PROJECTION-CONTRACT.md')
s = p.read_text()
s = s.replace('# File 08 Public Clinic Projection Contract 1.0.0', '# File 08 Public Clinic Projection Contract 1.1.0', 1)
s = s.replace("'contract_version' => '1.0.0'", "'contract_version' => '1.1.0'", 1)
s = s.replace(
    'Source tests and green CI establish code-level contract evidence only.',
    'Source tests and green CI establish code-level contract evidence only. Candidate build and independent verification must also prove that this runtime projection contract version equals the canonical `WCA_Contracts::PUBLIC_CLINIC_CONTRACT_VERSION` embedded in the same exact artifact.'
)
p.write_text(s)

replace_once(
    'tools/build-candidate.php',
    "if ( ! hash_equals( $version, $contractVersions['runtime_contract_version'] ) ) {\n\tfwrite( STDERR, \"Plugin/runtime contract version mismatch.\\n\" );\n\texit( 2 );\n}",
    "if ( ! hash_equals( $version, $contractVersions['runtime_contract_version'] ) ) {\n\tfwrite( STDERR, \"Plugin/runtime contract version mismatch.\\n\" );\n\texit( 2 );\n}\n$publicClinicProjectionVersion = wca_build_class_constant( $root, 'includes/class-swc-public-clinic.php', 'CONTRACT_VERSION' );\nif ( ! hash_equals( $contractVersions['public_clinic_contract_version'], $publicClinicProjectionVersion ) ) {\n\tfwrite( STDERR, \"Public Clinic runtime/canonical contract version mismatch.\\n\" );\n\texit( 2 );\n}"
)

replace_once(
    'tools/verify-candidate.php',
    "$future24 = $zip->getFromName( $prefix . 'includes/class-wca-future24.php' );\nif ( ! is_string( $plugin ) || ! is_string( $contracts ) || ! is_string( $continuity ) || ! is_string( $future24 ) ) { fwrite( STDERR, \"Runtime contract payload is missing.\\n\" ); exit( 11 ); }",
    "$future24 = $zip->getFromName( $prefix . 'includes/class-wca-future24.php' );\n$publicClinic = $zip->getFromName( $prefix . 'includes/class-swc-public-clinic.php' );\nif ( ! is_string( $plugin ) || ! is_string( $contracts ) || ! is_string( $continuity ) || ! is_string( $future24 ) || ! is_string( $publicClinic ) ) { fwrite( STDERR, \"Runtime contract payload is missing.\\n\" ); exit( 11 ); }"
)
replace_once(
    'tools/verify-candidate.php',
    "foreach ( $sourceParity as $key => $value ) {\n\tif ( '' === $value || ! hash_equals( (string) ( $manifest[ $key ] ?? '' ), $value ) ) {\n\t\tfwrite( STDERR, \"Manifest/runtime source parity failed: {$key}\\n\" );\n\t\texit( 13 );\n\t}\n}\n$zip->close();",
    "foreach ( $sourceParity as $key => $value ) {\n\tif ( '' === $value || ! hash_equals( (string) ( $manifest[ $key ] ?? '' ), $value ) ) {\n\t\tfwrite( STDERR, \"Manifest/runtime source parity failed: {$key}\\n\" );\n\t\texit( 13 );\n\t}\n}\n$publicClinicVersion = wca_verify_constant( $publicClinic, 'CONTRACT_VERSION' );\nif ( '' === $publicClinicVersion || ! hash_equals( (string) ( $manifest['public_clinic_contract_version'] ?? '' ), $publicClinicVersion ) ) {\n\tfwrite( STDERR, \"Manifest/Public Clinic runtime contract parity failed.\\n\" );\n\texit( 14 );\n}\n$zip->close();"
)

Path('tests/t26-r1-public-clinic-contract-parity-regressions.php').write_text('''<?php
$root = dirname( __DIR__ );
$canonical = file_get_contents( $root . '/includes/class-wca-contracts.php' );
$projection = file_get_contents( $root . '/includes/class-swc-public-clinic.php' );
$doc = file_get_contents( $root . '/PUBLIC-CLINIC-PROJECTION-CONTRACT.md' );
$builder = file_get_contents( $root . '/tools/build-candidate.php' );
$verifier = file_get_contents( $root . '/tools/verify-candidate.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
$ledger = file_get_contents( $root . '/docs/T26-R1-FROZEN-LEDGER.md' );
foreach ( array( $canonical, $projection, $doc, $builder, $verifier, $runner, $ledger ) as $source ) {
    if ( ! is_string( $source ) ) { fwrite( STDERR, "T26 R1 source read failed\n" ); exit( 1 ); }
}
$checks = array(
    'canonical Public Clinic contract is 1.1.0' => false !== strpos( $canonical, "PUBLIC_CLINIC_CONTRACT_VERSION  = '1.1.0'" ),
    'runtime public projection contract is 1.1.0' => false !== strpos( $projection, "const CONTRACT_VERSION = '1.1.0';" ),
    'dedicated contract document is 1.1.0' => false !== strpos( $doc, '# File 08 Public Clinic Projection Contract 1.1.0' ) && false !== strpos( $doc, "'contract_version' => '1.1.0'" ),
    'builder checks runtime projection parity' => false !== strpos( $builder, "class-swc-public-clinic.php', 'CONTRACT_VERSION'" ) && false !== strpos( $builder, 'Public Clinic runtime/canonical contract version mismatch.' ),
    'verifier opens packaged projection contract' => false !== strpos( $verifier, "includes/class-swc-public-clinic.php" ) && false !== strpos( $verifier, 'Manifest/Public Clinic runtime contract parity failed.' ),
    'R1 ledger proves review-first discipline' => false !== strpos( $ledger, 'No T26 R1 correction was started while the review remained open' ) && false !== strpos( $ledger, 'R1-D1' ),
    'T26 R1 regression bound into aggregate runner' => false !== strpos( $runner, 't26-r1-public-clinic-contract-parity-regressions.php' ),
);
$bad = array(); foreach ( $checks as $name => $ok ) { if ( ! $ok ) { $bad[] = $name; } }
if ( $bad ) { fwrite( STDERR, "T26 R1 public-clinic contract parity regressions failed:\n- " . implode( "\n- ", $bad ) . "\n" ); exit( 1 ); }
echo 'T26 R1 public-clinic contract parity regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
''')

p = Path('tests/run-all.php')
s = p.read_text()
needle = "'t25-r10-release-closure-regressions.php'"
if "'t26-r1-public-clinic-contract-parity-regressions.php'" not in s:
    if needle not in s:
        raise SystemExit('run-all insertion point missing')
    p.write_text(s.replace(needle, needle + ", 't26-r1-public-clinic-contract-parity-regressions.php'", 1))
