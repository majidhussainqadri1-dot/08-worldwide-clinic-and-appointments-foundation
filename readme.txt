=== Worldwide Clinic and Appointments ===
Contributors: majidhussainqadri1-dot
Tags: clinic, appointments, doctors, scheduling, privacy, accessibility
Requires at least: 6.6
Requires PHP: 7.4
Stable tag: 1.2.6
License: GPLv2 or later

Canonical File 08 clinic and appointment runtime for the Sabri Social Homeopathy Platform.

== Description ==

Version 1.2.6 implements the File 08 Complete Master Plan, Future24 amendment, the earlier 80-round corrective closure, the first 10-round post-closure review-and-correct cycle, the second and third fresh 10-round corrective audits, and the fourth fresh sequential 10-round corrective audit. The runtime covers clinic identity and institutional activation, branches, services and fees, availability, timezone/DST-safe server-authoritative slots, atomic appointment-bound holds, explicit request idempotency and replay protection, appointment request/decision/reschedule/check-in/completion/cancellation/no-show state law, patient/guardian/doctor/delegated-staff authorization, opaque public scheduling references, dashboards, emergency diversion, versioned consent, expiring review eligibility, ICS calendar export, conditional payment and complaint bridges, scheduling-only CF-01 context, privacy, audit, outbox, observability, migration, rollback, accessibility, localization, secure continuity, and Future Clinic Intelligence & Interoperability 24.

The fourth fresh corrective audit binds branch identity to canonical slot holds and appointment/reschedule state, isolates public slot discovery by clinic, completes Future24 group leave/cancel semantics with current-state rechecks, strictly validates signed-link calendar timestamps, makes payment-intent uniqueness/idempotency migration-safe, enforces consent at the appointment service root, expands replay fingerprints, hardens clinic activation step-up/current-owner/publishable-inventory checks, and removes the 500-appointment doctor-suspension truncation through bounded paged reconciliation. Earlier authorization, concurrency, privacy, migration, package-parity, and Future24 gates remain in force.

The fifth fresh corrective audit fixes Future24 public service-reference resolution, actor-independent doctor-to-clinic serving authority and held-slot rechecks, cross-key semantic concurrency for arrival/virtual-room requests, complete paged guardian/disruption/policy scans, and strict fail-closed Future24 calendar parsing/depth handling.

The sixth fresh corrective audit moves current doctor eligibility into the canonical clinic-serving relationship root; gives outbox consumers a stable message identity and recovers abandoned processing leases; removes remaining waitlist/questionnaire/prerequisite/follow-up fixed-window truncation; and preserves end-of-month intent for monthly recurrence generation.

Platform commission is always 0%. Donations are optional and never affect visibility or service access. The plugin never replaces emergency care and never enables automated diagnosis or prescribing.

== Installation ==

1. Verify the exact required companion packages are installed and accepted for staging: Files 00, 03, 07, 09, 17, 19, 20, 24, 25, and 26.
2. Download the exact CI-generated File 08 v1.2.6 candidate whose manifest commit, artifact digest, and detached SHA-256 match the approved repository HEAD.
3. Install that exact candidate on the canonical Hostinger staging site only.
4. Complete STAGING-ACCEPTANCE.md, including fresh install/upgrade/migration, DB/schema/migration-state evidence, rollback/restore, real-role journeys, concurrency/replay/provider-failure cases, privacy/cache/accessibility checks, and Founder acceptance.
5. Do not deploy live until staging acceptance is complete and a controlled production deployment is explicitly authorized.
6. After deployment, re-freeze the exact deployed artifact/version/schema/migration state and complete live re-test/parity confirmation before calling the release operational or resolved.

== Privacy and Security ==

Protected routes use authentication, object authorization, nonce/CSRF controls where applicable, explicit idempotency/replay protection, rate limiting, and no-store/noindex controls. Public clinic output is allow-listed and uses opaque references. Appointment, patient, contact, clinical-like, native-identifier and private-note data are excluded from public projections. Export, erasure, retention and legal-hold controls are provided. Uninstall is non-destructive by default.

== Changelog ==

