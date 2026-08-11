# Changelog

## 1.2.9 — 2026-08-11

- Completed the ninth fresh sequential 10-round corrective cycle against exact v1.2.8 repository state.
- Replaced browser-visible practitioner numeric references with opaque references and removed global doctor enumeration from mutation authorization.
- Made locked appointment mutations transactional and transition events/outbox/review eligibility/audit fail closed.
- Made Future24 governance-audited record creation fail closed and corrected waitlist offer, support participant revocation, virtual-room and group-cancellation atomicity.
- Runtime 1.2.9; core schema remains 3.2.0; continuity schema 1.1.0; Future24 schema 1.0.0.
- Added a permanent ninth-ten-review regression gate; staging/live evidence remains separate.

## 1.2.8 — 2026-08-11

- Completed the eighth fresh sequential 10-round corrective cycle.
- Removed artificial total-page ceilings from doctor-suspension and verification reconciliation.
- Made due follow-up reminders complete, transactionally claimed and failure-aware.
- Replaced silent Future24 operational payload truncation with explicit overflow errors.
- Serialized practitioner-reference, waitlist, flexible-window and support-participant semantic creation.
- Corrected multi-rule/timezone slot starvation and exact held-slot replay/reprojection.
- Support/interpreter participant creation is atomic with File17 projection.
- Runtime 1.2.8; core schema 3.2.0; continuity schema 1.1.0; Future24 schema 1.0.0.
- Added a permanent eighth-ten-review regression gate; staging/live evidence remains separate.

## 1.2.7 — 2026-08-11

- Completed a seventh fresh sequential 10-round review-and-correct cycle against exact v1.2.6 repository state.
- Corrected continuity privacy erasure so records beyond the first 100 are processed with stable cursor progression and truthful completion state.
- Removed legal-hold starvation from continuity and Future24 retention by complete keyset-bounded traversal.
- Replaced silent tail truncation with explicit overflow errors for scheduling windows, prerequisite policies, follow-up resources, and episode appointment chains.
- Removed the first-100 prerequisite evidence ceiling and the 2,000-appointment caps from heatmap and no-show aggregate calculations.
- Advanced runtime identity to 1.2.7 without schema inflation: core 3.2.0, continuity 1.1.0, Future24 1.0.0.
- Added a permanent seventh-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

## 1.2.6 — 2026-08-11

- Completed a sixth fresh sequential 10-round review-and-correct cycle against exact v1.2.5 repository state.
- Moved current doctor eligibility into the canonical doctor-to-clinic serving relationship root.
- Added stable outbox message identity and abandoned-processing lease recovery/dead-letter progression.
- Removed remaining fixed-window truncation from waitlist offers, questionnaire templates, prerequisite rules and follow-up listing.
- Corrected monthly recurrence month-end drift by preserving the originating day-of-month and clamping only when the target month is shorter.
- Advanced runtime identity to 1.2.6 without schema inflation: core 3.2.0, continuity 1.1.0, Future24 1.0.0.
- Added a permanent sixth-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

## 1.2.5 — 2026-08-11

- Completed a fifth fresh sequential 10-round review-and-correct cycle against exact v1.2.4 repository state.
- Corrected Future24 public service-reference lookup and actor-independent doctor-to-clinic serving authority, including slot/hold rechecks.
- Added semantic MySQL advisory locks for arrival and virtual-room de-duplication across different replay keys.
- Removed fixed-count truncation from guardian-family, disruption affected-set, and slot-policy evaluation by bounded paging.
- Replaced permissive Future24 canonical timestamp parsing with strict round-trip parsing; tightened waitlist dates and nested REST calendar/DTO fail-closed depth behavior.
- Advanced runtime identity to 1.2.5 without schema inflation: core 3.2.0, continuity 1.1.0, Future24 1.0.0.
- Added a permanent fifth-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

## 1.2.4 — 2026-08-11

- Completed a fourth fresh sequential 10-round review-and-correct cycle against exact v1.2.3 source state.
- Persisted canonical branch identity through slot holds, appointment creation and reschedule confirmation; isolated slot queries by clinic.
- Added Future24 group leave/cancel semantics and current clinic/service/start-time revalidation.
- Replaced permissive signed-calendar timestamp parsing with strict UTC validation.
- Corrected payment-intent provider reference uniqueness and added canonical service-root idempotency with migration-safe nullable keys.
- Enforced privacy/emergency/teleconsult consent at the service root and expanded appointment replay fingerprints to bind meaningful request semantics.
- Required activation step-up, current clinic-owner eligibility, and active public branch/eligible-service inventory.
- Replaced the 500-record doctor-suspension ceiling with bounded paged reconciliation.
- Advanced core schema to 3.2.0 and runtime to 1.2.4; continuity and Future24 schemas remain unchanged.
- Added a permanent fourth-ten-review regression gate; repository/package/CI, staging, live and operational evidence remain separate states.

## 1.2.3 — 2026-08-11

- Completed a third fresh sequential 10-round review-and-correct cycle against the corrected v1.2.2 source state.
- Removed canonical repository stale-idempotency auto-takeover and aligned the HTTP stale guard with the actual `http_` reservation scope.
- Enforced patient/current-guardian payment authority and explicit expected-status/version transition preconditions at canonical service roots.
- Applied private no-store/noindex headers to all protected core mutations and strictly validated persisted UTC appointment timestamps before ICS generation.
- Made outbox row claiming atomic by rechecking pending/retry, schedule and lock eligibility in the claim UPDATE itself.
- Required current doctor-to-clinic serving authority for service and availability assignment; global verification/eligibility alone is insufficient.
- Advanced runtime/document identity to `1.2.3`; core schema remains `3.1.0`, restricted continuity schema `1.1.0`, Future24 schema `1.0.0`.
- Added a permanent third-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

## 1.2.2 — 2026-08-11

- Opened and completed a second fresh sequential 10-round corrective audit after the v1.2.1 post-closure cycle; each discovered defect was corrected before the next review proceeded.
- Required purpose-limited `operations` access plus recent step-up before the canonical administrator actor may transition appointments.
- Required explicit slot-hold idempotency keys and namespaced repository replay identity by authorized patient to prevent cross-account client-key collision.
- Added fail-closed handling for ambiguous stale `processing` idempotency reservations on both the governed appointment command and the cross-cutting REST mutation boundary; stale claims are not automatically stolen.
- Added strict Future24 date/timestamp validation and recursive removal of native numeric identifiers from Future24 REST responses.
- Serialized outbox dispatch with a MySQL advisory lock so cron, shutdown, or overlapping workers cannot concurrently claim/finalize the same outbox work.
- Retained supported entry-point transition state/version preconditions, patient/current-authorized-guardian payment-intent authority, doctor-to-clinic availability scope, delegated staff bounds, timezone/DST correctness, branch audit evidence and File26 projection invalidation.
- Advanced runtime/document identity to `1.2.2`; core schema remains `3.1.0`, restricted continuity schema `1.1.0`, Future24 schema `1.0.0`.
- Added a permanent second-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

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
