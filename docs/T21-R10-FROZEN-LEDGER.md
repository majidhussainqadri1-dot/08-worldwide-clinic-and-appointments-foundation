# File 08 — T21 R10 Frozen Defect Ledger

Reviewed baseline: `312dcf66e88528d9c52125f490e79a57729e3a2f`

Round discipline: the entire R10 review was completed read-only before this ledger was created. No R10 correction was applied during the review.

## Review scope

Fresh final integrative review for the T21 ten-round batch, covering exact-head canonical workflow/package evidence, deterministic candidate builder/verifier boundaries, required runtime payload completeness, current repository/release identity documentation, PR governing evidence, repository hygiene, and strict separation of repository candidate truth from staging/live/DB/migration truth.

## Frozen defects

### R10-D1 — Candidate packaging does not fail closed when required root payload files disappear

`tools/build-candidate.php` listed `worldwide-clinic.php`, `readme.txt`, and `uninstall.php` as allowed root files but silently included each only when present. `worldwide-clinic.php` was indirectly required by runtime-version extraction, while `readme.txt` and `uninstall.php` could disappear and the builder could still produce a candidate. `tools/verify-candidate.php` verified manifest/ZIP parity and several runtime contract sources but did not require `readme.txt` or `uninstall.php` to exist in the manifest/ZIP.

Impact: an accidentally incomplete candidate could pass canonical reproducibility/verification despite omitting the safe-uninstall surface or release readme. This was a repository/package-integrity defect; it was not evidence that any deployed/live package was missing these files.

Correction: the builder now fails closed if any mandatory root payload is absent; the independent verifier requires the same files in the candidate manifest/ZIP; permanent regression coverage was added and bound into the canonical suite.

### R10-D2 — Governing PR evidence was stale after R8/R9 completion

PR #10 described R8 as exact-head retest in progress and said R9 must not begin, while R8 exact-head run `34366620911` had succeeded and R9 was subsequently reviewed, frozen, corrected, and exact-head run `34372325133` succeeded on `312dcf66e88528d9c52125f490e79a57729e3a2f`.

Impact: the governing progress surface contradicted current repository/CI evidence. This was evidence/documentation drift only; no runtime/live defect was claimed.

Correction: PR #10 is to carry the complete T21 result and final external exact-head evidence after the branch head containing this closure note passes canonical QA.

## Correction verification before closure-note commit

The R10 implementation/test correction candidate `7ac12c25a1898217e03eb6c1121ce53f0dbe528b` passed canonical run `34373622092`: PHP 7.4/source contracts, PHP 8.3/source contracts, JavaScript syntax, repository hygiene, deterministic double-build, independent candidate verification and artifact upload all passed.

Because this closure ledger itself is tracked repository content, final T21 exact-head evidence must be taken from the canonical run on the branch head that contains this note. That final SHA/run is recorded on PR #10 rather than embedded here, avoiding a self-referential commit identity.

## T21 ten-round result

- R1 — defect-bearing (4)
- R2 — defect-bearing (2)
- R3 — defect-bearing (1)
- R4 — defect-bearing (1)
- R5 — defect-bearing (1)
- R6 — defect-bearing (1)
- R7 — clean
- R8 — clean
- R9 — defect-bearing (2)
- R10 — defect-bearing (2)

Clean rounds: **2/10 = 20%**. The project stopping threshold requires **more than 70% clean**, i.e. at least 8/10 clean rounds, so T21 does not meet the stopping condition and a fresh subsequent ten-round cycle is required.

## Classification

R10 is **defect-bearing with both frozen defects corrected**. Formal exact-head closure is established only by the canonical QA run on the final branch head containing this ledger note. T21 is repository/source review evidence only.

Staging acceptance, deployed/live parity, production DB/schema, executed migration state and live behavior remain separate unverified evidence domains.