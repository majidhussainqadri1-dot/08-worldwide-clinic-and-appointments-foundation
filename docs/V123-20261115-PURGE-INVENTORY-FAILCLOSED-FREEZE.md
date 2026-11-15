# File08 V12.3 — irreversible purge legal-hold inventory false-row source freeze

**Freeze:** 2026-11-15. Exact parent draft PR61 SHA `2f06a4a36036a6ec7a88320b3d292423a2b90c10`, previously PR60 candidate `e241562bb7ee24fcd27de360382c7ac41ef944de` green 7/7. Main still `d086840f7e2ac0ed328006385f064e0e2fb58cb1`. **Neither source SHA is proof of Hostinger deployed code.** V12.3 F08-NFR-002 (legal hold / retention) and F08-NFR-007 (backup/rollback).

## Exact frozen root cause
`WCA_Privacy::assert_purge_allowed()` is the irreversible File08 purge's backup-attestation/legal-hold preflight. Its appointments `$wpdb->get_col(...)` and Future24 `$wpdb->get_results(...)` each use `null === raw || $wpdb->last_error !== ''`. SQL failure returning `[]` **with an error is already caught**; do not misdiagnose it. But **malformed non-array `false` with empty last_error** passes each check. Both loops cast `(array) false` to `[false]`; the candidate then returns **true** despite having no trustworthy complete legal-hold inventory. The cleanup operation can proceed past the preflight in that case.

Local PHP 8.4 reproduction loaded the exact PR60 `class-wca-privacy.php`: valid => true, stale previous error => true, `get_col(false)` => **true (incorrect)**, `get_results(false)` => **true (incorrect)**, SQL [] + current error => typed WP_Error (correct), known legal hold => `wca_purge_legal_hold` (correct). No real data touched.

## Minimal correction / expected behavior
- Change **only** these two preflight reads from `null === raw || current error` to `!is_array(raw) || current error`, preserve existing WP_Error codes and status 503.
- Iterate directly over validated arrays; preserve explicit backup attestation and legal-hold rechecks. No deletion, schema or permissions change.
- Native executable assertions on actual method: valid empty, stale error, malformed appointment IDs array (typed), malformed Future24 rows array (typed), genuine SQL error with [] caught, existing legal hold caught, backup unverified blocked. Verify backup must be checked before DB reads. Do not claim 100% legal-hold accuracy without actual host DB.
- Full GitHub final SHA CI and separate human audit; no merge or Hostinger deployment until live-read-only parity and stage/rollback.

**Source-only defect group count:** previous PR60 20, PR61 FUT23 1, this one = **22 scoped groups** if all fixed+proven. Not an exhaustive all-File08 defect count. Merged main 0, staging accepted 0, Live-resolved 0.

Hostinger Jetpack `site_disconnected`, WPVibe no connected site and prior HTTP 530; deployed package/DB/schema/migration/logs unknown. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**