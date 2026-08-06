<?php
/**
 * Dependency-free tests for authoritative practitioner eligibility.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'SMC_VERSION', '1.2.4' );
define( 'SMC_CONTRACT_VERSION', '1.1.2' );
define( 'GDO_VERSION', '1.1.0' );

final class WP_User {
	public $ID;
	public function __construct( $id ) { $this->ID = $id; }
}

$GLOBALS['swc_authority_users'] = array( 7 => new WP_User( 7 ), 8 => new WP_User( 8 ), 9 => new WP_User( 9 ) );
$GLOBALS['swc_assertions'] = array();
$GLOBALS['swc_founders'] = array( 8 => true );
$GLOBALS['swc_gdo_decisions'] = array();
$GLOBALS['swc_gdo_snapshots'] = array();
$GLOBALS['swc_gdo_verified'] = array();
$GLOBALS['swc_spd_status'] = array();
$GLOBALS['swc_authority_filter'] = null;

function absint( $value ) { return abs( (int) $value ); }
function get_userdata( $user_id ) { return isset( $GLOBALS['swc_authority_users'][ $user_id ] ) ? $GLOBALS['swc_authority_users'][ $user_id ] : false; }
function sanitize_key( $value ) { return trim( preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ) ); }
function apply_filters( $hook, $value ) {
	$args = func_get_args();
	if ( is_callable( $GLOBALS['swc_authority_filter'] ) ) {
		return call_user_func_array( $GLOBALS['swc_authority_filter'], $args );
	}
	return $value;
}
function smc_is_founder( $user_id ) { return ! empty( $GLOBALS['swc_founders'][ $user_id ] ); }
function gdo_get_verification_decision( $user_id ) { return $GLOBALS['swc_gdo_decisions'][ $user_id ] ?? array(); }
function gdo_get_approved_snapshot( $user_id ) { return $GLOBALS['swc_gdo_snapshots'][ $user_id ] ?? array(); }
function gdo_user_is_verified( $user_id ) { return ! empty( $GLOBALS['swc_gdo_verified'][ $user_id ] ); }

final class SMC_Contracts {
	public static function assertions( $user_id ) { return $GLOBALS['swc_assertions'][ $user_id ] ?? array(); }
}
final class SPD_Helpers {
	public static function verification_status( $user_id ) { return $GLOBALS['swc_spd_status'][ $user_id ] ?? 'verified'; }
}

require dirname( __DIR__ ) . '/includes/class-swc-doctor-authority.php';

$tests = 0;
function swc_authority_assert( $condition, $message ) {
	global $tests;
	++$tests;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

$doctor_assertions = array(
	'contract_version' => '1.1.2',
	'user_id' => 7,
	'account_class' => 'member',
	'membership_type' => 'doctor',
	'approved' => true,
	'eligible' => true,
	'suspended' => false,
	'professional_verified' => true,
	'can_practice' => true,
);
$GLOBALS['swc_assertions'][7] = $doctor_assertions;
$GLOBALS['swc_gdo_decisions'][7] = array( 'state' => 'verified', 'verified' => true );
$GLOBALS['swc_gdo_snapshots'][7] = array( 'profile' => array( 'clinic' => 'Clinic' ) );
$GLOBALS['swc_gdo_verified'][7] = true;
$GLOBALS['swc_spd_status'][7] = 'verified';

swc_authority_assert( SWC_Doctor_Authority::is_eligible( 7 ), 'File 00 and File 09 agreement admits an eligible Doctor' );
$GLOBALS['swc_gdo_verified'][7] = false;
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 7 ), 'File 09 decision/helper disagreement fails closed' );
$GLOBALS['swc_gdo_verified'][7] = true;
$GLOBALS['swc_gdo_snapshots'][7] = array();
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 7 ), 'missing approved File 09 snapshot fails closed' );
$GLOBALS['swc_gdo_snapshots'][7] = array( 'profile' => array( 'clinic' => 'Clinic' ) );
$GLOBALS['swc_assertions'][7]['suspended'] = true;
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 7 ), 'File 00 suspension overrides Doctor verification' );
$GLOBALS['swc_assertions'][7]['suspended'] = false;
$GLOBALS['swc_assertions'][7]['user_id'] = 9;
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 7 ), 'File 00 assertion user mismatch fails closed' );
$GLOBALS['swc_assertions'][7] = $doctor_assertions;
$GLOBALS['swc_spd_status'][7] = 'revoked';
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 7 ), 'File 03 revoked state narrows public eligibility' );
$GLOBALS['swc_spd_status'][7] = 'verified';

$GLOBALS['swc_assertions'][8] = array(
	'contract_version' => '1.1.2',
	'user_id' => 8,
	'account_class' => 'founder',
	'membership_type' => '',
	'institutional_account' => true,
	'approved' => true,
	'eligible' => true,
	'suspended' => false,
);
swc_authority_assert( SWC_Doctor_Authority::is_eligible( 8 ), 'canonical File 00 Founder is admitted without fabricated Doctor application state' );

$GLOBALS['swc_assertions'][9] = array(
	'contract_version' => '1.1.2',
	'user_id' => 9,
	'account_class' => 'member',
	'membership_type' => 'member',
	'approved' => true,
	'eligible' => true,
	'suspended' => false,
);
$GLOBALS['swc_authority_filter'] = function ( $hook, $value ) {
	return 'swc_authoritative_practitioner_eligible' === $hook ? true : $value;
};
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 9 ), 'extension filters cannot grant denied practitioner authority' );
$GLOBALS['swc_authority_filter'] = function ( $hook, $value, $user_id ) {
	return 'swc_authoritative_practitioner_eligible' === $hook && 7 === $user_id ? false : $value;
};
swc_authority_assert( ! SWC_Doctor_Authority::is_eligible( 7 ), 'extension filters may revoke an otherwise eligible Doctor' );

$contract = SWC_Doctor_Authority::contract();
swc_authority_assert( '1.1.2' === $contract['file_00_contract'], 'File 00 assertion contract is exact' );
swc_authority_assert( false === $contract['local_role_inference'] && false === $contract['local_meta_inference'], 'local role and metadata inference are prohibited' );

echo "All {$tests} authoritative practitioner checks passed.\n";
