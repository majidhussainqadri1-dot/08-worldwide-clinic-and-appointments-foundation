# File08 V12.1 — exact-source two-fix integration freeze, 2026-11-13

## Boundary and safety (before writing runtime code)
This document is an **immutable planning/evidence snapshot before combining two GitHub source candidates**. The Hostinger live site, active deployed plugin, DB schema, wp_options, migrations, logs and checksum are NOT accessible. WordPress.com/Jetpack reports `site_disconnected`; WPVibe has no linked site and the earlier authorization returned HTTP 530. Exact deployed code remains **unverified**; repository-based diagnosis for live defects is **provisional**. This branch does not patch production or assert Live-Resolved.

## Frozen exact source lineage
- Release baseline: `main d086840f7e2ac0ed328006385f064e0e2fb58cb1` (declares File08 plugin 1.2.16; WCA schema 3.4.0, legacy SWC schema 3.1.0).
- Correction A: draft PR #45, `58fcd1efc3473fffde7117a78a5ab695163e30e1`, CI #43370605005 4/4 PASS, artifact #14012666347. Six grouped FUT08 Heatmap source findings corrected.
- Correction B: draft PR #48, `41006e8bc506bb5e2caa3a6e3500019d8937ca04`, fail-first CI #43371971720 proved page-2 query error, corrected CI #43372209517 4/4 PASS, artifact #14012966910. One distinct paginated appointment-source finding corrected.
- The two candidates share one large runtime file `includes/class-wca-future24.php`; naive merging the whole file would silently regress one correction. The integration branch starts from **PR45 exact HEAD**, then selectively applies only PR48's private enumerator and FUT10/FUT17 error propagation, because PR45 already includes FUT08 propagation.

## Acceptance scope
1. No version bump, DB/schema changes, unrelated plugin edits, patient data, fees, clinical records, provider secrets or production configuration.
2. Source test must include **both** corrected native PHP fixtures; integration must fail when either set of safeguards is removed.
3. Compare entire code to PR45 for exact differences: only private enumerator and FUT10/FUT17 guards may change.
4. Verify both source fixtures and canonical whole-suite on PHP7.4 and PHP8.3; exact integrated HEAD 40 entire-suite rounds and deterministic build/hash.
5. Two fresh *post-integration* source reviews must consider interplay (FUT08 query & heatmap, FUT10 forecast, FUT17 disruption/transaction).
6. Real WordPress/MariaDB on ephemeral host with no production secrets; replace stub evidence only with actual detected runtime when available.
7. Hard stop at Draft PR: no merge into main until User/Founder release decision, owner contract review, WP staging, Hostinger deployed parity and live re-test. Old PRs #45/#48 remain distinct historical candidates and should not be closed without recorded supersession evidence.

## Non-goals / blockers
PR #44 File26 public search, #42 opt-in analytics, Issue #4 CF01 scope, Issue #47 GitHub unprotected main, incomplete old PR disposal, staging provider journeys, active user records and rollback are separate. Do not turn source QA green into operational acceptance.

## Required status columns
`Repository main HEAD | Integration candidate SHA | Deployed Version | DB Version | Migration State | Live Verification Status | Source findings 7 | Source corrected 7 (pending integration CI) | Staging accepted 0 | Live resolved 0 | Remaining blockers`.
