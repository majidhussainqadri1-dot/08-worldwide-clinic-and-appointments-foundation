<?php
$root = dirname( __DIR__ );
$helpers = file_get_contents( $root . '/includes/class-swc-helpers.php' );
$authority = file_get_contents( $root . '/includes/class-swc-doctor-authority.php' );
if ( ! is_string( $helpers ) || ! is_string( $authority ) ) { fwrite( STDERR, "T19 R7 source read failed\n" ); exit( 1 ); }

$start = strpos( $helpers, 'public static function is_verified_doctor' );
$end = strpos( $helpers, 'public static function doctor_is_requestable', $start );
$authority_block = false !== $start && false !== $end ? substr( $helpers, $start, $end - $start ) : '';
$doctor_ids_start = strpos( $helpers, 'public static function doctor_ids' );
$doctor_ids_end = strpos( $helpers, 'private static function practitioner_is_public', $doctor_ids_start );
$doctor_ids_block = false !== $doctor_ids_start && false !== $doctor_ids_end ? substr( $helpers, $doctor_ids_start, $doctor_ids_end - $doctor_ids_start ) : '';

$checks = array(
	'helper delegates eligibility to canonical authority adapter' => false !== strpos( $authority_block, 'SWC_Doctor_Authority::is_eligible( $user_id )' ),
	'helper no longer reads local doctor verification meta' => false === strpos( $authority_block, '_smc_doctor_verified' ),
	'helper no longer infers eligibility from profile verification' => false === strpos( $authority_block, 'SDD_Helpers::is_verified' ),
	'helper no longer infers eligibility from completion percentage' => false === strpos( $authority_block, 'GDO_Helpers::completion' ),
	'discovery has no local role prefilter' => false === strpos( $doctor_ids_block, 'role__in' ),
	'discovery uses bounded raw batches' => false !== strpos( $doctor_ids_block, "'number'  => \$batch_size" ) && false !== strpos( $doctor_ids_block, '$batch_size   = 200;' ),
	'discovery advances bounded raw offset' => false !== strpos( $doctor_ids_block, '$raw_offset += $batch_size;' ),
	'discovery applies canonical eligibility before acceptance' => false !== strpos( $doctor_ids_block, 'self::is_verified_doctor( $id ) && self::practitioner_is_public( $id )' ),
	'discovery applies requested pagination after eligibility' => false !== strpos( $doctor_ids_block, 'array_slice( array_values( array_unique( $eligible_ids ) ), $offset, $limit )' ),
	'publicity helper fails closed on missing profile authority' => false !== strpos( $helpers, "private static function practitioner_is_public" ) && false !== strpos( $helpers, "return false;\n\t\t}\n\t\ttry" ),
	'canonical authority contract prohibits local role inference' => false !== strpos( $authority, "'local_role_inference'   => false" ),
	'canonical authority contract prohibits local meta inference' => false !== strpos( $authority, "'local_meta_inference'   => false" ),
	'appointment doctor management still uses canonicalized helper' => false !== strpos( $helpers, '&& self::is_verified_doctor( $user_id );' ),
	'slot availability still uses canonicalized helper' => false !== strpos( $helpers, 'if ( ! self::is_verified_doctor( $doctor_id ) || ! $utc )' ),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R7 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R7 practitioner authority regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
