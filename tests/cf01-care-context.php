<?php
/**
 * No-network runtime checks for File 08 CF-01 care-context assertions.
 */

declare(strict_types=1);

define( 'ABSPATH', __DIR__ . '/' );
define( 'SWC_VERSION', '0.2.2' );
define( 'SMC_VERSION', '1.2.7' );
define( 'SMC_CF01_CONTRACT_VERSION', '1.0.0' );

$GLOBALS['swc_test_current_user'] = 10;
$GLOBALS['swc_test_post_type']    = array( 100 => 'swc_appointment', 101 => 'swc_appointment' );
$GLOBALS['swc_test_meta']         = array(
	100 => array(
		'patient_user_id'     => 10,
		'doctor_id'           => 20,
		'status'              => 'accepted',
		'record_version'      => 7,
		'consultation_type'   => 'online',
		'preferred_at_utc'    => '2026-08-05 10:00:00',
		'consent_at'          => '2026-08-03 10:00:00',
		'consent_version'     => 'appointment-1.0',
		'reason'              => 'private presenting concern',
		'concern_duration'    => 'private duration',
		'doctor_private_note' => 'private note',
		'patient_message'     => 'private message',
		'phone'               => '+923001234567',
	),
	101 => array(
		'patient_user_id'   => 10,
		'doctor_id'         => 20,
		'status'            => 'completed',
		'record_version'    => 3,
		'consultation_type' => 'in-person',
		'preferred_at_utc'  => '2026-08-01 09:00:00',
	),
);
$GLOBALS['swc_test_post_author']  = array( 100 => 10, 101 => 10 );
$GLOBALS['swc_test_users']        = array( 10 => true, 20 => true, 30 => true );
$GLOBALS['swc_test_denied_users'] = array();

function absint( $value ) { return abs( (int) $value ); }
function sanitize_key( $value ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $value ) ); }
function get_post_type( $id ) { return $GLOBALS['swc_test_post_type'][ (int) $id ] ?? ''; }
function get_post_field( $field, $id ) { unset( $field ); return $GLOBALS['swc_test_post_author'][ (int) $id ] ?? 0; }
function get_current_user_id() { return (int) $GLOBALS['swc_test_current_user']; }
function user_can( $user_id, $capability ) { return 30 === (int) $user_id && 'manage_worldwide_clinic' === $capability; }
function wp_salt( $scheme = 'auth' ) { return hash( 'sha256', 'file08-cf01-test|' . $scheme ); }

final class SWC_Helpers {
	const TYPE = 'swc_appointment';
	public static function meta( $id, $key, $default = '' ) { return $GLOBALS['swc_test_meta'][ (int) $id ][ $key ] ?? $default; }
	public static function record_version( $id ) { return (int) self::meta( $id, 'record_version', 1 ); }
	public static function status( $id ) { return (string) self::meta( $id, 'status', 'requested' ); }
	public static function can_patient_manage( $id, $user_id = 0 ) { return (int) self::meta( $id, 'patient_user_id', 0 ) === (int) $user_id; }
	public static function can_doctor_manage( $id, $user_id = 0 ) { return (int) self::meta( $id, 'doctor_id', 0 ) === (int) $user_id; }
}

final class SMC_CF01_Contract {
	public static $available = true;
	public static function membership_assertion( $user_id, $context = array() ) {
		unset( $context );
		if ( ! self::$available || ! isset( $GLOBALS['swc_test_users'][ (int) $user_id ] ) ) {
			return array( 'result' => 'unknown' );
		}
		$uuid = 10 === (int) $user_id
			? '11111111-1111-4111-8111-111111111111'
			: '22222222-2222-4222-8222-222222222222';
		return array(
			'contract'         => 'smc.cf01.membership-assurance',
			'contract_version' => '1.0.0',
			'result'           => in_array( (int) $user_id, $GLOBALS['swc_test_denied_users'], true ) ? 'deny' : 'allow',
			'reason_code'      => 'capability_allowed',
			'subject'          => array( 'platform_uuid' => $uuid ),
		);
	}
}

require dirname( __DIR__ ) . '/includes/class-swc-cf01-care-context.php';

