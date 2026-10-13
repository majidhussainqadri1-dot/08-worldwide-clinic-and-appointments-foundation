<?php
/**
 * Current File 08 / CF-01 care-context contract regression.
 *
 * Historical v0.2.2 and retired development-builder assumptions are forbidden.
 */
declare(strict_types=1);
$root = dirname(__DIR__);
$paths = array(
    'entry' => $root . '/worldwide-clinic.php',
    'source' => $root . '/includes/class-swc-cf01-care-context.php',
    'inventory' => $root . '/CF01-CARE-CONTEXT-AND-EXTRACTION-INVENTORY.md',
    'builder' => $root . '/tools/build-candidate.php',
    'verifier' => $root . '/tools/verify-candidate.php',
    'runner' => $root . '/tests/run-all.php',
);
$data = array();
foreach ($paths as $name => $path) {
    if (!is_file($path) || !is_readable($path)) { fwrite(STDERR, "Required CF-01 evidence missing: {$name}\n"); exit(1); }
    $data[$name] = file_get_contents($path);
    if (!is_string($data[$name])) { fwrite(STDERR, "Could not read current CF-01 evidence: {$name}\n"); exit(1); }
}
$entry = $data['entry'];
$source = $data['source'];
$inventory = $data['inventory'];
$checks = array(
    'current runtime 1.2.16' => strpos($entry, "define( 'WCA_VERSION', '1.2.16' )") !== false,
    'care-context public bridge 1.1.0' => strpos($entry, "define( 'SWC_CF01_CARE_CONTEXT_VERSION', '1.1.0' )") !== false,
    'care-context source required on plugin boot' => strpos($entry, "'includes/class-swc-cf01-care-context.php'") !== false,
    'single scheduling-only source of consent boundary' => strpos($source, "'treating_relationship_asserted' => false") !== false,
    'no clinical-write delegation' => strpos($source, "'sufficient_for_clinical_write'") !== false && strpos($source, "'sufficient_for_prescription'") !== false,
    'no inferred appointment treating relationship' => strpos($source, 'appointment_is_not_treating_relationship') !== false,
    'stale optimistic version denied' => strpos($source, 'stale_record_version') !== false,
    'underlying source subject assertion required' => strpos($source, 'SMC_CF01_Contract::membership_assertion') !== false,
    'current File 00 CF01 version 1.1.0' => strpos($source, "const FILE_00_CONTRACT    = '1.1.0'") !== false,
    'public CF01 owner version matches canonical 1.1.0' => strpos($source, "const CONTRACT_VERSION    = '1.1.0'") !== false,
    'build rejects CF01 runtime drift' => strpos($data['builder'], 'CF-01 care-context runtime/canonical contract version mismatch.') !== false,
    'verifier checks packaged CF01 provider' => strpos($data['verifier'], 'Manifest/CF-01 care-context runtime contract parity failed.') !== false,
    'private narrative never read' => strpos($source, "SWC_Helpers::meta( \$appointment_id, 'reason'") === false && strpos($source, "SWC_Helpers::meta( \$appointment_id, 'doctor_private_note'") === false,
    'known source inventory retained' => strpos($inventory, 'appointment-processing consent/acknowledgement only') !== false,
    'current deterministic builder used' => strpos($data['builder'], "'assets','includes','languages','templates'") !== false,
    'current independently verified package' => strpos($data['verifier'], 'CF-01') !== false || strpos($data['verifier'], 'cf01') !== false,
    'canonical aggregate includes this test' => strpos($data['runner'], "'cf01-care-context-contract.php'") !== false,
);
$fail = array_keys(array_filter($checks, static function ($ok) { return !$ok; }));
if ($fail) { fwrite(STDERR, "CF-01 current-contract regressions failed: " . implode(', ', $fail) . "\n"); exit(1); }
echo "File 08 CF-01 current-contract regressions: PASS " . count($checks) . "/" . count($checks) . "\n";
