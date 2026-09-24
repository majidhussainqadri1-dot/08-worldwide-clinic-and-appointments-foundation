# T25 R4 — Frozen Review Ledger

Status: **REVIEW COMPLETE / CLEAN LEDGER FROZEN**

Review baseline: `41a84b670b436d2e22871f831aaa76ffc1319979` (T25 R3 exact-head canonical run `35054535290` successful).

## Mandatory discipline

R4 was completed read-only across File 08 fee/payment/cancellation financial boundaries. No R4 correction was started while the review remained open.

## Review scope

Service fee and ISO-style currency validation; appointment-time fee/service snapshot persistence; immutable zero platform commission; patient/current-guardian payer authority; approved provider allowlist; payment-intent replay fingerprint and uniqueness; CF03 payment-intent outbox request; verified CF03 status projection; uncertain transaction idempotency retention; refund/cancellation/no-show/decline fee-policy review events; legacy missing-snapshot reconciliation state; payment native-ID/privacy projection; and separation of File 08 appointment facts from CF03 financial truth.

## Frozen defect ledger

**No new proven repository/source defect was identified in T25 R4.**

Current source validates persisted payment/service currency through the canonical currency allowlist, persists payment commission as zero, snapshots booked service/fee policy before later financial requests, limits payment-intent creation to the patient/current guardian plus object authorization, rejects unapproved providers, emits `CF03.PaymentIntentRequested.v1` instead of owning settlement truth, emits cancellation/decline/no-show policy-review requests rather than computing an external money ledger, and retains idempotency claims when transaction state is uncertain. Existing T20/T23/T24 permanent regressions cover currency persistence, zero commission and uncertain financial replay boundaries.

## Evidence boundary

Repository/source evidence only. Actual CF03 ledger/provider state, refunds, captures, settlements, disputes, taxes and deployed payment-provider behavior remain external staging/live evidence.

## Closure gate

No R4 correction batch is required. The exact ledger HEAD must pass canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate verification before R5 begins.
