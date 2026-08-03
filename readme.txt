=== Worldwide Clinic and Appointments Foundation ===
Contributors: sabrihomeopathy
Tags: clinic, appointments, doctors, patients, scheduling, privacy
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.2.2
License: GPLv2 or later

A privacy-safe foundation for worldwide clinic discovery, enforceable doctor availability, audited appointment requests, a bounded public clinic projection for File 25, and a scheduling-only CF-01 care-context contract.

== Corrective Release Status ==
Version 0.2.2 is a stacked contract candidate based on the unmerged File 08 0.2.1 corrective branch. Source tests and CI do not constitute production acceptance. Merge order, immutable File 00/09 dependencies, CF-01 consumer fixtures, WordPress installation/upgrade/rollback, Hostinger staging, privacy/security review, migration dry run, accessibility and multi-account end-to-end acceptance remain mandatory.

== Implemented Foundations ==
* Public clinic browsing without registration and without duplicating File 20 global navigation.
* Login required before private appointment submission or dashboard access.
* Eligibility consumes Files 00, 03, 07, and 09; File 08 does not create or mutate verified-doctor identity.
* Versioned read-only `swc_get_public_clinic_projection()` contract `1.0.0` for File 25.
* Versioned `swc_get_cf01_care_context()` contract `1.0.0` for privacy-minimal scheduling context.
* The CF-01 contract uses File 00 opaque subject UUID assertions and never returns clinical-like free text or contact details.
* Appointment status, acceptance and completion never establish a treating relationship or clinical chart authority.
* Appointment-processing consent never becomes clinical-treatment, publication, research, transfer or AI-processing consent.
* Missing canonical clinic/location entities remain explicitly unmodeled rather than fabricated.
* Public projection is limited to verified public doctors and the allow-listed fields name, address, country, city, hours, and timezone.
* Public projection excludes phone, WhatsApp, email, user/native identifiers, appointment data, and patient data; extensions may revoke but cannot add forbidden fields.
* Published doctor days, hours, time zone, duration, consultation modes, accepting state, and temporary unavailability.
* Server-enforced availability, slot alignment, future-time rules, strict date parsing, DST-gap rejection, repeated-hour rejection, and collision detection.
* Dedicated appointment capabilities and explicit patient, assigned-doctor, and administrator ownership checks.
* Requested, under-review, accepted, reschedule-requested, declined, cancelled, and completed states with actor-specific transitions and immutable terminal states.
* Optimistic record versions, update locks, structured audit history, and viewable administrator audit timelines.
* Patient-visible messages separated from private doctor/administrator notes.
* Reassignment proposals require patient acceptance before a different doctor receives the appointment.
* Phone and WhatsApp contact controls inside authorized dashboards.
* UTC storage with patient and doctor time-zone display.
* File 19 unified notifications when available, with checked and logged privacy-safe email fallback.
* Configurable emergency warning used on public and request pages.
* Atomic database rate limiting, strict server-side field bounds, noindex/noarchive, no-store/no-cache, and LiteSpeed no-cache signaling for private pages.
* Complete privacy export and erasure coverage for appointment, proposal, consent, private-note, and audit-linked data.
* Paginated doctor discovery, patient dashboard, doctor dashboard, and administrator management.
* Controlled schema upgrades, activation snapshots, page repair, exact rollback boundaries, system checks, and an explicitly confirmed administrator purge.
* Internationalization-ready strings and WCAG-aware focus, touch-target, and color-contrast foundations.

== CF-01 Boundary ==
File 08 remains the canonical owner of appointment requests, scheduling times, consultation mode, assignment/reassignment, availability, appointment status and scheduling audit facts.

CF-01 may own longitudinal charts, treating relationships, clinical intake/history, encounters, prescriptions, follow-ups, clinical attachments and clinical consent only after its independent activation gates.

Current clinical-like or mixed-purpose File 08 fields—`_swc_reason`, `_swc_concern_duration`, `_swc_doctor_private_note`, `_swc_patient_message` and free-text audit fields—are inventoried for controlled future classification/extraction. They are not returned by the care-context contract and are not migrated by this release.

== Requirements ==
The following central contracts must be active:
* File 00 — Sabri Membership Core.
* File 03 — Sabri Profiles and Doctors.
* File 07 — Doctors Directory and Discovery.
* File 09 — Global Doctor Onboarding and Verification Completion.

The CF-01 care-context contract additionally requires File 00 `1.2.7` and `smc.cf01.membership-assurance` `1.0.0`; when unavailable or incompatible, only the care-context call fails explicitly as `unknown`. Existing File 08 appointment functions remain governed by their 0.2.1 dependency checks.

File 19 is used for unified notifications when available. File 20 remains the sole global application-shell and navigation owner. File 25 consumes only the File 08 public clinic projection and does not own clinic or appointment data.

== Installation and Acceptance ==
1. Resolve and accept the File 08 0.2.1 base PR before this stacked candidate.
2. Merge and freeze the File 00 1.2.7 provider contract and applicable File 09 practitioner contract before CF-01 integration acceptance.
3. Create a verified full backup.
4. Install the exact corrective package on Hostinger staging only.
5. Activate Files 00, 03, 07, and 09 before File 08.
6. Run Clinic Management > System Check and Complete Repair.
7. Configure Clinic Settings and one eligible doctor’s availability.
8. Test public projection and care-context contracts with patient, assigned doctor, administrator, wrong actor, stale record version, unavailable identity provider and every appointment state.
9. Verify that reason, concern duration, private note, patient message, contact details and audit narrative never enter the care-context response.
10. Test every allowed and forbidden state transition, reassignment consent, slot collision, privacy export/erasure, File 19 delivery, File 25 consumption, email fallback, LiteSpeed exclusions, mobile layouts, keyboard access, upgrade, rollback, backup restore and migration dry run.
11. Do not install live or begin clinical extraction until the applicable acceptance record and Founder change control are complete.

== Important Limitations ==
This foundation does not provide emergency care, payments, video visits, prescriptions, clinical charts, medical-record uploads, diagnosis, ratings, cure guarantees, autonomous advice or a treating-relationship system of record. It does not claim production readiness merely because code or CI is green.

== Changelog ==
= 0.2.2 =
* Added `swc.cf01.care-context` contract `1.0.0` with File 00 opaque patient/practitioner UUIDs, File 08 record version and scheduling-only state.
* Added explicit denial of clinical read/write, treating-relationship activation, prescription and break-glass authority from appointment status.
* Added explicit appointment-processing-only consent scope and false clinical/publication consent assertions.
* Added field-by-field clinical-like data and extraction inventory without performing migration.
* Added stale-version, wrong-actor, identity-provider, leakage and completed-appointment adversarial tests.
* Added deterministic package-manifest coverage for the care-context provider.

= 0.2.1 =
* Added File 08 Public Clinic Projection Contract `1.0.0` for File 25.
* Added verified-public-doctor fail-closed eligibility, bounded clinic fields, deterministic public hours/timezone, and explicit exclusion of contact, identifiers, appointment, and patient data.
* Added projection regression tests, bootstrap/version checks, CI coverage, documentation, and checksum evidence.

= 0.2.0 =
* Corrected all 32 findings recorded by the File 08 independent source audit.
* Added privacy separation, transition enforcement, central verification ownership, dedicated capabilities, schedule validation, collision safeguards, complete privacy callbacks, structured audit history, reassignment consent, strict time parsing, File 20 shell compliance, accessible contrast, schema migration, rollback, repair, and purge controls.

= 0.1.0 =
* Original preserved source baseline.
