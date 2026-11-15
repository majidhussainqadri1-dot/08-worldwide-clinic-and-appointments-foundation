# File08 V12.3 — FUT23 privacy episode schema migration: two source reviews (2026-11-15)

**Scope:** review passes A/B are independent *methodological passes by the same AI assistant*, not independent human code reviews, host staging acceptance, or authorization to migrate patient records. Candidate descendant of PR60 `e241562bb7ee24fcd27de360382c7ac41ef944de`; main unchanged `d086840f7e2ac0ed328006385f064e0e2fb58cb1`.

## Review A: root-cause-to-correction parity
- **Cause:** `WCA_Future24::install_schema` treated `[]` plus `$wpdb->last_error` as legitimate zero-result page and proceeded to write Future24 schema version. On an unprocessed F08-FUT-23 privacy episode, the migration marker becomes a false success condition.
- **Fix:** clear last_error directly before each of its legacy episode page reads, block any non-array result or new SQL error, validate each legacy episode row is an array with id and appointment_id fields, then loop on the validated array. Version marker code not moved or modified; it remains strictly after the complete loop.
- **No unrelated changes:** no table SQL, migration schema numbering, fees, consent, guardian rights, appointment source-of-truth, cryptography, provider, patient data or runtime deployment change. The prior 20 grouped source fixes preserved from parent PR60.
- **Additional bound:** keyset query continues at 200 rows; a failed page anywhere aborts before marker. A current source-CI test cannot establish rollback of writes to earlier rows in a partially processed page; Hostinger migration handling must be reviewed separately.

## Review B: adversarial acceptance and limitations
- Native `tests/v123-future24-migration-read-native.php` directly invokes the real `WCA_Future24::install_schema` against a WordPress DB transport stub, not a duplicated algorithm. Five conditions: clean empty, stale previous SQL error, first page SQL []+error, second page SQL []+error after 200 migrated rows, invalid non-array result.
- For the two normal scenarios, version marker must be written exactly once. For the 3 error cases, throw and write zero schema version markers. The second-page case also verifies 200 source rows were processed before the fault and the loop did not claim completion.
- Final SHA CI must pass PHP 7.4 + 8.3, 40 whole-suite rounds, three disposable WordPress/MariaDB versions, reproducible plugin ZIP. An earlier PR60 green check does not validate this SHA.
- Real end-to-end migration needs an existing pre-upgrade schema and actual synthetic old episode records, forced SQL error on later page, rollback/restore, idempotent rerun, wp_options migration reconciliation and File00/09 owner identity constraints on a **separate true Hostinger staging** environment. No clinical patient records tested.
- Static V12.3 54-item source traceability is not equivalent to 54/54 real acceptance, WCAG, 320px responsive or cross-owner privacy authorization.

## Ledger
One new `F08-NFR-007 / FUT23` source defect group; previously scoped 20 source groups. Candidate 21 groups subject to exact-HEAD CI. Integrated in main 0; Hostinger staging accepted 0; Live-resolved 0. This is not an exhaustive claim about all File08 defects.

**NO-GO:** Jetpack `site_disconnected` and WPVibe no connected site / earlier HTTP530, actual deployed plugin SHA, DB version, migration flags, logs and dependencies unavailable.

Required final: Repository HEAD `d086840f7e2ac0ed328006385f064e0e2fb58cb1` / Candidate PR61 HEAD from GitHub / Deployed Version UNVERIFIED / DB Version UNVERIFIED / Migration State UNVERIFIED / Live Verification Status UNVERIFIED.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