# File08 V12.3 — Privacy export, erasure and retention SQL failure freeze

**Date:** 2026-11-15. **Exact source baseline:** Draft PR56 HEAD `4f6fe7ca754dbd695da06022774f34c25b4eb228`, full exact-SHA run #43669619813 7/7 jobs SUCCESS. This branch is cumulative only in lineage; the present correction touches a separate privacy source file. Default main remains `d086840f7e2ac0ed328006385f064e0e2fb58cb1`. Neither is Hostinger Live.

**Governing clauses:** V12.3 F08-NFR-002 Privacy lifecycle (purpose, consent, minimization, retention, export, erasure, legal hold), F08-NFR-003 reliability, and central M/QA privacy release gates. **No patient data must be represented as exported, erased or retained correctly without a verified read.**

## Frozen exact code proof before patch
- `WCA_Privacy::export`: File08 Future24 query checks `null === $future_rows_raw && last_error` but can receive `[]` + nonempty `last_error` from failed `wpdb->get_results`. That becomes an empty export with `done=true` when the appointment page is also empty. Invalid `false` result is converted to `[false]` and can lead to PHP warning/fatal while projecting `id`.
- `WCA_Privacy::erase`: after successful appointment erasure check, Future24 query can return `[]` + SQL error, bypassing the false-result branch; then `done=true` and cursor is cleared although Future24 privacy work was not verified. Invalid `false` can also become an unsafe row.
- `WCA_Privacy::apply_retention`: Future24 expired-record sweep can return `[]` + SQL error; method reports true while retention status was not verified. Malformed `false` is cast to `[false]`.
- All three already reset `$wpdb->last_error` immediately before their Future24 queries, so the **smallest fix is not a new error-clear policy**; only inspect the returned shape or fresh `last_error` and avoid false casts.

## Pre-patch native behavior reproduction
A local isolated PHP method harness with actual pre-patch WCA_Privacy from reproducible PR53 candidate and WordPress DB-transport stubs: 9 cases (valid empty, SQL returns [] + error, stale prior error) with **3 failed** cases — export, erasure, retention false success on SQL error. An additional non-array `false` scenario triggered a PHP offset warning/fatal. No production secrets, PHI, or Hostinger state accessed. In a local patched copy all 12 state+method scenarios passed. Final acceptance is **GitHub exact-HEAD CI native regression and disposable WordPress**, not the local copy alone.

## Minimal controlled correction
1. Replace these three conditionals with `! is_array($rows_raw) || '' !== (string)$wpdb->last_error` (proper variable for export) without altering message and return statuses.
2. For these three result rows only, use `is_array($rows_raw) ? $rows_raw : array()`, not `(array) false`.
3. Tests must execute real `WCA_Privacy::export`, `erase`, `apply_retention` with stubbed DB: 3 methods × 4 modes = 12 scenarios; assert correctly typed WP_Error / incomplete erase and no corrupt row hydration.
4. Entire PHP7.4/PHP8.3/40 source rounds/3 disposable WP matrices and deterministic build required after final SHA. Reviewer passes and formal staging/rollback remain separate. No schema, plugin version, fee, consent, actual record or provider changes.

## Release boundary
WordPress/Jetpack `site_disconnected`, WPVibe disconnected/HTTP 530. No deployed package checksum, actual DB schema/rows/options/migrations/logs; therefore no inference of Hostinger incident, production source fix or true privacy right fulfilment. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

**Count:** One grouped privacy-lifecycle source defect family affecting three processing routes. Previous candidate report had 16 groups + PR56 1 = 17; this candidate makes **18 scoped source defect groups**, assuming the same counting convention; merged main 0, staging verified 0, live-resolved 0. This number is *not* an exhaustive all-platform defect count.
