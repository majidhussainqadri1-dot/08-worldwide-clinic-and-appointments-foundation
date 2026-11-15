# File08 V12.3 — 44-PR snapshot addendum, after audit-history corrections (2026-11-15)

This addendum updates the earlier `docs/V123-20261115-42-PR-EXACT-SHA-DECISION-LEDGER.md`, which remains a valid earlier frozen 42-item snapshot. **Current GitHub query returns 44 open PRs**; the increase is exactly two new Draft source candidates, #64 and #65. No PR was merged/closed for release; historical existing PRs keep their separate dispositions.

| New PR | Exact frozen source HEAD | Source scope | Decision |
|---|---|---|---|
| #64 | `1c259b101bfa3ace78e3018b25f1f8c26e069814` | Strict legacy audit reads: SQL error []/malformed row must not create false empty personal audit export | Direct draft ancestor; preserve original test; not merged/Live |
| #65 | Final exact SHA from latest GitHub PR head | Restricted admin audit timeline: must show unavailable rather than fabricated zero events during strict reader failure | Primary cumulative V12.3 candidate; require full exact SHA CI, 2 independent humans, true Hostinger staging |

## Current lineage and action

#65 is directly descended from #64, #63, #62, #61, #60, #58, #57, #56, #54, #53, #51, #49. Prove semantic carry-forward of parallel #50 booking race and #45/#48 frozen negative tests prior to human signoff; this commit history does not prove all V12.3 features accepted. 26 historical PRs #12–#38 remain diverged and require per-PR semantic review. #44 File26 cross-owner discovery approval and #42 analytics consent hold remain separate.

**Counting:** previous latest corrected source defect register 23 at #63; +1 strict legacy audit export (#64) +1 administrator observability truth (#65) = **25 narrowly scoped source defect groups**, pending final candidate CI. Main merge 0, staging accepted 0, live resolved 0. This is not a census of the total File08 defects.

**Release gates:** 54 requirements need actual integrated WordPress journeys, 12 Future24 P0, 11 P1, 1 P2 require correct activated/disabled state, real guardian/consent, File14/17/19/20/25/26/CF01/CF03, WCAG/RTL, concurrent slots, migration rollback and backup/restore. GitHub Actions' disposable CI is not actual Hostinger stage. No deployment without evidence and Founder Go.

**Current source main SHA** `d086840f7e2ac0ed328006385f064e0e2fb58cb1`. Hostinger deployed version, DB/schema, wp_options migration, checksums, logs and Live test all unverified. Jetpack `site_disconnected`, WPVibe no site paired (previous HTTP530). No root-cause inference from connectivity. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**