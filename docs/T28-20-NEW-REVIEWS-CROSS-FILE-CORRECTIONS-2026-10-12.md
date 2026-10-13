# File 08 — T28: 20 fresh source sweeps, cross-repository corrective review and exact-head QA

Date: 2026-10-12. Governing basis: uploaded File 08 master plan `SSH-F08-PLAN-2026-v1.0` as amended by Future24, consolidated platform plan `SSH-PMP-2026-v3.0`, and contemporary canonical File 00, 09, 19, 03/07/20/26 repository contracts.

## Methodology and bounded interpretation

Twenty separate **full runtime-source reread + PHP syntax sweeps** were executed on the reproducibly packaged candidate at exact commit `99002d2cd7e4b8f2a33ca972602e09f7e77c2776`. Each pass traversed the entire candidate's **50 PHP/JS/CSS implementation files**, syntax-checked all **44 PHP files** with installed PHP 8.4 CLI, and verified its own distinct source-contract objectives. The 20 distinct passes totaled **79/79 source-presence signals and zero syntax failures** (post-fix tree fingerprint SHA-256: `2e949117659b45fefa2465fc81c0a5a0058be379d67f41400d529f15f87fd080`). Repetition of source scans is not 20 complete functional/DB/browser end-to-end tests and cannot prove deployed behavior.

| Audit round | Primary coding/ownership topic | Source scan outcome |
|---|---|---|
| T28-R01 | boot/dependencies | Runtime source signals present; PHP lint clean |
| T28-R02 | clinic/branches/services | Runtime source signals present; PHP lint clean |
| T28-R03 | schedule/DST | Runtime source signals present; PHP lint clean |
| T28-R04 | atomic holds | Runtime source signals present; PHP lint clean |
| T28-R05 | booking/idempotency | Runtime source signals present; PHP lint clean |
| T28-R06 | lifecycle | Runtime source signals present; PHP lint clean |
| T28-R07 | actor/guardian authorization | Runtime source signals present; PHP lint clean |
| T28-R08 | doctor claims | Runtime source signals present; PHP lint clean |
| T28-R09 | consent/retention | Runtime source signals present; PHP lint clean |
| T28-R10 | emergency diversion | Runtime source signals present; PHP lint clean |
| T28-R11 | review eligibility | Runtime source signals present; PHP lint clean |
| T28-R12 | calendar/fees/complaints | Runtime source signals present; PHP lint clean |
| T28-R13 | File19 delivery | Runtime source signals present; PHP lint clean |
| T28-R14 | File17/File03/CF01 boundary | Runtime source signals present; PHP lint clean |
| T28-R15 | File26 public discovery | Runtime source signals present; PHP lint clean |
| T28-R16 | File20/File25 presentation | Runtime source signals present; PHP lint clean |
| T28-R17 | Future24 F01-08 | Runtime source signals present; PHP lint clean |
| T28-R18 | Future24 F09-16 | Runtime source signals present; PHP lint clean |
| T28-R19 | Future24 F17-24 | Runtime source signals present; PHP lint clean |
| T28-R20 | schema/migration/observability | Runtime source signals present; PHP lint clean |

## Source review findings (collected and corrected on the PR branch)

