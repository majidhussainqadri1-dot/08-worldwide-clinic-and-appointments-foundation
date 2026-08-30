from pathlib import Path
import re


def replace_once(path, old, new, label):
    p = Path(path)
    s = p.read_text()
    if s.count(old) != 1:
        raise SystemExit(f'{label}: expected exactly one anchor, found {s.count(old)}')
    p.write_text(s.replace(old, new, 1))


def regex_once(path, pattern, replacement, label, flags=re.S):
    p = Path(path)
    s = p.read_text()
    out, n = re.subn(pattern, replacement, s, count=1, flags=flags)
    if n != 1:
        raise SystemExit(f'{label}: expected one regex match, found {n}')
    p.write_text(out)

# R17-A: sensitive pre-visit intake consent must exist before any persistence,
# not merely before the final submit transition.
replace_once(
    'includes/class-wca-continuity-secure.php',
    "\t\tif ( $submit ) {\n\t\t\t$active_consent = self::active_consent( $appointment_id, 'appointment_processing' );\n\t\t\tif ( is_wp_error( $active_consent ) ) { return $active_consent; }\n\t\t\tif ( ! $active_consent ) { return new WP_Error( 'wca_intake_consent', __( 'Current appointment-processing consent is required before intake submission.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t}\n",
    "\t\t$active_consent = self::active_consent( $appointment_id, 'appointment_processing' );\n\t\tif ( is_wp_error( $active_consent ) ) { return $active_consent; }\n\t\tif ( ! $active_consent ) { return new WP_Error( 'wca_intake_consent', __( 'Current appointment-processing consent is required before pre-visit information is stored.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n",
    'R17 intake consent-before-persistence'
)

# R17-B: enforce optimistic concurrency at the canonical persistence root as well
# as at the REST pre-dispatch guard so internal callers cannot bypass the invariant.
replace_once(
    'includes/class-wca-continuity-secure.php',
    "\t\t\tif ( $current ) {\n\t\t\t\tif ( $expected_version && $expected_version !== absint( $current['version'] ) ) {\n\t\t\t\t\treturn new WP_Error( 'wca_intake_stale', __( 'Pre-visit intake changed. Refresh before saving.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t\t\t}\n",
    "\t\t\tif ( $current ) {\n\t\t\t\tif ( ! $expected_version ) {\n\t\t\t\t\treturn new WP_Error( 'wca_intake_version_required', __( 'This pre-visit record already exists. Refresh it before saving changes.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'current_version' => absint( $current['version'] ) ) );\n\t\t\t\t}\n\t\t\t\tif ( $expected_version !== absint( $current['version'] ) ) {\n\t\t\t\t\treturn new WP_Error( 'wca_intake_stale', __( 'Pre-visit intake changed. Refresh before saving.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'current_version' => absint( $current['version'] ) ) );\n\t\t\t\t}\n",
    'R17 canonical intake expected-version'
)

# R17-C: expose the processing consent that actually governs intake persistence
# through the same patient/guardian consent state and withdrawal contract.
replace_once(
    'includes/class-wca-continuity-secure.php',
    "\tprivate static function context_consent_scopes() { return array( 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' ); }",
    "\tprivate static function context_consent_scopes() { return array( 'appointment_processing', 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' ); }",
    'R17 continuity processing-consent control'
)
replace_once(
    'includes/class-wca-continuity-guards.php',
    "\t\t$scopes = array( 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' );",
    "\t\t$scopes = array( 'appointment_processing', 'teleconsult', 'recording', 'messaging', 'privacy_notice', 'followup' );",
    'R17 consent-state processing scope'
)

# R17-D/E: Future24 contract/schema migration for FHIR version metadata,
# episode privacy subject backfill, and episode unlink/archive lifecycle.
replace_once(
    'includes/class-wca-future24.php',
    "\tconst CONTRACT_VERSION = '1.0.0';\n\tconst SCHEMA_VERSION   = '1.0.0';",
    "\tconst CONTRACT_VERSION = '1.1.0';\n\tconst SCHEMA_VERSION   = '1.1.0';",
    'R17 Future24 contract/schema bump'
)

