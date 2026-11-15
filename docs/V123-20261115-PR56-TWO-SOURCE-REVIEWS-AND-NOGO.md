# File08 V12.3 — Two post-correction source reviews: clinic collection integrity

**Date:** 2026-11-15. **Type:** two independent *methodological passes by the same AI assistant*, **not two independent human reviewer approvals**. This document cannot substitute for real WordPress staging/Hostinger/Founder acceptance.

## Frozen baseline and lineage
- Default main: `d086840f7e2ac0ed328006385f064e0e2fb58cb1`.
- Prior consolidated PR #53: `f388cbf09b2ec565a31be6bcb10555e9a54822c0`, CI #43619421753 passed 6/6, not merged or deployed.
- Direct source parent PR #54: `0e71aa40f18ac0255038ef19ea3eb0c170d6a1ac`, FR002 service/fee/policy enhancements, green CI #43666469158, not merged/deployed.
- This draft PR #56 branches from that precise PR54 commit. **No Hostinger or main change**.

## Review A: structural root cause and minimal patch

| Check | Source evidence | Finding |
|---|---|---|
| Four collection readers | `list_clinics`, `list_branches`, `list_services`, `list_availability_rules` are the only scoped edits in `includes/class-wca-repository.php` | Limited source footprint, no schema or domain contract change |
| SQL empty vs no rows | Previously `null === $rows_raw && last_error`, but `wpdb->get_results()` may be `[]` with SQL `last_error` | Fail-open empty collection in source |
| Pre-query stale SQL marker | Previously no explicit reset immediately before query | Could misclassify result if query transport preserves old marker |
| Non-array transport result | `(array) false` yields `array(false)`, allowing `hydrate_clinic(false)` warnings/fatal | Separate failure symptom within same family; fixed by `is_array() ? rows : array()` |
| Post-fix behavior | Reset last_error; reject non-array OR newly set last_error and latch typed 503; retain public return type | Source-corrected; native/CI proof required |
| Domain/cross-owner changes | No changes to consent, fees, patient record, payment, guardian roles, expiry, calendar or migration | No unrelated patch stacking |

**Review A conclusion:** one grouped defect family affecting four collection APIs. Prior 'FR002' fix of undefined service limit in PR54 stays unchanged. No claim that all collection and data integrity risks throughout File08 are exhausted.

## Review B: adversarial/negative and integration checks

| Scenario | Source native test | Expected |
|---|---|---|
| Valid empty clinic/service/branch/availability | Actual WCA_Repository four methods | [] with no error latch |
| Old unrelated last_error before a valid query | Each method resets marker before query | [] with no error latch |
| Empty array + fresh SQL failure | Each method checks last_error even for [] | typed WP_Error 503 latch |
| Invalid/false database result | Each method checks is_array before cast | typed error, safe [] return, no fake false item/no fatal |
| Public-service caller | Actual private `WCA_Service::repository_read` via reflection with actual repository | Valid empty stays [], SQL failure surfaces typed WP_Error |
| Invalid patient, doctor revoked, privacy export, payment status | Out of this correction scope | Existing code remains; must re-run complete suites |
| PHP 7.4 / 8.3 / native WP7.0.1/7.0.8/7.1.4 | Exact-head GitHub CI | **Pending verified final SHA**; never reuse prior PR54 green results |
| Hostinger installed package, DB, wp_options/migration | No connectivity; Jetpack `site_disconnected`, WPVibe absent/HTTP530 | **BLOCKED** |
| File26 public search / File14 analytics / File17/19 providers | Needs live owner contracts; PR44/42 and issue 52 still pending | **BLOCKED** |

## QA history
- First correction at PR56 SHA `ac72661e7c044f66bf8e287aaa6caf123bd2db03` failed GitHub Actions run #43669177352 on PHP 7.4/8.3 because non-array `false` could still hydrate as an array element. This is an actual fail-first regression (not disguised as success).
- Follow-up source SHA `e55a6a123ff7f03cfedfb68676ad767b10413452` corrected that false-cast path in all four methods.
- Native fixture `tests/v123-collection-read-native.php` extended to real `WCA_Service::repository_read` propagation; final source SHA must be read from PR HEAD after this document commit.
- Separately, a local equivalent PHP 8.3 harness with extracted PR53 runtime and compatible PR54 service-limit correction passed 16 collection + 2 service-wrapper scenarios. This is an independent *environment* from GitHub CI but not an exact PR56 HEAD build receipt.

## No-Go and final truth
**GitHub main is not Live.** The current branch is Draft and not approved for merge/deployment. Even full CI green is not staging clinical E2E, rollback, 54/54 acceptance, privacy/a11y/RTL or Production parity.

**Correction counts:** 1 newly frozen *defect family*, 4 affected collection methods; a source candidate exists, main-fixed 0, Hostinger Stage accepted 0, Live-resolved 0. Prior PR54 reports 16 scoped defect groups; this review adds 1 group (cumulative **17 scoped source groups**, assuming same frozen counting scheme, not a universal defect count).

**Mandatory state:** Repository HEAD `d086840f7e2ac0ed328006385f064e0e2fb58cb1` / Candidate PR56 SHA to be read freshly / Deployed Version unverified / DB Version unverified / Migration State unverified / Live Verification Status unverified.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
