# File 08 — T19 R19 Frozen Defect Ledger

Review scope: admin operations, WP-CLI recovery, operability, queue inspection, bounded repository reads, and public discovery query shape.

Review discipline: this ledger was frozen only after the R19 source review completed. No R19 production correction was applied during the review phase.

Exact reviewed parent HEAD: `021a76d630222609cd01bebc81f8eb22995bffd8`.

## Frozen defects

1. **R19-D1 — Unbounded owned collection reads.** `WCA_Repository::list_branches()`, `list_services()`, and `list_availability_rules()` return arbitrarily large result sets without a hard SQL `LIMIT`, contrary to F08-NFR-004 bounded-query requirements.
2. **R19-D2 — Redundant public-clinic hydration.** The public clinic collection first hydrates branches/services in `list_clinics()`, then calls `public_clinic_projection()` for every row; that projection re-reads/hydrates the same clinic in private and public forms. The discovery page size is bounded, but the avoidable repeated child reads multiply DB work per result.
3. **R19-D3 — Canonical Operations screen is attached to a hidden CPT parent.** `WCA_Admin::menu()` registers below `edit.php?post_type=...` while the appointment post type has `show_ui => false`; the richer canonical Operations screen is therefore not reliably discoverable from the visible File 08 administration menu.
4. **R19-D4 — Outbox retry operation is not operable from its UI and reports false ambiguity.** `retry_outbox()` exists, but the Operations page renders no retry form; the handler also ignores `WP_Error`/processed result and redirects without success/failure state.
5. **R19-D5 — Health/operations do not expose queue/dead-letter state.** Health currently checks whether cron hooks are scheduled, but does not expose pending/retry/processing/dead-letter counts or due-work state, leaving F08-NFR-008 queue inspection incomplete.
6. **R19-D6 — Recovery coverage is incomplete exactly when migration repair is needed.** Runtime migration failure returns before `WCA_CLI::register()`, so File 08 recovery commands disappear on that failure path. In addition, `wca migrate` and the admin “Run Complete Repair” path do not explicitly repair/verify every File 08-owned schema layer (legacy SWC, canonical WCA, continuity, Future24) before claiming completion.

## Correction gate

All six defects must be corrected as one post-review R19 batch, followed by PHP syntax, full source regressions, JavaScript syntax, canonical PHP 7.4/PHP 8.3 CI, and reproducible independently verified candidate packaging before R20 may begin.
