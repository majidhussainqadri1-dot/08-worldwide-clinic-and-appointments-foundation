<?php
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
