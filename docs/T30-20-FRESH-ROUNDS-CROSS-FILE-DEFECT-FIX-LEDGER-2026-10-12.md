# File 08 T30 — Fresh Twenty Full-Source Reviews and Cross-File Correction Ledger
Date: 2026-10-12. Review target: `review/file08-t30-20-fresh-plan-and-companion-audits-20261012` based on T27/T28 draft PR #12 at `0dbfddc789f295b30b9aa1b71cb7ec4fda443bff`.

## Governing sources and strict evidence levels
- Actual uploaded `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx` (`SSH-F08-PLAN-2026-v1.0`, dated addenda prevail); `Sabri ... Three Central Plans Consolidated Governing Master Plan 2026` (`SSH-PMP-2026-v3.0`), File07, File09, File14 and File25 approved companion specifications.
- 18 File08 functional requirements, 10 NFRs, 24 Future24 IDs, and canonical ownership/dependency boundaries to File00,03,07,09,17,19,20,24,25,26, CF01/CF02/CF03. Individual repository source was separately fetched for File00,03,07,09,17 and File19.
- A **full-source sweep** here means rereading every runtime PHP/JS/CSS file and PHP-linting every PHP file; each of the twenty sweeps has a different contract/source focus. Source-token presence and syntactic correctness **do not prove end-to-end semantics, database effects, provider acceptance, browser accessibility, staging or live deployment**.

## Twenty distinct finished source/syntax audit passes
Baseline of corrected packaged code: `c726c627736232e263a58928af4c20d6b1fa2238`, CI `38285493310`. Deterministic extracted source fingerprint SHA-256 `a4c9c5ee487f445aee25e72d7f838f48df4ac57d68e34827cfff6d78a43e7d03`; 50 runtime PHP/JS/CSS files traversed per pass, 44 PHP files independently syntax-checked per pass (880 lint checks total), 79/79 distinctive source signals passed overall after fixes.

| Round | Fresh plan/owner boundary and implementation focus | Post-fix review result |
|---|---|---|
| 01 | Boot and dependency wiring | 3/3 source signals, no PHP syntax failure |
| 02 | Clinic/branch/service ownership | 4/4 source signals, no PHP syntax failure |
| 03 | Availability/recurrence/timezones/DST | 4/4 source signals, no PHP syntax failure |
| 04 | Atomic capacity and expiring holds | 4/4 source signals, no PHP syntax failure |
| 05 | Authenticated booking/consent/idempotency | 4/4 source signals, no PHP syntax failure |
| 06 | Appointment transitions, rescheduling, terminal states | 4/4 source signals, no PHP syntax failure |
| 07 | Object-level authorization and guardians | 4/4 source signals, no PHP syntax failure |
| 08 | Doctor verification and suspension | 4/4 source signals, no PHP syntax failure |
| 09 | Consent, privacy, retention, CF01 denial | 4/4 source signals, no PHP syntax failure |
| 10 | Emergency screening and diversion | 3/3 source signals, no PHP syntax failure |
| 11 | Authentic review eligibility and File03 display | 3/3 source signals, no PHP syntax failure |
| 12 | Calendar, CF03 payments, CF02 complaints | 4/4 source signals, no PHP syntax failure |
| 13 | File19 versioned events and outbox semantics | 4/4 source signals, no PHP syntax failure |
| 14 | File17 event context, File03 delegates, CF01 separation | 4/4 source signals, no PHP syntax failure |
| 15 | File26 discovery/index invalidation | 3/3 source signals, no PHP syntax failure |
| 16 | File20/25 routes, RTL, JS | 4/4 source signals, no PHP syntax failure |
| 17 | Future24 features F01-F08 | 5/5 source signals, no PHP syntax failure |
| 18 | Future24 features F09-F16 | 4/4 source signals, no PHP syntax failure |
| 19 | Future24 features F17-F24 | 5/5 source signals, no PHP syntax failure |
| 20 | Security, schema, migrations, recovery, observability | 5/5 source signals, no PHP syntax failure |

## Proven defects from fresh T30 source and exact companion contracts

