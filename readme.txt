=== Worldwide Clinic and Appointments Foundation ===
Contributors: sabrihomeopathy
Tags: clinic, appointments, doctors, patients, scheduling, privacy
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 0.2.0
License: GPLv2 or later

A privacy-safe foundation for worldwide clinic discovery, enforceable doctor availability, and audited appointment requests in American English.

== Corrective Release Status ==
Version 0.2.0 is a corrective development candidate. Static syntax, source-level regression tests, and corrective CI may pass, but production approval still requires fresh WordPress installation, upgrade, rollback, Hostinger staging, LiteSpeed cache, File 19 delivery, accessibility, and multi-account end-to-end acceptance.

== Implemented Foundations ==
* Public clinic browsing without registration and without duplicating File 20 global navigation.
* Login required before private appointment submission or dashboard access.
* Eligibility consumes Files 00, 03, 07, and 09; File 08 does not create or mutate verified-doctor identity.
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

== Requirements ==
The following central contracts must be active:
* File 00 — Sabri Membership Core.
* File 03 — Sabri Profiles and Doctors.
* File 07 — Doctors Directory and Discovery.
* File 09 — Global Doctor Onboarding and Verification Completion.

File 19 is used for unified notifications when available. File 20 remains the sole global application-shell and navigation owner.

== Installation and Acceptance ==
1. Create a verified full backup.
2. Install the exact corrective package on Hostinger staging only.
3. Activate Files 00, 03, 07, and 09 before File 08.
4. Run Clinic Management > System Check and Complete Repair.
5. Configure Clinic Settings and one eligible doctor’s availability.
6. Test separate patient, eligible doctor, administrator, and ineligible-doctor accounts.
7. Test every allowed and forbidden state transition, reassignment consent, slot collision, privacy export/erasure, File 19 delivery, email fallback, LiteSpeed exclusions, mobile layouts, keyboard access, upgrade, rollback, and backup restore.
8. Do not install live until the staging acceptance record is complete.

== Important Limitations ==
This foundation does not provide emergency care, payments, video visits, prescriptions, medical-record uploads, diagnosis, ratings, cure guarantees, or automated medical advice. It does not claim production readiness merely because code or CI is green.

== Changelog ==
= 0.2.0 =
* Corrected all 32 findings recorded by the File 08 independent source audit.
* Added privacy separation, transition enforcement, central verification ownership, dedicated capabilities, schedule validation, collision safeguards, complete privacy callbacks, structured audit history, reassignment consent, strict time parsing, File 20 shell compliance, accessible contrast, schema migration, rollback, repair, and purge controls.

= 0.1.0 =
* Original preserved source baseline.
