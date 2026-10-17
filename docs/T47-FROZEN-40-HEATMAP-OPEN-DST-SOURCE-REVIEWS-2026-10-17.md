# T47 — 40 original-plan/central/companion source reviews: frozen before edits

**Review date:** 17 October 2026. **Exact baseline repository candidate** T46 Draft PR37 `4eb9485bf73bd0c0a1f7ac9b7b286744a0adb7a3`. Separate GitHub main `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03` (unmerged and not assumed deployed). File00 canonical identity helper companion PR54 remains separate Draft. No installed plugin checksum/version, WordPress/PHP logs, DB version/tables/rows/wp_options/migration state or staging parity has been verified.

**Governing original DOCX content actually read:** Attached `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx` §F08-FUT-07/08/09 (lines 648–659 in the extracted paragraph inventory): 7/30/90-day operational aggregate capacity and truthful advisory, authorization, timezone correctness, low-volume privacy safeguards and no fabricated availability. Attached Three Central Consolidated Governing Plans: File08 is sole schedule owner; File00 owns subject/guardian; File09 verified doctor; File17 communications; File19 notification; File20 shell, File25 view and File26 rank owners. Companion File07 Directory/File09 Verification/File14 conversion/File25 public UI plans remain independent of File08 schedule truth.

**Method:** 40 distinct requirements-to-source inspections: FR001–18, NFR001–10, twelve paired Future24 groups, with extra Round 32 FUT07/08 negative invariants below. These are source/contract screens **NOT forty WordPress/MySQL/browser E2E proof runs**; failures in actual runtime must be investigated against exact deployed reality separately. Frozen list BEFORE source modifications:

| Round | Requirement family | Reviewed source/owner contract |
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

## New defects confirmed on the exact baseline source, frozen before correction

**T47-D01 — R32 FUT08 invalid stored open exception silently ignored (degraded state misrepresented as normal capacity).** `WCA_Future24::heatmap()` sets `$open_override=true` as soon as it encounters `type='open'`; if an exception's start/end is absent, malformed or inverted, it silently retains `$start_h/$end_h`. This fabricates healthy-looking aggregate capacity from stale/corrupted availability override rather than a degraded error. A valid date-specific opening may legitimately open an otherwise closed weekday (T46), but an *invalid* override must not produce a successful capacity projection. Fix: validate both bounds before applying the override; invalid matching open must return controlled 503 without silently substituting regular hours. Fixture: invalid/open reversed/missing versus valid special opening on closed weekday.

**T47-D02 — R32 FUT08 ambiguous/nonexistent DST slot counted despite canonical File08 converter rejecting it.** Heatmap constructs `DateTimeImmutable::createFromFormat( '!Y-m-d H:i',..., $tz)` and advances slots via `modify('+N minutes')`, but does not call canonical `SWC_Helpers::to_utc(date, HH:mm, tz)` for each generated local slot. The File08 canonical helper explicitly rejects nonexistent spring-forward times and repeated fall-back local wall-clock times. With an active Europe/Berlin 2026-10-25 02:00 local rule, heatmap can count a DST-ambiguous capacity slot even though booking cannot safely use that wall-time. Fix: verify each candidate local slot START+END using canonical converter; reject ambiguous/nonexistent slots from capacity **without revealing patient data or failing the whole clinic if a single projected slot is DST-invalid**; maintain slot progress and UTC-day bucket. For a wholly invalid rule boundary itself, prefer controlled degraded error. Native fixtures: 2026-10-25 Europe/Berlin repeated hour vs unambiguous later slots, direct canonical converter behavior, zero patient identifiers.

**Confirmed scope:** These are source-code defects of a Draft GitHub build only, not demonstrated installed/live incidents. Correct after freeze, run genuine native negative PHP fixtures, full PHP7.4 and PHP8.3 and reproducible package, and two post-final-code source verifications. Real staging WordPress/MySQL/DST, File00/09/17/19 interoperability, performance and migration+rollback, Founder deployment approval and live re-test all outstanding.
