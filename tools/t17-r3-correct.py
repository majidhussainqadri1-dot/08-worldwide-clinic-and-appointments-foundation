from pathlib import Path


def replace_once(path, old, new):
    p = Path(path)
    s = p.read_text()
    if old not in s:
        raise SystemExit(f"anchor missing in {path}: {old[:80]!r}")
    if s.count(old) != 1:
        raise SystemExit(f"anchor not unique in {path}: {s.count(old)} matches")
    p.write_text(s.replace(old, new, 1))


# R3 was completed as a read-only authorization/IDOR review before this
# correction batch was written. This transformer applies only the frozen R3
# findings: legacy/browser and operations surfaces must not rely on login or a
# stale native capability without File 00 claim revalidation.

replace_once(
    'includes/class-swc-helpers.php',
    """\tpublic static function can_doctor_manage( $appointment_id, $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\treturn $user_id\n\t\t\t&& self::TYPE === get_post_type( absint( $appointment_id ) )\n\t\t\t&& absint( self::meta( $appointment_id, 'doctor_id' ) ) === $user_id\n\t\t\t&& self::is_verified_doctor( $user_id );\n\t}\n\n\tpublic static function can_patient_manage( $appointment_id, $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\treturn $user_id\n\t\t\t&& self::TYPE === get_post_type( absint( $appointment_id ) )\n\t\t\t&& absint( get_post_field( 'post_author', $appointment_id ) ) === $user_id;\n\t}\n\n\tpublic static function can_view( $id ) {\n\t\treturn self::can_patient_manage( $id ) || self::can_doctor_manage( $id ) || current_user_can( 'manage_worldwide_clinic' );\n\t}\n""",
    """\tpublic static function can_doctor_manage( $appointment_id, $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\tif ( ! $user_id || ! class_exists( 'WCA_Authorization' ) ) {\n\t\t\treturn false;\n\t\t}\n\t\t$claims = WCA_Authorization::claims( $user_id );\n\t\tif ( is_wp_error( $claims ) ) {\n\t\t\treturn false;\n\t\t}\n\t\treturn self::TYPE === get_post_type( absint( $appointment_id ) )\n\t\t\t&& absint( self::meta( $appointment_id, 'doctor_id' ) ) === $user_id\n\t\t\t&& self::is_verified_doctor( $user_id );\n\t}\n\n\tpublic static function can_patient_manage( $appointment_id, $user_id = 0 ) {\n\t\t$user_id = $user_id ? absint( $user_id ) : get_current_user_id();\n\t\tif ( ! $user_id || ! class_exists( 'WCA_Authorization' ) ) {\n\t\t\treturn false;\n\t\t}\n\t\t$claims = WCA_Authorization::claims( $user_id );\n\t\tif ( is_wp_error( $claims ) ) {\n\t\t\treturn false;\n\t\t}\n\t\treturn self::TYPE === get_post_type( absint( $appointment_id ) )\n\t\t\t&& absint( get_post_field( 'post_author', $appointment_id ) ) === $user_id;\n\t}\n\n\tpublic static function can_view( $id ) {\n\t\tif ( ! class_exists( 'WCA_Authorization' ) ) {\n\t\t\treturn false;\n\t\t}\n\t\treturn ! is_wp_error( WCA_Authorization::can_view_appointment( absint( $id ), get_current_user_id() ) );\n\t}\n"""
)

replace_once(
    'includes/class-swc-appointments.php',
    """\t\t$user_id = get_current_user_id();\n\t\tif ( SWC_Helpers::rate_limit_hit( $user_id, 5, HOUR_IN_SECONDS ) ) {\n""",
    """\t\t$user_id = get_current_user_id();\n\t\t$claims = class_exists( 'WCA_Authorization' ) ? WCA_Authorization::claims( $user_id ) : new WP_Error( 'wca_authorization_unavailable', __( 'Current membership authorization is unavailable.', 'worldwide-clinic-appointments' ) );\n\t\tif ( is_wp_error( $claims ) ) {\n\t\t\twp_die( esc_html__( 'Your current membership state does not permit an appointment request.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );\n\t\t}\n\t\t$patient_context = WCA_Authorization::guardian_context( $user_id, 0, $user_id );\n\t\tif ( is_wp_error( $patient_context ) ) {\n\t\t\twp_die( esc_html__( 'This legacy request form cannot establish the required patient or guardian context. Use the governed booking flow.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );\n\t\t}\n\t\tif ( SWC_Helpers::rate_limit_hit( $user_id, 5, HOUR_IN_SECONDS ) ) {\n"""
)

