# T25 R10 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `db908c37ecc1d4235ec5daab78c1cddb55feb016` (T25 R9 corrected exact-head canonical run `35060761425` successful: PHP 7.4, PHP 8.3, aggregate source/JS/hygiene gates and reproducible independently verified candidate package all green).

## Mandatory discipline

R10 was completed as a full read-only closure/release-truth review before this ledger was created. No R10 correction was started while the review remained open. All proven findings below were collected first; correction starts only after this frozen ledger.

## Review scope

Current-cycle/release identity across `README.md`, `STATUS.md`, `CHANGELOG.md`, packaged `readme.txt` and `docs/RELEASE-STATUS-1.0.0.md`; runtime/schema/contract parity; canonical-route documentation; deterministic candidate builder/verifier; aggregate regression binding; workflow PHP 7.4/8.3 + JS/hygiene + deterministic double-build/independent verification; T25 R1–R9 frozen-ledger/correction evidence; repository-vs-main-vs-staging/live truth boundaries.

## Frozen defect ledger

### R10-D1 — Repository release/currentness documents still identify T24 as the current cycle

`README.md`, `STATUS.md`, `CHANGELOG.md`, and `docs/RELEASE-STATUS-1.0.0.md` still state that T24 is the current/active closure cycle even though T25 R1–R9 have been completed and R9 corrected exact HEAD is already green. The historical `tests/t24-r9-release-currentness-regressions.php` also still positively requires T24 to be current, so the regression suite now protects stale release truth instead of historical truth.

The packaged `readme.txt` correctly identifies T25 as the current sequence, but it only records the opening T25 baseline and does not summarize the current T25 round state. This creates contradictory release surfaces between the packaged candidate and repository status documents.

**Required correction:** make T25 the current repository closure cycle everywhere current status is stated; summarize T25 R1–R10 truth without hard-coding a future exact-head success; convert the T24 regression from a current-cycle assertion into a historical-provenance assertion; add a T25 R10 permanent currentness regression and bind it into the aggregate runner.

### R10-D2 — README canonical booking route narrows the governing route contract incorrectly

The governing File 08 plan and `WCA_Contracts::routes()` define the booking route as `/appointments/book/{doctor_or_clinic}`. The current frontend legitimately emits a clinic public reference for this route. `README.md`, however, documents `/appointments/book/{opaque_practitioner_ref}` only, which is narrower than both the governing plan and implemented route semantics.

**Required correction:** document the canonical route as `/appointments/book/{doctor_or_clinic}` (with opaque external reference semantics where applicable) so release documentation matches the governing plan and runtime contract.

## Clean closure boundaries confirmed during R10

- Runtime/plugin contract remains 1.2.15; core schema 3.4.0; continuity and Future24 schema/contracts 1.1.0; Public Clinic and CF-01 contracts 1.1.0.
- Candidate builder derives runtime/contract/schema truth from source, requires exact 40-hex commit/source epoch, emits a deterministic manifest and marks staging/production acceptance false.
- Independent verifier checks checksum, exact commit, manifest/source parity, required payloads, duplicate/path safety and runtime/schema/contract identity.
- Canonical workflow runs PHP 7.4 and 8.3 source contracts, JS syntax, repository hygiene, deterministic double build, independent verification and artifact upload.
- T25 R1/R8/R9 permanent regressions are currently bound into `tests/run-all.php`; R2–R7 were clean review rounds.
- Repository evidence does not claim staging acceptance, live deployment or operational status.

## Evidence boundary

Repository/source evidence only. `main` remains a separate repository reality from this review branch, and neither repository branch proves staging/live deployment or database state.

## Correction gate

R10-D1 and R10-D2 must be corrected only after this ledger freeze. The final corrected HEAD must pass the canonical PHP 7.4/PHP 8.3, aggregate source/JS/hygiene and reproducible candidate-package verification before T25 can be closed.
