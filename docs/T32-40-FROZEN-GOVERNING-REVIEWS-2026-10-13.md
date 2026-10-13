# File08 T32 — 40 frozen governing-plan / source / owner checks BEFORE changes
Date: 13 October 2026 Pakistan. Frozen current **source candidate** Draft PR#18 HEAD `258b8e00a4c1ea360802c636c10919bc13a89faa`, exact SHA Actions `38308495222` successful on **that source**. Repository main `70541974ce0ffb16aebef557c3016eb7447662f4` separate; deployed source/schema not verified. Branch review/file08-t32-40-audits-future24-policies-2026-10-13 was based on this exact PR18, retaining T31 File00 canonical group member protection and persisted public DTO JSON integrity.

## Source documents and actual comparison scope
Read uploaded Word files: File08 **Worldwide Clinic and Appointments Complete Master Plan v1.1 Future24 amended 10-Aug-2026**, notably FR001–018, NFR001–010, approved F08-FUT001–024 and FUT24 Definition of Done; **Three Central Plans Consolidated Governing Master Plan 2026**, canonical owner law (CEN-OWN-001) and Founder-controlled CEN-REL-001 staging/rollback; companion uploaded File07 Doctors Directory, File09 Global Doctor Verification, File14 Global Clinic USP. Also inspected real GitHub File08 PHP files `class-wca-service`, `class-wca-repository`, `class-wca-authorization`, `class-wca-query-api`, `class-wca-contracts`, `class-wca-privacy`, `class-wca-outbox`, `class-wca-compatibility`, `class-wca-calendar-link`, CF01 adapter and `class-wca-future24`. These owner source files are not the deployed artifact.

**Methodological honesty:** The forty reviews below are 40 **different bounded requirement-to-source inspections**, completed as a set before changing source. 01–38 confirm named PHP paths/contract evidence only and do not establish 38 full working journeys; 39–40 inspect actual Future24 persisted templates/policy and keyset loop. FUT14–24 were reviewed through the Future24 capability registry/ownership plus staged DoD constraints, **not individually executed as another 12 checks**. They are NOT 40 separate exhaustive line-by-line repository reviews or real staging E2E runs. No source-based fix can justify a live Resolved claim.

