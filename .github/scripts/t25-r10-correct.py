from pathlib import Path


def patch(path, replacements):
    p = Path(path)
    s = p.read_text()
    for old, new in replacements:
        if old not in s:
            raise SystemExit(f"missing expected text in {path}: {old[:120]}")
        s = s.replace(old, new, 1)
    p.write_text(s)


patch('README.md', [
    ('Current review cycle: **T24 closure cycle — Review → Ledger Freeze → Fix → Regression → Exact-head CI/package → Next Round**', 'Current review cycle: **T25 closure cycle — Review → Ledger Freeze → Fix → Regression → Exact-head CI/package → Next Round**'),
    ('Current ten-round continuation: **T23 R10 clean; T24 R1 defect-bearing/corrected; T24 R2–R8 clean; T24 R9 defect-bearing/corrected at repository-documentation level.** Exact-head CI/package status is defined only by the current canonical workflow and is never inferred from an older run recorded in a static document.', 'Current T25 ten-round cycle: **R1 defect-bearing/corrected; R2–R7 clean; R8 defect-bearing/corrected; R9 defect-bearing/corrected; R10 defect-bearing/corrected at repository-documentation level.** Exact-head CI/package status is defined only by the current canonical workflow and is never inferred from an older run recorded in a static document.'),
    ('- `/appointments/book/{opaque_practitioner_ref}`', '- `/appointments/book/{doctor_or_clinic}`'),
    ('T24 is the current closure cycle. Any correction changes HEAD and therefore requires a fresh exact-head canonical CI/package run before that corrected HEAD may inherit `Packaged` or `Automated-QA Green` status.', 'T25 is the current closure cycle. R1 corrected governing brand/release drift; R2–R7 were clean; R8 corrected cross-file projection/CF-01 checked-in context defects; R9 corrected bounded reconciliation/migration-health defects; R10 corrected release-currentness and canonical booking-route documentation. Any correction changes HEAD and therefore requires a fresh exact-head canonical CI/package run before that corrected HEAD may inherit `Packaged` or `Automated-QA Green` status.'),
])

patch('STATUS.md', [
    ('Current review discipline: **T24 closure cycle — Review → Ledger Freeze → Fix → Regression → Exact-head CI/package → Next Round**', 'Current review discipline: **T25 closure cycle — Review → Ledger Freeze → Fix → Regression → Exact-head CI/package → Next Round**'),
    ('Current ten-round continuation: **T23 R10 clean; T24 R1 defect-bearing/corrected; T24 R2–R8 clean; T24 R9 defect-bearing/corrected at repository-documentation level.**', 'Current T25 ten-round cycle: **R1 defect-bearing/corrected; R2–R7 clean; R8 defect-bearing/corrected; R9 defect-bearing/corrected; R10 defect-bearing/corrected at repository-documentation level.**'),
    ('## Current T24 evidence law', '## Current T25 evidence law'),
    ('For every T24 round, the governing order remains:', 'For every T25 round, the governing order remains:'),
    ('The present ten-round continuation begins with historical T23 R10 and continues through T24 R1–R9. T24 R1 corrected uncertain-transaction idempotency replay boundaries in CF02 complaint-status projection, CF03 payment-status projection and Future24 generic idempotent mutations. T24 R2–R8 were clean. T24 R9 identified and corrected release-document currentness drift. Exact-head CI/package status is determined from the canonical workflow on the exact HEAD and is not hard-coded here as a future result.', 'T25 R1 corrected governing brand/release drift; R2–R7 were clean; R8 corrected atomic File26 search-projection invalidation and checked-in CF-01 scheduling projection; R9 corrected bounded verification reconciliation, complete CLI legacy migration and migration-aware health; R10 corrected current-cycle/release documentation and canonical booking-route documentation. Exact-head CI/package status is determined from the canonical workflow on the exact HEAD and is not hard-coded here as a future result.'),
    ('T23/T24 re-reviewed the same source across financial, migration, authorization, scheduling, privacy, discovery, provider/outbox, frontend/accessibility/localization and release-truth boundaries without changing the runtime candidate identity.', 'T23/T24 added further financial, migration, authorization, scheduling, privacy, discovery, provider/outbox, frontend/accessibility/localization and release-truth hardening. T25 then re-reviewed brand/release truth, APIs/authorization, appointment concurrency, finance, calendar/outbox, privacy, schema/migration, cross-file reliability, operability and final release currentness without changing the runtime candidate identity.'),
    ('**Complete candidate** — current T24 review/correction sequence operates on the implemented 1.2.15 candidate.', '**Complete candidate** — current T25 review/correction sequence operates on the implemented 1.2.15 candidate.'),
    ('unless a statement is explicitly repeated as current T24 evidence.', 'unless a statement is explicitly repeated as current T25 evidence.'),
])

