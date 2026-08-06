# File 08 Status

## Current state

**Runtime `1.0.1` — four-round reviewed and corrected 2026 master-plan source candidate, automatically verified and reproducibly packaged; external Hostinger staging and production acceptance remain pending.**

## Canonical identity

- Module: **File 08 — Worldwide Clinic and Appointments**
- Runtime: `1.0.1`
- Schema: `3.1.0`
- Text domain: `worldwide-clinic-appointments`
- Canonical implementation prefix: `WCA_`
- Audited compatibility prefix: `SWC_`
- Platform commission: **0%**
- Public Clinic Projection Contract: `1.1.0`
- CF-01 Scheduling-Only Care Context Contract: `1.1.0`

## Completed source scope

- Original `0.1.0` baseline and independent 32-finding audit evidence preserved.
- All 32 prior critical/high/medium/low findings remain corrected and regression-covered.
- Four fresh plan-governed review rounds completed against the Definitive Master Plan 2026 v3.0, Comprehensive Master Plan 2026 v2.0 and File 08 Complete Master Plan 2026 v1.0.
- Complete clinic, branch, public/private location, service, fee and currency model.
- Institutional clinic draft, submission, review and activation gate; Doctor self-activation is prohibited.
- Authoritative Doctor and Founder eligibility boundaries through Files 00/03/07/09.
- Clinic-scoped branch, service and availability ownership with cross-tenant reassignment rejection.
- Versioned availability rules and exceptions, DST-safe slot projection and server-authoritative atomic expiring holds.
- Idempotent appointment requests and actor-specific lifecycle covering request, review, acceptance, decline, reschedule, reassignment, cancellation, check-in, completion and no-show.
- Optimistic versions, resource/advisory locks, collision prevention, replay resistance and immutable audit events.
- Guardian/current-actor binding, object-level access control and purpose-limited, step-up administrative access.
- Opaque public practitioner and appointment references; native numeric identifiers are excluded from public scheduling surfaces.
- Appointment-bound, compensation-safe rescheduling and stale/expired hold rejection.
- Appointment-processing consent separated from clinical treatment, publication, research and AI consent.
- Emergency diversion, expiring review eligibility, private calendar export, conditional payment-intent reference, complaint/case reference and notification outbox boundaries.
- CF-01 scheduling-only context explicitly denies treating relationship, chart read/write, prescription and break-glass authority.
- Strict public projection allow lists exclude phone, WhatsApp, email, native identifiers, appointments, patients, clinical-like narratives and private notes.
- REST object authorization, rate limiting, fail-closed dependencies, privacy export/erasure/retention/legal hold, no-store/noindex controls and safe uninstall.
- Metrics, health diagnostics, circuit breakers, traces, schema migration, rollback metadata, WP-CLI and operational runbooks.
- Responsive, green-first, RTL-aware and accessibility-oriented public/admin surfaces.
- Requirements traceability covers all `18/18` functional and `10/10` non-functional master-plan groups.

## Four review rounds

1. **Architecture, ownership and lifecycle:** institutional activation, tenant ownership and authority boundaries corrected.
2. **Security, privacy and authorization:** actor/guardian IDOR, blanket administration and native-ID exposure corrected.
3. **Scheduling integrity and resilience:** authoritative slot validation, atomic holds, reschedule compensation and eligibility expiry corrected.
4. **UI, accessibility, release and regression governance:** opaque UI references, version/schema parity, regression coverage and reproducible release controls completed.

Detailed evidence is recorded in `FOUR-ROUND-REVIEW-AND-CORRECTION-1.0.1.md`.

## Automated verification

The canonical GitHub Actions workflow performs:

- PHP 7.4 and PHP 8.3 syntax verification;
- JavaScript syntax verification;
- contract and state-law tests;
- three-plan static traceability and four-round regression checks;
- security and repository-hygiene checks;
- deterministic double build and byte comparison;
- embedded/detached manifest and checksum verification;
- independent candidate re-opening and payload verification;
- governed exact-head artifact upload.

A passing workflow proves repository source and package reproducibility only; it does not substitute for real staging acceptance.

## External acceptance gates not claimed by source

- Fresh installation on canonical Hostinger staging.
- Upgrade/migration rehearsal from the exact accepted legacy baseline and current staging data.
- Database backup restoration and rollback exercise.
- Real immutable Files 00/03/07/09/19/20/25 and CF-01 package integration.
- LiteSpeed/Hostinger private-cache and no-store validation.
- Real notification delivery and SMTP fallback validation.
- Real patient, Founder, eligible/ineligible/suspended/revoked Doctor and administrator workflows.
- Concurrent overlapping-acceptance and failure-injection runtime testing.
- Urdu RTL, cross-browser, mobile, keyboard, screen-reader, zoom, forced-colors, reduced-motion and manual WCAG acceptance.
- Privacy, security and professional review; Founder acceptance; controlled merge; live deployment and post-deployment monitoring.

## Release classification

- Baseline provenance: **PASS**
- Independent source audit: **COMPLETE**
- Prior corrective implementation: **COMPLETE — 32/32**
- Four fresh plan-governed review rounds: **COMPLETE**
- Defects found in those four source rounds: **CORRECTED AND REGRESSION-COVERED**
- Complete 2026 master-plan source implementation: **COMPLETE**
- Functional traceability: **COMPLETE — 18/18**
- Non-functional traceability: **COMPLETE — 10/10**
- Automated source verification: **PASS when the exact-head workflow is green**
- Reproducible candidate engineering: **IMPLEMENTED**
- Hostinger staging acceptance: **PENDING EXTERNAL EVIDENCE**
- Production release: **NOT AUTHORIZED**
- Live installation: **NOT AUTHORIZED**

## Governing next step

Install only the exact CI-generated `1.0.1` candidate and immutable reviewed dependency packages on canonical Hostinger staging, then execute `docs/STAGING-ACCEPTANCE-1.0.0.md`. Every discovered runtime defect must be corrected, followed by fresh exact-head regression, package, migration, restore, rollback and integration verification before production authorization.
