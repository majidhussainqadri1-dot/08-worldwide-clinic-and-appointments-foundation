# V12.1 File08 exact-HEAD source divergence freeze — 2026-11-13

## Evidence boundary (MANDATORY)
This is **repository-only** research. The live Hostinger site is NOT accessible for parity verification: the WordPress/Jetpack connector reports `site_disconnected`, and a WPVibe authorization/info attempt returned HTTP 530. The failed HTTP attempt does not identify a root cause, Cloudflare ownership or any live code version. **Exact deployed code is unverified; any repository diagnosis is provisional for production.** This dossier does not authorize production changes or declare live defects resolved.

## Frozen verified source
- Repository: `majidhussainqadri1-dot/08-worldwide-clinic-and-appointments-foundation`.
- Baseline `main`: `d086840f7e2ac0ed328006385f064e0e2fb58cb1`, plugin 1.2.16; declared core schema 3.4.0. Note the *legacy* `SWC_Activator::DB_VERSION` is separately 3.1.0, not the canonical WCA schema.
- Exact-baseline Actions run: `42384319821`, four jobs marked successful (PHP 7.4, PHP 8.3, 40 whole-source-suite passes, candidate build).
- Main `includes/class-wca-future24.php` SHA `08b408d427686683286583c86a76d380373cbf18`, inspected directly; PR38 candidate `16d4ee922149fd7e461a9ef7f73d670c5f704712` is an **unmerged and heavily diverged** branch, not safe to merge wholesale.
- PR38 vs main: 219 commits ahead / 14 behind, 88 changed files; cherry-pick only proven, isolated fixes after fresh-head tests. PR44 is only 2 commits ahead / 0 behind, but discovery integration still needs File26 owner approval and staging verification.

## Fresh-main confirmed source divergences (not asserted as live failures)
| ID | Main source behavior | Risk and exact acceptance |
|---|---|---|
| SD-01 | Heatmap accepts `get_results() === []` despite `$wpdb->last_error` nonempty | A failed rules query can be rendered as zero capacity. After fix, corrupt SQL read returns a controlled 503; legitimate no-rules returns zero |
| SD-02 | Matched-day malformed capacity exception is silently ignored | Stale or corrupt capacity silently presents base capacity. After fix, reject malformed stored override |
| SD-03 | Bounded `closed` exception closes the **whole** day | Projection can understate availability; compare interval overlaps |
| SD-04 | Explicit `open` exception on an otherwise closed weekday is ignored | Projection can understate bookable slots; explicit open is evaluated before weekday skip |
| SD-05 | `while ($slot < $end)` counts a candidate whose duration ends beyond closing; interval overlaps a break only if the **start** lies inside break | Projection can overstate capacity; require complete slot and check full overlap |
| SD-06 | Invalid `open` exception bounds silently fall back to base; DST nonexistent/ambiguous wall times are not validated against canonical `SWC_Helpers::to_utc()` | Projection can appear healthy with invalid local times; reject invalid stored bounds and skip unbookable DST candidates |

Source locations: main `WCA_Future24::heatmap` and `SWC_Helpers::to_utc`; prior unmerged PR35–38 include recorded fail-first tests. Findings are structural/code-path proofs against `main` only; no claim of user-visible live symptoms.

## Frozen corrective scope
This branch may backport **only** the self-contained `WCA_Future24::heatmap` and `heatmap_interval_overlaps` behavior from the previously reviewed T44–T47 branch, leaving other module functions and schemas alone. Preserve current main identity/doctor/File09, MariaDB and WordPress-init fixes. Add a real PHP fixture exercising actual native method behavior, invoke it via `tests/run-all.php`, run canonical exact-head PHP7.4/8.3 and 40 rounds. Do not merge until two fresh post-final-change source reviews, controlled staging, and explicit promotion decision under governing master plan.

## Open PR reconciliation — do not bulk-merge
- `#44`: separate File08→File26 public clinic projection candidate; user/doctor/patient/slot privacy controls + cross-repository owner acceptance needed.
- `#42`: opt-in aggregate *proposal*; no analytics data collection is authorized. Remains on policy and consent-ledger hold.
- `#27–38`: old chained T36–T47 safety work. Many changes share the same historical merge-base and carry unrelated code; extract isolated deltas onto current `main` only after proof. Do not mark them integrated solely from green historical CI.
- `#12–26, #8, #2`: older competing reviews and corrective candidates. Triage exact file diffs and current presence, close as historical only after evidence and repository governance decision; do not equate an open PR with an active independent bug.
- Issue `#4`: CF-01 care-context and chart extraction governed separately; no patient-table migration or chart authorization follows from a scheduling appointment.

## Acceptance blockers — not passed
1. Exact Hostinger deployed plugin version/hash and dependent plugin versions.
2. Live/staging DB, core/legacy schema, migration `wp_options`, relevant rows, logs.
3. Current active PHP/WP configuration, timezone, caching and provider credentials/health.
4. Controlled real WordPress+MariaDB staging: install/upgrade/rollback, doctor/patient/guardian journeys, concurrent last-slot and idempotent booking replay, protected REST/cache, DST and privacy/erasure.
5. Founder approval, production deployment, live re-test and deployed parity confirmation.

## Required end-state report columns
`Repository HEAD | Deployed Version | DB Version | Migration State | Live Verification Status | New Source Defects | Source-Corrected | Staging-Corrected | Live-Resolved | Blocked`.

**No live bug patch, merge-to-production, or claim of full plan completion is authorized by this frozen source-only ledger.**
