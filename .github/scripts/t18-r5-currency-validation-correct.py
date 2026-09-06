from pathlib import Path

svc=Path('includes/class-wca-service.php'); s=svc.read_text()
anchor="""\tpublic static function valid_hhmm( $value ) {\n\t\treturn 1 === preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', (string) $value );\n\t}\n"""
insert="""\tpublic static function valid_hhmm( $value ) {\n\t\treturn 1 === preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', (string) $value );\n\t}\n\n\tpublic static function valid_currency( $value ) {\n\t\tif ( ! is_string( $value ) ) { return false; }\n\t\t$currency = strtoupper( trim( $value ) );\n\t\t$codes = array(\n\t\t\t'AED','AFN','ALL','AMD','AOA','ARS','AUD','AWG','AZN','BAM','BBD','BDT','BGN','BHD','BIF','BMD','BND','BOB','BRL','BSD','BTN','BWP','BYN','BZD',\n\t\t\t'CAD','CDF','CHF','CLP','CNY','COP','CRC','CUP','CVE','CZK','DJF','DKK','DOP','DZD','EGP','ERN','ETB','EUR','FJD','FKP','GBP','GEL','GHS','GIP','GMD','GNF','GTQ','GYD',\n\t\t\t'HKD','HNL','HTG','HUF','IDR','ILS','INR','IQD','IRR','ISK','JMD','JOD','JPY','KES','KGS','KHR','KMF','KPW','KRW','KWD','KYD','KZT','LAK','LBP','LKR','LRD','LSL','LYD',\n\t\t\t'MAD','MDL','MGA','MKD','MMK','MNT','MOP','MRU','MUR','MVR','MWK','MXN','MYR','MZN','NAD','NGN','NIO','NOK','NPR','NZD','OMR','PAB','PEN','PGK','PHP','PKR','PLN','PYG',\n\t\t\t'QAR','RON','RSD','RUB','RWF','SAR','SBD','SCR','SDG','SEK','SGD','SHP','SLE','SOS','SRD','SSP','STN','SYP','SZL','THB','TJS','TMT','TND','TOP','TRY','TTD','TWD','TZS',\n\t\t\t'UAH','UGX','USD','UYU','UZS','VES','VND','VUV','WST','XAF','XCD','XCG','XOF','XPF','YER','ZAR','ZMW','ZWG'\n\t\t);\n\t\treturn in_array( $currency, $codes, true );\n\t}\n"""
if s.count(anchor)!=1: raise SystemExit('R5 service validator anchor not unique')
s=s.replace(anchor,insert,1)
old="if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { return new WP_Error( 'wca_service_currency', __( 'A valid three-letter currency code is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }"
new="if ( ! self::valid_currency( $currency ) ) { return new WP_Error( 'wca_service_currency', __( 'A currently supported ISO currency code is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }"
if s.count(old)!=1: raise SystemExit('R5 service currency check anchor not unique')
s=s.replace(old,new,1); svc.write_text(s)

repo=Path('includes/class-wca-repository.php'); r=repo.read_text()
old="""\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_currency', __( 'Service persistence requires a valid three-letter currency code.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}"""
new="""\t\tif ( ! WCA_Service::valid_currency( $currency ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_currency', __( 'Service persistence requires a currently supported ISO currency code.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}"""
if r.count(old)!=1: raise SystemExit('R5 repository currency check anchor not unique')
repo.write_text(r.replace(old,new,1))

Path('tests/t18-r5-currency-validation-regressions.php').write_text(r'''<?php
$root=dirname(__DIR__); $svc=file_get_contents($root.'/includes/class-wca-service.php'); $repo=file_get_contents($root.'/includes/class-wca-repository.php');
$checks=array(
 'canonical currency validator exists'=>strpos($svc,'public static function valid_currency')!==false,
 'major worldwide currencies included'=>strpos($svc,"'PKR'")!==false && strpos($svc,"'USD'")!==false && strpos($svc,"'EUR'")!==false && strpos($svc,"'JPY'")!==false && strpos($svc,"'KWD'")!==false,
 'service root uses canonical currency validator'=>strpos($svc,'if ( ! self::valid_currency( $currency ) )')!==false,
 'repository root uses canonical currency validator'=>strpos($repo,'if ( ! WCA_Service::valid_currency( $currency ) )')!==false,
 'regex-only currency trust removed'=>strpos($svc,"preg_match( '/^[A-Z]{3}$/', $currency )")===false && strpos($repo,"preg_match( '/^[A-Z]{3}$/', $currency )")===false,
 'bogus code not whitelisted'=>strpos($svc,"'ZZZ'")===false && strpos($svc,"'FOO'")===false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R5 FAIL: {$n}\n");exit(1);}} echo "T18 R5 currency validation regressions: PASS\n";
''')
ra=Path('tests/run-all.php'); rr=ra.read_text(); needle="'t18-r4-currency-display-regressions.php',"
if needle in rr and 't18-r5-currency-validation-regressions.php' not in rr: ra.write_text(rr.replace(needle,needle+"\n    't18-r5-currency-validation-regressions.php',",1))
print('T18 R5 frozen ledger correction applied')