function swc_expect( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, 'FAIL: ' . $message . PHP_EOL );
		exit( 1 );
	}
}

$context = SWC_CF01_Care_Context::assertion( 100, 10, 'appointment_context_read', 7 );
swc_expect( 'allow' === $context['result'], 'Patient must receive authorized scheduling context.' );
swc_expect( 'scheduling_context_available' === $context['reason_code'], 'Scheduling allow reason is incorrect.' );
swc_expect( false === $context['relationship']['treating_relationship_asserted'], 'Accepted appointment must not assert a treating relationship.' );
swc_expect( false === $context['relationship']['sufficient_for_clinical_read'], 'Appointment must not grant clinical read.' );
swc_expect( false === $context['relationship']['sufficient_for_prescription'], 'Appointment must not grant prescription authority.' );
swc_expect( false === $context['consent']['clinical_treatment_consent'], 'Appointment consent must not become clinical consent.' );
swc_expect( false === $context['consent']['publication_consent'], 'Appointment consent must not become publication consent.' );
swc_expect( '' === $context['context']['clinic_reference'] && false === $context['context']['clinic_location_modeled'], 'Missing clinic entity must remain explicitly unmodeled.' );
$serialized = serialize( $context );
foreach ( array( 'private presenting concern', 'private duration', 'private note', 'private message', '+923001234567' ) as $secret ) {
	swc_expect( false === strpos( $serialized, $secret ), 'Care-context leaked a prohibited clinical/contact value.' );
}

$clinical = SWC_CF01_Care_Context::assertion( 100, 10, 'clinical_write', 7 );
swc_expect( 'deny' === $clinical['result'] && 'appointment_is_not_treating_relationship' === $clinical['reason_code'], 'Appointment must deny clinical writes.' );

$stale = SWC_CF01_Care_Context::assertion( 100, 10, 'appointment_context_read', 6 );
swc_expect( 'deny' === $stale['result'] && 'stale_record_version' === $stale['reason_code'], 'Stale record version must fail closed.' );

$GLOBALS['swc_test_denied_users'] = array( 10 );
$suspended = SWC_CF01_Care_Context::assertion( 100, 10, 'appointment_context_read', 7 );
swc_expect( 'deny' === $suspended['result'] && 'actor_membership_not_eligible' === $suspended['reason_code'], 'Current actor eligibility must be revalidated.' );
$GLOBALS['swc_test_denied_users'] = array();

$GLOBALS['swc_test_current_user'] = 30;
$admin = SWC_CF01_Care_Context::assertion( 100, 30, 'appointment_context_read', 7 );
swc_expect( 'allow' === $admin['result'], 'Authorized eligible clinic administrator must receive scheduling context.' );
$GLOBALS['swc_test_current_user'] = 99;
$unauthorized = SWC_CF01_Care_Context::assertion( 100, 99, 'appointment_context_read', 7 );
swc_expect( 'deny' === $unauthorized['result'] && 'context_not_available' === $unauthorized['reason_code'], 'Unauthorized actor must fail without object detail.' );

$GLOBALS['swc_test_current_user'] = 10;
$completed = SWC_CF01_Care_Context::assertion( 101, 10, 'treating_relationship_activate', 3 );
swc_expect( 'deny' === $completed['result'], 'Completed appointment must not activate a treating relationship.' );
swc_expect( 'appointment_completed_without_relationship_assertion' === $completed['relationship']['status'], 'Completed relationship state is incorrect.' );

$unsupported = SWC_CF01_Care_Context::assertion( 100, 10, 'prescription_issue', 7 );
swc_expect( 'unknown' === $unsupported['result'] && 'unsupported_purpose' === $unsupported['reason_code'], 'Unsupported purpose must fail unknown.' );

SMC_CF01_Contract::$available = false;
$identity_missing = SWC_CF01_Care_Context::assertion( 100, 10, 'appointment_context_read', 7 );
swc_expect( 'unknown' === $identity_missing['result'] && 'actor_identity_assertion_unavailable' === $identity_missing['reason_code'], 'Unavailable actor identity assertion must fail unknown.' );

echo "File 08 CF-01 care-context checks: 22 PASS, 0 FAIL\n";
