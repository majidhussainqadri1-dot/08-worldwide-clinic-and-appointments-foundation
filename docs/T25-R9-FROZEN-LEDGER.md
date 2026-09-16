# T25 R9 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `f3d9d828b793dada5415bd9bdaa3ea367782a02d` (T25 R8 corrected exact-head canonical run `35060225956` successful on PHP 7.4, PHP 8.3, aggregate source/JS/hygiene tests and reproducible candidate packaging; artifact digest `sha256:c5ef37fcb7be464045ea82992b24874420b9ab4bfe791c7ebbd02489f983f217`).

## Mandatory discipline

R9 was completed as a fresh read-only operability, migration-currentness, retry/dead-letter, health and bounded-work review before this ledger was created. No R9 correction was started during the review. All findings below were frozen first.

## Review scope

Verification/delegation reconciliation, WP-Cron retry behavior, outbox retry/dead-letter semantics, health reporting, migration/repair CLI truthfulness, legacy-status compatibility migration, schema/continuity/Future24 health, maintenance behavior, bounded query/page traversal, purge iteration, provider circuits and operator-visible failure paths.

## Frozen defect ledger

### R9-D1 — Verification reconciliation retries forever at a fixed one-minute cadence

`WCA_Verification_Reconciliation::run_or_retry()` schedules `wca_retry_doctor_eligibility_reconciliation` one minute later after every failure, but carries no attempt count, maximum-attempt boundary, backoff, or terminal dead-letter state. A persistent File 09/File 08/File 26 reconciliation failure can therefore recur indefinitely and never become an explicit exhausted/operator-action state.

This conflicts with the File 08 reliability requirement for bounded retry, dead-letter and reconciliation instead of silent/infinite background failure.

**Required correction:** add explicit attempt state, bounded/capped backoff, a finite maximum attempt count, durable dead-letter evidence, critical observability on exhaustion, and clearing of the matching dead-letter after a later successful reconciliation.

### R9-D2 — `wca migrate` can report “All ... legacy statuses reconciled” after only one 5,000-row batch

`WCA_CLI::migrate()` invokes `WCA_Compatibility::migrate_legacy_statuses( 5000 )` once and then emits an unconditional success message claiming all File 08 schema layers and legacy statuses are reconciled. `migrate_legacy_statuses()` is intentionally bounded and may leave more legacy-status rows for a later call when a batch reaches the limit. Therefore the CLI can publish a false completion result on a large installation.

**Required correction:** the explicit repair CLI must continue bounded batches until the compatibility migration completion marker is durably present (or fail), aggregate the migrated count across batches, and never print all-reconciled success while remaining work exists.

### R9-D3 — Health can be green while legacy-status migration is still incomplete

`WCA_Observability::health()` includes core schema, continuity, Future24, runtime migration failure, dependencies, legacy page/table checks, cron and outbox health, but it does not include the `WCA_Compatibility::MIGRATION_OPTION` completion state. Since normal compatibility migration is deliberately incremental, the system can report `ok=true` while legacy appointment statuses are still pending reconciliation.

**Required correction:** make legacy-status migration completion an explicit boolean health gate so repository/runtime health truth cannot call the migration layer green before its completion marker exists.

## Clean boundaries confirmed during R9

Canonical outbox delivery has bounded attempts, retry/dead-letter status, stale-processing recovery and worker fencing. Core legacy-record migration uses resumable checkpoints; doctor-suspension traversal and destructive purge traverse bounded pages rather than silently stopping at a fixed total count. Future24 complete-set helpers use bounded paging where completeness is required. Provider circuit breakers are capped and recoverable. Query APIs retain explicit page-size bounds rather than pretending to return complete unbounded collections.

## Evidence boundary

Repository/source and exact package evidence only. This review does not establish actual WP-Cron execution, provider behavior, database row counts, staging migration completion, live migration state or live dead-letter state.

## Correction gate

All three R9 findings must be corrected only after this frozen ledger, permanent regressions must be bound into the aggregate runner, and the corrected exact HEAD must pass canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate-package verification before R10 begins.
