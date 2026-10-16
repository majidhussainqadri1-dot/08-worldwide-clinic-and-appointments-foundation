# T44 FROZEN 40-plan/source/companion review ledger — 17 October 2026

**Exact baseline:** File08 unmerged T43 PR34 source commit `9847d4f49dcb825b1f643d305a168c0f3792d9bf`. GitHub main independently `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. The source plugin candidate is NOT installed/deployed code. WordPress/DB/schema version, migration options, installed File00/09/17/19 companions and package parity unverified. File00 PR54 separately unmerged.

**Actual authoritative source documents reviewed:** user-attached File08 `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx`: FUT08 states manager/authorized doctor aggregate 7/30/90-day capacity heatmap, aggregate-only, low-volume suppression, bounded range and **measured query performance**; FUT24 states degraded-mode truthfulness, explicit source failures, provenance. Three Central Plans Consolidated Governing Master Plan establishes File08 canonical availability/schedule authority, File00 actor/guardian, File09 doctor verification, File17 calls, File19 notification, File20/25 shell; File07/09/14/25 companion plan boundaries also consulted. Code methodology: 18 FR, 10 NFR, 12 paired Future24 requirements under independent scope anchors. These are 40 distinct source/invariant screens **NOT** forty independent browser/WordPress/MySQL E2E sessions. Each passed/missing result has a machine-readable code source/anchor in T44 QA.

| Round | Requirement family | Scope/related owners |
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

## Fresh T44 findings — all frozen before corrective source edit
- **T44-D01 — R32 FUT07+08 / NFR004, failed heatmap availability read returns fabricated zero capacity.** `WCA_Future24::heatmap()` calls `$wpdb->get_results()` for active availability rules and only rejects **null**. WordPress may return an empty result array while `$wpdb->last_error` is populated (SQL error state), and a non-array return would be cast and iterated. This permits projecting zeros/free estimates rather than explicit degraded error. **Fix after freeze:** clear per-read DB error before query and require actual array with empty `last_error`; reject all SQL failure conditions with 503 and non-success code. Native negative fixture must inject nonempty last_error + [].
- **T44-D02 — R32 FUT07+08 / NFR007, invalid stored capacity exception silently ignored.** In `heatmap()` for the current local date, stored `type='capacity'` with invalid `capacity` results in `WCA_Service::strict_int(...)` null and continues with prior capacity, presenting an inaccurate valid-looking configured-capacity estimate. This is distinct from a transient DB read fault: stored rule/migration corruption is concealed instead of degraded. **Fix after freeze:** controlled fail-closed 503 for invalid matching capacity exception; do not silently fallback to base rule; negative method fixture with actual saved availability rows, retaining valid capacity and closed/open semantics.

Both source findings are **provisional for the real website**, no live error evidence. Freeze first, then minimal source fix, strict positive+negative PHP fixtures, T36–T43 legacy regression suites, exact-head PHP7.4/8.3, reproducible package, two after-final no-code source verification sweeps. Actual WordPress/MySQL staging fault injection, migration state, backup+rollback, Founder approval and Live re-test remain mandatory. No main merge/live deploy.
