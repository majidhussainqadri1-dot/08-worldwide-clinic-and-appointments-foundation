from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[2]
ROUND = 'R20'


def read(path):
    return (ROOT / path).read_text(encoding='utf-8')


def write(path, text):
    p = ROOT / path
    p.parent.mkdir(parents=True, exist_ok=True)
    p.write_text(text, encoding='utf-8')


def replace_once(path, old, new):
    text = read(path)
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{ROUND} {path}: expected exactly one match, found {count}')
    write(path, text.replace(old, new, 1))


# D8: preserve historical checksum evidence under an explicitly historical path,
# while removing ambiguous root-level checksum names.
historical = ROOT / 'docs' / 'historical'
historical.mkdir(parents=True, exist_ok=True)
for source, target in (
    ('CHECKSUMS.sha256', 'ORIGINAL-0.1.0-CHECKSUMS.sha256'),
    ('CORRECTIVE-CHECKSUMS.sha256', 'CORRECTIVE-0.2.1-CHECKSUMS.sha256'),
):
    src = ROOT / source
    if not src.is_file():
        raise SystemExit(f'{ROUND}: missing historical checksum source {source}')
    (historical / target).write_bytes(src.read_bytes())
    src.unlink()

# D8: make the old audit evidence unambiguously historical.
audit = read('AUDIT-EVIDENCE.md')
if not audit.startswith('# File 08 Audit Evidence'):
    raise SystemExit('R20 AUDIT-EVIDENCE unexpected header')
audit = audit.replace(
    '# File 08 Audit Evidence\n',
    '# Historical File 08 Audit Evidence — original 0.1.0 baseline only\n\n> **Historical evidence only.** This document records the original 0.1.0 archive/source audit. It does not describe the current T19 candidate, current schema/contracts, current source behavior, or any staging/live deployment. Current repository truth is governed by `STATUS.md` plus the exact-head candidate manifest/CI evidence.\n',
    1,
)
audit = audit.replace('The current helper uses `DateTime::createFromFormat', 'The original 0.1.0 helper used `DateTime::createFromFormat', 1)
write('AUDIT-EVIDENCE.md', audit)

# D4: current status must describe the actual completed numbered review series
# without claiming staging/live acceptance.
write('STATUS.md', '''# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Working review branch: `review/file08-t19-twenty-round-2026-09-06`
- Review discipline: **T19 fresh 20-round Review → Ledger Freeze → Fix → Regression → Exact-head CI → Next Round**
- Numbered review sequence: **R1–R20 complete at source-review level**
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 additive operational schema/contract: **1.1.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**

Repository release identity is always the exact candidate HEAD together with its exact-head canonical GitHub Actions run, deterministic manifest, artifact digest and candidate SHA-256. The manifest must independently match the runtime, plan, core schema, continuity schema/contract, Future24 schema/contract, Public Clinic contract and CF-01 contract embedded in that same artifact.

## T19 twenty-round result

R1–R9 were defect-bearing and were corrected/retested before R10. R10 was clean. R11 was defect-bearing; R12–R17 were clean; R18 and R19 were defect-bearing; R20 was defect-bearing at closure/release-hygiene level. Every defect-bearing round was reviewed completely before its ledger was frozen and its correction batch began.

The final R20 correction is release/repository hygiene and documentation truth; it does not convert repository evidence into staging or live evidence. Automated-QA Green and Packaged apply only when the **current exact HEAD** has a successful canonical PHP 7.4/PHP 8.3 quality run and reproducible independently verified candidate package.

## Source implementation state

The candidate implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24` while preserving File 08 ownership boundaries. Current source includes clinic identity/branches/services/fees, timezone/DST-aware availability and slots, atomic holds, appointment lifecycle, patient/guardian/doctor/delegated-staff authorization, consent, secure continuity, review eligibility, calendar/payment/complaint adapters, privacy/audit/outbox/observability, migration/rollback and recovery, accessibility/localization, and Future24 scheduling/interoperability.

## Evidence-state classification

| State | Repository evidence rule |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Complete candidate** — numbered T19 source review/correction sequence is complete. |
| Packaged | **Exact-head only** — true only for the exact HEAD whose deterministic candidate package verifies. |
| Automated-QA Green | **Exact-head only** — true only for the exact HEAD whose canonical quality workflow is green. |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Unverified / not claimed.** |
| Operational | **Not claimed.** |

## Mandatory staging / production gates

Install only the exact verified CI artifact on canonical Hostinger staging. Record package checksum, plugin/runtime and core/continuity/Future24 schema versions, DB/migration state, active configuration and companion-package parity. Complete fresh-install and real-upgrade/migration evidence; backup/restore/rollback; patient/guardian/doctor/delegated-staff/admin journeys; state/concurrency/replay/provider-outage tests; privacy/cache/accessibility checks; two fresh post-final-runtime-code verification sweeps; and Founder acceptance.

Only after explicitly authorized production deployment may live parity confirmation and live re-test begin.

## Live truth

This repository does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum and post-deploy behavior must be independently frozen and verified before any live/operational assertion.

## Historical evidence note

T13–T18, earlier corrective cycles, original-archive manifests/checksums and their embedded exact-head/schema values are historical provenance only. Historical regression labels such as **Fifteenth fresh 20-round main-cycle closure**, **Sixteenth fresh 20-round sequential audit**, and **Current sixteenth-cycle runtime alignment** are retained only where old regression evidence requires them; they are not current release identity.
''')

