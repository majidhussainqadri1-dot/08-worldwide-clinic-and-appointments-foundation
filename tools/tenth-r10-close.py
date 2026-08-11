from pathlib import Path
def req(path, old, new, count=-1):
    p=Path(path); s=p.read_text()
    if old not in s: raise SystemExit(f'missing required anchor in {path}: {old}')
    p.write_text(s.replace(old,new,count))
req('worldwide-clinic.php','Version: 1.2.9','Version: 1.2.10',1)
req('worldwide-clinic.php',"define( 'WCA_VERSION', '1.2.9' );","define( 'WCA_VERSION', '1.2.10' );",1)
req('includes/class-wca-contracts.php',"const RUNTIME_VERSION                 = '1.2.9';","const RUNTIME_VERSION                 = '1.2.10';",1)
for p in Path('tests').glob('*.php'):
    p.write_text(p.read_text().replace('1.2.9','1.2.10'))
p=Path('README.md'); s=p.read_text().replace('Runtime candidate: **1.2.9**','Runtime candidate: **1.2.10**',1)
if 'The **tenth fresh 10-round corrective audit**' not in s:
    marker='The **ninth fresh 10-round corrective audit**'
    pos=s.find(marker)
    if pos<0: raise SystemExit('README ninth audit marker missing')
    end=s.find('\n',pos)
    paragraph="\nThe **tenth fresh 10-round corrective audit** closes residual owner-transaction, event/outbox, replay-finalization and public-query contract gaps: initial appointment requests now commit slot/consent/events/File17/File19/audit/replay evidence atomically; clinic lifecycle, branch, service, availability, complaint and payment mutations fail closed with required evidence/projections; branch/availability time-zone inputs fail closed; HTTP replay finalization exposes authoritative mutation-status reconciliation; and public clinic discovery uses opaque cursor pagination plus conditional ETag caching.\n"
    s=s[:end]+paragraph+s[end:]
p.write_text(s)
p=Path('STATUS.md'); s=p.read_text().replace('Runtime candidate: **1.2.9**','Runtime candidate: **1.2.10**',1)
if '## Tenth fresh 10-round corrective audit' not in s:
    marker='## Evidence-state classification'
    if marker not in s: raise SystemExit('STATUS evidence marker missing')
    section="""## Tenth fresh 10-round corrective audit

A tenth fresh sequential review-and-correct cycle was run against exact v1.2.9 repository state. R1-R9 corrected owner transaction/event/outbox atomicity across appointment creation, clinic lifecycle, branch, service, availability, complaint and payment flows; replay-finalization now fails closed with a caller-owned mutation-status query; public clinic discovery uses opaque cursor pagination and conditional ETag caching. R10 aligns runtime/tests/docs to v1.2.10 while schemas remain 3.2.0 / 1.1.0 / 1.0.0. Repository evidence remains distinct from staging/live evidence.

"""
    s=s.replace(marker,section+marker,1)
p.write_text(s)
p=Path('readme.txt'); s=p.read_text().replace('Stable tag: 1.2.9','Stable tag: 1.2.10',1)
s=s.replace('Version 1.2.9 implements','Version 1.2.10 implements',1)
s=s.replace('File 08 v1.2.9 candidate whose manifest','File 08 v1.2.10 candidate whose manifest',1)
if '= 1.2.10 =' not in s:
    marker='== Changelog ==\n'
    entry="""
= 1.2.10 =
* Completed the tenth fresh sequential 10-round corrective audit.
* Appointment request, clinic lifecycle, branch, service, availability, complaint and payment owner writes now fail closed with required event/outbox evidence.
* Branch and availability timezone/window inputs fail closed instead of silent normalization.
* HTTP mutation replay finalization has authoritative status reconciliation.
* Public clinic listing uses opaque cursor pagination and ETag conditional caching.
* Runtime 1.2.10; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.
"""
    if marker not in s: raise SystemExit('readme changelog marker missing')
    s=s.replace(marker,marker+entry,1)
p.write_text(s)
p=Path('CHANGELOG.md'); s=p.read_text()
if '## 1.2.10 — 2026-08-12' not in s:
    entry="""## 1.2.10 — 2026-08-12

- Completed the tenth fresh sequential 10-round corrective cycle against exact v1.2.9 repository state.
- Made appointment creation, clinic lifecycle, branch, service, availability, complaint and payment mutations atomic with their required event/outbox/replay evidence.
- Added fail-closed branch/availability timezone and availability-window validation.
- Added fail-closed HTTP idempotency finalization plus authoritative mutation-status reconciliation.
- Replaced public clinic offset paging with opaque keyset cursor state and conditional ETag caching.
- Runtime 1.2.10; core schema remains 3.2.0; continuity schema 1.1.0; Future24 schema 1.0.0.
- Staging/live evidence remains separate.

"""
    s=s.replace('# Changelog\n\n','# Changelog\n\n'+entry,1)
