# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Branch: `codex/file08-new-governing-plans-completion-2026`
- Runtime candidate: **1.2.9**
- Core File 08 schema: **3.2.0**
- Restricted continuity schema: **1.1.0**
- Future24 additive operational schema: **1.0.0**
- File plan contract: **SSH-F08-PLAN-2026-v1.0**
- Future24 amendment contract: **1.0.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Platform commission: **0%**

The authoritative repository release identity is the exact final branch HEAD together with its exact-head GitHub Actions run, deterministic candidate manifest, artifact digest, and candidate SHA-256. Documentation history, older commits, older artifacts, or prior green CI are not substitutes for that exact final identity.

## Source implementation state

The current candidate implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24` while preserving the File 08 ownership boundary. The runtime includes clinic identity/branches/services/fees, timezone/DST-aware availability and slots, atomic holds, appointment lifecycle, patient/guardian/doctor/delegated-staff authorization, consent, secure continuity, review eligibility, calendar/payment/complaint adapters, privacy/audit/outbox/observability, migration/rollback metadata, accessibility/localization, and the Future24 scheduling/interoperability layer.

## Ninth fresh 10-round corrective audit

A ninth fresh sequential review-and-correct cycle was run against the exact v1.2.8 corrected repository state. Each supported defect was corrected and the complete source QA suite passed before the next round began. The cycle closes opaque browser practitioner-reference leakage, O(N) doctor enumeration in mutation authorization, fail-open locked mutation audit behavior, transition event/outbox/review-eligibility partial commits, Future24 governance-audit fail-open record creation, waitlist-offer duplicate/projection races, support-participant revocation projection gaps, virtual-room projection gaps, and non-atomic group cancellation. Runtime is 1.2.9 while core schema remains 3.2.0, continuity schema 1.1.0 and Future24 schema 1.0.0. Repository evidence does not prove Hostinger staging or live state.

## Evidence-state classification

| State | Current repository evidence |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Corrected candidate** — ninth-cycle source corrections are present. |
| Fresh post-final-code reviews | **Complete at repository/source-review level.** |
| Packaged | **Automated deterministic candidate packaging required and verified on the exact candidate HEAD before release use.** |
| Automated-QA Green | **Exact-candidate-HEAD workflow success is mandatory and is the only valid automated-QA evidence for this status.** |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Not claimed.** |
| Operational | **Pending.** |

No older v1.2.1/v1.2.2/v1.2.3/v1.2.4/v1.2.5/v1.2.6/v1.2.7/v1.2.8 artifact or older CI run may be used as evidence for the v1.2.9 candidate. Package and QA status are valid only when the candidate manifest, artifact and workflow all identify the exact same final commit.

## Mandatory staging / production gates

The exact final CI artifact must be installed on canonical Hostinger staging. Record the exact package checksum, plugin version, core/continuity/Future24 schemas, DB/migration state, active configuration and exact companion versions for Files 00/03/07/09/17/19/20/24/25/26. Staging acceptance must cover fresh install and real upgrade/migration; backup/restore and rollback; patient/guardian/doctor/delegated-staff/admin journeys; allowed and forbidden transitions; stale/duplicate/replay and concurrency cases; payment-payer and doctor/clinic-scope denial cases; timezone/DST boundaries; File17/File19/File26 integration; provider outage/dead-letter behavior; private no-store/noindex/cache behavior; mobile/desktop, Urdu/Arabic RTL, English LTR, keyboard/screen-reader/zoom/reflow/forced-colors/reduced-motion; and Founder acceptance.

Only after controlled production deployment may live re-test and repository/deployed parity confirmation begin.

## Live truth

This repository status does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum, and post-deploy behavior must be independently frozen and verified before any live/operational assertion.
