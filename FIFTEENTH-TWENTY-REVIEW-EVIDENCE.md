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


## R7 — File00/File09 claims, guardian/minor, consent/revocation review

R7 completed before correction. Cross-plugin helper returns were not uniformly type-checked: error objects or unexpected values could become truthy in Founder, guardian, step-up, guardian-relationship or verification decisions. Versioned age/guardian helper failure could also fall through to legacy metadata. The post-review batch makes authority grants explicit and fail-closed and treats a versioned provider response as authoritative rather than silently downgrading to legacy state.

R7 result: **SUPPORTED DEFECTS FOUND — full retest required before R8.**


## R8 — REST routes / permission / opaque-reference / cache review

R8 completed before correction. Numeric legacy protected endpoints remain disabled by default and external payload stripping/no-store layers were re-traced. Two supported defects remained: the canonical opaque payment-intent adapter did not copy the caller's Idempotency-Key into its internal proxy request, and the stale-idempotency precheck did not distinguish an SQL read failure from no existing replay row. Both are corrected together after R8 closure.

R8 result: **SUPPORTED DEFECTS FOUND — full retest required before R9.**


## R9 — transaction / CAS / projection atomicity review

R9 completed against the R8-corrected state without source modification. Owner transactions, optimistic version predicates, required event/outbox evidence and Future24 semantic serialization were re-traced. No new supported atomicity defect was proven.

R9 result: **CLEAN — no correction required.**

## R10 — migration / upgrade / rollback review

R10 completed before correction. The canonical migration state recorded the legacy SWC schema as its `from_version` instead of the actual prior WCA schema version. In addition, schema and activation snapshots could remain from an older deployment rather than the immediate pre-change state required by the rollback runbook. The R10 batch captures the true WCA from-version, refreshes the schema snapshot for a real schema transition, and refreshes the activation snapshot for every activation/deployment attempt.

R10 result: **SUPPORTED DEFECTS FOUND — full retest required before R11.**


## R11 — complete Future24 F08-FUT-01…24 functional / safety review

R11 was completed against the R10-corrected state before any R11 source change. Supported findings covered: nested Future24 owner transactions that could issue a second START TRANSACTION inside an outer atomic mutation; cancellation-waitlist traversal/delivery failures that could be acknowledged silently; SQL failure collapsed into Future24 record-not-found; policy/template traversal false-empty behavior; readiness intake false-state behavior; service-scoped waitlist/questionnaire mismatch when service truth was unavailable; optional group-session creation versus mandatory-service join inconsistency; cross-actor duplicate arrival rows and queue inflation; non-strict external subject resolution; and successful safe-reschedule returning success despite missing Future24 governance evidence. The correction batch joins nested transactions to the outer transaction, makes read/retry state authoritative, repairs scope semantics, deduplicates arrival per appointment and makes audit-finalization ambiguity explicit.

R11 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R12.**


## R12 — public/private projection, minimization, cache and existence-leak review

R12 completed against the R11-corrected state before any R12 source change. The opaque appointment read route did not itself guarantee private no-store/noindex headers, and an existing-but-unauthorized appointment could return an authorization error distinguishable from a missing opaque reference. The review also caught a remaining application-layer currency normalization path that could transform malformed input before the repository's strict persistence check. The R12 batch makes opaque object responses private/non-indexable, conceals participant-denial existence, and validates exact currency intent at the application root.

R12 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R13.**


## R13 — cross-file ownership / integration / projection review

R13 completed before correction. CF-01 care context could bypass the canonical purpose-limited appointment authorization root for generic clinic administrators. File09 verification reconciliation ignored event/File26 outbox write failures and could treat a failed clinic page read as completion. File19 notification delivery treated WP_Error as a truthy success. The post-review batch routes CF-01 through object authorization, makes verification projection writes atomic/retryable and makes File19 provider errors explicit.

R13 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R14.**
