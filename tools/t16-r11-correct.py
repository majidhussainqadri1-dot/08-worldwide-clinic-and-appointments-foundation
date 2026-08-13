from pathlib import Path


def rep(path, old, new, count=1):
    p=Path(path); s=p.read_text()
    if s.count(old)!=count:
        raise SystemExit(f'{path}: expected {count} occurrences, found {s.count(old)} for {old[:100]!r}')
    p.write_text(s.replace(old,new,count))

# 1/7 + 7/7: claims filter may restrict, never elevate authoritative identity/security claims.
p=Path('includes/class-wca-authorization.php'); s=p.read_text()
old="""\t\t$claims = apply_filters( 'wca_identity_claims', $claims, $user_id );
\t\tif ( empty( $claims['approved'] ) || ! empty( $claims['suspended'] ) ) {
"""
new="""\t\t$authoritative_claims = $claims;
\t\t$filtered_claims = apply_filters( 'wca_identity_claims', $claims, $user_id );
\t\tif ( ! is_array( $filtered_claims ) ) {
\t\t\treturn new WP_Error( 'wca_identity_claim_filter_invalid', __( 'Identity authorization could not be evaluated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t\t}
\t\t$claims = $filtered_claims;
\t\tforeach ( array( 'contract', 'version', 'user_id', 'subject_uuid', 'capabilities', 'issued_at_utc', 'expires_at_utc' ) as $protected_key ) {
\t\t\t$claims[ $protected_key ] = $authoritative_claims[ $protected_key ];
\t\t}
\t\tforeach ( array( 'approved', 'founder', 'doctor', 'clinic_staff', 'guardian' ) as $monotonic_key ) {
\t\t\t$claims[ $monotonic_key ] = ! empty( $authoritative_claims[ $monotonic_key ] ) && ! empty( $filtered_claims[ $monotonic_key ] );
\t\t}
\t\t$claims['suspended'] = ! empty( $authoritative_claims['suspended'] ) || ! empty( $filtered_claims['suspended'] );
\t\t$claims['role'] = ! empty( $claims['founder'] ) ? 'founder' : ( ! empty( $claims['doctor'] ) ? 'doctor' : ( user_can( $user_id, 'manage_worldwide_clinic' ) ? 'administrator' : ( ! empty( $claims['clinic_staff'] ) ? 'clinic_staff' : 'member' ) ) );
\t\tif ( empty( $claims['approved'] ) || ! empty( $claims['suspended'] ) ) {
"""
if s.count(old)!=1: raise SystemExit('claims filter block mismatch')
s=s.replace(old,new,1)

# 2/7: canonical subject helper is authoritative when present; no stale-meta fallback after failure.
old="""\tpublic static function subject_uuid( $user_id ) {
\t\t$user_id = absint( $user_id );
\t\tif ( function_exists( 'smc_get_subject_uuid' ) ) {
\t\t\t$value = smc_get_subject_uuid( $user_id );
\t\t\tif ( is_string( $value ) && preg_match( '/^[0-9a-f-]{36}$/i', $value ) ) {
\t\t\t\treturn strtolower( $value );
\t\t\t}
\t\t}
\t\t$value = (string) get_user_meta( $user_id, '_smc_subject_uuid', true );
\t\treturn preg_match( '/^[0-9a-f-]{36}$/i', $value ) ? strtolower( $value ) : '';
\t}
"""
new="""\tpublic static function subject_uuid( $user_id ) {
\t\t$user_id = absint( $user_id );
\t\tif ( function_exists( 'smc_get_subject_uuid' ) ) {
\t\t\ttry { $value = smc_get_subject_uuid( $user_id ); } catch ( Throwable $e ) { return ''; }
\t\t\tif ( is_wp_error( $value ) || ! is_string( $value ) ) { return ''; }
\t\t\treturn preg_match( '/^[0-9a-f-]{36}$/i', $value ) ? strtolower( $value ) : '';
\t\t}
\t\t$value = (string) get_user_meta( $user_id, '_smc_subject_uuid', true );
\t\treturn preg_match( '/^[0-9a-f-]{36}$/i', $value ) ? strtolower( $value ) : '';
\t}
"""
if s.count(old)!=1: raise SystemExit('subject_uuid block mismatch')
s=s.replace(old,new,1)

# 3/7: doctor-serving relationship filter may only narrow native relationship truth.
old="""\t\t$allowed = in_array( $clinic_id, array_map( 'absint', $delegated ), true );
\t\treturn (bool) apply_filters( 'wca_doctor_may_serve_clinic', $allowed, $doctor_user_id, $clinic_id, $actor_user_id );
"""
new="""\t\t$allowed = in_array( $clinic_id, array_map( 'absint', $delegated ), true );
\t\t$filtered = (bool) apply_filters( 'wca_doctor_may_serve_clinic', $allowed, $doctor_user_id, $clinic_id, $actor_user_id );
\t\treturn $allowed && $filtered;
"""
if s.count(old)!=1: raise SystemExit('doctor serving filter mismatch')
s=s.replace(old,new,1)

# 4/7: delegation filter may only narrow an explicitly persisted native grant.
old="""\t\treturn (bool) apply_filters( 'wca_clinic_delegation_allows_scope', $direct, $entry, $scope );
"""
new="""\t\t$filtered = (bool) apply_filters( 'wca_clinic_delegation_allows_scope', $direct, $entry, $scope );
\t\treturn $direct && $filtered;
"""
if s.count(old)!=1: raise SystemExit('delegation filter mismatch')
s=s.replace(old,new,1)
p.write_text(s)

