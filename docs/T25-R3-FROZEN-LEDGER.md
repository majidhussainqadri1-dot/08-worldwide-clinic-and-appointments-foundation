# T25 R3 — Frozen Review Ledger

Status: **REVIEW COMPLETE / CLEAN LEDGER FROZEN**

Review baseline: `93cbe973cb41b9dc7f30e49edb90ec9e6641baac` (T25 R2 exact-head canonical run `35054401050` successful).

## Mandatory discipline

R3 was completed read-only. No correction was applied while the review remained open.

## Review scope

Canonical eight-state appointment lifecycle and terminal states; actor transition matrices; required expected-status/version preconditions; object authorization recheck inside mutation locks; slot-hold ownership/freshness/expiry; reschedule hold scope; doctor/clinic/service binding; simultaneous slot contention; appointment request consent and emergency acknowledgement; teleconsult consent; idempotency claim/request fingerprint/replay behavior; stale-processing fail-closed reconciliation; transaction start/commit/rollback uncertainty; idempotency completion/release semantics; and duplicate appointment prevention.

## Frozen defect ledger

**No new proven repository/source defect was identified in T25 R3.**

The canonical appointment command requires privacy/emergency consent and remote-consultation consent where applicable, resolves a current held slot, blocks ambiguous stale idempotency reservations, and delegates the owner mutation to the transactional service. Transition commands require both expected status and positive record version, recheck authorization under the appointment lock, and enforce the actor/state transition matrix. Appointment-request uncertain transaction state retains the idempotency reservation instead of allowing blind replay. Reschedule holds re-run transition authorization and require replacement clinic/service/doctor scope to match the appointment.

## Evidence boundary

Repository/source evidence only. Production MySQL transaction/isolation behavior, concurrent HTTP execution, server clock and real role/guardian state remain staging/live acceptance items.

## Closure gate

No R3 correction batch is required. The exact ledger HEAD must pass canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate verification before R4 begins.
