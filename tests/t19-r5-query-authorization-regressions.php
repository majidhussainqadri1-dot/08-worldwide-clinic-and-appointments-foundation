<?php
$root = dirname( __DIR__ );
$query = file_get_contents( $root . '/includes/class-wca-query-api.php' );
if ( ! is_string( $query ) ) { fwrite( STDERR, "T19 R5 source read failed\n" ); exit( 1 ); }
$checks = array(
	'appointment projection receives explicit actor' => false !== strpos( $query, 'appointment_projection( $id, false, $actor_user_id )' ) && false !== strpos( $query, 'function appointment_projection( $appointment_id, $clinic_schedule = false, $actor_user_id = 0 )' ),
	'allowed actions use explicit actor' => false !== strpos( $query, 'appointment_actor( $appointment_id, $actor_user_id )' ),
	'clinic schedule REST accepts explicit access purpose' => false !== strpos( $query, "get_header( 'X-WCA-Access-Purpose' )" ),
	'owner schedule scope is explicit and doctor founder bound' => false !== strpos( $query, '$owner_scope') && false !== strpos( $query, "! empty( \$claims['doctor'] )") && false !== strpos( $query, "! empty( \$claims['founder'] )"),
	'appointment delegation is explicit' => false !== strpos( $query, '$appointment_scope') && false !== strpos( $query, "delegated_clinic_ids( \$actor_user_id, 'appointments' )" ),
	'global admin access is purpose limited' => false !== strpos( $query, '$allowed_admin_purposes') && false !== strpos( $query, "'privacy_request'") && false !== strpos( $query, "'support_case'"),
	'global admin access requires step up' => false !== strpos( $query, "require_step_up( 'appointment_' . \$purpose, \$actor_user_id )" ),
	'global admin schedule items use canonical appointment authorization' => false !== strpos( $query, 'can_view_appointment( $id, $actor_user_id, $purpose )' ),
	'admin purpose is bound into cursor filter' => false !== strpos( $query, "'purpose' => \$admin_scope && ! \$owner_scope && ! \$appointment_scope ? \$purpose : ''" ),
	'authorization provider failures are not normalized through can_manage_clinic' => false === strpos( $query, 'WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id )' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R5 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R5 query-authorization regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
