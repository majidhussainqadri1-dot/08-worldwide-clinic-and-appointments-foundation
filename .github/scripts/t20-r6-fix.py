from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]

def read(path): return (ROOT/path).read_text()
def write(path, text): (ROOT/path).write_text(text)
def replace_once(path, old, new):
    text=read(path)
    if text.count(old)!=1: raise SystemExit(f'{path}: patch anchor count {text.count(old)}')
    write(path,text.replace(old,new,1))

replace_once('includes/class-wca-authorization.php', '''\tpublic static function appointment_actor( $appointment_id, $user_id = 0 ) {\n\t\t$user_id = absint( $user_id ?: get_current_user_id() );\n\t\tif ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return 'admin'; }\n\t\tif ( SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return 'doctor'; }\n\t\tif ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }\n\t\t$claims = self::claims( $user_id );\n\t\tif ( ! is_wp_error( $claims ) && ! empty( $claims['guardian'] ) && class_exists( 'WCA_Central_Governance' ) ) {\n\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );\n\t\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $user_id, $user_id );\n\t\t\tif ( ! is_wp_error( $guardian ) ) { return 'guardian'; }\n\t\t}\n\t\treturn 'patient';\n\t}\n''', '''\tpublic static function appointment_actor( $appointment_id, $user_id = 0 ) {\n\t\t$user_id = absint( $user_id ?: get_current_user_id() );\n\t\t/* Relationship-specific authority is evaluated before global administration.\n\t\t * Otherwise a treating participant who also holds a global capability could\n\t\t * silently acquire the broader admin transition matrix without traversing the\n\t\t * purpose/step-up/audit branch required for administrative appointment access. */\n\t\tif ( SWC_Helpers::can_patient_manage( $appointment_id, $user_id ) ) { return 'patient'; }\n\t\t$claims = self::claims( $user_id );\n\t\tif ( ! is_wp_error( $claims ) && ! empty( $claims['guardian'] ) && class_exists( 'WCA_Central_Governance' ) ) {\n\t\t\t$patient_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );\n\t\t\t$guardian = WCA_Central_Governance::validate_patient_guardian( $patient_id, $user_id, $user_id );\n\t\t\tif ( ! is_wp_error( $guardian ) ) { return 'guardian'; }\n\t\t}\n\t\tif ( SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) { return 'doctor'; }\n\t\tif ( self::can_staff_access_appointment( $appointment_id, $user_id, 'appointments' ) ) { return 'clinic_staff'; }\n\t\tif ( user_can( $user_id, 'manage_worldwide_clinic' ) ) { return 'admin'; }\n\t\treturn 'patient';\n\t}\n''')

replace_once('includes/class-swc-plugin.php', '''\t\tif ( user_can( $user_id, 'manage_worldwide_clinic' ) ) {\n\t\t\treturn array( 'manage_worldwide_clinic' );\n\t\t}\n\t\tif ( 'read_swc_appointment' === $cap && ( SWC_Helpers::can_patient_manage( $appointment_id, $user_id ) || SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) ) {\n\t\t\treturn array( 'read' );\n\t\t}\n\t\treturn array( 'do_not_allow' );\n''', '''\t\t/* Generic WordPress post capabilities are not an administrative mutation surface.\n\t\t * Purpose-limited administrators must use File 08 governed commands, where\n\t\t * current identity, step-up and audit evidence are enforced. */\n\t\tif ( 'read_swc_appointment' === $cap ) {\n\t\t\t$access = WCA_Authorization::can_view_appointment( $appointment_id, $user_id );\n\t\t\treturn is_wp_error( $access ) ? array( 'do_not_allow' ) : array( 'read' );\n\t\t}\n\t\treturn array( 'do_not_allow' );\n''')

test = ROOT/'tests/t20-r6-authorization-boundary-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ );
$auth = file_get_contents( $root . '/includes/class-wca-authorization.php' );
$legacy = file_get_contents( $root . '/includes/class-swc-plugin.php' );
$fails = array(); $n = 0;
function t20r6( $name, $ok ) { global $fails, $n; $n++; if ( ! $ok ) { $fails[] = $name; } }
t20r6( 'authorization source readable', is_string( $auth ) );
t20r6( 'legacy plugin source readable', is_string( $legacy ) );
$patient = strpos( $auth, 'can_patient_manage( $appointment_id, $user_id ) ) { return \'patient\'; }' );
$admin = strpos( $auth, 'user_can( $user_id, \'manage_worldwide_clinic\' ) ) { return \'admin\'; }' );
t20r6( 'participant actor precedes global admin actor', false !== $patient && false !== $admin && $patient < $admin );
t20r6( 'doctor relationship remains explicit', false !== strpos( $auth, 'can_doctor_manage( $appointment_id, $user_id ) ) { return \'doctor\'; }' ) );
t20r6( 'staff relationship remains explicit', false !== strpos( $auth, "return 'clinic_staff'" ) );
t20r6( 'generic read path delegates to canonical authorization', false !== strpos( $legacy, 'WCA_Authorization::can_view_appointment( $appointment_id, $user_id )' ) );
t20r6( 'generic edit delete path fails closed', false === strpos( $legacy, "return array( 'manage_worldwide_clinic' )" ) );
if ( $fails ) { fwrite( STDERR, "T20 R6 authorization regressions failed:\n- " . implode( "\n- ", $fails ) . "\n" ); exit( 1 ); }
echo "T20 R6 authorization regressions: PASS {$n}/{$n}.\n";
''')

path='tests/run-all.php'; text=read(path); old="'t20-r5-idempotency-header-parity-regressions.php' );"; new="'t20-r5-idempotency-header-parity-regressions.php', 't20-r6-authorization-boundary-regressions.php' );";
if text.count(old)!=1: raise SystemExit('run-all R6 anchor mismatch')
write(path,text.replace(old,new,1))
