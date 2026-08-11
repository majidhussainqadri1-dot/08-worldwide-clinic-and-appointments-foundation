# File 08 — Worldwide Clinic and Appointments

Canonical repository implementation of **File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026** plus its approved Future Clinic Intelligence & Interoperability 24 amendment for the Sabri Social Homeopathy Platform.

## Canonical identity

- Repository/package folder: `08-worldwide-clinic-and-appointments`
- WordPress plugin entry: `worldwide-clinic.php`
- Runtime candidate: **1.2.2**
- Core schema: **3.1.0**
- Restricted continuity schema: **1.1.0**
- Future24 additive operational schema: **1.0.0**
- Text domain: `worldwide-clinic-appointments`
- Canonical PHP prefix: `WCA_`
- Legacy compatibility prefix: `SWC_`
- Platform commission: **0%**

The authoritative repository release identity is the **exact candidate HEAD + exact-head CI run + deterministic manifest + candidate SHA-256**. Repository evidence never proves the current staging or live installation.

## Governing ownership boundaries

File 08 owns clinic identity, branches, services/fees, availability, slot/appointment state, scheduling relationship eligibility, clinical-safety scheduling gates, and completed-appointment review eligibility. File 00 owns identity/age/guardian truth; File 09 doctor verification; File 17 messaging/calls/virtual-room transport; File 19 notification delivery; File 20 the global shell; File 24 assurance/security governance; File 25 visual tokens; and File 26 search/discovery/ranking.

The runtime does not introduce automated diagnosis/prescribing, emergency-service replacement, paid/donor visibility advantage, hidden individual patient scoring, or direct ownership of companion tables.

## Implemented scope

The current source implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24`: institutional clinic activation, branches, services/fees, availability, timezone/DST-safe slot projection, server-authoritative holds, explicit mutation idempotency/replay protection, canonical appointment lifecycle, compensation-safe rescheduling, patient/guardian/doctor/delegated-staff authorization, opaque protected references, emergency diversion, consent, secure continuity, expiring review eligibility, calendar, payment/complaint adapters, privacy lifecycle, audit/outbox/observability, migration/rollback metadata, accessibility/localization, waitlist/series/resource/group scheduling, readiness/prerequisite governance, queue/disruption/support/interpreter contracts, consent-gated File 17 virtual-room requests, privacy-safe interoperability adapters, external busy projections, and episode chains.

The first post-closure 10-round review corrected canonical detail routing, legacy native-ID browser mutation surfaces, delegated clinic-staff appointment/dashboard visibility, transition state/version preconditions, core mutation rate/replay behavior, payment-payer authority, doctor-to-clinic availability scope, display-timezone boundary slot discovery, hold reprojection across timezone/DST day boundaries, and branch-change audit/File26 projection events.

The **second fresh 10-round corrective audit** further hardens administrator transition purpose/step-up checks, requires explicit slot-hold replay keys, namespaces hold replay identity by patient, fails closed on ambiguous stale mutation reservations, strictly validates Future24 date/time inputs, removes native numeric identifiers from Future24 REST DTOs, and serializes outbox dispatch so cron/shutdown workers cannot overlap. Every supported mutation entry point remains guarded by authorization, rate/replay controls, and state/object constraints.

## Canonical routes

- `/clinic/{clinic_slug}`
- `/appointments/book/{opaque_practitioner_ref}`
- `/appointments`
- `/clinic/dashboard`
- `/appointment/{public_ref}`

An old plural appointment-detail alias containing an opaque UUID is redirected to the canonical singular detail route. Legacy numeric browser mutation workflows are disabled by default and may only be re-enabled through an explicit migration filter.

## Verification

Run repository checks with:

```bash
find . -type f -name '*.php' -not -path './vendor/*' -print0 | xargs -0 -n1 php -l
node --check assets/js/clinic.js
node --check assets/js/continuity.js
node --check assets/js/future24.js
php tests/run-all.php
```

The GitHub Actions quality workflow repeats PHP 7.4/8.3 syntax and source-contract/security tests, JavaScript syntax, repository hygiene, deterministic double build/byte comparison, exact-commit manifest verification, candidate reopening, checksum verification, and artifact upload.

## Evidence-state classification

The repository candidate may be called **Coded**, **Packaged**, or **Automated-QA Green** only after those respective exact-head gates actually pass. It is not `Staging-Accepted`, `Live-Deployed`, or `Operational` from repository evidence alone.

Before production, complete `STAGING-ACCEPTANCE.md` against the exact CI artifact on canonical Hostinger staging, including actual DB/schema/migration state, companion-package parity, real-role journeys, concurrency/replay/provider-outage behavior, privacy/cache/accessibility acceptance, backup/restore/rollback, Founder acceptance, controlled deployment, and post-deploy live parity re-test.
