<?php
require __DIR__ . '/bootstrap.php';
require dirname( __DIR__ ) . '/includes/class-wca-contracts.php';

wca_test_assert( '1.2.1' === WCA_Contracts::RUNTIME_VERSION, 'runtime contract is 1.2.1' );
wca_test_assert( 18 === count( WCA_Contracts::functional_requirements() ), 'all 18 functional requirements are catalogued' );
wca_test_assert( 10 === count( WCA_Contracts::nonfunctional_requirements() ), 'all 10 nonfunctional requirements are catalogued' );
wca_test_assert( 24 === count( WCA_Contracts::future_requirements() ), 'all 24 future requirements are catalogued' );
wca_test_assert( 8 === count( WCA_Contracts::appointment_statuses() ), 'canonical appointment lifecycle has eight states' );
wca_test_assert( 'confirmed' === WCA_Contracts::normalize_appointment_status( 'accepted' ), 'legacy accepted maps to confirmed' );
wca_test_assert( 'reschedule_pending' === WCA_Contracts::normalize_appointment_status( 'reschedule-requested' ), 'legacy reschedule maps safely' );
wca_test_assert( WCA_Contracts::is_appointment_status( 'accepted', true ), 'legacy status is recognized only through compatibility path' );
wca_test_assert( ! WCA_Contracts::is_appointment_status( 'destroyed', true ), 'unknown status is rejected' );
wca_test_assert( WCA_Contracts::can_transition( 'doctor', 'requested', 'confirmed' ), 'doctor may confirm a request' );
wca_test_assert( ! WCA_Contracts::can_transition( 'patient', 'requested', 'completed' ), 'patient cannot complete an appointment' );
wca_test_assert( WCA_Contracts::is_terminal( 'completed' ), 'completed is terminal' );
wca_test_assert( ! WCA_Contracts::can_transition( 'admin', 'completed', 'requested' ), 'terminal state cannot revive' );
wca_test_assert( 0 === WCA_Contracts::contract_manifest()['commission_percent'], 'platform commission is immutable at zero' );
wca_test_assert( false === WCA_Contracts::contract_manifest()['donation_visibility_link'], 'donations are not linked to visibility' );
wca_test_assert( false === WCA_Contracts::contract_manifest()['automated_diagnosis'], 'future expansion never enables automated diagnosis' );
wca_test_assert( false === WCA_Contracts::contract_manifest()['automated_prescribing'], 'future expansion never enables automated prescribing' );
wca_test_assert( 5 === count( WCA_Contracts::routes() ), 'all canonical public/protected routes are declared' );
wca_test_assert( in_array( 'AppointmentCompleted.v1', WCA_Contracts::published_events(), true ), 'completion event is published' );
echo "Contract tests complete.\n";
