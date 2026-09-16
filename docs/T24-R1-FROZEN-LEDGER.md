# T24 R1 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `aabe2ca159b14c52c9ffc5f8562c67c6ee28eac1` (T23 R10 clean ledger; canonical run `35050486867` completed successfully).

## Mandatory discipline

R1 was completed as a full read-only API / cross-domain idempotency / transaction-uncertainty review before this ledger was created. No R1 correction was applied while the review remained open.

## Review scope

Canonical REST and opaque-reference mutation surfaces; signed/cursor query read paths; complaint-status projection from CF02; verified payment-status projection from CF03; Future24 generic idempotent mutation wrapper; owner transaction semantics; rollback/commit uncertainty; replay and duplicate prevention; external owner boundaries; and fail-closed response behavior.

## Frozen defects

### R1-D1 — CF02 complaint-status idempotency claim is released even when owner transaction state is uncertain

`WCA_Service::consume_cf02_case_status_event()` claims the verified event id before entering `WCA_Repository::transaction()`, then unconditionally releases that claim whenever the transaction returns a `WP_Error`. The repository transaction explicitly returns `state_uncertain=true` when commit/rollback outcome cannot be verified. Releasing the claim in that branch permits the same complaint event to be retried as new even though the prior projection may already have persisted.

### R1-D2 — verified CF03 payment-status projection has the same uncertain-transaction replay gap

`WCA_Service::consume_payment_status_event()` also releases its event idempotency claim on every transaction error. If the financial projection transaction returns `state_uncertain=true`, the same verified CF03 event can be admitted again despite uncertain persistence, risking duplicate projection/audit work and weakening financial reconciliation guarantees.

### R1-D3 — Future24 generic idempotent mutation wrapper releases claims after uncertain nested owner transactions

The generic Future24 mutation wrapper claims an idempotency key, calls the requested scheduling mutation, and releases the claim for every returned `WP_Error`. Multiple wrapped Future24 mutation methods use `WCA_Repository::transaction()` and can therefore return `state_uncertain=true`. In that branch the wrapper must retain the claim and surface reconciliation evidence rather than allow a fresh replay.

## Reviewed non-defects

- Opaque-reference routes preserve idempotency headers when proxying payment requests and do not expose internal numeric identities as canonical public object identities.
- Query API paths are read-only, use signed cursor state, repeat native authorization checks, and return no mutation idempotency responsibility.
- Appointment creation and payment-intent creation already contain explicit fail-safe claim retention for `state_uncertain=true`.

## Correction gate

After this ledger freeze only:
1. Make CF02 complaint-status projection retain its idempotency claim on uncertain transaction state, emit a dedicated metric/log, and release only on verified-safe failure.
2. Apply the same rule to verified CF03 payment-status projection.
3. Apply the same rule to the Future24 generic idempotent mutation wrapper.
4. Add a permanent aggregate regression for all three branches.
5. Run canonical PHP 7.4/PHP 8.3, full source/JS/hygiene and reproducible candidate-package verification before the next review begins.

## Evidence boundary

Repository/source candidate evidence only. Actual CF02/CF03 provider delivery, production rows, deployed package parity, staging concurrency and live behavior remain separate evidence domains.
