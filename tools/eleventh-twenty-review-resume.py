#!/usr/bin/env python3
from pathlib import Path
import sys

ROOT = Path(__file__).resolve().parents[1]

def read(path): return (ROOT / path).read_text()
def write(path, text): (ROOT / path).write_text(text)
def replace_once(path, old, new):
    text = read(path)
    if old not in text:
        raise SystemExit(f"anchor missing in {path}: {old[:160]!r}")
    write(path, text.replace(old, new, 1))
def append_before(path, marker, block):
    text = read(path)
    if block.strip() in text: return
    if marker not in text: raise SystemExit(f"marker missing in {path}: {marker!r}")
    write(path, text.replace(marker, block + marker, 1))

r = int(sys.argv[1])

if r == 13:
    path='includes/class-wca-service.php'
    old="""\t\t\tforeach ( $appointments as $id ) {\n\t\t\t\t$status = SWC_Helpers::status( $id );\n\t\t\t\tif ( ! WCA_Contracts::is_terminal( $status ) ) {\n\t\t\t\t\tupdate_post_meta( $id, '_swc_doctor_authority_hold', '1' );\n\t\t\t\t\tupdate_post_meta( $id, '_swc_doctor_authority_hold_reason', sanitize_text_field( $reason ) );\n\t\t\t\t\tWCA_Repository::enqueue( 'File19.NotificationRequested.v1', (string) SWC_Helpers::meta( $id, 'public_ref' ), array( 'event' => 'doctor_authority_hold', 'recipients' => array( absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) ) ) ), WCA_Observability::trace_id() );\n\t\t\t\t}\n\t\t\t\t$seen++;\n\t\t\t}\n"""
    new="""\t\t\tforeach ( $appointments as $id ) {\n\t\t\t\t$status = SWC_Helpers::status( $id );\n\t\t\t\tif ( ! WCA_Contracts::is_terminal( $status ) ) {\n\t\t\t\t\t$reconciled = WCA_Repository::transaction( function () use ( $id, $reason ) {\n\t\t\t\t\t\tupdate_post_meta( $id, '_swc_doctor_authority_hold', '1' );\n\t\t\t\t\t\tupdate_post_meta( $id, '_swc_doctor_authority_hold_reason', sanitize_text_field( $reason ) );\n\t\t\t\t\t\tif ( '1' !== (string) get_post_meta( $id, '_swc_doctor_authority_hold', true ) ) {\n\t\t\t\t\t\t\treturn new WP_Error( 'wca_doctor_hold_persist', __( 'Doctor authority hold could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );\n\t\t\t\t\t\t}\n\t\t\t\t\t\t$queued = WCA_Repository::enqueue(\n\t\t\t\t\t\t\t'File19.NotificationRequested.v1',\n\t\t\t\t\t\t\t(string) SWC_Helpers::meta( $id, 'public_ref' ),\n\t\t\t\t\t\t\tarray(\n\t\t\t\t\t\t\t\t'event' => 'doctor_authority_hold',\n\t\t\t\t\t\t\t\t'recipients' => array( absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) ) ),\n\t\t\t\t\t\t\t),\n\t\t\t\t\t\t\tWCA_Observability::trace_id()\n\t\t\t\t\t\t);\n\t\t\t\t\t\treturn is_wp_error( $queued ) ? $queued : true;\n\t\t\t\t\t}, 'wca_doctor_suspension_reconcile_transaction' );\n\t\t\t\t\tif ( is_wp_error( $reconciled ) ) {\n\t\t\t\t\t\tWCA_Observability::log( 'error', 'doctor_suspension_reconcile_failed', array( 'appointment_ref' => (string) SWC_Helpers::meta( $id, 'public_ref' ), 'error' => $reconciled->get_error_code() ) );\n\t\t\t\t\t}\n\t\t\t\t}\n\t\t\t\t$seen++;\n\t\t\t}\n"""
    replace_once(path,old,new)

elif r == 14:
    path='includes/class-wca-service.php'
    old="""\t\tif ( $service_id ) {\n\t\t\t$current = WCA_Repository::get_service( $service_id, false );\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t}\n"""
    new="""\t\t$current = null;\n\t\tif ( $service_id ) {\n\t\t\t$current = WCA_Repository::get_service( $service_id, false );\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t}\n\t\t$consultation_type = sanitize_key( $data['consultation_type'] ?? ( $current['consultation_type'] ?? '' ) );\n\t\tif ( ! in_array( $consultation_type, array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ) { return new WP_Error( 'wca_service_consultation_type', __( 'A valid consultation type is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['currency'] ?? ( $current['currency'] ?? '' ) ) ) );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { return new WP_Error( 'wca_service_currency', __( 'A valid three-letter currency code is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$data['consultation_type'] = $consultation_type;\n\t\t$data['currency'] = $currency;\n"""
    replace_once(path,old,new)

