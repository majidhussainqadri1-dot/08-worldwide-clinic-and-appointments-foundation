# File 08 Corrective Matrix

## Corrective branch

`fix/file-08-corrective-completion`

## Corrective version

`0.2.0`

The original `0.1.0` source remains preserved on `baseline/file-08-original-import`. This matrix maps every independent-audit finding to its corrective implementation. A source-level correction is not a production acceptance claim; Hostinger staging and operational tests remain mandatory.

| Finding | Corrective implementation | Primary evidence |
|---|---|---|
| SWC-CRIT-001 | Split patient-visible messages and private doctor/administrator notes into separate metadata keys; private note rendering is doctor-only and excluded from patient notifications. | `class-swc-frontend.php`, `class-swc-appointments.php`, `class-swc-privacy.php`, `tests/run.php` |
| SWC-CRIT-002 | Added an explicit seven-state, actor-specific transition matrix; terminal states cannot revive; current status/version are submitted and checked; expired proposals are rejected. | `class-swc-helpers.php`, appointment/admin handlers, regression tests |
| SWC-CRIT-003 | Removed all doctor-role/status mutation; File 08 now consumes and requires Files 00, 03, 07, and 09 verification contracts and fails closed. | `class-swc-helpers.php`, `class-swc-activator.php`, main plugin bootstrap |
| SWC-HIGH-001 | Registered dedicated appointment capabilities and added ownership-aware `map_meta_cap` protection. | `class-swc-activator.php`, `class-swc-plugin.php` |
| SWC-HIGH-002 | Added activation snapshots, owned-page detection, safe page creation, guarded rollback, and no overwrite of unrelated or subsequently edited pages. | `class-swc-activator.php` |
| SWC-HIGH-003 | Enforced doctor weekday, time window, time zone, duration, slot alignment, accepting state, and consultation mode at submission. | `class-swc-helpers.php`, `class-swc-appointments.php` |
| SWC-HIGH-004 | Added strict availability validation; contradictory accepting/unavailable states and malformed schedules are rejected. | `class-swc-helpers.php`, `class-swc-appointments.php` |
| SWC-HIGH-005 | Added overlap detection, appointment/doctor resource locks, optimistic record versions, and stale-write rejection. | `class-swc-helpers.php`, appointment/admin handlers |
| SWC-HIGH-006 | Expanded export and erasure to appointment ownership, times, time zones, consent, proposals, private notes, messages, assignment identifiers, and user-linked audit content. | `class-swc-privacy.php` |
| SWC-HIGH-007 | Added structured old/new status and doctor fields, source/reason/details, failure logging, and an administrator audit timeline. | database schema, `class-swc-helpers.php`, `class-swc-admin.php` |
| SWC-HIGH-008 | Doctor reassignment is now a proposal; the original assignment remains until the patient explicitly accepts; all parties are notified and audited. | appointment/admin handlers and patient dashboard |
| SWC-HIGH-009 | Invalid dates, DST gaps, and ambiguous repeated-hour times are rejected through warning checks and exact round-trip validation. | `SWC_Helpers::to_utc()`, regression tests |
| SWC-HIGH-010 | Removed File 08 global navigation and sticky navigation CSS; File 20 remains the shell/navigation owner. | frontend, CSS, static test |
| SWC-HIGH-011 | Replaced failing white-on-orange controls with dark text on Sabri Orange; computed ratio is 6.900:1. | `clinic.css`, `tests/check-contrast.php` |
| SWC-HIGH-012 | Added idempotent database migration, DB version, activation rollback, repair/system check, safe uninstall, and explicit confirmed purge. | `class-swc-activator.php`, admin system check, `uninstall.php` |
| SWC-MED-001 | Only requestable doctors appear in the request form; unsupported consultation modes are disabled in UI and rejected server-side. | helpers, frontend, JavaScript, submit handler |
| SWC-MED-002 | Public cards now show days, hours, time zone, appointment duration, consultation modes, and requestability. | `class-swc-frontend.php` |
| SWC-MED-003 | Patient time zone uses saved preference and browser detection; doctor rescheduling defaults to the doctor’s own zone. | helpers, frontend, JavaScript |
| SWC-MED-004 | Replaced transient read/increment/write logic with a database-backed atomic per-user/per-IP counter. | schema and `SWC_Helpers::rate_limit_hit()` |
| SWC-MED-005 | Added server-side length, format, phone, date, time, time-zone, duration, and required-field validation. | helpers and handlers |
| SWC-MED-006 | Added `DONOTCACHEPAGE`, object/database no-cache constants, strict no-store headers, X-Robots-Tag, and LiteSpeed no-cache signaling. | `class-swc-plugin.php` |
| SWC-MED-007 | File 19 is the primary notification path; checked and logged email is the privacy-safe fallback. | `SWC_Helpers::notify_user()` |
| SWC-MED-008 | Clinic and request pages render the administrator-configured emergency notice. | helper, frontend, admin settings |
| SWC-MED-009 | Added pagination to public doctors, patient and doctor dashboards, and administrator appointment management. | frontend and admin |
| SWC-MED-010 | Profile prefill now uses central File 00/File 03 profile contracts rather than obsolete `_sa_*` fields. | `SWC_Helpers::profile_value()` and request form |
| SWC-MED-011 | Public/admin strings use the plugin text domain and the bootstrap loads translations. | all runtime classes and main bootstrap |
| SWC-MED-012 | Added page validation, unrelated-slug protection, repair, system checks, and controlled map updates. | `class-swc-activator.php`, admin system check |
| SWC-MED-013 | Reschedule proposals have a bounded expiry and must remain future, eligible, and collision-free at acceptance. | appointment/admin handlers |
| SWC-MED-014 | Forms carry expected status/version; writes use resource locks and reject stale submissions. | helpers and all update handlers |
| SWC-LOW-001 | Documentation now distinguishes implemented source, CI, staging acceptance, and production approval. | `readme.txt`, corrective status documents |
| SWC-LOW-002 | Removed the unverified “Tested up to” declaration; it will be restored only after current staging compatibility tests. | `readme.txt` |
| SWC-LOW-003 | Capabilities are removed on deactivation and uninstall while clinical data remains protected unless explicitly purged. | activator and `uninstall.php` |

## Source-level result

All 32 audit findings have corresponding corrective code and regression evidence. The next gate is an independent post-correction review followed by fresh-install, upgrade, rollback, cache, delivery, accessibility, and multi-account Hostinger staging acceptance.
