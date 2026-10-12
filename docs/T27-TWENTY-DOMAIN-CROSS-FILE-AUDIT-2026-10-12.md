# File 08 — Twenty focused source/plan/cross-file audit passes (T27)

Date: 2026-10-12 (PKT). **Repository-only evidence, not live or staging verification.**
Base main SHA: `70541974ce0ffb16aebef557c3016eb7447662f4`.
Reviewed materials: governing File 08 Worldwide Clinic & Appointments plan (SSH-F08-PLAN-2026-v1.0 and approved Future24 amendment); consolidated central platform plan v3.0; applicable File 07/09/14 plans; exact File 08 source tree; actual File 07/09/19/20/26 current repository contracts. File 03/17/00 and actual live plugin artifacts were **not fully independently runtime-tested**.

## Review method and classification

Twenty **independently scoped source/contract checks** were performed. These are different focused audit passes, **not twenty exhaustive repetitions of the entire codebase**. Each considers the File 08 charter, platform ownership, applicable cross-file ownership, source implementation and available regression/static evidence. "Source-checked" does not mean production accepted. Confirmed defects are marked and repaired after their review; no ungrounded missing code is invented.

| Pass | Reviewed owner contract and source/test evidence | Result |
|---|---|---|
| R01 | File 08 18 functional requirements and 10 NFRs; `docs/MASTER-PLAN-TRACEABILITY-1.0.0.md`, `tests/master-plan-contract.php`, `tests/central-plan-2026.php` | Source coverage present; no additional confirmed omission in mapped requirements |
| R02 | File 00 identity/claims, explicit appointment authorization; `includes/class-wca-authorization.php` and `includes/class-wca-central-governance.php` | Source-checked; independent File 00 installed-state verification pending |
| R03 | File 09 doctor verification and suspension authority; `includes/class-swc-doctor-authority.php`, `includes/class-wca-verification-reconciliation.php` | Source-checked; live File 09 contract/package parity pending |
| R04 | File 07 directory uses public clinic read projection only; `includes/class-swc-public-clinic.php`, `includes/class-wca-service.php` | Source-checked; real File 07 consumer compatibility pending staging |
| R05 | File 03 profile/clinic/delegated schedule read contract; `includes/class-wca-file03-adapter.php`, `tests/file03-profile-contracts.php` | Source-checked; PR #11 already part of base main |
| R06 | File 14 clinic USP/CTA consumer boundary (no duplicate booking owner); `includes/class-wca-routes.php` and canonical routes in `README.md` | Source-checked, not real browser CTA verification |
| R07 | File 20 owns shell; File 08 owns clinic route content only; `includes/class-wca-frontend.php`, `includes/class-wca-routes.php` | Source-checked; runtime shell-slot parity pending |
| R08 | File 25 visual/RTL and WCAG surface; `assets/css/clinic.css`, `assets/js/clinic.js`, `tests/check-contrast.php` | Source-checked; accessible browser testing pending |
| R09 | File 17 appointment-context native ID isolation, booking state event and ICS export; `includes/class-wca-service.php` | **DEFECT D1:** numeric fallback `appointment-<native ID>` found in 3 contexts; fixed with validated opaque ref, fail-closed transaction and calendar gates; executable regression added |
| R10 | File 19 sole notification/preferences/delivery owner; File 08 `includes/class-wca-outbox.php` vs actual File 19 3.0.5 `sun_register_notification_producer` + `sun_ingest_domain_event` APIs | **DEFECT D2:** obsolete `sn_notify_users` and direct `wp_mail` fallback; fixed to File 19 producer/event ingestion, stable idempotency and fail-closed owner absence; executable regression added |
| R11 | File 26 discovery index/projection invalidation; `includes/class-wca-second-ten-review-hardening.php`, `tests/t25-r8-cross-file-reliability-regressions.php` | Source-checked; live reindex worker pending |
| R12 | CF-01 scheduling context not treatment/chart/prescribing authority; `includes/class-swc-cf01-care-context.php`, `tests/cf01-care-context-contract.php` | Source-checked, clinical permission remains native to CF-01 |
| R13 | Zero platform commission and payment-provider-only ledger; `includes/class-wca-service.php`, `includes/class-wca-repository.php` | Source-checked; real ledger-provider settlement not established |
| R14 | Clinic/branch/service/fees/timezone, DST-safe rules; `includes/class-wca-service.php`, `includes/class-wca-schema.php` | Source-checked; real timezone test cases remain staging gate |
| R15 | Atomic hold capacity, expiry, concurrency, idempotency; `includes/class-wca-repository.php`, `includes/class-wca-appointment-command.php` | Source-checked; independent concurrent DB execution pending staging |
| R16 | Actor-specific appointment state machine, check-in, no-show, reschedule, audit; `includes/class-wca-service.php`, `includes/class-wca-contracts.php` | Source-checked; real users/DB still needed |
| R17 | Guardian/minor + consent separation, privacy exports/erasure/legal holds; `includes/class-wca-authorization.php`, `includes/class-wca-continuity-secure.php`, `includes/class-wca-privacy.php` | Source-checked; linked File 00 relationship + retention tests pending |
| R18 | Emergency symptom diversion and no implied emergency care; `WCA_Service::emergency_red_flag`, intake safety gate `WCA_Continuity::save_intake` | Source-checked; clinical safety acceptance pending |
| R19 | Future24 scheduling, queue, resource/group, virtual-room, external-busy, episode interoperability; `includes/class-wca-future24.php`, `tests/future24.php`, `tests/new-plan-hardening.php` | Source-checked; companion runtime provider interoperability pending |
| R20 | Schema upgrade, migration, release parity, exact-head CI/package and rollback; `includes/class-wca-schema.php`, `includes/class-swc-activator.php`, `tools/build-candidate.php`, `tools/verify-candidate.php`, `STAGING-ACCEPTANCE.md` | Source/CI evidence verified; **staging + deployed DB/version/migration + live** remain unverified; not a claim of production completion |

