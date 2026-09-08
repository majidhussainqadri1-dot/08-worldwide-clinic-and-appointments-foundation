# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Working review branch: `review/file08-t20-twenty-round-2026-09-07`
- Review discipline: **T20 fresh 20-round Review → Ledger Freeze → Fix → Regression → Exact-head CI → Next Round**
- Numbered review sequence: **T20 R1–R12 complete at source-review level; cycle remains in progress**
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 additive operational schema/contract: **1.1.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**

Repository release identity is always the exact candidate HEAD together with its exact-head canonical GitHub Actions run, deterministic manifest, artifact digest and candidate SHA-256. The manifest must independently match the runtime, plan, core schema, continuity schema/contract, Future24 schema/contract, Public Clinic contract and CF-01 contract embedded in that same artifact.

## T20 progress

R1 and R2 were clean. R3–R9 were defect-bearing and corrected/retested before the next round. R10 was defect-bearing at repository/PR evidence level only and corrected without changing runtime/schema/live state. R11 was clean. R12 found repository documentation/release-identity drift (`README.md` and `STATUS.md` still naming T19 as current) and its frozen correction updates only repository evidence plus a permanent regression. T20 remains open after R12; no R13+ result is claimed here.

## Historical T19 twenty-round result

R1–R9 were defect-bearing and were corrected/retested before R10. R10 was clean. R11 was defect-bearing; R12–R17 were clean; R18 and R19 were defect-bearing; R20 was defect-bearing at closure/release-hygiene level. Every defect-bearing round was reviewed completely before its ledger was frozen and its correction batch began.

The final T19 R20 correction was release/repository hygiene and documentation truth; it did not convert repository evidence into staging or live evidence. Automated-QA Green and Packaged apply only when the **current exact HEAD** has a successful canonical PHP 7.4/PHP 8.3 quality run and reproducible independently verified candidate package.

## Source implementation state

The candidate implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24` while preserving File 08 ownership boundaries. Current source includes clinic identity/branches/services/fees, timezone/DST-aware availability and slots, atomic holds, appointment lifecycle, patient/guardian/doctor/delegated-staff authorization, consent, secure continuity, review eligibility, calendar/payment/complaint adapters, privacy/audit/outbox/observability, migration/rollback and recovery, accessibility/localization, and Future24 scheduling/interoperability.

## Evidence-state classification

| State | Repository evidence rule |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Complete candidate** — current T20 source review/correction sequence is still in progress on an already implemented candidate. |
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

T13–T19, earlier corrective cycles, original-archive manifests/checksums and their embedded exact-head/schema values are historical provenance only. Historical regression labels such as **Fifteenth fresh 20-round main-cycle closure**, **Sixteenth fresh 20-round sequential audit**, and **Current sixteenth-cycle runtime alignment** are retained only where old regression evidence requires them; they are not current release identity.
