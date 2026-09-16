# T26 R10 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `56b5eb36ea371c433ac9a62523ca2b9367426d5d` (T26 R9 exact-head canonical run `35115636944` successful: PHP 7.4, PHP 8.3, aggregate source/JavaScript/repository-hygiene gates and reproducible independently verified candidate package all green).

## Mandatory discipline

R10 was completed as a full read-only release/currentness/closure review before this ledger was created. **No R10 correction was started while the review remained open.** All proven findings below were collected first; correction begins only after this frozen ledger.

## Review scope

Fresh read-only closure review covered:

- `README.md`, `STATUS.md`, `CHANGELOG.md`, packaged `readme.txt`, and `docs/RELEASE-STATUS-1.0.0.md` current-cycle/release identity;
- T26 R1–R9 frozen-ledger provenance and the current round-result truth;
- canonical runtime/schema/contract identity (runtime 1.2.15, core 3.4.0, continuity 1.1.0, Future24 1.1.0, Public Clinic 1.1.0, CF-01 1.1.0);
- canonical booking route `/appointments/book/{doctor_or_clinic}` and repository-vs-staging-vs-live evidence separation;
- historical T24/T25 release-currentness regressions and their current assertions;
- aggregate `tests/run-all.php` bindings;
- canonical GitHub Actions quality/package workflow;
- deterministic candidate builder and independent verifier, including exact-commit and Public Clinic runtime/canonical parity gates;
- package payload requirements, checksum/manifest/ZIP parity and no staging/live acceptance inflation.

## Frozen defect ledger

### R10-D1 — Current release/status surfaces still identify T25 as the active review cycle

T26 R1–R9 are already the active sequential review cycle, yet the following current release surfaces still identify **T25** as current and omit the current T26 round truth:

- `README.md`;
- `STATUS.md`;
- `CHANGELOG.md` current-candidate section;
- packaged `readme.txt`;
- `docs/RELEASE-STATUS-1.0.0.md`.

This is release-currentness drift. T25 must remain historical provenance, while current T26 truth must state: **R1 defect-bearing/corrected; R2–R9 clean; R10 defect-bearing/corrected at release-currentness/documentation-regression level.** Runtime remains 1.2.15 because this correction changes release evidence/currentness, not the public runtime contract.

**Required correction:** make T26 the current closure cycle on all current release/status surfaces, preserve T25/T24 as historical provenance, record the T26 R1 Public Clinic contract-parity hardening, preserve the canonical booking route and live/staging evidence boundary, and avoid hard-coding a future CI result into static source documentation.

### R10-D2 — Historical T25 R10 regression still enforces T25 as current

`tests/t25-r10-release-closure-regressions.php` still requires README, STATUS, release status, changelog and packaged readme to identify T25 as the current cycle. That historical regression now blocks truthful T26 currentness and incorrectly turns a past cycle's closure assertion into present release law.

**Required correction:** convert the T25 R10 regression to historical invariants (T25 ledger retained; T25 no longer current; canonical route/workflow/live-truth safeguards retained), add a permanent T26 R10 release-currentness regression that requires T26 current truth and the correct round results, and bind the new regression into `tests/run-all.php`.

## Clean boundaries confirmed during R10

- Canonical workflow still gates PHP 7.4 and PHP 8.3, aggregate source tests, JavaScript syntax, repository hygiene, deterministic double-build comparison, independent candidate verification and artifact upload.
- Candidate builder/verifier still bind exact 40-hex source commit, runtime, plan, core/continuity/Future24/Public-Clinic/CF-01 contract/schema identity, payload manifest and SHA-256; Public Clinic runtime/canonical parity remains fail-closed after T26 R1.
- T24 currentness regression is already historical rather than asserting T24 as current.
- Canonical booking route remains `/appointments/book/{doctor_or_clinic}`.
- Staging-Accepted, Live-Deployed and Operational are not established by repository evidence.
- No new runtime/application-code defect was proven in R10 beyond the two release-currentness/regression defects above.

## Evidence boundary

Repository/source candidate evidence only. R10 does not establish the plugin files/version, DB/schema/migration state, active dependencies/configuration, checksum or runtime behavior actually deployed on staging or production.

## Correction gate

Only after this frozen ledger may R10-D1 and R10-D2 be corrected. The corrected exact HEAD must pass canonical PHP 7.4/PHP 8.3 source/JavaScript/hygiene gates plus reproducible independently verified candidate packaging before the T26 ten-round cycle is closed.
