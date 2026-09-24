# File 08 — T21 R9 Frozen Defect Ledger

Reviewed baseline: `7171c881025bafe670ddc96ea6a1e5a4ec9dc273`

Round discipline: the entire R9 review was completed read-only before this ledger was created. No R9 correction was applied during the review.

## Review scope

Fresh adversarial release/repository-hygiene review after T21 R8 closure, covering:

- canonical GitHub Actions trigger topology and exact-HEAD release evidence;
- single-workflow enforcement and repository-hygiene gates;
- candidate builder runtime allowlist/package boundary;
- historical correction-tool residue under active tool namespaces;
- closure-hygiene regression coverage against obsolete/self-mutating correction surfaces;
- separation of repository candidate evidence from staging/live truth.

## Frozen defects

### R9-D1 — Canonical workflow does not verify the post-merge `main` commit

`.github/workflows/file08-complete-quality.yml` runs on pull requests to `main`, but its `push.branches` list contains historical feature/review branches and omits `main`. A PR head can therefore be green while a later merge commit on `main` receives no automatic exact-commit canonical QA/package run. This conflicts with the project evidence rule that the exact source commit promoted toward release must have its own verification evidence.

Required correction: make `main` the canonical push target while preserving pull-request validation to `main`; remove historical branch-specific push triggers that are no longer current release surfaces. Add regression coverage.

### R9-D2 — Obsolete historical correction scripts remain in the active `tools/` namespace

Four historical one-shot correction scripts remain in `tools/`:

- `tools/t17-r2-correct.py`
- `tools/t17-r3-correct.py`
- `tools/t17-r7-correct.py`
- `tools/t17-r8-correct.py`

They are not part of the candidate runtime package and are not referenced by the canonical quality workflow. The existing closure-hygiene regression only rejects correction scripts under `.github/scripts`, so stale executable correction tooling can remain under `tools/` unnoticed. This contradicts the prior closure-hygiene rule that one-shot correction tooling must not remain as an active repository surface.

Required correction: retire these obsolete scripts from `tools/` and extend the permanent closure-hygiene gate to reject future `tools/t*-correct*.py` residue.

## Classification

R9 is **defect-bearing** with **2 proven repository/release-evidence defects**. No runtime/live defect is claimed by this ledger.

Correction may begin only after this frozen ledger commit.