1. **QA completeness (T28-D1):** Repository tree contained seven independent tests not invoked by canonical `tests/run-all.php`: CF01 static and runtime, doctor authority, public clinic projection, File03 profile contracts, UI contrast and seventeenth R17 privacy/interoperability. All seven were connected to canonical QA. Obsolete test fixtures/assertions were reconciled with current contracts without skipping them. Permanent `tests/t28-test-coverage-regressions.php` now fails CI whenever an executable test is orphaned, missing or duplicated (exempt bootstrap/helpers only).
2. **File 09 version/state consumer mismatch (T28-D2):** Current File 09 exact public source `GDO_VERSION=1.3.0`, `GDO_State::public_verified` permits verified/reinstated/renewal_due under positive verification, current claims, unexpired snapshot and no sanctions. File 08 previously required `<1.2.0` and accepted only verified/approved, falsely rejecting valid current practitioners. Corrected bounded range `>=1.1.0 <1.4.0` and allowed verified/reinstated/renewal_due only with the existing `verified=true`, File00 professional authority, snapshot and revocation checks. Permanent eligibility tests expanded.
3. **File 00 public contract mismatch (T28-D3):** Current File 00 runtime `1.2.44` exports `SMC_CONTRACT_VERSION=1.2.3` and `SMC_Contracts::assertions`; File 08's doctor adapter expected 1.1.2 and failed all normal identity checks. Updated exact contract to 1.2.3; tests use the actual current version. This does not loosen identity eligibility.
4. **File 00 CF-01 assurance mismatch (T28-D4):** Current File 00 `SMC_CF01_CONTRACT_VERSION=1.1.0`, while File 08's care-context provider required exact 1.0.0; scheduling context therefore failed closed even on correctly verified members. Updated File 08 consumer to 1.1.0; refreshed runtime actor/guardian/clinical scope tests.
5. **File 08 own CF-01 advertised/runtime parity (T28-D5):** Plugin alias and canonical contract declared CF-01 context 1.1.0 but `SWC_CF01_Care_Context::CONTRACT_VERSION` remained 1.0.0. Corrected provider to 1.1.0 and added CF01 parity checks to both deterministic package builder and independent artifact verifier, with permanent regression.
6. **CI execution reliability (T28-D6):** Adding the previously skipped tests pushed sequential PHP syntax scanning into the runner command time limit. Canonical workflow now lints *every* PHP source in bounded eight-way parallel `xargs -P 8 -n1 php -l`, without omitting files or weakening failure propagation. The newly revived static CF01 test's historical 0.2.2 assumptions and literal quoting were corrected; warnings/fatals were resolved in the tests themselves. No application code bug was inferred from the old test failures.

## Previously corrected on this same draft PR (T27 provenance)

Protected `File17` events, appointment transitions and ICS from native numeric-ID fallback when canonical appointment public references are missing/corrupt; corrected File19 interface to `sun_register_notification_producer` + `sun_ingest_domain_event`, with owner-held preferences, privacy-minimal delivery, stable outbox idempotency and no bypass through raw `wp_mail`.

## Exact-head CI/package result

At `99002d2cd7e4b8f2a33ca972602e09f7e77c2776` GitHub Actions run `38279326955` has **SUCCESS**:
- PHP 7.4 source contracts, PHP 8.3 source contracts, JavaScript syntax, repository hygiene;
- All **98** executable registered PHP suites included, **zero orphaned**;
- File08 CF01 static **17/17**, CF01 runtime **22/22**, doctor authority **15/15**, public clinic projection **17/17**, File03 contract **PASS**, restored R17 privacy/interoperability **PASS**;
- Reproducible candidate build and independent verification, artifact ID `11760855697`, outer digest `sha256:2b11419267659520f78779a3ea4b5a9a177f086ef7973fd06004edc60e4fed14`.

When this ledger is added, HEAD changes; this historical exact-head success remains valid only for the above source SHA. **Re-run canonical CI/package on the new ledger HEAD before saying the final PR tip is green.**

## Mandatory separate reality and final staging gates

Repository main at audit baseline: `70541974ce0ffb16aebef557c3016eb7447662f4`. Draft PR #12 is repository source only and has not proved install parity. Actual Deployed Version: **UNVERIFIED**. Live DB Version: **UNVERIFIED**. Migration State: **UNVERIFIED**. Live Verification Status: **NOT PERFORMED**. Hostinger staging deployment/backup/restore, real File00/03/07/09/17/19/20/25/26 companion versions, valid patient/guardian/doctor journeys, production-key continuity, actual booking concurrency, provider outage and File19 privacy-preference delivery, browser/RTL/zoom tests, founder sign-off, authorized production deployment and live re-test remain release gates.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
