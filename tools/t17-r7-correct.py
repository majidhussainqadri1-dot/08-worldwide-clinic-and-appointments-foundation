from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

def replace_once(path, old, new, label):
    p = ROOT / path
    s = p.read_text()
    n = s.count(old)
    if n != 1:
        raise SystemExit(f"{label}: expected exactly one match, found {n}")
    p.write_text(s.replace(old, new, 1))
    print(f"patched: {label}")

auth = 'includes/class-wca-authorization.php'
central = 'includes/class-wca-central-governance.php'
continuity = 'includes/class-wca-continuity-secure.php'
test = 'tests/seventeenth-twenty-review-regressions.php'

# Guardian identity must never be granted by a local filter when File 00 is unavailable.
replace_once(auth,
"\tpublic static function is_guardian( $user_id ) {\n\t\tif ( function_exists( 'smc_user_is_verified_guardian' ) ) {\n\t\t\ttry { $result = smc_user_is_verified_guardian( absint( $user_id ) ); return true === $result || 1 === $result || '1' === $result; } catch ( Throwable $e ) { return false; }\n\t\t}\n\t\treturn true === apply_filters( 'wca_user_is_verified_guardian', false, absint( $user_id ) );\n\t}\n",
"\tpublic static function is_guardian( $user_id ) {\n\t\tif ( ! function_exists( 'smc_user_is_verified_guardian' ) ) { return false; }\n\t\ttry { $result = smc_user_is_verified_guardian( absint( $user_id ) ); } catch ( Throwable $e ) { return false; }\n\t\tif ( is_wp_error( $result ) || ! in_array( $result, array( true, false, 1, 0, '1', '0' ), true ) ) { return false; }\n\t\t$verified = true === $result || 1 === $result || '1' === $result;\n\t\t$filtered = true === apply_filters( 'wca_user_is_verified_guardian', $verified, absint( $user_id ) );\n\t\treturn $verified && $filtered;\n\t}\n",
'guardian claim cannot be locally elevated')

# Appointment access follows the current File00 guardian relationship, not a stale appointment snapshot.
replace_once(auth,
"\t\t$guardian_id = absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) );\n\t\tif ( $guardian_id === $user_id && ! empty( $claims['guardian'] ) ) {\n\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );\n\t\t\t$guardian = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_id, $guardian_id, $user_id ) : true;\n\t\t\treturn is_wp_error( $guardian ) ? $guardian : true;\n\t\t}\n",
"\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );\n\t\tif ( $patient_id && $patient_id !== $user_id && ! empty( $claims['guardian'] ) && class_exists( 'WCA_Central_Governance' ) ) {\n\t\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $user_id, $user_id );\n\t\t\tif ( true === $guardian ) { return true; }\n\t\t\tif ( is_wp_error( $guardian ) ) {\n\t\t\t\t$error_data = $guardian->get_error_data();\n\t\t\t\t$error_status = is_array( $error_data ) ? absint( $error_data['status'] ?? 0 ) : 0;\n\t\t\t\tif ( $error_status >= 500 ) { return $guardian; }\n\t\t\t}\n\t\t}\n",
'appointment guardian access rechecks current relationship')

replace_once(auth,
"\t\tif ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }\n\t\tif ( absint( SWC_Helpers::meta( $appointment_id, 'guardian_user_id', 0 ) ) === $user_id ) { return 'guardian'; }\n\t\treturn 'patient';",
"\t\tif ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }\n\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );\n\t\tif ( $patient_id === $user_id ) { return 'patient'; }\n\t\tif ( $patient_id && class_exists( 'WCA_Central_Governance' ) ) {\n\t\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $user_id, $user_id );\n\t\t\tif ( true === $guardian ) { return 'guardian'; }\n\t\t}\n\t\treturn 'patient';",
'appointment actor uses current guardian truth')

