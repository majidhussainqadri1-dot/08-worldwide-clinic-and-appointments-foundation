from pathlib import Path
import re
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
 wr(p,s.replace(a,b,1))
def rx(p,pat,repl):
 s=rd(p); out,n=re.subn(pat,repl,s,count=1,flags=re.S)
 if n!=1: raise SystemExit(f'{p}: regex expected 1 got {n}')
 wr(p,out)

# CF01 must use the canonical File 08 object authorization root. Generic admin capability alone is not enough.
p='includes/class-swc-cf01-care-context.php'
once(p,"\t\tif ( ! self::actor_can_read( $appointment_id, $actor_id ) ) {\n\t\t\t$envelope['result'] = 'deny';","\t\t$object_access = self::actor_can_read( $appointment_id, $actor_id );\n\t\tif ( is_wp_error( $object_access ) || ! $object_access ) {\n\t\t\t$envelope['result'] = 'deny';")
rx(p,r"\tprivate static function actor_can_read\( \$appointment_id, \$actor_id \) \{.*?\n\t\}\n\n\tprivate static function context_state",'''\tprivate static function actor_can_read( $appointment_id, $actor_id ) {
\t\tif ( ! $actor_id || get_current_user_id() !== $actor_id ) { return false; }
\t\tif ( class_exists( 'WCA_Authorization' ) ) {
\t\t\treturn WCA_Authorization::can_view_appointment( $appointment_id, $actor_id, '' );
\t\t}
\t\treturn false;
\t}

\tprivate static function context_state''')

# File09 -> File08 -> File26 reconciliation must be explicit, atomic per clinic, and retryable.
p='includes/class-wca-verification-reconciliation.php'
once(p,"\t\tadd_action( 'wca_doctor_verified', array( __CLASS__, 'doctor_reverified' ), 20, 1 );","\t\tadd_action( 'wca_doctor_verified', array( __CLASS__, 'doctor_reverified' ), 20, 1 );\n\t\tadd_action( 'wca_retry_doctor_eligibility_reconciliation', array( __CLASS__, 'retry' ), 20, 3 );")
once(p,"\tpublic static function doctor_ineligible( $doctor_user_id, $reason = '' ) {\n\t\tself::publish_clinic_eligibility( absint( $doctor_user_id ), false, sanitize_text_field( $reason ) );\n\t}\n\n\tpublic static function doctor_reverified( $doctor_user_id ) {\n\t\tself::publish_clinic_eligibility( absint( $doctor_user_id ), true, 'verification_restored' );\n\t}","\tpublic static function doctor_ineligible( $doctor_user_id, $reason = '' ) {\n\t\tself::run_or_retry( absint( $doctor_user_id ), false, sanitize_text_field( $reason ) );\n\t}\n\n\tpublic static function doctor_reverified( $doctor_user_id ) {\n\t\tself::run_or_retry( absint( $doctor_user_id ), true, 'verification_restored' );\n\t}\n\n\tpublic static function retry( $doctor_user_id, $eligible, $reason ) {\n\t\tself::run_or_retry( absint( $doctor_user_id ), (bool) $eligible, sanitize_text_field( $reason ) );\n\t}\n\n\tprivate static function run_or_retry( $doctor_user_id, $eligible, $reason ) {\n\t\t$result = self::publish_clinic_eligibility( $doctor_user_id, $eligible, $reason );\n\t\tif ( ! is_wp_error( $result ) ) { return true; }\n\t\tWCA_Observability::log( 'error', 'verification_reconciliation_failed', array( 'doctor_user_id' => $doctor_user_id, 'eligible' => $eligible ? 'yes' : 'no', 'error_code' => $result->get_error_code() ) );\n\t\t$args = array( $doctor_user_id, $eligible ? 1 : 0, $reason );\n\t\tif ( ! wp_next_scheduled( 'wca_retry_doctor_eligibility_reconciliation', $args ) ) { wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'wca_retry_doctor_eligibility_reconciliation', $args ); }\n\t\treturn $result;\n\t}")
once(p,"\tprivate static function publish_clinic_eligibility( $doctor_user_id, $eligible, $reason ) {\n\t\tif ( ! $doctor_user_id ) { return; }","\tprivate static function publish_clinic_eligibility( $doctor_user_id, $eligible, $reason ) {\n\t\tglobal $wpdb;\n\t\tif ( ! $doctor_user_id ) { return new WP_Error( 'wca_verification_reconciliation_doctor', __( 'A doctor identity is required for reconciliation.', 'worldwide-clinic-appointments' ) ); }")
once(p,"\t\t\t$clinics = WCA_Repository::list_clinics( array(\n\t\t\t\t'owner_user_id' => $doctor_user_id,\n\t\t\t\t'status'        => '',\n\t\t\t\t'page'          => $page,\n\t\t\t\t'per_page'      => 100,\n\t\t\t) );\n\t\t\tforeach ( (array) $clinics as $clinic ) {","\t\t\t$clinics = WCA_Repository::list_clinics( array(\n\t\t\t\t'owner_user_id' => $doctor_user_id,\n\t\t\t\t'status'        => '',\n\t\t\t\t'page'          => $page,\n\t\t\t\t'per_page'      => 100,\n\t\t\t) );\n\t\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_verification_reconciliation_read', __( 'Owned clinics could not be read safely for verification reconciliation.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\t\tforeach ( (array) $clinics as $clinic ) {")
# Replace unverified append/enqueue with one owner transaction and fail closed.
once(p,"\t\t\t\tWCA_Repository::append_event( 'ClinicEligibilityChanged.v1', 'clinic', $clinic_ref, $payload, 0, $trace );\n\t\t\t\tWCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(\n\t\t\t\t\t'contract'      => 'wca.file26-clinic-projection',\n\t\t\t\t\t'version'       => WCA_Central_Governance::FILE26_PROJECTION_VERSION,\n\t\t\t\t\t'object_type'   => 'clinic',\n\t\t\t\t\t'public_ref'    => strtolower( $clinic_ref ),\n\t\t\t\t\t'eligible'      => (bool) $eligible,\n\t\t\t\t\t'change_source' => 'ClinicEligibilityChanged.v1',\n\t\t\t\t\t'owner'         => 'File08',\n\t\t\t\t), $trace );","\t\t\t\t$written = WCA_Repository::transaction( static function () use ( $clinic_ref, $payload, $trace, $eligible ) {\n\t\t\t\t\t$event = WCA_Repository::append_event( 'ClinicEligibilityChanged.v1', 'clinic', $clinic_ref, $payload, 0, $trace );\n\t\t\t\t\tif ( is_wp_error( $event ) ) { return $event; }\n\t\t\t\t\t$outbox = WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(\n\t\t\t\t\t\t'contract'      => 'wca.file26-clinic-projection',\n\t\t\t\t\t\t'version'       => WCA_Central_Governance::FILE26_PROJECTION_VERSION,\n\t\t\t\t\t\t'object_type'   => 'clinic',\n\t\t\t\t\t\t'public_ref'    => strtolower( $clinic_ref ),\n\t\t\t\t\t\t'eligible'      => (bool) $eligible,\n\t\t\t\t\t\t'change_source' => 'ClinicEligibilityChanged.v1',\n\t\t\t\t\t\t'owner'         => 'File08',\n\t\t\t\t\t), $trace );\n\t\t\t\t\treturn is_wp_error( $outbox ) ? $outbox : true;\n\t\t\t\t}, 'wca_verification_reconciliation_write' );\n\t\t\t\tif ( is_wp_error( $written ) ) { return $written; }")
once(p,"\t\tWCA_Observability::metric( 'verification_reconciliation_total', 1, array( 'eligible' => $eligible ? 'yes' : 'no' ) );\n\t}\n}","\t\tWCA_Observability::metric( 'verification_reconciliation_total', 1, array( 'eligible' => $eligible ? 'yes' : 'no' ) );\n\t\treturn true;\n\t}\n}")

