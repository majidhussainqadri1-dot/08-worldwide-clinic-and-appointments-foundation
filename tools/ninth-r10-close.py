from pathlib import Path


def replace_required(path, old, new, count=-1):
    p = Path(path)
    s = p.read_text()
    if old not in s:
        raise SystemExit(f"missing required pattern in {path}: {old}")
    p.write_text(s.replace(old, new, count))


replace_required('worldwide-clinic.php', 'Version: 1.2.8', 'Version: 1.2.9', 1)
replace_required('worldwide-clinic.php', "define( 'WCA_VERSION', '1.2.8' );", "define( 'WCA_VERSION', '1.2.9' );", 1)
replace_required('includes/class-wca-contracts.php', "const RUNTIME_VERSION                 = '1.2.8';", "const RUNTIME_VERSION                 = '1.2.9';", 1)

# All permanent source gates asserting the current candidate identity advance together.
for p in Path('tests').glob('*.php'):
    p.write_text(p.read_text().replace('1.2.8', '1.2.9'))

p = Path('README.md')
s = p.read_text().replace('Runtime candidate: **1.2.8**', 'Runtime candidate: **1.2.9**', 1)
if 'The **ninth fresh 10-round corrective audit**' not in s:
    marker = 'The **eighth fresh 10-round corrective audit**'
    pos = s.find(marker)
    if pos < 0:
        raise SystemExit('README eighth audit marker missing')
    end = s.find('\n', pos)
    ninth = "\nThe **ninth fresh 10-round corrective audit** closes residual browser-reference, authorization-scale and mutation-atomicity gaps: legacy browser booking now uses opaque practitioner references; mutation authorization no longer enumerates all doctors; locked appointment mutations roll back on fail-closed errors; appointment transitions require event, outbox, notification, communication, review-eligibility and audit persistence; Future24 record creation requires governance audit persistence; waitlist offer creation is serialized and atomic with File19 projection; participant revocation and virtual-room requests are atomic with File17 projection; and group-session cancellation is atomic across session/member state and governance audit.\n"
    s = s[:end] + ninth + s[end:]
p.write_text(s)

p = Path('STATUS.md')
s = p.read_text().replace('Runtime candidate: **1.2.8**', 'Runtime candidate: **1.2.9**', 1)
a = s.find('## Eighth fresh 10-round corrective audit')
b = s.find('## Evidence-state classification')
if a < 0 or b < 0 or b <= a:
    raise SystemExit('STATUS eighth section anchors missing')
section = """## Ninth fresh 10-round corrective audit

A ninth fresh sequential review-and-correct cycle was run against the exact v1.2.8 corrected repository state. Each supported defect was corrected and the complete source QA suite passed before the next round began. The cycle closes opaque browser practitioner-reference leakage, O(N) doctor enumeration in mutation authorization, fail-open locked mutation audit behavior, transition event/outbox/review-eligibility partial commits, Future24 governance-audit fail-open record creation, waitlist-offer duplicate/projection races, support-participant revocation projection gaps, virtual-room projection gaps, and non-atomic group cancellation. Runtime is 1.2.9 while core schema remains 3.2.0, continuity schema 1.1.0 and Future24 schema 1.0.0. Repository evidence does not prove Hostinger staging or live state.

"""
s = s[:a] + section + s[b:]
s = s.replace('eighth-cycle source corrections are present', 'ninth-cycle source corrections are present')
s = s.replace('No older v1.2.1/v1.2.2/v1.2.3/v1.2.4/v1.2.5/v1.2.6/v1.2.7 artifact or older CI run may be used as evidence for the v1.2.8 candidate.', 'No older v1.2.1/v1.2.2/v1.2.3/v1.2.4/v1.2.5/v1.2.6/v1.2.7/v1.2.8 artifact or older CI run may be used as evidence for the v1.2.9 candidate.')
p.write_text(s)

p = Path('readme.txt')
s = p.read_text()
s = s.replace('Stable tag: 1.2.8', 'Stable tag: 1.2.9', 1)
s = s.replace('Version 1.2.8 implements', 'Version 1.2.9 implements', 1)
s = s.replace('File 08 v1.2.8 candidate whose manifest', 'File 08 v1.2.9 candidate whose manifest', 1)
if '= 1.2.9 =' not in s:
    marker = '== Changelog ==\n'
    entry = """
= 1.2.9 =
* Completed the ninth fresh sequential 10-round corrective audit.
* Browser practitioner references are opaque and mutation authorization avoids global doctor enumeration.
* Locked appointment mutations and canonical transition side effects now fail closed transactionally.
* Future24 audit-backed records, waitlist offers, participant revocation, virtual-room requests and group cancellation are atomic with required projections/audit state.
* Runtime 1.2.9; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.
"""
    if marker not in s:
        raise SystemExit('readme changelog marker missing')
    s = s.replace(marker, marker + entry, 1)
p.write_text(s)

