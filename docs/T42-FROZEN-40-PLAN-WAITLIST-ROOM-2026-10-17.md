# T42 — 40 plan-specific File08/Central/Companion checks, frozen before edits

**Date:** 2026-10-17. **Frozen File08 source candidate:** `20eadc6937e97320e3182c160a47ed49da5b3b67` (T41 PR32). GitHub main separately `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. **Installed File08, File00/17/19, DB schema and migration parity unverified**.

Actually read the user's attached **File08 Worldwide Clinic v1.1 Future24 Amended master plan**: FUT01 paragraphs 326–329 explicitly demand expiring, idempotent, actor/guardian-authorized waitlist, `no-auto-book`, stale availability recheck and File19 notification ownership; FUT19 paragraphs 398–401 require secure short-lived room request, current teleconsult consent, `recording=false` and File17 as sole transport owner. Also read the attached **Three Central Plans Consolidated Governing Master Plan** (File00 identity; File08 appointments; File17 conversations/calls; File19 notifications; File20/25 shell/visual; CF01 records) and File07 Directory / File09 Verification / File14 Conversion / File25 Public UI plans.

These 40 distinct FR/NFR/FUT requirement-family checks are **repository source/invariant inspections**, not 40 full WordPress/MySQL/browser E2E tests; source presence never alone proves runtime acceptance. Freeze findings after the full set, correct afterward:

| Round | Source requirement | Code/companion boundary |
|---:|---|---|
| 1 | F08-FR001 | Clinic/branch canonical data; File03 profile projection |
| 2 | F08-FR002 | Service catalog/fees and File14 zero-commission CTA |
| 3 | F08-FR003 | Availability rules and UTC/DST exceptions |
| 4 | F08-FR004 | Slot search/hold fresh-capacity truth |
| 5 | F08-FR005 | Patient/guardian idempotent request |
| 6 | F08-FR006 | Doctor confirm/decline and File19 outbox |
| 7 | F08-FR007 | Reschedule old-slot safety and version |
| 8 | F08-FR008 | Cancellation/no-show and slot release |
| 9 | F08-FR009 | Clinical check-in/completion vs arrival separation |
| 10 | F08-FR010 | Role-scoped clinic/patient schedule pages |
| 11 | F08-FR011 | Emergency diversion before persistence |
| 12 | F08-FR012 | Versioned consent and current guardian File00 |
| 13 | F08-FR013 | File17 relationship/context-only bridge |
| 14 | F08-FR014 | One real completed review File07 |
| 15 | F08-FR015 | ICS external calendar no canonical override |
| 16 | F08-FR016 | Payment provider and zero-commission ledger |
| 17 | F08-FR017 | Encrypted continuity CF01 records partition |
| 18 | F08-FR018 | Complaint/appeal CF02 support owner |
| 19 | F08-NFR001 | IDOR/purpose-limited File00/09 identity |
| 20 | F08-NFR002 | Retention/erasure/legal hold owner |
| 21 | F08-NFR003 | Outbox durability and File19 ACK |
| 22 | F08-NFR004 | Bounded paging/source failure fail closed |
| 23 | F08-NFR005 | WCAG/RTL and File25/20 shared tokens |
| 24 | F08-NFR006 | Privacy-safe audit and operational health |
| 25 | F08-NFR007 | Schema migration options/rollback |
| 26 | F08-NFR008 | CLI runbook repair and health checks |
| 27 | F08-NFR009 | Version compatibility and service provider absent |
| 28 | F08-NFR010 | Timezone/locale/currency normalization |
| 29 | F08-FUT01+02 | Waitlist no auto-book + flexible request windows |
| 30 | F08-FUT03+04 | Recurring intent + branch-specific resource reservation |
| 31 | F08-FUT05+06 | Group no IDOR + safe reschedule |
| 32 | F08-FUT07+08 | Buffers + private aggregate heatmap |
| 33 | F08-FUT09+10 | No auto-apply advisor + low-volume no-show suppression |
| 34 | F08-FUT11+12 | Approved intake templates + consent readiness |
| 35 | F08-FUT13+14 | Blocking prerequisites + current guardian hub |
| 36 | F08-FUT15+16 | Arrival signal not check-in + same-current-clinic peer queue |
| 37 | F08-FUT17+18 | Disruption complete set + explicit support role allowlist |
| 38 | F08-FUT19+20 | File17 virtual rooms + privacy-safe FHIR DTO |
| 39 | F08-FUT21+22 | SMART find/hold/book + external calendar busy |
| 40 | F08-FUT23+24 | Episode private graph + explicit nonclinical governance |

## Frozen source-confirmed defects BEFORE correction

**T42-D01 — R29, FUT01 idempotent waiting-list scope replay.** `WCA_Future24::join_waitlist()` returns `self::public_record($existing)` for an active row selected by feature_id/clinic_id/subject_user_id/fingerprint status+expiry without verifying persisted JSON object, approved date/timezone/service fields, `auto_book === false` or current guardian authority persisted in that row. A corrupted or older record may be treated as valid even when auto-book flag is unsafe or enrollment scope no longer matches the freshly authorized request. Correct with a strict read-only validator before replay; fail closed on malformed/stale row and do not silently overwrite.

**T42-D02 — R38, FUT19 idempotent virtual-room scope replay.** `WCA_Future24::request_virtual_room()` verifies live appointment state/type/teleconsult consent, then returns any active `room_requested` row for appointment without revalidating its saved `appointment_ref`, `transport_owner === File17`, `recording_assumed === false`, `teleconsult_consent_verified === true`, current clinic and finite room expiration. A corrupt/stale row can be returned as a valid File17 request. Correct with an owner-only strict validator before replay, without turning File08 into transport owner, and retain File17 outbox.

Source proof is for the exact **GitHub candidate only**, not evidence that these bugs are present on the installed website. Tests need to exercise validator via current class, ensure validation occurs before replay, and preserve original waitlist creation/room notification path. File00 canonical subject and guardian check require independently deployed proof.

**Acceptance:** new T42 40-source trace, native 2-owner replay validator negative tests, T36–T41 suite, PHP7.4/PHP8.3 and deterministic package exact-head; extra no-edit post-final spot checks. Staging WordPress/MySQL, owner companion interoperability, DB migrations/options/logs, backup/restore, founder approval, deployed checksum and live re-test remain blockers.
