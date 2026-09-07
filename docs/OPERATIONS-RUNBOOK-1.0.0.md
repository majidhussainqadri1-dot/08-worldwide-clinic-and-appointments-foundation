# Operations Runbook — File 08 — document version 1.0.0

## Routine checks

- Open **Clinic Management → Operations** and review health, dependency matrix and outbox queue counts (`pending`, `retry`, `processing`, `dead_letter`, `due`, oldest due work).
- Confirm `wca_process_outbox` and `wca_maintenance` are scheduled.
- Use `wp wca health` for machine-readable health and `wp wca queue` for operator queue/dead-letter inspection.
- Alert on dead-letter growth, due-work backlog, error-rate increase, slot-hold expiry backlog, migration mismatch, missing tables or open provider circuits.
- Preserve logs without clinical/contact narrative; correlate by `X-Request-ID`.

## Safe operator actions

1. **Process due outbox:** use the nonce/capability-protected Operations control or `wp wca outbox --limit=<bounded-value>`. Treat a returned error as failure; do not report success from a redirect alone.
2. **Migration/repair:** `wp wca migrate` repairs/verifies all File 08-owned schema layers — legacy SWC, canonical WCA, restricted continuity and Future24 — before reconciling legacy status state. The admin Complete Repair path covers the same owned schema layers.
3. **Runtime migration failure:** recovery CLI registers before runtime migration execution so `wp wca health`, `wp wca queue` and `wp wca migrate` remain available for diagnosis/recovery when the web runtime is paused by a migration failure.
4. **Dead-letter work:** inspect privacy-safe error codes/provider state first; correct the root cause before retrying. Never edit outbox payloads into a second writable truth.

## Failure handling

1. **File 00/09 unavailable:** protected clinic/doctor actions fail closed; do not downgrade to local roles.
2. **File 19 unavailable:** privacy-minimal email fallback is attempted; failures retry through outbox and then dead-letter.
3. **Calendar/payment/case provider failure:** retain local intent/event, retry asynchronously, open circuit after repeated failures and never duplicate provider writes.
4. **Database contention:** return conflict/stale response, preserve original state and require refreshed action.
5. **Emergency content:** divert immediately; no appointment or delayed support workflow.
6. **Doctor suspension:** place nonterminal appointments on authority hold and notify affected patients without exposing private details.

## Recovery targets

Recovery objectives must be measured on Hostinger staging and recorded with actual backup/restore durations. Source documentation does not invent an RTO/RPO. Production authorization requires an observed restore and post-restore reconciliation.