replace_once(
    'includes/class-swc-admin.php',
    """\tprivate function guard() {\n\t\tif ( ! current_user_can( 'manage_worldwide_clinic' ) ) {\n\t\t\twp_die( esc_html__( 'You are not allowed to manage the clinic.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );\n\t\t}\n\t}\n""",
    """\tprivate function guard() {\n\t\tif ( ! current_user_can( 'manage_worldwide_clinic' ) || ! class_exists( 'WCA_Authorization' ) ) {\n\t\t\twp_die( esc_html__( 'You are not allowed to manage the clinic.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );\n\t\t}\n\t\t$claims = WCA_Authorization::claims( get_current_user_id() );\n\t\tif ( is_wp_error( $claims ) ) {\n\t\t\twp_die( esc_html__( 'Your current membership state does not permit clinic administration.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );\n\t\t}\n\t\t$step = WCA_Authorization::require_step_up( 'appointment_operations', get_current_user_id() );\n\t\tif ( is_wp_error( $step ) ) {\n\t\t\twp_die( esc_html__( 'Recent security verification is required for clinic administration.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );\n\t\t}\n\t}\n"""
)

replace_once(
    'includes/class-wca-admin.php',
    """\tpublic static function page() {\n\t\tif ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'worldwide-clinic-appointments' ) ); }\n\t\t$health = WCA_Observability::health();\n""",
    """\tpublic static function page() {\n\t\tif ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'worldwide-clinic-appointments' ) ); }\n\t\t$claims = WCA_Authorization::claims( get_current_user_id() );\n\t\tif ( is_wp_error( $claims ) ) { wp_die( esc_html__( 'Current membership authorization is required.', 'worldwide-clinic-appointments' ) ); }\n\t\t$health = WCA_Observability::health();\n"""
)

replace_once(
    'includes/class-wca-admin.php',
    """\tprivate static function authorize( $nonce ) {\n\t\tif ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'worldwide-clinic-appointments' ) ); }\n\t\tcheck_admin_referer( $nonce );\n\t}\n""",
    """\tprivate static function authorize( $nonce ) {\n\t\tif ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'worldwide-clinic-appointments' ) ); }\n\t\t$claims = WCA_Authorization::claims( get_current_user_id() );\n\t\tif ( is_wp_error( $claims ) ) { wp_die( esc_html__( 'Current membership authorization is required.', 'worldwide-clinic-appointments' ) ); }\n\t\t$step = WCA_Authorization::require_step_up( 'clinic_operations', get_current_user_id() );\n\t\tif ( is_wp_error( $step ) ) { wp_die( esc_html__( 'Recent security verification is required.', 'worldwide-clinic-appointments' ) ); }\n\t\tcheck_admin_referer( $nonce );\n\t}\n"""
)

replace_once(
    'includes/class-wca-continuity-secure.php',
    """\tpublic static function admin() { return current_user_can( 'manage_wca_operations' ) || current_user_can( 'manage_worldwide_clinic' ) || current_user_can( 'manage_options' ) ? true : new WP_Error( 'wca_admin_required', __( 'Operations permission is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }\n""",
    """\tpublic static function admin() {\n\t\tif ( ! ( current_user_can( 'manage_wca_operations' ) || current_user_can( 'manage_worldwide_clinic' ) || current_user_can( 'manage_options' ) ) ) {\n\t\t\treturn new WP_Error( 'wca_admin_required', __( 'Operations permission is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );\n\t\t}\n\t\t$claims = WCA_Authorization::claims( get_current_user_id() );\n\t\treturn is_wp_error( $claims ) ? new WP_Error( 'wca_admin_membership', __( 'Current membership authorization is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ) : true;\n\t}\n"""
)

print('T17 R3 closed authorization correction batch applied')
