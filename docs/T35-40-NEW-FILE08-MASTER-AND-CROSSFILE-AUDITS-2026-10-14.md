# File 08 T35 — FORTY NEW requirement/companion source audits and two verified stored-state defects

Date: 2026-10-14. **This is a new T35 audit**, not a re-labelled T34 review. Branch: `review/file08-t35-forty-resource-scope-and-policy-20261014`; draft PR #25. Exact working source before this ledger commit: `f72d9cf9f7c3446de3da70c18aa3c711af2de24a`. Source File08 plugin 1.2.16, core schema contract 3.4.0; previous draft T34 source HEAD `25de8f7679a03da2635447d3a93b80f37363f14c`. Current main at freeze: `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. No claim that live/staging deploy corresponds to any of these.

## Actual governing plans consulted
1. Original attached `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx`: 18 mandatory functional requirements from **table 6**, ten NFR release gates from **table 7**, and Future24 amendment. IDs, original descriptions and acceptance conditions included in full machine-readable audit.
2. Actual attached `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026`: File 08 owns clinic/appointments, File 17 messaging transport, File 19 notification delivery, File 03/07 doctor identity/directory, File 09 verified doctor eligibility, File 14 conversion, File 20 shell, File 25 presentation, CF01 clinical records, CF02 support and CF03 finance. Source boundary comparisons are Repository evidence, not runtime proof.
3. File 07, 09, 14 and 25 companion documents and prior exact versioned source contract checks were retained; current **real installed** companion runtime contracts are not available in this review.

## Forty freshly executed source/syntax passes
The exact Github CI independently generated source artifact for T35 pre-ledger HEAD was downloaded, independently extracted, and evaluated. Each of the forty separately executed *requirement-focused* rounds: reopened all **50 runtime PHP/JS/CSS sources**, ran actual PHP CLI parser `php -l` on **44 distinct runtime PHP sources**, evaluated the appropriate source-path/negative-fallback/companion guard probes and recorded the per-round source tree fingerprint. Aggregate result: **40/40 completed and passing; 158/158 positive/negative source checks passing; 1,760/1,760 individual PHP syntax checks passing; 0 syntax errors**. Corrected candidate tree SHA256: `85d080804279c7110a25e170d5c01c29ca708267470bf44ed13423551b4d7d4b`. Raw authoritative machine-readable per-round evidence: `file08-t35-forty-new-plan-source-audit.json` (user-delivered conversation file). Earlier T34 source probes were re-executed against **different exact source and new T35 negative guards**; their reuse as source-presence assertions must not be represented as forty functionally exhaustive code audits.

| Review | Plan identity | Audited focus | Actual source/syntax outcome |
|---|---|---|---|
| 01 | F08-FR-001 | Clinic identity and branch ownership | Source/PHP lint PASS |
| 02 | F08-FR-002 | Services, fees, tax/zero-commission | Source/PHP lint PASS |
| 03 | F08-FR-003 | Availability DST, rule/freshness | Source/PHP lint PASS |
| 04 | F08-FR-004 | Slot search, holds and CAS | Source/PHP lint PASS |
| 05 | F08-FR-005 | Appointment requests, consent/guardian | Source/PHP lint PASS |
| 06 | F08-FR-006 | Doctor decision and template notification | Source/PHP lint PASS |
| 07 | F08-FR-007 | Reschedule proposal/acceptance | Source/PHP lint PASS |
| 08 | F08-FR-008 | Cancellation, no-show and fee audit | Source/PHP lint PASS |
| 09 | F08-FR-009 | Check-in and completed review eligibility | Source/PHP lint PASS |
| 10 | F08-FR-010 | Patient/doctor/staff scoped dashboard | Source/PHP lint PASS |
| 11 | F08-FR-011 | Emergency red-flag diversion | Source/PHP lint PASS |
| 12 | F08-FR-012 | Consent scope/revocation | Source/PHP lint PASS |
| 13 | F08-FR-013 | File17 appointment/CF01 protected context | Source/PHP lint PASS |
| 14 | F08-FR-014 | Single completed review and File03 projection | Source/PHP lint PASS |
| 15 | F08-FR-015 | Signed calendar ICS/provider | Source/PHP lint PASS |
| 16 | F08-FR-016 | CF03 finance ledger and zero commission | Source/PHP lint PASS |
| 17 | F08-FR-017 | CF01 clinical record/no public disclosure | Source/PHP lint PASS |
| 18 | F08-FR-018 | CF02 complaint/appeal evidence | Source/PHP lint PASS |
| 19 | F08-NFR-001 | File00 claims and native object gates | Source/PHP lint PASS |
| 20 | F08-NFR-002 | Retention, erasure, legal-hold privacy | Source/PHP lint PASS |
| 21 | F08-NFR-003 | File19/17/26 outbox ACK/retry/reconcile | Source/PHP lint PASS |
| 22 | F08-NFR-004 | Keyset cursor, pagination, performance | Source/PHP lint PASS |
| 23 | F08-NFR-005 | RTL, keyboard, contrast, accessible actions | Source/PHP lint PASS |
| 24 | F08-NFR-006 | Structured logs, privacy-safe alerts | Source/PHP lint PASS |
| 25 | F08-NFR-007 | Schema migrations and rollback evidence | Source/PHP lint PASS |
| 26 | F08-NFR-008 | System health/repair and queued jobs | Source/PHP lint PASS |
| 27 | F08-NFR-009 | File00/03/07/09/17/19 contracts | Source/PHP lint PASS |
| 28 | F08-NFR-010 | en-US/Urdu time/currency/timezone | Source/PHP lint PASS |
| 29 | F08-FUT--1/00 | Waitlist and flexible requests | Source/PHP lint PASS |
| 30 | F08-FUT-01/02 | Series intents, FUT04 branch-bound resource | Source/PHP lint PASS |
| 31 | F08-FUT-03/04 | Group sessions/privacy and safe reschedule | Source/PHP lint PASS |
| 32 | F08-FUT-05/06 | Strict buffer controls and heatmap | Source/PHP lint PASS |
| 33 | F08-FUT-07/08 | Advisory scheduling and no-show forecasts | Source/PHP lint PASS |
| 34 | F08-FUT-09/10 | Previsit questionnaire/readiness | Source/PHP lint PASS |
| 35 | F08-FUT-11/12 | FUT13 blocking prerequisites/guardian | Source/PHP lint PASS |
| 36 | F08-FUT-13/14 | Digital check-in and private queue position | Source/PHP lint PASS |
| 37 | F08-FUT-15/16 | Delay disruption and support/interpreter | Source/PHP lint PASS |
| 38 | F08-FUT-17/18 | File17 secure room and FHIR projection | Source/PHP lint PASS |
| 39 | F08-FUT-19/20 | SMART scheduling and external busy | Source/PHP lint PASS |
| 40 | F08-FUT-21/22 | FUT23 clinical episode metadata and FUT24 governance | Source/PHP lint PASS |