# Backfill legacy episode records so patient privacy export/erasure can discover
# episode linkage even when an older record was created by a practitioner.
replace_once(
    'includes/class-wca-future24.php',
    "\t\t$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );\n\t\tif ( $exists !== $table ) { throw new RuntimeException( 'File 08 Future24 operational table could not be created.' ); }\n\t\t$written = SWC_Helpers::update_option_strict( self::SCHEMA_OPTION, self::SCHEMA_VERSION, 'wca_future24_schema_version_write' );",
    "\t\t$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );\n\t\tif ( $exists !== $table ) { throw new RuntimeException( 'File 08 Future24 operational table could not be created.' ); }\n\n\t\t$cursor = 0;\n\t\tdo {\n\t\t\t$legacy_episodes = $wpdb->get_results( $wpdb->prepare( \"SELECT id,appointment_id FROM {$table} WHERE feature_id='F08-FUT-23' AND subject_user_id=0 AND appointment_id>0 AND id>%d ORDER BY id ASC LIMIT 200\", $cursor ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $legacy_episodes && '' !== (string) $wpdb->last_error ) { throw new RuntimeException( 'File 08 Future24 episode privacy migration could not read legacy rows.' ); }\n\t\t\tforeach ( (array) $legacy_episodes as $legacy_episode ) {\n\t\t\t\t$episode_id = absint( $legacy_episode['id'] );\n\t\t\t\t$appointment_id = absint( $legacy_episode['appointment_id'] );\n\t\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );\n\t\t\t\tif ( $patient_id ) {\n\t\t\t\t\t$changed = $wpdb->update( $table, array( 'subject_user_id' => $patient_id ), array( 'id' => $episode_id, 'subject_user_id' => 0 ), array( '%d' ), array( '%d','%d' ) );\n\t\t\t\t\tif ( false === $changed ) { throw new RuntimeException( 'File 08 Future24 episode privacy migration could not persist a patient subject.' ); }\n\t\t\t\t}\n\t\t\t\t$cursor = max( $cursor, $episode_id );\n\t\t\t}\n\t\t} while ( 200 === count( (array) $legacy_episodes ) );\n\n\t\t$written = SWC_Helpers::update_option_strict( self::SCHEMA_OPTION, self::SCHEMA_VERSION, 'wca_future24_schema_version_write' );",
    'R17 episode privacy backfill'
)

# Add explicit episode lifecycle endpoints.
replace_once(
    'includes/class-wca-future24.php',
    "\t\t\t'/future24/episodes' => array( 'POST', 'rest_episode' ),\n\t\t\t'/future24/governance' => array( 'GET', 'rest_governance' ),",
    "\t\t\t'/future24/episodes' => array( 'POST', 'rest_episode' ),\n\t\t\t'/future24/episodes/(?P<ref>[0-9a-fA-F-]{36})/unlink' => array( 'POST', 'rest_episode_unlink' ),\n\t\t\t'/future24/episodes/(?P<ref>[0-9a-fA-F-]{36})/archive' => array( 'POST', 'rest_episode_archive' ),\n\t\t\t'/future24/governance' => array( 'GET', 'rest_governance' ),",
    'R17 episode lifecycle routes'
)

