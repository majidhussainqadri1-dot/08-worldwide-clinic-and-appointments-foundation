from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:100]!r}')
 wr(p,s.replace(a,b,1))

p='includes/class-wca-authorization.php'
once(p,"\t\t\tSWC_Helpers::audit( $appointment_id, 'purpose-limited-admin-access', array( 'reason' => $purpose, 'source' => 'authorization' ) );\n\t\t\treturn true;","\t\t\tif ( ! SWC_Helpers::audit( $appointment_id, 'purpose-limited-admin-access', array( 'reason' => $purpose, 'source' => 'authorization' ) ) ) {\n\t\t\t\treturn new WP_Error( 'wca_admin_access_audit_failed', __( 'Purpose-limited administrative access could not be audited safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );\n\t\t\t}\n\t\t\treturn true;")

p='includes/class-wca-plan-guard.php'
once(p,"\t\t$lock = 'wca-practitioner-ref-' . $user_id;\n\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock ) ) ) { return ''; }","\t\t$lock = 'wca-practitioner-ref-' . $user_id;\n\t\t$lock_raw = $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,3)', $lock ) );\n\t\tif ( null === $lock_raw && '' !== (string) $wpdb->last_error ) { return ''; }\n\t\tif ( 1 !== (int) $lock_raw ) { return ''; }")
once(p,"\t\t\t\t$candidate = WCA_Repository::uuid();\n\t\t\t\tupdate_user_meta( $user_id, '_wca_practitioner_ref', $candidate );\n\t\t\t\t$ref = strtolower( (string) get_user_meta( $user_id, '_wca_practitioner_ref', true ) );","\t\t\t\t$candidate = WCA_Repository::uuid();\n\t\t\t\t$written = update_user_meta( $user_id, '_wca_practitioner_ref', $candidate );\n\t\t\t\tif ( false === $written ) { return ''; }\n\t\t\t\t$ref = strtolower( (string) get_user_meta( $user_id, '_wca_practitioner_ref', true ) );")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R6 admin access audit fail closed','includes/class-wca-authorization.php','wca_admin_access_audit_failed');
t15h('R6 practitioner lock read separated','includes/class-wca-plan-guard.php','$lock_raw = $wpdb->get_var');
t15h('R6 practitioner ref persistence checked','includes/class-wca-plan-guard.php','if ( false === $written )');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
wr(p,s.replace(mark,ins+mark,1))
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R6 — authorization / ownership / opaque-reference review

R6 completed before correction. Object-level patient/doctor/guardian/staff checks and serving/delegation scopes were re-traced. Two supported gaps remained: purpose-limited administrative appointment access did not fail closed when its mandatory audit write failed; opaque practitioner-reference creation did not separately handle DB lock failure or metadata persistence failure. The R6 batch corrects both classes after review completion.

R6 result: **SUPPORTED DEFECTS FOUND — full retest required before R7.**
"""; wr(p,s)
