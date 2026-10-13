# Release Status — File 08 — document version 1.0.0

The `1.0.0` in this filename is the release-status document version; it is **not** the current plugin runtime.

## Current repository identity

- Runtime candidate: **1.2.15**
- Core schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 schema/contract: **1.1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Plan: **SSH-F08-PLAN-2026-v1.0**
- Current review branch: `review/file08-t33-40-review-prerequisite-policy-conflicts-2026-10-13`
- Current review cycle: **T26 closure cycle**
- Current T26 ten-round cycle: **R1 defect-bearing/corrected; R2–R9 clean; R10 defect-bearing/corrected at repository release-currentness/regression level.**
- Historical T25, T22 and T21: closed; their recorded exact heads/runs are provenance only after later commits.

## Repository-scope completion

T26 is the active closure sequence and follows the mandatory Review → Ledger Freeze → Fix → Regression → Exact-head CI/package discipline. Exact numbered-round closure is tracked in PR #10 and frozen ledgers because a static status document cannot truthfully embed a future CI result that occurs only after the commit containing the document itself.

Implemented repository scope includes 18/18 FR, 10/10 NFR and Future24 24/24 capability governance; canonical data/schema and migration/rollback/recovery controls; security/privacy/reliability/accessibility/localization/observability; warning-clean source regression aggregation; and deterministic candidate engineering whose manifest binds exact commit, runtime, plan and core/continuity/Future24/Public-Clinic/CF-01 schema-contract identity.

Current T26 review evidence re-examines governing contract/release parity, API authorization, scheduling/concurrency, finance, privacy, calendar/outbox, Future24 interoperability, frontend/localization, schema/migration/operability and final release currentness. R1 was defect-bearing and corrected after its ledger was frozen; R2–R9 were clean; R10 was defect-bearing at release-currentness/regression level and corrected only after its frozen ledger. T26 R1 specifically aligned the Public Clinic runtime contract with canonical 1.1.0 and made both builder and independent verifier fail closed on Public Clinic contract mismatch. Runtime remains 1.2.15 because these corrections do not change the public runtime version contract.

## Evidence-state classification

- Source implementation: **CODED CANDIDATE — T26 CLOSURE CYCLE**
- Automated source checks: **EXACT-HEAD ONLY — consult PR #10/current canonical workflow for the exact HEAD under evaluation**
- Deterministic candidate: **EXACT-HEAD ONLY — consult PR #10/current canonical workflow for the exact HEAD under evaluation**
- Earlier T22/T23/T24/T25 exact-head CI/package evidence: **HISTORICAL after later commits**
- Hostinger staging acceptance: **NO / NOT CLAIMED**
- Founder staging acceptance: **NO / NOT CLAIMED**
- Production release/deployment: **NO / UNVERIFIED**
- Operational status: **NO / NOT CLAIMED**

A correction changes HEAD and makes prior CI/package evidence historical for final-release purposes. Packaged/Automated-QA Green status therefore applies only when the exact HEAD under consideration itself passes the canonical PHP 7.4/PHP 8.3 quality jobs and reproducible candidate verification. This static file deliberately does not hard-code a future CI success.

## Historical review provenance

T19, T20, T21, T22, T23, T24 and T25 remain historical provenance; T26 frozen ledgers are the current ten-round review record. Historical T22 and T21 remain closed historical evidence and never override current T26 release identity. Historical exact heads, runs and package hashes must never be presented as the current branch or current release status after later commits.

Environment-dependent acceptance cannot be manufactured in source. Execute `STAGING-ACCEPTANCE-1.0.0.md`/`STAGING-ACCEPTANCE.md` on canonical Hostinger staging with the exact verified artifact, including the two required fresh post-final-runtime-code verification sweeps; then, only after explicit production authorization, freeze deployed artifact/version/schema/migration state and perform live parity re-test.


## T29 — unified current-plan source candidate (12 October 2026)
Latest File08 PR#12/T28 Source `0dbfddc789f295b30b9aa1b71cb7ec4fda443bff` retains current File00 1.2.3, File09 eligible verification, CF01 1.1.0, all original + revived runtime tests, package parity and File17/19 fixes. T29 candidate additionally integrates proven PR#14 strict File08 patient/clinic signed cursor decoder, RAW UTC cursor encoder validation and no-partial-pagination degraded responses. **20 bounded File08/central/companion source reviews** were frozen before modification at `docs/T29-20-FROZEN-REVIEWS-2026-10-12.md`; 20 complete real E2E reviews have NOT been performed. Tests include `tests/t27-r1-query-cursor-integrity-regressions.php`, `tests/t27-r2-cursor-producer-consumer-regressions.php` and the T28 no-orphan suite. Software 1.2.15; source core schema 3.4.0, Future24/Continuity 1.1.0 unchanged. Hostinger/physical DB/migrations/staging/live verification not performed; PR is a draft and no deployment authorized.


