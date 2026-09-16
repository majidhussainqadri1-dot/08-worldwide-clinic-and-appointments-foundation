<?php
$root = dirname( __DIR__ );
$hardening = file_get_contents( $root . '/includes/class-wca-second-ten-review-hardening.php' );
$cf01 = file_get_contents( $root . '/includes/class-swc-cf01-care-context.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
if ( ! is_string( $hardening ) || ! is_string( $cf01 ) || ! is_string( $runner ) ) {
	fwrite( STDERR, "T25 R8 source read failed\n" );
	exit( 1 );
}

$checks = array(
	'legacy File26 observer is removed after governance boot' => false !== strpos( $hardening, "remove_action( 'wca_outbox_event', array( 'WCA_Central_Governance', 'observe_outbox_event' ), 20 );" ),
	'retry-safe File26 observer is registered' => false !== strpos( $hardening, "add_action( 'wca_outbox_event', array( __CLASS__, 'observe_search_projection_event' ), 20, 1 );" ),
	'File26 invalidation is durably queued' => false !== strpos( $hardening, "\$queued = WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1'" ),
	'File26 enqueue failure propagates to parent outbox retry' => false !== strpos( $hardening, 'if ( is_wp_error( $queued ) )' ) && false !== strpos( $hardening, 'throw new RuntimeException( $queued->get_error_message() );' ),
	'checked-in context remains active scheduled state' => false !== strpos( $cf01, "array( 'confirmed', 'checked_in' )" ) && false !== strpos( $cf01, "return 'scheduled';" ),
	'checked-in relationship remains scheduling-only contact' => substr_count( $cf01, "array( 'confirmed', 'checked_in' )" ) >= 2 && false !== strpos( $cf01, "return 'scheduled_contact';" ),
	'checked-in context preserves scheduled UTC time' => false !== strpos( $cf01, "array( 'confirmed', 'checked_in', 'completed' )" ),
	'duplicate requested state entry is gone' => false === strpos( $cf01, "array( 'requested', 'requested', 'reschedule_pending' )" ),
	'CF01 appointment context still grants no treating relationship' => false !== strpos( $cf01, "'treating_relationship_asserted' => false" ) && false !== strpos( $cf01, "'sufficient_for_clinical_write'  => false" ) && false !== strpos( $cf01, "'sufficient_for_prescription'    => false" ),
	'R8 regression is bound into aggregate suite' => false !== strpos( $runner, "'t25-r8-cross-file-reliability-regressions.php'" ),
);

$failures = array();
foreach ( $checks as $name => $ok ) {
	if ( ! $ok ) { $failures[] = $name; }
}
if ( $failures ) {
	fwrite( STDERR, "T25 R8 cross-file reliability regressions failed:\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}
echo 'T25 R8 cross-file reliability regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
