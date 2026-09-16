# T25 R8 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `dc85aaae147dbaebb064b9172979df2d9f0a6254` (T25 R7 exact-head canonical run `35054964489` successful: PHP 7.4, PHP 8.3, source/JS/hygiene and reproducible candidate package all green).

## Mandatory discipline

R8 was completed as a full read-only cross-file contract/reliability review before this ledger was created. No R8 correction was started while the review remained open. All proven findings below were collected first; correction starts only after this frozen ledger.

## Review scope

File 08 canonical-owner boundaries; File 26 public search projection and invalidation freshness; File 09 verification/delegation reconciliation; File 17 appointment-context transport boundary; File 19 privacy-minimal notification delivery; CF-01 scheduling-only care-context contract; CF-02/CF-03 bridge ownership; outbox retry/dead-letter semantics; opaque/public identifiers; and canonical appointment lifecycle projection into cross-file contexts.

## Frozen defect ledger

### R8-D1 — Search-projection invalidation can be silently lost after owner commit

`WCA_Central_Governance::observe_outbox_event()` listens for `ClinicActivated.v1`, `ClinicServiceChanged.v1`, and `ClinicAvailabilityChanged.v1` only after the canonical owner mutation has already committed and the original outbox item is being dispatched. It calls `WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', ... )` but ignores a `WP_Error` result. The original outbox item can therefore be finalized as delivered even when the required File 26 freshness invalidation was never durably queued.

This conflicts with File 08's governing reliability law: owner data + required outbox evidence must be persisted atomically; queue/consumer failure must be retryable/bounded/dead-lettered rather than silently lost. The branch-creation path already demonstrates the correct model by enqueueing its File 26 invalidation inside the owner transaction.

**Required correction:** persist File 26 search-projection invalidation in the same owner transaction for clinic activation, service mutation, and availability mutation; retire the post-dispatch re-enqueue observer so duplicate/unacknowledged derived enqueueing is not the correctness mechanism. Add a permanent regression proving the three owner mutation roots bind their File 26 invalidations atomically and that the legacy observer hook is absent.

### R8-D2 — `checked_in` appointment is projected to CF-01 as ended/proposed and loses scheduled time

The canonical File 08 lifecycle defines `checked_in` as a live non-terminal appointment state between `confirmed` and `completed/cancelled/no_show`. `SWC_CF01_Care_Context`, however, currently maps `checked_in` through the fallback branches: `context_state()` returns `ended`, `relationship_state()` returns `proposed_contact`, and `scheduled_time()` suppresses the appointment time because it only allows `confirmed` and `completed`.

This produces an internally contradictory scheduling-only cross-file assertion for an appointment that is actively checked in. It does **not** grant clinical/treating authority, but it misstates the File 08 appointment context CF-01 is allowed to consume.

**Required correction:** keep `checked_in` strictly scheduling-only/non-clinical, but classify it with the active scheduled-contact state and preserve its scheduled UTC time. Add runtime/regression coverage for the checked-in assertion and remove the duplicate `requested` entry in `context_state()` while touching that state map.

## Clean boundaries confirmed during R8

File 09 verification/delegation reconciliation recomputes whole-clinic discoverability and transactionally emits its File 26 projection change; File 17 receives appointment-context references rather than becoming appointment truth; File 19 notification fallback is privacy-minimal and returns delivery failure; CF-03 payment truth remains external with File 08 at zero commission; CF-01 remains scheduling-only and does not gain diagnosis, chart-write, prescription, publication or break-glass authority from an appointment.

## Evidence boundary

Repository/source evidence only. No claim is made here about staging or production consumers, deployed File 17/19/26/CF modules, actual queue delivery, database rows, or live projection freshness.

## Correction gate

Both R8 findings must be corrected as one post-review batch, permanent regressions must be bound into the aggregate test runner, and the corrected exact HEAD must pass canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate-package verification before R9 begins.
