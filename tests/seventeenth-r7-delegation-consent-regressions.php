<?php
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
    'followup no longer reads delegation user meta directly' => false === strpos( $continuity, "get_user_meta(\$user_id,'_wca_clinic_delegations'" ),
    'followup uses canonical clinical-followup scope' => false !== strpos( $continuity, "can_staff_access_appointment( \$appointment_id, \$user_id, 'clinical_followup' )" ),
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