# 5/7, 6/7 and File26 upstream error propagation.
p=Path('includes/class-wca-central-governance.php'); s=p.read_text()
old="""\t\t$raw = apply_filters( 'wca_age_guardian_claim', $raw, $patient_user_id );
\t\tif ( is_array( $raw ) && ! $source ) { $source = 'versioned-filter'; }

\t\tif ( ! is_array( $raw ) && $versioned_attempted ) {
"""
new="""\t\t// A local extension may provide a fallback only when no File 00 versioned provider exists.
\t\t// It may never overwrite a canonical File 00 age/guardian assertion.
\t\tif ( ! $versioned_attempted ) {
\t\t\t$filtered_raw = apply_filters( 'wca_age_guardian_claim', $raw, $patient_user_id );
\t\t\tif ( is_array( $filtered_raw ) ) { $raw = $filtered_raw; $source = $source ?: 'versioned-filter'; }
\t\t}

\t\tif ( ! is_array( $raw ) && $versioned_attempted ) {
"""
if s.count(old)!=1: raise SystemExit('age filter block mismatch')
s=s.replace(old,new,1)
old="""\t\t\tif ( $birth || $gender || function_exists( 'smc_user_is_minor' ) ) {
\t\t\t\t$raw = array(
\t\t\t\t\t'birth_date' => $birth,
\t\t\t\t\t'gender'     => $gender,
\t\t\t\t\t'is_minor'   => function_exists( 'smc_user_is_minor' ) ? (bool) smc_user_is_minor( $patient_user_id ) : null,
\t\t\t\t);
\t\t\t\t$source = 'file00:legacy-read-projection';
\t\t\t}
"""
new="""\t\t\t$legacy_minor = null;
\t\t\tif ( function_exists( 'smc_user_is_minor' ) ) {
\t\t\t\ttry { $legacy_minor_raw = smc_user_is_minor( $patient_user_id ); } catch ( Throwable $e ) { return new WP_Error( 'wca_age_claim_provider_failure', __( 'Current minor eligibility could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t\tif ( is_wp_error( $legacy_minor_raw ) || ! in_array( $legacy_minor_raw, array( true, false, 1, 0, '1', '0' ), true ) ) {
\t\t\t\t\treturn new WP_Error( 'wca_age_claim_invalid_provider_response', __( 'Current minor eligibility returned an invalid response.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t\t\t\t}
\t\t\t\t$legacy_minor = true === $legacy_minor_raw || 1 === $legacy_minor_raw || '1' === $legacy_minor_raw;
\t\t\t}
\t\t\tif ( $birth || $gender || null !== $legacy_minor ) {
\t\t\t\t$raw = array(
\t\t\t\t\t'birth_date' => $birth,
\t\t\t\t\t'gender'     => $gender,
\t\t\t\t\t'is_minor'   => $legacy_minor,
\t\t\t\t);
\t\t\t\t$source = 'file00:legacy-read-projection';
\t\t\t}
"""
if s.count(old)!=1: raise SystemExit('legacy minor block mismatch')
s=s.replace(old,new,1)
old="""\t\t$projection = WCA_Service::public_clinic_projection( $clinic_ref );
\t\tif ( ! is_array( $projection ) || empty( $projection['verified_owner'] ) || empty( $projection['public_ref'] ) ) {
"""
new="""\t\t$projection = WCA_Service::public_clinic_projection( $clinic_ref );
\t\tif ( is_wp_error( $projection ) ) { return $projection; }
\t\tif ( ! is_array( $projection ) || empty( $projection['verified_owner'] ) || empty( $projection['public_ref'] ) ) {
"""
if s.count(old)!=1: raise SystemExit('File26 projection block mismatch')
s=s.replace(old,new,1)
p.write_text(s)

# Permanent R11 gates.
p=Path('tests/sixteenth-twenty-review-regressions.php'); s=p.read_text()
footer="if($fail){fwrite(STDERR,\"T16 regression gate failed:\\n- \".implode(\"\\n- \",$fail).\"\\n\");exit(1);} echo \"T16 regression assertions passed: {$pass}/{$pass}\\n\";"
if s.count(footer)!=1: raise SystemExit('T16 footer mismatch')
checks="""t16h('R11 canonical subject helper failure never falls back to stale meta','includes/class-wca-authorization.php',"if ( is_wp_error( $value ) || ! is_string( $value ) ) { return ''; }");
t16h('R11 identity filter cannot elevate approved claim','includes/class-wca-authorization.php',"$claims[ $monotonic_key ] = ! empty( $authoritative_claims[ $monotonic_key ] ) && ! empty( $filtered_claims[ $monotonic_key ] );");
t16h('R11 suspension filter is monotonic restrictive','includes/class-wca-authorization.php',"$claims['suspended'] = ! empty( $authoritative_claims['suspended'] ) || ! empty( $filtered_claims['suspended'] );");
t16h('R11 doctor serving filter cannot create native relationship','includes/class-wca-authorization.php','return $allowed && $filtered;');
t16h('R11 delegation scope filter cannot create a native scope','includes/class-wca-authorization.php','return $direct && $filtered;');
t16h('R11 canonical File00 age claim cannot be overwritten by local filter','includes/class-wca-central-governance.php','if ( ! $versioned_attempted ) {');
t16h('R11 legacy minor helper invalid response fails closed','includes/class-wca-central-governance.php','wca_age_claim_invalid_provider_response');
t16h('R11 File26 projection propagates upstream repository errors','includes/class-wca-central-governance.php','if ( is_wp_error( $projection ) ) { return $projection; }');
"""
s=s.replace(footer,checks+footer,1); p.write_text(s)
print('R11 closed defect ledger applied')