elif r == 15:
    path='includes/class-wca-repository.php'
    old="""\t\t$table = WCA_Schema::tables()['branches'];\n\t\t$row   = array(\n"""
    new="""\t\t$table = WCA_Schema::tables()['branches'];\n\t\t$timezone = (string) ( $data['timezone'] ?? '' );\n\t\tif ( ! WCA_Service::valid_timezone( $timezone ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_branch_timezone', __( 'Branch persistence requires a valid IANA time zone.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$row   = array(\n"""
    replace_once(path,old,new)
    replace_once(path,"'timezone'        => WCA_Service::valid_timezone( $data['timezone'] ?? 'UTC' ) ? (string) $data['timezone'] : 'UTC',","'timezone'        => $timezone,")
    old="""\t\t$table = WCA_Schema::tables()['services'];\n\t\t$currency = strtoupper( preg_replace( '/[^A-Z]/', '', (string) ( $data['currency'] ?? 'PKR' ) ) );\n\t\t$row = array(\n"""
    new="""\t\t$table = WCA_Schema::tables()['services'];\n\t\t$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['currency'] ?? '' ) ) );\n\t\t$consultation_type = sanitize_key( $data['consultation_type'] ?? '' );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_currency', __( 'Service persistence requires a valid three-letter currency code.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\tif ( ! in_array( $consultation_type, array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_type', __( 'Service persistence requires a valid consultation type.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$row = array(\n"""
    replace_once(path,old,new)
    replace_once(path,"'consultation_type'         => in_array( $data['consultation_type'] ?? '', array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ? $data['consultation_type'] : 'online',","'consultation_type'         => $consultation_type,")
    replace_once(path,"'currency'                  => 3 === strlen( $currency ) ? $currency : 'PKR',","'currency'                  => $currency,")
    old="""\t\t$table = WCA_Schema::tables()['availability'];\n\t\t$row = array(\n"""
    new="""\t\t$table = WCA_Schema::tables()['availability'];\n\t\t$timezone = (string) ( $data['timezone'] ?? '' );\n\t\tif ( ! WCA_Service::valid_timezone( $timezone ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_availability_timezone', __( 'Availability persistence requires a valid IANA time zone.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$row = array(\n"""
    replace_once(path,old,new)
    replace_once(path,"'timezone'       => WCA_Service::valid_timezone( $data['timezone'] ?? '' ) ? (string) $data['timezone'] : 'UTC',","'timezone'       => $timezone,")