p.write_text(s)
p=Path('STAGING-ACCEPTANCE.md')
if p.exists(): p.write_text(p.read_text().replace('1.2.9','1.2.10'))
gate=r'''<?php
/** File 08 tenth fresh ten-round corrective regression gate. */
$root=dirname(__DIR__); $failures=array(); $checks=0;
function t10src($p){global $root,$failures;$f=$root.'/'.$p;if(!is_file($f)){$failures[]='Missing '.$p;return '';} $s=file_get_contents($f);return is_string($s)?$s:'';}
function t10has($l,$s,$n){global $failures,$checks;$checks++;if(false===strpos($s,$n)){$failures[]=$l.' missing: '.$n;}}
function t10lacks($l,$s,$n){global $failures,$checks;$checks++;if(false!==strpos($s,$n)){$failures[]=$l.' forbidden: '.$n;}}
$boot=t10src('worldwide-clinic.php'); $contracts=t10src('includes/class-wca-contracts.php'); $repo=t10src('includes/class-wca-repository.php'); $service=t10src('includes/class-wca-service.php'); $rest=t10src('includes/class-wca-rest.php'); $hard=t10src('includes/class-wca-ten-review-hardening.php');
foreach(array('wca_appointment_request_transaction','wca_clinic_create_transaction','wca_clinic_review_transaction','wca_clinic_activate_transaction','wca_branch_create_transaction','wca_service_mutation_transaction','wca_availability_mutation_transaction','wca_complaint_transaction','wca_payment_intent_transaction') as $t){t10has('owner transaction',$service,$t);}
foreach(array('AppointmentRequested.v1','ClinicActivated.v1','ClinicBranchChanged.v1','ClinicAvailabilityChanged.v1','AppointmentComplaintSubmitted.v1','CF03.PaymentIntentRequested.v1') as $t){t10has('required evidence',$service,$t);}
t10has('generic transaction helper',$repo,'public static function transaction'); t10has('rollback',$repo,"query( 'ROLLBACK' )");
t10has('idempotency status repository',$repo,'public static function idempotency_status'); t10has('mutation status route',$hard,"'/mutation-status'"); t10has('replay finalization fail closed',$hard,'wca_idempotency_finalize_failed'); t10has('reconciliation flag',$hard,"'reconciliation_required' => true");
t10has('branch timezone validation',$service,'wca_branch_timezone'); t10has('availability timezone validation',$service,'wca_availability_timezone'); t10has('availability window validation',$service,'wca_availability_window'); t10has('availability range validation',$service,'wca_availability_effective_range');
t10has('clinic cursor',$rest,"get_param( 'cursor' )"); t10has('opaque cursor state',$rest,"'wca_clinic_cursor_' . md5( $cursor )"); t10has('next cursor',$rest,"'next_cursor'"); t10has('conditional request',$rest,"get_header( 'If-None-Match' )"); t10has('etag header',$rest,"header( 'ETag'"); t10has('cursor keyset repository',$repo,'c.updated_at<%s OR (c.updated_at=%s AND c.id<%d)');
t10has('plugin 1.2.10',$boot,'Version: 1.2.10'); t10has('runtime 1.2.10',$contracts,"RUNTIME_VERSION                 = '1.2.10'"); t10has('schema stays 3.2.0',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
$runtime=implode("\n",array($boot,$contracts,$repo,$service,$rest,$hard)); foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $t){t10lacks('forbidden runtime primitive',$runtime,$t);} if($failures){fwrite(STDERR,"File 08 tenth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n");exit(1);} echo 'File 08 tenth fresh ten-round regression assertions passed: '.$checks.'/'.$checks.".\n";
'''
gate='\n'.join(line[10:] if line.startswith('          ') else line for line in gate.splitlines())+'\n'
Path('tests/tenth-ten-review-regressions.php').write_text(gate)
p=Path('tests/run-all.php'); s=p.read_text()
if "'tenth-ten-review-regressions.php'" not in s:
    s=s.replace("'ninth-ten-review-regressions.php' );","'ninth-ten-review-regressions.php', 'tenth-ten-review-regressions.php' );",1)
p.write_text(s)
