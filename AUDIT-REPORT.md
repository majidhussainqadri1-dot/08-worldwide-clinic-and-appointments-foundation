# File 08 Independent Source Review

## Module

- **File:** 08
- **Canonical name:** Worldwide Clinic and Appointments Foundation
- **Reviewed version:** 0.1.0
- **Repository:** `majidhussainqadri1-dot/08-worldwide-clinic-and-appointments-foundation`
- **Baseline branch:** `baseline/file-08-original-import`
- **Review branch:** `audit/file-08-source-review`
- **Review date:** 2026-07-29

## Governing decision

**Result: REJECTED / BLOCKED FOR CORRECTIVE WORK**

The imported source is a valid preservation baseline, but it is not a development candidate, staging candidate, production package, or live-installation candidate. The review found privacy, authorization, state-integrity, scheduling, integration, accessibility, migration, and rollback defects that must be corrected and independently retested before File 08 may advance.

No baseline source file was edited during this review. This document records defects only. Corrections must occur on a separate corrective branch after the audit is accepted.

## Evidence examined

1. Exact extracted source from `08-worldwide-clinic-and-appointments-foundation-0.1.0.zip`.
2. Archive SHA-256: `3c891d33b809a87edf3df70945d970a0d62d6cdb96cd34e1c2695751c04bd057`.
3. Thirteen source files totaling 56,444 bytes.
4. File 03 helper contracts in `SPD_Helpers`.
5. File 07 helper contracts in `SDD_Helpers`.
6. File 09 onboarding and completion contracts in `GDO_Helpers`.
7. The governing platform rules for File 00 membership authority, File 20 shell ownership, File 19 notifications, privacy, medical safety, rollback, and the seven-state appointment lifecycle.

## Automated and reproducible checks

- PHP syntax: **9/9 PASS** under PHP 8.4.
- JavaScript syntax: **1/1 PASS** under Node.js.
- High-confidence secret indicator scan: **PASS**; no credential, token, or private-key indicator found.
- ZIP checksum: **PASS**.
- Orange/white contrast calculation: `#FF8A1F` against `#FFFFFF` = **2.358:1**, below WCAG AA requirements.
- Date parser proof: `2026-02-30 10:00` is silently normalized to `2026-03-02 10:00`.
- DST-gap proof: `2026-03-08 02:30 America/New_York` is silently normalized to `03:30`.

## Finding summary

| Severity | Count |
|---|---:|
| Critical | 3 |
| High | 12 |
| Medium | 14 |
| Low | 3 |
| **Total** | **32** |

---

# Critical findings

## SWC-CRIT-001 — A doctor’s “Private administrative note” is disclosed to the patient

**Evidence:** `SWC_Frontend::doctor_form()` labels `_swc_doctor_note` as **Private administrative note**. `SWC_Frontend::appointment_card()` renders the same value for both doctor and patient dashboards without a role restriction.

**Impact:** Confidential clinical or administrative material can be disclosed to the patient contrary to the field’s own privacy label. This is a direct privacy and professional-confidentiality breach.

**Required correction:** Separate patient-visible messages from private doctor/admin notes. Store them under different keys, enforce capability/ownership checks, include them correctly in privacy export/erasure, and add regression tests proving private notes never appear in patient output or email.

## SWC-CRIT-002 — No enforceable appointment state machine

**Evidence:** `SWC_Appointments::doctor_update()` permits any listed doctor status regardless of the current status. `SWC_Admin::update()` permits every status from every status. The doctor form does not preselect the current status, so it defaults to `under-review`. `patient_cancel()` can change a server-side `declined` record to `cancelled`, and `patient_accept_reschedule()` does not verify that the proposed time is still in the future.

**Impact:** Accepted, declined, cancelled, or completed appointments can be revived, regressed, or rewritten. A doctor can accidentally move an accepted record back to under review merely by submitting the form. Audit history cannot reliably represent the legal or operational sequence.

**Required correction:** Freeze the approved seven-state lifecycle; implement an explicit transition matrix per actor; preselect current values; forbid terminal-state revival except through a separately audited administrator recovery action; validate time-dependent transitions; add transition tests for patient, doctor, and administrator.

