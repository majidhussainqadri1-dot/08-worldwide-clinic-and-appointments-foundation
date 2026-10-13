# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Working review branch: `review/file08-t32-40-audits-future24-policies-2026-10-13`
- Current review discipline: **T26 closure cycle — Review → Ledger Freeze → Fix → Regression → Exact-head CI/package → Next Round**
- Current T26 ten-round cycle: **R1 defect-bearing/corrected; R2–R9 clean; R10 defect-bearing/corrected at repository release-currentness/regression level.**
- Historical T25: **R1–R10 closed**; defect-bearing R1, R8, R9 and R10; clean R2–R7. Former T25 exact heads/runs are historical provenance only.
- Historical T22: **R1–R10 closed**; former T22 exact heads/runs are historical provenance only.
- Historical T21: **R1–R10 closed**; defect-bearing R1–R6, R9, R10; clean R7–R8.
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 additive operational schema/contract: **1.1.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**

Repository release identity is always the exact candidate HEAD together with its exact-head canonical GitHub Actions run, deterministic manifest, artifact digest and candidate SHA-256. The manifest must independently match the runtime, plan, core schema, continuity schema/contract, Future24 schema/contract, Public Clinic contract and CF-01 contract embedded in that same artifact.

## Current T26 evidence law

Numbered-round checkpoints are maintained in PR #10 and frozen ledgers because a static repository file cannot truthfully claim a future CI result that occurs only after its own commit. For every T26 round, the governing order remains: full review-only pass → frozen ledger → complete correction batch → permanent regression → exact-head canonical CI/package → next round. A round is not closed merely because correction source exists; the exact corrected HEAD itself must pass the canonical gates.

T26 R1 corrected Public Clinic canonical/runtime contract-version drift and the package-verifier blind spot; R2–R9 were clean. R10 identified and corrected stale T25 current-cycle release surfaces plus the historical T25 regression that still enforced T25 as current. Exact-head CI/package status is determined from the canonical workflow on the exact HEAD and is not hard-coded here as a future result.

## Historical T25 result

T25 is closed and historical. Defect-bearing rounds: R1, R8, R9, R10. Clean rounds: R2–R7. Its earlier exact-head/run references remain provenance only and must not be used as current-candidate evidence after T26 commits.

## Historical T22 result

T22 is closed and historical. Its earlier exact-head/run references remain provenance only and must not be used as current-candidate evidence after later commits.

## Historical T21 result

T21 R1–R10 are closed. Defect-bearing rounds: R1, R2, R3, R4, R5, R6, R9, R10. Clean rounds: R7, R8. Clean rate: 2/10.

## Historical T20 twenty-round result

T20 R1 and R2 were clean. R3–R10 were defect/gap-bearing, with R10 evidence-only. R11 was clean; R12 was defect-bearing and corrected; R13–R19 were clean; R20 was evidence-only defect-bearing and corrected through governing PR evidence. The latest T20 ten-round set therefore had clean rounds R11 and R13–R19 = 8/10 (80%), with R12 and R20 defect-bearing. The user later explicitly resumed further review cycles.

## Historical T19 twenty-round result

R1–R9 were defect-bearing and were corrected/retested before R10. R10 was clean. R11 was defect-bearing; R12–R17 were clean; R18 and R19 were defect-bearing; R20 was defect-bearing at closure/release-hygiene level. Every defect-bearing round was reviewed completely before its defect ledger was frozen and its correction batch began.

The final T19 R20 correction was release/repository hygiene and documentation truth; it did not convert repository evidence into staging or live evidence. Automated-QA Green and Packaged apply only when the **current exact HEAD** has a successful canonical PHP 7.4/PHP 8.3 quality run and reproducible independently verified candidate package.

## Source implementation state

