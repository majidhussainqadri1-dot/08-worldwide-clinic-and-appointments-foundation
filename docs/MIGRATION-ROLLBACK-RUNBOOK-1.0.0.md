# Migration and Rollback Runbook — File 08 1.0.0

## Preconditions

1. Freeze File 08 writes and record the exact source commit, dependency versions, PHP/WordPress versions and active plugin list.
2. Create and verify a database backup and complete `wp-content` backup; record hashes and restoration location.
3. Export counts and checksums for legacy appointment posts, `_swc_*` metadata, audit rows, rate-limit rows and owned page mappings.
4. Confirm no active incident, legal hold conflict or unresolved concurrent deployment.

## Forward migration

1. Install the exact CI candidate on a disposable clone first.
2. Activate with Files 00, 03, 07 and 09 available. Activation creates legacy audit/rate tables and canonical WCA tables through idempotent `dbDelta`.
3. Preserve a pre-migration metadata snapshot under `wca_schema_snapshot`.
4. Map legacy states: `accepted → confirmed`, `reschedule-requested → reschedule_pending`, `under-review → requested`; record the former state on each migrated object.
5. Do not generically migrate clinical-like free text. CF-01 extraction remains a separately approved, field-mapped, consent/provenance-controlled change.
6. Reconcile record counts, public references, statuses, ownership, version values, appointment times, consent and audit evidence.
7. Run health, source tests, real role journeys, cache/delivery/accessibility and rollback rehearsal before accepting staging.

## Rollback

1. Stop writes and drain or suspend the outbox.
2. Prefer application rollback to the prior plugin while retaining canonical tables read-only; never create two writable owners.
3. Restore saved page/version metadata with `WCA_Schema::rollback_to_snapshot()` only when the recorded snapshot matches the deployment.
4. For full database rollback, restore the verified backup, then re-run count/hash reconciliation.
5. Confirm revoked access, erased content and legal holds were not resurrected.
6. Re-enable one owner, clear private caches, verify notification suppression/replay policy and monitor errors.

## Prohibitions

- No destructive table drop during normal deactivate/uninstall.
- No direct writes into Files 00/03/07/09/17/19/24 or CF-01/02/03 stores.
- No automatic conversion of scheduling narrative into a signed clinical encounter, prescription or treating relationship.
- No production cutover without a successful restore test and Founder acceptance.