= 1.2.6 =
* Completed a sixth fresh sequential review-and-correct cycle against exact v1.2.5 repository state.
* Canonical doctor-to-clinic serving authority now fails closed when current doctor eligibility is revoked.
* Outbox envelopes now carry stable message IDs and abandoned processing leases are recovered for idempotent retry/dead-letter handling.
* Removed remaining fixed-window starvation/truncation from waitlist offers, dynamic questionnaire templates, prerequisite policies, and follow-up lists.
* Monthly recurrence preserves the originating day-of-month and clamps only to the target month's valid last day.
* Runtime is 1.2.6; core schema remains 3.2.0; continuity schema remains 1.1.0; Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.

= 1.2.5 =
* Completed a fifth fresh sequential 10-round corrective audit on the v1.2.4 exact repository state.
* Corrected Future24 clinic-scoped public service-reference resolution.
* Made current doctor-to-clinic serving authority actor-independent and rechecked it at slot search and held-slot booking boundaries.
* Serialized semantic arrival and virtual-room de-duplication across distinct idempotency keys.
* Removed silent 100/1000-record truncation from guardian family, disruption affected-set and slot-policy evaluation paths through bounded pagination.
* Replaced permissive Future24 root timestamp parsing with strict round-trip parsing; impossible waitlist dates and excessive nested calendar/DTO payloads now fail closed.
* Runtime is 1.2.5; core schema remains 3.2.0; continuity schema remains 1.1.0; Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.

= 1.2.4 =
* Completed a fourth fresh sequential 10-round corrective audit on the v1.2.3 exact source state.
* Bound branch identity to canonical slot holds and appointment/reschedule state, and isolated slot discovery by clinic.
* Added Future24 group leave/cancel semantics plus live clinic/service/start-time rechecks.
* Strictly validated signed-link calendar timestamps and hardened payment intent uniqueness/idempotency with a migration-safe schema change.
* Enforced consent at the appointment service root, expanded replay fingerprints, and hardened clinic activation step-up/current-owner/publishable-inventory checks.
* Removed the 500-appointment doctor-suspension truncation by bounded paged reconciliation.
* Runtime is 1.2.4; core schema is 3.2.0; continuity schema remains 1.1.0; Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.

= 1.2.3 =
* Completed a third fresh sequential 10-round corrective audit on the corrected v1.2.2 source state.
* Moved fail-closed stale idempotency, payment-payer authority and transition preconditions into canonical repository/service roots rather than relying only on cross-cutting REST guards.
* Added no-store/noindex protection for every protected mutation response, strict persisted UTC validation for ICS output, atomic outbox row claims, and current doctor-to-clinic authority checks for service and availability assignment.
* Added a permanent third-ten-review regression gate; core DB schema remains 3.1.0, restricted continuity schema remains 1.1.0, and Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.

= 1.2.2 =
* Completed a second fresh 10-round corrective audit after the v1.2.1 closure.
* Added purpose-limited + step-up administrator transitions, explicit and patient-namespaced slot-hold replay keys, fail-closed stale-idempotency protection, strict Future24 date/time validation, native numeric-ID response scrubbing, and serialized outbox dispatch.
* Added a permanent second-ten-review regression gate and retained all earlier authorization, concurrency, privacy, migration, package-parity, and Future24 gates.
* Core DB schema remains 3.1.0; restricted continuity schema remains 1.1.0; Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.

= 1.2.1 =
* Completed the 80-round corrective closure and a subsequent 10-round post-closure review-and-correct cycle.
* Added canonical core-mutation replay/rate hardening, transition preconditions, payment-payer authorization, doctor/clinic availability scope validation, delegated staff visibility, canonical detail-route correction, legacy numeric browser-workflow suppression, cross-timezone slot-boundary correction, and branch audit/search-projection events.
* Bound every candidate to an exact 40-hex source commit in the deterministic manifest and verifier.
* Repository/CI/package evidence remains distinct from staging/live evidence.

= 1.0.1 =
* Four plan-governed review rounds corrected clinic approval, actor/guardian binding, opaque public scheduling references, server-authoritative slot holds, purpose-limited administrative access, cross-clinic ownership, compensation-safe rescheduling, and expiring review eligibility.

= 1.0.0 =
* Complete master-plan source candidate and deterministic release engineering.
