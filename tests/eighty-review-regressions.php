<?php
/**
 * File 08 eighty-round corrective source regression gate.
 * Repository/source evidence only; staging/live acceptance is a separate gate.
 */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function f08r_src( $path ) {
	global $root, $failures;
	$file = $root . '/' . $path;
	if ( ! is_file( $file ) ) { $failures[] = 'Missing ' . $path; return ''; }
	$data = file_get_contents( $file );
	if ( ! is_string( $data ) ) { $failures[] = 'Unreadable ' . $path; return ''; }
	return $data;
}
function f08r_has( $label, $source, $needle ) {
	global $failures, $checks; $checks++;
	if ( false === strpos( $source, $needle ) ) { $failures[] = $label . ' missing: ' . $needle; }
}
function f08r_lacks( $label, $source, $needle ) {
	global $failures, $checks; $checks++;
	if ( false !== strpos( $source, $needle ) ) { $failures[] = $label . ' forbidden: ' . $needle; }
}
function f08r_true( $label, $condition ) {
	global $failures, $checks; $checks++;
	if ( ! $condition ) { $failures[] = $label; }
}

$bootstrap = f08r_src( 'worldwide-clinic.php' );
$contracts = f08r_src( 'includes/class-wca-contracts.php' );
$auth = f08r_src( 'includes/class-wca-authorization.php' );
$repo = f08r_src( 'includes/class-wca-repository.php' );
$service = f08r_src( 'includes/class-wca-service.php' );
$future = f08r_src( 'includes/class-wca-future24.php' );
$privacy = f08r_src( 'includes/class-wca-privacy.php' );
$readme = f08r_src( 'readme.txt' );

// R78: coherent release identity after corrective source changes.
foreach ( array( 'Version: 1.2.1', "WCA_VERSION', '1.2.1'" ) as $token ) { f08r_has( 'bootstrap runtime', $bootstrap, $token ); }
f08r_has( 'contract runtime', $contracts, "RUNTIME_VERSION                 = '1.2.1'" );
f08r_has( 'readme runtime', $readme, 'Stable tag: 1.2.1' );

// R08/R13/R14: current guardian truth and least-privilege staff delegation.
foreach ( array( 'guardian_context', 'can_staff_access_appointment', 'clinic_manage', 'delegation_allows_scope' ) as $token ) { f08r_has( 'authorization', $auth, $token ); }
f08r_has( 'future current guardian state', $future, 'current_guardian_state' );
f08r_has( 'future patient context', $future, 'patient_context' );
f08r_has( 'service guardian revalidation', $service, 'WCA_Authorization::guardian_context' );

// R10-R33: Future24 scope, state, safety, and interoperability boundaries.
foreach ( array(
	'wca_future24_service_scope', 'wca_future24_branch_scope', 'wca_future24_resource_scope',
	'wca_future24_waitlist_window', 'wca_future24_windows', 'wca_future24_series_count',
	'wca_future24_group_scope', 'wca_future24_questionnaire_scope', 'wca_future24_prerequisite',
	'wca_future24_disruption', 'wca_future24_support', 'wca_future24_virtual',
	'wca_future24_smart', 'wca_future24_episode'
) as $token ) { f08r_has( 'Future24 validation', $future, $token ); }
f08r_has( 'group join body preserved', $future, 'rest_group_join( WP_REST_Request $r ){ $d=self::data($r);' );
f08r_has( 'virtual recording fail-safe', $future, "'recording' => false" );
f08r_has( 'no patient scoring', $future, "'patient_scoring' => false" );
foreach ( array( 'fhir', 'smart', 'external_busy', 'episode', 'virtual_room' ) as $token ) { f08r_has( 'interoperability boundary', strtolower( $future ), $token ); }
f08r_has( 'no automated diagnosis', $contracts, "'automated_diagnosis' => false" );
f08r_has( 'no automated prescribing', $contracts, "'automated_prescribing' => false" );
f08r_has( 'zero commission', $contracts, "'commission_percent' => 0" );
f08r_has( 'no donor visibility advantage', $contracts, "'donation_visibility_link' => false" );

// R43: true idempotency ownership, in-progress refusal, replay/release paths.
foreach ( array( 'claimed_new', 'release_idempotency', 'processing', 'DATE_SUB', 'idempotency_key' ) as $token ) { f08r_has( 'repository idempotency', $repo, $token ); }
f08r_has( 'appointment concurrent refusal', $service, 'wca_idempotency_in_progress' );
f08r_has( 'appointment claim ownership', $service, "empty( $claim['claimed_new'] )" );
f08r_has( 'appointment failure releases claim', $service, 'WCA_Repository::release_idempotency' );
f08r_has( 'Future24 mutation wrapper', $future, 'private static function mutate' );
f08r_has( 'Future24 concurrent refusal', $future, 'wca_idempotency_in_progress' );
f08r_has( 'Future24 request fingerprint', $future, 'request_fingerprint' );
f08r_has( 'Future24 completion ledger', $future, 'complete_idempotency' );

// R44-R46: privacy graph, real metric column, Future24 legal-hold inheritance.
f08r_has( 'doctor erasure coverage', $privacy, "'_swc_doctor_id'" );
f08r_has( 'Future24 erasure cursor', $privacy, "'_future24'" );
f08r_has( 'metrics retention real column', $privacy, 'metric_bucket < %s' );
f08r_lacks( 'metrics stale column', $privacy, 'bucket_at < %s' );
f08r_has( 'Future24 legal hold', $privacy, 'future24_legal_hold' );
f08r_has( 'Future24 legal hold filter', $privacy, 'wca_future24_legal_hold' );
f08r_has( 'linked appointment hold inheritance', $privacy, "row['appointment_id']" );

for ( $i = 1; $i <= 24; $i++ ) { f08r_has( 'Future24 capability ' . $i, $future, sprintf( 'F08-FUT-%02d', $i ) ); }
f08r_true( 'Future24 manifest must declare exactly 24 capability IDs', 24 === preg_match_all( "/'F08-FUT-[0-9]{2}'\\s*=>/", $future ) );

$runtime = implode( "\n", array( $auth, $repo, $service, $future, $privacy, $bootstrap ) );
foreach ( array( 'eval(', 'base64_decode(', 'shell_exec(', 'unserialize(' ) as $token ) { f08r_lacks( 'forbidden runtime primitive', $runtime, $token ); }
f08r_true( 'Review80 staging directory must be absent', ! is_dir( $root . '/.codex/review80' ) );
f08r_true( 'One-shot correction workflow must be absent', ! is_file( $root . '/.github/workflows/apply-file08-forty-v102.yml' ) );

if ( $failures ) {
	fwrite( STDERR, "File 08 eighty-round regression gate failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}
echo "File 08 eighty-round corrective regression assertions passed: {$checks}/{$checks}.\n";
