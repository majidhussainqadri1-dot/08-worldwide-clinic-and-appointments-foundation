# T25 R8 — Correction Evidence

Status: **R8 FROZEN DEFECTS CORRECTED / EXACT-HEAD QA REQUIRED**

Frozen review ledger: `docs/T25-R8-FROZEN-LEDGER.md` at commit `ba436426686ce554358e813dccd416e79d699bc2`.

## Discipline preserved

The R8 review was completed and its two defects were frozen before any correction began. This document records only the post-review correction batch; it does not rewrite the frozen findings.

## R8-D1 correction — File 26 freshness invalidation is no longer silently acknowledged

The frozen ledger correctly identified that the historical `WCA_Central_Governance::observe_outbox_event()` could ignore a failed `File26.SearchProjectionChanged.v1` enqueue after the canonical owner event had already committed.

During root-cause correction, the implementation point was refined without changing the defect itself: the canonical File 08 owner transaction already persists the owner event/outbox fact atomically. File 26 is a derived consumer projection and must not become part of File 08 canonical business truth. Therefore the reliability-preserving correction is to replace the historical observer at runtime with `WCA_Second_Ten_Review_Hardening::observe_search_projection_event()`. If the derived File 26 invalidation cannot be durably queued, the callback throws; `WCA_Outbox::process()` catches that failure and retains the parent event in the existing bounded retry/dead-letter path rather than marking it delivered.

This preserves both laws at once: File 08 owns its canonical fact; File 26 owns search/index/ranking; cross-file projection failure is retryable and cannot be silently lost.

## R8-D2 correction — checked-in CF-01 scheduling context

`SWC_CF01_Care_Context` now treats `checked_in` as an active scheduled appointment context for the scheduling-only assertion:

- context state: `scheduled`;
- relationship state: `scheduled_contact`;
- scheduled UTC time remains available;
- the duplicate `requested` state-map entry was removed.

No clinical authority was added. The assertion still declares `scheduling_only`, `treating_relationship_asserted=false`, clinical read/write/prescription/break-glass authority false, and appointment consent remains appointment-processing-only.

## Permanent regression

`tests/t25-r8-cross-file-reliability-regressions.php` is bound into `tests/run-all.php`. It permanently checks retry propagation for File 26 invalidation, retirement of the legacy observer at runtime, checked-in CF-01 lifecycle projection, and continued denial of treating/clinical authority.

## Evidence boundary

These corrections establish repository/source behavior only. The corrected exact HEAD still requires canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate-package verification. Staging and live deployment remain separate evidence states.
