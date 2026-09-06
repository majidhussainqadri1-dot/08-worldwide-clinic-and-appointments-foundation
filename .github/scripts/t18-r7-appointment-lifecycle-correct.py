from pathlib import Path

# R7-1: governed command must propagate hold/service storage failures before dereferencing.
p=Path('includes/class-wca-appointment-command.php'); s=p.read_text()
old="""\t\t$hold = $hold_token ? WCA_Repository::get_slot_hold( $hold_token ) : null;\n\t\tif ( ! $hold ) {\n\t\t\treturn new WP_Error( 'wca_hold_missing', __( 'The selected appointment hold is unavailable or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$service = ! empty( $hold['service_id'] ) ? WCA_Repository::get_service( absint( $hold['service_id'] ), true ) : null;"""
new="""\t\t$hold = $hold_token ? WCA_Repository::get_slot_hold( $hold_token ) : null;\n\t\tif ( is_wp_error( $hold ) ) { return $hold; }\n\t\tif ( ! $hold ) {\n\t\t\treturn new WP_Error( 'wca_hold_missing', __( 'The selected appointment hold is unavailable or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\tWCA_Repository::clear_read_error();\n\t\t$service = ! empty( $hold['service_id'] ) ? WCA_Repository::get_service( absint( $hold['service_id'] ), true ) : null;\n\t\t$service_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $service_read_error ) ) { return $service_read_error; }"""
if s.count(old)!=1: raise SystemExit('R7 appointment-command read anchor not unique')
s=s.replace(old,new,1); p.write_text(s)

