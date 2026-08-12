# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Branch: `codex/file08-new-governing-plans-completion-2026`
- Runtime candidate: **1.2.14**
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

## Tenth fresh 10-round corrective audit

A tenth fresh sequential review-and-correct cycle was run against exact v1.2.9 repository state. R1-R9 corrected owner transaction/event/outbox atomicity across appointment creation, clinic lifecycle, branch, service, availability, complaint and payment flows; replay-finalization now fails closed with a caller-owned mutation-status query; public clinic discovery uses opaque cursor pagination and conditional ETag caching. R10 aligns runtime/tests/docs to v1.2.10 while schemas remain 3.2.0 / 1.1.0 / 1.0.0. Repository evidence remains distinct from staging/live evidence.

## Eleventh fresh 20-round corrective audit

Fresh sequential review against exact v1.2.10 source. R1-R15 corrected supported repository defects; R16 aligns runtime/tests/docs and permanent regression evidence to v1.2.11 without schema inflation. R17-R20 are corrected-state closure reviews. Repository evidence remains distinct from staging/live evidence.

## Evidence-state classification

| State | Current repository evidence |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Corrected candidate** — fourteenth-cycle source corrections and post-main corrections are present. |
| Fresh post-final-code reviews | **Complete at repository/source-review level for Fourteenth closure.** |
| Packaged | **Automated deterministic candidate packaging required and verified on the exact candidate HEAD before release use.** |
| Automated-QA Green | **Exact-candidate-HEAD workflow success is mandatory and is the only valid automated-QA evidence for this status.** |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Not claimed.** |
| Operational | **Pending.** |

No older v1.2.1 through v1.2.13 artifact or older CI run may be used as evidence for the v1.2.14 candidate. Package and QA status are valid only when the candidate manifest, artifact and workflow all identify the exact same final commit.

## Mandatory staging / production gates

The exact final CI artifact must be installed on canonical Hostinger staging. Record the exact package checksum, plugin version, core/continuity/Future24 schemas, DB/migration state, active configuration and exact companion versions for Files 00/03/07/09/17/19/20/24/25/26. Staging acceptance must cover fresh install and real upgrade/migration; backup/restore and rollback; patient/guardian/doctor/delegated-staff/admin journeys; allowed and forbidden transitions; stale/duplicate/replay and concurrency cases; payment-payer and doctor/clinic-scope denial cases; timezone/DST boundaries; File17/File19/File26 integration; provider outage/dead-letter behavior; private no-store/noindex/cache behavior; mobile/desktop, Urdu/Arabic RTL, English LTR, keyboard/screen-reader/zoom/reflow/forced-colors/reduced-motion; and Founder acceptance.

Only after controlled production deployment may live re-test and repository/deployed parity confirmation begin.

## Live truth

This repository status does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum, and post-deploy behavior must be independently frozen and verified before any live/operational assertion.

## Thirteenth fresh 20-round corrected-state closure

- Main review rounds: T13-R01 through T13-R20; all 20 contained a supported repository defect/gap and were corrected sequentially.
- Post-final corrective sweeps: legacy mutation persistence, repair/purge failure visibility, and canonical purge fail-closed semantics corrected.
- Two fresh post-coding source reviews: PASS / PASS, no new supported defect.
- Runtime: 1.2.13; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0.
- Staging-Accepted: false; Live-Deployed: unverified; Operational: not claimed.

## Fourteenth fresh 20-round corrected-state closure

- Frozen baseline: `fe10d05bdb4d1ffb726063f657f283097e65abbd` / runtime 1.2.13.
- Main review rounds: T14-R01 through T14-R20. Supported product defects were corrected in R01-R18; R19-R20 were clean corrected-state reviews at that point.
- Broader post-main sweep then found and corrected three additional repository defects: rule-local timezone/DST/effective-range heatmap projection, low-volume outcome privacy suppression, and deterministic signed stateless public clinic cursors for stable ETags.
- Because post-main source changed, the two required fresh post-coding reviews were restarted and both passed.
- Fourteenth corrected source/tooling cleanup checkpoint: `f976c7c8e64d4303a1e8ce07af97430b36551bc3`.
- Runtime: 1.2.14; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0.
- Temporary T14 workflows/scripts were removed; only the canonical quality workflow remains.
- Staging-Accepted: false; Live-Deployed: unverified; Operational: not claimed.
