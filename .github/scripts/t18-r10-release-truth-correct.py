from pathlib import Path

root = Path(__file__).resolve().parents[2]
readme = root / 'readme.txt'
s = readme.read_text()
old = "* Runtime 1.2.15; core schema remains 3.2.0, continuity 1.1.0, Future24 1.0.0. All 20 fifteenth-cycle main reviews are complete: R19 found no new supported defect; R20 found no new product-code defect but corrected closure-document/PR evidence lag. Two extra post-correction verification sweeps plus exact-final-head package/CI evidence remain before repository closure; staging/live acceptance remains separate."
new = "* Runtime 1.2.15; current core schema is 3.4.0, continuity schema 1.1.0, and Future24 schema/contract 1.1.0. The later T17 and T18 repository corrections supersede the older fifteenth-cycle schema/evidence snapshot. Current exact-head package/CI evidence must identify the same corrected commit; staging/live acceptance remains a separate gate and is not claimed by repository evidence."
if old not in s:
    raise SystemExit('R10 readme current-release needle missing')
s = s.replace(old, new, 1)
readme.write_text(s)

status = root / 'STATUS.md'
t = status.read_text()
old_header = """## Current repository candidate

- Branch: `codex/file08-new-governing-plans-completion-2026`
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.2.0**
- Restricted continuity schema: **1.1.0**
- Future24 additive operational schema: **1.0.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Future24 amendment contract: **1.0.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**
"""
new_header = """## Current repository candidate

- Working review branch: `review/file08-t18-ten-round-2026-09-06`
- Current corrected source lineage: **T18 sequential review/correction cycle**
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.4.0**
- Restricted continuity schema: **1.1.0**
- Future24 additive operational schema: **1.1.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Future24 amendment contract: **1.1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**
"""
if old_header not in t:
    raise SystemExit('R10 STATUS current header needle missing')
t = t.replace(old_header, new_header, 1)
old_table = """| Coded | **Corrected T16 candidate** — all sixteenth-cycle R1-R20 sequential reviews are complete; R20 found closure/release-evidence defects but no new functional PHP/JavaScript defect. |
| Fresh post-final-code reviews | **PASS** — R20 correction verification and the required fresh read-only sweeps completed before exact-final-head canonical CI/package closure. |
| Packaged | **PASS** — deterministic candidate package verified on exact HEAD `56a1ee4a59722e6574c4722e2c6bda791b15be39`; ZIP SHA-256 `bcbc4fa1279fb2ee2e555c8972b36776039ce9e84110dab40eccb1a851d80c09`. |
| Automated-QA Green | **PASS** — canonical exact-head run `31741321738` succeeded for PHP 7.4/8.3, source contracts, JavaScript, repository hygiene and reproducible package verification. |
"""
new_table = """| Coded | **T18 correction in progress** — R1-R9 are corrected/Green; R10 release-truth correction must complete exact-head regression before this cycle can close. |
| Fresh post-final-code reviews | **Pending for current T18 final corrected HEAD.** Older T16/T17 closure reviews remain historical evidence only. |
| Packaged | **Pending for current T18 final corrected HEAD.** No older candidate artifact is current-release evidence. |
| Automated-QA Green | **Pending for current T18 final corrected HEAD.** Older successful workflow runs remain historical evidence only. |
"""
if old_table not in t:
    raise SystemExit('R10 STATUS evidence-table needle missing')
t = t.replace(old_table, new_table, 1)
status.write_text(t)

test = root / 'tests/t18-r10-release-truth-regressions.php'
test.write_text(r'''<?php
$root=dirname(__DIR__);
$readme=file_get_contents($root.'/readme.txt');
$status=file_get_contents($root.'/STATUS.md');
$contracts=file_get_contents($root.'/includes/class-wca-contracts.php');
$future=file_get_contents($root.'/includes/class-wca-future24.php');
$checks=array(
 'runtime contract remains 1.2.15'=>strpos($contracts,"RUNTIME_VERSION                 = '1.2.15'")!==false,
 'core runtime schema is 3.4.0'=>strpos($contracts,"SCHEMA_VERSION                  = '3.4.0'")!==false,
 'future runtime schema is 1.1.0'=>strpos($future,"SCHEMA_VERSION   = '1.1.0'")!==false,
 'readme current release records core 3.4.0'=>strpos($readme,'current core schema is 3.4.0')!==false,
 'readme current release records Future24 1.1.0'=>strpos($readme,'Future24 schema/contract 1.1.0')!==false,
 'status current branch is T18 review branch'=>strpos($status,'Working review branch: `review/file08-t18-ten-round-2026-09-06`')!==false,
 'status current core schema is 3.4.0'=>strpos($status,'Core File 08 schema: **3.4.0**')!==false,
 'status current Future24 schema is 1.1.0'=>strpos($status,'Future24 additive operational schema: **1.1.0**')!==false,
 'status current Future24 contract is 1.1.0'=>strpos($status,'Future24 amendment contract: **1.1.0**')!==false,
 'current evidence does not claim old exact-head package as current'=>strpos(substr($status,0,strpos($status,'## Mandatory staging / production gates')),'56a1ee4a59722e6574c4722e2c6bda791b15be39')===false,
 'current evidence does not claim old CI as current'=>strpos(substr($status,0,strpos($status,'## Mandatory staging / production gates')),'31741321738')===false,
 'current package evidence is pending'=>strpos($status,'| Packaged | **Pending for current T18 final corrected HEAD.**')!==false,
 'current automated QA evidence is pending'=>strpos($status,'| Automated-QA Green | **Pending for current T18 final corrected HEAD.**')!==false,
);
foreach($checks as $name=>$ok){if(!$ok){fwrite(STDERR,"T18 R10 FAIL: {$name}\n");exit(1);}}
echo "T18 R10 release-truth regressions: PASS\n";
''')

run = root / 'tests/run-all.php'
r = run.read_text()
needle = "'t18-r9-privacy-subject-boundary-regressions.php' );"
if needle not in r:
    raise SystemExit('R10 run-all insertion needle missing')
if "'t18-r10-release-truth-regressions.php'" not in r:
    r = r.replace(needle, "'t18-r9-privacy-subject-boundary-regressions.php', 't18-r10-release-truth-regressions.php' );", 1)
run.write_text(r)
