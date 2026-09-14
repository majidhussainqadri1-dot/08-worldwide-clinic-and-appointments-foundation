<?php
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
