# File08 V12.3 — Privacy SQL source reviews A/B and production No-Go

**Two separate methodological review passes by one assistant; neither replaces independent human reviewer approval or Stage/Live.**

## Review A — minimal code and state contract

- Exact ancestor Draft PR56 `4f6fe7ca754dbd695da06022774f34c25b4eb228` passed full GitHub CI run #43669619813 **7/7** jobs; unmerged `main d086840f7e2ac0ed328006385f064e0e2fb58cb1`.
- No change to the prior 17 scoped source correction groups (PR53/PR54/PR56); PR57 modifies only `includes/class-wca-privacy.php`, an isolated native fixture, the suite runner and source-review/freeze docs.
- Three Future24 SQL list reads already clear `$wpdb->last_error` before the SQL; replacing `null === rows && last_error` with `!is_array(rows) || last_error` captures empty array plus failed SQL and malformed `false`.
- Avoid `(array) false` because it creates `[false]`; a fabricated row can cause PHP warnings and a fatal on `row['id']`.
- Existing API meanings retained: export returns typed `wca_privacy_export_future24_query` error; erase sets `done=false` with a retry message; retention returns `wca_retention_future24_read_failed`. No "erase success" is inferred from an unreadable record set.
- Data-specific conditions, file owner boundaries, deletion/update SQL, legal holds, consent, logging, user roles, payment, plugin version and DB/migrations untouched.

**A result:** narrowly scoped source correction for one grouped privacy integrity defect with three paths, no claim of real host enactment.

## Review B — adversarial behavior, reliability, limitations

| Native scenario | Export | Erase | Retention |
|---|---|---|---|
| Valid [] response with no SQL error | data=[], done=true | no remaining records, done=true | true |
| Prior SQL error but current valid [] | stale error cleared and complete | stale error cleared and complete | stale error cleared and true |
| Fresh SQL error and `get_results() = []` | typed WP_Error | done=false, retry message | typed WP_Error |
| Malformed `get_results() = false` | typed WP_Error, no PHP fatal | done=false, no false row | typed WP_Error |

- A local isolated PHP 8.3 harness executing actual `WCA_Privacy` before patch showed **3 wrong success outcomes among 9 scenarios** plus fatal on non-array. Identical minimal local patch then passed **12/12** source-level conditions. New GitHub fixture `tests/v123-privacy-lifecycle-sql-native.php` executes actual methods with fake DB transport; final exact-head CI needed.
- This fixture does **not** prove WordPress native privacy exporter contract, real patient consent, large paging, legal holds, actual erasure across File00/CF01, backups, or on-host privacy provider behavior.
- Real Hostinger staging, data minimization, legal holds, export/erase correctness and backup restoration are mandatory before any human claim that a patient privacy request is fulfilled.
- Security concern: separate File14/17/19/20/25/26/CF01/CF03 dependency runtime and identity/authorization tests not included in the six/three-dependency disposable matrix.

## Release state, exact source truth
- New source findings: one grouped Privacy Lifecycle SQL-source defect affecting three paths. Cumulative **18 scoped source defect families** (former 16 plus 1 PR56 plus 1 PR57), not a total bug count for entire project.
- Candidate corrections: 18 source families drafted, final exact SHA and CI to be established. Main merged 0; Staging accepted 0; Live-resolved 0.
- Jetpack `site_disconnected`, WPVibe no connected site and earlier HTTP530 cannot establish Live origin root cause. Live installed plugin/version/tree hash; DB/schema/rows; wp_options migration and logs are unverified.
- Do not merge, package as a production-ready release, deploy, close dependencies, or declare Source tests equivalent to actual clinical/patient privacy acceptance.
- Re-run all tests after the last doc/source commit; old successful SHAs do not prove new SHA.

**Mandatory terminal fields:** Repository HEAD / PR57 final SHA/CI / Deployed Version unverified / DB Version unverified / Migration State unverified / Live Verification Status unverified.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
