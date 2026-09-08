<?php
$root=dirname(__DIR__);
$repo=file_get_contents($root.'/includes/class-wca-repository.php');
$svc=file_get_contents($root.'/includes/class-wca-service.php');
$f=array();$n=0;function t20r8($name,$ok){global $f,$n;$n++;if(!$ok)$f[]=$name;}
t20r8('sources readable',is_string($repo)&&is_string($svc));
t20r8('payment persistence uses canonical currency allowlist',false!==strpos($repo, "WCA_Service::valid_currency( \$row['currency'] )"));
t20r8('payment persistence no longer trusts regex-only currency',false===strpos($repo, "preg_match( '/^[A-Z]{3}$/', \$row['currency'] )"));
t20r8('service persistence uses same allowlist',false!==strpos($repo,'wca_repository_service_currency')&&substr_count($repo,'WCA_Service::valid_currency')>=3);
t20r8('booked payment snapshot uses canonical currency validator',false!==strpos($svc,'if ( ! self::valid_currency( $currency ) || null === $amount'));
t20r8('platform commission remains zero at payment persistence',false!==strpos($repo,"'platform_commission_minor'  => 0")||false!==strpos($repo,"'platform_commission_minor' => 0"));
if($f){fwrite(STDERR,"T20 R8 finance regressions failed:\n- ".implode("\n- ",$f)."\n");exit(1);}echo "T20 R8 finance regressions: PASS {$n}/{$n}.\n";
