# File08 T36 — 40 scoped requirement/source screens, remediation, open gates

**Date:** 2026-10-15. **Starting candidate SHA:** `4a0e5cb0b0fcbd6f038946207ee94ab83eb1bdb1` (unmerged PR #26). Separate main `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`. See four pre-correction frozen ledgers `T36-FROZEN-REVIEWS-{01-10,11-20,21-30,31-40}-2026-10-15.md`.

## What was actually checked
Exactly **40 distinct requirement-family code-anchor screens**: 18 File08 functional requirements (FR001–FR018), 10 nonfunctional (NFR001–NFR010), and 12 pairing passes for FUT01–FUT24. For each pass the actual contents of a selected, relevant PHP/JS/CSS code set were fetched from the frozen SHA, searched for target code-path anchors, and the evidence paths frozen before source corrections. These were **not 40 independent whole-repository semantic code proofs**, 40 actual PHP executions, or full-scale WordPress/MySQL/browser E2E runs. Code presence does NOT imply verified behavior.

- 39 screens had at least one matching source anchor in that pass's selected file set.
- FR018 complaint/dispute initially had no anchor in its selected file set (R18). Follow-up source inspection proved `WCA_Service::create_complaint`, `WCA_Service::complaint_projection`, `/complaints` REST routes and existing `tests/t21-r2-complaint-authorization-regressions.php`. This is a **screen-set false negative**, not a proved missing feature.
- All 18 FR, 10 NFR and 24 Future24 features have at least source-level codepath/specific-file evidence or existing regression provenance; implementation completeness is **not** established by anchor scans.
- Companion boundary review: File00 guardian/identity, 03 profile, 07 directory, 09 verification, 14 conversions, 17 communications, 19 notifications, 20 shell, 24 security governance, 25 visual UI, 26 search/ranking, CF01 clinical continuity. Their **actual deployed/runtime parity** was not tested.

## Evidence-based defects and corrections

| ID | What was found | Source action | Regression | Status |
|---|---|---|---|---|
| T36-D01 FUT04 | Corrupt stored scheduling-resource JSON or incorrect branch resource scope could pass into reservation (negative regression reproduced in predecessor PR25) | Ported `verified_resource_reservation_scope`, strict type/branch ownership and fail-closed guard into PR26 candidate | `tests/t35-resource-reservation-scope-regressions.php` added to `tests/run-all.php` | Source correction committed; exact-head CI pending |
| T36-D02 FUT23 | Broken persisted episode linkage JSON could be coerced to an empty array and overwritten (negative regression reproduced in predecessor PR25) | Ported strict episode JSON, privacy/scope and link-reference integrity validation | `tests/t35-episode-stored-integrity-regressions.php` added to `tests/run-all.php` | Source correction committed; exact-head CI pending |
| T36-D03 FUT16 | Queue peer count included stale arrival records without checking other appointments' current `_swc_status` | Query now joins File08-owned WordPress appointments/status meta, accepts only live canonical/approved legacy states, retains scoped DISTINCT/expiry/failure checks | Extended native `tests/t35-future24-guardian-paging-and-stale-queue-regressions.php` with three SQL construction assertions | Source correction committed; **real MySQL fixture required** |
| T36-D04 | Current 1.2.16 candidate documentation had contradictory current 1.2.15 bullet | Corrected `README.md`, `STATUS.md`, `docs/RELEASE-STATUS-1.0.0.md`; T36 precedence notice added while preserving historical T26 regression marker strings | Documentation/text check; exact-head CI pending | Current bullet fixed, historical fixtures remain |

## Preserved predecessor protections
PR #26 previously stacked on #24/#23. The integrated `includes/class-wca-future24.php` retains FUT13 `verified_prerequisite_rules`, FUT14 `family_hub` pagination fail-closed, FUT16 `queue_position` patient state check, and introduces PR25 FUT04/FUT23 checks plus T36 peer-state SQL. File14's previously merged owner-native postcommit event remains in source. Actual companion-system integration is still to be tested on staging.

## Mandatory release blockers — **not resolved**

1. Run exact-HEAD canonical GitHub Actions PHP 7.4/8.3 source/contract/regression and reproducible package verification. CI belonging to an older candidate cannot certify this one.
2. Compare exact generated package source, manifest and SHA-256 with installed staging files; test actual WordPress/Hostinger PHP/WP settings and companion versions.
3. Inspect real DB tables/columns/rows, `wp_options`, core schema 3.4.0, continuity and Future24 contracts 1.1.0, migration state/options, runtime logs and install/upgrade/rollback.
4. Negative staging scenarios with corrupt resource/episode JSON; same-branch isolation; guardian revocation; current and expired arrival signals including *cancelled peers*; large queue p95 and query plans; concurrency, IDOR, privacy/cache, clinical safety, File17/19/14 notifications and owner receipts; browsers, mobile, Urdu RTL, keyboard accessibility.
5. Two independent fresh staging sweeps, backup-and-restore proof, Founder approval, controlled deployment, post-deploy live smoke test, deployed SHA parity and incident verification.
6. **No live root-cause or resolved claim** without exact deployed reality.

## Final truth table

| Dimension | Status |
|---|---|
| Repository HEAD | The current T36 PR head, to be verified on GitHub after final commit |
| Deployed runtime version | UNVERIFIED |
| DB version | UNVERIFIED |
| Migration state | UNVERIFIED |
| Live verification | NOT DONE |
| Remaining defects | Unknown until exact-head QA and staging/live checks; no zero-defect claim |