old_delegation = """\tpublic static function has_active_clinic_delegation( $user_id = 0 ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\tforeach ( self::delegations( $user_id ) as $entry ) {
\t\t\tif ( is_array( $entry ) && ! empty( $entry['active'] ) ) { return true; }
\t\t}
\t\treturn false;
\t}

\t/** @return array<string,mixed> */
\tprivate static function delegations( $user_id ) {
\t\t$value = get_user_meta( absint( $user_id ), '_wca_clinic_delegations', true );
\t\treturn is_array( $value ) ? $value : array();
\t}

\t/** @return array<string,mixed> */
\tprivate static function clinic_delegation( $user_id, $clinic_id ) {
\t\t$all = self::delegations( absint( $user_id ) );
\t\t$entry = isset( $all[ absint( $clinic_id ) ] ) && is_array( $all[ absint( $clinic_id ) ] ) ? $all[ absint( $clinic_id ) ] : array();
\t\treturn ! empty( $entry['active'] ) ? $entry : array();
\t}

\tprivate static function delegation_allows_scope( $entry, $scope ) {
\t\tif ( ! is_array( $entry ) || empty( $entry['active'] ) ) { return false; }
\t\t$scope = sanitize_key( $scope );
\t\t$scopes = isset( $entry['scopes'] ) && is_array( $entry['scopes'] ) ? array_map( 'sanitize_key', $entry['scopes'] ) : array();
\t\t$direct = ! empty( $entry[ $scope ] ) || in_array( $scope, $scopes, true );
\t\tif ( 'appointments' === $scope ) {
\t\t\t// Appointment visibility/operations require an explicit appointment grant.
\t\t\t// Schedule-only or clinical-followup grants must never broaden into appointment access.
\t\t\t$direct = $direct || ! empty( $entry['appointment_ops'] ) || in_array( 'appointment_ops', $scopes, true );
\t\t} elseif ( 'clinical_followup' === $scope ) {
\t\t\t$direct = $direct || ! empty( $entry['clinical'] ) || in_array( 'clinical', $scopes, true );
\t\t} elseif ( 'clinic_manage' === $scope ) {
\t\t\t// Management is explicit; schedule/appointments grants are narrower and cannot escalate.
\t\t\t$direct = $direct || ! empty( $entry['manage'] ) || in_array( 'manage', $scopes, true );
\t\t}
\t\t$filtered = (bool) apply_filters( 'wca_clinic_delegation_allows_scope', $direct, $entry, $scope );
\t\treturn $direct && $filtered;
\t}
"""
new_delegation = """\tpublic static function has_active_clinic_delegation( $user_id = 0 ) {
\t\t$user_id = absint( $user_id ?: get_current_user_id() );
\t\tforeach ( self::delegations( $user_id ) as $entry ) {
\t\t\tif ( self::delegation_is_current( $entry ) ) { return true; }
\t\t}
\t\treturn false;
\t}

\t/** @return array<string,mixed> */
\tprivate static function delegations( $user_id ) {
\t\t$value = get_user_meta( absint( $user_id ), '_wca_clinic_delegations', true );
\t\treturn is_array( $value ) ? $value : array();
\t}

\tprivate static function delegation_timestamp( $value ) {
\t\tif ( is_int( $value ) ) { return $value > 0 ? $value : 0; }
\t\tif ( is_string( $value ) && ctype_digit( $value ) ) { return (int) $value; }
\t\tif ( ! is_string( $value ) || '' === trim( $value ) ) { return 0; }
\t\t$timestamp = strtotime( trim( $value ) );
\t\treturn false === $timestamp ? 0 : (int) $timestamp;
\t}

\tprivate static function delegation_is_current( $entry ) {
\t\tif ( ! is_array( $entry ) || empty( $entry['active'] ) || ! empty( $entry['revoked'] ) || ! empty( $entry['revoked_at'] ) ) { return false; }
\t\t$issued_at = self::delegation_timestamp( $entry['issued_at'] ?? ( $entry['granted_at'] ?? '' ) );
\t\t$expires_at = self::delegation_timestamp( $entry['expires_at'] ?? ( $entry['valid_until'] ?? '' ) );
\t\t$now = time();
\t\tif ( ! $issued_at || ! $expires_at || $issued_at > $now + 300 || $expires_at <= $now || $expires_at <= $issued_at ) { return false; }
\t\tif ( $expires_at - $issued_at > 90 * DAY_IN_SECONDS ) { return false; }
\t\treturn true;
\t}

\t/** @return array<string,mixed> */
\tprivate static function clinic_delegation( $user_id, $clinic_id ) {
\t\t$all = self::delegations( absint( $user_id ) );
\t\t$entry = isset( $all[ absint( $clinic_id ) ] ) && is_array( $all[ absint( $clinic_id ) ] ) ? $all[ absint( $clinic_id ) ] : array();
\t\treturn self::delegation_is_current( $entry ) ? $entry : array();
\t}

\tprivate static function delegation_allows_scope( $entry, $scope ) {
\t\tif ( ! self::delegation_is_current( $entry ) ) { return false; }
\t\t$scope = sanitize_key( $scope );
\t\t$allowed_scopes = array( 'appointments', 'appointment_ops', 'schedule', 'clinic_manage', 'manage', 'clinical_followup' );
\t\tif ( ! in_array( $scope, $allowed_scopes, true ) ) { return false; }
\t\t$scopes = isset( $entry['scopes'] ) && is_array( $entry['scopes'] ) ? array_values( array_intersect( array_map( 'sanitize_key', $entry['scopes'] ), $allowed_scopes ) ) : array();
\t\t$direct = ! empty( $entry[ $scope ] ) || in_array( $scope, $scopes, true );
\t\tif ( 'appointments' === $scope ) {
\t\t\t$direct = $direct || ! empty( $entry['appointment_ops'] ) || in_array( 'appointment_ops', $scopes, true );
\t\t} elseif ( 'clinic_manage' === $scope ) {
\t\t\t$direct = $direct || ! empty( $entry['manage'] ) || in_array( 'manage', $scopes, true );
\t\t}
\t\t$filtered = (bool) apply_filters( 'wca_clinic_delegation_allows_scope', $direct, $entry, $scope );
\t\treturn $direct && $filtered;
\t}
"""
replace_once(auth, old_delegation, new_delegation, 'delegation currentness expiry and scoped authority')

