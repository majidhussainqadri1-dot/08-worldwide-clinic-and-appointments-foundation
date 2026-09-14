# T23 R9 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `46cf1703cec8e12b6c2af46c986118b899cb5822` (T23 R8 corrected exact head; canonical run `34901445288` completed successfully).

## Mandatory discipline

R9 was completed as a full read-only financial-boundary / complaint / review-eligibility review before this ledger was created. No R9 correction was applied while the review was open.

## Review scope

Appointment fee snapshots, payment-intent ownership boundary with CF03, supported provider and currency controls, patient/current-guardian payer authority, payment idempotency, transaction uncertainty, zero platform commission, cancellation/refund policy events, complaint purpose limitation and authorization, and review eligibility creation only on completed appointments.

## Frozen defect

### R9-D1 — payment-intent idempotency claim is released even when transaction outcome is uncertain

`WCA_Service::create_payment_intent()` claims a payment idempotency record before entering the owner transaction. If `WCA_Repository::transaction()` returns any `WP_Error`, the service unconditionally calls `WCA_Repository::release_idempotency( $claim['id'] )`. The repository transaction explicitly marks failed commit/rollback cases with `state_uncertain=true`; in that case persistence may already have occurred even though the caller cannot prove the final state. Releasing the claim after an uncertain financial transaction permits the same client key to be retried as a new payment intent, risking duplicate financial requests/outbox work.

## Reviewed non-defects

- Payment intent creation is limited to patient/current verified guardian and rechecks appointment access.
- Provider selection is delegated to the approved shared financial-owner boundary rather than persisting arbitrary provider secrets in File08.
- Fee amount/currency come from the immutable appointment fee snapshot and zero platform commission is explicit.
- Completion grants review eligibility only inside the completed lifecycle transition transaction.
- Complaint projections apply complainant/admin purpose checks and step-up controls rather than exposing case data publicly.

## Correction gate

After this ledger freeze only:
1. Preserve the payment idempotency claim when the returned transaction error carries `state_uncertain=true`; release only after a verified-safe rollback/failure.
2. Emit an observability signal for uncertain payment-intent outcome so reconciliation can find it.
3. Add a permanent regression proving the fail-safe claim-retention branch and bind it into `tests/run-all.php`.
4. Run exact-head canonical PHP 7.4/PHP 8.3, full source/JS/hygiene and reproducible candidate-package verification before R10 begins.

## Evidence boundary

Repository/source candidate evidence only. CF03 provider processing, external settlement, production payment records and live refunds/voids remain separate evidence domains.
