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
- Current review branch: `review/file08-t21-ten-round-2026-09-09`
- Current review cycle: **T22 closure cycle**
- Historical T21: **R1–R10 closed**
- T22 through R9: R1 clean; R2 defect-bearing/corrected; R3–R7 clean; R8 defect-bearing/corrected; R9 defect-bearing/corrected and exact-head green at `c3b46247b69bd87534e5fab4133ebf423a1da893` / run `34838735323`.

## Repository-scope completion

T21 is historical and closed. T22 is the active closure sequence and follows the mandatory Review → Ledger Freeze → Fix → Regression → Exact-head CI/package discipline. Exact numbered-round closure is tracked in PR #10 and frozen ledgers because a static status document cannot truthfully embed a future CI result that occurs only after the commit containing the document itself.

Implemented repository scope includes 18/18 FR, 10/10 NFR and Future24 24/24 capability governance; canonical data/schema and migration/rollback/recovery controls; security/privacy/reliability/accessibility/localization/observability; warning-clean source regression aggregation; and deterministic candidate engineering whose manifest binds exact commit, runtime, plan and core/continuity/Future24/Public-Clinic/CF-01 schema-contract identity.

T22 hardening already present in the 1.2.15 candidate includes explicit lifecycle/audit actor provenance, current whole-clinic File26 reconciliation with delegation-change invalidation, localized machine-state/consultation/clinic labels, patient-timezone locale-aware rendering, browser-timezone/date alignment and translation-safe continuity labels.

## Evidence-state classification

- Source implementation: **CODED CANDIDATE — T22 CLOSURE CYCLE ACTIVE**
- Automated source checks: **EXACT-HEAD ONLY — consult PR #10/current canonical workflow for the latest T22 HEAD**
- Deterministic candidate: **EXACT-HEAD ONLY — consult PR #10/current canonical workflow for the latest T22 HEAD**
- R9 verified exact-head CI/package: **`c3b46247b69bd87534e5fab4133ebf423a1da893` / run `34838735323`**; historical after any later correction changes HEAD
- Hostinger staging acceptance: **NO / NOT CLAIMED**
- Founder staging acceptance: **NO / NOT CLAIMED**
- Production release/deployment: **NO / UNVERIFIED**
- Operational status: **NO / NOT CLAIMED**

A correction changes HEAD and makes prior CI/package evidence historical for final-release purposes. Packaged/Automated-QA Green status therefore applies only when the exact HEAD under consideration itself passes the canonical PHP 7.4/PHP 8.3 quality jobs and reproducible candidate verification.

## Historical review provenance

T19, T20 and T21 remain historical evidence. T20 materially hardened the same 1.2.15 runtime candidate with appointment idempotency-header normalization, reschedule lifecycle/hold cleanup, authorization-boundary correction, availability-projection correction, payment-currency persistence parity, calendar-provider practitioner/order/idempotency hardening, and release-evidence corrections. T21 then closed a further ten-round sequence. Historical review evidence must never be presented as the current branch or current release status.

Environment-dependent acceptance cannot be manufactured in source. Execute `STAGING-ACCEPTANCE-1.0.0.md`/`STAGING-ACCEPTANCE.md` on canonical Hostinger staging with the exact verified artifact, including the two required fresh post-final-runtime-code verification sweeps; then, only after explicit production authorization, freeze deployed artifact/version/schema/migration state and perform live parity re-test.
