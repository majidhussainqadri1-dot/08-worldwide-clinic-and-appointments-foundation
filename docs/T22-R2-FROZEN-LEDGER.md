# T22 R2 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `1ac642d3b41f9ba520ee79293e962e153b733c86` (T22 R1 exact ledger HEAD; canonical run `34379560888` completed successfully before R2 opened).

## Mandatory discipline

This round was completed as a read-only review before this ledger was created. No source, test, package, schema, migration, staging, deployed/live, or database correction was applied while the R2 review was open. This file is the post-review ledger freeze.

## Review scope

Fresh appointment lifecycle review covering canonical reschedule proposal/acceptance, terminal cancellation/decline/no-show cleanup, slot-hold replacement/release, optimistic preconditions, transactional locking, lifecycle event/outbox emission, and audit provenance.

## Frozen defect ledger

### R2-D1 — Canonical appointment transition audit can record the wrong actor

`WCA_Service::transition_appointment( ..., $actor_user_id )` treats the explicit actor as authoritative for authorization, transition-role resolution, and the persisted event record. However its mandatory `SWC_Helpers::audit()` call does not pass that actor, while `SWC_Helpers::audit()` always persists `actor_id = get_current_user_id()`.

Therefore a valid non-browser or explicit-actor invocation (for example a CLI/system/delegated service call where `$actor_user_id` is intentionally supplied and differs from the ambient WordPress current user) can persist lifecycle evidence in which the canonical event actor and mandatory audit actor disagree. This is an evidence-integrity/provenance defect in repository source behavior; it is not evidence of any such mismatch in staging or production data.

Required correction after freeze: make the audit helper accept an explicit actor ID while preserving the ambient-current-user default for legacy callers, pass the canonical transition actor explicitly, and bind a permanent regression proving explicit actor provenance is retained without changing legacy default behavior.

## Evidence-domain boundary

Repository/source candidate truth only. Staging, deployed/live files, production DB/schema contents, executed migration parity, active production configuration, provider behavior, and operational production behavior remain separate and unverified unless independently evidenced.

## Closure gate

R2 is not closed by freezing this ledger. All frozen defects must be corrected as one batch, permanent regression coverage must be added, and the resulting exact HEAD must pass canonical PHP 7.4/PHP 8.3 quality gates plus reproducible independently verified candidate packaging before T22 R3 may begin.