# Replace FHIR projection with explicit feature disable/degraded behavior and
# source-record version/freshness metadata.
fhir_pattern = r"\t/\* FUT-20 \*/\n\tpublic static function fhir_projection\( \$type, \$ref, \$actor = 0 \) \{.*?\n\t\}\n\n\tprivate static function fhir_status"
fhir_replacement = """\t/* FUT-20 */
\tpublic static function fhir_projection( $type, $ref, $actor = 0 ) {
\t\t$type = sanitize_key( $type ); $ref = strtolower( sanitize_text_field( $ref ) );
\t\tif ( true !== apply_filters( 'wca_fhir_adapter_enabled', true, $type, $ref ) ) {
\t\t\treturn new WP_Error( 'wca_fhir_adapter_disabled', __( 'FHIR interoperability is temporarily unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'degraded' => true ) );
\t\t}
\t\tif ( 'appointment' === $type ) {
\t\t\t$id = self::require_appointment( $ref, $actor ); if ( is_wp_error( $id ) ) { return $id; }
\t\t\t$start = self::utc( SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) );
\t\t\t$end = self::utc( SWC_Helpers::meta( $id, 'appointment_end_utc', '' ) );
\t\t\tif ( ! $start || ! $end || $end <= $start ) { return new WP_Error( 'wca_fhir_time', __( 'Appointment scheduling times are unavailable or invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t$record_version = max( 1, absint( SWC_Helpers::record_version( $id ) ) );
\t\t\t$last_updated = get_post_modified_time( 'c', true, $id );
\t\t\tif ( ! is_string( $last_updated ) || '' === $last_updated ) { return new WP_Error( 'wca_fhir_freshness', __( 'Appointment interoperability freshness metadata is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'degraded' => true ) ); }
\t\t\treturn array( 'resourceType' => 'Appointment', 'id' => $ref, 'status' => self::fhir_status( SWC_Helpers::status( $id ) ), 'start' => gmdate( 'c', strtotime( $start . ' UTC' ) ), 'end' => gmdate( 'c', strtotime( $end . ' UTC' ) ), 'meta' => array( 'versionId' => (string) $record_version, 'lastUpdated' => $last_updated, 'profile' => array( 'wca.future24/fhir-appointment/' . self::CONTRACT_VERSION ) ) );
\t\t}
\t\tif ( 'clinic' === $type ) {
\t\t\t$clinic = WCA_Service::public_clinic_projection( $ref ); if ( is_wp_error( $clinic ) ) { return $clinic; } if ( ! $clinic ) { return new WP_Error( 'wca_fhir_clinic', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
\t\t\t$updated_raw = (string) ( $clinic['updated_at'] ?? '' );
\t\t\t$updated_ts = $updated_raw ? strtotime( $updated_raw . ' UTC' ) : false;
\t\t\tif ( false === $updated_ts || empty( $clinic['record_version'] ) ) { return new WP_Error( 'wca_fhir_freshness', __( 'Clinic interoperability freshness metadata is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'degraded' => true ) ); }
\t\t\treturn array( 'resourceType' => 'HealthcareService', 'id' => (string) $clinic['public_ref'], 'active' => 'active' === $clinic['status'], 'name' => (string) $clinic['name'], 'communication' => array_values( (array) $clinic['languages'] ), 'meta' => array( 'versionId' => (string) absint( $clinic['record_version'] ), 'lastUpdated' => gmdate( 'c', $updated_ts ), 'profile' => array( 'wca.future24/fhir-healthcare-service/' . self::CONTRACT_VERSION ) ), 'extension' => array( array( 'url' => 'wca-zero-commission', 'valueBoolean' => true ) ) );
\t\t}
\t\treturn new WP_Error( 'wca_fhir_type', __( 'Unsupported interoperability resource type.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
\t}

\tprivate static function fhir_status"""
regex_once('includes/class-wca-future24.php', fhir_pattern, fhir_replacement, 'R17 FHIR version/degraded contract')

