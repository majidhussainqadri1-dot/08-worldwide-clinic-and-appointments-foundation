# T40-D03 — post-freeze native regression discovery: undefined clock helper

**New finding recorded before correction** — discovered only when the new T40 FUT22 native PHP fixture exercised the resolved-practitioner/no-busy-calendar path. Earlier 40 plan-source screens were frozen separately in `T40-FROZEN-FORTY-CROSSPLAN-REVIEWS-2026-10-16.md`; do not backdate this finding into that initial count.

- Exact candidate before D03 correction: `7cb71f8b9193a33f72833e9677cea803b5ab8dc0`.
- GitHub Actions exact-head PHP7.4 run **38937377550**, job **117096378437** fails the native regression with `Fatal error: Call to undefined method WCA_Future24::now()`, `includes/class-wca-future24.php`, line ~2143.
- A complete source text search reveals exactly **one** `self::now()` call in this class and no `now()` declaration in `WCA_Future24`. The canonical `WCA_Repository::now()` is used throughout this plugin for persisted operational UTC timestamps.
- Root cause: `external_busy_conflict()` uses the nonexistent `self::now()` for its `expires_at>%s` SQL condition after successful practitioner resolution. This creates a fatal rather than a controlled free/busy/error projection.
- Correction: replace only the undefined clock call with `WCA_Repository::now()`; preserve all query predicates, File09 canonical identity failure and SQL error checks. Re-run T40 native fixture and entire PHP7.4/8.3 + reproducible package gates. No claim of live occurrence or installed build parity.

**Status at freeze:** 3 repository-source defects identified in T40 (2 during 40-scope source review + 1 executable regression discovery); first two code fixes committed, D03 pending source edit. Live deployed version, DB schema/migration and actual external provider event reconciliation remain unverified.
