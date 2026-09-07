from pathlib import Path


def must_replace(text: str, old: str, new: str, label: str) -> str:
    if old not in text:
        raise SystemExit(f"missing expected source for {label}")
    if text.count(old) != 1:
        raise SystemExit(f"expected one source match for {label}, got {text.count(old)}")
    return text.replace(old, new, 1)


auth_path = Path('includes/class-wca-authorization.php')
auth = auth_path.read_text()

auth = must_replace(
    auth,
    """\t\t$guardian_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
\t\tif ( $guardian_id === $user_id && ! empty( $claims['guardian'] ) ) {
\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
\t\t\t$guardian = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_id, $guardian_id, $user_id ) : true;
\t\t\treturn is_wp_error( $guardian ) ? $guardian : true;
\t\t}
""",
    """\t\t$guardian_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );
\t\tif ( ! empty( $claims['guardian'] ) && class_exists( 'WCA_Central_Governance' ) ) {
\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
\t\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $user_id, $user_id );
\t\t\tif ( ! is_wp_error( $guardian ) ) { return true; }
\t\t\t// A guardian stored on the appointment must fail closed if File 00 no longer confirms the relationship.
\t\t\tif ( $guardian_id === $user_id ) { return $guardian; }
\t\t}
""",
    'guardian succession appointment access',
)

auth = must_replace(
    auth,
    """\tpublic static function appointment_actor( $appointment_id, $user_id = 0 ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\tif ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return 'admin'; }
\t\tif ( SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return 'doctor'; }
\t\tif ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }
\t\tif ( absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) ) === $user_id ) { return 'guardian'; }
\t\treturn 'patient';
\t}
""",
    """\tpublic static function appointment_actor( $appointment_id, $user_id = 0 ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\tif ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return 'admin'; }
\t\tif ( SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return 'doctor'; }
\t\tif ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }
\t\t$claims = self::claims( $user_id );
\t\tif ( ! is_wp_error( $claims ) && ! empty( $claims['guardian'] ) && class_exists( 'WCA_Central_Governance' ) ) {
\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
\t\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $user_id, $user_id );
\t\t\tif ( ! is_wp_error( $guardian ) ) { return 'guardian'; }
\t\t}
\t\treturn 'patient';
\t}
""",
    'guardian succession actor classification',
)

auth = must_replace(
    auth,
    """\tpublic static function can_staff_access_appointment( $appointment_id, $user_id = 0, $scope = 'appointments' ) {
\t\t$user_id   = absint( $user_id ?: get_current_user_id() );
\t\t$clinic_id = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) );
\t\tif ( ! $user_id || ! $clinic_id ) { return false; }
\t\t$entry = self::clinic_delegation( $user_id, $clinic_id );
\t\treturn $entry ? self::delegation_allows_scope( $entry, sanitize_key( $scope ) ) : false;
\t}
""",
    """\tpublic static function can_staff_access_appointment( $appointment_id, $user_id = 0, $scope = 'appointments' ) {
\t\t$user_id   = absint( $user_id ?: get_current_user_id() );
\t\t$clinic_id = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id', 0 ) );
\t\tif ( ! $user_id || ! $clinic_id ) { return false; }
\t\t$claims = self::claims( $user_id );
\t\tif ( is_wp_error( $claims ) ) { return false; }
\t\t$entry = self::clinic_delegation( $user_id, $clinic_id );
\t\treturn $entry ? self::delegation_allows_scope( $entry, sanitize_key( $scope ) ) : false;
\t}
""",
    'delegated appointment current claims',
)

auth = must_replace(
    auth,
    """\tpublic static function delegated_clinic_ids( $user_id = 0, $scope = 'appointments' ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\t$out = array();
""",
    """\tpublic static function delegated_clinic_ids( $user_id = 0, $scope = 'appointments' ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\t$claims = self::claims( $user_id );
\t\tif ( is_wp_error( $claims ) ) { return array(); }
\t\t$out = array();
""",
    'delegated clinic list current claims',
)

