# File 08 — T21 R10 Frozen Defect Ledger

Reviewed baseline: `312dcf66e88528d9c52125f490e79a57729e3a2f`

Round discipline: the entire R10 review was completed read-only before this ledger was created. No R10 correction was applied during the review.

## Review scope

Fresh final integrative review for the T21 ten-round batch, covering exact-head canonical workflow/package evidence, deterministic candidate builder/verifier boundaries, required runtime payload completeness, current repository/release identity documentation, PR governing evidence, repository hygiene, and strict separation of repository candidate truth from staging/live/DB/migration truth.

## Frozen defects

### R10-D1 — Candidate packaging does not fail closed when required root payload files disappear

`tools/build-candidate.php` lists `worldwide-clinic.php`, `readme.txt`, and `uninstall.php` as allowed root files but silently includes each only when present. `worldwide-clinic.php` is indirectly required by runtime-version extraction, while `readme.txt` and `uninstall.php` can disappear and the builder can still produce a candidate. `tools/verify-candidate.php` verifies manifest/ZIP parity and several runtime contract sources but does not require `readme.txt` or `uninstall.php` to exist in the manifest/ZIP.

Impact: an accidentally incomplete candidate can pass canonical reproducibility/verification despite omitting the safe-uninstall surface or release readme. This is a repository/package-integrity defect; it is not evidence that any deployed/live package is missing these files.

Required correction: make all three root payload files mandatory in the builder, make the independent verifier require them in the manifest/ZIP, and add permanent regression coverage.

### R10-D2 — Governing PR evidence is stale after R8/R9 completion

PR #10 still describes R8 as exact-head retest in progress and says R9 must not begin, while R8 exact-head run `34366620911` succeeded and R9 was subsequently reviewed, frozen, corrected, and exact-head run `34372325133` succeeded on `312dcf66e88528d9c52125f490e79a57729e3a2f`.

Impact: the governing progress surface contradicts current repository/CI evidence. This is evidence/documentation drift only; no runtime/live defect is claimed.

Required correction: update PR #10 after R10 correction/verification so R8 and R9 are closed and R10 status is exact.

## Classification

R10 is **defect-bearing** with **2 proven defects**: one repository/package-integrity defect and one governing-evidence defect.

Correction may begin only after this frozen ledger commit.