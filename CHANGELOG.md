# Changelog

## 1.2.1 — 2026-08-11

- Closed the requested 80-round corrective cycle and then completed a separate 10-round post-closure review-and-correct cycle against the current File 08/Future24 and platform governance boundaries.
- Added cross-cutting canonical core-mutation hardening: explicit idempotency keys, full-request replay fingerprints, bounded rate limiting, and transition expected-state/record-version preconditions.
- Restricted payment-intent creation to the patient or currently authorized guardian and revalidated appointment object access.
- Added doctor-to-clinic scope validation for availability writes, including explicit scoped-delegation and integration-filter support.
- Corrected modern frontend canonical appointment detail URLs and added a safe plural opaque-UUID compatibility redirect; disabled legacy native-numeric browser mutation workflows by default.
- Corrected delegated clinic-staff appointment and clinic-management views without broadening companion ownership.
- Corrected worldwide display-timezone date-window slot discovery and cross-timezone/DST-boundary slot-hold revalidation.
- Added `ClinicBranchChanged.v1` audit/domain evidence and File 26 search-projection invalidation after branch creation.
- Updated current repository/readme/status/staging documentation to runtime `1.2.1`, exact-head artifact binding, current companion boundaries, and Sabri Green `#087A4E` acceptance rather than superseded orange guidance.
- Added a permanent ten-round regression gate; repository, package, staging, and live evidence remain separate states.

## 1.0.1 — 2026-08-06

- Completed four fresh review-and-correction rounds against the Definitive Master Plan 2026 v3.0, Comprehensive Master Plan 2026 v2.0 and File 08 Complete Master Plan 2026 v1.0.
- Corrected clinic review/activation governance, actor and guardian authorization, cross-clinic scope, opaque public identifiers, atomic slot integrity, compensation-safe rescheduling and expiring review eligibility.
- Added the plan guard, four-round regression evidence, schema `3.1.0` and deterministic runtime `1.0.1` release parity.

## 1.0.0 — 2026-08-06

- Harmonized File 08 with the Complete Master Plan 2026 v1.0.
- Added canonical WCA contracts, schema, repository, service, authorization, REST, routes, frontend, operations, privacy, outbox, observability, compatibility and WP-CLI layers.
- Added clinic entities/branches/services/fees, availability/slot holds, canonical appointment lifecycle, consent, review eligibility, complaint/payment/calendar/clinical-boundary adapters and zero-commission enforcement.
- Migrated legacy appointment status vocabulary while preserving audit evidence.
- Adopted canonical text domain and green-first accessible responsive RTL visual system.
- Added master-plan/security/state tests and deterministic release packaging.
- Preserved non-destructive uninstall and explicit external staging/Founder/production gates.

## 0.2.2

- Added CF-01 scheduling-only care-context contract and exact-head automated candidate verification.

## 0.2.1

- Corrected all 32 independent audit findings and added authoritative public clinic projection.

## 0.1.0

- Original imported foundation preserved as baseline evidence.
