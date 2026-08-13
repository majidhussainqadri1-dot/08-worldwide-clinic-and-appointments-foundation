from pathlib import Path
p=Path('includes/class-wca-central-governance.php'); s=p.read_text()
def one(old,new):
    global s
    if s.count(old)!=1: raise SystemExit(f'anchor mismatch {s.count(old)}: {old[:80]}')
    s=s.replace(old,new,1)
one("\t\t$minor = array_key_exists( 'is_minor', $raw ) && null !== $raw['is_minor'] ? (bool) $raw['is_minor'] : null;", """\t\t$minor = null;
\t\tif ( array_key_exists( 'is_minor', $raw ) && null !== $raw['is_minor'] ) {
\t\t\t$minor_raw = $raw['is_minor'];
\t\t\tif ( ! in_array( $minor_raw, array( true, false, 1, 0, '1', '0' ), true ) ) { return new WP_Error( 'wca_age_claim_invalid_provider_response', __( 'Current minor eligibility returned an invalid response.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t$minor = true === $minor_raw || 1 === $minor_raw || '1' === $minor_raw;
\t\t}""")
anchor="\t/** @return true|WP_Error */\n\tpublic static function validate_patient_guardian"
helpers="""\t/** @return bool|WP_Error */
\tprivate static function strict_guardian_verified( $guardian_user_id ) {
\t\tif ( function_exists( 'smc_user_is_verified_guardian' ) ) {
\t\t\ttry { $raw = smc_user_is_verified_guardian( absint( $guardian_user_id ) ); } catch ( Throwable $e ) { return new WP_Error( 'wca_guardian_verification_provider_failure', __( 'Current guardian verification could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\tif ( is_wp_error( $raw ) || ! in_array( $raw, array( true, false, 1, 0, '1', '0' ), true ) ) { return new WP_Error( 'wca_guardian_verification_provider_invalid', __( 'Current guardian verification returned an invalid response.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\treturn true === $raw || 1 === $raw || '1' === $raw;
\t\t}
\t\treturn true === apply_filters( 'wca_user_is_verified_guardian', false, absint( $guardian_user_id ) );
\t}

\t/** @return bool|WP_Error */
\tprivate static function strict_guardian_relationship( $guardian_user_id, $patient_user_id ) {
\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) {
\t\t\ttry { $raw = smc_guardian_may_act_for( absint( $guardian_user_id ), absint( $patient_user_id ) ); } catch ( Throwable $e ) { return new WP_Error( 'wca_guardian_relationship_provider_failure', __( 'Current guardian relationship could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\tif ( is_wp_error( $raw ) || ! in_array( $raw, array( true, false, 1, 0, '1', '0' ), true ) ) { return new WP_Error( 'wca_guardian_relationship_provider_invalid', __( 'Current guardian relationship returned an invalid response.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\treturn true === $raw || 1 === $raw || '1' === $raw;
\t\t}
\t\treturn true === apply_filters( 'wca_guardian_may_act_for_patient', false, absint( $guardian_user_id ), absint( $patient_user_id ) );
\t}

"""
one(anchor,helpers+anchor)
one("\t\t$claim = self::age_guardian_claim( $patient_user_id );\n\t\tif ( is_wp_error( $claim ) ) { return $claim; }", """\t\t$claim = self::age_guardian_claim( $patient_user_id );
\t\tif ( is_wp_error( $claim ) ) { return $claim; }
\t\t$guardian_verified = false;
\t\tif ( $guardian_user_id ) { $guardian_verified = self::strict_guardian_verified( $guardian_user_id ); if ( is_wp_error( $guardian_verified ) ) { return $guardian_verified; } }""")
one("! WCA_Authorization::is_guardian( $guardian_user_id )", "! $guardian_verified")
one("$guardian_user_id && $guardian_user_id === $actor_user_id && WCA_Authorization::is_guardian( $guardian_user_id )", "$guardian_user_id && $guardian_user_id === $actor_user_id && $guardian_verified")
old="\t\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) { try { $rel = smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ); $allowed = true === $rel || 1 === $rel || '1' === $rel; } catch ( Throwable $e ) { $allowed = false; } }"
new="\t\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) { $allowed = self::strict_guardian_relationship( $guardian_user_id, $patient_user_id ); if ( is_wp_error( $allowed ) ) { return $allowed; } }"
if s.count(old)!=2: raise SystemExit(f'relationship anchor mismatch {s.count(old)}')
s=s.replace(old,new,2)
p.write_text(s)