# R7-2: Plan Guard reads must preserve repository storage-failure semantics.
p=Path('includes/class-wca-plan-guard.php'); s=p.read_text()
anchor="final class WCA_Plan_Guard {\n\tconst REVIEW_ELIGIBILITY_DAYS = 180;"
insert="""final class WCA_Plan_Guard {\n\tconst REVIEW_ELIGIBILITY_DAYS = 180;\n\n\t/** Execute a repository read and propagate the repository's fail-closed read channel. */\n\tprivate static function repository_read( $callback ) {\n\t\tWCA_Repository::clear_read_error();\n\t\t$result = call_user_func( $callback );\n\t\t$error = WCA_Repository::consume_read_error();\n\t\treturn is_wp_error( $error ) ? $error : $result;\n\t}"""
if s.count(anchor)!=1: raise SystemExit('R7 Plan Guard helper anchor not unique')
s=s.replace(anchor,insert,1)
old="""\t\t$clinic = WCA_Repository::get_clinic( sanitize_text_field( $args['clinic_ref'] ?? '' ), true );\n\t\t$service = WCA_Repository::get_service_by_ref( sanitize_text_field( $args['service_ref'] ?? '' ), true );\n\t\t$doctor_id = self::practitioner_id( $args['practitioner_ref'] ?? '' );\n\t\tif ( ! $clinic || ! $service || ! $doctor_id ) {"""
new="""\t\t$clinic_ref = sanitize_text_field( $args['clinic_ref'] ?? '' );\n\t\t$service_ref = sanitize_text_field( $args['service_ref'] ?? '' );\n\t\t$clinic = self::repository_read( static function () use ( $clinic_ref ) { return WCA_Repository::get_clinic( $clinic_ref, true ); } );\n\t\tif ( is_wp_error( $clinic ) ) { return $clinic; }\n\t\t$service = self::repository_read( static function () use ( $service_ref ) { return WCA_Repository::get_service_by_ref( $service_ref, true ); } );\n\t\tif ( is_wp_error( $service ) ) { return $service; }\n\t\t$doctor_id = self::practitioner_id( $args['practitioner_ref'] ?? '' );\n\t\tif ( ! $clinic || ! $service || ! $doctor_id ) {"""
if s.count(old)!=1: raise SystemExit('R7 public-slot read anchor not unique')
s=s.replace(old,new,1)
old="\t\t$rule = WCA_Repository::get_availability_rule_by_ref( sanitize_text_field( $data['rule_ref'] ?? '' ), true );"
new="""\t\t$rule_ref = sanitize_text_field( $data['rule_ref'] ?? '' );\n\t\t$rule = self::repository_read( static function () use ( $rule_ref ) { return WCA_Repository::get_availability_rule_by_ref( $rule_ref, true ); } );\n\t\tif ( is_wp_error( $rule ) ) { return $rule; }"""
if s.count(old)!=1: raise SystemExit('R7 availability-rule read anchor not unique')
s=s.replace(old,new,1)
old="\t\t$service = $query['service_id'] ? WCA_Repository::get_service( $query['service_id'], true ) : null;"
new="""\t\t$service_id = absint( $query['service_id'] );\n\t\t$service = $service_id ? self::repository_read( static function () use ( $service_id ) { return WCA_Repository::get_service( $service_id, true ); } ) : null;\n\t\tif ( is_wp_error( $service ) ) { return $service; }"""
if s.count(old)!=1: raise SystemExit('R7 canonical-hold service read anchor not unique')
s=s.replace(old,new,1)
old="""\t\t$clinic = WCA_Repository::get_clinic( absint( $hold['clinic_id'] ?? 0 ), true );\n\t\t$service = WCA_Repository::get_service( absint( $hold['service_id'] ?? 0 ), true );\n\t\tif ( ! $clinic || ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) || ! SWC_Doctor_Authority::is_eligible( absint( $hold['doctor_user_id'] ?? 0 ) ) ) {"""
new="""\t\t$clinic_id = absint( $hold['clinic_id'] ?? 0 );\n\t\t$service_id = absint( $hold['service_id'] ?? 0 );\n\t\t$clinic = self::repository_read( static function () use ( $clinic_id ) { return WCA_Repository::get_clinic( $clinic_id, true ); } );\n\t\tif ( is_wp_error( $clinic ) ) { return $clinic; }\n\t\t$service = self::repository_read( static function () use ( $service_id ) { return WCA_Repository::get_service( $service_id, true ); } );\n\t\tif ( is_wp_error( $service ) ) { return $service; }\n\t\tif ( ! $clinic || ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) || ! SWC_Doctor_Authority::is_eligible( absint( $hold['doctor_user_id'] ?? 0 ) ) ) {"""
if s.count(old)!=1: raise SystemExit('R7 bookable-hold scope read anchor not unique')
s=s.replace(old,new,1)
old="""\t\tif ( $branch_id ) {\n\t\t\t$branch = WCA_Repository::get_branch( $branch_id );\n\t\t\tif ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) || 'active' !== (string) $branch['status'] ) {"""
new="""\t\tif ( $branch_id ) {\n\t\t\t$branch = self::repository_read( static function () use ( $branch_id ) { return WCA_Repository::get_branch( $branch_id ); } );\n\t\t\tif ( is_wp_error( $branch ) ) { return $branch; }\n\t\t\tif ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) || 'active' !== (string) $branch['status'] ) {"""
if s.count(old)!=1: raise SystemExit('R7 branch read anchor not unique')
s=s.replace(old,new,1); p.write_text(s)

# R7-3/R7-4: quarantine the superseded mutation plane. Canonical WCA routes/services own appointment writes.
p=Path('includes/class-swc-appointments.php'); s=p.read_text()
old="""\tpublic function hooks() {\n\t\tadd_action( 'init', array( 'SWC_Activator', 'register_type' ) );\n\t\tadd_action( 'admin_post_swc_submit_appointment', array( $this, 'submit' ) );\n\t\tadd_action( 'admin_post_swc_patient_cancel', array( $this, 'patient_cancel' ) );\n\t\tadd_action( 'admin_post_swc_patient_accept_reschedule', array( $this, 'patient_accept_reschedule' ) );\n\t\tadd_action( 'admin_post_swc_patient_accept_reassignment', array( $this, 'patient_accept_reassignment' ) );\n\t\tadd_action( 'admin_post_swc_patient_decline_reassignment', array( $this, 'patient_decline_reassignment' ) );\n\t\tadd_action( 'admin_post_swc_doctor_update', array( $this, 'doctor_update' ) );\n\t\tadd_action( 'admin_post_swc_save_availability', array( $this, 'save_availability' ) );\n\t}"""
new="""\tpublic function hooks() {\n\t\tadd_action( 'init', array( 'SWC_Activator', 'register_type' ) );\n\t\t/* Legacy appointment mutation endpoints are intentionally not registered.\n\t\t * All appointment creation/transitions now pass through WCA_Appointment_Command\n\t\t * and WCA_Service so holds, consent, finance, outbox and state law cannot diverge. */\n\t\tadd_action( 'admin_post_swc_save_availability', array( $this, 'save_availability' ) );\n\t}"""
if s.count(old)!=1: raise SystemExit('R7 legacy mutation hook anchor not unique')
s=s.replace(old,new,1); p.write_text(s)