# D4: release-status document mirrors current source-review state without
# manufacturing staging/live evidence.
write('docs/RELEASE-STATUS-1.0.0.md', '''# Release Status — File 08 — document version 1.0.0

The `1.0.0` in this filename is the release-status document version; it is **not** the current plugin runtime.

## Current repository identity

- Runtime candidate: **1.2.15**
- Core schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 schema/contract: **1.1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Plan: **SSH-F08-PLAN-2026-v1.0**
- Current review branch: `review/file08-t19-twenty-round-2026-09-06`

## Repository-scope completion

The numbered **T19 R1–R20** source review/correction sequence is complete. Defect-bearing rounds were R1–R9, R11, R18, R19 and R20; clean rounds were R10 and R12–R17. Each defect-bearing round was completed before its defect ledger was frozen and corrected.

Implemented repository scope includes 18/18 FR, 10/10 NFR and Future24 24/24 capability governance; canonical data/schema and migration/rollback/recovery controls; security/privacy/reliability/accessibility/localization/observability; complete warning-clean source regression aggregation; and deterministic candidate engineering whose manifest binds exact commit, runtime, plan and core/continuity/Future24/Public-Clinic/CF-01 schema-contract identity.

## Evidence-state classification

- Source implementation: **CODED CANDIDATE — T19 R1–R20 SOURCE REVIEW COMPLETE**
- Automated source checks: **EXACT-HEAD EVIDENCE ONLY**
- Deterministic candidate: **EXACT-HEAD EVIDENCE ONLY**
- Hostinger staging acceptance: **NO / NOT CLAIMED**
- Founder staging acceptance: **NO / NOT CLAIMED**
- Production release/deployment: **NO / UNVERIFIED**
- Operational status: **NO / NOT CLAIMED**

A correction changes HEAD and makes prior CI/package evidence historical for final-release purposes. Packaged/Automated-QA Green status therefore applies only when the current exact HEAD itself passes the canonical PHP 7.4/PHP 8.3 quality jobs and reproducible candidate verification.

Environment-dependent acceptance cannot be manufactured in source. Execute `STAGING-ACCEPTANCE-1.0.0.md`/`STAGING-ACCEPTANCE.md` on canonical Hostinger staging with the exact verified artifact, including the two required fresh post-final-runtime-code verification sweeps; then, only after explicit production authorization, freeze deployed artifact/version/schema/migration state and perform live parity re-test.
''')

