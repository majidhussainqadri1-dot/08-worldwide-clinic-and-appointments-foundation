# T23 R6 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT-BEARING LEDGER FROZEN**

Review baseline: `af42aabd6900506778d1eeb5f99e587ed0b7dd2d` (T23 R5 clean ledger; canonical run `34891388808` completed successfully).

## Mandatory discipline

R6 was completed as a full read-only privacy/consent/retention/legal-hold review before this ledger was created. No R6 correction was applied while the review was open.

## Review scope

WordPress privacy exporter/eraser registration; subject discovery and cursor safety; appointment/doctor/guardian/proposed-doctor anonymization; continuity/Future24 erasure; consent grant/revoke and current scope state; encrypted continuity data; legal-hold monotonicity; backup-attested destructive purge; retention maintenance; fail-closed database reads/writes; and separation of privacy erasure from canonical appointment lifecycle mutations.

## Frozen defects

### R6-D1 — legacy privacy erasure directly mutates appointment lifecycle state

`SWC_Privacy` is actively registered by `SWC_Plugin`. Its patient eraser directly writes `_swc_status = cancelled` when a patient transition appears locally allowed. That bypasses the canonical appointment transition owner and can therefore skip canonical slot-release, event/outbox/audit, version/state and other lifecycle side effects. Privacy erasure must anonymize/remove subject-linked data without silently performing a partial lifecycle transition.

### R6-D2 — configured retention option advertises terminal-appointment/event day limits that maintenance does not execute

`WCA_Privacy::register_policy()` currently seeds `completed_appointments_days`, `cancelled_appointments_days`, and `events_days`, but `WCA_Privacy::apply_retention()` does not apply those keys. The File 08 governing plan classifies appointment retention as clinical/jurisdiction policy, appointment-event retention as retention policy, and requires retention/legal-hold behavior to be configurable and audited. The runtime must not publish unsupported automatic day-based policy values as though they are enforced.

## Reviewed non-defects

- Scope-wide consent revocation is intentional and transactional.
- Active consent is based on durable appointment/scope grant state; the plan does not support silently invalidating a historically valid patient/guardian grant solely because the original granting actor later loses guardian authority. Current actor/guardian authority is rechecked for new grant/revoke and protected appointment access.
- Continuity and Future24 erasure/retention paths use bounded traversal and respect legal holds.
- Irreversible purge is separately guarded by backup attestation and a full legal-hold inventory.

## Correction gate

After this ledger freeze only:
1. Remove the legacy eraser's direct appointment-status mutation; erasure may anonymize but must not partially transition lifecycle state.
2. Replace unsupported fixed terminal-appointment/event day defaults with explicit external clinical/jurisdiction and audit-retention ownership markers while preserving actually enforced operational retention settings. Existing stale option values must be normalized without deleting legal/audit records.
3. Add permanent regressions covering both defects and bind them into the canonical aggregate suite.
4. Run exact-head canonical PHP 7.4/PHP 8.3, source/JS/hygiene and reproducible candidate-package verification before R7 begins.

## Evidence boundary

Repository/source candidate evidence only. Production privacy requests, actual legal holds, jurisdiction-specific retention decisions, backups, deployed schema and live behavior remain separate evidence domains.
