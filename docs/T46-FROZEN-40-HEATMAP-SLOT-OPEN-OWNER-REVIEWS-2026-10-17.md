# T46 — FROZEN 40 File08 requirements/central/companion source checks (BEFORE corrections)

Date: 2026-10-17. File08 exact GitHub candidate baseline **`a9dfb3b312ed44096be8cd194d49ac5ad1faf4dd`** is Draft PR36, not `main`. Repo main independently **`9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`**. File00 linked reverse-subject PR54 is another Draft. **Deployed runtime/plugin/package checksum, DB/schema/version, wp_options/migrations, installed companions, current live errors/config unknown.** GitHub-only findings are not live root-cause evidence.

**Requirements read from user-attached documents:** File08 v1.1 Future24 Amended paragraphs FUT07–09 (FUT08 7/30/90-day capacity/appointments aggregate, privacy-safe, correct service/doctor schedule owner; FUT09 advisory only, no auto-change), File08 NFR001–010 incl accuracy, performance, truthful degraded modes and cross-owner integration. Three Central Plans Consolidated Governing Master Plan (File08 schedules/capacity; File00 subject/guardian; File09 practitioner verification; File07 discovery; File14 CTA; File17 transport, File19 notifications, File20 shell, File25 UI; File26 ranking), companion File07/09/14/25 original plans. Code source audited: `includes/class-wca-future24.php::heatmap()` against canonical `includes/class-wca-service.php::generate_rule_slots()` and `exception_for_date()`. These 40 categories are separate source/contract reviews: **not forty full WordPress+MySQL+browser E2E tests.**

| Review round | Master Plan requirement | Cross-file/source dimension |
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

## Two source-confirmed defects frozen BEFORE application edits

**T46-D01 — R32 FUT07+08 / NFR reliability, end-of-day partial slot fabricated.** File08 `WCA_Future24::heatmap()` has `while ( $slot < $end )` and increments `configured_capacity` even when `$slot_end = $slot->modify('+interval minutes')` extends beyond `$end`. Actual canonical `WCA_Service::generate_rule_slots()` only allows `while ( $slot->modify('+duration minutes') <= $day_end )`. Example availability 09:00–10:30, 60min interval, capacity2: canonical candidate 09:00–10:00 only (2), heatmap counts 09:00 and 10:00 (4). Correction: count full contained slots only with `slot_end <= end`. Preserve DST, break and partial closed checks.

**T46-D02 — R32 FUT07+08 / File08 sole schedule owner, approved open-day exception lost.** `heatmap()` checks `if ( ! in_array( $weekday, $rrule['days'] ) ) continue` BEFORE it scans `exceptions`. Canonical `WCA_Service::generate_rule_slots()` explicitly allows `(in_array($weekday,$days)||$open_override)` for an approved matching-date `type=open` exception, including on a normally closed weekday. Example rule active Mondays only with `open` exception on Tuesday for 09:00–11:00; search can offer availability while heatmap projects zero. Correction: perform after-exception eligible-day decision, accept valid `open` exception without duplicating scheduling ownership, keep all-day `closed` and capacity/break semantics.

**Fix approach:** only these exact File08 source defects after freezing ledger, build new PHP native actual `heatmap()` negative and positive fixtures, verify guard with fresh current source T46 trace. Retain T36–T45 suites, run exact-head PHP 7.4/8.3 and independently reproducible candidate ZIP, then two post-final no-edit source reviews. No automatic merge/deploy. Hostinger staging MySQL capacity/UTC/DST/concurrency, File00/09 companion parity, backup/restore and Founder-approved live re-test remain pending. Live is NOT verified/resolved.
