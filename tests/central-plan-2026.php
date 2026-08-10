<?php
/**
 * Source-level parity suite for the 7 August 2026 central addendum and the
 * newly supplied File 08 Complete Master Plan. Runtime/staging acceptance is
 * intentionally a separate evidence gate.
 */

$root = dirname( __DIR__ );
$failures = array();

function f08_read( $path ) {
	global $root, $failures;
	$file = $root . '/' . $path;
	if ( ! is_file( $file ) ) {
		$failures[] = 'Missing required source file: ' . $path;
		return '';
	}
	$data = file_get_contents( $file );
	if ( ! is_string( $data ) ) {
		$failures[] = 'Unreadable required source file: ' . $path;
		return '';
	}
	return $data;
}

function f08_require_tokens( $label, $source, $tokens ) {
	global $failures;
	foreach ( $tokens as $token ) {
		if ( false === strpos( $source, $token ) ) {
			$failures[] = $label . ' missing token: ' . $token;
		}
	}
}

$bootstrap  = f08_read( 'worldwide-clinic.php' );
$governance = f08_read( 'includes/class-wca-central-governance.php' );
$continuity = f08_read( 'includes/class-wca-continuity-secure.php' );
$auth       = f08_read( 'includes/class-wca-authorization.php' );
$css        = f08_read( 'assets/css/clinic.css' );
$js         = f08_read( 'assets/js/continuity.js' );

f08_require_tokens( 'bootstrap', $bootstrap, array(
	'class-wca-central-governance.php',
	'class-wca-continuity-secure.php',
	'WCA_Central_Governance::boot()',
	'WCA_Continuity::boot()',
	"register_activation_hook( WCA_FILE, array( 'WCA_Continuity', 'activate' ) )",
	'wca_get_file26_clinic_projection',
) );

f08_require_tokens( 'central governance', $governance, array(
	'CEN-GOV-001', 'CEN-OWN-001', 'CEN-BIZ-001', 'CEN-DON-001', 'CEN-BRAND-001',
	'CEN-NAV-001', 'CEN-AGE-001', 'CEN-PRI-001', 'CEN-MED-001', 'CEN-RANK-001',
	'CEN-REL-001', 'CEN-STATUS-001', 'CEN-ACC-001', 'CEN-SEARCH-001',
	'F08-CEN-01', 'F08-CEN-02', '#087A4E', 'File26.SearchProjectionChanged.v1',
	'wca.file26-clinic-projection', 'paid_boost', 'donor_boost', 'outcome_rank',
	'age_guardian_claim', 'validate_patient_guardian', 'wca_age_claim_unavailable',
) );

f08_require_tokens( 'age/guardian authorization', $auth, array(
	'WCA_Central_Governance::validate_patient_guardian',
	'wca_guardian_required',
) );

f08_require_tokens( 'continuity', $continuity, array(
	'wca_previsit_intake', 'wca_followups',
	'PreVisitIntakeSubmitted.v1', 'FollowUpPlanCreated.v1', 'FollowUpPlanCompleted.v1',
	'wca.file17-clinic-context', 'messaging_allowed', 'call_allowed', 'recording_allowed',
	'File19.NotificationRequested.v1', 'appointment_processing', 'followup',
	'sodium-secretbox-v1', 'aes-256-gcm-v1', 'wca_crypto_unavailable', 'key_id',
	'wca_continuity_decryption_keys', 'wca_continuity_encryption_key',
	'wp_privacy_personal_data_exporters', 'wp_privacy_personal_data_erasers',
	'wca_continuity_legal_hold', 'apply_retention',
	'wca_emergency_diversion', 'no-store', 'noindex',
	'/continuity/appointments/', '/file17-context', '/followups', '/continuity/health',
) );

// Sensitive continuity records must never have a plaintext narrative column.
foreach ( array( 'payload longtext', 'reason text NOT NULL', 'instructions text NOT NULL', 'clinical_note text', 'resource_refs_json' ) as $unsafe_schema ) {
	if ( false !== stripos( $continuity, $unsafe_schema ) ) {
		$failures[] = 'Sensitive continuity schema contains a plaintext field: ' . $unsafe_schema;
	}
}

if ( false !== strpos( $continuity, 'base64_decode(' ) ) {
	$failures[] = 'Continuity implementation contains forbidden base64 decoding.';
}
if ( false === strpos( $continuity, 'bin2hex(' ) || false === strpos( $continuity, 'hex2bin(' ) ) {
	$failures[] = 'Continuity ciphertext encoding is not explicit hexadecimal encoding.';
}

if ( false === strpos( $css, '--wca-green:#087A4E' ) ) {
	$failures[] = 'File 08 fallback primary token is not exact Sabri Green #087A4E.';
}
if ( false !== stripos( $css, '--wca-green:#166534' ) ) {
	$failures[] = 'Superseded primary green remains in File 08 CSS.';
}

f08_require_tokens( 'continuity client', $js, array(
	'X-WP-Nonce', 'credentials', 'data-wca-previsit', 'data-wca-followups',
) );

// File 08 may publish a projection contract but must not create File 26 ranking/index tables.
foreach ( array( 'wca_file26_index', 'wca_search_index', 'CREATE TABLE file26', 'rank_score' ) as $duplicate_index ) {
	if ( false !== stripos( $governance . $continuity, $duplicate_index ) ) {
		$failures[] = 'Duplicate File 26 ownership detected: ' . $duplicate_index;
	}
}

// No donor or subscription entitlement may gate clinic/appointment actions.
foreach ( array( 'is_donor', 'donor_only', 'premium_required', 'pro_required', 'subscription_required' ) as $gate ) {
	if ( false !== stripos( $governance . $continuity . $auth, $gate ) ) {
		$failures[] = 'Forbidden paid/donor gate token detected: ' . $gate;
	}
}

// Cross-file ownership: integrations are events/helpers only, never direct table writes.
foreach ( array( 'smc_', 'sn_', 'file17_', 'file19_', 'file24_', 'file26_' ) as $prefix ) {
	$pattern = '/(?:INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+[^;\n]*' . preg_quote( $prefix, '/' ) . '/i';
	if ( preg_match( $pattern, $governance . "\n" . $continuity ) ) {
		$failures[] = 'Direct companion write pattern detected for prefix: ' . $prefix;
	}
}

// The new source routes must remain opaque-reference based; no new numeric appointment route.
if ( preg_match( '#continuity/appointments/\(\?P<ref>\\d\+#', $continuity ) ) {
	$failures[] = 'Continuity REST route exposes a numeric appointment identifier.';
}

if ( $failures ) {
	fwrite( STDERR, "File 08 new governing-plan parity failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}

echo "File 08 new governing-plan parity: PASS\n";
