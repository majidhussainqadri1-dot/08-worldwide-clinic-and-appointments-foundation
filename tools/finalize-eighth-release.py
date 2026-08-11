from pathlib import Path


def rep(path, old, new, count=-1, required=True):
    p = Path(path)
    s = p.read_text()
    if old not in s:
        if required:
            raise SystemExit(f"missing expected pattern in {path}: {old[:90]}")
        return
    p.write_text(s.replace(old, new, count))


rep('worldwide-clinic.php', 'Version: 1.2.7', 'Version: 1.2.8', 1)
rep('worldwide-clinic.php', "define( 'WCA_VERSION', '1.2.7' );", "define( 'WCA_VERSION', '1.2.8' );", 1)
rep('includes/class-wca-contracts.php', "const RUNTIME_VERSION                 = '1.2.7';", "const RUNTIME_VERSION                 = '1.2.8';", 1)

replacements = {
    'Version: 1.2.7': 'Version: 1.2.8',
    "define( 'WCA_VERSION', '1.2.7' )": "define( 'WCA_VERSION', '1.2.8' )",
    "WCA_VERSION', '1.2.7'": "WCA_VERSION', '1.2.8'",
    "RUNTIME_VERSION                 = '1.2.7'": "RUNTIME_VERSION                 = '1.2.8'",
    'Stable tag: 1.2.7': 'Stable tag: 1.2.8',
    'Runtime candidate: **1.2.7**': 'Runtime candidate: **1.2.8**',
    "'1.2.7' === WCA_Contracts::RUNTIME_VERSION": "'1.2.8' === WCA_Contracts::RUNTIME_VERSION",
    'runtime contract is 1.2.7': 'runtime contract is 1.2.8',
    'plugin version is 1.2.7': 'plugin version is 1.2.8',
    'plugin 1.2.7': 'plugin 1.2.8',
    'runtime 1.2.7': 'runtime 1.2.8',
    'readme stable 1.2.7': 'readme stable 1.2.8',
    'repository readme 1.2.7': 'repository readme 1.2.8',
    'status 1.2.7': 'status 1.2.8',
    'changelog 1.2.7': 'changelog 1.2.8',
}
for p in Path('tests').glob('*.php'):
    s = p.read_text()
    for old, new in replacements.items():
        s = s.replace(old, new)
    p.write_text(s)

p = Path('README.md')
s = p.read_text().replace('Runtime candidate: **1.2.7**', 'Runtime candidate: **1.2.8**', 1)
if 'The **eighth fresh 10-round corrective audit**' not in s:
    marker = 'The **seventh fresh 10-round corrective audit** closes privacy-lifecycle starvation, silent input truncation, evidence truncation, and high-volume analytics undercounting:'
    pos = s.find(marker)
    if pos < 0:
        raise SystemExit('README seventh audit marker missing')
    end = s.find('\n', pos)
    eighth = "\n\nThe **eighth fresh 10-round corrective audit** closes additional high-volume, concurrency and exact-slot correctness gaps: doctor-suspension and verification-reconciliation scans no longer have artificial total ceilings; due follow-up reminders traverse the complete due set transactionally; canonical Future24 operational writes reject oversized payloads instead of silently truncating them; first-time practitioner opaque references are serialized; slot discovery avoids multi-rule/timezone starvation; hold revalidation projects the exact rule/slot while preserving true idempotent replay; and waitlist, flexible-window and support-participant semantic duplicates are serialized, with support-participant File17 projection committed atomically.\n"
    s = s[:end] + eighth + s[end:]
p.write_text(s)

p = Path('STATUS.md')
s = p.read_text().replace('Runtime candidate: **1.2.7**', 'Runtime candidate: **1.2.8**', 1)
a = s.find('## Seventh fresh 10-round corrective audit')
b = s.find('## Evidence-state classification')
if a < 0 or b < 0 or b <= a:
    raise SystemExit('STATUS audit anchors missing')
