# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Branch: `codex/file08-new-governing-plans-completion-2026`
- Runtime candidate: **1.2.4**
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

## Fourth fresh 10-round corrective audit

A fourth fresh sequential 10-round review-and-correct cycle was completed on the corrected v1.2.3 repository state. Every proved defect was corrected before the next round. The cycle adds or strengthens:

- canonical `branch_id` persistence in slot holds and propagation through booking/rescheduling, with live branch-scope revalidation;
- clinic-isolated availability-rule lookup and slot discovery, including service-to-clinic scope validation;
- Future24 group-session leave/cancel semantics with advisory locking, optimistic updates, audit evidence, and live clinic/service/start-time rechecks;
- strict round-trip UTC validation in signed calendar-link generation, not only the primary calendar export path;
- migration-safe payment-intent uniqueness using nullable provider references plus request-key idempotency at the canonical service root;
- explicit privacy/emergency/telehealth consent validation in direct/internal appointment service calls and replay fingerprints that bind the actual request semantics;
- clinic activation step-up, current owner-doctor eligibility, and publishable branch/service inventory revalidation;
- bounded paged doctor-suspension reconciliation so appointment authority holds are not silently truncated at 500 records;
- runtime/document/test alignment at **1.2.4**, core schema **3.2.0**, continuity schema **1.1.0**, and Future24 schema **1.0.0**;
- a permanent `tests/fourth-ten-review-regressions.php` gate covering the new invariants.

Supported public/cross-file mutation paths continue to enforce transition expected-state/version preconditions, patient/current-authorized-guardian payment authority, doctor-to-clinic availability scope, stale replay fail-closed behavior, protected-response cache controls, and canonical ownership boundaries. Direct companion-table ownership remains prohibited.

## Evidence-state classification

| State | Current repository evidence |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Corrected candidate** — fourth-cycle source corrections are present. |
| Fresh post-final-code reviews | **Complete at source-review level; exact-final-HEAD CI/package reopening remains the release gate.** |
| Packaged | **Pending exact-final-HEAD CI artifact confirmation.** |
| Automated-QA Green | **Pending exact-final-HEAD workflow confirmation.** |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Not claimed.** |
| Operational | **Pending.** |

Do not promote this candidate to Packaged or Automated-QA Green using any older v1.2.1/v1.2.2/v1.2.3 artifact. Rebuild and reverify from the exact final HEAD.

## Mandatory staging / production gates

The exact final CI artifact must be installed on canonical Hostinger staging. Record the exact package checksum, plugin version, core/continuity/Future24 schemas, DB/migration state, active configuration and exact companion versions for Files 00/03/07/09/17/19/20/24/25/26. Staging acceptance must cover fresh install and real upgrade/migration; backup/restore and rollback; patient/guardian/doctor/delegated-staff/admin journeys; allowed and forbidden transitions; stale/duplicate/replay and concurrency cases; payment-payer and doctor/clinic-scope denial cases; timezone/DST boundaries; File17/File19/File26 integration; provider outage/dead-letter behavior; private no-store/noindex/cache behavior; mobile/desktop, Urdu/Arabic RTL, English LTR, keyboard/screen-reader/zoom/reflow/forced-colors/reduced-motion; and Founder acceptance.

Only after controlled production deployment may live re-test and repository/deployed parity confirmation begin.

## Live truth

This repository status does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum, and post-deploy behavior must be independently frozen and verified before any live/operational assertion.
