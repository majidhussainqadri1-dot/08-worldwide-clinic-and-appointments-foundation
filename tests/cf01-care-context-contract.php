<?php
/**
 * Static contract and inventory guard for File 08 0.2.2.
 */

declare(strict_types=1);

$root      = dirname( __DIR__ );
$main      = file_get_contents( $root . '/worldwide-clinic.php' );
$contract  = file_get_contents( $root . '/includes/class-swc-cf01-care-context.php' );
$inventory = file_get_contents( $root . '/CF01-CARE-CONTEXT-AND-EXTRACTION-INVENTORY.md' );
$builder   = file_get_contents( $root . '/tools/build-development-candidate.php' );
$failures  = array();
$passed    = 0;

$check = static function ( $condition, $label ) use ( &$failures, &$passed ) {
	if ( $condition ) {
		++$passed;
		return;
	}
	$failures[] = $label;
};

$check( false !== strpos( $main, 'Version: 0.2.2' ), 'Plugin header is not 0.2.2.' );
$check( false !== strpos( $main, "define( 'SWC_VERSION', '0.2.2' )" ), 'Runtime version is not 0.2.2.' );
$check( false !== strpos( $main, "define( 'SWC_CF01_CARE_CONTEXT_VERSION', '1.0.0' )" ), 'Care-context contract version is missing.' );
$check( false !== strpos( $main, "require_once SWC_DIR . 'includes/class-swc-cf01-care-context.php'" ), 'Care-context provider is not loaded.' );
$check( false !== strpos( $contract, "const CONTRACT_NAME       = 'swc.cf01.care-context'" ), 'Named contract is missing.' );
$check( false !== strpos( $contract, "'treating_relationship_asserted' => false" ), 'Appointment/relationship separation is missing.' );
$check( false !== strpos( $contract, "'clinical_treatment_consent'      => false" ), 'Appointment/clinical-consent separation is missing.' );
$check( false !== strpos( $contract, "'publication_consent'             => false" ), 'Appointment/publication-consent separation is missing.' );
$check( false !== strpos( $contract, "'appointment_is_not_treating_relationship'" ), 'Clinical-purpose denial is missing.' );
$check( false !== strpos( $contract, "'stale_record_version'" ), 'Optimistic version denial is missing.' );
$check( false !== strpos( $contract, "'clinic_location_modeled'   => false" ), 'Unmodeled clinic/location state is missing.' );
$check( false !== strpos( $contract, "SMC_CF01_Contract::membership_assertion" ), 'File 00 opaque subject assertion is not consumed.' );
$check( false === strpos( $contract, "SWC_Helpers::meta( $appointment_id, 'reason'" ), 'Contract must not read presenting-concern narrative.' );
$check( false === strpos( $contract, "SWC_Helpers::meta( $appointment_id, 'concern_duration'" ), 'Contract must not read concern duration.' );
$check( false === strpos( $contract, "SWC_Helpers::meta( $appointment_id, 'doctor_private_note'" ), 'Contract must not read private doctor note.' );
$check( false === strpos( $contract, "SWC_Helpers::meta( $appointment_id, 'patient_message'" ), 'Contract must not read patient message.' );
$check( false === strpos( $contract, "SWC_Helpers::meta( $appointment_id, 'phone'" ), 'Contract must not read patient phone.' );
$check( false !== strpos( $inventory, '`_swc_reason`' ) && false !== strpos( $inventory, '`_swc_concern_duration`' ), 'Clinical-like source inventory is incomplete.' );
$check( false !== strpos( $inventory, '`_swc_doctor_private_note`' ) && false !== strpos( $inventory, '`_swc_patient_message`' ), 'Mixed/private narrative inventory is incomplete.' );
$check( false !== strpos( $inventory, 'appointment-processing consent/acknowledgement only' ), 'Appointment consent scope is not explicit.' );
$check( false !== strpos( $inventory, 'no automatic signed encounter or prescription creation' ), 'Extraction safety law is missing.' );
$check( false !== strpos( $builder, "'includes/class-swc-cf01-care-context.php'" ), 'Candidate payload does not include the provider.' );

if ( $failures ) {
	fwrite( STDERR, 'File 08 CF-01 static contract: ' . $passed . ' PASS, ' . count( $failures ) . " FAIL\n- " . implode( "\n- ", $failures ) . "\n" );
	exit( 1 );
}

echo 'File 08 CF-01 static contract: ' . $passed . " PASS, 0 FAIL\n";
