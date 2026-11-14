# File08 V12.3 — Executed Evidence Register & Release Blockers (2026-11-14)

**Governing file:** Sabri-Website-Master-Plan-V12.3.docx, chapter 11, File08 pp.151–156; execution blueprint File-08-V12.3-Complete-Execution-Plan-2026-11-14.docx.
**Exact baseline:** main `d086840f7e2ac0ed328006385f064e0e2fb58cb1`, candidate plugin 1.2.16, declared WCA schema 3.4.0. **Neither is deployed-state evidence.**
**Candidates:** PR49 `1ad072db46f440fb91184aa8d9dec311a90ed705` (seven source defect groups, CI #43380112964 6 jobs green), PR50 `bec3b6204a93559c2db2bbc45c65e85c9f1e237f` (two source UI groups, full source CI #43617456113 success), PR51 FUT07 (four source groups including midnight adjacency, CI final source SHA requires verification), and **PR53** integrated draft after exact blob parity checks; NOT merged or deployed.
**Live reality freeze attempt:** WordPress.com/Jetpack reports site_disconnected; WPVibe site has no approved connection, connect attempt failed with HTTP 530. No live plugin tree, DB, wp_options, migrations, logs, dependencies or checksum obtained. HTTP 530 is a symptom, NOT root cause. No production source or deployment actions authorized by this document.

## Evidence law
- **SRC** = a named source owner/method exists at the above main or candidate; this is not functional acceptance.
- **CI** = test passed at a stated immutable source SHA; source-only CI or disposable WP/MariaDB is not Hostinger staging.
- **WP** = real integrated staging confirmation; **none established**.
- **LIVE** = actual deployed plugin, DB/schema/migration and live user journey; **none established**.
- Source-confirmed defect cannot be declared fixed in main or live merely because a draft PR contains its correction.
- Every row below has `WP=pending; LIVE=pending`. The last column is a **next test**, never a claim that it passed.

## 54 File08 clauses: requirement-to-source and acceptance candidates

| ID | Plan V12.3 requirement | Existing source candidate (not accepted functionality) | Critical missing evidence / known gap |
|---|---|---|---|
| F08-FR-001 | Clinic identity | `WCA_Service::create_clinic; WCA_Repository::create_clinic` | verified owner/branch and public visibility; WP/LIVE pending. |
| F08-FR-002 | Services and fees | `WCA_Service::save_service; WCA_Repository::save_service` | policy/currency/0% commission; WP/LIVE pending. |
| F08-FR-003 | Availability rules | `WCA_Service::set_availability; generate_rule_slots` | DST, exceptions, breaks, capacity; WP/LIVE pending. |
| F08-FR-004 | Slot search | `WCA_Service::search_slots; hold_slot` | stale filter UX candidate PR50; concurrency; WP/LIVE pending. **PR50 separate draft UI correction**. |
| F08-FR-005 | Appointment request | `WCA_Appointment_Command::request; WCA_Service::request_appointment` | pending vs confirmed copy candidate PR50; replay; WP/LIVE pending. **PR50 separate draft UI correction**. |
| F08-FR-006 | Doctor decision | `WCA_Service::transition_appointment; WCA_Contracts::transition_matrix` | confirm/decline/propose by owner; WP/LIVE pending. |
| F08-FR-007 | Reschedule | `WCA_Service::hold_reschedule_slot; transition_appointment` | atomic replacement, rollback; WP/LIVE pending. |
| F08-FR-008 | Cancellation/no-show | `WCA_Service::transition_appointment; WCA_Repository::release_appointment_slot` | authorized release and reason; WP/LIVE pending. |
| F08-FR-009 | Check-in/completion | `WCA_Service::transition_appointment; grant_review_eligibility` | check-in/check-out constraints; WP/LIVE pending. |
| F08-FR-010 | Dashboards | `WCA_Query_API; WCA_Frontend::dashboard` | patient/doctor/guardian object scope; WP/LIVE pending. |
| F08-FR-011 | Emergency safety | `WCA_Service::emergency_red_flag; WCA_Frontend::booking` | urgent diversion, no delayed appointment; WP/LIVE pending. |
| F08-FR-012 | Consent | `WCA_Repository::record_consent; revoke_consent` | version/guardian/teleconsult; WP/LIVE pending. |
| F08-FR-013 | Clinical relationship | `SWC_CF01_Care_Context; WCA_Contracts` | no implicit chart authority; WP/LIVE pending. |
| F08-FR-014 | Review eligibility | `WCA_Repository::grant_review_eligibility` | one completed appointment, revocable; WP/LIVE pending. |
| F08-FR-015 | Calendar integration | `WCA_Calendar_Link; WCA_Service::appointment_ics` | signed links, provider timeouts; WP/LIVE pending. |
| F08-FR-016 | Fees/payment bridge | `WCA_Service::create_payment_intent; WCA_Repository::project_payment_status` | CF03 status, 0% commission; WP/LIVE pending. |
| F08-FR-017 | Clinical record boundary | `WCA_Continuity; WCA_Privacy` | restricted continuity and erasure; WP/LIVE pending. |
| F08-FR-018 | Complaint/dispute | `WCA_Service::create_complaint; appeal_complaint` | CF02 escalation/audit; WP/LIVE pending. |
| F08-NFR-001 | Object/field authorization | `WCA_Authorization; WCA_REST; WCA_Query_API` | native IDOR/roles and revocation; WP/LIVE pending. |
| F08-NFR-002 | Privacy lifecycle | `WCA_Privacy; WCA_Continuity` | subject export/erase/legal hold; WP/LIVE pending. |
| F08-NFR-003 | Reliability | `WCA_Outbox; WCA_Repository::claim_idempotency` | dead letter/timeout/replay; WP/LIVE pending. |
| F08-NFR-004 | Performance | `WCA_Query_API; WCA_Repository` | p75/p95 real measurements still absent; WP/LIVE pending. |
| F08-NFR-005 | Accessibility | `WCA_Frontend; assets/css/clinic.css; assets/js/clinic.js` | WCAG 2.2 AA browser/manual pending; WP/LIVE pending. |
| F08-NFR-006 | Observability | `WCA_Observability; WCA_CLI` | redacted live logs/metrics pending; WP/LIVE pending. |
| F08-NFR-007 | Migration/rollback | `WCA_Schema; WCA_Compatibility` | Hostinger schema/restore pending; WP/LIVE pending. |
| F08-NFR-008 | Operability | `WCA_Admin; WCA_CLI` | real host WP-CLI and runbook pending; WP/LIVE pending. |
| F08-NFR-009 | Compatibility | `WCA_Compatibility; CI workflow` | disposable WP on PR49, host pending; WP/LIVE pending. |
| F08-NFR-010 | Localization | `WCA_Frontend; assets/css/clinic.css` | Urdu/Arabic/English RTL native browser pending; WP/LIVE pending. |
| F08-CEN-01 | Canonical owner / File26 projection | `WCA_Central_Governance::file26_clinic_projection; PR44` | File26 owner approval pending; WP/LIVE pending. |
| F08-CEN-02 | Versioned owner commands / cached projection auth | `WCA_Central_Governance; WCA_Outbox; WCA_Authorization` | cross-file runtime and stale cache pending; WP/LIVE pending. |
| F08-FUT-01 (P0) | Smart Cancellation Waitlist | `WCA_Future24::join_waitlist; offer_waitlist_for_cancelled_appointment` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-02 (P0) | Flexible Appointment Request Windows | `WCA_Future24::save_windows` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-03 (P0) | Recurring / Series Appointments | `WCA_Future24::create_series` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-04 (P0) | Multi-Resource Scheduling | `WCA_Future24::create_resource; reserve_resource` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-05 (P1) | Capacity-Based / Group Appointment Mode | `WCA_Future24::create_group_session; join_group_session; leave_group_session` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-06 (P0) | One-Tap Safe Reschedule | `WCA_Future24::safe_reschedule` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-07 (P0) | Smart Buffer & Transition Rules | `WCA_Future24::set_buffers` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-08 (P1) | Availability Capacity Heatmap | `WCA_Future24::heatmap` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. **main has source-frozen paging/heatmap risk; corrected only in draft PR49**. |
| F08-FUT-09 (P1) | Schedule Optimization Advisor | `WCA_Future24::advisor` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-10 (P2) | Privacy-Safe No-Show Forecasting | `WCA_Future24::no_show_forecast` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. **main has source-frozen paging/heatmap risk; corrected only in draft PR49**. |
| F08-FUT-11 (P0) | Structured Dynamic Pre-Visit Questionnaire | `WCA_Future24::save_questionnaire; questionnaire_for_appointment` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-12 (P0) | Appointment Readiness Center | `WCA_Future24::readiness` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-13 (P1) | Prerequisite & Document Rules | `WCA_Future24::save_prerequisites; prerequisite_state` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-14 (P0) | Family / Guardian Appointment Hub | `WCA_Future24::family_hub` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-15 (P1) | Digital Check-In & Arrival Queue | `WCA_Future24::arrive` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-16 (P1) | Privacy-Preserving Live Queue Position | `WCA_Future24::queue_position` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-17 (P0) | Doctor Delay / Clinic Disruption State | `WCA_Future24::create_disruption; clinic_appointments_between_all` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. **main has source-frozen paging/heatmap risk; corrected only in draft PR49**. |
| F08-FUT-18 (P1) | Consultation Support Person / Interpreter Role | `WCA_Future24::add_participant; revoke_participant` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-19 (P0) | Secure Virtual-Room Provisioning Contract | `WCA_Future24::request_virtual_room` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-20 (P1) | FHIR Interoperability Adapter | `WCA_Future24::fhir_projection` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-21 (P1) | SMART Scheduling Links Compatibility | `WCA_Future24::smart_find; smart_hold; smart_book` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-22 (P1) | External Calendar Two-Way Reconciliation | `WCA_Future24::save_verified_external_busy; save_external_busy` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-23 (P1) | Clinical Episode / Follow-Up Chain | `WCA_Future24::create_episode; unlink_episode_appointment` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |
| F08-FUT-24 (P0) | Appointment Intelligence & Interoperability Governance Layer | `WCA_Future24::capabilities; governance_log; rest_manifest` | Native functional + negative + provider-failure/role/rollback tests; WP/LIVE pending. |

## First actionable defect register — strict scope

| Defect | Proved in main | Corrective candidate | Main source resolved? | Live resolved? |
|---|---|---|---|---|
| SD-01–SD-06 | Six Heatmap SQL/capacity/time-range/opening/DST defects | PR49 (combined from PR45) | No | No |
| SD-07 | Incomplete paginated appointments on a failed second WP_Query page; unsafe downstream FUT08/10/17 projections | PR49 (combined from PR48) | No | No |
| V123-UI-01 | Service/date/timezone changes fail to invalidate an outstanding slot search and stale slot buttons | PR50, executable Node fixture | No | No |
| V123-UI-02 | Public booking page used the headline “Book with” while submission only creates a pending appointment request | PR50, request-truth source regression | No | No |
| FUT07-POL-01 | A first/later failed WordPress appointment page can silently bypass active buffer policy | PR51/PR53, native SQL failures | No | No |
| FUT07-POL-02 | Malformed active FUT07 policy JSON or limits silently disables policy | PR51/PR53, native policy integrity regression | No | No |
| FUT07-POL-03 | Error objects require explicit propagation through holds and REST slot projections; pagination needs an upper bound | PR51/PR53, native REST/hold and 250-page regressions | No | No |
| FUT07-POL-04 | Query's same-day UTC appointment constraint omits a prior-day appointment ending shortly before candidate midnight slot | PR51/PR53, native cross-midnight fixture | No | No |

**Total frozen source findings = 13 distinct groups. Source corrections authored in PR49/PR50/PR51 and composed in PR53 = 13; integrated into main = 0; Staging-confirmed = 0; Live-resolved = 0.** Do not infer a universe-wide “only 9 defects” from these scoped findings.

## 34 open pull requests — individual exact-main ancestry and disposition

Current GitHub open PR search: **34** after opening PR53. Separate Issue #52 concerns the File07↔File08 public clinic discovery owner contract; it is not a pull request. Historical snapshot of 31 open PRs belonged to the preceding planning stage. This table is a decision register, **not** an approval or automatic closure instruction.

| PR | Current evidence against main | Decision / next gate |
|---|---|---|
| #53 | Integrated V12.3 source candidate, exactly preserved PR49/PR51 FUT07 and PR50 UI blobs | Primary consolidated source QA; keep Draft; Hostinger staging/live blocked |
| #51 | Four FUT07 policy/SQL/midnight source groups; depends on #49 | Keep Draft and retain native fail-first chain; final-head CI needed |
| #50 | V12.3 UI/UX source patch; three native JS cases; separate PR | Keep Draft; full exact-HEAD CI, 2 reviews, combine carefully with PR49 |
| #49 | Ahead 25 / behind 0; 12 files; prior run #43380112964 6/6 success | Primary seven-source-defect integration candidate; full V12.3, staging and owner review before merge |
| #48 | Ahead 5 / behind 0; 4 files | Superseded candidate by #49, preserve fail-first provenance; no blanket closure |
| #45 | Ahead 7 / behind 0; 6 files | Superseded candidate by #49, preserve heatmap provenance; no blanket closure |
| #44 | Ahead 2 / behind 0; 2 files | File26 public clinic read projection, privacy and native owner approval pending |
| #42 | Ahead 6 / behind 11; 3 files; diverged | Analytics policy/consent hold, no silent collection |
| #38 | Ahead 219 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #37 | Ahead 206 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #36 | Ahead 194 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #35 | Ahead 183 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #34 | Ahead 172 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #33 | Ahead 159 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #32 | Ahead 148 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #31 | Ahead 134 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #30 | Ahead 120 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #29 | Ahead 108 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #28 | Ahead 89 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #27 | Ahead 77 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #26 | Ahead 56 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #25 | Ahead 57 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #24 | Ahead 47 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #23 | Ahead 40 / behind 14; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #22 | Ahead 89 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #20 | Ahead 78 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #19 | Ahead 79 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #18 | Ahead 69 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #17 | Ahead 59 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #16 | Ahead 46 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #15 | Ahead 42 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #14 | Ahead 24 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #13 | Ahead 14 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |
| #12 | Ahead 32 / behind 35; diverged | Historical source lineage; per-file semantic diff and supersession proof; **do not merge wholesale** |

## Risk-first execution tickets

| Ticket | Owner domain | Action | Acceptance / stop condition |
|---|---|---|---|
| P01-B01 | Hostinger / site operator | Read-only installed plugin package tree/version/checksum, WP/PHP, schema, wp_options migration markers, relevant logs, provider health | No production patch diagnosis until exact deployed state verified |
| P02-B01 | File08 source QA | Bind all 54 IDs to runnable behavior, not mere string matches; inspect V12.3 M01–M12 + QA15–24, UX04, AJ21/22 | No mandatory ID marked complete on static evidence only |
| P03-B01 | GitHub code owner | 26 divergent historical PRs per-path audit; individual conflict / supersession disposition | No bulk merge; do not erase unported safety delta |
| P04-B01 | File08 source owner | Validate PR49 seven fixes and PR50 two fixes, merge only via one tested exact-source release candidate | Test every previous bug after integrations; 2 fresh review rounds |
| P05-B01 | File08 + File00/09 | Real patient/guardian/doctor flows, last-slot collision, idempotency replay, consent/eligibility revoke, DST gap/fold | All active P0 security/clinical gates green |
| P06-B01 | File08 | P0 FUT 01,02,03,04,06,07,11,12,14,17,19,24; P1 and P2 separately | Optional unready routes effectively disabled, no stale UI or implicit entitlement |
| P07-B01 | File20/25 + File08 | 320/390/768/1024/1440 responsive, EN/UR/AR, RTL, 400% zoom, keyboard/screen-reader, visual states | WCAG 2.2 AA applicable tests + UX04 evidence |
| P08-B01 | File07/17/19/26/CF01/CF03 owners | Public clinic projections, calendar, room, notification, payment, chart boundary, outages | Actual contract consumer tests; no producer's “adapter exists” claim sufficient |
| P09-B01 | File24 independent QA | 2 fresh full reviews after final source; adversarial auth, export/retention, p75/p95, operation logs | Zero known defects in active release scope |
| P10-B01 | Hostinger staging operator | Exact ZIP installation, fresh/upgrade/partial migrations, backup + restore/rollback, sanitized roles | Positive staged smoke and negative regression, founder signoff |
| P11-B01 | Authorized release owner | controlled deployment, post-install parity and live smoke, rollback monitoring | Release only after exact-deployed evidence; no Live-Resolved without live retest |

## Mandatory terminal report fields
Repository HEAD / Candidate SHA+CI / Deployed Version / DB Version / Migration State / Live Verification Status / Found / Corrected-source / Merged / Staging-pass / Live-resolved / New blockers.

**Live-source conclusion:** “Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔”