section = """## Eighth fresh 10-round corrective audit

An eighth fresh sequential review-and-correct cycle was run against the corrected v1.2.7 repository state. Every proved defect was corrected and the complete source QA suite passed before the next review proceeded. The cycle closes complete doctor-suspension and verification-reconciliation traversal; complete transactionally claimed due follow-up reminders; explicit failure for oversized canonical Future24 operational payloads; serialized practitioner opaque-reference creation; slot discovery without multi-rule/timezone starvation; exact held-slot reprojection with canonical service duration and true namespaced replay; semantic de-duplication for waitlist and flexible windows; and atomic de-duplicated support/interpreter participant creation with File17 projection. Runtime is 1.2.8 while core schema remains 3.2.0, continuity schema 1.1.0 and Future24 schema 1.0.0. Repository evidence does not prove Hostinger staging or live state.

"""
s = s[:a] + section + s[b:]
s = s.replace('seventh-cycle source corrections are present', 'eighth-cycle source corrections are present')
s = s.replace('No older v1.2.1/v1.2.2/v1.2.3/v1.2.4/v1.2.5/v1.2.6 artifact or older CI run may be used as evidence for the v1.2.7 candidate.', 'No older v1.2.1/v1.2.2/v1.2.3/v1.2.4/v1.2.5/v1.2.6/v1.2.7 artifact or older CI run may be used as evidence for the v1.2.8 candidate.')
p.write_text(s)

p = Path('readme.txt')
s = p.read_text()
s = s.replace('Stable tag: 1.2.7', 'Stable tag: 1.2.8', 1)
s = s.replace('Version 1.2.7 implements', 'Version 1.2.8 implements', 1)
s = s.replace('File 08 v1.2.7 candidate whose manifest', 'File 08 v1.2.8 candidate whose manifest', 1)
if 'The eighth fresh corrective audit' not in s:
    idx = s.find('== Changelog ==')
    if idx < 0:
        raise SystemExit('readme changelog marker missing')
    summary = "\nThe eighth fresh corrective audit removes residual total-page ceilings from doctor-suspension and verification reconciliation, makes due follow-up reminders complete and transactionally claimed, rejects oversized canonical Future24 operational payloads, serializes first-time practitioner opaque-reference creation, prevents multi-rule/timezone slot starvation, revalidates exact held slots while preserving true replay, serializes waitlist/flexible-window semantic creation, and makes support/interpreter participant creation both de-duplicated and atomic with its File17 projection.\n\n"
    s = s[:idx] + summary + s[idx:]
if '= 1.2.8 =' not in s:
    s = s.replace('== Changelog ==\n', "== Changelog ==\n\n= 1.2.8 =\n* Eighth fresh sequential 10-round corrective review completed; all ten supported findings corrected before the next round.\n* High-volume traversal, reminder transactionality, payload overflow, opaque-ref creation, slot projection/replay, waitlist/window semantic races, and support-participant atomicity hardened.\n* Runtime 1.2.8; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0.\n* Repository/CI/package evidence remains distinct from staging/live evidence.\n", 1)
p.write_text(s)

p = Path('CHANGELOG.md')
s = p.read_text()
if '## 1.2.8 — 2026-08-11' not in s:
    entry = """## 1.2.8 — 2026-08-11

- Completed the eighth fresh sequential 10-round corrective cycle.
- Removed artificial total-page ceilings from doctor-suspension and verification reconciliation.
- Made due follow-up reminders complete, transactionally claimed and failure-aware.
- Replaced silent Future24 operational payload truncation with explicit overflow errors.
- Serialized practitioner-reference, waitlist, flexible-window and support-participant semantic creation.
- Corrected multi-rule/timezone slot starvation and exact held-slot replay/reprojection.
- Support/interpreter participant creation is atomic with File17 projection.
- Runtime 1.2.8; core schema 3.2.0; continuity schema 1.1.0; Future24 schema 1.0.0.
- Added a permanent eighth-ten-review regression gate; staging/live evidence remains separate.

"""
    s = s.replace('# Changelog\n\n', '# Changelog\n\n' + entry, 1)
p.write_text(s)

rep('STAGING-ACCEPTANCE.md', 'File 08 core schema `3.1.0`', 'File 08 core schema `3.2.0`', 1, False)