patch('CHANGELOG.md', [
    ('- **T24 closure cycle is the current repository review sequence.** Current ten-round continuation: T23 R10 clean; T24 R1 defect-bearing/corrected; T24 R2–R8 clean; T24 R9 defect-bearing/corrected. Exact-head CI/package status is determined only by the canonical workflow on the exact HEAD under evaluation.', '- **T25 closure cycle is the current repository review sequence.** R1 was defect-bearing/corrected; R2–R7 were clean; R8 and R9 were defect-bearing/corrected; R10 was defect-bearing/corrected at release-documentation level. Exact-head CI/package status is determined only by the canonical workflow on the exact HEAD under evaluation.'),
    ('- T24 material hardening in the same 1.2.15 candidate includes uncertain-transaction idempotency retention for verified **CF02** complaint-status projection, verified **CF03** payment-status projection and generic **Future24** mutations, plus release-currentness documentation/regression correction.', '- T25 hardening in the same 1.2.15 candidate includes governing brand/release alignment; atomic File26 search-projection invalidation; corrected checked-in CF-01 scheduling projection; bounded/dead-lettered verification reconciliation; complete CLI legacy-status migration; migration-aware health; and final release-currentness/canonical-route documentation. Historical T24 hardening remains provenance.'),
])

patch('docs/RELEASE-STATUS-1.0.0.md', [
    ('- Current review cycle: **T24 closure cycle**', '- Current review cycle: **T25 closure cycle**'),
    ('- Current ten-round continuation: **T23 R10 clean; T24 R1 defect-bearing/corrected; T24 R2–R8 clean; T24 R9 defect-bearing/corrected at repository-documentation level.**', '- Current T25 ten-round cycle: **R1 defect-bearing/corrected; R2–R7 clean; R8 defect-bearing/corrected; R9 defect-bearing/corrected; R10 defect-bearing/corrected at repository-documentation level.**'),
    ('T24 is the active closure sequence and follows the mandatory Review → Ledger Freeze → Fix → Regression → Exact-head CI/package discipline.', 'T25 is the active closure sequence and follows the mandatory Review → Ledger Freeze → Fix → Regression → Exact-head CI/package discipline.'),
    ('Current T23/T24 review evidence re-examines financial, migration, authorization, scheduling, privacy, discovery, calendar/outbox, frontend and release-truth boundaries. T24 R1 corrected uncertain-transaction replay handling for CF02 complaint status, CF03 payment-status projection and Future24 generic idempotent mutations. T24 R2–R8 were clean. T24 R9 corrected stale release-currentness documentation/regression. Runtime remains 1.2.15 because these corrections did not change the public runtime version contract.', 'Current T25 review evidence re-examines governing brand/release truth, API authorization, scheduling/concurrency, finance, calendar/outbox, privacy, schema/migration, cross-file reliability, operability and final release currentness. R1, R8, R9 and R10 were defect-bearing and corrected after their ledgers were frozen; R2–R7 were clean. Runtime remains 1.2.15 because these corrections did not change the public runtime version contract.'),
    ('- Source implementation: **CODED CANDIDATE — T24 CLOSURE CYCLE ACTIVE**', '- Source implementation: **CODED CANDIDATE — T25 CLOSURE CYCLE ACTIVE**'),
    ('- Earlier T22/T23 exact-head CI/package evidence: **HISTORICAL after later commits**', '- Earlier T22/T23/T24 exact-head CI/package evidence: **HISTORICAL after later commits**'),
    ('T19, T20, T21, T22 and T23 remain historical provenance except where an explicit T23/T24 ledger is part of the current ten-round continuation.', 'T19, T20, T21, T22, T23 and T24 remain historical provenance; T25 frozen ledgers are the current ten-round review record.'),
])

patch('readme.txt', [
    ('* T25 is the current review sequence. It began only after T24 R9 exact head `8e3945b8b79afdf5ba5feb2d8c3222da0d86c225` passed canonical run `35053963450`; every T25 round remains review-first and any later corrected HEAD requires its own exact-head CI/package proof.', '* T25 is the current review sequence. It began only after T24 R9 exact head `8e3945b8b79afdf5ba5feb2d8c3222da0d86c225` passed canonical run `35053963450`; T25 R1, R8, R9 and R10 were defect-bearing and corrected only after their frozen ledgers, while R2–R7 were clean. Any corrected HEAD requires its own exact-head CI/package proof.'),
])

Path('tests/t24-r9-release-currentness-regressions.php').write_text('''<?php
$root=dirname(__DIR__);$ledger=file_get_contents($root.'/docs/T24-R9-FROZEN-LEDGER.md');$readme=file_get_contents($root.'/README.md');$status=file_get_contents($root.'/STATUS.md');$release=file_get_contents($root.'/docs/RELEASE-STATUS-1.0.0.md');$change=file_get_contents($root.'/CHANGELOG.md');
$checks=array('historical T24 ledger retained'=>is_string($ledger)&&false!==strpos($ledger,'R9-D1'),'T24 no longer current in README'=>is_string($readme)&&false===strpos($readme,'Current review cycle: **T24 closure cycle'),'T24 no longer current in STATUS'=>is_string($status)&&false===strpos($status,'Current review discipline: **T24 closure cycle'),'T24 no longer current in release status'=>is_string($release)&&false===strpos($release,'Current review cycle: **T24 closure cycle**'),'T24 no longer current in changelog'=>is_string($change)&&false===strpos($change,'**T24 closure cycle is the current repository review sequence.**'),'live truth remains unclaimed'=>false!==strpos($status,'Live-Deployed | **Unverified / not claimed.**')&&false!==strpos($release,'Production release/deployment: **NO / UNVERIFIED**'));$bad=array();foreach($checks as $n=>$ok){if(!$ok)$bad[]=$n;}if($bad){fwrite(STDERR,'Historical T24 R9 release regressions failed: '.implode(', ',$bad)."\n");exit(1);}echo 'Historical T24 R9 release regressions: PASS '.count($checks).'/'.count($checks)."\n";
''')