# File19 WP_Error must never be truthy success.
p='includes/class-wca-outbox.php'
once(p,"\t\t\tif ( false === $result ) {\n\t\t\t\tWCA_Observability::circuit_failure( $provider, 'File 19 rejected the notification.' );\n\t\t\t\treturn new WP_Error( 'wca_file19_delivery', 'File 19 rejected the notification.' );\n\t\t\t}","\t\t\tif ( is_wp_error( $result ) || false === $result ) {\n\t\t\t\t$message = is_wp_error( $result ) ? $result->get_error_message() : 'File 19 rejected the notification.';\n\t\t\t\tWCA_Observability::circuit_failure( $provider, $message );\n\t\t\t\treturn new WP_Error( 'wca_file19_delivery', $message );\n\t\t\t}")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R13 CF01 uses canonical appointment authorization','includes/class-swc-cf01-care-context.php','WCA_Authorization::can_view_appointment');
t15h('R13 verification reconciliation retry hook','includes/class-wca-verification-reconciliation.php','wca_retry_doctor_eligibility_reconciliation');
t15h('R13 verification projection writes transactional','includes/class-wca-verification-reconciliation.php','wca_verification_reconciliation_write');
t15h('R13 verification clinic read failure explicit','includes/class-wca-verification-reconciliation.php','wca_verification_reconciliation_read');
t15h('R13 File19 WP_Error rejected','includes/class-wca-outbox.php','is_wp_error( $result ) || false === $result');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
wr(p,s.replace(mark,ins+mark,1))
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R13 — cross-file ownership / integration / projection review

R13 completed before correction. CF-01 care context could bypass the canonical purpose-limited appointment authorization root for generic clinic administrators. File09 verification reconciliation ignored event/File26 outbox write failures and could treat a failed clinic page read as completion. File19 notification delivery treated WP_Error as a truthy success. The post-review batch routes CF-01 through object authorization, makes verification projection writes atomic/retryable and makes File19 provider errors explicit.

R13 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R14.**
"""; wr(p,s)
