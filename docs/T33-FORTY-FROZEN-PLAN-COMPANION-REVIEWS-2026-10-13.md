# T33 FROZEN: 40 File08 amended plan / three-central-plan / companion owner-to-code checks
**Date:** 13 October 2026 PKT. **Immutable reviewed baseline:** PR#19 exact SHA `531fc91f5a4c7d0f86f015a34e85ebc3c907178c`, GitHub Actions run `38356227736` green on THAT SHA. Repository `main` has independently advanced to `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`; separate unmerged PR#20 exact source HEAD `2b353c5ae6749208c504e97460101f561a85ee76`, with competing incomplete T32 fixes. The GitHub trees PR19 vs PR20 differ by ten files including `class-wca-future24.php`, canonical runner and regressions; the main branch also differs significantly from PR19 in contracts and source modules. They must NOT be conflated or silently merged. Source parent for T33 is PR19; true staging/installed Hostinger remains unknown.

**Written governing plans actually read from DOCX:** File08 Worldwide Clinic and Appointments v1.1 Future24 Amended 2026-08-10 (FR001–018, NFR001–010, FUT01–24 and FUT24-DoD, specifically FUT13 rule-versioning and no-silent-bypass); Sabri Three Central Plans Consolidated Governing Master Plan (CEN-OWN-001, CEN-REL-001, CEN-STATUS-001); companion File07 Directory, File09 Global Verification and File14 Clinic USP plans. File00 owns identity, File09 certification, File17 communication, File19 notices, File24 assurance, File26 discovery, CF01 clinical records. **The attached plans define scope, NOT installed functionality.**

**Method:** Forty distinct bounded topical plan-to-exact-source inspections completed BEFORE any application source correction. Reviews 01–28 inspect native method/contract ownership (presence and limited path), not complete end-to-end operational proofs. Reviews 29–32 inspect actual FUT13 PHP writer/reader code and identify three unambiguous unsafe silent overrides. Reviews 33–40 inspect separate FUT14–24 owner paths. These 40 checks are NOT 40 independent exhaustive whole-repository code audits, real WordPress user journeys or production acceptance. No plan content replaced by outside assumptions.

| Check | Source requirement | Code inspected | Evidence / gap |
|---|---|---|---|
| 01 | FR001 — Clinic ownership and activation | `WCA_Service::create_clinic` | source path seen |
| 02 | FR002 — Services, fee and duration | `WCA_Service::save_service` | source path seen |
| 03 | FR003 — Availability DST | `WCA_Service::set_availability` | source path seen |
| 04 | FR004 — Slot capacity and holds | `WCA_Repository::slot_capacity_available` | source path seen |
| 05 | FR005 — Appointment request, consent | `WCA_Service::request_appointment` | source path seen |
| 06 | FR006 — Doctor decision verification | `WCA_Service::transition_appointment` | source path seen |
| 07 | FR007 — Reschedule hold | `WCA_Service::hold_reschedule_slot` | source path seen |
| 08 | FR008 — Cancel/no-show states | `WCA_Contracts::appointment_statuses` | source status found |
| 09 | FR009 — Check-in and completion | `WCA_Contracts::transition_matrix` | source status found |
| 10 | FR010 — Private dashboards | `WCA_Query_API::list_patient_appointments` | source path seen |
| 11 | FR011 — Emergency diversion | `WCA_Service::emergency_red_flag` | source path seen |
| 12 | FR012 File00 — Guardian and consent recheck | `WCA_Authorization::guardian_context` | source path seen |
| 13 | FR013 File17 — Appointment-derived relationship ACL | `WCA_Authorization::can_view_appointment` | source path seen |
| 14 | FR014 File07 — Review entitlement | `WCA_Repository::grant_review_eligibility` | source path seen |
| 15 | FR015 — Opaque ICS/calendar | `WCA_Service::appointment_ics` | source path seen |
| 16 | FR016 File19 — Payment state/notifications | `WCA_Service::create_payment_intent` | source path seen |
| 17 | FR017 CF01/File24 — Restricted clinical context | `SWC_CF01_Care_Context` | source owner found |
| 18 | FR018 — Complaint and appeal | `WCA_Service::appeal_complaint` | source path seen |
| 19 | NFR001 — Current actor/object authorization | `WCA_Authorization::can_staff_access_appointment` | source guard found, IDOR unverified |
| 20 | NFR002 — Patient privacy/minimization | `WCA_Privacy` | source owner found, live unverified |
| 21 | NFR003 — Durable outbox/retry | `WCA_Outbox::process` | source path found |
| 22 | NFR004 — Bounded query with signed cursor | `WCA_Query_API::decode_cursor` | source path found |
| 23 | NFR005 File25 — Dashboard accessibility/RTL | `WCA_Query_API::render_dashboard` | source path found; browser unverified |
| 24 | NFR006 — Runtime dependencies/observability | `WCA_Compatibility::dependency_health` | source path found |
| 25 | NFR007 — Migrations and rollback | `WCA_Compatibility::migrate_legacy_statuses` | repository only; DB unverified |
| 26 | NFR008 — System check/queue health | `WCA_Repository::outbox_queue_status` | source path found |
| 27 | NFR009 File00/09 — Current versioned contracts | `WCA_Contracts::contract_manifest` | source path found, installed unverified |
| 28 | NFR010 — Timezone and locale | `WCA_Service::valid_timezone` | source path found |
| 29 | FUT13 — Allowed rule field serializer | `WCA_Future24::verified_prerequisite_rules` | T32 restricted fields present |
| 30 | FUT13 — Rule input duplicates within save | `WCA_Future24::save_prerequisites` | D01 duplicate same key with contradictory behavior accepted |
| 31 | FUT13 — Rule duplicate within historic stored policy | `WCA_Future24::prerequisite_state` | D02 duplicate key is silently last-write-wins |
| 32 | FUT13 — Conflicting active policy versions | `WCA_Future24::prerequisite_state` | D03 older policy can silently override newer blocking with provisional |
| 33 | FUT14 File00 — Guardian appointment hub current relation | `WCA_Future24::family_hub` | source path found; real guardian unverified |
| 34 | FUT15 — Arrival signal not clinical check-in | `WCA_Future24::arrive` | source path found |
| 35 | FUT16 — Queue privacy | `WCA_Future24::queue_position` | source path found |
| 36 | FUT17 File19 — Disruption state | `WCA_Future24::create_disruption` | source path found |
| 37 | FUT18 File17 — Support participants | `WCA_Future24::add_participant/revoke_participant` | source paths found |
| 38 | FUT19 File17 — Virtual room TTL and consent | `WCA_Future24::request_virtual_room` | source path found |
| 39 | FUT20/21 — FHIR + SMART adapter owned truths | `WCA_Future24::fhir_projection/smart_find` | source paths found |
| 40 | FUT22–24 File19/26 — Calendar reconciler and governance | `WCA_Future24::capabilities/governance_log` | capability/owner paths found |

