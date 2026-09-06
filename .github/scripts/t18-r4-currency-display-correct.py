from pathlib import Path

p=Path('includes/class-wca-frontend.php'); s=p.read_text()
old="""\tprivate static function money( $minor, $currency ) {\n\t\treturn strtoupper( sanitize_text_field( $currency ) ) . ' ' . number_format_i18n( absint( $minor ) / 100, 2 );\n\t}"""
new="""\tprivate static function currency_fraction_digits( $currency ) {\n\t\t$currency = strtoupper( sanitize_text_field( $currency ) );\n\t\t$zero = array( 'BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF' );\n\t\t$three = array( 'BHD','IQD','JOD','KWD','LYD','OMR','TND' );\n\t\t$four = array( 'CLF','UYW' );\n\t\tif ( in_array( $currency, $zero, true ) ) { return 0; }\n\t\tif ( in_array( $currency, $three, true ) ) { return 3; }\n\t\tif ( in_array( $currency, $four, true ) ) { return 4; }\n\t\treturn 2;\n\t}\n\n\tprivate static function money( $minor, $currency ) {\n\t\t$currency = strtoupper( sanitize_text_field( $currency ) );\n\t\t$digits = self::currency_fraction_digits( $currency );\n\t\t$divisor = 10 ** $digits;\n\t\treturn $currency . ' ' . number_format_i18n( absint( $minor ) / $divisor, $digits );\n\t}"""
if s.count(old)!=1: raise SystemExit('R4 money anchor not unique')
p.write_text(s.replace(old,new,1))

Path('tests/t18-r4-currency-display-regressions.php').write_text(r'''<?php
$root=dirname(__DIR__); $src=file_get_contents($root.'/includes/class-wca-frontend.php');
$checks=array(
 'currency fraction helper exists'=>strpos($src,'private static function currency_fraction_digits')!==false,
 'zero decimal currencies covered'=>strpos($src,"'JPY'")!==false && strpos($src,"'KRW'")!==false && strpos($src,"'CLP'")!==false,
 'three decimal currencies covered'=>strpos($src,"'KWD'")!==false && strpos($src,"'BHD'")!==false && strpos($src,"'OMR'")!==false,
 'four decimal currencies covered'=>strpos($src,"'CLF'")!==false && strpos($src,"'UYW'")!==false,
 'hard-coded /100 removed'=>strpos($src,'absint( $minor ) / 100')===false,
 'money divisor uses currency digits'=>strpos($src,'$divisor = 10 ** $digits;')!==false && strpos($src,'number_format_i18n( absint( $minor ) / $divisor, $digits )')!==false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R4 FAIL: {$n}\n");exit(1);}} echo "T18 R4 currency display regressions: PASS\n";
''')
ra=Path('tests/run-all.php'); r=ra.read_text(); needle="'t18-r3-booking-timezone-regressions.php',"
if needle in r and 't18-r4-currency-display-regressions.php' not in r: ra.write_text(r.replace(needle,needle+"\n    't18-r4-currency-display-regressions.php',",1))
print('T18 R4 frozen ledger correction applied')