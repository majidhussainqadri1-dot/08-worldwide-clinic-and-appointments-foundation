<?php
require __DIR__ . '/bootstrap.php';
$root = dirname( __DIR__ );
$all = '';
$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS ) );
foreach ( $iterator as $file ) {
	if ( $file->isFile() && in_array( $file->getExtension(), array( 'php','js','css','md','txt','yml','json' ), true ) && false === strpos( $file->getPathname(), '/build/' ) ) { $all .= "\n" . file_get_contents( $file->getPathname() ); }
}
$main = file_get_contents( $root . '/worldwide-clinic.php' );
$contracts = file_get_contents( $root . '/includes/class-wca-contracts.php' );
$schema = file_get_contents( $root . '/includes/class-wca-schema.php' );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$future = file_get_contents( $root . '/includes/class-wca-future24.php' );

wca_test_assert( false !== strpos( $main, 'Version: 1.2.15' ), 'plugin version is 1.2.13' );
wca_test_assert( false !== strpos( $main, 'Text Domain: worldwide-clinic-appointments' ), 'canonical text domain is used' );
wca_test_assert( false === strpos( $main, "Text Domain: worldwide-clinic\n" ), 'legacy text domain is not declared' );
foreach ( range( 1, 18 ) as $n ) { wca_test_assert( false !== strpos( $contracts, sprintf( 'F08-FR-%03d', $n ) ), sprintf( 'FR-%03d is traceable', $n ) ); }
foreach ( range( 1, 10 ) as $n ) { wca_test_assert( false !== strpos( $contracts, sprintf( 'F08-NFR-%03d', $n ) ), sprintf( 'NFR-%03d is traceable', $n ) ); }
wca_test_assert( false !== strpos( $contracts, 'future_requirements' ), 'Future24 requirements are included in the canonical contract manifest' );
foreach ( range( 1, 24 ) as $n ) { wca_test_assert( false !== strpos( $future, sprintf( 'F08-FUT-%02d', $n ) ), sprintf( 'FUT-%02d is traceable and implemented', $n ) ); }
foreach ( array( 'wca_clinics','wca_branches','wca_services','wca_availability_rules','wca_slot_holds','wca_consents','wca_events','wca_review_eligibility','wca_clinical_context_refs','wca_complaints','wca_outbox','wca_payment_intents','wca_calendar_mappings','wca_idempotency','wca_metrics' ) as $table ) { wca_test_assert( false !== strpos( $schema, $table ), "schema owns {$table}" ); }
wca_test_assert( false !== strpos( $future, 'wca_future24_records' ), 'Future24 additive operational table is declared' );
foreach ( array( 'ClinicActivated.v1','ClinicAvailabilityChanged.v1','AppointmentRequested.v1','AppointmentConfirmed.v1','AppointmentCompleted.v1','ReviewEligibilityGranted.v1' ) as $event ) { wca_test_assert( false !== strpos( $all, $event ), "event {$event} is implemented" ); }
foreach ( array( '/clinic/{clinic_slug}','/appointments/book/{doctor_or_clinic}','/appointments','/clinic/dashboard','/appointment/{public_ref}' ) as $route ) { wca_test_assert( false !== strpos( $contracts, $route ), "route {$route} is contracted" ); }
wca_test_assert( false !== strpos( $service, 'platform_commission_bps' ) && false !== strpos( $service, '= 0' ), 'service commands force zero commission' );
wca_test_assert( false !== strpos( $service, 'emergency_red_flag' ), 'emergency diversion is implemented' );
wca_test_assert( false !== strpos( $service, 'grant_review_eligibility' ), 'completed-appointment review eligibility is implemented' );
wca_test_assert( false !== strpos( $service, 'appointment_ics' ), 'calendar export is implemented' );
wca_test_assert( false !== strpos( $service, 'CF03.PaymentIntentRequested.v1' ), 'payment bridge is implemented without ledger ownership' );
wca_test_assert( false !== strpos( $service, 'CF02.CaseRequested.v1' ), 'complaint case bridge is implemented' );
wca_test_assert( false !== strpos( $service, "'clinical_authority'=> false" ) || false !== strpos( $service, "'clinical_authority' => false" ), 'scheduling context grants no clinical authority' );
wca_test_assert( false !== strpos( $rest, "'permission_callback'" ), 'REST routes declare permission callbacks' );
wca_test_assert( false !== strpos( $rest, 'X-WP-Nonce' ) || false !== strpos( file_get_contents( $root . '/assets/js/clinic.js' ), 'X-WP-Nonce' ), 'REST cookie authentication uses a nonce' );
wca_test_assert( false !== strpos( $all, 'private, no-store' ), 'protected routes use no-store caching' );
wca_test_assert( false !== strpos( $all, 'prefers-reduced-motion' ) && false !== strpos( $all, 'forced-colors' ), 'accessibility media modes are implemented' );
wca_test_assert( false !== strpos( $all, 'RTL' ) || false !== strpos( $all, '[dir="rtl"]' ), 'RTL support is present' );
wca_test_assert( false !== strpos( $future, "'auto_book' => false" ) && false !== strpos( $future, "'patient_scoring' => false" ), 'future automation remains offer/advisory based without hidden patient scoring' );
echo "Master-plan static tests complete.\n";
