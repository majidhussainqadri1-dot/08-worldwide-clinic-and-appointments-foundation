# File08 V12.3 — PR63 two post-patch source review passes, Future24 waitlist and clinic policies

**2026-11-15.** Methodologically separate review passes by one AI assistant; **NOT** two independent human QA/security/clinical approvals. Main exact `d086840f7e2ac0ed328006385f064e0e2fb58cb1`; separate Draft PR63 lineage starts PR62 exact `436818ac7349340890c1fe4f91c260e377dae091`. PR61 ancestor CI #43674224248 passed 7/7 source/disposable tests; PR62 final CI must be checked on exact SHA.

## Review A — source ownership and root-cause correction

- **F08-FUT-01 P0:** `WCA_Future24::waitlist_candidates` generates 100-row pages. It formerly accepted `[]` + active SQL error as no waiting patients, and non-array `false` as one bogus candidate. A skipped second page could suppress offers to waiting patients beyond first 100 even when outbox event would be marked successful. Corrected per-page guard throws `RuntimeException('wca_waitlist_candidate_read_failed')`, preserving retry semantics, not issuing automatic appointments.
- **F08-FUT-11 / 13:** `WCA_Future24::feature_rows_for_clinic` returned an empty or partial policy/template list when a page failed with `[]` + error, and could insert a fake record from `false`. Corrected guard returns preexisting typed `wca_future24_policy_read_failed` (503), which existing callers propagate.
- Both methods now clear `$wpdb->last_error` immediately before SQL, check returned array type and live error, validate strictly monotonically increasing positive row IDs before advancing keyset cursor, then iterate only verified rows.
- No change to encrypted questionnaire answers, consent/guardian checks, File19 notification authority, waiting-list 10-offer limit, clinic fees, provider secrets, database schema or migration versions.

## Review B — adversarial tests and unresolved limits

- Real-method local PHP 8.4 reproduction BEFORE patch: both methods generated 0 (bad first SQL []), 100 partial (bad second SQL []), and 1 bogus row (malformed false), all wrongly accepted. AFTER isolated equivalent patch, each method respectively threw the expected RuntimeException or returned the expected WP_Error; legitimate 0 still accepted.
- GitHub native fixture `tests/v123-future24-operational-list-native.php` exercises real protected methods with six scenarios each: valid empty, stale prior error, first page SQL error, second page SQL error after 100 valid rows, non-array false, corrupt row. Tests assert actual traversal of 2 pages and fail-closed error propagation.
- Important nuance: a waitlist Generator yields page-1 entries before a later-page SQL failure. Its consumer can already have emitted some guarded offers by the time the failed query raises; **outbox idempotency and duplicate-offer compensation must be exercised on real WordPress staging**, not inferred from this isolated fixture.
- Heavy waitlists (>10000), latency p75/p95, privacy-safe File19 notifications, real guardian claim revocation, stale slot recheck and File26 cross-owner rank/visibility are not accepted by these source tests. Exact SHA CI (PHP7.4/8.3, 3 disposable WordPress/MariaDB, 40 source rounds, deterministic candidate package) needed after final commit.
- No actual Hostinger patient data, DB, provider, schema or runtime logs were accessed. No production release.

## Acceptance and counts
One newly proven grouped Future24 list-read integrity issue (two methods). Cumulative **23 scoped source defect groups** in candidate branch after previous 20+PR61 migration+PR62 purge+PR63 list, assuming the prior register's consistent counting, NOT a global File08 defect census. Main merged 0; Hostinger Stage accepted 0; Live-resolved 0.

**Production NO-GO:** Jetpack `site_disconnected`, WPVibe site unconnected/earlier HTTP530; installed code checksum/version, DB/schema/migration flags and actual dependencies unknown. Independent human review, reversible staging upgrade, backup/restore, real role/consent and owner testing required.

**Mandatory terminal fields:** Main HEAD `d086840f7e2ac0ed328006385f064e0e2fb58cb1`, Candidate PR63 final HEAD from GitHub, Deployed Version UNVERIFIED, DB Version UNVERIFIED, Migration State UNVERIFIED, Live Verification Status UNVERIFIED.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