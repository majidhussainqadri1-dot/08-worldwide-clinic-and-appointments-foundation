from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:120]!r}')
 wr(p,s.replace(a,b,1))

# Cross-plugin claims: only explicit supported scalar/boolean results may grant authority.
p='includes/class-wca-authorization.php'
once(p,"\t\t$status    = function_exists( 'smc_user_status' ) ? (string) smc_user_status( $user_id ) : 'unknown';\n\t\t$founder   = function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );","\t\t$status = 'unknown';\n\t\tif ( function_exists( 'smc_user_status' ) ) {\n\t\t\ttry { $status_raw = smc_user_status( $user_id ); $status = is_scalar( $status_raw ) ? (string) $status_raw : 'unknown'; } catch ( Throwable $e ) { $status = 'unknown'; }\n\t\t}\n\t\t$founder = false;\n\t\tif ( function_exists( 'smc_is_founder' ) ) {\n\t\t\ttry { $founder_raw = smc_is_founder( $user_id ); $founder = true === $founder_raw || 1 === $founder_raw || '1' === $founder_raw; } catch ( Throwable $e ) { $founder = false; }\n\t\t}")
once(p,"\tpublic static function is_guardian( $user_id ) {\n\t\tif ( function_exists( 'smc_user_is_verified_guardian' ) ) {\n\t\t\treturn (bool) smc_user_is_verified_guardian( absint( $user_id ) );\n\t\t}\n\t\treturn (bool) apply_filters( 'wca_user_is_verified_guardian', false, absint( $user_id ) );\n\t}","\tpublic static function is_guardian( $user_id ) {\n\t\tif ( function_exists( 'smc_user_is_verified_guardian' ) ) {\n\t\t\ttry { $result = smc_user_is_verified_guardian( absint( $user_id ) ); return true === $result || 1 === $result || '1' === $result; } catch ( Throwable $e ) { return false; }\n\t\t}\n\t\treturn true === apply_filters( 'wca_user_is_verified_guardian', false, absint( $user_id ) );\n\t}")
once(p,"\t\tif ( function_exists( 'smc_step_up_is_valid' ) && smc_step_up_is_valid( $user_id, sanitize_key( $purpose ) ) ) { return true; }\n\t\tif ( (bool) apply_filters( 'wca_step_up_is_valid', false, $user_id, sanitize_key( $purpose ) ) ) { return true; }","\t\tif ( function_exists( 'smc_step_up_is_valid' ) ) {\n\t\t\ttry { $step_result = smc_step_up_is_valid( $user_id, sanitize_key( $purpose ) ); if ( true === $step_result || 1 === $step_result || '1' === $step_result ) { return true; } } catch ( Throwable $e ) { /* fail closed below */ }\n\t\t}\n\t\tif ( true === apply_filters( 'wca_step_up_is_valid', false, $user_id, sanitize_key( $purpose ) ) ) { return true; }")
# guardian_context fallback strict helper result.
once(p,"\t\t$allowed = (bool) apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );\n\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) { $allowed = (bool) smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ); }","\t\t$allowed = true === apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );\n\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) {\n\t\t\ttry { $relationship = smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ); $allowed = true === $relationship || 1 === $relationship || '1' === $relationship; } catch ( Throwable $e ) { $allowed = false; }\n\t\t}")