Path('tests/t25-r10-release-closure-regressions.php').write_text('''<?php
$root=dirname(__DIR__);$readme=file_get_contents($root.'/README.md');$status=file_get_contents($root.'/STATUS.md');$release=file_get_contents($root.'/docs/RELEASE-STATUS-1.0.0.md');$change=file_get_contents($root.'/CHANGELOG.md');$package=file_get_contents($root.'/readme.txt');$contracts=file_get_contents($root.'/includes/class-wca-contracts.php');$workflow=file_get_contents($root.'/.github/workflows/file08-complete-quality.yml');$runner=file_get_contents(__DIR__.'/run-all.php');$ledger=file_get_contents($root.'/docs/T25-R10-FROZEN-LEDGER.md');
foreach(array($readme,$status,$release,$change,$package,$contracts,$workflow,$runner,$ledger) as $s){if(!is_string($s)){fwrite(STDERR,"T25 R10 source read failed\n");exit(1);}}$checks=array('README identifies T25 current'=>false!==strpos($readme,'Current review cycle: **T25 closure cycle'),'STATUS identifies T25 current'=>false!==strpos($status,'Current review discipline: **T25 closure cycle'),'release status identifies T25 current'=>false!==strpos($release,'Current review cycle: **T25 closure cycle**'),'changelog identifies T25 current'=>false!==strpos($change,'**T25 closure cycle is the current repository review sequence.**'),'packaged readme identifies T25'=>false!==strpos($package,'current repository review sequence is **T25**'),'round result truth is present'=>false!==strpos($status,'R1 defect-bearing/corrected; R2–R7 clean; R8 defect-bearing/corrected; R9 defect-bearing/corrected; R10 defect-bearing/corrected'),'governing booking route documented'=>false!==strpos($readme,'/appointments/book/{doctor_or_clinic}')&&false!==strpos($contracts,"'pattern' => '/appointments/book/{doctor_or_clinic}'"),'old practitioner-only route documentation retired'=>false===strpos($readme,'/appointments/book/{opaque_practitioner_ref}'),'canonical workflow exact-head package gate retained'=>false!==strpos($workflow,"php: ['7.4', '8.3']")&&false!==strpos($workflow,'Build twice')&&false!==strpos($workflow,'Independent verification'),'live truth remains unclaimed'=>false!==strpos($status,'Live-Deployed | **Unverified / not claimed.**')&&false!==strpos($release,'Production release/deployment: **NO / UNVERIFIED**'),'R10 ledger proves review first'=>false!==strpos($ledger,'No R10 correction was started while the review remained open')&&false!==strpos($ledger,'R10-D1')&&false!==strpos($ledger,'R10-D2'),'R10 regression bound'=>false!==strpos($runner,"'t25-r10-release-closure-regressions.php'"));$bad=array();foreach($checks as $n=>$ok){if(!$ok)$bad[]=$n;}if($bad){fwrite(STDERR,"T25 R10 release closure regressions failed:\n- ".implode("\n- ",$bad)."\n");exit(1);}echo 'T25 R10 release closure regressions: PASS '.count($checks).'/'.count($checks)."\n";
''')

runner=Path('tests/run-all.php');s=runner.read_text();needle="'t25-r9-operability-reconciliation-regressions.php'"
if "'t25-r10-release-closure-regressions.php'" not in s:
    if needle not in s:
        raise SystemExit('run-all insertion point missing')
    runner.write_text(s.replace(needle, needle+", 't25-r10-release-closure-regressions.php'", 1))

Path('docs/T25-R10-CORRECTION-EVIDENCE.md').write_text('''# T25 R10 — Correction Evidence

R10 corrections were applied only after `docs/T25-R10-FROZEN-LEDGER.md` froze the complete review findings.

- Current release/status surfaces now identify T25 rather than T24 as the active closure cycle and preserve T24 as historical provenance.
- T25 round truth is summarized as R1/R8/R9/R10 defect-bearing and corrected, with R2–R7 clean.
- README booking-route documentation now matches the governing `/appointments/book/{doctor_or_clinic}` contract.
- The historical T24 currentness regression no longer forces T24 to remain current.
- `tests/t25-r10-release-closure-regressions.php` is bound into the aggregate suite.
- Repository/staging/live evidence states remain separate; no staging or live claim is created by this correction.

Final Packaged / Automated-QA Green status belongs only to the exact corrected HEAD if the restored canonical workflow succeeds on that same HEAD.
''')
