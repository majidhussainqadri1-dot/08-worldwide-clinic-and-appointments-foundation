# T45 — forty File08 / central / related owner source review scopes, frozen before correction

**Source baseline**: File08 Draft PR35 exactly `dfe9cd0f97423f47ebdbf21b263e9f163ed37742`; distinct **main** `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`; separate companion File00 PR54 unmerged. **Active deployed package/build checksum, plugin version, real database/schema/wp_options and migration state unknown.** Repository evidence must never be described as live.

**User's actual governing document checked:** `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx`, FUT08 paragraphs 354–357: authorized 7/30/90-day correct aggregate capacity with low-count privacy and bounded range, File08 sole schedule owner; FUT07 paragraphs 350–353: availability buffer truth and non-destructive current booking; FUT09 advisor paragraphs 358–361 consumes this capacity truth without autonomous modifications. `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026` requires one canonical owner (File08 schedules; File00 identity; File09 verification; File17 communication; File19 delivery; File20 shell; File25 views, File26 rank), fault/rollback/real-role proof and privacy. Companion File07/09/14/25 plans separately require that Files 07, 09 and 14 never fabricate File08 availability. **Source checks only**: 18 FR + 10 NFR + 12 Future24 paired groups, recorded below, NOT 40 independent WordPress/MySQL/browser integrations.

| Round | Requirement family | Review scope |
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

## Confirmed GitHub candidate source findings, frozen before patch

**T45-D01 — R32 FUT08: partial break intersection overstates configured capacity.** Exact `WCA_Future24::heatmap()` source on baseline uses only `$slot_hm >= $break['start'] && $slot_hm < $break['end']`, which considers the *slot start* but NOT its entire interval. E.g. a 09:00–10:00 slot and 09:30–10:00 break produces configured capacity 1 rather than unavailable 0. `WCA_Service::in_break(DateTimeImmutable $start, DateTimeImmutable $end,...)` canonical scheduling owner checks interval overlap (`start < break_end && end > break_start`). Fix: compute actual slot end, timezone-safe overlap, fail closed on invalid stored boundaries, preserve no-break and legitimate free slot. Native method fixture must demonstrate this specific failing prior case and correct post-fix result.

**T45-D02 — R32 FUT08: bounded 'closed' exception ignored in capacity projection.** Heatmap's per-date `closed` exception handling sets `$closed=true` and removes the **whole day** without distinguishing an all-day closed exception from a bounded partial-day closed interval. Conversely `WCA_Service::in_closed_exception()` treats a bounded closure as a slot interval and rejects only overlapping slots, preserving other hours. E.g. 09:00–11:00 availability with `closed` 09:30–10:30 should suppress only intersecting slots; the heatmap currently removes all capacity for day. This is an underestimation and an owner semantic divergence. Fix must preserve all-day closed, partial closed, and DST-safe interval handling without exposing patient identity. Native method fixture must test all three: partial overlap, disjoint slots and all-day closure.

**Fix boundaries:** no new owner DB, no changes to File17/19, no fabricated free slots, no clinical narrative. All 40 requirement family traces frozen now before source edits; native regression demonstrations, PHP7.4/8.3 exact-head CI, deterministic artifact and two post-final unchanged-code source checks required. Real Staging WordPress/MySQL, performance timing, deployment parity, migrations, backup/rollback, Founder approval and Live re-test remain unverified mandatory gates.
