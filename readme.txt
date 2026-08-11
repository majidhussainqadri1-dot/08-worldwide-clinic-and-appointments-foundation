=== Worldwide Clinic and Appointments ===
Contributors: majidhussainqadri1-dot
Tags: clinic, appointments, doctors, scheduling, privacy, accessibility
Requires at least: 6.6
Requires PHP: 7.4
Stable tag: 1.2.2
License: GPLv2 or later

Canonical File 08 clinic and appointment runtime for the Sabri Social Homeopathy Platform.

== Description ==

Version 1.2.2 implements the File 08 Complete Master Plan, Future24 amendment, the earlier 80-round corrective closure, the first 10-round post-closure review-and-correct cycle, and a second fresh 10-round corrective audit. The runtime covers clinic identity and institutional activation, branches, services and fees, availability, timezone/DST-safe server-authoritative slots, atomic appointment-bound holds, explicit request idempotency and replay protection, appointment request/decision/reschedule/check-in/completion/cancellation/no-show state law, patient/guardian/doctor/delegated-staff authorization, opaque public scheduling references, dashboards, emergency diversion, versioned consent, expiring review eligibility, ICS calendar export, conditional payment and complaint bridges, scheduling-only CF-01 context, privacy, audit, outbox, observability, migration, rollback, accessibility, localization, secure continuity, and Future Clinic Intelligence & Interoperability 24.

The second fresh corrective audit adds purpose-limited + step-up administrator transitions, explicit slot-hold replay keys, patient-namespaced slot replay identity, fail-closed handling of ambiguous stale idempotency reservations, strict Future24 calendar-value validation, Future24 native numeric-ID response scrubbing, serialized outbox dispatch to prevent overlapping workers, and a dedicated permanent regression gate. Existing supported mutation entry points retain transition state/version preconditions, patient/current-authorized-guardian payment-intent authority, doctor-to-clinic availability scope, delegated clinic-staff views, canonical singular appointment detail URLs, legacy numeric browser-workflow suppression, cross-timezone slot-window correctness, and branch-change audit/search-projection events.

Platform commission is always 0%. Donations are optional and never affect visibility or service access. The plugin never replaces emergency care and never enables automated diagnosis or prescribing.

== Installation ==

1. Verify the exact required companion packages are installed and accepted for staging: Files 00, 03, 07, 09, 17, 19, 20, 24, 25, and 26.
2. Download the exact CI-generated File 08 v1.2.2 candidate whose manifest commit, artifact digest, and detached SHA-256 match the approved repository HEAD.
3. Install that exact candidate on the canonical Hostinger staging site only.
4. Complete STAGING-ACCEPTANCE.md, including fresh install/upgrade/migration, DB/schema/migration-state evidence, rollback/restore, real-role journeys, concurrency/replay/provider-failure cases, privacy/cache/accessibility checks, and Founder acceptance.
5. Do not deploy live until staging acceptance is complete and a controlled production deployment is explicitly authorized.
6. After deployment, re-freeze the exact deployed artifact/version/schema/migration state and complete live re-test/parity confirmation before calling the release operational or resolved.

== Privacy and Security ==

Protected routes use authentication, object authorization, nonce/CSRF controls where applicable, explicit idempotency/replay protection, rate limiting, and no-store/noindex controls. Public clinic output is allow-listed and uses opaque references. Appointment, patient, contact, clinical-like, native-identifier and private-note data are excluded from public projections. Export, erasure, retention and legal-hold controls are provided. Uninstall is non-destructive by default.

== Changelog ==

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