auth = must_replace(
    auth,
    """\tpublic static function has_active_clinic_delegation( $user_id = 0 ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\tforeach ( self::delegations( $user_id ) as $entry ) {
\t\t\tif ( is_array( $entry ) && ! empty( $entry['active'] ) ) { return true; }
\t\t}
\t\treturn false;
\t}
""",
    """\tpublic static function has_active_clinic_delegation( $user_id = 0 ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\tforeach ( self::delegations( $user_id ) as $entry ) {
\t\t\tif ( self::delegation_is_current( $entry ) ) { return true; }
\t\t}
\t\treturn false;
\t}
""",
    'delegation active currentness',
)

auth = must_replace(
    auth,
    """\t/** @return array<string,mixed> */
\tprivate static function clinic_delegation( $user_id, $clinic_id ) {
""",
    """\tprivate static function delegation_is_current( $entry ) {
\t\tif ( ! is_array( $entry ) || empty( $entry['active'] ) || ! empty( $entry['revoked_at'] ) || ! empty( $entry['revoked'] ) ) { return false; }
\t\t$status = isset( $entry['status'] ) ? sanitize_key( $entry['status'] ) : '';
\t\tif ( $status && ! in_array( $status, array( 'active', 'granted' ), true ) ) { return false; }
\t\t$starts = trim( (string) ( $entry['starts_at_utc'] ?? $entry['starts_at'] ?? '' ) );
\t\tif ( $starts ) {
\t\t\t$starts_ts = strtotime( $starts );
\t\t\tif ( false === $starts_ts || $starts_ts > time() ) { return false; }
\t\t}
\t\t$expires = trim( (string) ( $entry['expires_at_utc'] ?? $entry['expires_at'] ?? '' ) );
\t\tif ( '' === $expires ) {
\t\t\t// Legacy indefinite grants are denied unless an authoritative compatibility adapter proves currentness.
\t\t\treturn true === apply_filters( 'wca_legacy_clinic_delegation_is_current', false, $entry );
\t\t}
\t\t$expires_ts = strtotime( $expires );
\t\tif ( false === $expires_ts || $expires_ts <= time() ) { return false; }
\t\treturn true;
\t}

\t/** @return array<string,mixed> */
\tprivate static function clinic_delegation( $user_id, $clinic_id ) {
""",
    'delegation currentness helper',
)

auth = must_replace(
    auth,
    """\t\treturn ! empty( $entry['active'] ) ? $entry : array();
\t}

\tprivate static function delegation_allows_scope( $entry, $scope ) {
\t\tif ( ! is_array( $entry ) || empty( $entry['active'] ) ) { return false; }
""",
    """\t\treturn self::delegation_is_current( $entry ) ? $entry : array();
\t}

\tprivate static function delegation_allows_scope( $entry, $scope ) {
\t\tif ( ! self::delegation_is_current( $entry ) ) { return false; }
""",
    'delegation scope currentness',
)

auth_path.write_text(auth)

continuity_path = Path('includes/class-wca-continuity-secure.php')
continuity = continuity_path.read_text()

continuity = must_replace(
    continuity,
    """\t\t$result = WCA_Repository::transaction( function () use ( $table, $appointment_id, $scope, $actor_user_id ) {
\t\t\tglobal $wpdb;
\t\t\t$changed = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND scope=%s AND actor_user_id=%d AND status='granted'\", WCA_Repository::now(), $appointment_id, $scope, $actor_user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
""",
    """\t\t$result = WCA_Repository::transaction( function () use ( $table, $appointment_id, $scope, $actor_user_id ) {
\t\t\tglobal $wpdb;
\t\t\t// Withdrawal is scope-wide for the appointment: the current patient/current verified guardian may revoke a grant created by a prior authorized actor.
\t\t\t$changed = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND scope=%s AND status='granted'\", WCA_Repository::now(), $appointment_id, $scope ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
""",
    'scope-wide consent withdrawal',
)

