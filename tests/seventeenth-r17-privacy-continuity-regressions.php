<?php
$root = dirname(__DIR__);
$continuity = file_get_contents($root . '/includes/class-wca-continuity-secure.php');
$guards = file_get_contents($root . '/includes/class-wca-continuity-guards.php');
$future = file_get_contents($root . '/includes/class-wca-future24.php');
if (!is_string($continuity) || !is_string($guards) || !is_string($future)) { fwrite(STDERR, "R17 source read failed\n"); exit(1); }
$checks = array(
    'intake consent before persistence' => strpos($continuity, 'appointment_processing') !== false && strpos($continuity, 'before pre-visit information is stored') !== false,
    'canonical intake version required' => strpos($continuity, 'wca_intake_version_required') !== false && strpos($continuity, 'if ( ! $expected_version )') !== false,
    'processing consent controllable' => strpos($continuity, "array( 'appointment_processing', 'teleconsult'") !== false && strpos($guards, "array( 'appointment_processing', 'teleconsult'") !== false,
    'future24 schema migration bumped' => strpos($future, "const SCHEMA_VERSION   = '1.1.0';") !== false,
    'episode privacy subject persisted' => strpos($future, 'subject_user_id') !== false && strpos($future, 'patient_id') !== false,
    'legacy episode subject backfill' => strpos($future, "feature_id='F08-FUT-23' AND subject_user_id=0") !== false,
    'episode unlink route' => strpos($future, '/future24/episodes/(?P<ref>[0-9a-fA-F-]{36})/unlink') !== false,
    'episode archive route' => strpos($future, '/future24/episodes/(?P<ref>[0-9a-fA-F-]{36})/archive') !== false,
    'episode mutation optimistic concurrency' => strpos($future, 'wca_episode_version_required') !== false && strpos($future, 'wca_episode_stale') !== false,
    'fhir adapter degraded toggle' => strpos($future, 'wca_fhir_adapter_enabled') !== false && strpos($future, 'wca_fhir_adapter_disabled') !== false,
    'fhir appointment version metadata' => strpos($future, 'versionId') !== false && strpos($future, 'lastUpdated') !== false && strpos($future, '$record_version') !== false,
    'fhir clinic version metadata' => strpos($future, 'record_version') !== false && strpos($future, 'fhir-healthcare-service') !== false,
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "R17 FAIL: {$name}\n"); exit(1); } }
echo "R17 privacy/continuity/interoperability regressions: PASS\n";