# No local guardian relationship fallback if the central governance class itself is unavailable.
old_fallback = """\t\tif ( class_exists( 'WCA_Central_Governance' ) ) {
\t\t\treturn WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
\t\t}
\t\tif ( ! $guardian_user_id ) {
\t\t\treturn $patient_user_id === $actor_user_id ? true : new WP_Error( 'wca_patient_actor_mismatch', __( 'A user may only make a personal request or act through a verified guardian relationship.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
\t\t}
\t\tif ( $guardian_user_id !== $actor_user_id || ! self::is_guardian( $guardian_user_id ) ) {
\t\t\treturn new WP_Error( 'wca_guardian_unverified', __( 'The current actor must be the verified guardian.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
\t\t}
\t\t$allowed = true === apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );
\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) {
\t\t\ttry { $relationship = smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ); $allowed = true === $relationship || 1 === $relationship || '1' === $relationship; } catch ( Throwable $e ) { $allowed = false; }
\t\t}
\t\treturn $allowed ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
"""
new_fallback = """\t\tif ( class_exists( 'WCA_Central_Governance' ) ) {
\t\t\treturn WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
\t\t}
\t\treturn new WP_Error( 'wca_guardian_authority_unavailable', __( 'Current patient and guardian authority cannot be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
"""
replace_once(auth, old_fallback, new_fallback, 'guardian context fails closed without central governance')