# D5: replace the contradictory current 1.2.15 changelog block. Older entries
# remain historical evidence.
changelog = read('CHANGELOG.md')
new_current = '''## 1.2.15 — current repository candidate\n\n- T19 fresh sequential R1–R20 source review/correction sequence completed under Review → Ledger Freeze → Fix → Regression → Exact-head CI discipline. Defect-bearing rounds: R1–R9, R11, R18, R19 and R20; clean rounds: R10 and R12–R17.\n- T19 corrections include test aggregation and workflow hygiene; release/schema/contract truth; query/object/participant authorization; appointment commit semantics; practitioner/service projection reconciliation; availability exception correctness; signed calendar-link and patient-timezone presentation; bounded repository reads; queue/dead-letter observability; migration/recovery operability; and final release-surface hygiene.\n- Runtime remains **1.2.15**. Current core schema is **3.4.0**; restricted continuity schema/contract **1.1.0**; Future24 schema/contract **1.1.0**; Public Clinic and CF-01 contracts **1.1.0**.\n- The former T15/T16/T17/T18 exact heads, schemas and package hashes remain historical evidence only. Current Packaged/Automated-QA status is valid only for the exact current HEAD with a successful canonical deterministic package run. Staging/live acceptance remains separate and is not claimed by repository evidence.\n\n'''
changelog, count = re.subn(r'## 1\.2\.15[^\n]*\n.*?(?=## 1\.2\.14)', new_current, changelog, count=1, flags=re.S)
if count != 1:
    raise SystemExit('R20 CHANGELOG current block not found')
write('CHANGELOG.md', changelog)

readme = read('readme.txt')
anchor = 'Version 1.2.15 implements the File 08 Complete Master Plan, Future24 amendment, the earlier 80-round corrective closure, the first 10-round post-closure review-and-correct cycle, the second and third fresh 10-round corrective audits, and the fourth fresh sequential 10-round corrective audit. '
if anchor not in readme:
    raise SystemExit('R20 readme description anchor not found')
readme = readme.replace(anchor, 'Version 1.2.15 implements the File 08 Complete Master Plan and Future24 amendment. The current T19 R1–R20 sequential source review is complete; defect-bearing rounds were R1–R9, R11, R18, R19 and R20, while R10 and R12–R17 were clean. Current identity is core schema 3.4.0, continuity 1.1.0, Future24 1.1.0, Public Clinic 1.1.0 and CF-01 1.1.0. Historical earlier audit cycles remain provenance only. ', 1)
marker = '= 1.2.15 =\n'
if readme.count(marker) != 1:
    raise SystemExit('R20 readme current changelog marker mismatch')
insert = '''= 1.2.15 =\n* T19 R1–R20 source review/correction sequence completed under review-first ledger discipline. Defect rounds: R1–R9, R11, R18, R19 and R20; clean rounds: R10 and R12–R17.\n* Current release identity: runtime 1.2.15; core schema 3.4.0; continuity schema/contract 1.1.0; Future24 schema/contract 1.1.0; Public Clinic and CF-01 contracts 1.1.0. Exact-head CI/package evidence remains distinct from staging/live evidence.\n'''
readme = readme.replace(marker, insert, 1)
write('readme.txt', readme)

# D6: exact staging schema handoff truth.
replace_once(
    'STAGING-ACCEPTANCE.md',
    '- [ ] File 08 core schema `3.2.0`, restricted continuity schema `1.1.0`, Future24 additive schema `1.0.0`, migration/options state and relevant tables/columns verified from the actual staging database.',
    '- [ ] File 08 core schema `3.4.0`, restricted continuity schema/contract `1.1.0`, Future24 additive schema/contract `1.1.0`, migration/options state and relevant tables/columns verified from the actual staging database.'
)

