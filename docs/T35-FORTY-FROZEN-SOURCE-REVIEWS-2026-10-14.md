# T35 — Forty new scoped File08, central master and companion-owner Source Reviews (FROZEN BEFORE CODE REPAIRS)
**Audit date:** 2026-10-14 Pakistan. **Frozen exact development HEAD** = current green Draft PR#24 `25de8f7679a03da2635447d3a93b80f37363f14c` (File08 source software 1.2.16), associated GitHub Actions `38428669141` success applies ONLY to this parent commit. Repository `main` separately `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`; PR#22 and PR#23 are different unmerged code realities, no live/production code established. New T35 branch sourced from PR#24 exact HEAD to preserve validated FUT13 duplicate/conflict, File14 outcome and schema contracts.

## Governing sources actually consulted
Uploaded amended **File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026 v1.1 Future24 (10-Aug-2026)**, canonical FR001–FR018, NFR001–NFR010 and FUT01–FUT24; specifically FUT14 guardian hub must recheck current File00 relation for every protected dependent, FUT15 is an operational arrival-only signal and NOT clinical `checked_in`, FUT16 is privacy-safe *live* queue position with stale/disruption handling and no public cached disclosure. Uploaded **Three Central Plans Consolidated Governing Master Plan 2026**, including exact owner law, actor/object-purpose checks, external failure degraded states, source-vs-live evidence and release gates. Uploaded File07 Doctors Directory, File09 Global Doctor Verification, File14 Clinic USP and File25 Public UI companion plans provide owner boundaries and acceptance context, not proofs installed code meets these conditions.

**Methodological boundary:** 40 distinct bounded requirement-to-real-Source checks were finished *before any source repair*. Checks 01–36,39–40 inspect named Source methods and scoped contract behavior, and cannot assert 38 complete end-to-end operational workflows. Reviews 37–38 examine real Future24 queue/guardian methods and confirm two specific fail-safe discrepancies. These were not forty exhaustive whole-repo code audits, real database tests or twenty sets of re-used historic green CI; counting a function anchor as a full feature test is forbidden.

| Review | Written plan + owner | Source inspected | Frozen code finding |
|---|---|---|---|
| 01 | FR001 File07/09 — Clinic identity/verification | `Service create_clinic` | Source method present; installed owner unverified |
| 02 | FR002 File00 — Services and 0% commission | `Service save_service` | Source method present |
| 03 | FR003 — Availability DST | `Service set_availability` | Source method present |
| 04 | FR004 — Slots, holds, capacity | `Service search_slots/hold_slot` | Source methods present; real concurrency unverified |
| 05 | FR005 File00 — Patient consent and booking | `Service request_appointment` | Source method present |
| 06 | FR006 File09 — Doctor acceptance/decline | `Service transition_appointment` | Source method present |
| 07 | FR007 — Reschedule preservation | `Service hold_reschedule_slot` | Source method present |
| 08 | FR008 — Cancellation and no-show | `Contracts status lifecycle` | State registry present; used for new FUT16 cross-state review |
| 09 | FR009 — Check-in/completion | `Contracts statuses` | Status paths present; queued signal is NOT lifecycle state |
| 10 | FR010 File20 — Patient/clinic private dashboard | `Query_API list_patient_appointments` | Source method present |
| 11 | FR011 — Emergency red-flag diversion | `Service emergency_red_flag` | Source method present |
| 12 | FR012 File00 — Guardian/age/consent authority | `Authorization guardian_context` | Source method present |
| 13 | FR013 File17 — Appointment-scoped communication | `Authorization can_view_appointment` | Source method present |
| 14 | FR014 File07 — Review eligibility owner | `Repository grant_review_eligibility` | Source method present |
| 15 | FR015 — Opaque calendar/ICS | `Calendar_Link provider_webhook` | Source method present |
| 16 | FR016 File19 — Financial state and delivery | `Service create_payment_intent/Outbox` | Source methods present |
| 17 | FR017 CF01/File24 — Restricted patient records | `SWC_CF01_Care_Context` | Source owner exists; installed version unverified |
| 18 | FR018 CF02 — Complaint/appeal | `Service appeal_complaint` | Source method present |
| 19 | NFR001 File00 — Per-action current actor/object authorization | `Future24 require_appointment / Authorization` | Method present; FUT16 state freshness missing |
| 20 | NFR002 File24 — Privacy/no disclosure of peer identity | `Future24 queue_position aggregate only` | No peer ID in output; stale signal risk |
| 21 | NFR003 — Degraded provider/data-read behavior | `Future24 family_hub WP_Query` | D02: silent failure can report completed result |
| 22 | NFR004 — Bounded/accurate pagination | `Future24 family_hub 200-row pages` | D02: failed later page silently misstates pagination_complete |
| 23 | NFR005 File25 — RTL keyboard/mobile navigation | `Query_API render_dashboard` | Source present; actual browser unverified |
| 24 | NFR006 — Privacy-safe logging | `Observability log` | Source present |
| 25 | NFR007 — Schema/migration/rollback | `Future24 maybe_upgrade and install_schema` | Source present; physical schema unverified |
| 26 | NFR008 — Outbox queue health | `Outbox process and Compatibility` | Source present |
| 27 | NFR009 File00/09/17/19 — Versioned companion owner compatibility | `Contracts contract_manifest` | Source registry present; installed parity unverified |
| 28 | NFR010 — Timezone/localization | `Service valid_timezone and translations` | Source present; staged DST unverified |
| 29 | FUT01–02 — Waitlist/requests | `Future24 join_waitlist/save_windows` | Source methods present |
| 30 | FUT03–04 — Series/resource capacity | `Future24 create_series/reserve_resource` | Source methods present |
| 31 | FUT05–06 — Group + safe reschedule | `Future24 join_group_session/safe_reschedule` | Source methods present |
| 32 | FUT07–08 — Buffers/heatmap | `Future24 slot_allowed_by_policy/heatmap` | Source methods present |
| 33 | FUT09–10 — Scheduling intelligence | `Future24 advisor/no_show_forecast` | Source methods present |
| 34 | FUT11–13 — Questionnaire/readiness/prerequisites | `Future24 save_questionnaire/readiness/prerequisite_state` | T34 FUT13 fixes present; real DB unverified |
| 35 | FUT17 — Disruptions/rebook-only default | `Future24 create_disruption` | Source method present; external owners unverified |
| 36 | FUT18–19 — Interpreter/room consent | `Future24 add_participant/request_virtual_room` | Source methods present |
| 37 | FUT14 File00 — Guardian hub current dependents + complete pagination | `Future24 family_hub; WP_Query and guarded rows` | D02: WP_Query failure produces partial/empty result and asserts pagination_complete |
| 38 | FUT15–16 — Arrival signal vs live queue position | `Future24 arrive and queue_position` | D01: queue_position accepts existing arrival after lifecycle cancelled/completed/no_show |
| 39 | FUT20–21 — FHIR/SMART opaque reference mapping | `Future24 fhir_projection/smart_find` | Source methods present |
| 40 | FUT22–24 — External calendar/episode/governance | `Future24 external_busy_conflict/create_episode/governance_log` | Source methods present |