## SWC-CRIT-003 — File 08 bypasses central identity and verification ownership

**Evidence:** `SWC_Activator::founder()` adds the `sabri_doctor_verified` role and writes `_spd_account_type` and `_spd_verification_status`. File 08 only checks for File 03 and File 07 classes; it does not require File 00 Membership Core or apply File 09’s complete-document/onboarding gate.

**Impact:** A clinic module mutates identity and verification data owned by other modules and can make a user verified without the central membership and institutional verification workflow. This violates the platform’s single-source-of-truth and capability architecture.

**Required correction:** Remove cross-module role/status mutation. Consume a documented verification service or integration contract from Files 00/03/07/09. File 08 must fail safely when the central membership and verification contract is unavailable.

---

# High findings

## SWC-HIGH-001 — Appointment records reuse ordinary WordPress post capabilities

`register_post_type()` uses `capability_type => post` and `map_meta_cap => true`. Although the UI and REST exposure are disabled, generic WordPress editing paths or third-party integrations can inherit ordinary post capabilities. Sensitive appointment records require a dedicated capability map and explicit meta authorization.

## SWC-HIGH-002 — Activation can overwrite existing page content and rollback is incomplete

`SWC_Activator::page()` replaces content when any page contains `[swc_` or the platform placeholder, even when File 08 does not own the whole page. Deactivation restores only the clinic page and assumes one fixed fallback string; request, patient, doctor, and availability pages remain with dead shortcodes. No activation snapshot or exact rollback record exists.

## SWC-HIGH-003 — Published availability is not enforced when a request is submitted

The request precheck validates only accepting/unavailable flags and consultation mode. It does not compare the requested weekday or time with the doctor’s available days, start time, end time, time zone, or appointment duration. The displayed availability is therefore informational rather than an enforceable scheduling control.

## SWC-HIGH-004 — Contradictory or invalid availability can be saved

The module accepts empty days, malformed start/end values, end before start, no consultation mode, `accepting=1` with `unavailable=1`, or `accepting=1` without a usable time window. Server-side normalization and validation are absent.

## SWC-HIGH-005 — No collision or overbooking protection

A doctor or administrator can accept multiple appointments for the same time. Duration is not used to calculate overlap, and concurrent updates have no locking or conflict detection.

## SWC-HIGH-006 — Privacy export and erasure are materially incomplete

The exporter omits doctor notes, consent timestamp, proposed time/time zone, audit history, status history, and several retained identifiers. The eraser leaves the patient user ID, appointment time, time zone, doctor assignment, consent timestamp, proposed times, and audit actors in place. Audit-note text is not erased or exported. The callback can therefore report removal while substantial linked personal data remains.

## SWC-HIGH-007 — Audit history is insufficient and not operationally viewable

The readme claims audit history, but no interface displays it. Entries store only a new action string and free-text note; they do not preserve old status, new status, old doctor, new doctor, source request, or structured reason. Database insertion failures are ignored.

## SWC-HIGH-008 — Administrator reassignment changes the privacy recipient without renewed consent

The patient consents to sharing data with the selected doctor. `SWC_Admin::update()` can assign a different doctor without patient reconfirmation, without structured old/new-doctor audit data, and without notifying the patient, old doctor, or new doctor.

## SWC-HIGH-009 — Invalid dates and DST-gap times are silently normalized

`SWC_Helpers::to_utc()` uses `DateTime::createFromFormat()` but does not inspect `DateTime::getLastErrors()` or round-trip the parsed local value. Invalid dates and nonexistent daylight-saving times are accepted after normalization; ambiguous repeated-hour times are not disambiguated.

## SWC-HIGH-010 — File 08 duplicates navigation owned by File 20

`SWC_Helpers::navigation()` renders a complete main navigation bar, and clinic CSS makes it sticky on mobile. The governing architecture assigns global navigation and responsive shell ownership to File 20. Running both creates duplicate navigation, conflicting sticky headers, inconsistent routing, and dead-link risk.