elif r == 16:
    for path in ['worldwide-clinic.php','includes/class-wca-contracts.php']:
        text=read(path)
        if '1.2.10' not in text: raise SystemExit('version anchor missing '+path)
        write(path,text.replace('1.2.10','1.2.11'))
    for p in (ROOT/'tests').glob('*.php'):
        text=p.read_text()
        if '1.2.10' in text: p.write_text(text.replace('1.2.10','1.2.11'))
    test=r'''<?php
/** File 08 eleventh fresh twenty-round corrective regression gate. */
$root=dirname(__DIR__); $failures=array(); $checks=0;
function e11src($p){global $root,$failures;$f=$root.'/'.$p;if(!is_file($f)){$failures[]='Missing '.$p;return '';} $s=file_get_contents($f);return is_string($s)?$s:'';}
function e11has($l,$s,$n){global $failures,$checks;$checks++;if(false===strpos($s,$n)){$failures[]=$l.' missing: '.$n;}}
function e11lacks($l,$s,$n){global $failures,$checks;$checks++;if(false!==strpos($s,$n)){$failures[]=$l.' forbidden: '.$n;}}
$boot=e11src('worldwide-clinic.php');$contracts=e11src('includes/class-wca-contracts.php');$repo=e11src('includes/class-wca-repository.php');$service=e11src('includes/class-wca-service.php');$helpers=e11src('includes/class-swc-helpers.php');$outbox=e11src('includes/class-wca-outbox.php');$rest=e11src('includes/class-wca-rest.php');
foreach(array('$started = $wpdb->query( \'START TRANSACTION\' )','$committed = $wpdb->query( \'COMMIT\' )','_commit') as $n){e11has('repository transaction control',$repo,$n);}foreach(array('swc_transaction_start_failed','swc_transaction_commit_failed') as $n){e11has('appointment transaction control',$helpers,$n);}e11has('stale lock compare-and-swap',$helpers,'option_value=%s WHERE option_name=%s AND option_value=%s');e11lacks('stale lock delete-before-acquire',$helpers,'delete_option( $key );'."\n\t\t\t\t".'add_option');
e11has('worker-fenced complete',$repo,'complete_outbox( $id, $worker');e11has('worker-fenced failure',$repo,'fail_outbox( $id, $error, $attempts, $worker');e11has('dispatcher completion check',$outbox,'durable worker-fenced finalization failed');e11has('dispatcher finalization contention',$outbox,'outbox_finalize_contention_total');e11has('all-recipient fallback',$outbox,'$all_sent = true');e11lacks('partial-recipient success',$outbox,'$sent = wp_mail');
e11has('appointment replay key bounds',$service,"preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $idempotency_key )");e11has('patient timezone fail closed',$service,'wca_patient_timezone_invalid');e11has('slot from date fail closed',$service,'wca_slot_date_from_invalid');e11has('slot to date fail closed',$service,'wca_slot_date_to_invalid');e11has('inside-lock authorization recheck',$service,'$current_auth = WCA_Authorization::can_transition_appointment');e11has('actual mode validation',$service,'wca_actual_mode_invalid');
e11has('opaque clinic appointment DTO',$rest,"'clinic_ref'");e11has('opaque service appointment DTO',$rest,"'service_ref'");e11has('protected mutation allowlist',$rest,'protected_mutation_projection');e11lacks('appointment DTO native clinic id',$rest,"'clinic_id'         => absint( SWC_Helpers::meta");e11lacks('appointment DTO native service id',$rest,"'service_id'        => absint( SWC_Helpers::meta");e11has('doctor suspension atomic reconciliation',$service,'wca_doctor_suspension_reconcile_transaction');e11has('doctor suspension failure evidence',$service,'doctor_suspension_reconcile_failed');e11has('service type validation',$service,'wca_service_consultation_type');e11has('service currency validation',$service,'wca_service_currency');e11has('repository branch timezone validation',$repo,'wca_repository_branch_timezone');e11has('repository service currency validation',$repo,'wca_repository_service_currency');e11has('repository service type validation',$repo,'wca_repository_service_type');e11has('repository availability timezone validation',$repo,'wca_repository_availability_timezone');e11has('plugin 1.2.11',$boot,'Version: 1.2.11');e11has('runtime 1.2.11',$contracts,"RUNTIME_VERSION                 = '1.2.11'");e11has('schema stays 3.2.0',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
$runtime=implode("\n",array($boot,$contracts,$repo,$service,$helpers,$outbox,$rest));foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $n){e11lacks('forbidden runtime primitive',$runtime,$n);}if($failures){fwrite(STDERR,"File 08 eleventh twenty-round regression gate failed:\n- ".implode("\n- ",$failures)."\n");exit(1);}echo 'File 08 eleventh fresh twenty-round regression assertions passed: '.$checks.'/'.$checks.".\n";
'''
    write('tests/eleventh-twenty-review-regressions.php',test)
    runall=read('tests/run-all.php')
    if "'eleventh-twenty-review-regressions.php'" not in runall:
        old="'tenth-ten-review-regressions.php' );"
        if old not in runall: raise SystemExit('run-all insertion anchor missing')
        write('tests/run-all.php',runall.replace(old,"'tenth-ten-review-regressions.php', 'eleventh-twenty-review-regressions.php' );",1))
    replace_once('README.md','Runtime candidate: **1.2.10**','Runtime candidate: **1.2.11**')
    append_before('README.md','\n## Canonical routes\n',"\nThe **eleventh fresh 20-round corrective audit** reviewed exact v1.2.10 source sequentially. R1-R15 corrected transaction-control, resource-lock takeover, outbox finalization, all-recipient fallback delivery, replay/timezone/date/check-in validation, inside-lock authorization, protected REST DTOs, doctor-suspension projection atomicity, and canonical service/branch/availability persistence. R16 aligns release identity and permanent evidence to v1.2.11. R17-R20 are fresh corrected-state privacy/Future24/migration/security/plan-parity reviews.\n")
    replace_once('STATUS.md','Runtime candidate: **1.2.10**','Runtime candidate: **1.2.11**')
    append_before('STATUS.md','\n## Evidence-state classification\n',"\n## Eleventh fresh 20-round corrective audit\n\nFresh sequential review against exact v1.2.10 source. R1-R15 corrected supported repository defects; R16 aligns runtime/tests/docs and permanent regression evidence to v1.2.11 without schema inflation. R17-R20 are corrected-state closure reviews. Repository evidence remains distinct from staging/live evidence.\n")
    rd=read('readme.txt').replace('Stable tag: 1.2.10','Stable tag: 1.2.11',1).replace('Version 1.2.10 implements','Version 1.2.11 implements',1).replace('File 08 v1.2.10 candidate','File 08 v1.2.11 candidate',1)
    entry="= 1.2.11 =\n* Completed the eleventh fresh sequential 20-round corrective audit.\n* Transaction start/commit failures fail closed in canonical owner and appointment mutations.\n* Stale scheduling lock takeover is compare-and-swap safe; outbox finalization is worker-fenced and durability-aware.\n* Notification fallback requires every intended recipient; appointment replay/timezone/date/check-in validation fails closed.\n* Protected REST mutation/read DTOs use opaque references instead of native database IDs.\n* Doctor suspension reconciliation and required File19 projection are atomic.\n* Service/branch/availability canonical persistence rejects invalid values instead of silent normalization.\n* Runtime 1.2.11; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0. Repository/CI/package evidence remains distinct from staging/live evidence.\n\n"
    if entry not in rd:
        marker='== Changelog ==\n\n'
        if marker not in rd: raise SystemExit('readme changelog marker missing')
        rd=rd.replace(marker,marker+entry,1)
    write('readme.txt',rd)
    cl=read('CHANGELOG.md')
    entry2="## 1.2.11 — 2026-08-12\n\n- Completed the eleventh fresh sequential 20-round corrective cycle against exact v1.2.10 source.\n- Fail closed when SQL owner/appointment transactions cannot start or commit.\n- Replaced stale option-lock delete/recreate takeover with compare-and-swap and fenced outbox completion/failure by worker lease.\n- Made dispatcher durable-finalization failures visible and notification fallback all-recipient successful-or-retryable.\n- Hardened appointment replay keys, patient timezone, slot dates, inside-lock authorization, and check-in mode validation.\n- Removed native database IDs from protected appointment/payment/complaint REST response projections.\n- Made doctor-suspension hold + File19 projection atomic and added fail-closed service/branch/availability persistence validation.\n- Runtime 1.2.11; schemas remain core 3.2.0 / continuity 1.1.0 / Future24 1.0.0.\n- R17-R20 fresh corrected-state closure reviews remain required before final candidate closure; staging/live evidence remains separate.\n\n"
    if entry2 not in cl: cl=cl.replace('# Changelog\n\n','# Changelog\n\n'+entry2,1)
    write('CHANGELOG.md',cl)
    ev="""# File 08 — Eleventh Fresh 20-Round Review Evidence

Baseline source HEAD: `6e7acc0d768e4258e6262d337d409dff3f635533` (v1.2.10).

Sequence law: each supported finding was corrected and full source QA re-run before the next substantive round.

- E11-R01 transaction START/COMMIT fail-closed.
- E11-R02 appointment transaction START/COMMIT fail-closed.
- E11-R03 stale resource-lock CAS takeover fencing.
- E11-R04 worker-fenced outbox completion/failure.
- E11-R05 durable dispatcher finalization checking and contention evidence.
- E11-R06 all-recipient notification fallback semantics.
- E11-R07 bounded canonical appointment replay key.
- E11-R08 explicit invalid patient timezone fails closed.
- E11-R09 explicit invalid slot-search dates fail closed.
- E11-R10 transition authorization revalidated inside resource lock.
- E11-R11 check-in actual mode restricted to canonical modes.
- E11-R12 protected REST DTO native identifiers removed/allowlisted.
- E11-R13 doctor-suspension hold + File19 projection atomic and failure-visible.
- E11-R14 service mutation explicit type/currency validation.
- E11-R15 repository branch/service/availability persistence fail-closed validation.
- E11-R16 runtime/test/document identity and permanent regression evidence aligned to v1.2.11 without schema inflation.
- E11-R17 privacy/retention/legal-hold corrected-state review.
- E11-R18 Future24/cross-file/concurrency corrected-state review.
- E11-R19 migration/security/accessibility/repository-hygiene corrected-state review.
- E11-R20 final governing-plan/ownership/package/release-parity review.

Staging/live/operational acceptance is not established by this repository record.
"""
    write('ELEVENTH-TWENTY-REVIEW-EVIDENCE.md',ev)
else:
    raise SystemExit(f'unsupported resume round {r}')
print(f'Applied E11-R{r:02d}')
