# File 08 — Worldwide Clinic and Appointments — Candidate Status

## Current repository candidate

- Branch: `codex/file08-new-governing-plans-completion-2026`
- Runtime candidate: **1.2.1**
- Core File 08 schema: **3.1.0**
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

## Ten-round post-closure hardening

A separate sequential 10-round review-and-correct cycle was performed after the earlier 80-round closure. Runtime/source defects were corrected in the early/middle passes before the required final two fresh post-final-code review gates. The corrections include:

- canonical singular appointment-detail routing plus an opaque-UUID plural compatibility redirect;
- default suppression of legacy numeric browser mutation/shortcode workflows;
- delegated clinic-staff appointment and clinic-management visibility;
- explicit core-mutation idempotency keys, full-request replay fingerprints and bounded rate limiting;
- appointment transition expected-status/expected-version preconditions;
- payment-intent authority restricted to patient/currently authorized guardian;
- doctor-to-clinic availability-scope validation;
- display-timezone date-window slot discovery and cross-timezone/DST-boundary hold reprojection;
- branch-change audit/domain evidence and File 26 projection invalidation;
- current runtime/release/staging documentation parity.

The permanent `tests/ten-review-regressions.php` suite guards these invariants and is included in `tests/run-all.php`.

## Evidence-state classification

| State | Current repository evidence |
|---|---|
| Specified | **Complete** — governing File 08 + Future24 requirements mapped. |
| Coded | **Complete candidate** — current source includes the 10-round corrections. |
| Fresh post-final-code reviews | **Must be evidenced after the final runtime/source commit.** |
| Packaged | **Requires exact-final-HEAD CI artifact confirmation.** |
| Automated-QA Green | **Requires exact-final-HEAD workflow confirmation.** |
| Staging-Accepted | **Pending / not claimed.** |
| Live-Deployed | **Not claimed.** |
| Operational | **Pending.** |

Do not promote the repository to Packaged or Automated-QA Green based on an older `1.2.1` artifact after any source change. Rebuild and reverify from the exact final HEAD.

## Mandatory staging / production gates

The exact final CI artifact must be installed on canonical Hostinger staging. Record the exact package checksum, plugin version, core/continuity/Future24 schemas, DB/migration state, active configuration and exact companion versions for Files 00/03/07/09/17/19/20/24/25/26. Staging acceptance must cover fresh install and real upgrade/migration; backup/restore and rollback; patient/guardian/doctor/delegated-staff/admin journeys; allowed and forbidden transitions; stale/duplicate/replay and concurrency cases; payment-payer and doctor/clinic-scope denial cases; timezone/DST boundaries; File17/File19/File26 integration; provider outage/dead-letter behavior; private no-store/noindex/cache behavior; mobile/desktop, Urdu/Arabic RTL, English LTR, keyboard/screen-reader/zoom/reflow/forced-colors/reduced-motion; and Founder acceptance.

Only after controlled production deployment may live re-test and repository/deployed parity confirmation begin.

## Live truth

This repository status does not prove the current staging or live installation. Exact deployed plugin files/version, database/schema version, migration state, active configuration/dependencies, deployed artifact checksum, and post-deploy behavior must be independently frozen and verified before any live/operational assertion.