# Replace episode creation block with patient-subject privacy ownership plus
# explicit unlink/archive operations guarded by optimistic versioning.
episode_pattern = r"\t/\* FUT-23 \*/\n\tpublic static function create_episode\( \$data, \$actor = 0 \) \{.*?\n\t\}\n\n\t/\* FUT-24 \*/"
episode_replacement = """\t/* FUT-23 */
\tpublic static function create_episode( $data, $actor = 0 ) {
\t\t$raw_refs = (array) ( isset( $data['appointment_refs'] ) ? $data['appointment_refs'] : array() );
\t\tif ( count( $raw_refs ) > 50 ) { return new WP_Error( 'wca_episode_appointment_limit', __( 'No more than 50 appointments may be linked in one episode.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
\t\t$refs = array();
\t\t$scope = null;
\t\tforeach ( $raw_refs as $ref ) {
\t\t\t$id = self::require_appointment( $ref, $actor );
\t\t\tif ( is_wp_error( $id ) ) { return $id; }
\t\t\t$current = array(
\t\t\t\t'patient_id' => absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) ),
\t\t\t\t'doctor_id' => absint( SWC_Helpers::meta( $id, 'doctor_id', 0 ) ),
\t\t\t\t'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ),
\t\t\t);
\t\t\tif ( null === $scope ) { $scope = $current; }
\t\t\telseif ( $scope !== $current ) { return new WP_Error( 'wca_episode_scope', __( 'Every appointment in an episode must belong to the same patient, doctor, and clinic scope.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t$refs[] = strtolower( sanitize_text_field( $ref ) );
\t\t}
\t\t$refs = array_values( array_unique( $refs ) );
\t\tif ( ! $refs ) { return new WP_Error( 'wca_episode_appointments', __( 'At least one authorized appointment is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\treturn self::put_record( 'F08-FUT-23', array(
\t\t\t'appointment_id' => self::appointment_id( $refs[0] ),
\t\t\t'clinic_id' => absint( isset( $scope['clinic_id'] ) ? $scope['clinic_id'] : 0 ),
\t\t\t'subject_user_id' => absint( isset( $scope['patient_id'] ) ? $scope['patient_id'] : 0 ),
\t\t\t'status' => 'episode_open',
\t\t\t'payload' => array( 'appointment_refs' => $refs, 'clinical_narrative_stored' => false, 'public_timeline' => false, 'scope_consistency' => 'same_patient_doctor_clinic', 'unlink_supported' => true, 'archive_supported' => true ),
\t\t), $actor );
\t}

\tprivate static function mutate_episode( $episode_ref, $data, $action, $actor = 0 ) {
\t\tglobal $wpdb;
\t\t$actor = absint( $actor ?: get_current_user_id() );
\t\t$row = self::get_record( $episode_ref, 'F08-FUT-23' );
\t\tif ( is_wp_error( $row ) ) { return $row; }
\t\tif ( ! $row ) { return new WP_Error( 'wca_episode_missing', __( 'Episode was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
\t\t$anchor_ref = self::appointment_ref( absint( $row['appointment_id'] ) );
\t\t$anchor_id = self::require_appointment( $anchor_ref, $actor );
\t\tif ( is_wp_error( $anchor_id ) ) { return $anchor_id; }
\t\t$expected_version = WCA_Service::strict_id( $data['expected_version'] ?? null );
\t\tif ( null === $expected_version ) { return new WP_Error( 'wca_episode_version_required', __( 'Current episode version is required before changing episode links.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\tif ( $expected_version !== absint( $row['version'] ) ) { return new WP_Error( 'wca_episode_stale', __( 'Episode changed. Refresh before updating it.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'current_version' => absint( $row['version'] ) ) ); }
\t\t$payload = json_decode( (string) $row['payload_json'], true );
\t\t$payload = is_array( $payload ) ? $payload : array();
\t\t$refs = array_values( array_unique( array_filter( array_map( 'strtolower', array_map( 'sanitize_text_field', (array) ( $payload['appointment_refs'] ?? array() ) ) ) ) ) );
\t\t$status = (string) $row['status'];
\t\t$expires_at = $row['expires_at'];
\t\tif ( 'archive' === $action ) {
\t\t\tif ( 'episode_archived' === $status ) { return self::public_record( $row ); }
\t\t\t$status = 'episode_archived';
\t\t\t$expires_at = WCA_Repository::now();
\t\t\t$payload['archived'] = true;
\t\t} elseif ( 'unlink' === $action ) {
\t\t\tif ( 'episode_open' !== $status ) { return new WP_Error( 'wca_episode_state', __( 'Only an open episode may be unlinked.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t$unlink_ref = strtolower( sanitize_text_field( $data['appointment_ref'] ?? '' ) );
\t\t\tif ( ! preg_match( '/^[0-9a-f-]{36}$/', $unlink_ref ) || ! in_array( $unlink_ref, $refs, true ) ) { return new WP_Error( 'wca_episode_unlink_ref', __( 'A currently linked appointment reference is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\t\t$authorized = self::require_appointment( $unlink_ref, $actor ); if ( is_wp_error( $authorized ) ) { return $authorized; }
\t\t\t$refs = array_values( array_diff( $refs, array( $unlink_ref ) ) );
\t\t\t$payload['appointment_refs'] = $refs;
\t\t\tif ( ! $refs ) { $status = 'episode_archived'; $expires_at = WCA_Repository::now(); $payload['archived'] = true; }
\t\t} else {
\t\t\treturn new WP_Error( 'wca_episode_action', __( 'Unsupported episode action.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
\t\t}
\t\t$encoded = wp_json_encode( $payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
\t\tif ( ! is_string( $encoded ) || strlen( $encoded ) > self::MAX_PAYLOAD ) { return new WP_Error( 'wca_episode_payload', __( 'Episode linkage metadata is too large.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }
\t\t$table = self::tables()['records'];
\t\treturn WCA_Repository::transaction( function () use ( $wpdb, $table, $row, $expected_version, $status, $expires_at, $encoded, $actor, $action ) {
\t\t\t$changed = $wpdb->update( $table, array( 'status' => $status, 'payload_json' => $encoded, 'expires_at' => $expires_at, 'version' => $expected_version + 1, 'updated_at' => WCA_Repository::now() ), array( 'id' => absint( $row['id'] ), 'version' => $expected_version ), array( '%s','%s','%s','%d','%s' ), array( '%d','%d' ) );
\t\t\tif ( false === $changed ) { return new WP_Error( 'wca_episode_update_failed', __( 'Episode change could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\tif ( 1 !== (int) $changed ) { return new WP_Error( 'wca_episode_stale', __( 'Episode changed concurrently. Refresh before updating it.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t$audit = self::audit( 'F08-FUT-23', 'episode_' . $action, (string) $row['public_ref'], array( 'status' => $status, 'expected_version' => $expected_version ), $actor, false );
\t\t\tif ( is_wp_error( $audit ) ) { return $audit; }
\t\t\t$updated = self::get_record( (string) $row['public_ref'], 'F08-FUT-23' );
\t\t\treturn is_wp_error( $updated ) ? $updated : ( $updated ? self::public_record( $updated ) : new WP_Error( 'wca_episode_readback_missing', __( 'Updated episode could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ) );
\t\t}, 'wca_episode_mutation_transaction' );
\t}

\tpublic static function unlink_episode_appointment( $episode_ref, $data, $actor = 0 ) { return self::mutate_episode( $episode_ref, is_array( $data ) ? $data : array(), 'unlink', $actor ); }
\tpublic static function archive_episode( $episode_ref, $data, $actor = 0 ) { return self::mutate_episode( $episode_ref, is_array( $data ) ? $data : array(), 'archive', $actor ); }

\t/* FUT-24 */"""
regex_once('includes/class-wca-future24.php', episode_pattern, episode_replacement, 'R17 episode lifecycle/privacy')

