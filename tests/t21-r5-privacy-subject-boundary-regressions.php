<?php
$root = dirname( __DIR__ );
$privacy = file_get_contents( $root . '/includes/class-wca-privacy.php' );
if ( false === $privacy ) {
	fwrite( STDERR, "T21 R5 FAIL: privacy source unavailable\n" );
	exit( 1 );
}
$checks = array(
	'export delegates to canonical appointment selector' => false !== strpos( $privacy, 'self::appointment_ids_page( absint( $user->ID ), $page, 50 )' ),
	'legacy post author participates in both appointment selectors' => substr_count( $privacy, 'p.post_author=%d' ) >= 2,
	'proposed doctor participates in canonical selection' => substr_count( $privacy, "'_swc_proposed_doctor_id'" ) >= 3,
	'erasure recognizes proposed doctor relationship' => false !== strpos( $privacy, '$is_proposed_doctor = absint( SWC_Helpers::meta( $id, \'proposed_doctor_id\', 0 ) ) === $user_id;' ),
	'erasure anonymizes proposed doctor relationship' => false !== strpos( $privacy, "'_swc_proposed_doctor_id', 0, 'wca_privacy_proposed_doctor_anonymize'" ),
	'appointment selectors remain bounded to File 08 appointment posts' => substr_count( $privacy, 'p.post_type=%s' ) >= 2 && substr_count( $privacy, "p.post_status IN ('private','publish','draft')" ) >= 2,
);
foreach ( $checks as $label => $passed ) {
	if ( ! $passed ) {
		fwrite( STDERR, "T21 R5 FAIL: {$label}\n" );
		exit( 1 );
	}
}
echo "T21 R5 privacy subject-boundary regressions passed.\n";
