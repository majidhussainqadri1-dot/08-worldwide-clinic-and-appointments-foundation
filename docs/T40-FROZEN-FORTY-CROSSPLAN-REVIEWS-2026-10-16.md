# T40 — 40 plan/cross-owner source checks frozen before new corrections

Review date: 2026-10-16. Baseline File08 T39 candidate **`a052b5aa4786a17ad5cfb75c37e594ed5ed4543b`** (PR #30, unmerged); main `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`, separate. File00 inverse-identity owner PR #54 remains separate Draft; **installed versions and DB are not verified**.

**Primary text read**: user-attached `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx` paragraphs identifying FR001–18, NFR001–10 and F08-FUT-01–24. Specifically FUT22 paragraphs 370–373 require a verified doctor's optional external busy imports, expiring projections, provider-token exclusion, conflict filtering, stale provider degraded behavior and **reconciliation audit**. FUT21 requires canonical fresh Hold/Book and error propagation. The attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026` establishes File08 appointment ownership, no duplicate data owners and source trace law; companion File07/09/14/25 provide directory, verification, conversion and UI boundaries. These documents are source truth for this audit; GitHub current candidate is implementation truth only, never deployed truth.

**Method**: 40 separate source/owner trace screens (18 FR, 10 NFR and 12 Future24 paired scopes), with extra negative invariants in R39 FUT21+22. They are source code/contract presence checks, not forty independent WordPress/MySQL/browser proof runs. Freeze the entire review and findings before code edits.

| Round | Plan scope | Review focus |
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

## Defects found in original code before correction

- **T40-D01 (R39 FUT22 — source confirmed)**: `WCA_Future24::external_busy_conflict()` calls `WCA_Plan_Guard::practitioner_id($practitioner_ref)`; if it yields zero, it immediately returns **`false`** (= definitely no busy calendar). This is consumed by `WCA_Service::hold_slot()` and `hold_reschedule_slot()` and by FUT21 smart find. A missing/failed/ambiguous File09 practitioner mapping must never be represented as positive free-slot evidence. Correction: controlled `WP_Error` (503/degraded), not boolean false; no SQL check attempted. Baseline native negative fixture required.
- **T40-D02 (R39 FUT22 — source confirmed)**: `WCA_Future24::save_verified_external_busy()` sanitizes provider and event ID but does not reject empty values. Thus records can be persisted with `hash('sha256', '')` for provider event identity and blank provider, undermining reconciliation source/provenance and event differentiation. Correction: require bounded, nonempty provider ID and event ID in the verified-owner entry point **before** any insert/outbox; retain opaque hashes and avoid raw provider secrets. Negative method fixture required.

## Owner boundary, conservative conclusions
File09 verification remains `SWC_Doctor_Authority`/File09 owner; File08 owns operational busy projections, not provider secrets/tokens, File19 owns notifications and File26 ranking. Source failures are NOT claimed to have occurred on a live deployment. Existing T36–T39 positive and negative tests must survive. No changes to live site or main. Exact-HEAD CI/package + two post-final-code source sweeps + File00/File09 staging compatibility, actual DB schema, migration options, backup/restore, Founder approval and live re-test are nonwaivable.