p = Path('CHANGELOG.md')
s = p.read_text()
if '## 1.2.9 — 2026-08-11' not in s:
    entry = """## 1.2.9 — 2026-08-11

- Completed the ninth fresh sequential 10-round corrective cycle against exact v1.2.8 repository state.
- Replaced browser-visible practitioner numeric references with opaque references and removed global doctor enumeration from mutation authorization.
- Made locked appointment mutations transactional and transition events/outbox/review eligibility/audit fail closed.
- Made Future24 governance-audited record creation fail closed and corrected waitlist offer, support participant revocation, virtual-room and group-cancellation atomicity.
- Runtime 1.2.9; core schema remains 3.2.0; continuity schema 1.1.0; Future24 schema 1.0.0.
- Added a permanent ninth-ten-review regression gate; staging/live evidence remains separate.

"""
    s = s.replace('# Changelog\n\n', '# Changelog\n\n' + entry, 1)
p.write_text(s)

gate = r'''<?php
/** File 08 ninth fresh ten-round corrective regression gate. */
$root=dirname(__DIR__); $failures=array(); $checks=0;
function n9src($p){global $root,$failures;$f=$root.'/'.$p;if(!is_file($f)){$failures[]='Missing '.$p;return '';} $s=file_get_contents($f);return is_string($s)?$s:'';}
function n9has($l,$s,$n){global $failures,$checks;$checks++;if(false===strpos($s,$n)){$failures[]=$l.' missing: '.$n;}}
function n9lacks($l,$s,$n){global $failures,$checks;$checks++;if(false!==strpos($s,$n)){$failures[]=$l.' forbidden: '.$n;}}
$boot=n9src('worldwide-clinic.php'); $contracts=n9src('includes/class-wca-contracts.php'); $helpers=n9src('includes/class-swc-helpers.php'); $front=n9src('includes/class-swc-frontend.php'); $appointments=n9src('includes/class-swc-appointments.php'); $admin=n9src('includes/class-swc-admin.php'); $service=n9src('includes/class-wca-service.php'); $future=n9src('includes/class-wca-future24.php');
n9has('browser uses opaque doctor field',$front,'name="doctor_ref"'); n9lacks('browser hides numeric doctor field',$front,'name="doctor_id" required'); n9has('opaque doctor resolver',$appointments,'SWC_Helpers::practitioner_id( $doctor_ref )');
n9has('submit direct eligibility',$appointments,'SWC_Helpers::doctor_is_requestable( $doctor )'); n9has('reassignment direct eligibility',$appointments,'SWC_Helpers::doctor_is_requestable( $new_doctor )'); n9has('admin direct eligibility',$admin,'SWC_Helpers::doctor_is_requestable( $doctor )');
n9has('appointment DB transaction',$helpers,'with_database_transaction'); n9has('transaction rollback',$helpers,"query( 'ROLLBACK' )"); n9has('postmeta cache invalidation',$helpers,'wp_cache_delete(');
n9has('transition event checked',$service,'event_record = WCA_Repository::append_event'); n9has('transition outbox checked',$service,'outbox_event = WCA_Repository::enqueue'); n9has('transition notification checked',$service,'notification = WCA_Repository::enqueue'); n9has('transition communication checked',$service,'communication = WCA_Repository::enqueue'); n9has('completion eligibility fail closed',$service,'if ( is_wp_error( $eligibility ) ) { return $eligibility; }'); n9has('transition audit fail closed',$service,'wca_transition_audit');
n9has('future record id captured',$future,'record_id = (int) $wpdb->insert_id;'); n9has('future audit unavailable error',$future,'wca_future24_audit_unavailable'); n9has('future audit returns event result',$future,"return WCA_Repository::append_event( 'Future24GovernanceRecorded.v1'");
n9has('waitlist offer semantic lock',$future,"semantic_lock( 'waitlist-offer'"); n9has('waitlist enqueue checked',$future,'if ( is_wp_error( $queued ) )');
n9has('participant revoke semantic lock',$future,"semantic_lock( 'support-participant-revoke'"); n9has('participant revoke version CAS',$future,'AND version=%d');
n9has('virtual room semantic lock',$future,"semantic_lock('virtual-room',$id)"); n9has('virtual room enqueue checked',$future,'if(is_wp_error($queued))');
n9has('group cancellation member failure',$future,'wca_group_member_cancel_failed'); n9has('group cancellation audit checked',$future,'if ( is_wp_error( $audit ) )');
n9has('plugin 1.2.9',$boot,'Version: 1.2.9'); n9has('runtime 1.2.9',$contracts,"RUNTIME_VERSION                 = '1.2.9'"); n9has('schema remains 3.2.0',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
$runtime=implode("\n",array($boot,$contracts,$helpers,$front,$appointments,$admin,$service,$future)); foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $t){n9lacks('forbidden runtime primitive',$runtime,$t);} if($failures){fwrite(STDERR,"File 08 ninth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n");exit(1);} echo 'File 08 ninth fresh ten-round regression assertions passed: '.$checks.'/'.$checks.".\n";
'''
Path('tests/ninth-ten-review-regressions.php').write_text(gate)

p = Path('tests/run-all.php')
s = p.read_text()
if "'ninth-ten-review-regressions.php'" not in s:
    marker = "'eighth-ten-review-regressions.php' );"
    if marker not in s:
        raise SystemExit('run-all eighth gate anchor missing')
    s = s.replace(marker, "'eighth-ten-review-regressions.php', 'ninth-ten-review-regressions.php' );", 1)
p.write_text(s)

Path('tools/ninth-review-patches.tgz.b64').unlink(missing_ok=True)
