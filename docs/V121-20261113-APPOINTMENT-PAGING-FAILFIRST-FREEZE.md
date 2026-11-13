# File08 V12.1 freeze: incomplete paginated appointment set — 2026-11-13

## Independent source baseline and status
- `main` exact `d086840f7e2ac0ed328006385f064e0e2fb58cb1` (plugin declared 1.2.16).
- This is repository-only work, **not a live incident**; Hostinger's exact deployed plugin/DB/migrations/logs/backup remain unverified (Jetpack disconnected, WPVibe HTTP 530).
- Separate PR #45 addresses FUT08 capacity calculations and MUST NOT be bulk-merged or used as this branch's source. This branch is independently based on original main and addresses distinct Issue #46.

## Frozen proven source defect
`includes/class-wca-future24.php::clinic_appointments_between_all` loops `WP_Query` pages, casts `(array) $q->posts`, then unconditionally returns a successful list. Absent guards for SQL failure (`$wpdb->last_error`), malformed `posts`, zero/invalid post IDs and an excessive page count permit incomplete/invalid enumeration to be reported as complete. A later page error can turn nonempty successful first pages into an incomplete but ostensibly successful result.

Affected callers in original main:
- `heatmap` FUT08 treats returned IDs as actual counts without validating `WP_Error`.
- `no_show_forecast` FUT10 computes misleading rates and suppression from partial results.
- `create_disruption` FUT17 could silently omit affected appointments before recording a disruption.

## Scope and fail-first acceptance
1. First register actual-method PHP fixture on this exact main (positive: empty list; two valid pages; negative: a failed later page; invalid posts; illegal ID; 250-page bound).
2. After fail-first evidence, amend only enumerator and three direct callers. Correctly return `WP_Error` 503 for uncertain/oversize data, no false zero-rate, no transactional disruption on unknown affected set.
3. Follow real source WordPress contract: `WP_Query` can have `posts=[]` and $wpdb->last_error set on SQL failure.
4. Avoid changing clinic ownership, booking, consent, payment, schema, persistence, provider delivery or other unrelated code.
5. Exact final HEAD PHP7.4/PHP8.3 full suites, 40 rounds, deterministic candidate; two post-runtime source reviews.
6. Real WordPress/MariaDB role journeys and staging must establish correctness before promotion; production deploy requires Founder consent and live parity.

## Snapshot
Repository main above; Deployed version unknown; DB version unknown; Migration state unknown; Live verification blocked; source defect one independent family; source fix not yet coded at freeze.