### Frozen defect and correction record

**D1 (R09, cross-owner confidentiality):** Three calls in `WCA_Service` used a public-reference fallback constructed from the native numeric appointment ID. The File 17 event payload, transition outbox and calendar UID now require a UUID-shaped canonical opaque reference; malformed legacy records return a controlled `WP_Error`. The originating transaction checks failure before enqueue; the calendar returns the error without exporting the internal ID.

**D2 (R10, broken cross-owner contract):** File 08 checked for obsolete `sn_notify_users`; File 19 currently exposes `sun_register_notification_producer` and `sun_ingest_domain_event`. Direct-mail fallback bypassed File 19 ownership of user preferences/delivery. File 08 now produces a privacy-minimal versioned `Clinic.AppointmentUpdated` event via the canonical File 19 API, dedupes explicit recipients, uses its durable outbox message ID as producer-scoped event ID, requires processed/duplicate acknowledgement, and fails closed for retry if the owner is unavailable.

**Test-system reconciliation (not an extra application defect):** The PHP8.3 test bootstrap WP_Error stub had undeclared dynamic properties; declared its fields. Historical security/T11/T15 static tests previously asserted the now-obsolete direct-mail fallback; replaced with stronger current contract/durable acknowledgement/no-bypass assertions. New executable tests `tests/t27-opaque-appointment-ref-regressions.php` (7 assertions) and `tests/t27-file19-notification-contract-regressions.php` (11 assertions) are bound to `tests/run-all.php`.

### Exact source verification

Corrected source snapshot `702fe8e04f34cbb6dfc1f38b2e79c863137d9533` (before adding this evidence ledger): GitHub Actions `38267233974` = **SUCCESS** for PHP 7.4 source/contracts, PHP 8.3 source/contracts, JS syntax, repository hygiene and reproducible independently verified candidate package. Candidate artifact ID `11755820349`, outer digest `sha256:1b06c750a39cc85f24ba55423cbad043b9166e709885e62c60c3c2ec60467662`. Subsequent ledger-only commits require a new exact-head run before claiming that new head green.

### Unverified states / prohibition on release inflation

Repository main `70541974ce0ffb16aebef557c3016eb7447662f4` remains unchanged until an authorized merge. Deployed plugin version: **unverified**. Live database/core schema: **unverified**. Migration/option state: **unverified**. Staging: **not accepted**. Live retest: **not performed**. No source audit, green CI or candidate ZIP proves these states. Freeze exact deployed artifacts and DB before diagnosing any live symptom or claiming its correction is deployed. No production merge/deploy is authorized by this ledger.

Remaining staging gates: immutable File 00/03/07/09/17/19/20/25/26 companion artifact parity, actual PHP/WP compatibility, real user/doctor/guardian states, File 19 notification/preferences/delivery acceptance including controlled provider outage, File 17 context, File 26 reconciliation, DB migrations/backups/restore/rollback, concurrent booking and privacy/RTL/browser journeys.
