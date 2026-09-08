# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Working review branch: `review/file08-t21-ten-round-2026-09-09`
- Review discipline: **T21 resumed 10-round Review → Ledger Freeze → Fix → Regression → Exact-head CI → Next Round**
- Verified pre-T21 baseline: **`79f18b5cb3ad9270a52027df7c3a6b50465fdfa4`**
- T20 closure: **R1–R20 complete**; latest ten (R11–R20) were **8/10 clean = 80%**. The prior automatic stopping threshold was reached, and the user explicitly resumed review work on 2026-09-09.
- T21 R1: review completed before correction; the frozen R1 release-evidence/currentness defects were corrected in the T21 branch. Exact R1 closure (the HEAD that is actually green/package-verified) is intentionally recorded in PR #10 / its canonical workflow rather than hard-coded here, so this status file does not become stale merely because CI finishes after the commit containing it.
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 additive operational schema/contract: **1.1.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**

Repository release identity is always the exact candidate HEAD together with its exact-head canonical GitHub Actions run, deterministic manifest, artifact digest and candidate SHA-256. The manifest must independently match the runtime, plan, core schema, continuity schema/contract, Future24 schema/contract, Public Clinic contract and CF-01 contract embedded in that same artifact.

## T21 progress evidence law

Numbered-round checkpoints are maintained in PR #10 because a static repository file cannot truthfully claim a CI result that occurs only after the file's own commit. For every T21 round, the governing order remains: full review-only pass → frozen ledger → complete correction batch → permanent regression → exact-head canonical CI/package → next round. A round is not closed merely because its correction source exists; the exact corrected HEAD itself must pass the canonical gates.

R1's frozen defect class covered current repository/release identity drift: stale T20/T19 current-state documentation, stale packaged/current lineage metadata, and historical cycle regressions that incorrectly forced T15/T16/T18/T19 to remain current identities. Those historical regressions now preserve their old evidence without pinning the current release identity.

## Historical T20 twenty-round result

T20 R1 and R2 were clean. R3–R10 were defect/gap-bearing, with R10 evidence-only. R11 was clean; R12 was defect-bearing and corrected; R13–R19 were clean; R20 was evidence-only defect-bearing and corrected through governing PR evidence. The latest T20 ten-round set therefore had clean rounds R11 and R13–R19 = 8/10 (80%), with R12 and R20 defect-bearing. The user later explicitly resumed a new T21 cycle.

## Historical T19 twenty-round result

R1–R9 were defect-bearing and were corrected/retested before R10. R10 was clean. R11 was defect-bearing; R12–R17 were clean; R18 and R19 were defect-bearing; R20 was defect-bearing at closure/release-hygiene level. Every defect-bearing round was reviewed completely before its defect ledger was frozen and its correction batch began.

The final T19 R20 correction was release/repository hygiene and documentation truth; it did not convert repository evidence into staging or live evidence. Automated-QA Green and Packaged apply only when the **current exact HEAD** has a successful canonical PHP 7.4/PHP 8.3 quality run and reproducible independently verified candidate package.

## Source implementation state

The candidate implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24` while preserving File 08 ownership boundaries. Current source includes clinic identity/branches/services/fees, timezone/DST-aware availability and slots, atomic holds, appointment lifecycle, patient/guardian/doctor/delegated-staff authorization, consent, secure continuity, review eligibility, calendar/payment/complaint adapters, privacy/audit/outbox/observability, migration/rollback and recovery, accessibility/localization, and Future24 scheduling/interoperability.

T20 materially hardened that source after the T19 checkpoint, including appointment idempotency-header normalization, reschedule lifecycle/hold cleanup, authorization boundary corrections, availability projection correction, payment-currency persistence parity, calendar-provider scope/order/idempotency hardening, and release-evidence alignment.

## Evidence-state classification

| State | Repository evidence rule |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Complete candidate** — current T21 review/correction sequence operates on the implemented 1.2.15 candidate. |
| Packaged | **Exact-head only** — valid only for a HEAD whose canonical reproducible-candidate job succeeds; see PR #10/current workflow evidence for the latest result. |
| Automated-QA Green | **Exact-head only** — valid only for a HEAD whose PHP 7.4/PHP 8.3/source/JS/hygiene gates are green; see PR #10/current workflow evidence for the latest result. |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Unverified / not claimed.** |
| Operational | **Not claimed.** |

## Mandatory staging / production gates

Install only the exact verified CI artifact on canonical Hostinger staging. Record package checksum, plugin/runtime and core/continuity/Future24 schema versions, DB/migration state, active configuration and companion-package parity. Complete fresh-install and real-upgrade/migration evidence; backup/restore/rollback; patient/guardian/doctor/delegated-staff/admin journeys; state/concurrency/replay/provider-outage tests; privacy/cache/accessibility checks; two fresh post-final-runtime-code verification sweeps; and Founder acceptance.

Only after explicitly authorized production deployment may live parity confirmation and live re-test begin.

## Live truth

This repository does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum and post-deploy behavior must be independently frozen and verified before any live/operational assertion.

## Historical evidence note

T13–T20, earlier corrective cycles, original-archive manifests/checksums and their embedded exact-head/schema values are historical provenance only unless a statement is explicitly repeated as current T21 evidence. Historical regression labels are retained only where old regression evidence requires them; they are not current release identity.
