# File 08 — Worldwide Clinic and Appointments

Canonical repository implementation of **File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026 v1.0** for the Sabri Social Homeopathy Platform.

## Canonical identity

- Repository/package folder: `08-worldwide-clinic-and-appointments`
- WordPress plugin entry: `worldwide-clinic.php`
- Runtime: `1.0.1`
- Schema: `3.1.0`
- Text domain: `worldwide-clinic-appointments`
- Canonical PHP namespace prefix: `WCA_`
- Legacy compatibility prefix: `SWC_`
- Platform commission: **0%**

## Governing review basis

Runtime `1.0.1` has completed four fresh source-level review-and-correction rounds against:

1. Definitive Master Plan 2026 v3.0;
2. Comprehensive Master Plan 2026 v2.0;
3. File 08 Complete Master Plan 2026 v1.0.

The detailed defect and correction record is `FOUR-ROUND-REVIEW-AND-CORRECTION-1.0.1.md`.

## Implemented scope

The runtime implements institutional clinic identity and activation, branches and public/private locations, tenant-scoped services and fees, availability rules, DST-safe slot projection, server-authoritative atomic holds, idempotent appointment requests, canonical appointment state law, compensation-safe rescheduling, cancellation, check-in, completion and no-show, patient/doctor/clinic dashboards, actor and guardian authorization, purpose-limited administration, opaque public scheduling references, emergency diversion, versioned consent, expiring verified-completion review eligibility, private ICS calendar export, conditional payment-intent bridge, CF-01 scheduling-only clinical boundary, File 17 context events, File 19 notifications with privacy-minimal fallback, CF-02 complaint bridge, transactional outbox, privacy export/erasure/retention/legal holds, audit events, metrics, traces, circuit breakers, schema migration, rollback metadata, health checks, accessible responsive RTL UI, REST API, WP-CLI and deterministic release engineering.

## Canonical routes

- `/clinic/{clinic_slug}`
- `/appointments/book/{opaque_practitioner_ref}`
- `/appointments`
- `/clinic/dashboard`
- `/appointment/{public_ref}`

Protected routes and REST resources are private, `no-store`, `noindex`, object-authorized and fail closed. Public routes do not expose native numeric user, clinic or appointment identifiers.

## Verification

Run:

```bash
find . -type f -name '*.php' -print0 | xargs -0 -n1 php -l
node --check assets/js/clinic.js
php tests/run-all.php
```

GitHub Actions repeats these checks on PHP 7.4 and 8.3, runs the three-plan and four-round regression suites, builds the candidate twice, byte-compares it and independently verifies every payload hash, size, path, embedded manifest and detached checksum.

## Acceptance classification

`1.0.1` is a **source-complete, automatically verified candidate** after four fresh review-and-correction rounds. It is not marked staging-accepted or production-accepted by source code. Hostinger staging, exact dependency packages, real accounts, backup restoration, rollback rehearsal, browser/mobile/Urdu RTL/manual accessibility, privacy/security/professional review, Founder acceptance and live deployment require external evidence under `docs/STAGING-ACCEPTANCE-1.0.0.md`.
