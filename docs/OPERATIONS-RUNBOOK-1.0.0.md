# Operations Runbook — File 08 1.0.0

## Routine checks

- Review File 08 Operations health and dependency matrix.
- Confirm `wca_process_outbox` and `wca_maintenance` are scheduled.
- Alert on dead-letter growth, error-rate increase, slot-hold expiry backlog, migration mismatch, missing tables or open provider circuits.
- Preserve logs without clinical/contact narrative; correlate by `X-Request-ID`.

## Failure handling

1. **File 00/09 unavailable:** protected clinic/doctor actions fail closed; do not downgrade to local roles.
2. **File 19 unavailable:** privacy-minimal email fallback is attempted; failures retry through outbox and then dead-letter.
3. **Calendar/payment/case provider failure:** retain local intent/event, retry asynchronously, open circuit after repeated failures and never duplicate provider writes.
4. **Database contention:** return conflict/stale response, preserve original state and require refreshed action.
5. **Emergency content:** divert immediately; no appointment or delayed support workflow.
6. **Doctor suspension:** place nonterminal appointments on authority hold and notify affected patients without exposing private details.

## Recovery targets

Recovery objectives must be measured on Hostinger staging and recorded with actual backup/restore durations. Source documentation does not invent an RTO/RPO. Production authorization requires an observed restore and post-restore reconciliation.