## SWC-HIGH-011 — Primary orange buttons fail accessibility contrast

White text on `#FF8A1F` produces approximately **2.358:1** contrast. This fails WCAG AA for normal text and also fails the 3:1 large-text threshold. The failure affects primary buttons, active navigation, and numbered step badges.

## SWC-HIGH-012 — No controlled schema upgrade, rollback, or purge mechanism

The plugin creates a table only during activation, stores only `swc_version`, and has no idempotent schema migration runner, database version, rollback snapshot, or repair routine. `uninstall.php` intentionally retains all data but provides no authorized purge tool. Partial activation failures can leave roles, pages, options, tables, and cross-module metadata changed.

---

# Medium findings

## SWC-MED-001 — Public request controls contradict server eligibility

A doctor who is not accepting requests still receives an enabled Request Appointment button unless marked unavailable. The request form always offers both online and in-person modes even when the selected doctor supports only one; rejection occurs only after submission.

## SWC-MED-002 — Public cards omit the availability details claimed by the module

The readme promises days, hours, time zone, duration, and consultation type. Public doctor cards show only broad mode and accepting/unavailable tags. Days, hours, time zone, and duration are not displayed.

## SWC-MED-003 — Time-zone defaults are unsuitable for a global platform

The patient form defaults to the WordPress site time zone rather than the user’s saved or browser-resolved time zone. The doctor reschedule selector does not select the doctor’s own time zone and therefore defaults to the first identifier in the list.

## SWC-MED-004 — Rate limiting is non-atomic and easy to exceed concurrently

The transient counter is read, incremented, and written only after record creation. Parallel requests can pass the same check, and cache deletion resets the limit. There is no secondary abuse signal or administrative visibility.

## SWC-MED-005 — Server-side field bounds and phone validation are incomplete

HTML maxlength values are not consistently enforced on the server. Country, city, duration, administrator note, doctor note, dates, and times lack strict length/format validation. Phone cleaning can accept implausibly short values.

## SWC-MED-006 — Private-page cache protection is not sufficiently explicit

The module calls `nocache_headers()` for mapped private pages but does not set a module-level no-cache constant or documented LiteSpeed/Hostinger cache exclusion. Clinical scheduling pages need verified no-store/no-cache behavior under the actual hosting stack.

## SWC-MED-007 — Email delivery failures are ignored and File 19 is bypassed

`wp_mail()` results are not checked, queued, retried, logged, or surfaced. Notifications are sent directly rather than through the unified File 19 notification contract, so in-app history and delivery diagnostics are absent.

## SWC-MED-008 — The configurable emergency notice is not used publicly

Clinic Settings saves `swc_emergency_notice`, but the clinic and request pages render hard-coded warnings. Administrative changes have no public effect.

## SWC-MED-009 — Hard query limits silently hide records and doctors

Doctor discovery is limited to 250 users, the administrator list to 250 appointments, the doctor dashboard to 200, and patient lists to 100/50, all without pagination or a warning. Older or later records can disappear from operational views.

## SWC-MED-010 — Profile prefill uses legacy metadata directly

The request form reads `_sa_country`, `_sa_city`, and `_sa_phone` instead of the File 03 helper contract. Current `_spd_` profile values may therefore fail to prefill; WhatsApp is not prefilled at all.

## SWC-MED-011 — Internationalization architecture is incomplete

Most public and administrator strings are hard-coded, no text domain is loaded, and only a minority of messages use translation functions. This conflicts with the planned multilingual interface and makes later translation costly.

## SWC-MED-012 — Page-map repair is not robust

Existing mapped IDs are trusted even if a page is trashed or unsuitable. A same-slug unrelated page can be mapped without receiving the shortcode, producing a dead workflow. There is no repair or system-check routine.

## SWC-MED-013 — Reschedule proposals can expire before acceptance

Patient acceptance checks only status and the presence of `_swc_proposed_at_utc`; it does not verify that the proposed time is still future or that the doctor remains eligible/available.

## SWC-MED-014 — Concurrent updates can silently overwrite one another

