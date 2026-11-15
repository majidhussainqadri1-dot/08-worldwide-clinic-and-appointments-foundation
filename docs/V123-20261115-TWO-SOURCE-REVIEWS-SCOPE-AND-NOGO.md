# File08 V12.3 — Two Post-Correction Source Reviews, Scope and Release Gate (2026-11-15)

**Review subjects:** Draft PR #54 descendants of #53; source baseline `main d086840f7e2ac0ed328006385f064e0e2fb58cb1`, parent PR #53 `f388cbf09b2ec565a31be6bcb10555e9a54822c0`, candidate File08 declares runtime `1.2.16` and WCA schema `3.4.0`. This document is a review of repository source, diff and CI methodology, **not independent human review, real-browser accessibility evidence, Hostinger staging or Live acceptance**.

## Review 1 — Functional exact-source and data-contract review

1. **V123-F08-SVC-01:** `WCA_Repository::list_services` had undefined `$limit`. Real method previously read one rather than all permitted 100, and PHP could emit a diagnostic. The updated method uses the existing declared collection cap `MAX_COLLECTION_ROWS` directly; no schema/record writes. A fail-first executable method fixture in `tests/v123-services-list-native.php` tests all 3 mock rows and LIMIT 100, whereas `tests/v123-real-wordpress-services.php` additionally executes the genuine code against a **disposable** MariaDB table seeded with three isolated service records and removed via `finally`. Legitimate greater-than-100 paging remains unimplemented by this scoped correction; this is **a deliberately documented bounded query contract**, not universal infinite pagination.
2. **V123-F08-SVC-02:** `WCA_Service::public_clinic_projection` already gates current clinic/doctor eligibility and exposes public `fee_minor`, `fee_max_minor`, currency and service/clinic terms. `WCA_Frontend` uses only these public fields, displays exact minimum and bounded maximum without fabricating taxes, discloses 0% platform commission, and labels requests as unconfirmed. Missing tax/refund/cancellation terms use no invented policy; negative source fixture asserts unpublished status.
3. **V123-F08-SVC-03:** The canonical service writer permits `home_visit` among four types; the presentation label now recognizes Home visit. The client emits remote-consultation consent only for `online` or `hybrid`, not ordinary home_visit or in-person services. No automated diagnosis/clinical act is introduced.
4. **Race/selection:** A selected service change synchronizes its fee/policy panel with the request field; the old stale-search/held-slot protection remains. Browser-accessibility and screen reader live testing are **not substituted** by the Node mock.
5. **Release code scope:** Compared with parent PR #53, the new correction edits only `includes/class-wca-repository.php`, `includes/class-wca-frontend.php`, `assets/js/clinic.js`, `assets/css/clinic.css`, new test fixtures, workflow/test aggregator and evidence docs. It does not change WCA schema, plugin runtime version, payment mutations, clinical records, stored service values, provider secrets, canonical File00 eligibility, or deployment.
6. **All 54 clauses:** `docs/V123-20261114-F08-EVIDENCE-EXECUTION-REGISTER.md` is traceability only (FR18/NFR10/CEN2/FUT24), not a claim that 54/54 runtime journeys were witnessed.

**Review 1 verdict:** Three new *source-scope* findings have plausible bounded source corrections and executable coverage; accept for exact-final-SHA CI review only. No production claim.

## Review 2 — Adversarial privacy, integration and failure-state review

1. **Public/private boundary:** Clinic projection requires active clinic, current verified owner, service practitioner eligible and authorized to serve this clinic before emitting public services; unapproved/private services must never be included. Test this with *actual roles and invalidated identity* in controlled staging; source comments and unit fixture are not complete access-control evidence.
2. **Untrusted policy strings:** No policy's raw HTML is trusted. Server markup uses `esc_html`; the new native XSS fixture checks both service and clinic fallback payloads, plus a missing-all-terms case. A later human security/privacy reviewer must additionally inspect sensitive-field ownership and PHP filter hooks on Staging.
3. **Fees:** Currency fractional digits must follow File08 source-specific formatting, no assumed flat two digits; max zero is an unspecified upper range and must not be fabricated; the platform remains **single free tier, 0% clinic commission**, physician professional fees may exist. Payment status can be authoritative only after CF03 verified state; public request is not payment success.
4. **Legacy 13 source fixes:** Parent #53 collects earlier FUT08 heatmap/DST, FUT07 SQL/UTC midnight and client races. This scoped follow-up changes no corresponding runtime implementation `includes/class-wca-future24.php`. Whole-suite must rerun to prove no regressions; source ancestry itself is not a release certificate.
5. **Companion modules:** File14 booking aggregate issue #70 unverified; File25 doctor-profile booking destination owner contract pending; File26 search projection PR #44 draft; File17 remote room, File19 notifications, File20 shell, CF01 *disabled-by-default* medical chart, and CF03 payment providers are separate source owners. See `docs/V123-20261115-CROSS-OWNER-CONTRACT-FREEZE.md`. These eight companion modules are **not** included in File08's six-plugin WP CI matrix. Do not activate CF01 or assume external financial/notification handlers are healthy.
6. **Platform access:** Read-only reality freeze attempted using WordPress.com/Jetpack and WPVibe. Jetpack `site_disconnected`, WPVibe HTTP 530, without evidence of a precise failure root. Hostinger deployed code/version, entry/tree checksum, WP options, migration and logs remain unknown. No deployment parity and no sanctioned staging or live outcome.
7. **CI acceptance law:** PHP7.4, PHP8.3, Node booking race, 40 whole-suite repetitions, real *disposable* WP7.0.1 / 7.0.8 / 7.1.4 + MariaDB, deterministic ZIP must all PASS on the **final exact candidate HEAD after this document's commit**. Prior successes at different SHAs are historical only. Do not mark code packaged until matching artifact manifests/hash recorded.
8. **Defect ledger:** 13 original scoped source groups from #53 + 3 new F08-FR-002 source groups = **16 identified/source-corrected *candidate* groups**. Source merged into `main` = **0**. Hostinger Staging-accepted = **0**. Live-resolved = **0**. Number of additional unknown defects not claimed to be zero.
9. **Governance:** Current GitHub register has 35 open PRs with exact per-PR SHA inventory, and 26 historically divergent branches; no bulk merge/close. Issue #47 main branch protection, Issue #4 CF01 scope, Issue #52 File07 richer search and File14 #70 require qualified owners' decisions.

**Review 2 verdict:** No source fact authorizes clinical/financial, production or Live enablement. Require independent human code review, actual owner contract confirmation and **P01 Live Reality Freeze → P08 cross-owner staged workflows → P09 accessibility/performance/roles → P10 backup/rollback + Founder go/no-go → P11 parity+live retest**.

## Status and hard-stop decision

| Field | Source truth / status |
|---|---|
| Repository HEAD | `d086840f7e2ac0ed328006385f064e0e2fb58cb1` |
| Candidate | Draft PR #54, final SHA to be re-fetched after this review commit |
| Deployed Version | **Unverified** |
| DB Version | **Unverified**; source *declares* WCA 3.4.0 |
| Migration State | **Unverified** |
| Live Verification Status | **NOT TESTED / NOT RESOLVED** |
| Found / corrected-source / merged / staging / live | **16 / 16 (candidate only) / 0 / 0 / 0** |
| Release decision | **NO-GO** until independent host/prod evidence |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
