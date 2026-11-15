# File08 V12.3 — PR62 irreversible purge preflight: review passes A/B and NO-GO

**2026-11-15.** These are two *methodological AI source-review passes*, **not** two independent security/clinical human reviewers. Main `d086840f7e2ac0ed328006385f064e0e2fb58cb1` is unchanged; this is a draft branch derived from PR61 exact `2f06a4a36036a6ec7a88320b3d292423a2b90c10`.

## Review A — exact source/limited behavior
- `WCA_Privacy::assert_purge_allowed()` is a **read-only preflight**: a verified restorable backup must be attested, and all appointment and Future24 legal holds must be read before any irreversible purge.
- Existing condition **already catches `[]` plus nonempty last_error**. Do NOT misstate that as the new issue. Proven defect is malformed `false` source result **without** an SQL error, which is not `null`; PHP `(array)false` creates a fabricated zero row and the method can return true.
- Corrective source only changes the two inventory checks to `!is_array(raw) || last_error` and iterates verified arrays without changing existing backup, role, legal-hold or deletion semantics. No DB writes, schema or version changes.
- The actual method's local PHP 8.4 harness **before patch** returned `ok` for `get_col(false)` and `get_results(false)`; with a byte-equivalent isolated two-query fix, both returned 503 `wca_purge_hold_inventory_failed` or `wca_purge_future24_hold_inventory_failed`, while valid-empty, stale markers, SQL faults, confirmed hold tests retained their intended statuses. This is a source experiment, not a Hostinger test.

## Review B — adversarial safety acceptance

| Condition | Expected | Proven context |
|---|---|---|
| No legal holds, valid SQL [] | true, after backup verified | Real method + synthetic DB |
| Old unrelated SQL error | last_error cleared before current reads | Real method + synthetic DB |
| Appointment list malformed false | typed 503, NEVER authorize purge | Real method + synthetic DB |
| Future24 list malformed false | typed 503, NEVER authorize purge | Real method + synthetic DB |
| Appointment or Future24 SQL failed []+error | Existing 503 checks preserved | Real method + synthetic DB |
| Actual appointment legal hold | 409 `wca_purge_legal_hold` | Real method + synthetic DB |
| No verified backup | immediate 409, no DB reads | Native fixture regression |
| Real legal-hold records, recovery, actual WordPress purge | **NOT tested** | Needs private Hostinger staging, backup/restore and human approval |

- Native CI `tests/v123-purge-inventory-native.php` uses the actual implementation and verifies all 8 conditions. The final exact SHA must pass PHP7.4/8.3, 40 source rounds, 3 disposable WP/MariaDB versions, deterministic ZIP; no stale predecessor CI may be reused.
- No real WordPress patient deletion was triggered by this work. No independent human signoff, no actual backup restore, no proof that the installed Hostinger code shares this SHA.
- P01 and P10/P11 hard stop: the installed plugin checksum/version, wp_options and DB/schema/migration state, logs, provider dependencies, validated backups, actual legal holds and Founder authorization must be obtained before any irreversible action.

## Defect accounting and release gate
**One new grouped source defect** (malformed preflight inventory shape, two read paths). Previous 21 groups (PR60 20 + PR61 migration 1), now **22 scoped source groups** potentially source-corrected in draft PR62 if final CI passes; merged main 0, Hostinger staging accepted 0, Live resolved 0. This does not assert the whole platform has exactly 22 defects or is ready for release.

**NO-GO for Live:** Jetpack `site_disconnected`, WPVibe no connected site/previous HTTP530. Deployed checksum, actual DB and migration not verified. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

**Mandatory state:** Main HEAD `d086840f7e2ac0ed328006385f064e0e2fb58cb1`; Candidate PR62 exact final HEAD via GitHub; Deployed Version unverified; DB Version unverified; Migration State unverified; Live Verification Status unverified.