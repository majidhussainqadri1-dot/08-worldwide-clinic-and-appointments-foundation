# T25 R5 — Frozen Review Ledger

Status: **REVIEW COMPLETE / CLEAN LEDGER FROZEN**

Review baseline: `fa0f9827db0122b7d98a9fe97004b7e55c70fbea` (T25 R4 exact-head canonical run `35054690392` successful).

## Mandatory discipline

R5 was completed read-only across calendar-provider, ICS and outbox reliability paths. No R5 correction was started while the review remained open.

## Review scope

Short-lived signed ICS links; signer/download participant reauthorization; appointment-state/time validation; calendar-provider allowlist and signature-adapter verification; webhook size/replay window/practitioner checks; provider-event idempotency and uncertain-transaction retention; external busy/mapping reconciliation without canonical appointment mutation; outbox advisory locking; stale-claim recovery; worker-fenced complete/fail semantics; stable message identity; retry/dead-letter behavior; File19 privacy-minimal fallback; CF02/CF03 adapter result boundaries; circuit breakers; maintenance failure propagation; and cron/deactivation ownership.

## Frozen defect ledger

**No new proven repository/source defect was identified in T25 R5.**

Signed calendar downloads revalidate the opaque appointment reference, bearer signature/expiry, subject mapping and current appointment authorization before returning schedule facts. Calendar-provider webhooks fail closed unless the adapter verifies the event, bind replay identity to provider/event facts, retain claims when storage state is uncertain, and explicitly report `canonical_appointment_mutated=false`. The outbox serializes workers with a database advisory lock, uses worker-fenced durable completion/failure and stale recovery, and preserves stable message identity for idempotent consumers. Notification fallback excludes clinical narrative and fails retryably when any intended delivery fails.

## Evidence boundary

Repository/source evidence only. Real provider signatures, WP-Cron timing, SMTP/File19 delivery, downstream CF02/CF03 idempotency, external calendar ordering and production MySQL advisory-lock behavior remain staging/live evidence.

## Closure gate

No R5 correction batch is required. The exact ledger HEAD must pass canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate verification before R6 begins.