## T30 exact-source review candidate — 12 October 2026
Twenty bounded plan/central/companion owner inspections against T29 SHA `03a376e3aab37e191eeb32fbf50910e644095f30` frozen before edits in `docs/T30-20-FROZEN-REVIEWS-2026-10-12.md`. T30-D01: malformed signed pagination cursor arrays, whitespace, HTML and dashboard query parameters could be cast, trimmed or sanitized before proof of canonical identity. T30-D02: malformed/nested `per_page` silently defaulted to 20. Current source `WCA_Query_API` now rejects wrong types without DB reads or private projection, preserves an exact signed opaque cursor and a canonical bounded positive page size, with a new real method regression in `tests/t30-private-query-input-integrity-regressions.php`. This is one source-only draft; prior T29 File00/09/CF01, File17/19 and all T28 test coverage are retained. Software candidate 1.2.15, source core schema 3.4.0 and Future24/Continuity 1.1.0 unchanged. Hostinger deployed code, DB/migrations, two staged reviews, Founder acceptance, deployment and live retest remain unverified.


## T31 original-plan Future24 integrity source candidate — 13 October 2026
The 20 **bounded** File08/central/companion source reviews were frozen *before code changes* in `docs/T31-20-FROZEN-SOURCE-REVIEWS-2026-10-13.md`. T31-D01: canonical File00 subject UUID not available, yet prior Group Session join could persist an empty member_ref; new `WCA_Future24::join_group_session` validates the exact owner-issued UUID before any group session lookup or write. T31-D02: malformed historical Future24 payload_json could masquerade as empty operational state; `public_record` now rejects nonobject/nonempty positional JSON with structured privacy-safe logs and a 503, preserving valid {} and legacy [] empty. Native regression `tests/t31-future24-group-identity-and-record-integrity.php` registered in the canonical full no-orphan runner. All code-level corrections are source-only: unchanged software 1.2.15, Core schema 3.4.0 and Continuity/Future24 1.1.0. No installed Hostinger plugin, physical DB/schema/wp_options, migration, dependency/staging/live checks verified.


## T32 — 40 governed source checks and FUT11/13 operational policy safety (13 October 2026)
Forty separate **bounded** original File08 FR001–018/NFR001–010/Future24 requirement-to-source inspections were FROZEN before fixes in `docs/T32-40-FROZEN-GOVERNING-REVIEWS-2026-10-13.md`, baseline exact PR18 SHA `258b8e00a4c1ea360802c636c10919bc13a89faa`. They are not forty complete real end-to-end audits. T32-D01: nested FUT13 blocking rule maps were discarded by generic operational sanitizer before storage, potentially making readiness falsely complete. Narrow canonical FUT13 rule whitelist in put_record now retains only four approved nonclinical fields. T32-D02: corrupt active FUT11 questionnaire and FUT13 rule JSON no longer silently becomes empty form/complete policy. T32-D03: keyset policy reader now rejects invalid/non-increasing source row IDs, avoiding nonprogress loops/duplicate projection. Real PHP `tests/t32-future24-policy-persistence-and-read-integrity.php` registered with all prior T31 tests and T28 zero-orphan gate. No source schema change: software `1.2.15`, Core `3.4.0`, Future24/Continuity `1.1.0`. Historical broken rows require actual DB inspection and controlled remediation; no physical migration performed, no claim of installed release or live verification.


## T33 Source-only 40 governed File08/companion checks — 13 October 2026
40 **bounded** requirement-to-exact-source checks (not forty independent full-application E2E reviews) frozen before any correction: `docs/T33-FORTY-FROZEN-PLAN-COMPANION-REVIEWS-2026-10-13.md`. Baseline PR19 HEAD `531fc91f5a4c7d0f86f015a34e85ebc3c907178c`. Current repository `main` separately advanced `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03` and unmerged PR20 `2b353c5ae6749208c504e97460101f561a85ee76` differs from PR19; **not automatically reconciled or merged**. FUT13 D01 duplicate normalized rule keys in new submitted policies rejected before storage, D02 duplicate historical stored rule keys fail closed, D03 contradictory simultaneously active block/provisional versions return an explicit policy conflict (503), rather than silently downgrading a blocking prerequisite. No undocumented automatic newest-vs-oldest precedence invented. `tests/t33-fut13-rule-uniqueness-and-policy-version-conflicts.php` is registered with full 105+ prior executable PHP suites, T28 zero-orphan guard, original T32 tests. Candidate source remains 1.2.15 / Core source schema 3.4.0 / Future24+Continuity source 1.1.0; no physical migration, deployment or live verification.
