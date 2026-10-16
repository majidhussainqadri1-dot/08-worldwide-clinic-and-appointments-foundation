# T39 frozen 40 plan-to-code review ledger (pre-correction)

Date: 2026-10-16. **File08 frozen code candidate:** `1a358ead5ed02938fe2ee0bcb45494d53b4ffa8d` (unmerged T38 PR29). Independent GitHub main `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. File00 T38 helper PR54 `63dd097874d9309236526eafb1f1f1faa86655eb`. **Installed/live plugin, DB/schema/migrations/dependencies all unverified.**

Requirements read directly from user's uploaded File08 amended plan, especially Future24 amendment FUT15/16 requirement: queue estimate must be current, private, actor-scoped and fail on stale/disruption state; FUT18 approved support/interpreter only with explicit role and no clinical-write rights. Governing central plans and companion File07/09/14/25 restrict identity, verification, File17 transport, File19 notification and File26 ranking ownership.

40 distinct FR001–018, NFR001–010 and paired FUT01–24 scopes below are source/invariant reviews, **not** claims of 40 independent real WordPress/MySQL/browser workflows. Each scope must be checked against actual File08 class/route/regression evidence on exact candidate code. No source corrections until findings frozen.

| Round | Plan scope | Requirement being checked |
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

## Source-confirmed negative findings before corrective edits
- **T39-D01 (R36 FUT16):** In `WCA_Future24::queue_position()` the peer COUNT joins WordPress appointments and `_swc_status` but not current `_swc_clinic_id`. It checks only the operational arrival row's historical `arrival.clinic_id`. A peer moved to another clinic can remain counted in the old clinic queue while its status is still confirmed, violating live-current clinic isolation. Add a second current-appointment-meta join filtered by clinic, and a native regression for SQL shape/peer isolation. Cross-clinic current DB scenario remains a staging gate.
- **T39-D02 (R37 FUT18):** `WCA_Future24::add_participant()` silently changes any unsupported submitted `role` to `support` after `sanitize_key()`. An explicitly invalid submitted purpose (including role=doctor/admin) is thus accepted as a different permission, contrary to explicit appointment role consent and fail-closed input. A truly **omitted** role may default to `support` for compatibility; explicitly present unsupported/empty roles must error before any lock/query/storage/outbox. Test actual PHP method with invalid role and verify no mutation.

## Cross-file and release gates
File00 canonical subject lookup exists on separately unmerged PR54; File08 T38 consumes helper and fails closed absent provider. File03/07/09/14/17/19/20/24/25/CF01 versions and deployed artifact checksums have **not** been parity verified. Preserve privacy-minimal owner event contracts and current T36–T38 suite. After all 40 scopes recorded, fix only confirmed defects and run fresh negative tests on PHP7.4/8.3, deterministic package, two post-final-code source review sweeps, staging WordPress/MySQL clinical workflows, backup/restore, Founder approval and live re-test. No claim of Resolved Live.