continuity = must_replace(
    continuity,
    """\tprivate static function followup_actor_allowed( $appointment_id, $user_id ) { $user_id=absint($user_id); if('doctor'===WCA_Authorization::appointment_actor($appointment_id,$user_id)){return true;} $clinic_id=absint(SWC_Helpers::meta($appointment_id,'clinic_id',0)); $delegated=(array)get_user_meta($user_id,'_wca_clinic_delegations',true); $entry=isset($delegated[$clinic_id])&&is_array($delegated[$clinic_id])?$delegated[$clinic_id]:array(); $allowed=!empty($entry['active'])&&(!empty($entry['clinical_followup'])||!empty($entry['clinical'])); return (bool)apply_filters('wca_followup_actor_allowed',$allowed,$appointment_id,$user_id); }
""",
    """\tprivate static function followup_actor_allowed( $appointment_id, $user_id ) {
\t\t$user_id = absint( $user_id );
\t\t$claims = WCA_Authorization::claims( $user_id );
\t\tif ( is_wp_error( $claims ) ) { return false; }
\t\tif ( 'doctor' === WCA_Authorization::appointment_actor( $appointment_id, $user_id ) ) { return true; }
\t\t$allowed = WCA_Authorization::can_staff_access_appointment( $appointment_id, $user_id, 'clinical_followup' );
\t\t$filtered = (bool) apply_filters( 'wca_followup_actor_allowed', $allowed, $appointment_id, $user_id );
\t\treturn $allowed && $filtered;
\t}
""",
    'followup canonical delegation authorization',
)

continuity_path.write_text(continuity)

run_all_path = Path('tests/run-all.php')
run_all = run_all_path.read_text()
run_all = must_replace(
    run_all,
    "'seventeenth-r3-authorization-regressions.php' );",
    "'seventeenth-r3-authorization-regressions.php', 'seventeenth-r7-delegation-consent-regressions.php' );",
    'register R7 regression suite',
)
run_all_path.write_text(run_all)

test_path = Path('tests/seventeenth-r7-delegation-consent-regressions.php')
test_path.write_text(r'''<?php
$auth = file_get_contents( __DIR__ . '/../includes/class-wca-authorization.php' );
$continuity = file_get_contents( __DIR__ . '/../includes/class-wca-continuity-secure.php' );
$checks = array(
    'delegated appointment access revalidates current claims' => false !== strpos( $auth, '$claims = self::claims( $user_id );' ) && false !== strpos( $auth, 'can_staff_access_appointment' ),
    'delegation has explicit currentness helper' => false !== strpos( $auth, 'private static function delegation_is_current' ),
    'delegation expiry is enforced' => false !== strpos( $auth, "['expires_at_utc']" ) && false !== strpos( $auth, '$expires_ts <= time()' ),
    'legacy indefinite delegation fails closed by default' => false !== strpos( $auth, "apply_filters( 'wca_legacy_clinic_delegation_is_current', false" ),
    'delegation scope requires current grant' => false !== strpos( $auth, 'if ( ! self::delegation_is_current( $entry ) ) { return false; }' ),
    'guardian appointment access rechecks current File00 relationship' => false !== strpos( $auth, 'validate_patient_guardian( $patient_id, $user_id, $user_id )' ),
    'guardian actor classification is current rather than snapshot-only' => substr_count( $auth, 'validate_patient_guardian( $patient_id, $user_id, $user_id )' ) >= 2,
    'followup no longer reads delegation user meta directly' => false === strpos( $continuity, "get_user_meta($user_id,'_wca_clinic_delegations'" ),
    'followup uses canonical clinical-followup scope' => false !== strpos( $continuity, "can_staff_access_appointment( $appointment_id, $user_id, 'clinical_followup' )" ),
    'followup filter cannot broaden denied authority' => false !== strpos( $continuity, 'return $allowed && $filtered;' ),
    'consent withdrawal is scope-wide for current authorized actor' => false !== strpos( $continuity, "WHERE appointment_id=%d AND scope=%s AND status='granted'" ),
    'consent withdrawal is not limited to original actor' => false === strpos( $continuity, "scope=%s AND actor_user_id=%d AND status='granted'" ),
);
$passed = 0;
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "[FAIL] {$label}\n" ); exit( 1 ); }
    echo "[PASS] {$label}\n";
    $passed++;
}
echo "R7 delegation/guardian/consent assertions: {$passed}/" . count( $checks ) . " PASS\n";
''')
