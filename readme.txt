=== Worldwide Clinic and Appointments ===
Contributors: majidhussainqadri1-dot
Tags: clinic, appointments, doctors, scheduling, privacy, accessibility
Requires at least: 6.6
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later

Canonical File 08 clinic and appointment runtime for the Sabri Social Homeopathy Platform.

== Description ==

Version 1.0.0 implements the File 08 Complete Master Plan: clinic identity and branches, services and fees, availability, DST-safe slots, atomic holds, idempotent requests, confirmed/declined/reschedule/check-in/completion/cancellation/no-show lifecycle, dashboards, emergency diversion, versioned consent, review eligibility, ICS calendar export, conditional payment and complaint bridges, scheduling-only CF-01 context, privacy, audit, outbox, observability, migration, rollback, accessibility and localization.

Platform commission is always 0%. Donations are optional and never affect visibility or service access.

== Installation ==

1. Verify Files 00, 03, 07 and 09 are installed and accepted.
2. Install the exact CI-generated candidate on staging.
3. Activate and execute docs/STAGING-ACCEPTANCE-1.0.0.md.
4. Do not deploy live until restore/rollback, real-role, privacy/security/accessibility and Founder acceptance are complete.

== Privacy ==

Protected routes use no-store/noindex controls. Public clinic output is allow-listed. Appointment, patient, contact, clinical-like and private-note data are excluded. Export, erasure, retention and legal-hold controls are provided. Uninstall is non-destructive by default.

== Changelog ==

= 1.0.1 =
* Four-plan-governed review rounds: clinic approval gate, actor/guardian binding, opaque public scheduling references, server-authoritative slot holds, purpose-limited administrative access, cross-clinic ownership checks, atomic rescheduling, expiring review eligibility, and expanded regression evidence.

= 1.0.0 =
* Complete master-plan source candidate and deterministic release engineering.
