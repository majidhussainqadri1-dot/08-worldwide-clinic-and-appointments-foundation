# File 08 — Worldwide Clinic and Appointments

Canonical repository implementation of **File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026** plus the approved **Future Clinic Intelligence & Interoperability 24** amendment for the Sabri Social Homeopathy Platform.

## Canonical repository identity

- Repository review branch: `review/file08-t21-ten-round-2026-09-09`
- Current review cycle: **T24 closure cycle — Review → Ledger Freeze → Fix → Regression → Exact-head CI/package → Next Round**
- Current ten-round continuation: **T23 R10 clean; T24 R1 defect-bearing/corrected; T24 R2–R8 clean; T24 R9 defect-bearing/corrected at repository-documentation level.** Exact-head CI/package status is defined only by the current canonical workflow and is never inferred from an older run recorded in a static document.
- Historical T22: **R1–R10 closed**; its former exact heads/runs are historical provenance only.
- Historical T21: **R1–R10 closed**; defect-bearing R1–R6, R9, R10; clean R7–R8.
- Package folder: `08-worldwide-clinic-and-appointments`
- WordPress plugin entry: `worldwide-clinic.php`
- Runtime candidate: **1.2.15**
- Core schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 additive schema/contract: **1.1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Plan contract: **SSH-F08-PLAN-2026-v1.0**
- Text domain: `worldwide-clinic-appointments`
- Canonical PHP prefix: `WCA_`
- Legacy compatibility prefix: `SWC_`
- Platform commission: **0%**

The authoritative repository release identity is the **exact candidate HEAD + exact-head CI run + deterministic manifest + candidate SHA-256**. The deterministic manifest must also match the runtime, plan, core schema, continuity schema/contract, Future24 schema/contract, Public Clinic contract and CF-01 contract embedded in that exact artifact. Repository evidence never proves the current staging or live installation.

## Governing ownership boundaries

File 08 owns clinic identity, branches, services/fees, availability, slot/appointment state, scheduling relationship eligibility, clinical-safety scheduling gates, and completed-appointment review eligibility. File 00 owns identity/age/guardian truth; File 09 doctor verification; File 17 messaging/calls/virtual-room transport; File 19 notification delivery; File 20 the global shell; File 24 assurance/security governance; File 25 visual tokens; and File 26 search/discovery/ranking.

The runtime does not introduce automated diagnosis/prescribing, emergency-service replacement, paid/donor visibility advantage, hidden individual patient scoring, or direct ownership of companion tables.

## Implemented scope

The current source implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24`, including clinic identity and activation, branches, services/fees, timezone/DST-safe availability and slot projection, atomic holds, appointment request/decision/reschedule/cancel/check-in/complete/no-show lifecycle, patient/guardian/doctor/delegated-staff authorization, opaque protected references, emergency diversion, versioned consent, secure continuity, expiring review eligibility, calendar/payment/complaint adapters, privacy lifecycle, audit/outbox/observability, migration/rollback metadata, accessibility/localization, waitlist/series/resource/group scheduling, readiness/prerequisite governance, queue/disruption/support/interpreter contracts, consent-gated File 17 virtual-room requests, privacy-safe interoperability adapters, external busy projections, and episode chains.

T20 materially hardened the same 1.2.15 candidate after T19. T21 and T22 added further authorization, reconciliation, release-evidence and localization hardening. T23/T24 then re-reviewed financial, migration, authorization, scheduling, privacy, discovery, provider/outbox, frontend and release-truth boundaries. T24 R1 corrected uncertain-transaction replay handling for CF02 complaint status, CF03 payment-status projection and the Future24 generic idempotent mutation wrapper; T24 R9 corrected stale release-currentness documentation. Earlier cycles remain historical provenance rather than current candidate identity.

## Canonical routes

- `/clinic/{clinic_slug}`
- `/appointments/book/{opaque_practitioner_ref}`
- `/appointments`
- `/clinic/dashboard`
- `/appointment/{public_ref}`

An old plural appointment-detail alias containing an opaque UUID is redirected to the canonical singular detail route. Legacy numeric browser mutation workflows are disabled by default and may only be re-enabled through an explicit migration filter.

## Verification

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
node --check assets/js/clinic.js
node --check assets/js/continuity.js
node --check assets/js/future24.js
php tests/run-all.php
```

The canonical GitHub Actions quality workflow repeats PHP 7.4/8.3 syntax and source-contract/security tests, JavaScript syntax, repository hygiene, deterministic double build/byte comparison, exact-commit manifest verification, runtime/schema/contract parity verification, candidate reopening, checksum verification, and artifact upload.

## Evidence-state law

A repository candidate may be called **Coded**, **Packaged**, or **Automated-QA Green** only when the respective evidence applies to the exact same HEAD. It is not `Staging-Accepted`, `Live-Deployed`, or `Operational` from repository evidence alone.

T24 is the current closure cycle. Any correction changes HEAD and therefore requires a fresh exact-head canonical CI/package run before that corrected HEAD may inherit `Packaged` or `Automated-QA Green` status. Static documentation deliberately does not hard-code a future CI result; consult the canonical workflow on the exact HEAD being evaluated.

Before production, run `STAGING-ACCEPTANCE.md` against the exact CI artifact on canonical Hostinger staging, recording actual DB/schema/migration state, companion-package parity, real-role journeys, concurrency/replay/provider-outage behavior, privacy/cache/accessibility acceptance, backup/restore/rollback and Founder acceptance. After an explicitly authorized production deployment, freeze the exact deployed artifact/version/schema/migration state and perform live parity re-test before any live resolution claim.
