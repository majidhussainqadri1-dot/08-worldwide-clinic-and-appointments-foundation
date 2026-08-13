# File 08 — Worldwide Clinic and Appointments

Canonical repository implementation of **File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026** plus its approved Future Clinic Intelligence & Interoperability 24 amendment for the Sabri Social Homeopathy Platform.

## Canonical identity

- Repository/package folder: `08-worldwide-clinic-and-appointments`
- WordPress plugin entry: `worldwide-clinic.php`
- Runtime candidate: **1.2.15**
- Core schema: **3.2.0**
- Restricted continuity schema: **1.1.0**
- Future24 additive operational schema: **1.0.0**
- Text domain: `worldwide-clinic-appointments`
- Canonical PHP prefix: `WCA_`
- Legacy compatibility prefix: `SWC_`
- Platform commission: **0%**

The authoritative repository release identity is the **exact candidate HEAD + exact-head CI run + deterministic manifest + candidate SHA-256**. Repository evidence never proves the current staging or live installation.

Current fifteenth-cycle runtime alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. All 20 main reviews are complete. R19 was clean; R20 found no new product-code defect and corrected closure-evidence lag. Two extra post-correction verification sweeps and exact-final-head CI/package evidence remain before repository closure.

## Governing ownership boundaries

File 08 owns clinic identity, branches, services/fees, availability, slot/appointment state, scheduling relationship eligibility, clinical-safety scheduling gates, and completed-appointment review eligibility. File 00 owns identity/age/guardian truth; File 09 doctor verification; File 17 messaging/calls/virtual-room transport; File 19 notification delivery; File 20 the global shell; File 24 assurance/security governance; File 25 visual tokens; and File 26 search/discovery/ranking.

The runtime does not introduce automated diagnosis/prescribing, emergency-service replacement, paid/donor visibility advantage, hidden individual patient scoring, or direct ownership of companion tables.

## Implemented scope

The current source implements `F08-FR-001…018`, `F08-NFR-001…010`, and `F08-FUT-01…24`: institutional clinic activation, branches, services/fees, availability, timezone/DST-safe slot projection, server-authoritative holds, explicit mutation idempotency/replay protection, canonical appointment lifecycle, compensation-safe rescheduling, patient/guardian/doctor/delegated-staff authorization, opaque protected references, emergency diversion, consent, secure continuity, expiring review eligibility, calendar, payment/complaint adapters, privacy lifecycle, audit/outbox/observability, migration/rollback metadata, accessibility/localization, waitlist/series/resource/group scheduling, readiness/prerequisite governance, queue/disruption/support/interpreter contracts, consent-gated File 17 virtual-room requests, privacy-safe interoperability adapters, external busy projections, and episode chains.

The first post-closure 10-round review corrected canonical detail routing, legacy native-ID browser mutation surfaces, delegated clinic-staff appointment/dashboard visibility, transition state/version preconditions, core mutation rate/replay behavior, payment-payer authority, doctor-to-clinic availability scope, display-timezone boundary slot discovery, hold reprojection across timezone/DST day boundaries, and branch-change audit/File26 projection events.

The **second fresh 10-round corrective audit** further hardens administrator transition purpose/step-up checks, requires explicit slot-hold replay keys, namespaces hold replay identity by patient, fails closed on ambiguous stale mutation reservations, strictly validates Future24 date/time inputs, removes native numeric identifiers from Future24 REST DTOs, and serializes outbox dispatch so cron/shutdown workers cannot overlap. Every supported mutation entry point remains guarded by authorization, rate/replay controls, and state/object constraints.

The **third fresh 10-round corrective audit** moves the same invariants to canonical roots where cross-cutting guards were insufficient: stale idempotency is fail-closed in the repository itself, payment and transition preconditions are enforced in the service root, protected mutations are no-store/noindex, ICS output strictly validates persisted UTC timestamps, outbox claims atomically re-check eligibility, and service/availability doctor assignment requires current clinic-serving authority rather than eligibility alone.

The **fourth fresh 10-round corrective audit** closes additional canonical-root gaps: branch identity now survives slot hold/booking/reschedule, slot discovery is clinic-isolated, Future24 group sessions have idempotent leave/cancel semantics and current-state rechecks, signed calendar links validate persisted UTC strictly, payment intents use migration-safe nullable provider references plus canonical idempotency, service-root appointment requests enforce explicit consent and full replay fingerprints, activation requires step-up/current owner eligibility/publishable inventory, and doctor suspension reconciliation is not truncated at 500 appointments.