## NEW proven defects, captured RED before production-safe correction

**T35-D1 — F08-FUT-04 Multi-Resource Scheduling persistent scope and branch matching:**
- Before: `WCA_Future24::reserve_resource` decoded `$resource['payload_json']` with `json_decode(...,true)`, and if corrupted could continue to the resource lock and write path with `resource_type=''`. It also checked only that an appointment belonged to the same *clinic*, not the specific **branch** declared by the resource. This violates operational resources/clinic scopes and may permit a branch resource reservation for an appointment in a different branch.
- Independently executed native pre-correction PHP test on T34 source: `FAIL: Corrupt resource does not become a reservation`; real `reserve_resource` method reached beyond the corrupted persisted payload. This is **not** a mere token-absence hypothesis.
- After full review, `verified_resource_reservation_scope` checks JSON object, approved enum type and canonical public branch UUID shape; for a branch-bound appointment it checks current File08 Repository branch against the appointment's stored branch_id, same clinic and public_ref. Validation runs **before advisory lock / capacity read / reservation write**; absent, malformed or cross-branch scope returns explicit 503/409. Clinic-global resources remain valid. New actual PHP method regression `tests/t35-resource-reservation-scope-regressions.php` checks corrupt JSON, invalid resource type, missing/malformed branch, wrong branch, valid global resource, and valid same-branch appointment.

**T35-D2 — F08-FUT-23 Clinical Episode/Follow-Up Chain damaged stored metadata:**
- Before: `WCA_Future24::mutate_episode` converted invalid persisted JSON into empty array and proceeded to archive it. Red native method execution on T34 source with `payload_json='{broken'` yielded `wca_episode_update_failed` with **1 attempted DB UPDATE**, proving a bad episode could reach database mutation.
- After review, the method fails closed **before a database mutation** unless native stored JSON is an object, appointment_refs are bounded typed valid opaque UUIDs without duplicates, open episodes have at least one reference, and privacy/scope boundary flags `clinical_narrative_stored=false`, `public_timeline=false`, `scope_consistency=same_patient_doctor_clinic` hold. The new native PHP suite `tests/t35-episode-stored-integrity-regressions.php` tests malformed, missing, wrong-type, duplicate, invalid UUID or privacy flag records with zero attempted DB updates. Valid archive/unlink still reaches the expected protected mutation path.

Both new test files were registered in `tests/run-all.php`; T28 orphan-test inventory remains active. The older File14 post-commit observer is preserved; no other module's database was written.

## Verified continuous integration (pre-ledger)
After T35 source/QA changes, GitHub Actions **run 38529134152 = SUCCESS** for exact source HEAD `f72d9cf9f7c3446de3da70c18aa3c711af2de24a`: PHP7.4 source/contract regression PASS, PHP8.3 PASS, Javascript/source hygiene PASS, reproducible verified candidate PASS. Artifact ID `11876369752`, outer digest sha256 `7cce47342890bb86bf8067e1eafd5b86f32d2d64e54b8a2e83b8707d231acd16`. Both native T35 tests were observed PASS in actual PHP 8.3 CI output. **This ledger commit changes GitHub HEAD; final exact-HEAD CI must be separately run and recorded.**

## Mandatory staging and live gates
The review is **source/test evidence only**. Exact installed WordPress plugin files, runtime versions and checksums, DB/schema and wp_options migration state, active File00/03/07/09/14/17/19/20/24/25/26/CF01/CF02/CF03 versions, production error logs and actual File17/File26/CF02/CF03 owner-side event receipts are **unverified**. Staging must test actual corrupted Future24 DB row behavior and branch identity, resource concurrency, episode archive/restore, patient/guardian/doctor flows, consent, notifications, payment, availability/DST, privacy/RTL, accessibility, backups/rollback, deploy parity, and live retest before production acceptance. No production patch is authorized merely by GitHub source.

Incident report:
- Repository HEAD: PR #25 exact SHA after final ledger (freshly verify); main SHA `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`
- Deployed Version: **UNVERIFIED**
- DB Version: **UNVERIFIED**
- Migration State: **UNVERIFIED**
- Live Verification Status: **NOT PERFORMED**

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
