# File08 V12.3 — Clinical continuity read-integrity freeze, 2026-11-15

## Exact prior state and risk scope
- Baseline Draft PR57 exact SHA `160d632fcab85ceb98bbbec7120118f7195ad8ce`, passing exact-HEAD GitHub run #43670205139 (7/7), is not main or deployed Live. Canonical `main=d086840f7e2ac0ed328006385f064e0e2fb58cb1`.
- V12.3 F08-NFR-002 privacy lifecycle, F08-NFR-003 reliability, F08-FR-017 continuity boundary and FUT11/12 preparatory records plus consent/guardian and reminders.
- **Code-path proof before changes:** `WCA_Continuity::list_followups`, `process_due_followups`, `apply_retention`, `privacy_exporter`, `privacy_eraser` contain **8 get_results() reads**, each uses `null === $result && $wpdb->last_error` and may mistake `[]` returned with a real SQL error for a valid empty result. `(array) false` also produces `[false]` and can trigger invalid-record hydration. The guardian-user erasure query in `privacy_eraser` is a separately important 8th read.
- Risk: falsely empty follow-up lists, missed reminders, misleading completed retention, incomplete private export and erasure marked complete; **source-only inference, not a verified Hostinger event**.

## Fail-first local evidence
- PHP 8.3 isolated harness loading the actual existing method code with WordPress DB stubs tested 5 methods x (valid empty, SQL error returned as [], stale prior SQL marker). **Five negative SQL scenarios failed before correction; 15/15 passed after local equivalent patch.**
- No real patient record was used. A new GitHub fixture must independently execute the exact PR branch source and include malformed false reads, a separate guardian SQL failure, first/second query failure in export and retention.

## Minimal correction boundary
- In those five methods only, clear `$wpdb->last_error` directly before each get_results, preserve existing error code/behavior, check `!is_array($raw) || last_error !== ''`, and use safe array-only iteration.
- Retain privacy erasure retry state and legal holds; no migration, fee, payment, clinical record write, role/consent or plugin version updates.
- The same-suite native test, 40 whole-source repeats, PHP7.4/8.3, disposable three-WP-version MariaDB and deterministic ZIP required on final candidate HEAD.
- Two methodological source reviews are not independent human review. Hostinger Staging acceptance and Founder authorized release remain mandatory.

## Live truth
WordPress/Jetpack `site_disconnected`; WPVibe no linked site and HTTP530 previously; exact deployed package, checksum, active plugin, DB/schema/rows, wp_options migrations, logs and actual dependencies unavailable. **No production patch.**

**Count:** 1 further grouped source defect affecting five methods and eight SQL read paths; previously 18 scoped groups, this makes **19 scoped groups** if candidate correction passes, with 0 merged to main, 0 Staging confirmed and 0 Live-Resolved. Not a global defect count.

Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔
