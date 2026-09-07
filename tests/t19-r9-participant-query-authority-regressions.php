<?php
$root = dirname( __DIR__ );
$q = file_get_contents( $root . '/includes/class-wca-query-api.php' );
$f = file_get_contents( $root . '/includes/class-wca-frontend.php' );
$o = file_get_contents( $root . '/includes/class-wca-opaque-api.php' );
$fail = array(); $pass = 0;
function r9ok( $label, $ok ) { global $fail, $pass; if ( $ok ) { echo 'PASS ' . (++$pass) . ': ' . $label . "\n"; } else { $fail[] = $label; } }
$patient_start = strpos( $q, 'public static function list_patient_appointments' );
$patient_end = strpos( $q, 'public static function list_clinic_schedule', $patient_start );
$patient = false !== $patient_start && false !== $patient_end ? substr( $q, $patient_start, $patient_end - $patient_start ) : '';
$candidate_start = strpos( $q, 'private static function query_candidate_appointments' );
$candidate_end = strpos( $q, 'private static function appointment_projection', $candidate_start );
$candidate = false !== $candidate_start && false !== $candidate_end ? substr( $q, $candidate_start, $candidate_end - $candidate_start ) : '';
$frontend_start = strpos( $f, 'private static function appointments()' );
$frontend_end = strpos( $f, 'private static function appointment( $public_ref )', $frontend_start );
$frontend = false !== $frontend_start && false !== $frontend_end ? substr( $f, $frontend_start, $frontend_end - $frontend_start ) : '';
r9ok( 'patient list no longer loads delegated clinic scope', false === strpos( $patient, 'delegated_clinic_ids' ) );
r9ok( 'patient candidate relation excludes doctor assignment', false === strpos( $candidate, "meta_key='_swc_doctor_id'" ) );
r9ok( 'patient candidate relation excludes delegated clinic expansion', false === strpos( $candidate, 'delegated_clinic_ids = array_values' ) );
r9ok( 'patient route consumes canonical cursor query', false !== strpos( $frontend, 'WCA_Query_API::list_patient_appointments' ) );
r9ok( 'patient route no longer pre-paginates a direct WP_Query', false === strpos( $frontend, 'new WP_Query' ) );
r9ok( 'patient route renders canonical projection cards', false !== strpos( $frontend, 'appointment_projection_card' ) );
r9ok( 'opaque transition supplies operations purpose for institutional admin', false !== strpos( $o, "user_can( get_current_user_id(), 'manage_worldwide_clinic' ) ? 'operations' : ''" ) );
r9ok( 'opaque transition precheck passes explicit purpose', false !== strpos( $o, 'self::appointment_access( $id, $purpose )' ) );
if ( $fail ) { fwrite( STDERR, "T19 R9 regression gate failed:\n- " . implode( "\n- ", $fail ) . "\n" ); exit( 1 ); }
echo 'T19 R9 participant/query authority regressions: PASS ' . $pass . '/' . $pass . "\n";