## Both proven defects collected, no source corrections before this ledger
**T35-D01 FUT16 canceled/completed/no_show stale queue:** Actual `WCA_Future24::arrive` only allows arrival in `confirmed` or `reschedule_pending`, but `WCA_Future24::queue_position` only verifies appointment exists/actor permission and previously recorded unexpired arrival row, with no present lifecycle status recheck. After a legitimate cancellation, completed appointment or no_show (while old arrival still within expiry), the patient can receive `appointments_ahead` as if still in live queue. This contradicts live queue stale-state handling and canonical appointment lifecycle owner truth. Correct by explicitly rechecking current eligible status BEFORE queue record SQL read and returning 409 `wca_queue_appointment_state` otherwise. Existing post-cancel peer arrival aggregate may still include stale entries; this limitation is separate real-DB design acceptance work, not falsely claimed resolved by self recheck.

**T35-D02 FUT14 guardian hub incomplete failed DB page mislabeled complete:** `WCA_Future24::family_hub` builds a WP_Query page and appends current File00-verified dependents. It loops until fewer than 200 rows and unconditionally returns `pagination_complete=true`. Unlike other File08 query functions, it neither checks `$wpdb->last_error` nor rejects unexpected/non-array posts after a WP_Query read. If page 1 or any later page failed, an empty or truncated private guardian list is incorrectly returned as a complete success. Correct by checking query/DB source result *on each page* before appending, fail-closed structured 503 with no partial list. Preserve current File00 guardian rechecks and valid complete pages; do not invent a new guardian relationship owner.

## After frozen ledger: implementation, tests and exact SHA gates
Use only scoped Future24 edits and isolated PHP7.4/8.3 real-method regression with WP_Query and $wpdb mocks for canceled/completed/no_show queue, canonical confirmed queue, first/later guardian page database failure/no partial success, normal paged results and no private field leakage. Include test in canonical run-all and zero-orphan gate, preserve PR24 entire test/owner source. Source software 1.2.16 and Core source DB schema/Continuity/Future24 version unchanged (verify source constants). No physical data migration, merge, deploy or live claim. Test new exact T35 HEAD via full PHP matrix and deterministic candidate double ZIP/independent verifier.

## Five mandatory distinct realities
Repository HEAD frozen PR24 above; **Deployed Version UNVERIFIED / Physical DB Version UNVERIFIED / Migration State UNVERIFIED / Live Verification Status NOT PERFORMED**. Required: exact Hostinger installed File08 and dependencies checksums/runtime/logs/config; physical tables/rows/wp_options/schema/migration; installed File00/07/09/14/17/19/20/24/25/26/CF01; real guardian revocation and queue disruption/stale entries; two fresh staging reviews and restore/rollback; Founder signoff, deploy/live parity+re-test. 
**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