### T30-D1: Canonical File00 platform subject missing
- File08 `WCA_Authorization::subject_uuid` used `smc_get_subject_uuid` (absent from inspected current File00 public code) or stale `_smc_subject_uuid` local metadata. File00 1.2.44 actually owns `_smc_platform_uuid_v1`, exposed by `SMC_CF01_Contract::ensure_subject_uuid`.
- Fixed: call File00's verified authoritative API; normalize and validate UUID shape, never invent an internal ID fallback, catch error/exception and return empty. All protected `WCA_Authorization::claims()` paths fail-closed with `wca_canonical_subject_unavailable` if canonical identity is absent.
- New runtime test covers valid UUID, zero user, native-id-shaped return, WP_Error, thrown exception and fail-closed source.

### T30-D2: False/obsolete cross-file dependency diagnostics
- Before: File19 readiness looked for removed `sn_notify_users`; File00, File03 and File07 relied on obsolete helper names; File17 and File24 relied on the `wca_outbox_event` hook that File08 itself registers, creating false readiness.
- Fixed: compare against actual File00 `SMC_Contracts::assertions` and `SMC_CF01_Contract::ensure_subject_uuid`; File03 `SPD_Helpers::canonical_profile_url`; File07 `SDD_Helpers::public_clinic`; File19 `sun_register_notification_producer` and `sun_ingest_domain_event`. File17 additionally requires File17 `SN_Context_Adapters::attach_context` and explicit durable receipt handler; File26 readiness now separately requires its receipt handler. File24 no longer claims ready because File08 itself registers an outbox observer.
- Test verifies positive/negative conditions and absence of deprecated detection.

### T30-D3: Outbox prematurely marks external integrations delivered
- Before: `WCA_Outbox::dispatch` defaulted true for CF02/CF03 and general File17/File26 topics even with no external owner; `process()` treated a boolean false dispatch as successful because it rejected only WP_Error.
- Fixed: File17/File26/CF02/CF03 topics now require a literal true acknowledgement from their *actual* registered recipient hook (`wca_file17_dispatch_result`, `wca_file26_dispatch_result`, `wca_cf02_dispatch_result`, `wca_cf03_dispatch_result`) after durable idempotent receipt of the envelope's message_id; absence, malformed reply or negative reply leads to a retryable error and worker-fenced fail/dead-letter rather than false delivery. Worker accepts only literal true.
- New independent 17-case dynamic regression verifies absent, negative, affirmative receipts across five event examples and strict worker guard.
- **IMPORTANT external gate:** Owner-side acknowledgement handlers have **not** been proven deployed or registered. This change intentionally reports them degraded and retries rather than falsely succeeding. Companion implementation/interoperability on staging must be established before release and before saying communication/financial/event delivery is operational.

### T30-QA release-currentness reconciliation
- `README.md` and `STATUS.md` updated to exact active branch; historic T21 test was incorrectly enforcing T21 as the *new* review branch, so was corrected to distinguish archival T21 release evidence from current T30 branch. No live changes.
- Permanent tests: `tests/t30-canonical-subject-and-companion-readiness-regressions.php` and `tests/t30-external-owner-acknowledgement-regressions.php`, both in canonical `tests/run-all.php` and covered by the T28 zero-orphan gate.

## Exact HEAD CI and candidate (before addition of this documentation)
Correction HEAD `c726c627736232e263a58928af4c20d6b1fa2238`, GitHub Actions `38285493310` **SUCCESS**: PHP7.4, PHP8.3 full tests/syntax/JS/hygiene, independently verified deterministic candidate. Artifact ID `11763425878`, ZIP digest `sha256:0c837eb32bc9f22663d2cb7ac623d58bdc3c8130cfa311a9a2ab9200652298d5`. New ledger commit changes repository SHA: **must re-run exact-head CI on the new SHA** before final report.

## Governance and blockers
- This is another Draft PR (#16), branched from earlier unmerged T27/T28 PR #12; do not claim any code is already on main/live.
- Live-first freeze has **not occurred**. Main HEAD originally `70541974ce0ffb16aebef557c3016eb7447662f4`; exact deployed WordPress plugin/version, DB/schema and migration state unknown. No installation, restore/rollback, controlled provider-offline tests, real-role/guardian + clinical journeys, File17/File26/CF02/CF03 consumers, browser/a11y/RTL acceptance or Founder staging signoff were performed.
- **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔** Do not call a live symptom resolved without deployment parity and live re-test.
