<?php
$root = dirname( __DIR__ );
$command = file_get_contents( $root . '/includes/class-wca-appointment-command.php' );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
if ( ! is_string( $command ) || ! is_string( $service ) ) { fwrite( STDERR, "T19 R6 source read failed\n" ); exit( 1 ); }
$checks = array(
	'governed REST command returns explicit 201' => false !== strpos( $command, '$response->set_status( 201 );' ),
	'governed response is protected from caching' => false !== strpos( $command, "'Cache-Control', 'private, no-store, max-age=0'" ),
	'command no longer performs post-commit consent reconciliation' => false === strpos( $command, 'ensure_context_consent' ),
	'command documents owner-transaction consent boundary' => false !== strpos( $command, 'owner transaction already persists appointment_processing' ),
	'owner transaction persists processing consent' => false !== strpos( $service, "'scope'              => 'appointment_processing'" ),
	'owner transaction persists privacy notice consent' => false !== strpos( $service, "\$context_scopes = array( 'privacy_notice' )" ),
	'owner transaction persists teleconsult consent when remote' => false !== strpos( $service, "if ( \$remote ) { \$context_scopes[] = 'teleconsult'; }" ),
	'owner idempotency evidence is completed as 201' => false !== strpos( $service, 'complete_idempotency( $claim[\'id\'], 201, $response )' ),
	'public command projection removes numeric appointment id' => false !== strpos( $command, "unset( \$result['appointment_id'] );" ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R6 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R6 appointment commit semantics regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
