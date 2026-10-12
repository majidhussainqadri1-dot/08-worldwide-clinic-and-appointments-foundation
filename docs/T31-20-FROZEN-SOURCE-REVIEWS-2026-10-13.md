File08 T31 — frozen twenty scoped source reviews BEFORE implementation
Date 2026-10-13 PKT. Source frozen Draft PR17 SHA e18ce3913f6904635f1e1568c018fd45a0e89537 with historical successful Actions 38285497473. Main 70541974ce0ffb16aebef557c3016eb7447662f4 separate. No deployed artifact verified.

Written authorities actually examined: attached File08 v1.1 Worldwide Clinic and Appointments amended 2026-08-10 FR001-018 NFR001-010 FUT01-24, especially FUT05 group capacity/membership and File00 identity, owner/guardian authorization, privacy and degraded guarantees; attached consolidated three central plans and related File07, File09, File14 companion plans. Canonical owner File08 appointments; File00 patient identity/UUID, File09 doctor trust, File17 messages, File19 notifications, File24 security, File26 discovery, CF01 clinical records. Companion installed runtime parity is NOT proven.

Twenty separate bounded owner requirement-to-exact-source inspections were finished before source modifications. The first eighteen establish real PHP source paths and owner boundaries ONLY, not working production journeys nor exhaustive whole-repository inspections. Rounds 19-20 read actual Future24 PHP group membership and shared DTO logic. Count below is not twenty real WordPress E2E checks.

| Round | Governing requirement | Source checked | Frozen result |
|---|---|---|---|
| 01 | FR001 File07/09 | Clinic identity/create/activate — WCA_Service create_clinic activate_clinic | methods present |
| 02 | FR002/016 | Fees/service and payment bridge — WCA_Service save_service create_payment_intent | methods present |
| 03 | FR003 NFR010 | Availability and DST — WCA_Service set_availability local_datetime | methods present |
| 04 | FR004/005 | Slots holds idempotent booking — WCA_Service search_slots hold_slot request_appointment | methods present |
| 05 | FR006-009 | Clinical booking lifecycle — WCA_Service transition_appointment | methods present |
| 06 | FR010 File20 | Private dashboards and scoped cursors — WCA_Query_API list_patient_appointments list_clinic_schedule | methods present |
| 07 | FR011 | Emergency red flag diversion — WCA_Service emergency_red_flag | method present |
| 08 | FR012 File00 | Guardian and consent truth — WCA_Authorization guardian_context | method present |
| 09 | FR013 File17 | Clinical relationship opaque context — WCA_Authorization can_view_appointment; WCA_Opaque_API | methods present |
| 10 | FR014 | Controlled review eligibility — WCA_Service transition_appointment | state/owner path present |
| 11 | FR015 | Calendar provider and signed calendar — WCA_Calendar_Link provider_webhook | method present |
| 12 | FR017 CF01 File24 | Clinical record privacy — SWC_CF01_Care_Context; WCA_Privacy | owner path present |
| 13 | FR018 | Complaint escalation and appeal — WCA_Service create_complaint appeal_complaint | methods present |
| 14 | FUT01-04 | Waitlists windows recurrence resource — WCA_Future24 join_waitlist save_windows create_series reserve_resource | methods present |
| 15 | FUT06-10 | Reschedule buffer heatmap predictions — WCA_Future24 safe_reschedule set_buffers heatmap no_show_forecast | methods present |
| 16 | FUT11-14 | Questionnaire readiness guardian hub — WCA_Future24 questionnaire_for_appointment readiness | methods present |
| 17 | FUT15-24 | Queue room FHIR governance — WCA_Future24::capabilities and handlers | capability source present |
| 18 | NFR003/007/008 | Outbox migration QA run-all — WCA_Outbox WCA_Compatibility tests/run-all | source/test paths present |
| 19 | FUT05 File00 NFR001 | Canonical group member identity — WCA_Future24 join_group_session to WCA_Authorization subject_uuid | D01: fails to deny empty canonical member reference |
| 20 | FUT05 NFR002/003 | Stored Future24 payload DTO integrity — WCA_Future24 public_record JSON decode | D02: corrupt JSON normalized as empty payload |

D01: WCA_Authorization::subject_uuid returns empty string when File00 cannot ensure a canonical subject UUID; WCA_Future24::join_group_session directly embeds that value as member_ref inside a new persistent group_member row without rejection. Correction after freeze: fail-closed before group membership DB/lock, no invented File08 identity.
D02: WCA_Future24::public_record decodes stored payload_json and replaces malformed/non-array JSON with empty array; nonempty JSON positional lists also appear as legitimate operational data, masking source corruption. Correction after freeze: validate stored root JSON map vs legitimate empty legacy state and deny corrupted DTO without partial projection; no physical DB migration.

After ledger freeze correct only proven defects, add real PHP7.4/8.3 isolated native-function tests, run retained 103 registered suites plus new no-orphan test and deterministic double ZIP/independent verifier on final immutable SHA. Source candidate software 1.2.15, source Core schema 3.4.0 and Continuity/Future24 schema 1.1.0 unchanged. Do not merge or deploy. Hostinger installed File08/plugins, runtime versions/checksums/errors, physical MySQL/wp_options, migrations, real File00/09/17/19/24/26/CF01 integration, group concurrency, backup/restore, two independent staging cycles and founder approval all unverified.
Required five live-first truths: Repository frozen PR17 above; Deployed Version UNVERIFIED; DB Version UNVERIFIED physically; Migration State UNVERIFIED; Live Verification Status NOT PERFORMED.
Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