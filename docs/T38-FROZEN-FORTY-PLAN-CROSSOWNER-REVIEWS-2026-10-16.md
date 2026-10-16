# T38 — 40 separate plan-to-source/companion reviews: frozen before corrections

**Review date:** 2026-10-16. **Repository File08 frozen baseline:** `b25705866b46188db7a73a83f61ffc0aee402e8f` (T37 PR28 candidate). **main** separately `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. **Production deployed code: unverified**.

## Actual source-of-requirements
Read the user's actual attached `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx`: chapter 5 FR001–018/NFR001–010; Future24 amendment FUT01–24 (particularly FUT18 appointment-bound, revocable, no clinical write authority, canonical File00 subject) and Future24 acceptance DoD 01–10. Compared with attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026`, and attached File07 Directory, File09 Verification, File14 Conversion and File25 UI plans. User's direction: check each scope first, freeze issues after all reviews, THEN fix, test, pack and report.

This ledger contains **40 distinct scope-specific source/contract review tracks** (18 FR + 10 NFR + 12 paired FUT). It is not evidence that 40 independent full semantic proofs, WordPress installations, real MySQL race tests or live checks happened. Each round must retain original plan requirement and owner, and findings are classified as source-confirmed / test-confirmed / unverified instead of false zero-defect claims.

| Review | Plan ID | Obligation and related-owner boundary | Investigated source/contract |
|---:|---|---|---|
| 1 | F08-FR001 | Clinic/branch identity, File08-only mutations | WCA_Service::create_clinic/create_branch; File03 profile projection |
| 2 | F08-FR002 | Services, fees, cancellation/refund, zero commission | WCA_Service::save_service and payment projections; File14 CTA is read-only |
| 3 | F08-FR003 | Availability recurrence, exception, DST | WCA_Service::set_availability/generate_rule_slots |
| 4 | F08-FR004 | Slot query freshness, hold expiry and capacity | WCA_Service::search_slots/hold_slot |
| 5 | F08-FR005 | Patient/guardian consented idempotent booking | WCA_Service::request_appointment; File00 guardian |
| 6 | F08-FR006 | Doctor confirm/decline/propose, outbox File19 | WCA_Service::transition_appointment/event_for_transition |
| 7 | F08-FR007 | Reschedule old-slot preservation, expected version | WCA_Service::hold_reschedule_slot/transition_appointment |
| 8 | F08-FR008 | Cancel/no-show release, policy, audit | WCA_Repository::release_appointment_slot |
| 9 | F08-FR009 | Doctor/staff check-in/completion/review control | WCA_Service::transition_appointment |
| 10 | F08-FR010 | Patient and doctor schedule dashboards, no IDOR | WCA_Query_API::list_patient_appointments/list_clinic_schedule |
| 11 | F08-FR011 | Emergency red flags immediate diversion | WCA_Service::emergency_red_flag; central clinical safety |
| 12 | F08-FR012 | Versioned teleconsult/guardian privacy consent | WCA_Continuity::grant_context_consent/revoke_context_consent |
| 13 | F08-FR013 | Only File17 context/participant entitlement, no chat owner | WCA_Service::file17_context_payload |
| 14 | F08-FR014 | One genuine appointment-controlled review | WCA_Repository::grant_review_eligibility/consume_review_eligibility; File07 display |
| 15 | F08-FR015 | Calendar/ICS and signed external status; no appointment authority | WCA_Service::appointment_ics |
| 16 | F08-FR016 | Fees/payment ledger intent; false paid prohibited | WCA_Service::create_payment_intent |
| 17 | F08-FR017 | Encrypted clinical continuity only, CF01 owner partition | WCA_Continuity::seal/open |
| 18 | F08-FR018 | Clinic/service complaint/appeal CF02 support owner | WCA_Service::create_complaint/appeal_complaint |
| 19 | F08-NFR001 | File00/09 canonical claims + object/field auth | WCA_Authorization::claims/can_view_appointment |
| 20 | F08-NFR002 | Privacy retention and erasure/legal hold | WCA_Continuity::privacy_eraser/apply_retention |
| 21 | F08-NFR003 | Durable event outbox/idempotency and File19 owner receipts | WCA_Outbox::require_owner_receipt |
| 22 | F08-NFR004 | Bounded private paging/signed cursors and read-error safety | WCA_Query_API::decode_cursor |
| 23 | F08-NFR005 | File20 shell + File25 responsive WCAG/RTL | assets/css/clinic.css and assets/js/continuity.js |
| 24 | F08-NFR006 | Privacy-safe diagnostic tracing and operator alerts | WCA_Observability::log/metric |
| 25 | F08-NFR007 | Fresh/install/upgrade/rollback idempotent migration | WCA_Schema::maybe_upgrade/rollback_to_snapshot |
| 26 | F08-NFR008 | Health/repair CLI/queue inspection | WCA_CLI and WCA_Schema::health |
| 27 | F08-NFR009 | Runtime 1.2.16/schema3.4/CF01/Future24 contract pinning | WCA_Contracts and plugin header |
| 28 | F08-NFR010 | en-US, Urdu/RTL, currency/timezone correctness | WCA_Service::valid_timezone/valid_currency |
| 29 | F08-FUT01+02 | Waitlist offer not auto-book; request windows | WCA_Future24::join_waitlist/save_windows |
| 30 | F08-FUT03+04 | Recurrence/resource strict branch lock | WCA_Future24::create_series/reserve_resource |
| 31 | F08-FUT05+06 | Group privacy and safe reschedule | WCA_Future24::join_group_session/safe_reschedule |
| 32 | F08-FUT07+08 | Versioned buffers and capacity heatmap | WCA_Future24::set_buffers/heatmap |
| 33 | F08-FUT09+10 | No individual no-show scoring; nonclinical advisor | WCA_Future24::advisor/no_show_forecast |
| 34 | F08-FUT11+12 | Questionnaire fields; encrypted answers elsewhere | WCA_Future24::save_questionnaire/readiness |
| 35 | F08-FUT13+14 | Blocking prerequisites and real guardian recheck File00 | WCA_Future24::verified_prerequisite_rules/family_hub |
| 36 | F08-FUT15+16 | Arrival signal not actual check-in; private peer queue | WCA_Future24::arrive/queue_position |
| 37 | F08-FUT17+18 | Disruption complete affected set; bound support identity | WCA_Future24::create_disruption/add_participant/revoke_participant |
| 38 | F08-FUT19+20 | File17 room transport; privacy-safe FHIR projections | WCA_Future24::request_virtual_room/fhir_projection |
| 39 | F08-FUT21+22 | SMART find-hold-book; external busy not canonical | WCA_Future24::smart_find/smart_hold/smart_book |
| 40 | F08-FUT23+24 | Episode privacy; human governance and no clinical automation | WCA_Future24::mutate_episode/governance_log |

