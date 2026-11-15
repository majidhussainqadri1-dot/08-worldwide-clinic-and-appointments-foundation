# File08 ↔ File09 V12.3 P05 — Real WordPress/MariaDB integration evidence lane
Date: 2026-11-15. **SOURCE-CANDIDATE / NOT HOSTINGER STAGING.**

## Pre-existing RED source defect
- File08 main `d086840f7e2ac0ed328006385f064e0e2fb58cb1` hardcoded `SMC_CONTRACT_VERSION='1.1.2'` and excluded File09 plugin 1.3.0 with `FILE_09_MAX_VERSION='1.2.0'`.
- Actual File00 main `1fad051c86d530d558be3eb1890d3c6e67bce6e8`: plugin 1.2.44, `SMC_CONTRACT_VERSION='1.2.3'`.
- File09 main `8f52f87a6d82fc89c6c78b7da80b474dff916d97`: plugin 1.3.0, `GDO_Integration_Contracts::VERSION='1.1.0'`, consumer `file08` explicitly available.
- RED GitHub Actions `43678306721`, PHP7.4/8.3 both fail current genuine-doctor fixture.

## Current consumer source fix — separate PR #66
- Exact source consumer `SWC_Doctor_Authority` now requires File00 1.2.3 assertion compatibility and File09 1.3.0 plugin + versioned 1.1.0 professional public projection, not retired helper-only inference.
- Explicit deny invariants: File00 unapproved/suspended/no-practice; File09 missing/stale/revoked/expired/pending-claim; mismatched consumer, wrong applicant, malformed public date, and institutional founder checks.
- 14 direct behavioral fixture assertions passed PHP7.4/8.3, and 14 actual PINNED current source comparison assertions passed. Exact run `43678779805` completed successful PHP7.4/8.3 + pinned owner parity + forty source-review pass + deterministic package. **No main merge or staging acceptance.**

## Added integration escalation — combined Draft PR #67
- New workflow job `installed_cross_owner` uses disposable GitHub runner, WordPress 7.0.1 and MariaDB 11.4.
- Exact File00 `1fad051...`, File02 `224c39...`, File03 `bf317e...`, File07 `8f9b56...`, File09 `8f52f8...`, and exact File08 PR HEAD are checked out and verified.
- `tests/ci-sixfile-wordpress-smoke.sh` performs installation and clean migration and activates all six real Plugins in a disposable synthetic environment. `tests/v123-wordpress-file00-02-03-07-08-09-smoke.php` checks real WordPress plugin/contract status, GDO schema 6/2, absence of authority for unapproved synthetic applicant.
- **Status: NEW JOB NOT YET VALIDATED** until its exact-head workflow completes. A CI success on PR66 is not the new six-plugin acceptance.
- `candidate` job waits on all source and installed integration jobs; never treat a partial run as release-ready.
- Security: no real credentials/patients; all keys are disposable CI literals, WordPress guard only allows the exact ephemeral `http://file08-p05.local`; no external issuer operation or production data.
- Not covered: positive issued-license doctor fixture with real claims/evidence, human 2-review, manual accessibility/RTL, restore/rollback and Hostinger exact deployed migration/version/artifact parity.

## Release freeze
**Repository File08 main:** `d086840f7e2ac0ed328006385f064e0e2fb58cb1`.
**File09 main:** `8f52f87a6d82fc89c6c78b7da80b474dff916d97`.
**Deployed Version:** UNVERIFIED. **DB Version:** UNVERIFIED. **Migration State:** UNVERIFIED. **Live Verification:** NOT PERFORMED.
File09 V12.3 Hostinger Staging gates remain **0/32 accepted**. Production Main merges **0**. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