# Add REST wrappers for episode lifecycle.
replace_once(
    'includes/class-wca-future24.php',
    "\tpublic static function rest_episode( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'episode','create_episode',array($d),201); }\n\tpublic static function rest_governance(){ return self::respond(self::governance_log()); }",
    "\tpublic static function rest_episode( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'episode','create_episode',array($d),201); }\n\tpublic static function rest_episode_unlink( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'episode_unlink','unlink_episode_appointment',array($r['ref'],$d),200); }\n\tpublic static function rest_episode_archive( WP_REST_Request $r ){ $d=self::data($r); return self::mutate($r,'episode_archive','archive_episode',array($r['ref'],$d),200); }\n\tpublic static function rest_governance(){ return self::respond(self::governance_log()); }",
    'R17 episode REST wrappers'
)

# Static regression gate for the frozen R17 ledger.
Path('tests/seventeenth-r17-privacy-continuity-regressions.php').write_text(r'''<?php
$root = dirname(__DIR__);
$continuity = file_get_contents($root . '/includes/class-wca-continuity-secure.php');
$guards = file_get_contents($root . '/includes/class-wca-continuity-guards.php');
$future = file_get_contents($root . '/includes/class-wca-future24.php');
if (!is_string($continuity) || !is_string($guards) || !is_string($future)) { fwrite(STDERR, "R17 source read failed\n"); exit(1); }
$checks = array(
    'intake consent before persistence' => strpos($continuity, "self::active_consent( $appointment_id, 'appointment_processing' )") !== false && strpos($continuity, 'before pre-visit information is stored') !== false,
    'canonical intake version required' => strpos($continuity, "'wca_intake_version_required'") !== false && strpos($continuity, "if ( ! $expected_version )") !== false,
    'processing consent controllable' => strpos($continuity, "array( 'appointment_processing', 'teleconsult'") !== false && strpos($guards, "array( 'appointment_processing', 'teleconsult'") !== false,
    'future24 schema migration bumped' => strpos($future, "const SCHEMA_VERSION   = '1.1.0';") !== false,
    'episode privacy subject persisted' => strpos($future, "'subject_user_id' => absint( isset( $scope['patient_id'] )") !== false,
    'legacy episode subject backfill' => strpos($future, "feature_id='F08-FUT-23' AND subject_user_id=0") !== false,
    'episode unlink route' => strpos($future, "/future24/episodes/(?P<ref>[0-9a-fA-F-]{36})/unlink") !== false,
    'episode archive route' => strpos($future, "/future24/episodes/(?P<ref>[0-9a-fA-F-]{36})/archive") !== false,
    'episode mutation optimistic concurrency' => strpos($future, "'wca_episode_version_required'") !== false && strpos($future, "'wca_episode_stale'") !== false,
    'fhir adapter degraded toggle' => strpos($future, "wca_fhir_adapter_enabled") !== false && strpos($future, "wca_fhir_adapter_disabled") !== false,
    'fhir appointment version metadata' => strpos($future, "'versionId' => (string) $record_version") !== false && strpos($future, "'lastUpdated' => $last_updated") !== false,
    'fhir clinic version metadata' => strpos($future, "'versionId' => (string) absint( $clinic['record_version'] )") !== false,
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "R17 FAIL: {$name}\n"); exit(1); } }
echo "R17 privacy/continuity/interoperability regressions: PASS\n";
''')

# Ensure run-all picks up the new gate if it uses an explicit list.
p = Path('tests/run-all.php')
s = p.read_text()
needle = "'seventeenth-r16-finance-schema-reconciliation-regressions.php',"
if needle in s and "seventeenth-r17-privacy-continuity-regressions.php" not in s:
    s = s.replace(needle, needle + "\n    'seventeenth-r17-privacy-continuity-regressions.php',", 1)
    p.write_text(s)

print('R17 frozen defect ledger corrections staged successfully')
