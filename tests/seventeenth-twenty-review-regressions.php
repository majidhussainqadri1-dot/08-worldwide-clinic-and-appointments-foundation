<?php
$root = dirname( __DIR__ );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$opaque = file_get_contents( $root . '/includes/class-wca-opaque-api.php' );
$future = file_get_contents( $root . '/includes/class-wca-future24.php' );
$assertions = array(
    'strict object identifier validator exists' => false !== strpos( $service, 'public static function strict_id' ),
    'transition CAS uses strict identifier validation' => false !== strpos( $service, "self::strict_id( \$data['expected_version'] )" ),
    'service mutation validates object/CAS identifiers' => false !== strpos( $service, "wca_service_identifier" ),
    'availability mutation validates object/CAS identifiers' => false !== strpos( $service, "wca_availability_identifier" ),
    'REST preserves raw expected version for service-root validation' => false === strpos( $rest, "absint( \$request->get_param( 'expected_version' ) )" ),
    'opaque API preserves raw expected version' => false === strpos( $opaque, "absint( \$request->get_param( 'expected_version' ) )" ),
    'Future24 body identifiers use strict validation' => false !== strpos( $future, 'private static function input_id' ),
    'Future24 flexible windows reject malformed members' => false !== strpos( $future, 'wca_windows_member_invalid' ),
    'Future24 questionnaire rejects unsupported or duplicate fields' => false !== strpos( $future, 'wca_questionnaire_field_invalid' ),
    'Future24 prerequisites reject malformed rules' => false !== strpos( $future, 'wca_prerequisite_rule_invalid' ) && false !== strpos( $future, 'wca_prerequisite_behavior_invalid' ),
);
$failed = array_keys( array_filter( $assertions, static function ( $ok ) { return ! $ok; } ) );
foreach ( $assertions as $name => $ok ) { echo ( $ok ? '[PASS] ' : '[FAIL] ' ) . $name . PHP_EOL; }
if ( $failed ) { fwrite( STDERR, 'T17 regression failures: ' . implode( ', ', $failed ) . PHP_EOL ); exit( 1 ); }
echo 'T17 regression assertions: ' . count( $assertions ) . '/' . count( $assertions ) . ' PASS' . PHP_EOL;