Doctor and administrator forms do not carry a record version, last-modified value, or expected current status. A stale form submission can overwrite a more recent assignment, status, or note.

---

# Low findings

## SWC-LOW-001 — Documentation overstates completed behavior

The readme describes public availability details and audit history that are not actually exposed. Release documentation must distinguish planned, coded, tested, and operational features.

## SWC-LOW-002 — Compatibility declaration is stale

The package says “Tested up to: 6.8,” while the project environment is newer. This is not itself a runtime defect, but no compatibility claim should be updated until fresh staging tests pass.

## SWC-LOW-003 — The administrator capability remains after deactivation/uninstall

`manage_worldwide_clinic` is added to administrators but never removed. Retaining data may be intentional, yet capability lifecycle and controlled cleanup need an explicit policy.

---

# Positive controls already present

The review also confirmed several sound foundations:

- The custom post type is non-public, hidden from REST, and excluded from search.
- All state-changing forms include WordPress nonces in the rendered UI.
- Patient and doctor dashboards query records by author or assigned doctor.
- Output is generally escaped and input is generally unslashed and sanitized.
- Appointment emails avoid including the consultation reason or other medical details.
- Private pages are marked noindex/noarchive.
- The module records UTC appointment values and uses named IANA time zones.
- Automatic destructive deletion on uninstall is avoided.

These controls reduce risk but do not cure the blockers listed above.

# Required corrective work order

1. **Privacy containment:** separate private notes from patient messages; correct export/erasure and audit-data handling.
2. **Authority and ownership:** remove File 08 verification mutations; require central membership/onboarding contracts; add dedicated appointment capabilities.
3. **State machine:** freeze statuses and actor-specific transitions; add optimistic locking and terminal-state protection.
4. **Scheduling engine:** validate availability, date/time/DST, duration, overlap, eligibility, and reschedule expiry.
5. **Page and shell integration:** remove duplicate global navigation; implement safe page ownership, repair, rollback, and File 20 routing.
6. **Notifications:** integrate File 19, log delivery outcomes, and notify all affected parties on reassignment/status changes.
7. **Privacy and operations:** controlled retention/purge, audit viewer, structured history, pagination, cache exclusions, and system checks.
8. **Accessibility and UI:** compliant contrast, truthful disabled controls, correct defaults, responsive/keyboard tests, and translatable strings.
9. **Release engineering:** migration runner, schema version, fresh install, upgrade, rollback, uninstall, and staging end-to-end tests.

# Mandatory acceptance tests after correction

- Patient cannot read private doctor/admin notes.
- Generic post editing, REST, XML-RPC, and direct URL paths cannot expose or mutate appointments without dedicated capabilities.
- Every allowed and forbidden state transition is covered by automated tests.
- Cancelled/completed/declined records cannot be revived accidentally.
- Requests outside availability, in DST gaps, with invalid dates, or overlapping accepted slots fail safely.
- Doctor reassignment requires approved policy, structured audit, patient notice/consent handling, and access revocation.
- Export and erasure cover every personal field and audit record according to a documented retention policy.
- File 00/03/07/09/19/20 contracts are tested together; no duplicate navigation or notification output appears.
- Private dashboards are not cached, indexed, leaked, or visible across patient/doctor accounts.
- WCAG 2.2 AA contrast, keyboard, focus, and mobile viewport tests pass.
- Fresh install, upgrade, database migration, rollback, deactivation, controlled purge, and Hostinger staging tests pass.

# Release classification after review

- Baseline integrity: **PASS**
- Static syntax: **PASS**
- Independent source review: **COMPLETE**
- Security/privacy acceptance: **FAIL**
- Functional acceptance: **FAIL**
- Integration acceptance: **FAIL**
- Accessibility acceptance: **FAIL**
- Development candidate: **REJECTED**
- Staging candidate: **REJECTED**
- Production release: **REJECTED**
- Live installation authorized: **NO**

## Next controlled branch

After this audit is accepted, create a separate corrective branch, recommended name:

`fix/file-08-corrective-completion`

Every finding must be corrected, tested, and independently re-reviewed before File 08 advances.
