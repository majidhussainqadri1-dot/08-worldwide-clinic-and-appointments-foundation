# File 08 — Fifteenth Fresh 20-Round Review / Fix / Retest Evidence

## Governing method

Each round is completed as a review first. Findings are collected without code correction during the review. Only after that review closes are all supported findings from that round corrected together, followed by full retest; only a green corrected state may become the next round baseline. Repository evidence is not staging/live evidence.

## R1 — complete mutation-safety / scheduling-concurrency review

Frozen reviewed baseline: `46ec33f82e732d6b5dfdab0cd88b6d53be9da620` (runtime 1.2.14).

The complete R1 review found a shared root defect class: several database reads on authoritative conflict, hold, replay, capacity, semantic-de-duplication, buffer/travel-policy, arrival/queue, support-participant, virtual-room and external-busy paths treated an SQL read failure like an empty result. This could permit duplicate or conflicting scheduling state, bypass a current policy, under-enforce rate limiting, or report a false successful absence. No correction was started until the R1 review was closed. The post-review corrective batch makes these paths fail closed or return explicit retryable errors and adds the permanent T15 regression gate.

R1 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R2.**


## R2 — complete privacy / export / erasure / retention review

R2 was completed against the R1-corrected state before any R2 source change. It found SQL-read failure paths that could be interpreted as an empty privacy set, a missing continuity row, or successful completion: canonical and legacy erasure cursors, Future24 retention, continuity optimistic-version/consent reads, follow-up list/reminder scans, and continuity retention. The post-review batch makes read failure explicit/retryable and prevents false completion.

R2 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R3.**


## R3 — complete outbox / idempotency / maintenance review

R3 was completed against the R2-corrected state before any R3 correction. It found failure-visibility gaps in advisory lock acquisition, abandoned-outbox recovery, pending-claim/readback, payment/idempotency replay reads, and the authoritative reconciliation query. Database errors could be flattened into contention, no work, or not-found states. The post-review batch makes those reads and recovery operations explicit fail-closed errors and propagates outbox processing failures into maintenance.

R3 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R4.**


## R4 — strict temporal / timezone / DST review

R4 completed against the R3-corrected state without source modification during review. Canonical UTC slot evidence, public date ranges, IANA timezone identifiers, DST local-time round trips and DOB round trips were re-traced. No new supported temporal product defect was proven.

R4 result: **CLEAN — no correction required.**

## R5 — numeric / money / bounds / code-integrity review

R5 completed before correction. It found that service currency silently stripped non-letters before validating; the R1 slot-hold read checks had been mechanically concentrated at one location instead of covering all three authoritative reads; and a DB failure acquiring the slot advisory lock was indistinguishable from normal contention. All R5 findings are corrected together after review completion.

R5 result: **SUPPORTED DEFECTS FOUND — full retest required before R6.**


## R6 — authorization / ownership / opaque-reference review

R6 completed before correction. Object-level patient/doctor/guardian/staff checks and serving/delegation scopes were re-traced. Two supported gaps remained: purpose-limited administrative appointment access did not fail closed when its mandatory audit write failed; opaque practitioner-reference creation did not separately handle DB lock failure or metadata persistence failure. The R6 batch corrects both classes after review completion.

R6 result: **SUPPORTED DEFECTS FOUND — full retest required before R7.**