p=Path('includes/class-swc-frontend.php'); s=p.read_text()
old="""\tpublic function hooks() {\n\t\tadd_shortcode( 'swc_worldwide_clinic', array( $this, 'clinic' ) );\n\t\tadd_shortcode( 'swc_request_appointment', array( $this, 'request' ) );\n\t\tadd_shortcode( 'swc_my_appointments', array( $this, 'patient' ) );\n\t\tadd_shortcode( 'swc_doctor_appointments', array( $this, 'doctor' ) );\n\t\tadd_shortcode( 'swc_doctor_availability', array( $this, 'availability' ) );\n\t}"""
new="""\tpublic function hooks() {\n\t\tadd_shortcode( 'swc_worldwide_clinic', array( $this, 'clinic' ) );\n\t\tadd_shortcode( 'swc_request_appointment', array( $this, 'legacy_governed_notice' ) );\n\t\tadd_shortcode( 'swc_my_appointments', array( $this, 'legacy_governed_notice' ) );\n\t\tadd_shortcode( 'swc_doctor_appointments', array( $this, 'legacy_governed_notice' ) );\n\t\tadd_shortcode( 'swc_doctor_availability', array( $this, 'availability' ) );\n\t}\n\n\tpublic function legacy_governed_notice() {\n\t\t$appointments = home_url( '/appointments/' );\n\t\treturn '<div class="swc-notice"><h2>' . esc_html__( 'This legacy appointment workflow has been retired', 'worldwide-clinic-appointments' ) . '</h2><p>' . esc_html__( 'Use the current verified clinic booking flow. Appointment writes are accepted only through the governed File 08 scheduling service.', 'worldwide-clinic-appointments' ) . '</p><a class="swc-button" href="' . esc_url( $appointments ) . '">' . esc_html__( 'Open current appointments', 'worldwide-clinic-appointments' ) . '</a></div>';\n\t}"""
if s.count(old)!=1: raise SystemExit('R7 legacy frontend hook anchor not unique')
s=s.replace(old,new,1); p.write_text(s)

Path('tests/t18-r7-appointment-lifecycle-regressions.php').write_text(r'''<?php
$root=dirname(__DIR__);
$cmd=file_get_contents($root.'/includes/class-wca-appointment-command.php');
$guard=file_get_contents($root.'/includes/class-wca-plan-guard.php');
$legacy=file_get_contents($root.'/includes/class-swc-appointments.php');
$front=file_get_contents($root.'/includes/class-swc-frontend.php');
$checks=array(
 'command propagates hold WP_Error'=>strpos($cmd,'if ( is_wp_error( $hold ) ) { return $hold; }')!==false,
 'command consumes service read error'=>strpos($cmd,'$service_read_error = WCA_Repository::consume_read_error();')!==false,
 'plan guard centralizes read failure propagation'=>strpos($guard,'private static function repository_read')!==false && substr_count($guard,'self::repository_read(')>=6,
 'legacy appointment mutation hooks quarantined'=>strpos($legacy,"admin_post_swc_submit_appointment")===false && strpos($legacy,"admin_post_swc_doctor_update")===false && strpos($legacy,"admin_post_swc_patient_cancel")===false,
 'legacy appointment shortcodes no longer render mutation forms'=>substr_count($front,"array( $this, 'legacy_governed_notice' )")>=3,
 'canonical availability compatibility remains'=>strpos($legacy,"admin_post_swc_save_availability")!==false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R7 FAIL: {$n}\n");exit(1);}}
echo "T18 R7 appointment lifecycle regressions: PASS\n";
''')
ra=Path('tests/run-all.php'); rr=ra.read_text(); needle="'t18-r6-financial-readside-regressions.php',"
if needle in rr and 't18-r7-appointment-lifecycle-regressions.php' not in rr:
    ra.write_text(rr.replace(needle,needle+"\n    't18-r7-appointment-lifecycle-regressions.php',",1))
print('T18 R7 frozen ledger correction applied')