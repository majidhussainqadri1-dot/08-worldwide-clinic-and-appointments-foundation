<?php
/**
 * Dependency-free tests for File 08 public clinic projection contract 1.0.0.
 */

define( 'ABSPATH', __DIR__ . '/' );

final class WP_User {
	public $ID;
	public $display_name;
	public function __construct( $id, $name ) { $this->ID = $id; $this->display_name = $name; }
}

$GLOBALS['swc_users'] = array(
	7 => new WP_User( 7, 'Dr Public' ),
	8 => new WP_User( 8, 'Dr Private' ),
	9 => new WP_User( 9, 'Dr Empty' ),
);
$GLOBALS['swc_filter'] = null;

function absint( $value ) { return abs( (int) $value ); }
function get_userdata( $user_id ) { return isset( $GLOBALS['swc_users'][ $user_id ] ) ? $GLOBALS['swc_users'][ $user_id ] : false; }
function sanitize_key( $value ) { return trim( preg_replace( '/[^a-z0-9_-]/', '', strtolower( (string) $value ) ) ); }
function sanitize_text_field( $value ) { return trim( preg_replace( '/\s+/u', ' ', strip_tags( (string) $value ) ) ); }
function wp_strip_all_tags( $value ) { return strip_tags( (string) $value ); }
function apply_filters( $hook, $value ) {
	$args = func_get_args();
	if ( is_callable( $GLOBALS['swc_filter'] ) ) {
		return call_user_func_array( $GLOBALS['swc_filter'], $args );
	}
	return $value;
}

final class SDD_Helpers {
	public static $public = array( 7 => true, 9 => true );
	public static function is_public( $user_id ) { return ! empty( self::$public[ $user_id ] ); }
	public static function is_founder( $user_id ) { return false; }
}

final class SWC_Helpers {
	public static $verified = array( 7 => true, 8 => true, 9 => true );
	public static $profiles = array(
		7 => array(
			'clinic_name' => '<b>Global Clinic</b>',
			'clinic_address' => '12 Main Street',
			'country' => 'Pakistan',
			'city' => 'Gujrat',
			'phone' => '+923001234567',
		),
		8 => array( 'clinic_name' => 'Private Clinic' ),
		9 => array( 'address' => 'Private residential address' ),
	);
	public static $availability = array(
		7 => array(
			'days' => array( 'monday', 'wednesday' ),
			'start' => '09:00', 'end' => '13:00', 'timezone' => 'Asia/Karachi',
			'duration' => 30, 'online' => true, 'in_person' => true,
			'accepting' => true, 'unavailable' => false,
		),
		8 => array(),
		9 => array(),
	);
	public static function is_verified_doctor( $user_id ) { return ! empty( self::$verified[ $user_id ] ); }
	public static function profile_value( $user_id, $key, $default = '' ) {
		return isset( self::$profiles[ $user_id ][ $key ] ) ? self::$profiles[ $user_id ][ $key ] : $default;
	}
	public static function availability( $user_id ) { return isset( self::$availability[ $user_id ] ) ? self::$availability[ $user_id ] : array(); }
	public static function availability_is_valid( $a ) {
		return ! empty( $a['days'] ) && ! empty( $a['start'] ) && ! empty( $a['end'] ) && ! empty( $a['timezone'] );
	}
	public static function weekdays() { return array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ); }
}

require dirname( __DIR__ ) . '/includes/class-swc-public-clinic.php';

$tests = 0;
function swc_contract_assert( $condition, $message ) {
	global $tests;
	++$tests;
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
	echo "PASS: {$message}\n";
}

swc_contract_assert( array() === swc_get_public_clinic_projection( 0 ), 'invalid user fails closed' );
SWC_Helpers::$verified[7] = false;
swc_contract_assert( array() === swc_get_public_clinic_projection( 7 ), 'unverified doctor fails closed' );
SWC_Helpers::$verified[7] = true;
swc_contract_assert( array() === swc_get_public_clinic_projection( 8 ), 'private doctor fails closed' );
swc_contract_assert( array() === swc_get_public_clinic_projection( 9 ), 'generic private address does not fabricate a clinic section' );

$projection = swc_get_public_clinic_projection( 7 );
swc_contract_assert( '1.0.0' === $projection['contract_version'], 'contract version is exact' );
swc_contract_assert( 'Global Clinic' === $projection['clinic']['name'], 'clinic name is plain text' );
swc_contract_assert( 'Monday, Wednesday · 09:00–13:00' === $projection['clinic']['hours'], 'hours are deterministic and bounded' );
swc_contract_assert( 'Asia/Karachi' === $projection['clinic']['timezone'], 'timezone is projected from File 08 availability' );
swc_contract_assert( ! isset( $projection['clinic']['phone'] ) && ! isset( $projection['clinic']['whatsapp'] ), 'contact data is excluded' );
swc_contract_assert( ! isset( $projection['clinic']['user_id'] ) && ! isset( $projection['clinic']['native_id'] ), 'native identifiers are excluded' );

$GLOBALS['swc_filter'] = function ( $hook, $value ) {
	if ( 'swc_public_clinic_projection' === $hook ) {
		$value['phone'] = '+923001234567';
		$value['native_id'] = 77;
		unset( $value['address'] );
		$value['name'] = 'Filtered Clinic';
	}
	return $value;
};
$filtered = swc_get_public_clinic_projection( 7 );
swc_contract_assert( 'Global Clinic' === $filtered['clinic']['name'], 'filters cannot replace canonical presentation values' );
swc_contract_assert( ! isset( $filtered['clinic']['address'] ), 'filters may revoke a canonical field' );
swc_contract_assert( ! isset( $filtered['clinic']['phone'] ) && ! isset( $filtered['clinic']['native_id'] ), 'filters cannot add forbidden fields' );

$contract = swc_public_clinic_projection_contract();
swc_contract_assert( 'file-08' === $contract['owner'], 'contract owner is File 08' );
swc_contract_assert( false === $contract['writes_data'], 'public projection is read-only' );
swc_contract_assert( in_array( 'patient_data', $contract['excludes'], true ), 'patient data exclusion is explicit' );

echo "All {$tests} public clinic projection checks passed.\n";
