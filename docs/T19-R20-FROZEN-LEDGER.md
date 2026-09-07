# File 08 — T19 R20 Final Adversarial/Closure Review — Frozen Defect Ledger

Review scope: final exact-tree adversarial closure, workflow mutation surface, repository hygiene, candidate/release tooling, package identity, current-status documentation, staging handoff truth, PR truth, and historical-evidence separation.

Review discipline: the entire R20 review was completed against the exact reviewed HEAD below before this ledger was frozen. No R20 production/release-hygiene correction was applied while the review remained open.

Exact reviewed HEAD: `347dc52826ec0a72b15d3b24867267e42464418e`.

## Frozen defects

1. **R20-D1 — Obsolete mutation/duplicate workflows remain active.** `.github/workflows/t19-round-fix.yml` is still a `contents: write` self-mutating correction runner, and `.github/workflows/t19-r1-regression.yml` duplicates the canonical full quality suite. The current release branch should have one read-only canonical QA workflow after correction work is closed.
2. **R20-D2 — Historical correction scripts remain in the active `.github/scripts` surface.** T17/T18 correction programs plus `t19-round-fix.py`/`t19-round-fix-v2.py` are inert historical mutation machinery, not current runtime or canonical QA dependencies. Keeping them in the active CI namespace increases ambiguity and accidental-mutation surface.
3. **R20-D3 — A stale alternate candidate builder/verifier can create an incomplete, obsolete package.** `tools/build-development-candidate.php` packages only the old SWC subset and hard-codes Public Clinic/CF01 contract `1.0.0`; `tools/verify-development-candidate.php` verifies that obsolete artifact family. The canonical builder/verifier now bind runtime 1.2.15 and the current schema/contract set, so the obsolete pair is unsafe as a competing release path.
4. **R20-D4 — Current status/release documents are materially stale.** `STATUS.md` still says R3 is current and labels later T19 closure as pending; `docs/RELEASE-STATUS-1.0.0.md` still says the source is a reviewable candidate under T19 sequential audit. They no longer describe the completed R1–R20 review sequence.
5. **R20-D5 — Current 1.2.15 changelog/readme release truth is inconsistent with source contracts.** `CHANGELOG.md` states core schema `3.2.0` and Future24 `1.0.0` in the current 1.2.15 section while source contracts are core `3.4.0` and Future24 `1.1.0`; `readme.txt` describes earlier audit cycles but does not accurately record the current T19 closure/corrected schema state.
6. **R20-D6 — The top-level staging handoff checklist carries stale schema truth.** `STAGING-ACCEPTANCE.md` instructs operators to verify core `3.2.0` and Future24 `1.0.0`, which can cause a valid current artifact/database to be judged against obsolete migration targets. Current source contracts are core `3.4.0`, continuity `1.1.0`, Future24 `1.1.0`.
7. **R20-D7 — PR #8 description is stale.** It stops at the R18 frozen ledger and says R18 correction may begin, even though R18 and R19 corrections/QA have already closed and R20 is the final review. PR truth must not lag the candidate it represents.
8. **R20-D8 — Historical checksum/audit evidence is insufficiently separated from current release truth.** Root `CHECKSUMS.sha256` and `CORRECTIVE-CHECKSUMS.sha256` contain historical hashes that do not describe the current tree, while `AUDIT-EVIDENCE.md` says “the current helper” has defects that belong to the original 0.1.0 baseline. Historical evidence must remain available without presenting a plausible current release checksum or current-source diagnosis.
9. **R20-D9 — Final closure hygiene lacks a permanent anti-regression gate.** The canonical repository-hygiene step checks secrets/symlinks but does not enforce a single canonical workflow, absence of executable historical correction scripts/obsolete development candidate tooling, absence of ambiguous root checksum files, current T19 status truth, or ignored local build output. A permanent R20 closure-hygiene gate is required.

## Correction gate

All nine frozen defects must be corrected only after this ledger freeze. The correction batch must then pass PHP syntax, the complete warning-clean source suite, JavaScript syntax, canonical PHP 7.4/PHP 8.3 jobs, deterministic double-build byte comparison, independent candidate verification and artifact upload on the exact corrected HEAD. Because the staging checklist separately requires two fresh post-final-runtime-code verification sweeps, those sweeps remain a closure gate after R20 correction; they are not counted as additional numbered R1–R20 reviews.
