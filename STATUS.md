# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Working review branch: `review/file08-t19-twenty-round-2026-09-06`
- Current review discipline: **T19 fresh 20-round Review → Ledger Freeze → Fix → Regression → Exact-head CI → Next Round**
- Runtime candidate: **1.2.15**
- Core File 08 schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 additive operational schema/contract: **1.1.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**

The authoritative repository release identity is the exact final branch HEAD together with its exact-head GitHub Actions run, deterministic candidate manifest, artifact digest and candidate SHA-256. The manifest must carry and independently verify the same runtime, plan, core schema, continuity schema/contract, Future24 schema/contract, Public Clinic contract and CF-01 contract embedded in the exact artifact.

Documentation history, older commits, older artifacts and prior green CI are not substitutes for the exact current HEAD.

## Source implementation state

The current candidate implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24` while preserving File 08 canonical ownership boundaries. The runtime includes clinic identity/branches/services/fees, timezone/DST-aware availability and slots, atomic holds, appointment lifecycle, patient/guardian/doctor/delegated-staff authorization, consent, secure continuity, review eligibility, calendar/payment/complaint adapters, privacy/audit/outbox/observability, migration/rollback metadata, accessibility/localization, and the Future24 scheduling/interoperability layer.

Historical audit cycles remain evidence of their own exact historical heads only; their branch names, schemas, CI runs, package hashes or closure statements do not describe the current T19 candidate.

## Current T19 sequential review state

- R1 completed review first, froze its defect ledger, corrected the complete R1 batch, and passed full PHP 7.4/PHP 8.3 source regression plus deterministic package verification.
- R2 completed a separate workflow/release-automation review, froze its ledger, retired obsolete self-mutating T17/T18 one-shot workflows, and passed exact-head PHP 7.4/PHP 8.3 source QA plus reproducible candidate verification.
- R3 is the current release/version/schema/contract/documentation-truth review. Any R3 correction invalidates earlier exact-head package evidence for final-release purposes and requires a new exact-head regression/package pass before R4 begins.

## Evidence-state classification

| State | Current repository evidence |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Candidate under T19 sequential review.** Current source is reviewable; final T19 closure has not yet occurred. |
| Fresh post-final-code reviews | **Pending until the final T19 code/documentation correction is complete.** |
| Packaged | **Head-specific only.** Earlier successful T19 R1/R2 package runs become historical when the branch changes; final package evidence must be regenerated on final T19 HEAD. |
| Automated-QA Green | **Head-specific only.** Earlier successful T19 R1/R2 runs do not prove a later corrected HEAD. |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Unverified / not claimed.** |
| Operational | **Not claimed.** |

## Mandatory staging / production gates

The exact final CI artifact must be installed on canonical Hostinger staging. Record the exact package checksum, plugin version, core/continuity/Future24 schemas, DB/migration state, active configuration and exact companion versions for Files 00/03/07/09/17/19/20/24/25/26. Staging acceptance must cover fresh install and real upgrade/migration; backup/restore and rollback; patient/guardian/doctor/delegated-staff/admin journeys; allowed and forbidden transitions; stale/duplicate/replay and concurrency cases; payment-payer and doctor/clinic-scope denial cases; timezone/DST boundaries; File17/File19/File26 integration; provider outage/dead-letter behavior; private no-store/noindex/cache behavior; mobile/desktop, Urdu/Arabic RTL, English LTR, keyboard/screen-reader/zoom/reflow/forced-colors/reduced-motion; and Founder acceptance.

Only after controlled production deployment may live re-test and repository/deployed parity confirmation begin.

## Live truth

This repository status does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum and post-deploy behavior must be independently frozen and verified before any live/operational assertion.

## Historical evidence note

Earlier T13–T18 and prior-cycle evidence files are intentionally retained as historical provenance. Their embedded schema values and exact heads apply only to the historical state they explicitly name. For current release decisions, the top-level current candidate section above and the final T19 exact-head CI/package evidence are governing.

Historical checkpoint label retained for permanent regression provenance: **Fifteenth fresh 20-round main-cycle closure**. This label records an older closed audit only; it does not assert that its historical schema/head/package is current.

Historical checkpoint label retained for permanent regression provenance: **Sixteenth fresh 20-round sequential audit**. This also refers only to the older T16 closure and is not the current T19 release identity.