gate = r'''<?php
/** File 08 eighth fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ ); $failures=array(); $checks=0;
function t810src($p){global $root,$failures;$f=$root.'/'.$p;if(!is_file($f)){$failures[]='Missing '.$p;return '';} $s=file_get_contents($f);return is_string($s)?$s:'';}
function t810has($l,$s,$n){global $failures,$checks;$checks++;if(false===strpos($s,$n)){$failures[]=$l.' missing: '.$n;}}
function t810lacks($l,$s,$n){global $failures,$checks;$checks++;if(false!==strpos($s,$n)){$failures[]=$l.' forbidden: '.$n;}}
$bootstrap=t810src('worldwide-clinic.php'); $contracts=t810src('includes/class-wca-contracts.php'); $service=t810src('includes/class-wca-service.php'); $guard=t810src('includes/class-wca-plan-guard.php'); $continuity=t810src('includes/class-wca-continuity-secure.php'); $future=t810src('includes/class-wca-future24.php'); $reconcile=t810src('includes/class-wca-verification-reconciliation.php'); $staging=t810src('STAGING-ACCEPTANCE.md');
t810lacks('doctor suspension has no artificial 500-page ceiling',$service,'&& $page <= 500');
t810has('doctor suspension complete terminal page',$service,'while ( 200 === count( $appointments ) )');
t810lacks('verification reconciliation has no artificial 100-page ceiling',$reconcile,'&& $page <= 100');
t810has('continuity reminder keyset cursor',$continuity,'AND id>%d'); t810has('continuity reminder transaction',$continuity,'START TRANSACTION'); t810has('continuity reminder row lock',$continuity,'FOR UPDATE'); t810has('continuity reminder rollback',$continuity,'ROLLBACK'); t810has('continuity reminder CAS',$continuity,'version=version+1');
t810has('canonical payload key overflow',$future,'wca_future24_payload_keys'); t810has('canonical payload item overflow',$future,'wca_future24_payload_items'); t810has('strict operational payload writer',$future,'sanitize_operational_payload( $payload, $strict = false )');
t810has('practitioner ref advisory lock',$guard,'SELECT GET_LOCK(%s,3)'); t810has('practitioner ref lock release',$guard,'SELECT RELEASE_LOCK(%s)');
t810has('slot display date lower bound',$service,'display_date_from'); t810has('slot display date upper bound',$service,'display_date_to'); t810has('exact held slot projector',$service,'project_rule_slot'); t810has('hold uses exact rule projector',$guard,'WCA_Service::project_rule_slot'); t810has('hold replay repository key',$guard,'$repository_key');
t810has('waitlist semantic lock',$future,"semantic_lock( 'waitlist'"); t810has('window semantic lock',$future,"semantic_lock( 'windows'"); t810has('support participant semantic lock',$future,"semantic_lock('support-participant'"); t810has('support participant transaction',$future,'START TRANSACTION'); t810has('support participant File17 projection',$future,'File17.AppointmentParticipantChanged.v1'); t810has('support participant commit',$future,'COMMIT');
t810has('plugin 1.2.8',$bootstrap,'Version: 1.2.8'); t810has('runtime 1.2.8',$contracts,"RUNTIME_VERSION                 = '1.2.8'"); t810has('core schema 3.2.0',$contracts,"SCHEMA_VERSION                  = '3.2.0'"); t810has('staging schema parity',$staging,'File 08 core schema `3.2.0`'); t810has('zero commission',$contracts,"'commission_percent' => 0"); t810has('no automated diagnosis',$contracts,"'automated_diagnosis' => false"); t810has('no automated prescribing',$contracts,"'automated_prescribing' => false");
$runtime=implode("\n",array($bootstrap,$contracts,$service,$guard,$continuity,$future,$reconcile)); foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $t){t810lacks('forbidden runtime primitive',$runtime,$t);} if($failures){fwrite(STDERR,"File 08 eighth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n");exit(1);} echo 'File 08 eighth fresh ten-round regression assertions passed: '.$checks.'/'.$checks.".\n";
'''
Path('tests/eighth-ten-review-regressions.php').write_text(gate)

p = Path('tests/run-all.php')
s = p.read_text()
if "'eighth-ten-review-regressions.php'" not in s:
    anchor = "'seventh-ten-review-regressions.php' );"
    if anchor not in s:
        raise SystemExit('run-all seventh gate anchor missing')
    s = s.replace(anchor, "'seventh-ten-review-regressions.php', 'eighth-ten-review-regressions.php' );", 1)
p.write_text(s)

# This payload was only for the serialized source review and must not survive the release tree.
Path('tools/eighth-review-patcher.gz.b64').unlink(missing_ok=True)
