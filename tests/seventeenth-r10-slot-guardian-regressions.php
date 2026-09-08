<?php
$guard   = file_get_contents( __DIR__ . '/../includes/class-wca-plan-guard.php' );
$service = file_get_contents( __DIR__ . '/../includes/class-wca-service.php' );
if ( false === $guard || false === $service ) {
	fwrite( STDERR, "T17 R10 source files are unavailable.\n" );
	exit( 1 );
}
$required_guard = array(
	"\$requested_patient_id = absint( \$data['patient_user_id'] ?? 0 )",
	"WCA_Authorization::guardian_context( \$requested_patient_id, \$guardian_user_id, \$patient_user_id )",
	"\$patient_user_id = \$requested_patient_id",
	"'patient_user_id' => absint( \$patient_user_id )",
	"'p' . absint( \$patient_user_id )",
);
foreach ( $required_guard as $needle ) {
	if ( false === strpos( $guard, $needle ) ) {
		fwrite( STDERR, "T17 R10 guardian slot ownership regression missing: {$needle}\n" );
		exit( 1 );
	}
}
if ( false === strpos( $service, 'guardian_context( $patient_user_id, $guardian_user_id, $actor_user_id )' ) ) {
	fwrite( STDERR, "T17 R10 service no longer proves guardian authority before holding the patient slot.\n" );
	exit( 1 );
}
if ( false === strpos( $guard, 'validate_bookable_hold( $hold, $patient_user_id )' ) ) {
	fwrite( STDERR, "T17 R10 bookable hold ownership validation is missing.\n" );
	exit( 1 );
}
echo "T17 R10 guardian slot ownership regressions passed.\n";
