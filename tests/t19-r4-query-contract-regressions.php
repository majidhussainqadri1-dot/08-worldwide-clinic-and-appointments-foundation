<?php
$root = dirname( __DIR__ );
$query = file_get_contents( $root . '/includes/class-wca-query-api.php' );
$main = file_get_contents( $root . '/worldwide-clinic.php' );
$template = file_get_contents( $root . '/templates/dashboard.php' );
if ( ! is_string( $query ) || ! is_string( $main ) || ! is_string( $template ) ) { fwrite( STDERR, "T19 R4 source read failed\n" ); exit( 1 ); }
$checks = array(
	'query class loaded by runtime' => false !== strpos( $main, "'includes/class-wca-query-api.php'" ) && false !== strpos( $main, 'WCA_Query_API::boot();' ),
	'patient appointment collection route exists' => false !== strpos( $query, "'/appointment-refs'" ),
	'clinic schedule opaque route exists' => false !== strpos( $query, "'/clinic-refs/(?P<ref>[0-9a-fA-F-]{36})/schedule'" ),
	'list patient appointments contract exists' => false !== strpos( $query, 'function list_patient_appointments' ) && false !== strpos( $query, "'wca.list-patient-appointments'" ),
	'list clinic schedule contract exists' => false !== strpos( $query, 'function list_clinic_schedule' ) && false !== strpos( $query, "'wca.list-clinic-schedule'" ),
	'queries are cursor based' => false !== strpos( $query, 'next_cursor' ) && false !== strpos( $query, 'encode_cursor' ) && false !== strpos( $query, 'decode_cursor' ),
	'cursor is signed' => false !== strpos( $query, "hash_hmac( 'sha256'" ) && false !== strpos( $query, "wp_salt( 'nonce' )" ),
	'cursor binds actor and filter' => false !== strpos( $query, "'a' => absint( \$actor_user_id )" ) && false !== strpos( $query, "'f' => (string) \$filter_hash" ),
	'patient query scopes participant identities' => false !== strpos( $query, "_swc_patient_user_id" ) && false !== strpos( $query, "_swc_doctor_id" ) && false !== strpos( $query, "_swc_guardian_user_id" ),
	'patient query includes appointment delegations' => false !== strpos( $query, "delegated_clinic_ids( \$actor_user_id, 'appointments' )" ),
	'candidate appointments are reauthorized' => false !== strpos( $query, 'WCA_Authorization::can_view_appointment( $id, $actor_user_id )' ),
	'infrastructure authorization errors propagate' => false !== strpos( $query, 'if ( $status >= 500 ) { return $access; }' ),
	'list storage failure is explicit' => false !== strpos( $query, 'wca_appointment_list_read_failed' ),
	'clinic schedule requires owner/delegated scope' => false !== strpos( $query, 'WCA_Authorization::can_manage_clinic' ) && false !== strpos( $query, "delegated_clinic_ids( \$actor_user_id, 'appointments' )" ),
	'clinic schedule exposes privacy-filtered reason category only' => false !== strpos( $query, "\$projection['reason_category']" ) && false !== strpos( $query, 'free-text patient reason stays out of staff schedule lists' ),
	'query responses are no-store and noindex' => false !== strpos( $query, "'Cache-Control', 'private, no-store, max-age=0'" ) && false !== strpos( $query, "'X-Robots-Tag', 'noindex, nofollow, noarchive'" ),
	'canonical dashboard uses schedule-aware template' => false !== strpos( $query, "'dashboard' === WCA_Routes::route()" ) && false !== strpos( $query, "templates/dashboard.php" ) && false !== strpos( $template, 'WCA_Query_API::render_dashboard()' ),
	'dashboard exposes schedule and appointment request state' => false !== strpos( $query, 'Appointment schedule and requests' ) && false !== strpos( $query, 'View appointment' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R4 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R4 appointment-query/dashboard regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