# R19 operator features must be reflected in the canonical runbook.
write('docs/OPERATIONS-RUNBOOK-1.0.0.md', '''# Operations Runbook — File 08 — document version 1.0.0

## Routine checks

- Open **Clinic Management → Operations** and review health, dependency matrix and outbox queue counts (`pending`, `retry`, `processing`, `dead_letter`, `due`, oldest due work).
- Confirm `wca_process_outbox` and `wca_maintenance` are scheduled.
- Use `wp wca health` for machine-readable health and `wp wca queue` for operator queue/dead-letter inspection.
- Alert on dead-letter growth, due-work backlog, error-rate increase, slot-hold expiry backlog, migration mismatch, missing tables or open provider circuits.
- Preserve logs without clinical/contact narrative; correlate by `X-Request-ID`.

## Safe operator actions

1. **Process due outbox:** use the nonce/capability-protected Operations control or `wp wca outbox --limit=<bounded-value>`. Treat a returned error as failure; do not report success from a redirect alone.
2. **Migration/repair:** `wp wca migrate` repairs/verifies all File 08-owned schema layers — legacy SWC, canonical WCA, restricted continuity and Future24 — before reconciling legacy status state. The admin Complete Repair path covers the same owned schema layers.
3. **Runtime migration failure:** recovery CLI registers before runtime migration execution so `wp wca health`, `wp wca queue` and `wp wca migrate` remain available for diagnosis/recovery when the web runtime is paused by a migration failure.
4. **Dead-letter work:** inspect privacy-safe error codes/provider state first; correct the root cause before retrying. Never edit outbox payloads into a second writable truth.

## Failure handling

1. **File 00/09 unavailable:** protected clinic/doctor actions fail closed; do not downgrade to local roles.
2. **File 19 unavailable:** privacy-minimal email fallback is attempted; failures retry through outbox and then dead-letter.
3. **Calendar/payment/case provider failure:** retain local intent/event, retry asynchronously, open circuit after repeated failures and never duplicate provider writes.
4. **Database contention:** return conflict/stale response, preserve original state and require refreshed action.
5. **Emergency content:** divert immediately; no appointment or delayed support workflow.
6. **Doctor suspension:** place nonterminal appointments on authority hold and notify affected patients without exposing private details.

## Recovery targets

Recovery objectives must be measured on Hostinger staging and recorded with actual backup/restore durations. Source documentation does not invent an RTO/RPO. Production authorization requires an observed restore and post-restore reconciliation.
''')

# D9: local build output should not be accidentally tracked.
gitignore = read('.gitignore')
if 'build/\n' not in gitignore:
    gitignore += 'build/\n'
write('.gitignore', gitignore)

# D9: make the canonical workflow enforce the post-correction release surface.
workflow = read('.github/workflows/file08-complete-quality.yml')
needle = '''          test ! -e .gitmodules\n          ! grep -RInE '(BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|ghp_[A-Za-z0-9]{30,}|AKIA[0-9A-Z]{16})' --exclude-dir=.git .\n'''
replacement = '''          test ! -e .gitmodules\n          test "$(find .github/workflows -maxdepth 1 -type f -name '*.yml' | wc -l | tr -d ' ')" -eq 1\n          test -z "$(find .github/scripts -type f -print -quit 2>/dev/null || true)"\n          test ! -e tools/build-development-candidate.php\n          test ! -e tools/verify-development-candidate.php\n          test ! -e CHECKSUMS.sha256\n          test ! -e CORRECTIVE-CHECKSUMS.sha256\n          test -z "$(git ls-files 'build/*')"\n          ! grep -RInE '(BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY|ghp_[A-Za-z0-9]{30,}|AKIA[0-9A-Z]{16})' --exclude-dir=.git .\n'''
if workflow.count(needle) != 1:
    raise SystemExit('R20 canonical workflow hygiene insertion point mismatch')
write('.github/workflows/file08-complete-quality.yml', workflow.replace(needle, replacement, 1))

