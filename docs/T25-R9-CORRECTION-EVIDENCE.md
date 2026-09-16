# T25 R9 — Correction Evidence

Status: **R9 FROZEN DEFECTS CORRECTED / EXACT-HEAD QA REQUIRED**

Frozen review ledger: `docs/T25-R9-FROZEN-LEDGER.md` at commit `7800e0a62fc589bfef7f8680973916534d975d02`.

## Discipline preserved

The complete R9 read-only review and frozen defect ledger preceded all R9 source changes. The changes below form the post-review correction batch.

## R9-D1 — Verification reconciliation retry/dead-letter

`WCA_Verification_Reconciliation` now carries explicit retry-attempt state, uses capped exponential backoff, stops after eight total attempts, detects retry-scheduling failure, and persists a bounded durable reconciliation dead-letter option. Exhaustion/scheduling failure emits critical observability and metrics. A later successful reconciliation clears its matching dead-letter record. The health surface treats any remaining verification reconciliation dead letter as non-green.

## R9-D2 — Truthful `wca migrate` completion

`WCA_CLI::migrate()` now processes legacy-status reconciliation in bounded 5,000-row batches until `WCA_Compatibility::MIGRATION_OPTION` is durably present. It aggregates the number of reconciled legacy statuses and fails instead of claiming completion if there is no progress or the explicit repair budget is exhausted. The all-reconciled success message is therefore reachable only after the compatibility completion marker exists.

## R9-D3 — Migration currentness in health

`WCA_Observability::health()` now includes `legacy_statuses_complete` as a boolean migration gate and includes verification-reconciliation dead-letter freedom as an additional health gate. Core schema version alone can no longer make health green while the bounded legacy-status migration is still unfinished.

## Permanent regression

`tests/t25-r9-operability-reconciliation-regressions.php` is bound into `tests/run-all.php`. It checks finite attempts, attempt propagation, capped backoff, scheduling-failure detection, durable dead-letter behavior, success cleanup, CLI completion looping/no-progress safeguards, and both new health gates.

## Evidence boundary

Repository/source behavior only until the corrected exact HEAD passes canonical CI and package verification. No staging or live WP-Cron, database migration, dead-letter, provider or deployment state is asserted here.
