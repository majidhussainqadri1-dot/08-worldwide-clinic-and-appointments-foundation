# File08 V12.3 — Restricted continuity guard: consent and privacy-erasure SQL source freeze (2026-11-15)

Exact base: PR58 `bcd3d8a2053781c4d25f4d21014d395b57454e03`; exact CI #43670809554 7/7 passed. Main `d086840f7e2ac0ed328006385f064e0e2fb58cb1`; no release/deployment.

## New root cause distinct from the already-corrected WCA_Continuity methods
The separate `includes/class-wca-continuity-guards.php` class contains two further `wpdb->get_results` reads:
1. `WCA_Continuity_Guards::consent_state` can misrepresent a failed read `[]` with `last_error` as every consent scope `not_granted` rather than reporting state unverifiable (a false current-consent assertion). `(array) false` can make invalid row hydration.
2. `WCA_Continuity_Guards::privacy_eraser` can misrepresent `[]` + fresh SQL error as no records remaining, then proceed to completion. Invalid `false` can enter the row loop as a fake row.
Both are outside PR58's corrected `WCA_Continuity` class and require distinct source test and patch. No claim this occurred on actual Hostinger.

## Narrow correction
- In each of these **two** get_results sites, reset `$wpdb->last_error` immediately before query, check `! is_array($raw) || '' !== last_error` instead of `null === $raw && last_error`, and iterate only verified row arrays.
- Consent API uses typed `wca_consent_state_read_failed` 503; erasure uses a descriptive retry and `done=false`.
- No database migration, role/guardian decisions, consent contents, patient data, operational provider, schema, fee, plugin version or main merge.
- Execute actual consent_state/eraser methods with valid, stale, SQL error returned `[]`, malformed non-array `false`, then exact-SHA PHP7.4/8.3, 40 suite repeats, 3 disposable WP versions and reproducible candidate ZIP.
- Separate reviewer/owner Staging clinical consent and privacy tests, rollback and Founder approval remain mandatory.

## Truth and status
A single grouped source flaw in **two distinct guard methods**; 19 previous scoped groups + this makes **20 grouped source findings** if corrected and tested. This is not a claim the entire repo has only 20 bugs. Main-fixed 0; Hostinger Staging accepted 0; Live-Resolved 0.

Jetpack `site_disconnected`, WPVibe no connected site / former HTTP 530, installed plugin/DB/migrations/logs/sha unknown. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
