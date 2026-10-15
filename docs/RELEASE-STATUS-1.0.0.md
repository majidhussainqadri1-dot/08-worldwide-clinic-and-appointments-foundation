> **CURRENT T36 repository review (15 October 2026):** This draft candidate is based on unmerged PR #26, includes the independent FUT04/FUT23 fixes from PR #25, and adds a FUT16 stale-peer-count correction. The four T36 frozen ledgers cover 40 scoped source-anchor screens, **not 40 full WordPress/browser/DB end-to-end tests**. Exact-HEAD CI/package, staging DB, installed plugin/dependencies, migrations, Founder approval, deployment and live parity must be verified separately. Prior T26 wording below is historical test-fixture text, not the current T36 review identity.

Runtime candidate: **1.2.16**
Core schema: **3.4.0**

# Release Status — File 08 — document version 1.0.0

The `1.0.0` in this filename is the release-status document version; it is **not** the current plugin runtime.

## Current repository identity

- Runtime candidate: **1.2.16**
- Core schema: **3.4.0**
- Restricted continuity schema/contract: **1.1.0**
- Future24 schema/contract: **1.1.0**
- Public Clinic Contract: **1.1.0**
- CF-01 scheduling context contract: **1.1.0**
- Plan: **SSH-F08-PLAN-2026-v1.0**
- Current review branch: `review/file08-t35-40-check-guardian-queue-currentness-2026-10-14`
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


## T35 — Source-only FUT14 Guardian Paging and FUT16 Queue Freshness (14 Oct 2026)
Forty new **bounded** File08 original-plan, central plans and companion-owner source inspections were frozen before changes in `docs/T35-FORTY-FROZEN-SOURCE-REVIEWS-2026-10-14.md`. Baseline exact-green PR24 SHA `25de8f7679a03da2635447d3a93b80f37363f14c` on current source runtime 1.2.16. T35-D01: `WCA_Future24::queue_position` now requires current appointment lifecycle `confirmed` or `reschedule_pending` before looking up an old arrival record; canceled, completed, no-show and other ineligible states fail 409 rather than showing a stale live queue. T35-D02: every `WCA_Future24::family_hub` WP_Query page must be an array and free of DB error, otherwise explicit 503 without claiming `pagination_complete` or leaking a partial dependent list. Native PHP7.4/8.3 fixture `tests/t35-future24-guardian-paging-and-stale-queue-regressions.php` uses actual methods, test runner/no-orphan gate retained. Existing rule and privacy patches preserved. Current repository source 1.2.16 (schema source version unchanged); exact installed Hostinger plugin and DB/migration, staging user IDOR, other canceled peer entries, backups, Founder approval and live re-test remain UNVERIFIED. This is neither merged nor deployed.
