# T37 frozen plan-to-code audit ledger — before corrective edits

Date: 2026-10-15. Baseline exact unmerged source `0f3b20a1aed8cd0c826ff5a0959e461f33fc3b6e` (prior green T36 PR #27). Independent main `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. All checks are of GitHub repository source, not installed website.

Governing inputs: uploaded File08 Worldwide Clinic and Appointments amended Future24 plan, uploaded Three Central Plans Consolidated Governing plan, File07 Directory, File09 Verification, File14 Conversion and File25 UI plans. File08 is the canonical clinic/scheduling owner; File00 identity/guardian, 07 discovery, 09 practitioner verification, 14 analytics, 17 communications/rooms, 19 delivery, 20 shell, 24 assurance, 25 visual presentation, 26 search, CF01 clinical-record continuity must not be silently re-owned.

Review protocol: **40 distinct requirement-specific source/contract review checks**, each based on the named original plan requirement plus native source owner and regression context. A code-path anchor alone is evidence of presence, NOT functional completion; a genuine negative-path proof needs a test. Complete all 40 checks and freeze findings BEFORE doing any code correction. Subsequent native tests, PHP 7.4/8.3 QA, reproducible package, staging DB/WordPress, dependency/live parity are separately classified.

| Round | Plan requirement | Code-review focus | Evidence class |
|---:|---|---|---|
| 1 | FR001 | Clinic identity and single owner File08 | Repository source / negative test required for closure |
| 2 | FR002 | Fees/currency/no platform commission | Repository source / negative test required for closure |
| 3 | FR003 | DST/availability rules | Repository source / negative test required for closure |
| 4 | FR004 | Fresh hold and slot proof | Repository source / negative test required for closure |
| 5 | FR005 | Booking and idempotency | Repository source / negative test required for closure |
| 6 | FR006 | Doctor decisions and File19 events | Repository source / negative test required for closure |
| 7 | FR007 | Reschedule and lock integrity | Repository source / negative test required for closure |
| 8 | FR008 | Cancellation/no-show and slot release | Repository source / negative test required for closure |
| 9 | FR009 | Clinical check-in and completion | Repository source / negative test required for closure |
| 10 | FR010 | Role-scoped dashboards | Repository source / negative test required for closure |
| 11 | FR011 | Emergency diversion and no false care | Repository source / negative test required for closure |
| 12 | FR012 | Versioned consent/guardian | Repository source / negative test required for closure |
| 13 | FR013 | File17 context-only bridge | Repository source / negative test required for closure |
| 14 | FR014 | One-review eligibility | Repository source / negative test required for closure |
| 15 | FR015 | Calendar provider adapter not appointment truth | Repository source / negative test required for closure |
| 16 | FR016 | Payment ledger and no false paid | Repository source / negative test required for closure |
| 17 | FR017 | Continuity encrypted and restricted | Repository source / negative test required for closure |
| 18 | FR018 | Complaint CF02 appeal authority | Repository source / negative test required for closure |
| 19 | NFR001 | IDOR/field claims and File00/09 | Repository source / negative test required for closure |
| 20 | NFR002 | Retention/legal hold/export | Repository source / negative test required for closure |
| 21 | NFR003 | Outbox idempotent durable receipts | Repository source / negative test required for closure |
| 22 | NFR004 | Bounded cursor pagination | Repository source / negative test required for closure |
| 23 | NFR005 | Responsive WCAG and RTL | Repository source / negative test required for closure |
| 24 | NFR006 | Privacy-safe observability | Repository source / negative test required for closure |
| 25 | NFR007 | Schema migration rollback | Repository source / negative test required for closure |
| 26 | NFR008 | Health CLI repair | Repository source / negative test required for closure |
| 27 | NFR009 | Runtime/contract version parity | Repository source / negative test required for closure |
| 28 | NFR010 | en-US, Urdu timezone currency | Repository source / negative test required for closure |
| 29 | FUT01+02 | Waitlist and window no-auto-book | Repository source / negative test required for closure |
| 30 | FUT03+04 | Series and resource branch | Repository source / negative test required for closure |
| 31 | FUT05+06 | Group and safe reschedule | Repository source / negative test required for closure |
| 32 | FUT07+08 | Buffer, heatmap, DST | Repository source / negative test required for closure |
| 33 | FUT09+10 | Advisor and aggregate privacy forecasting | Repository source / negative test required for closure |
| 34 | FUT11+12 | Questionnaire and encrypted continuity readiness | Repository source / negative test required for closure |
| 35 | FUT13+14 | Blocking prerequisites and current guardian | Repository source / negative test required for closure |
| 36 | FUT15+16 | Arrival != checked-in and private queue peers | Repository source / negative test required for closure |
| 37 | FUT17+18 | Disruption completeness and participant revocation | Repository source / negative test required for closure |
| 38 | FUT19+20 | Room transport File17 and FHIR DTO | Repository source / negative test required for closure |
| 39 | FUT21+22 | SMART + external calendar not truth | Repository source / negative test required for closure |
| 40 | FUT23+24 | Private episode and no auto-clinical action | Repository source / negative test required for closure |

## Pre-correction review findings (read from exact baseline)

- **T37-D01 (FUT17, scope of review R37):** `WCA_Future24::clinic_appointments_between_all()` uses `(array) $q->posts` and loops over pages without checking WordPress query-post shape or `$wpdb->last_error`. A failed first or later page can be treated as zero results/partial success by `create_disruption()`, violating approved all-affected, no-silent-omission and File19-notification-contract semantics. Fix should fail closed, return explicit WP_Error and abort without committing disruption or notifications; test actual paging helper with injected failure on page 1 and page 2. **No live root-cause claim.**
- **T37-D02 (FUT18, scope of review R37):** `WCA_Future24::revoke_participant()` performs a database UPDATE before verifying persisted `payload_json` is a canonical support/interpreter subject with appointment-only and no clinical write authority. A broken payload is silently coerced to `[]`, then a File17 event can carry fabricated empty subject or fallback role. Fix should verify persisted JSON and scope *before UPDATE*, fail closed on corruption, and preserve valid actor authorization and transaction/outbox invariants. Regression with method-level malformed and valid fixture needed.
- **T37-RISK01:** Current staging WordPress package/DB tables/wp_options/migration, companion versions, runtime logs and artifact checksums cannot be inferred from GitHub.
- **T37-RISK02:** Approved NFR performance p75/p95, RTL WCAG, real MySQL race/concurrency, legal hold, File17/19/14 provider receipts, real guardian revocation and release/rollback remain separate unverified gates.

Actual outcome ledger to be appended as CI evidence on exact correction HEAD, without false claims of 40 E2E rounds. No source edit before this freeze.
