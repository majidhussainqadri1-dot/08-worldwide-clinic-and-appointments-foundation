# File 08 — T27-R1: 20 frozen governing-plan/source checks before any code repair
Date: 2026-10-12 Pakistan. FROZEN repository `main` SHA: `70541974ce0ffb16aebef557c3016eb7447662f4`. Previous main canonical Actions run `36032175600` completed success on **that previous commit only**. Draft PR#8 has older head `99a5d89438aaf923ccc7e9fa740c6133263a2c5c`, not the latest main candidate.

## Written governing source actually supplied
**File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026**, document SSH-F08-PLAN-2026-v1.0 + 7 Aug central harmonization + 10 Aug **Future Clinic Intelligence & Interoperability 24**. In particular §§5–8, F08-FR-001 through F08-FR-018, F08-NFR-001 (object authorization), F08-NFR-004 (bounded query/cursor pagination), §8.4 query safe filters/cursors/stable sorting and current File00-identity contract, §12.2 authorization/privacy/migration tests, §14 exact-repo/live differentiation and CEN-OWN-001 / CEN-REL-001. Relevant companion canonical owners: File00 identity, File07 discovery, File09 verification, File17 communication, File19 notification, File20 shell, File24 privacy, File25 tokens, File26 search, CF01 restricted clinical record. These specifications define expected boundaries; companion real deployed version/API parity is not verified.

**Methodology:** exactly 20 **bounded topic-to-source checks** carried out on frozen main before edits. The first 18 examined named PHP owner method anchors and scope; this proves source presence only, not 18 working workflows. Checks 19–20 inspected actual live repository PHP decoder logic and contrast with signed cursor/query invariants. These are NOT 20 independent full-file exhaustive audits, nor 20 live web E2E sessions.

| Round | Governing requirement / companion | Focus | Observed implementation | Frozen finding |
|---|---|---|---|---|
| 01 | F08-FR-001 / Files07,09 | Clinic creation/activation and verified owner | `WCA_Service::create_clinic / activate_clinic` | method paths found |
| 02 | F08-FR-002 | Service fees/currency, zero-commission | `WCA_Service::save_service / valid_currency` | method paths found; provider E2E unverified |
| 03 | F08-FR-003 | Timezone DST and availability rules | `WCA_Service::set_availability / local_datetime` | method paths found |
| 04 | F08-FR-004 | Fresh slot search and holds | `WCA_Service::search_slots / hold_slot` | method paths found |
| 05 | F08-FR-005 / File00 | Appointment request, consent, idempotency | `WCA_Service::request_appointment / claim_idempotency` | method paths found |
| 06 | F08-FR-006 / File09 | Verified doctor confirmation/decline | `WCA_Service::transition_appointment` | method paths found |
| 07 | F08-FR-007 | Safe reschedule/hold semantics | `WCA_Service::hold_reschedule_slot` | method paths found |
| 08 | F08-FR-008 | Cancel/no-show + state history | `WCA_Service::transition_appointment` | method paths found |
| 09 | F08-FR-009 | Check-in/completion and review | `WCA_Service::transition_appointment` | method paths found |
| 10 | F08-FR-010 / Files00,20 | Private patient/clinic dashboard lists | `WCA_Query_API::list_patient_appointments / list_clinic_schedule` | method paths found |
| 11 | F08-FR-011 | Emergency red flags and diversion | `WCA_Service booking red-flag guard` | source references found |
| 12 | F08-FR-012 / File00 | Guardian and consent | `WCA_Authorization::guardian_context / claims` | method paths found |
| 13 | F08-FR-013 / File17 | Clinical relationship authorization | `WCA_Authorization::can_view_appointment / appointment_actor` | method paths found |
| 14 | F08-FR-014 | Completed appointment review eligibility | `WCA_Service completion / review eligibility` | method paths found |
| 15 | F08-FR-015 | Calendar/ICS external provider boundary | `WCA_Service calendar / WCA_Calendar_Link` | method paths found |
| 16 | F08-FR-016 | Fees/payment adapter provider control | `WCA_Service payment/currency + native contracts` | method paths found |
| 17 | F08-FR-017 / File24,CF01 | Clinical record privacy boundary | `WCA_Privacy and WCA_CF01_Care_Context` | privacy method paths found |
| 18 | F08-FR-018 | Complaint and appeal provenance | `WCA_Service::create_complaint / appeal_complaint` | method paths found |
| 19 | F08-NFR-001/004 / File00 | Patient pagination cursor canonical typed IDs | `WCA_Query_API::decode_cursor` | T27-D01: absint coerces signed v/a/i |
| 20 | F08-NFR-001/004 / Files20,24 | Clinic schedule cursor timestamp/query identity | `WCA_Query_API::decode_cursor` | T27-D02: timestamp sanitized/coerced without canonical time/length |

## Two source defects proven, before repair
**T27-D01 — HMAC verified, typed identity NOT verified.** `WCA_Query_API::decode_cursor` checks its signature/context, then tests version/actor with `absint` and returns `absint($state['i'])`; signed JSON negative, floating-point, boolean and numeral-string values can be **silently rebound** to different v/a/i. This is NOT an HMAC-forgery vulnerability: only a trusted signer can generate valid HMAC; source/backend should still reject malformed signed or legacy/corrupt cursor documents.

**T27-D02 — cursor sort-time normalization/unsupported state.** Decoder accepts any nonempty time, calling `sanitize_text_field` before passing as raw SQL cursor boundary. A properly HMAC-signed non-time value, a modified/markup value or exceptional length can be converted to a different sort position rather than fail closed. `query_candidate_appointments` compares this to `_swc_preferred_at_utc` or `post_date_gmt`. Plan promises reliable, stable bounded cursor/UTC page identity.

## Post-ledger correction scope (not yet performed at ledger freeze)
- Harden `decode_cursor` only, keep existing authenticated route and HMAC/actor/filter checks. Enforce compact strict JSON object schema; exact integer v/actor/last ID; bounded canonical UTC time string; no silent conversion; preserve generated signed tokens. Reject malformed input before any DB query.
- Add native PHP regression via Reflection on actual `WCA_Query_API::encode_cursor/decode_cursor`, checking valid generated tokens, trusted-but-malformed signed JSON, wrong actor/filter/scope, date anomalies and no unintended SQL continuation.
- Wire permanent regression into `tests/run-all.php`, update review traceability and real staging release tests, verify final exact-head PHP7.4/PHP8.3 CI and deterministic double-package with independent verifier. No deployment/migration.

## Live First reality and release gate
Current repository candidate software `1.2.15`; repository core schema `3.4.0`; source continuity and Future24 contracts `1.1.0`. These cannot establish what is deployed. Actual Hostinger installed active plugin/package checksum, WordPress/php runtime, wp_options, physical DB/schema migration state, File00/03/07/09/17/19/20/24/25/26/CF01 installed consumers, security/IDOR/RTL/staging and two independent post-change reviews, backup/restore, Founder approval, deployment and live re-test ALL unverified.

Required five truths — Repository HEAD: frozen main `70541974ce0ffb16aebef557c3016eb7447662f4`; Deployed Version: UNVERIFIED; DB Version: UNVERIFIED; Migration State: UNVERIFIED; Live Verification Status: NOT PERFORMED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