## Three proven source defects collected BEFORE edits
**T33-D01 — malformed duplicate FUT13 rule keys are accepted on write.** In `save_prerequisites`, looping `$raw_requirements` simply appends `$type:$label` rule maps with no duplicate-key check. An input like `[{type:document,label:ID,missing_behavior:block},{type:document,label:ID,missing_behavior:provisional}]` can be stored as one active policy with contradictory requirements. This contradicts FUT13 bounded versioned rule/no-silent-bypass promise. Correction: reject duplicate normalized keys at the input boundary, before DB writes.

**T33-D02 — historical active FUT13 policy can contain duplicate contradictory rules, silently last-write-wins.** `prerequisite_state` sets `$requirements[$key]=$behavior` without checking duplication in same policy. Even if new writes deny duplicates, physical historical DB may contain these rows. Correction: fail closed on repeated rule key inside a policy, no partially allowed readiness, no destructive migration.

**T33-D03 — two *active* policy versions silently override each other with the older one last.** `feature_rows_for_clinic` orders IDs ascending, `prerequisite_state` reverses to most recent first, but then overwrites `$requirements[$key]` on each older policy. A newer required `block` can silently become `provisional` from an older row and `complete=true` when no evidence exists. The written plan requires rule versioning and no silent bypass; it does not specify automatic override semantics for conflicting simultaneously-active policies. Safest provable correction: **explicit 503 conflict/no ready** if two applicable active policy records give a different behavior for the same key; preserve identical duplicate key across distinct policy records for compatibility. Do not invent a "newer always wins" automatic policy law without Founder authorization. Installed DB rows/migrations may need separate evidence-based reconciliation.

## Corrections after frozen ledger
Narrow native FUT13-only write+read checks; avoid changing File00/09/17/19/24 and preserved T31 group UUID and T32 four-field serializer. Native isolated PHP real methods test duplicate input, historical duplicate, conflicting active policies and valid nonconflicting policies, assert DB writes fail-closed and no false readiness. No physical DB migration or deploy; preserve candidate 1.2.15 Core source schema 3.4.0 Future24/Continuity source contracts 1.1.0. Entire canonical PHP7.4/8.3 matrix, zero-orphan runner, deterministic double ZIP plus verifier on *new exact SHA*. This PR alone cannot reconcile the independently changed `main` or draft PR#20.

**Required Live-First realities:** Repository baseline PR19 and distinct main/PR20 HEADs above; Deployed Version UNVERIFIED; Physical DB Version UNVERIFIED (source schema not DB proof); Migration State UNVERIFIED; Live Verification Status NOT PERFORMED. Need actual installed source/runtime/wp_options/DB/companion versions, database policy conflict inventory, IDOR/guardian/clinician real tests, two post-final staging review cycles, isolated backup+restore, Founder approval and post-deploy parity before Resolved.
**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
