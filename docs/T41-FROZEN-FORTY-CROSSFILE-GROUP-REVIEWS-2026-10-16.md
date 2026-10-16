# T41 — 40 distinct File08 and central/companion requirement source checks (frozen before code edits)

**Date:** 2026-10-16. **Baseline GitHub File08 PR31 exact source:** `e512c91806c45b775414b7048e44d8037c5437b1` (prior T40 green). **main separately:** `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. Installed files, DB/schema/options/migrations and package SHA parity **NOT VERIFIED**; repo green never means deployed.

Original authoritative source text read: user-provided File08 amended master plan § F08-FUT-05 paragraphs 302–305 specifically requiring group scheduling capacity locks, **idempotent join/leave, participant privacy, guardian eligibility, no-overbooking and cancellation semantics**; Future24 DoD04 privacy/guardian/IDOR; user-provided Three Central Plans governing master (one canonical owner, File00 identity, File08 schedules, File17 group conversation/media, File09 doctor verification); File07 Directory, File09 Trust, File14 CTA, File25 UI plans.

40 **different plan-to-implementation requirement families** are in the table (FR001–FR018, NFR001–NFR010, 12 paired Future24 capabilities); use the existing native plan-source fixtures and *new* R31 FUT05/06 privacy invariants. This is a **source/code-contract review**, not forty independent WordPress/MySQL/browser E2E tests. Requirements missing/unimplemented are defects only when evidence is established, not inferred from regex match absence alone. Complete all forty review categories before correcting, then native negative fixtures + PHP7.4/8.3 regression + reproducible candidate package, staging parity and live test remain distinct.

| Round | Requirement | Focus |
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

## Frozen source findings (two independent bugs at R31, F08-FUT-05)

1. **T41-D01 (idempotent JOIN unsafe stored identity):** `WCA_Future24::join_group_session()` returns `self::public_record($existing_member)` immediately after `SELECT ... subject_user_id=%d AND status='group_member'`, with **no validation of its persisted `payload_json.member_ref`, `peer_identity_visible=false`, `guardian_user_id` or current canonical File00 subject/clinic/parent binding**. A stale/corrupt group row could be treated as successfully joined under the wrong subject or privacy scope. Verify persisted object payload, identity and privacy before replay; no auto-repair or mutation on corruption.
2. **T41-D02 (LEAVE unverified stored privacy):** `leave_group_session()` reads the stored member row, returns existing `group_left/group_cancelled` as idempotent success or UPDATEs the `group_member` row to `group_left` **without validating the persisted member UUID, private peer-visibility or clinic/parent scope**. A corrupted row can be mutated or returned as valid without verifying File00 member identity. Fail closed before the no-op or SQL mutation.

Both issues are source-confirmed on the exact baseline, **not proof of any live incident**. On the eventual corrected branch, implement one owner-local strict `verified_group_member_scope()` usable from join and leave; reject non-object malformed JSON, swapped canonical subject, forged visibility, nonnumeric guardian, wrong clinic/parent/patient, or missing File00 subject; keep real guardian authorization in `patient_context()`. Actual group concurrency/SQL rollback and third-party File00/17 interoperability still require staging.

**T41 acceptance evidence:** negative native method fixture, 40 source tracers, existing T36–T40 tests, exact-HEAD CI package and two after-fix no-edit spot checks; explicit counts for found/fixed/pending. No merge or live changes.
