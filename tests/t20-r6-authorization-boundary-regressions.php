<?php
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
