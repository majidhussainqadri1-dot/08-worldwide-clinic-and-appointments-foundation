# File08 V12.1 — original PR disposition and safe dual-fix lineage (2026-11-13)

## Preservation of source truth
The original 30 open Draft PRs were enumerated against `main d086840f7e2ac0ed328006385f064e0e2fb58cb1`. Three additional Drafts #45 (Heatmap), #48 (Appointment Paging) and #49 (new combined acceptance candidate) were opened in this task sequence. The original source register `V121-20261113-OPEN-PR-TRIAGE-REGISTER.md` remains a **historical snapshot**, not a claim that the old 30 are still open.

## Two fully evidenced historical closures — no merges
| Original PR | Exact HEAD | Compare to main | Evidence | Disposition |
|---|---|---|---|---|
| #2 independent 0.1.0 source audit | `24901a7caace1781037f7899b63176729465dfa0` | ahead 0, behind 1348, **0 changed files** | Source audit report retained, GitHub comment #6845825810 | **Closed, unmerged**, historic findings preserved |
| #8 T19 twentieth-round ledger | `99a5d89438aaf923ccc7e9fa740c6133263a2c5c` | ahead 2, behind 332, **0 changed files** | Historic QA process/report preserved, GitHub comment #6845826300 | **Closed, unmerged**, historic findings preserved |

The original 28 other PRs remain unresolved and **may not be blanket-closed**; each must be tested against exact `main` for necessary functional deltas. Closures do not represent defect resolution or deployment.

## Current candidate lineage
| PR | Baseline | Corrective scope | Fresh independent source QA | Production |
|---|---|---|---|---|
| #45 | main `d086840f...` | six grouped FUT08 Heatmap findings, canonical DST/invalid intervals | exact HEAD `58fcd1ef...`, run #43370605005 4/4 green | Draft, not merged, not deployed |
| #48 | same main | page 2 query failure, invalid IDs, 250-page bound, FUT08/10/17 error propagation | exact HEAD `41006e8b...`, run #43372209517 4/4 green | Draft, not merged, not deployed |
| #49 | starts from #45; surgically adopts #48's **private** enumerator and two needed callers only | both source defect families in a **single** runtime file, both native regressions | source+native exact integrated HEAD to be checked; no previous green run proves this final SHA | Draft, not merged, not deployed |

## Disposable WordPress/MariaDB CI evidence
- A real six-plugin WordPress **7.1.4** runtime passed 26 native checks on `b212003466526de619bf9649d0ae270dd1809c00`, GitHub Actions #43378274502 native job.
- Correct File00's protected deferred `admin_init` migration; configure valid ephemeral File09 keyring, signing key and storage outside public web root, preserving required safety controls.
- Follow-up branch adds pinned WordPress **7.0.8 and 7.1.4** matrix, exact deterministic ZIP installation and read-only audit utility; these later changes require a **fresh final-head** green run.
- This is **disposable CI**, not Hostinger staging nor a tested clinical booking journey.

## Live evidence not obtained
WordPress.com lists `site_disconnected` for Jetpack-managed `www.sabrihomeopathy.com`; WPVibe has no registered connection, earlier HTTP530 is not enough to identify cause. No deployed checksum, `wp_options`, schema, tables/rows, logs or active provider health is verified. This blocks diagnosis of a Live defect, deploy, rollback and Live Resolved.

## Operator handoff
`tools/file08-live-reality-freeze.php` is a **read-only WP-CLI script in source, not in the deterministic plugin ZIP**, invoked only by a trusted host operator. Before using it on any site, obtain privileged access, run it locally on the host, store evidence privately and redact all sensitive output. It cannot establish Live parity by itself; compare its installed-tree digest and version/schema evidence with the approved candidate and verify logs/migrations independently.

## Open remediation and governance
- Issue #4 CF01 relationship/medical-record scope.
- Issue #46 source paging is corrected in Draft #48/#49, but **Live issue remains unverified**.
- Issue #47 main branch protection currently reports `protected=false`; admin-required checks and merge approvals are not yet configured.
- Original 28 PRs need individual dispositions, File26 #44 owner contract and #42 patient-consent policy hold remain.
- All gates P6/P7/P8/P9 require secure real staging, role-based E2E, concurrency, privacy/retention/rollback, Founder authorization, controlled deployment and actual Live re-test.

**Mandatory truth:** Source-corrected ≠ merged ≠ staged ≠ deployed ≠ Live-resolved.