# File00 canonical guardian helpers are mandatory; local filters can only restrict canonical truth.
replace_once(central,
"\t\treturn true === apply_filters( 'wca_user_is_verified_guardian', false, absint( $guardian_user_id ) );",
"\t\treturn new WP_Error( 'wca_guardian_verification_provider_unavailable', __( 'Current guardian verification provider is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'guardian verification provider absence is degraded')
replace_once(central,
"\t\treturn true === apply_filters( 'wca_guardian_may_act_for_patient', false, absint( $guardian_user_id ), absint( $patient_user_id ) );",
"\t\treturn new WP_Error( 'wca_guardian_relationship_provider_unavailable', __( 'Current guardian relationship provider is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'guardian relationship provider absence is degraded')
replace_once(central,
"\t\t\t$allowed = true === apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );\n\t\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) { $allowed = self::strict_guardian_relationship( $guardian_user_id, $patient_user_id ); if ( is_wp_error( $allowed ) ) { return $allowed; } }\n\t\t\treturn $allowed ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not currently authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );",
"\t\t\t$allowed = self::strict_guardian_relationship( $guardian_user_id, $patient_user_id );\n\t\t\tif ( is_wp_error( $allowed ) ) { return $allowed; }\n\t\t\t$filtered = true === apply_filters( 'wca_guardian_may_act_for_patient', $allowed, $guardian_user_id, $patient_user_id );\n\t\t\treturn $allowed && $filtered ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not currently authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );",
'minor guardian relationship cannot be locally elevated')
replace_once(central,
"\t\t\t$allowed = true === apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );\n\t\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) { $allowed = self::strict_guardian_relationship( $guardian_user_id, $patient_user_id ); if ( is_wp_error( $allowed ) ) { return $allowed; } }\n\t\t\treturn $allowed ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not currently authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );",
"\t\t\t$allowed = self::strict_guardian_relationship( $guardian_user_id, $patient_user_id );\n\t\t\tif ( is_wp_error( $allowed ) ) { return $allowed; }\n\t\t\t$filtered = true === apply_filters( 'wca_guardian_may_act_for_patient', $allowed, $guardian_user_id, $patient_user_id );\n\t\t\treturn $allowed && $filtered ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not currently authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );",
'adult guardian relationship cannot be locally elevated')

# Context consent actor is current patient or current File00 guardian, not stale appointment snapshot actor classification.
replace_once(continuity,
"\t\t$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );\n\t\tif ( ! in_array( $actor, array( 'patient', 'guardian' ), true ) ) {\n\t\t\treturn new WP_Error( 'wca_consent_actor', __( 'Only the patient or verified guardian may grant this consent.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );\n\t\t}\n\t\t$scope = sanitize_key( $scope );",
"\t\t$patient_user_id = self::patient_id( $appointment_id );\n\t\t$guardian_user_id = $actor_user_id === $patient_user_id ? 0 : $actor_user_id;\n\t\t$authority = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );\n\t\tif ( is_wp_error( $authority ) ) { return $authority; }\n\t\t$scope = sanitize_key( $scope );",
'consent grant uses current guardian authority')
replace_once(continuity,
"\t\t$patient_user_id  = self::patient_id( $appointment_id );\n\t\t$guardian_user_id = 'guardian' === $actor ? $actor_user_id : 0;\n\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );\n\t\tif ( is_wp_error( $guardian ) ) { return $guardian; }\n",
"",
'remove duplicate stale actor guardian validation')

replace_once(continuity,
"\t\t$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );\n\t\tif ( ! in_array( $actor, array( 'patient', 'guardian' ), true ) ) { return new WP_Error( 'wca_consent_actor', __( 'Only the patient or verified guardian may revoke this consent.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }\n\t\t$scope = sanitize_key( $scope );",
"\t\t$patient_user_id = self::patient_id( $appointment_id );\n\t\t$guardian_user_id = $actor_user_id === $patient_user_id ? 0 : $actor_user_id;\n\t\t$authority = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );\n\t\tif ( is_wp_error( $authority ) ) { return $authority; }\n\t\t$scope = sanitize_key( $scope );",
'consent revocation uses current guardian authority')
replace_once(continuity,
"\t\t$result = WCA_Repository::transaction( function () use ( $table, $appointment_id, $scope, $actor_user_id ) {\n\t\t\tglobal $wpdb;\n\t\t\t$changed = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND scope=%s AND actor_user_id=%d AND status='granted'\", WCA_Repository::now(), $appointment_id, $scope, $actor_user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( false === $changed ) { return new WP_Error( 'wca_consent_revoke', __( 'Consent could not be revoked.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\t\tif ( 0 === (int) $changed ) { return new WP_Error( 'wca_consent_not_active', __( 'No active consent matched this revocation request.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t\t$event = WCA_Repository::append_event( 'AppointmentConsentRevoked.v1', 'appointment', self::appointment_ref( $appointment_id ), array( 'appointment_ref' => self::appointment_ref( $appointment_id ), 'scope' => $scope ), $actor_user_id, WCA_Observability::trace_id() );",
"\t\t$result = WCA_Repository::transaction( function () use ( $table, $appointment_id, $scope, $actor_user_id ) {\n\t\t\tglobal $wpdb;\n\t\t\t$changed = $wpdb->query( $wpdb->prepare( \"UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL\", WCA_Repository::now(), $appointment_id, $scope ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( false === $changed ) { return new WP_Error( 'wca_consent_revoke', __( 'Consent could not be revoked.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\t\tif ( 0 === (int) $changed ) { return new WP_Error( 'wca_consent_not_active', __( 'No active consent matched this revocation request.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t\t$event = WCA_Repository::append_event( 'AppointmentConsentRevoked.v1', 'appointment', self::appointment_ref( $appointment_id ), array( 'appointment_ref' => self::appointment_ref( $appointment_id ), 'scope' => $scope, 'revoked_grants' => (int) $changed ), $actor_user_id, WCA_Observability::trace_id() );",
'current consent revocation invalidates all active grants for scope')

old_active = """\t/** @return bool|WP_Error */
\tprivate static function active_consent( $appointment_id, $scope ) {
\t\tglobal $wpdb;
\t\t$table = WCA_Schema::tables()['consents'];
\t\t$count = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL\", absint( $appointment_id ), sanitize_key( $scope ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_active_consent_read_failed', __( 'Current consent state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn absint( $count ) > 0;
\t}
"""
new_active = """\t/** @return bool|WP_Error */
\tprivate static function active_consent( $appointment_id, $scope ) {
\t\tglobal $wpdb;
\t\t$appointment_id = absint( $appointment_id );
\t\t$scope = sanitize_key( $scope );
\t\t$patient_user_id = self::patient_id( $appointment_id );
\t\tif ( ! $appointment_id || ! $patient_user_id ) { return false; }
\t\t$table = WCA_Schema::tables()['consents'];
\t\t$cursor = PHP_INT_MAX;
\t\tdo {
\t\t\t$wpdb->last_error = '';
\t\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT id,actor_user_id,guardian_user_id FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL AND id<%d ORDER BY id DESC LIMIT 100\", $appointment_id, $scope, $cursor ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_active_consent_read_failed', __( 'Current consent state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t$rows = (array) $rows_raw;
\t\t\tforeach ( $rows as $row ) {
\t\t\t\t$actor_user_id = absint( $row['actor_user_id'] ?? 0 );
\t\t\t\t$guardian_user_id = absint( $row['guardian_user_id'] ?? 0 );
\t\t\t\tif ( ! $actor_user_id || ( $guardian_user_id && $guardian_user_id !== $actor_user_id ) || ( ! $guardian_user_id && $actor_user_id !== $patient_user_id ) ) { continue; }
\t\t\t\t$current = WCA_Central_Governance::validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id );
\t\t\t\tif ( true === $current ) { return true; }
\t\t\t\tif ( is_wp_error( $current ) ) {
\t\t\t\t\t$error_data = $current->get_error_data();
\t\t\t\t\t$error_status = is_array( $error_data ) ? absint( $error_data['status'] ?? 0 ) : 0;
\t\t\t\t\tif ( $error_status >= 500 ) { return $current; }
\t\t\t\t}
\t\t\t}
\t\t\tif ( $rows ) { $cursor = absint( end( $rows )['id'] ?? 0 ); }
\t\t} while ( 100 === count( $rows ) && $cursor > 0 );
\t\treturn false;
\t}
"""
replace_once(continuity, old_active, new_active, 'active consent revalidates current guardian relationship')

replace_once(continuity,
"\tprivate static function followup_actor_allowed( $appointment_id, $user_id ) { $user_id=absint($user_id); if('doctor'===WCA_Authorization::appointment_actor($appointment_id,$user_id)){return true;} $clinic_id=absint(SWC_Helpers::meta($appointment_id,'clinic_id',0)); $delegated=(array)get_user_meta($user_id,'_wca_clinic_delegations',true); $entry=isset($delegated[$clinic_id])&&is_array($delegated[$clinic_id])?$delegated[$clinic_id]:array(); $allowed=!empty($entry['active'])&&(!empty($entry['clinical_followup'])||!empty($entry['clinical'])); return (bool)apply_filters('wca_followup_actor_allowed',$allowed,$appointment_id,$user_id); }",
"\tprivate static function followup_actor_allowed( $appointment_id, $user_id ) { $user_id=absint($user_id); if('doctor'===WCA_Authorization::appointment_actor($appointment_id,$user_id)){return true;} $allowed=WCA_Authorization::can_staff_access_appointment($appointment_id,$user_id,'clinical_followup'); $filtered=(bool)apply_filters('wca_followup_actor_allowed',$allowed,$appointment_id,$user_id); return $allowed&&$filtered; }",
'followup uses central scoped delegation and restrictive filter')

# Permanent T17 R7 regression coverage.
p = ROOT / test
s = p.read_text()
s = s.replace("$future = file_get_contents( $root . '/includes/class-wca-future24.php' );\n", "$future = file_get_contents( $root . '/includes/class-wca-future24.php' );\n$auth = file_get_contents( $root . '/includes/class-wca-authorization.php' );\n$central = file_get_contents( $root . '/includes/class-wca-central-governance.php' );\n$continuity = file_get_contents( $root . '/includes/class-wca-continuity-secure.php' );\n", 1)
anchor = "    'R6 repository mutation roots verify readback' => false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_clinic_readback_missing' ) && false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_service_readback_missing' ) && false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_availability_readback_missing' ),\n"
if s.count(anchor) != 1:
    raise SystemExit('T17 R7 test anchor mismatch')
addition = anchor + "    'R7 local filter cannot grant guardian identity without File00' => false !== strpos( $auth, \"if ( ! function_exists( 'smc_user_is_verified_guardian' ) ) { return false; }\" ),\n    'R7 missing guardian provider is explicit degraded state' => false !== strpos( $central, 'wca_guardian_verification_provider_unavailable' ) && false !== strpos( $central, 'wca_guardian_relationship_provider_unavailable' ),\n    'R7 current guardian relationship drives appointment access' => false !== strpos( $auth, 'validate_patient_guardian( $patient_id, $user_id, $user_id )' ),\n    'R7 delegation requires bounded current expiry' => false !== strpos( $auth, 'private static function delegation_is_current' ) && false !== strpos( $auth, '90 * DAY_IN_SECONDS' ),\n    'R7 blanket clinical delegation no longer broadens followup' => false === strpos( $auth, \"! empty( $entry['clinical'] )\" ),\n    'R7 active consent revalidates current guardian truth' => false !== strpos( $continuity, 'SELECT id,actor_user_id,guardian_user_id' ) && false !== strpos( $continuity, 'validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id )' ),\n    'R7 consent revocation clears all active grants for scope' => false !== strpos( $continuity, \"scope=%s AND status='granted' AND revoked_at IS NULL\" ) && false === strpos( $continuity, \"scope=%s AND actor_user_id=%d AND status='granted'\" ),\n    'R7 followup uses canonical scoped delegation' => false !== strpos( $continuity, \"can_staff_access_appointment($appointment_id,$user_id,'clinical_followup')\" ) && false !== strpos( $continuity, 'return $allowed&&$filtered;' ),\n"
p.write_text(s.replace(anchor, addition, 1))
print('patched: permanent T17 R7 regression coverage')