The **fifth fresh 10-round corrective audit** closes a further set of canonical-root and scale/concurrency gaps: Future24 service references now resolve through the public-ref repository path; doctor-to-clinic serving authority is actor-independent and rechecked at public slot/hold booking edges; arrival and virtual-room semantic de-duplication is serialized across distinct replay keys; guardian-family and disruption affected sets are fully paged; Future24 UTC/date parsing fails closed at the canonical root; nested calendar/DTO depth no longer fails open; and slot buffer/travel/continuous-consultation policy scans are no longer silently capped at 100 appointments.

The **sixth fresh 10-round corrective audit** hardens the remaining relationship, outbox-liveness, scale and recurrence edges: doctor-to-clinic serving truth now includes current doctor eligibility at its canonical root; outbox delivery exposes a stable message identity and abandoned processing leases can be recovered without silent permanent stalls; waitlist offers, questionnaire templates, prerequisite rules and follow-up lists no longer stop at arbitrary first-page ceilings; and monthly series retain their intended day-of-month across short months.

The **seventh fresh 10-round corrective audit** closes privacy-lifecycle starvation, silent input truncation, evidence truncation, and high-volume analytics undercounting: continuity erasure and retention now traverse complete keyset-bounded sets without legal-hold starvation; Future24 retention does the same; scheduling windows, prerequisite policies, follow-up resources and episode chains reject oversized requests instead of silently discarding tail data; prerequisite evidence is evaluated completely; and heatmap/no-show aggregates no longer stop at 2,000 appointments.

The **eighth fresh 10-round corrective audit** closes additional high-volume, concurrency and exact-slot correctness gaps: doctor-suspension and verification-reconciliation scans no longer have artificial total ceilings; due follow-up reminders traverse the complete due set transactionally; canonical Future24 operational writes reject oversized payloads instead of silently truncating them; first-time practitioner opaque references are serialized; slot discovery avoids multi-rule/timezone starvation; hold revalidation projects the exact rule/slot while preserving true idempotent replay; and waitlist, flexible-window and support-participant semantic duplicates are serialized, with support-participant File17 projection committed atomically.
The **ninth fresh 10-round corrective audit** closes residual browser-reference, authorization-scale and mutation-atomicity gaps: legacy browser booking now uses opaque practitioner references; mutation authorization no longer enumerates all doctors; locked appointment mutations roll back on fail-closed errors; appointment transitions require event, outbox, notification, communication, review-eligibility and audit persistence; Future24 record creation requires governance audit persistence; waitlist offer creation is serialized and atomic with File19 projection; participant revocation and virtual-room requests are atomic with File17 projection; and group-session cancellation is atomic across session/member state and governance audit.
The **tenth fresh 10-round corrective audit** closes residual owner-transaction, event/outbox, replay-finalization and public-query contract gaps: initial appointment requests now commit slot/consent/events/File17/File19/audit/replay evidence atomically; clinic lifecycle, branch, service, availability, complaint and payment mutations fail closed with required evidence/projections; branch/availability time-zone inputs fail closed; HTTP replay finalization exposes authoritative mutation-status reconciliation; and public clinic discovery uses opaque cursor pagination plus conditional ETag caching.




The **eleventh fresh 20-round corrective audit** reviewed exact v1.2.10 source sequentially. R1-R15 corrected transaction-control, resource-lock takeover, outbox finalization, all-recipient fallback delivery, replay/timezone/date/check-in validation, inside-lock authorization, protected REST DTOs, doctor-suspension projection atomicity, and canonical service/branch/availability persistence. R16 aligns release identity and permanent evidence to v1.2.11. R17-R20 are fresh corrected-state privacy/Future24/migration/security/plan-parity reviews.

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


## Thirteenth fresh 20-round corrective review

Fresh sequential review of the exact v1.2.12 source corrected 20 supported repository defects/gaps before advancing each round. Post-final corrective sweeps then hardened legacy mutation durability and destructive purge failure semantics; two fresh post-coding source reviews completed without a new supported defect. Runtime 1.2.13 keeps core schema 3.2.0, continuity 1.1.0 and Future24 1.0.0. Staging/live acceptance remains separate.
