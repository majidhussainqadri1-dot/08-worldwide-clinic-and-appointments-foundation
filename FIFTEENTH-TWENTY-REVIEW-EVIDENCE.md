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


## R14 — browser workflows / JavaScript / accessibility / deep-link review

R14 completed before correction. Browser replay keys used an insecure Math.random fallback; hold and appointment retries did not preserve the original semantic replay key across ambiguous retry; and signed calendar navigation did not enforce same-origin at the browser edge. The post-review batch uses Web Crypto only, gives each displayed slot and appointment intent a stable retry key until success/context change, and validates signed calendar destinations against the current origin.

R14 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R15.**


## R15 — public clinic discovery / cursor / cache / slot-search review

R15 completed before correction. Guest rate limiting was verified as per-user/per-IP and the signed keyset cursor matches `updated_at DESC, id DESC`. The supported defect was a repository-read failure family: clinic collection, clinic/branch/service projection and availability-rule reads could flatten SQL failure into empty/null state. Public discovery could therefore cache a false empty 200, return a false 404/partial projection, or slot search could advertise no availability after a database failure. The post-review batch records repository read failures across nested hydration and makes public discovery/projection and slot search propagate them explicitly rather than cache or project false absence.

R15 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R16.**


## R16 — cron / CLI / maintenance / observability review

R16 completed before correction. WP-CLI outbox could report success on a WP_Error, the CLI health command did not fail its exit status on unhealthy state, overall health ignored cron/legacy-system-check failures, and top-level cron/shutdown outbox errors could be returned without a guaranteed operational log. The post-review batch makes CLI outcomes authoritative, folds cron/system checks into health and wraps scheduled/opportunistic execution with explicit failure logging.

R16 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R17.**


## R17 — package / version / documentation / release-evidence review

R17 completed against the R16-corrected state before any R17 source change. The deterministic builder/verifier remained exact-commit/runtime-derived, but source, contracts and package tests still declared 1.2.14 while readme description/install guidance still referred to 1.2.13. The corrected source therefore lacked a truthful new release identity. The post-review batch advances the runtime to 1.2.15, aligns package tests/readme/STATUS/CHANGELOG and leaves schemas unchanged. R18-R20 remain required fresh closure reviews.

R17 result: **SUPPORTED RELEASE/EVIDENCE DEFECT FOUND — corrected together after review completion; full retest required before R18.**


## R18 — broad plan-to-code / repository-evidence / hygiene review

R18 completed against the R17-corrected state before any R18 correction. FR-001…018, NFR-001…010 and FUT-01…24 traceability remained present; deterministic candidate building is allow-listed to runtime content and binds exact commit/runtime in its generated manifest. Three repository/evidence defects remained: the temporary `t15-probe.yml` was still an active branch workflow, the old `CORRECTIVE-STATUS.md` described v0.2.1 under a misleading current-state heading without an explicit historical banner, and the master-plan gate checked 1.2.15 while reporting a stale 1.2.13 success label. The post-review batch removes temporary probe tooling, marks historical evidence unmistakably and makes the test label truthful.

R18 result: **SUPPORTED REPOSITORY/EVIDENCE DEFECTS FOUND — corrected together after review completion; full retest required before R19.**


## R19 — first fresh corrected-state closure review

R19 completed on exact R18-corrected HEAD `7894adbd9d19b58956342f66bd7f00e8226413ce` before any R19 change. Source/security/concurrency/privacy/integration/release hygiene were freshly re-traced; only the canonical quality workflow remained, the historical 0.2.1 status was clearly labeled historical, current version traceability was 1.2.15, and no new supported product/repository defect was proven. No correction was required and the exact source state was preserved for R20.

R19 result: **CLEAN — no correction required.**

## R20 — second fresh closure review

R20 completed against the same exact corrected product/source state before any R20 correction. No new product-code, authorization, privacy, security, concurrency or package-builder defect was proven. The review did find closure/release evidence lag: `readme.txt`, `README.md` and `STATUS.md` still described already-completed closure rounds as pending, and PR #7 still advertised v1.2.13 / the thirteenth cycle / obsolete HEAD and artifact evidence. The repository-document findings are corrected together after R20 closure; PR metadata is aligned separately after the corrected commit because PR metadata is not repository source.

R20 result: **CLOSURE-EVIDENCE DEFECTS FOUND; NO NEW PRODUCT DEFECT — repository evidence corrected together after review completion; full retest required.**

## Main 20-round result

- Defect/gap rounds: **R1, R2, R3, R5, R6, R7, R8, R10, R11, R12, R13, R14, R15, R16, R17, R18, R20**.
- Clean rounds: **R4, R9, R19**.
- Runtime after main cycle: **1.2.15**; schemas unchanged at core **3.2.0**, continuity **1.1.0**, Future24 **1.0.0**.
- Two extra post-correction verification sweeps are required because R20 changes closure evidence; they are not counted as additional main rounds.


## Post-R20 verification restart record

- Sweep A on `0ddc816cba623404e79cf130491e6b77553b12c7`: PASS. R20 changed only closure documentation/permanent regression evidence relative to the R18-reviewed product tree; no runtime PHP/JS changed and only the canonical quality workflow remained.
- Sweep B on the same HEAD: found one evidence-only contradiction inside `STATUS.md`: its lower Fifteenth main-cycle closure was current, while the upper classification table and R17 checkpoint still said R19/R20 were pending.
- The contradiction is corrected after Sweep B. No product runtime or schema change is made. Two final fresh read-only verification sweeps are restarted on the corrected status HEAD and are not counted among the 20 main review rounds.