# D9: permanent closure-hygiene regression gate.
test = ROOT / 'tests' / 't19-r20-closure-hygiene-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function r20_check( $name, $condition ) { global $failures, $checks; $checks++; if ( ! $condition ) { $failures[] = $name; } }
$workflows = glob( $root . '/.github/workflows/*.yml' );
$workflow_names = is_array( $workflows ) ? array_map( 'basename', $workflows ) : array();
sort( $workflow_names, SORT_STRING );
r20_check( 'single canonical workflow', array( 'file08-complete-quality.yml' ) === $workflow_names );
$scripts = glob( $root . '/.github/scripts/*' );
r20_check( 'historical correction script surface retired', ! is_array( $scripts ) || 0 === count( $scripts ) );
r20_check( 'obsolete development builder retired', ! is_file( $root . '/tools/build-development-candidate.php' ) );
r20_check( 'obsolete development verifier retired', ! is_file( $root . '/tools/verify-development-candidate.php' ) );
r20_check( 'ambiguous root original checksums retired', ! is_file( $root . '/CHECKSUMS.sha256' ) );
r20_check( 'ambiguous root corrective checksums retired', ! is_file( $root . '/CORRECTIVE-CHECKSUMS.sha256' ) );
r20_check( 'historical original checksum evidence preserved', is_file( $root . '/docs/historical/ORIGINAL-0.1.0-CHECKSUMS.sha256' ) );
r20_check( 'historical corrective checksum evidence preserved', is_file( $root . '/docs/historical/CORRECTIVE-0.2.1-CHECKSUMS.sha256' ) );
$status = file_get_contents( $root . '/STATUS.md' );
$release = file_get_contents( $root . '/docs/RELEASE-STATUS-1.0.0.md' );
$change = file_get_contents( $root . '/CHANGELOG.md' );
$staging = file_get_contents( $root . '/STAGING-ACCEPTANCE.md' );
$audit = file_get_contents( $root . '/AUDIT-EVIDENCE.md' );
$readme = file_get_contents( $root . '/readme.txt' );
$workflow = file_get_contents( $root . '/.github/workflows/file08-complete-quality.yml' );
$ignore = file_get_contents( $root . '/.gitignore' );
foreach ( array( $status,$release,$change,$staging,$audit,$readme,$workflow,$ignore ) as $source ) { if ( ! is_string( $source ) ) { fwrite( STDERR, "T19 R20 source read failed\n" ); exit( 1 ); } }
r20_check( 'status closes R1-R20 numbered source review', false !== strpos( $status, 'R1–R20 complete at source-review level' ) );
r20_check( 'status carries current core schema', false !== strpos( $status, 'Core File 08 schema: **3.4.0**' ) );
r20_check( 'release status is no longer under active R3 audit', false === strpos( $release, 'REVIEWABLE CANDIDATE UNDER T19 SEQUENTIAL AUDIT' ) && false !== strpos( $release, 'T19 R1–R20 SOURCE REVIEW COMPLETE' ) );
r20_check( 'current changelog carries core 3.4', false !== strpos( $change, 'Current core schema is **3.4.0**' ) );
r20_check( 'current changelog carries Future24 1.1', false !== strpos( $change, 'Future24 schema/contract **1.1.0**' ) );
r20_check( 'staging handoff carries core 3.4', false !== strpos( $staging, 'core schema `3.4.0`' ) );
r20_check( 'staging handoff carries Future24 1.1', false !== strpos( $staging, 'Future24 additive schema/contract `1.1.0`' ) );
r20_check( 'audit evidence explicitly historical', false !== strpos( $audit, 'Historical evidence only.' ) && false !== strpos( $audit, 'original 0.1.0 helper used' ) );
r20_check( 'plugin readme records T19 closure', false !== strpos( $readme, 'current T19 R1–R20 sequential source review is complete' ) );
r20_check( 'canonical workflow enforces one workflow', false !== strpos( $workflow, 'find .github/workflows -maxdepth 1' ) );
r20_check( 'canonical workflow rejects obsolete development builder', false !== strpos( $workflow, 'test ! -e tools/build-development-candidate.php' ) );
r20_check( 'local deterministic build output ignored', false !== strpos( $ignore, "build/\n" ) );
if ( $failures ) { fwrite( STDERR, "T19 R20 closure hygiene failed:\n- " . implode( "\n- ", $failures ) . "\n" ); exit( 1 ); }
echo "T19 R20 closure-hygiene regressions: PASS {$checks}/{$checks}.\n";
''', encoding='utf-8')

run = read('tests/run-all.php')
needle = "'t19-r19-operability-performance-regressions.php' );"
replacement = "'t19-r19-operability-performance-regressions.php', 't19-r20-closure-hygiene-regressions.php' );"
if run.count(needle) != 1:
    raise SystemExit('R20 run-all insertion point mismatch')
write('tests/run-all.php', run.replace(needle, replacement, 1))

# D1/D2/D3: retire all noncanonical mutation/duplicate workflows, all historical
# executable correction scripts (including this running script), and obsolete
# alternate candidate tooling. Git history remains the provenance store.
for path in (
    ROOT / '.github' / 'workflows' / 't19-r1-regression.yml',
    ROOT / '.github' / 'workflows' / 't19-round-fix.yml',
    ROOT / '.github' / 'workflows' / 't19-r20-correct.yml',
    ROOT / 'tools' / 'build-development-candidate.php',
    ROOT / 'tools' / 'verify-development-candidate.php',
):
    if path.exists():
        path.unlink()

scripts_dir = ROOT / '.github' / 'scripts'
if scripts_dir.is_dir():
    for path in scripts_dir.iterdir():
        if path.is_file():
            path.unlink()

print('T19 R20 frozen ledger corrections applied; correction machinery retired in working tree.')
