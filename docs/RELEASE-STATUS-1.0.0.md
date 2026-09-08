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
- Verified pre-T21 baseline: `79f18b5cb3ad9270a52027df7c3a6b50465fdfa4`

## Repository-scope completion

The prior **T20 R1–R20** sequence is complete. T20's latest ten rounds (R11–R20) were 8/10 clean: R11 and R13–R19 clean; R12 and R20 defect-bearing. That satisfied the previous stopping threshold. The user explicitly resumed review work, so **T21** is a fresh review cycle from the exact verified T20 baseline.

T21 R1 has completed its review-only phase and frozen its defect ledger before correction. R1 correction is repository/release-evidence work; R2 must not begin until the entire R1 correction batch has permanent regression coverage and the resulting exact HEAD passes the canonical PHP 7.4/PHP 8.3 quality and reproducible-candidate gates.

Implemented repository scope includes 18/18 FR, 10/10 NFR and Future24 24/24 capability governance; canonical data/schema and migration/rollback/recovery controls; security/privacy/reliability/accessibility/localization/observability; warning-clean source regression aggregation; and deterministic candidate engineering whose manifest binds exact commit, runtime, plan and core/continuity/Future24/Public-Clinic/CF-01 schema-contract identity.

## Evidence-state classification

- Source implementation: **CODED CANDIDATE — T21 REVIEW/CORRECTION IN PROGRESS**
- Automated source checks: **PENDING FOR CURRENT T21 CORRECTION HEAD**
- Deterministic candidate: **PENDING FOR CURRENT T21 CORRECTION HEAD**
- Prior verified T20 exact-head CI/package: **HISTORICAL BASELINE EVIDENCE after any T21 HEAD change**
- Hostinger staging acceptance: **NO / NOT CLAIMED**
- Founder staging acceptance: **NO / NOT CLAIMED**
- Production release/deployment: **NO / UNVERIFIED**
- Operational status: **NO / NOT CLAIMED**

A correction changes HEAD and makes prior CI/package evidence historical for final-release purposes. Packaged/Automated-QA Green status therefore applies only when the current exact HEAD itself passes the canonical PHP 7.4/PHP 8.3 quality jobs and reproducible candidate verification.

## Historical review provenance

T19 R1–R20 and earlier review cycles remain historical evidence. T20 then materially hardened the same 1.2.15 runtime candidate, including appointment idempotency-header normalization, reschedule lifecycle/hold cleanup, authorization-boundary correction, availability-projection correction, payment-currency persistence parity, calendar-provider practitioner/order/idempotency hardening, and release-evidence corrections. Historical review evidence must never be presented as the current branch or current release status.

Environment-dependent acceptance cannot be manufactured in source. Execute `STAGING-ACCEPTANCE-1.0.0.md`/`STAGING-ACCEPTANCE.md` on canonical Hostinger staging with the exact verified artifact, including the two required fresh post-final-runtime-code verification sweeps; then, only after explicit production authorization, freeze deployed artifact/version/schema/migration state and perform live parity re-test.
