# File08 V12.3 — legacy appointment audit history strict read source freeze (2026-11-15)

**Exact frozen base** is cumulative Draft PR63 SHA `56cd96b97dca2c612b42812bb0362f3ffca9c9ff`, GitHub run #43675088421 7/7 CI succeeded. Main `d086840f7e2ac0ed328006385f064e0e2fb58cb1` unchanged. **This source is not the installed Hostinger code.**

## Source root cause (before patch)
`SWC_Helpers::audit_rows_strict( $appointment_id )` is the strict legacy appointment audit reader in `includes/class-swc-helpers.php`. Its `$wpdb->get_results()` failure guard is `null === $rows && $wpdb->last_error !== ''`, rather than checking a fresh SQL error even when WordPress returns an empty array. A database error returning `[]` + a new `last_error` is misrepresented as a valid empty audit history. An invalid `false` result without SQL error is cast to `[false]`. The method does not clear a stale previous last_error before its query.

**Privacy impact:** `SWC_Privacy` calls `audit_rows_strict()` and propagates its typed WP_Error if returned, but a false empty audit means an incomplete personal information export without a retry/error. `SWC_Helpers::audit_rows()` intentionally down-converts errors for a legacy admin convenience caller; this separate legacy UI contract is not changed here.

Local PHP 8.4 reproduction with actual unmodified method and synthetic DB transport: valid [] => 0 rows; stale previous marker + valid [] => 0 rows; SQL []+new error => **0 rows incorrectly**; invalid false => **1 bogus row**; actual object row => 1. No real patient data.

## Controlled fix and acceptance
- In this one strict reader only: clear `$wpdb->last_error` before query; require `is_array($rows)` **and** newly empty last_error before returning; else emit its *existing* `WP_Error('swc_audit_read_failed',500)`.
- Do not alter private audit history records, log contents, clinical metadata, consent, payment, schema, WP migrations or actual accounts.
- Native PHP real-method tests: legitimate empty, stale old SQL marker, failed query producing [], invalid false, one valid audit object row. Add source assertion that `SWC_Privacy` still calls strict reader and returns its error (this alone not a real exporter E2E).
- Exact final HEAD 7/7 CI (PHP7.4/8.3, WP7.0.1/7.0.8/7.1.4 MariaDB, 40 rounds, reproducible ZIP), two post-fix source reviews and required independent human QA/staging before any merge/deploy.

**Count:** 1 new grouped legacy audit/PRIVACY-export source integrity defect. Existing cumulative 23, proposed now **24 scoped groups** after CI; `main` merged 0; Hostinger stage-confirmed 0; Live-resolved 0. No universal zero-defect claim.

**Live Reality Freeze remains blocked:** Jetpack `site_disconnected`, WPVibe no linked site/earlier HTTP530; installed package/DB/migration/logs parity unknown. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**