## External source-repository snapshots — NOT installed/production versions
- File00 GitHub main at inspection: `2fa7c022ee9cd1b65432e900579512f304532442`
- File03 GitHub main at inspection: `636e3ef965423887f810718abec3cd1c11c3659d`
- File07 GitHub main at inspection: `2f4a89707724fd2b9946600afe10ddab27ec3c2d`
- File09 GitHub main at inspection: `cfc5f781a766330314dc98c42abeca0eb7786eba`
- File14 GitHub main at inspection: `d2bf13a9ce71b185452c17d0ad7c915ddfd6e78f`
- File17 GitHub main at inspection: `8ae656e51796d1f05865d8be5dca2480443d79ca`
- File19 GitHub main at inspection: `04078025b643ab7696e4cb4e37826bf152defa18`
- File20 GitHub main at inspection: `8a4dbcaf4fef8e926b9b834ecfde16c21a0f00ca`
- File24 GitHub main at inspection: `a5b8d49968a7a5a7d6f3f4655bea541bf38a9acb`
- File25 GitHub main at inspection: `347a4ff4d4c233c5ea6cd82c7786ee5398ea9d1e`
- CF01 GitHub main at inspection: `3fa32005f3d53aa8d354b25e5ac71201d05f80b4`

These SHA snapshots prove the independently retrieved repository HEAD values only. **Actual cross-owner contract compatibility remains unverified until the companion files/API implementations and runtime integration are tested; fetching HEAD does not prove deployed parity.**

## Frozen findings before any corrective coding
- **T38-D01 (review R37, FUT18):** Existing `WCA_Future24::add_participant()` duplicate-active-row short-circuit decodes `payload_json`, tests only `role`, and calls `public_record` as success. Does not validate current stored `subject_uuid`, appointment-bound/revocable bits, clinical_write_authority or authoritative File00 subject-ID binding. Corrupt/hijacked stored record can be mistaken for valid existing participation. Source confirmed; executable negative test still required.
- **T38-D02 (review R37, FUT18):** Existing `verified_support_participant_payload()` used by `revoke_participant()` ensures shape and nonzero `subject_user_id`, but does not compare stored `subject_uuid` to authoritative canonical subject for that user. File17 revocation event could identify a different platform subject from the record's subject ID. Source confirmed; executable negative test required.
- **Release gates not proved:** This scope review does not prove installed plugin, schema 3.4, options/migrations, companion versions, real WordPress/MySQL transactions, cross-owner delivery receipts, staging backup/restore, Founder approval or live re-test.

**Correction sequence**: do not modify production directly. First implement reusable fail-closed stored-participant consistency validation + targeted native negative cases on a dedicated T38 branch; register suite, await exact-head PHP7.4/8.3 QA and deterministic candidate package; preserve T37/T36 tests; only then consider staging and live parity.
