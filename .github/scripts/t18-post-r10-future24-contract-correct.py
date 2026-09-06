from pathlib import Path

root = Path(__file__).resolve().parents[2]
contracts = root / 'includes/class-wca-contracts.php'
s = contracts.read_text()
old = "\tconst FUTURE24_CONTRACT_VERSION       = '1.0.0';"
new = "\tconst FUTURE24_CONTRACT_VERSION       = '1.1.0';"
if old not in s:
    raise SystemExit('post-R10 Future24 contract needle missing')
s = s.replace(old, new, 1)
contracts.write_text(s)

test = root / 'tests/t18-post-r10-future24-contract-regressions.php'
test.write_text(r'''<?php
$root=dirname(__DIR__);
$c=file_get_contents($root.'/includes/class-wca-contracts.php');
$f=file_get_contents($root.'/includes/class-wca-future24.php');
$s=file_get_contents($root.'/STATUS.md');
$checks=array(
 'canonical contract registry exposes Future24 1.1.0'=>strpos($c,"FUTURE24_CONTRACT_VERSION       = '1.1.0'")!==false,
 'Future24 implementation contract is 1.1.0'=>strpos($f,"CONTRACT_VERSION = '1.1.0'")!==false,
 'Future24 implementation schema is 1.1.0'=>strpos($f,"SCHEMA_VERSION   = '1.1.0'")!==false,
 'current status records Future24 contract 1.1.0'=>strpos($s,'Future24 amendment contract: **1.1.0**')!==false,
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"POST-R10 CONTRACT FAIL: {$name}\n");exit(1);}}
echo "Post-R10 Future24 contract-version regressions: PASS\n";
''')

run = root / 'tests/run-all.php'
r = run.read_text()
needle = "'t18-r10-release-truth-regressions.php' );"
if needle not in r:
    raise SystemExit('post-R10 run-all insertion needle missing')
if "'t18-post-r10-future24-contract-regressions.php'" not in r:
    r = r.replace(needle, "'t18-r10-release-truth-regressions.php', 't18-post-r10-future24-contract-regressions.php' );", 1)
run.write_text(r)
