from pathlib import Path

repo=Path('includes/class-wca-repository.php'); r=repo.read_text()
old="""\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_service_read_failed', __( 'Service data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\treturn $row ?: null;"""
new="""\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_service_read_failed', __( 'Service data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\tif ( $row && $public_only && ! WCA_Service::valid_currency( (string) $row['currency'] ) ) {\n\t\t\tself::note_read_error( 'wca_service_currency_invalid', __( 'Public service pricing contains an unsupported currency and cannot be trusted.', 'worldwide-clinic-appointments' ) );\n\t\t\treturn null;\n\t\t}\n\t\treturn $row ?: null;"""
if r.count(old)!=1: raise SystemExit('R6 get_service public currency anchor not unique')
r=r.replace(old,new,1)
old="""\t\treturn array_values( array_filter( array_map( static function ( $row ) use ( $clinic ) {\n\t\t\t$doctor_id = absint( $row['doctor_user_id'] ) ?: absint( $clinic['owner_user_id'] ?? 0 );"""
new="""\t\treturn array_values( array_filter( array_map( static function ( $row ) use ( $clinic ) {\n\t\t\tif ( ! WCA_Service::valid_currency( (string) ( $row['currency'] ?? '' ) ) ) { return null; }\n\t\t\t$doctor_id = absint( $row['doctor_user_id'] ) ?: absint( $clinic['owner_user_id'] ?? 0 );"""
if r.count(old)!=1: raise SystemExit('R6 list_services mapper anchor not unique')
r=r.replace(old,new,1); repo.write_text(r)

svc=Path('includes/class-wca-service.php'); s=svc.read_text()
old="""\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) || null === $amount || ! $service_ref || ! $service_version ) {\n\t\t\treturn new WP_Error( 'wca_payment_snapshot_missing', __( 'The appointment does not have a trustworthy booked fee snapshot. Financial reconciliation is required before payment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}"""
new="""\t\tif ( ! self::valid_currency( $currency ) || null === $amount || ! $service_ref || ! $service_version ) {\n\t\t\treturn new WP_Error( 'wca_payment_snapshot_missing', __( 'The appointment does not have a trustworthy booked fee snapshot. Financial reconciliation is required before payment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}"""
if s.count(old)!=1: raise SystemExit('R6 payment snapshot currency anchor not unique')
s=s.replace(old,new,1); svc.write_text(s)

Path('tests/t18-r6-financial-readside-regressions.php').write_text(r'''<?php
$root=dirname(__DIR__);
$repo=file_get_contents($root.'/includes/class-wca-repository.php');
$svc=file_get_contents($root.'/includes/class-wca-service.php');
$checks=array(
 'public get_service rejects invalid legacy currency'=>strpos($repo,"$public_only && ! WCA_Service::valid_currency")!==false && strpos($repo,'wca_service_currency_invalid')!==false,
 'public service list filters invalid legacy currency'=>strpos($repo,"if ( ! WCA_Service::valid_currency( (string) ( $row['currency'] ?? '' ) ) ) { return null; }")!==false,
 'payment snapshot uses canonical currency validator'=>strpos($svc,'if ( ! self::valid_currency( $currency ) || null === $amount')!==false,
 'payment snapshot regex-only trust removed'=>strpos($svc,"preg_match( '/^[A-Z]{3}$/', $currency )")===false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R6 FAIL: {$n}\n");exit(1);}}
echo "T18 R6 financial read-side regressions: PASS\n";
''')
ra=Path('tests/run-all.php'); rr=ra.read_text(); needle="'t18-r5-currency-validation-regressions.php',"
if needle in rr and 't18-r6-financial-readside-regressions.php' not in rr:
    ra.write_text(rr.replace(needle,needle+"\n    't18-r6-financial-readside-regressions.php',",1))
print('T18 R6 frozen ledger correction applied')