| Check | Plan and owner | Source focus | Actual frozen result |
|---|---|---|---|
| 01 | FR001 File07/09 | Clinic create/activate — `includes/class-wca-service.php` | presence |
| 02 | FR002 File00 | Service/fees — `includes/class-wca-service.php` | presence |
| 03 | FR003 | DST availability — `includes/class-wca-service.php` | presence |
| 04 | FR004 | Slot search/holds — `includes/class-wca-service.php` | presence |
| 05 | FR005 File00 | Consent/idempotency — `includes/class-wca-service.php` | presence |
| 06 | FR006 File09 | Doctor decision — `includes/class-wca-service.php` | presence |
| 07 | FR007 | Reschedule lifecycle — `includes/class-wca-service.php` | presence |
| 08 | FR008 | Cancellation/no-show — `includes/class-wca-contracts.php` | status present |
| 09 | FR009 File19 | Check-in/completion — `includes/class-wca-contracts.php` | status present |
| 10 | FR010 File20 | Private dashboard — `includes/class-wca-query-api.php` | presence |
| 11 | FR011 | Emergency diversion — `includes/class-wca-service.php` | presence |
| 12 | FR012 File00 | Guardian identity — `includes/class-wca-authorization.php` | presence |
| 13 | FR013 File17 | Clinical relationship — `includes/class-wca-authorization.php` | presence |
| 14 | FR014 File07 | Review eligibility — `includes/class-wca-repository.php` | presence |
| 15 | FR015 | ICS/calendar — `includes/class-wca-calendar-link.php` | presence |
| 16 | FR016 File19 | Payments/outbox — `includes/class-wca-service.php` | presence |
| 17 | FR017 CF01/File24 | Restricted clinical data — `includes/class-swc-cf01-care-context.php` | presence |
| 18 | FR018 | Complaints/appeals — `includes/class-wca-service.php` | presence |
| 19 | NFR001 File00 | Object ACL — `includes/class-wca-authorization.php` | presence |
| 20 | NFR002 File24 | Private data — `includes/class-wca-privacy.php` | presence |
| 21 | NFR003 File19 | Outbox/observability — `includes/class-wca-outbox.php` | presence |
| 22 | NFR004 | Bounded queries — `includes/class-wca-query-api.php` | private cursors present; policy scan defect in 39 |
| 23 | NFR005 File25 | A11y/RTL dashboard — `includes/class-wca-query-api.php` | source path present |
| 24 | NFR006 | Degraded dependency — `includes/class-wca-compatibility.php` | presence |
| 25 | NFR007 | Migration gates — `includes/class-wca-compatibility.php` | source only |
| 26 | NFR008 | Replay/idempotency — `includes/class-wca-repository.php` | presence |
| 27 | NFR009 File00/09 | Version contracts — `includes/class-wca-contracts.php` | presence |
| 28 | NFR010 | Timezone/i18n — `includes/class-wca-service.php` | presence |
| 29 | FUT01 | Waitlist — `includes/class-wca-future24.php` | presence |
| 30 | FUT02 | Request windows — `includes/class-wca-future24.php` | presence |
| 31 | FUT03 | Series appointments — `includes/class-wca-future24.php` | presence |
| 32 | FUT04 | Resources — `includes/class-wca-future24.php` | presence |
| 33 | FUT05 File00 | Capacity/group — `includes/class-wca-future24.php` | T31 guard retained |
| 34 | FUT06 | Safe reschedule — `includes/class-wca-future24.php` | presence |
| 35 | FUT07 | Buffer policies — `includes/class-wca-future24.php` | presence |
| 36 | FUT08 | Heatmap — `includes/class-wca-future24.php` | presence |
| 37 | FUT09 | Advisor — `includes/class-wca-future24.php` | presence |
| 38 | FUT10 | No-show aggregate forecast — `includes/class-wca-future24.php` | presence |
| 39 | FUT11 File24 | Dynamic previsit questionnaire — `includes/class-wca-future24.php` | D02: corrupt active template skipped; D03: feature row keyset may not advance |
| 40 | FUT12/13 CF01 | Readiness and blocking prerequisite rules — `includes/class-wca-future24.php` | D01: nested requirement rules are stripped from put_record; D02: corrupt active rule ignored |
## Three concrete source defects collected BEFORE fixes
**T32-D01 (FUT13 blocking prerequisites silently lost):** `save_prerequisites` forms an array of structured rule maps (key/type/label/missing_behavior), but `put_record` passes operational payload to `sanitize_operational_payload(..., true)`; this sanitizer retains only scalar members of nested lists and silently discards each structured rule. The saved `requirements` payload becomes an empty list; `prerequisite_state` can wrongly report complete=true when documented blocking previsit evidence is absent. This is source-code/data-flow proof, not evidence of which rows Hostinger holds. Fix ONLY validated FUT13 named rule maps before JSON storage, without generic nested arbitrary/clinical content support.
**T32-D02 (FUT11/13 broken active policy treated as no policy):** `questionnaire_for_appointment` and `prerequisite_state` decode active rows and `continue` for malformed JSON, silently returning no questionnaire / no blocking prerequisites, although the shared `public_record` already adopted fail-closed corrupt JSON principles in T31. Fix fail-closed active policy/template data integrity before returning a success. Do not fabricate missing requirements.
**T32-D03 (Future24 keyset progress/row integrity):** `feature_rows_for_clinic` loops while exactly 100 rows are returned but updates cursor with `max(cursor, absint(row.id))` without verifying each ID is strictly increasing. A malfunctioning/corrupt source batch with duplicate/zero/nonmonotonic IDs can be reread forever or silently duplicate active policies. Fix strict row shape/positive strictly ascending IDs and error return before partial results; no new DB schema or pagination truncation semantics.

## Repair gate after ledger freeze
Add focused actual PHP7.4/8.3 native-method regressions validating complete FUT13 structured requirements survive canonical sanitizer, corrupt questionnaire/prerequisite policies fail closed, and 100-row historical policy scan cannot loop without progress; retain all 104 previously registered suites/no orphan. Final immutable T32 SHA must pass 3/3 CI PHP7.4, PHP8.3, deterministic double-build byte-identical ZIP with independent verifier. Do not merge/deploy; source software 1.2.15, source Core schema 3.4.0, Continuity/Future24 schema 1.1.0 unchanged unless migration proven necessary.

## Live-first five separate truths
Repository source PR18 frozen SHA above, T32 source candidate after corrections pending; Deployed Version **UNVERIFIED**; Physical DB Version **UNVERIFIED** (source schema 3.4.0 not installed proof); Migration State **UNVERIFIED**; Live Verification Status **NOT PERFORMED**. Installed File00/03/07/09/17/19/20/24/25/26/CF01/02/03 API parity, Hostinger checksum/WordPress logs/wp_options/rows, doctor/guardian/real private IDOR and DB concurrency, two fresh staging reviews, actual backup/restore, Founder release approval, deploy and live re-test missing.
**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
