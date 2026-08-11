from pathlib import Path

p = Path('includes/class-wca-service.php')
s = p.read_text()

marker = "\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function save_service( $data, $service_id = 0, $expected_version = 0, $actor_user_id = 0 ) {\n"
helper = """\t/** A globally eligible doctor still requires current authority to serve this clinic. */
\tprivate static function doctor_may_serve_clinic( $clinic, $doctor_id, $actor_user_id ) {
\t\t$clinic_id = absint( $clinic['id'] ?? 0 );
\t\t$doctor_id = absint( $doctor_id );
\t\t$actor_user_id = absint( $actor_user_id );
\t\tif ( ! $clinic_id || ! $doctor_id || ! $actor_user_id ) { return false; }
\t\tif ( user_can( $actor_user_id, 'manage_worldwide_clinic' ) || $doctor_id === $actor_user_id || $doctor_id === absint( $clinic['owner_user_id'] ?? 0 ) ) { return true; }
\t\t$delegated = array_merge(
\t\t\tWCA_Authorization::delegated_clinic_ids( $doctor_id, 'schedule' ),
\t\t\tWCA_Authorization::delegated_clinic_ids( $doctor_id, 'clinic_manage' )
\t\t);
\t\t$allowed = in_array( $clinic_id, array_map( 'absint', $delegated ), true );
\t\treturn (bool) apply_filters( 'wca_doctor_may_serve_clinic', $allowed, $doctor_id, $clinic_id, $actor_user_id );
\t}

"""
if marker not in s:
    raise SystemExit('R8 save_service insertion marker not found')
if 'private static function doctor_may_serve_clinic(' not in s:
    s = s.replace(marker, helper + marker, 1)

old_service = """\t\tif ( ! empty( $data['doctor_user_id'] ) && ! SWC_Doctor_Authority::is_eligible( absint( $data['doctor_user_id'] ) ) ) { return new WP_Error( 'wca_service_doctor', __( 'The assigned practitioner is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
"""
new_service = """\t\tif ( ! empty( $data['doctor_user_id'] ) ) {
\t\t\t$service_doctor_id = absint( $data['doctor_user_id'] );
\t\t\tif ( ! SWC_Doctor_Authority::is_eligible( $service_doctor_id ) ) { return new WP_Error( 'wca_service_doctor', __( 'The assigned practitioner is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\tif ( ! self::doctor_may_serve_clinic( $clinic, $service_doctor_id, $actor_user_id ) ) { return new WP_Error( 'wca_service_doctor_scope', __( 'The selected doctor has no current authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
\t\t}
"""
if old_service not in s:
    raise SystemExit('R8 service doctor eligibility block not found')
s = s.replace(old_service, new_service, 1)

old_avail = """\t\tif ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
\t\t\treturn new WP_Error( 'wca_doctor_ineligible', __( 'The doctor is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
\t\t}
\t\t$data['doctor_user_id'] = $doctor_id;
"""
new_avail = """\t\tif ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) {
\t\t\treturn new WP_Error( 'wca_doctor_ineligible', __( 'The doctor is not currently eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
\t\t}
\t\tif ( ! self::doctor_may_serve_clinic( $clinic, $doctor_id, $actor_user_id ) ) {
\t\t\treturn new WP_Error( 'wca_availability_doctor_scope', __( 'The selected doctor has no current authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
\t\t}
\t\t$data['doctor_user_id'] = $doctor_id;
"""
if old_avail not in s:
    raise SystemExit('R8 availability doctor eligibility block not found')
s = s.replace(old_avail, new_avail, 1)

p.write_text(s)
