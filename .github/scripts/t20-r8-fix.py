from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
def read(p): return (ROOT/p).read_text()
def write(p,t): (ROOT/p).write_text(t)
def replace_once(p,old,new):
    t=read(p)
    if t.count(old)!=1: raise SystemExit(f'{p}: patch anchor count {t.count(old)}')
    write(p,t.replace(old,new,1))

replace_once('includes/class-wca-repository.php',
"""\t\tif ( ! $row['appointment_id'] || ! preg_match( '/^[A-Z]{3}$/', $row['currency'] ) ) { return new WP_Error( 'wca_payment_required', __( 'Valid appointment and ISO-style three-letter currency are required.', 'worldwide-clinic-appointments' ) ); }\n""",
"""\t\tif ( ! $row['appointment_id'] || ! WCA_Service::valid_currency( $row['currency'] ) ) { return new WP_Error( 'wca_payment_required', __( 'Valid appointment and a currently supported ISO currency are required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n""")

test=ROOT/'tests/t20-r8-payment-currency-persistence-regressions.php'
test.write_text(r'''<?php
$root=dirname(__DIR__);
$repo=file_get_contents($root.'/includes/class-wca-repository.php');
$svc=file_get_contents($root.'/includes/class-wca-service.php');
$f=array();$n=0;function t20r8($name,$ok){global $f,$n;$n++;if(!$ok)$f[]=$name;}
t20r8('sources readable',is_string($repo)&&is_string($svc));
t20r8('payment persistence uses canonical currency allowlist',false!==strpos($repo,"WCA_Service::valid_currency( $row['currency'] )"));
t20r8('payment persistence no longer trusts regex-only currency',false===strpos($repo,"preg_match( '/^[A-Z]{3}$/', $row['currency'] )"));
t20r8('service persistence uses same allowlist',false!==strpos($repo,'wca_repository_service_currency')&&substr_count($repo,'WCA_Service::valid_currency')>=3);
t20r8('booked payment snapshot uses canonical currency validator',false!==strpos($svc,'if ( ! self::valid_currency( $currency ) || null === $amount'));
t20r8('platform commission remains zero at payment persistence',false!==strpos($repo,"'platform_commission_minor'  => 0")||false!==strpos($repo,"'platform_commission_minor' => 0"));
if($f){fwrite(STDERR,"T20 R8 finance regressions failed:\n- ".implode("\n- ",$f)."\n");exit(1);}echo "T20 R8 finance regressions: PASS {$n}/{$n}.\n";
''')

run='tests/run-all.php';t=read(run);old="'t20-r7-availability-projection-regressions.php' );";new="'t20-r7-availability-projection-regressions.php', 't20-r8-payment-currency-persistence-regressions.php' );";
if t.count(old)!=1: raise SystemExit('run-all R8 anchor mismatch')
write(run,t.replace(old,new,1))
