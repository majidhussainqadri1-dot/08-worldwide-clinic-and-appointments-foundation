# T43 FROZEN 40 original-plan & cross-owner source reviews (before code modifications)

Date: 2026-10-17. Frozen **File08 GitHub source baseline** `7a333d6bd154d8d85d4879f444ef3db503e1f70d` (T42 Draft PR#33). Independent GitHub `main` `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. File00 reverse subject resolver companion PR#54 remains Draft. **Installed production, staging, database version, schema rows, wp_options migrations, active plugin dependencies/logs and ZIP checksum were not accessed.** GitHub version is not live truth.

**Original governing sources reviewed:** user-provided File08 Worldwide Clinic and Appointments v1.1 Future24 amended plan: FUT02 text requires bounded UTC windows, timezone preservation, overlap normalization, expiry and no-silent-booking; FUT04 requires atomic locks, clinic resource scope, overlap/capacity, stale release and rollback; DoD06 requires real resource concurrency and stale-hold checks; Central Three Consolidated Governing Plans forbid cross-owner shadow DB and grant File08 sole scheduling truth, File00 canonical identity, File17 transport, File19 notifications, File09 verification. Companion File07/09/14/25 plans inspected for cross-file boundaries. PHP7.4/8.3 native and owner CI are source gates; real MySQL/staging acceptance separate.

40 distinct requirement-family source inspections (not 40 WordPress/MySQL/browser tests) and specific negative cases: FR001–18 / NFR001–10 / 12 paired FUT01–24. Cross-owner eligibility must be checked on current installed state before staging merge or deploy.

| Round | Code owner family | Review dimension |
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

## Findings FROZEN before corrections

**T43-D01 — Round 29 FUT02, unsafe idempotent saved windows replay.** In `WCA_Future24::save_windows()` a current `open` record is fetched by appointment/clinic/fingerprint and returned immediately as `public_record( $existing )`. Source lacks persisted `payload_json` object & `windows`/service/timezone/current guardian/`auto_book=false` and non-expired row integrity recheck. Corrupt/stale values can be represented as an approved patient preference or silent booking scope. Source-only defect, NOT observed live. Add a read-only strict same-request validator in duplicate branch; reject altered/duplicate/unsorted windows, wrong guardian, incorrect fingerprint and expired records without writing or changing ownership.

**T43-D02 — Round 30 FUT04, stale/past resource reservation accepted.** `WCA_Future24::reserve_resource()` uses valid UTC and `end>start`, max 24h but **no check that end is in the future, start not already stale, or within resource booking horizon**. As a result, direct authorized REST call can write `reserved` operational records for entirely past time ranges, violating stale release and time bounded booking invariants. Add bounded horizon (start >= now − short tolerated clock skew; end>now; start<=now+180d) before locks, capacity DB and insert, preserving legitimate short clock skew and conditional booking. Negative tests call the actual method with older timestamps and assert no DB lock/write.

**Root-cause classification:** exact GitHub source candidate defects; installed state unverified. These are not proven production bugs. No merge/deploy, migrations or companion code modifications without full live-first parity and founder approval. Post-correction: separate T43 40-source review evidence, native negative PHP7.4/8.3, inherited T36–T42, deterministic package, two independent source checks, real staging WordPress/MySQL rollback and then live re-test.
