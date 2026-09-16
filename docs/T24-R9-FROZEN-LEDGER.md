# T24 R9 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `db6d09fb53a9b7e946624431e4410f379687fa80`.

## Discipline

R9 was completed as the final full read-only release-truth / package-parity / documentation-currentness review before this ledger was created. No R9 correction was applied while the review remained open.

## Review scope

Runtime/plugin/schema/contract identity; main loader completeness; deterministic candidate build; exact-commit manifest binding; independent ZIP/checksum/payload verification; CI matrix and repository hygiene; test aggregation; required package payload; staging/live non-claim law; README/STATUS/release-status/changelog currentness; historical-vs-current evidence labeling; and plan/repository/deployment truth separation.

## Frozen defect ledger

### R9-D1 — Release-status documentation and its regression still freeze the obsolete T22 cycle as “current”

The runtime/package tooling itself is exact-head aware, but `README.md`, `STATUS.md`, `docs/RELEASE-STATUS-1.0.0.md`, and the current-candidate section of `CHANGELOG.md` still identify **T22** (through its old R9 exact head/run) as the current review cycle. The active branch has subsequently completed T23 and T24 ledgers, including the present ten-round continuation. `tests/t22-r10-release-currentness-regressions.php` reinforces that stale statement by requiring T22 to remain current. This is repository release-truth drift: old CI evidence is correctly historical after later commits and must not be advertised as the current candidate state.

## Reviewed non-defects

- `worldwide-clinic.php` binds plugin header and `WCA_VERSION` at 1.2.15 and loads the current canonical runtime files.
- Candidate build derives runtime/schema/contract versions from source, binds the exact 40-hex commit and source-date epoch, sets staging/production acceptance false, and includes only bounded runtime payload roots.
- Independent verification checks checksum, exact manifest commit, runtime/plan/schema/contract parity, duplicate/path safety, required payloads and no unmanifested ZIP entries.
- Canonical workflow runs PHP 7.4 and 8.3 syntax/source gates, JavaScript syntax, repository hygiene, deterministic double-build byte comparison and independent candidate verification.
- `tests/run-all.php` includes the T24 R1 uncertainty regression and rejects PHP warnings/notices/deprecations/fatal/parse diagnostics.

## Correction gate

Only after this ledger freeze:
1. Update README, STATUS, release-status and current changelog text so T24 is the active closure cycle and T22/T23 are historical provenance.
2. Remove the old T22 exact-head/run from any “current” status statement.
3. Convert the historical T22 currentness regression so it no longer requires T22 to be current, and add a permanent T24 release-currentness regression.
4. Bind the new regression into `tests/run-all.php`.
5. Run canonical PHP 7.4/PHP 8.3, full source/JS/hygiene and reproducible candidate verification on the corrected exact HEAD.

## Evidence boundary

Repository/source/package evidence only. No source document or green CI may be promoted to staging/live/deployed/operational evidence without exact deployed artifact, DB/schema/migration and live re-test evidence.