p='includes/class-wca-central-governance.php'
# Propagate versioned helper errors and do not silently fall back after a canonical helper responded invalidly.
once(p,"\t\tif ( function_exists( 'smc_get_age_guardian_claim' ) ) {\n\t\t\t$raw = smc_get_age_guardian_claim( $patient_user_id );\n\t\t\t$source = 'file00:smc_get_age_guardian_claim';\n\t\t} elseif ( function_exists( 'smc_get_membership_claims' ) ) {\n\t\t\t$all = smc_get_membership_claims( $patient_user_id );","\t\t$versioned_attempted = false;\n\t\tif ( function_exists( 'smc_get_age_guardian_claim' ) ) {\n\t\t\t$versioned_attempted = true;\n\t\t\ttry { $raw = smc_get_age_guardian_claim( $patient_user_id ); } catch ( Throwable $e ) { return new WP_Error( 'wca_age_claim_provider_failure', __( 'Current age and guardian eligibility could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( is_wp_error( $raw ) ) { return $raw; }\n\t\t\t$source = 'file00:smc_get_age_guardian_claim';\n\t\t} elseif ( function_exists( 'smc_get_membership_claims' ) ) {\n\t\t\t$versioned_attempted = true;\n\t\t\ttry { $all = smc_get_membership_claims( $patient_user_id ); } catch ( Throwable $e ) { return new WP_Error( 'wca_age_claim_provider_failure', __( 'Current membership claims could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tif ( is_wp_error( $all ) ) { return $all; }")
once(p,"\t\tif ( ! is_array( $raw ) ) {\n\t\t\t$birth = '';","\t\tif ( ! is_array( $raw ) && $versioned_attempted ) {\n\t\t\treturn new WP_Error( 'wca_age_claim_invalid_provider_response', __( 'Current age and guardian eligibility returned an invalid response.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );\n\t\t}\n\t\tif ( ! is_array( $raw ) ) {\n\t\t\t$birth = '';")
# Replace both guardian relationship helper ternaries.
s=rd(p)
old="$allowed = function_exists( 'smc_guardian_may_act_for' ) ? (bool) smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ) : (bool) apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );"
if s.count(old)!=2: raise SystemExit(f'central guardian relationship count {s.count(old)}')
new="$allowed = true === apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );\n\t\t\tif ( function_exists( 'smc_guardian_may_act_for' ) ) { try { $rel = smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ); $allowed = true === $rel || 1 === $rel || '1' === $rel; } catch ( Throwable $e ) { $allowed = false; } }"
wr(p,s.replace(old,new))

p='includes/class-swc-doctor-authority.php'
once(p,"\t\t\t$founder = function_exists( 'smc_is_founder' ) && smc_is_founder( $user_id );","\t\t\t$founder_raw = function_exists( 'smc_is_founder' ) ? smc_is_founder( $user_id ) : false;\n\t\t\t$founder = true === $founder_raw || 1 === $founder_raw || '1' === $founder_raw;")
once(p,"\t\t\t$verified = gdo_user_is_verified( $user_id );","\t\t\t$verified_raw = gdo_user_is_verified( $user_id );\n\t\t\t$verified = true === $verified_raw || 1 === $verified_raw || '1' === $verified_raw;")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R7 strict Founder helper result','includes/class-wca-authorization.php','$founder_raw = smc_is_founder');
t15h('R7 strict guardian helper result','includes/class-wca-authorization.php','return true === $result || 1 === $result');
t15h('R7 strict step-up helper result','includes/class-wca-authorization.php','$step_result = smc_step_up_is_valid');
t15h('R7 versioned age claim failure is authoritative','includes/class-wca-central-governance.php','wca_age_claim_invalid_provider_response');
t15h('R7 doctor Founder result strict','includes/class-swc-doctor-authority.php','$founder_raw = function_exists');
t15h('R7 File09 verified result strict','includes/class-swc-doctor-authority.php','$verified_raw = gdo_user_is_verified');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
wr(p,s.replace(mark,ins+mark,1))
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R7 — File00/File09 claims, guardian/minor, consent/revocation review

R7 completed before correction. Cross-plugin helper returns were not uniformly type-checked: error objects or unexpected values could become truthy in Founder, guardian, step-up, guardian-relationship or verification decisions. Versioned age/guardian helper failure could also fall through to legacy metadata. The post-review batch makes authority grants explicit and fail-closed and treats a versioned provider response as authoritative rather than silently downgrading to legacy state.

R7 result: **SUPPORTED DEFECTS FOUND — full retest required before R8.**
"""; wr(p,s)
