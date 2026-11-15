# File08 V12.3 PR64 — strict appointment audit export SQL read two source reviews and NO-GO

Date: 2026-11-15. These are **two methodological reviews by the same AI assistant**, not two independent human QA approvals. Baseline direct ancestor PR63 exact `56cd96b97dca2c612b42812bb0362f3ffca9c9ff` CI #43675088421 7/7. Main `d086840f7e2ac0ed328006385f064e0e2fb58cb1` unchanged.

## Review A — exact root cause and corrected method contract
- `SWC_Helpers::audit_rows_strict` is the strict source of personal appointment audit events consumed by `SWC_Privacy` export. Previously `null === $rows && last_error` allowed SQL failed `[]` to be treated as valid empty, so audit history could be missing from a personal data export without an error. A return value `false` silently cast to `array(false)` also made fake rows.
- Fix is one helper method only: clear previous SQL error immediately before query, check **non-array OR current error** then return existing `WP_Error('swc_audit_read_failed', 500)`; legitimate row arrays unchanged. `SWC_Privacy` already checks for and returns that error.
- No change to audit event row schema, logger, private fields, role/guardian authorization, actual patient data, clinical decisions, payment, fee, DB migrations, provider integrations or plugin version.
- `SWC_Helpers::audit_rows()` convenience admin wrapper intentionally still hides errors for historical UI behavior; this is separately acknowledged as an unaccepted observability weakness, not surreptitiously changed in this patch.

## Review B — negative test and operational acceptance
- Local PHP 8.4 harness loading the exact previous `SWC_Helpers` returned **0** for SQL failed `[]` and **1 fake row** for `false`; after an equivalent isolated patch both became typed `swc_audit_read_failed` while valid empty, stale prior last_error and genuine audit event remained correct.
- `tests/v123-legacy-audit-strict-read-native.php` invokes real production method under 5 scenarios and a source check that SWC_Privacy still calls strict helper/protects failure. This does not simulate real PHI, auth, multi-page export, a running native WordPress privacy UI, MariaDB hold states or backups.
- Full exact-HEAD CI: PHP7.4, PHP8.3, 40 whole-suite rounds, WordPress 7.0.1/7.0.8/7.1.4 + MariaDB disposable dependencies and reproducible ZIP. Any earlier SHA green run does not cover the final doc/patch SHA.
- Real File08/WP and File00 identity, privacy export/erasure across File17/CF01, legal hold, guardian revocation, provider, p75/p95 and audit observability must undergo genuine private Hostinger staging before any Live claim. Two human independent reviewers and Founder signoff remain mandatory.

## Ledger
One newly evidenced source group; previous 23 + PR64 1 = **24 scoped source defect groups** potentially corrected in draft. Main merged 0, Hostinger staging-accepted 0, Live resolved 0. Not a universal count of every repo or platform defect. No PRs bulk merged/closed.

**NO-GO for Live:** Jetpack site `site_disconnected`, WPVibe site not connected/previous HTTP530. Installed version, checksum, schema and wp_options/migration state and logs unverified.

Mandatory final state: Repository HEAD `d086840f7e2ac0ed328006385f064e0e2fb58cb1` / PR64 exact SHA to be checked / Deployed Version UNVERIFIED / DB Version UNVERIFIED / Migration State UNVERIFIED / Live Verification Status UNVERIFIED.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**