The candidate implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24` while preserving File 08 ownership boundaries. Current source includes clinic identity/branches/services/fees, timezone/DST-aware availability and slots, atomic holds, appointment lifecycle, patient/guardian/doctor/delegated-staff authorization, consent, secure continuity, review eligibility, calendar/payment/complaint adapters, privacy/audit/outbox/observability, migration/rollback and recovery, accessibility/localization, and Future24 scheduling/interoperability.

T20 materially hardened that source after T19. T21 and T22 added further governance, reconciliation and presentation hardening. T23/T24 added further financial, migration, authorization, scheduling, privacy, discovery, provider/outbox, frontend/accessibility/localization and release-truth hardening. T25 added brand/release truth, File26/CF-01 reliability, bounded reconciliation/migration-health and release-currentness hardening. T26 then re-reviewed contract/release parity, APIs/authorization, appointment concurrency, finance, privacy, provider/outbox, Future24, frontend/localization and migration/operability; R1 corrected Public Clinic runtime/canonical contract parity and R10 corrected current-cycle release truth without changing the runtime candidate identity.

## Evidence-state classification

| State | Repository evidence rule |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Complete candidate** — current T26 review/correction sequence operates on the implemented 1.2.15 candidate. |
| Packaged | **Exact-head only** — valid only for a HEAD whose canonical reproducible-candidate job succeeds; consult PR #10/current workflow evidence. |
| Automated-QA Green | **Exact-head only** — valid only for a HEAD whose PHP 7.4/PHP 8.3/source/JS/hygiene gates are green; consult PR #10/current workflow evidence. |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Unverified / not claimed.** |
| Operational | **Not claimed.** |

## Mandatory staging / production gates

Install only the exact verified CI artifact on canonical Hostinger staging. Record package checksum, plugin/runtime and core/continuity/Future24 schema versions, DB/migration state, active configuration and companion-package parity. Complete fresh-install and real-upgrade/migration evidence; backup/restore/rollback; patient/guardian/doctor/delegated-staff/admin journeys; state/concurrency/replay/provider-outage tests; privacy/cache/accessibility checks; two fresh post-final-runtime-code verification sweeps; and Founder acceptance.

Only after explicitly authorized production deployment may live parity confirmation and live re-test begin.

## Live truth

This repository does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum and post-deploy behavior must be independently frozen and verified before any live/operational assertion.

## Historical evidence note

T13–T25 and earlier corrective cycles, original-archive manifests/checksums and their embedded exact-head/schema values are historical provenance only unless a statement is explicitly repeated as current T26 evidence. Historical regression labels are retained only where old regression evidence requires them; they are not current release identity.


## T29 — unified current-plan source candidate (12 October 2026)
Latest File08 PR#12/T28 Source `0dbfddc789f295b30b9aa1b71cb7ec4fda443bff` retains current File00 1.2.3, File09 eligible verification, CF01 1.1.0, all original + revived runtime tests, package parity and File17/19 fixes. T29 candidate additionally integrates proven PR#14 strict File08 patient/clinic signed cursor decoder, RAW UTC cursor encoder validation and no-partial-pagination degraded responses. **20 bounded File08/central/companion source reviews** were frozen before modification at `docs/T29-20-FROZEN-REVIEWS-2026-10-12.md`; 20 complete real E2E reviews have NOT been performed. Tests include `tests/t27-r1-query-cursor-integrity-regressions.php`, `tests/t27-r2-cursor-producer-consumer-regressions.php` and the T28 no-orphan suite. Software 1.2.15; source core schema 3.4.0, Future24/Continuity 1.1.0 unchanged. Hostinger/physical DB/migrations/staging/live verification not performed; PR is a draft and no deployment authorized.


## T30 exact-source review candidate — 12 October 2026
Twenty bounded plan/central/companion owner inspections against T29 SHA `03a376e3aab37e191eeb32fbf50910e644095f30` frozen before edits in `docs/T30-20-FROZEN-REVIEWS-2026-10-12.md`. T30-D01: malformed signed pagination cursor arrays, whitespace, HTML and dashboard query parameters could be cast, trimmed or sanitized before proof of canonical identity. T30-D02: malformed/nested `per_page` silently defaulted to 20. Current source `WCA_Query_API` now rejects wrong types without DB reads or private projection, preserves an exact signed opaque cursor and a canonical bounded positive page size, with a new real method regression in `tests/t30-private-query-input-integrity-regressions.php`. This is one source-only draft; prior T29 File00/09/CF01, File17/19 and all T28 test coverage are retained. Software candidate 1.2.15, source core schema 3.4.0 and Future24/Continuity 1.1.0 unchanged. Hostinger deployed code, DB/migrations, two staged reviews, Founder acceptance, deployment and live retest remain unverified.


## T31 original-plan Future24 integrity source candidate — 13 October 2026
The 20 **bounded** File08/central/companion source reviews were frozen *before code changes* in `docs/T31-20-FROZEN-SOURCE-REVIEWS-2026-10-13.md`. T31-D01: canonical File00 subject UUID not available, yet prior Group Session join could persist an empty member_ref; new `WCA_Future24::join_group_session` validates the exact owner-issued UUID before any group session lookup or write. T31-D02: malformed historical Future24 payload_json could masquerade as empty operational state; `public_record` now rejects nonobject/nonempty positional JSON with structured privacy-safe logs and a 503, preserving valid {} and legacy [] empty. Native regression `tests/t31-future24-group-identity-and-record-integrity.php` registered in the canonical full no-orphan runner. All code-level corrections are source-only: unchanged software 1.2.15, Core schema 3.4.0 and Continuity/Future24 1.1.0. No installed Hostinger plugin, physical DB/schema/wp_options, migration, dependency/staging/live checks verified.


## T32 — 40 governed source checks and FUT11/13 operational policy safety (13 October 2026)
Forty separate **bounded** original File08 FR001–018/NFR001–010/Future24 requirement-to-source inspections were FROZEN before fixes in `docs/T32-40-FROZEN-GOVERNING-REVIEWS-2026-10-13.md`, baseline exact PR18 SHA `258b8e00a4c1ea360802c636c10919bc13a89faa`. They are not forty complete real end-to-end audits. T32-D01: nested FUT13 blocking rule maps were discarded by generic operational sanitizer before storage, potentially making readiness falsely complete. Narrow canonical FUT13 rule whitelist in put_record now retains only four approved nonclinical fields. T32-D02: corrupt active FUT11 questionnaire and FUT13 rule JSON no longer silently becomes empty form/complete policy. T32-D03: keyset policy reader now rejects invalid/non-increasing source row IDs, avoiding nonprogress loops/duplicate projection. Real PHP `tests/t32-future24-policy-persistence-and-read-integrity.php` registered with all prior T31 tests and T28 zero-orphan gate. No source schema change: software `1.2.15`, Core `3.4.0`, Future24/Continuity `1.1.0`. Historical broken rows require actual DB inspection and controlled remediation; no physical migration performed, no claim of installed release or live verification.
