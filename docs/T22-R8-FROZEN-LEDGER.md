# T22 R8 — Frozen Review Ledger

Status: **REVIEW COMPLETE / DEFECT LEDGER FROZEN**

Review baseline: `fb9f5d69504dfd3415bc0450ee0795730fdbf531` (T22 R7 clean ledger exact HEAD; canonical File 08 Complete Master Plan Quality run `34438714210` completed successfully before R8 opened).

## Mandatory discipline

This round was completed as a read-only review before this ledger was created. No source, test, package, schema, migration, staging, deployed/live, or database correction was applied while the R8 review was open. This file is the post-review ledger freeze.

## Review scope

Fresh clinic-discovery and practitioner-authority review covering authoritative File 00/File 09 practitioner eligibility, clinic-serving delegation currentness, assigned service/availability scope, public clinic/service projection, public slot-query revalidation, File 26 projection truth/freshness, verification reconciliation, delegation revocation/expiry, and search-index invalidation boundaries.

## Frozen defect ledger

### R8-D1 — Practitioner verification reconciliation can publish the wrong whole-clinic eligibility to File 26

`WCA_Verification_Reconciliation::publish_clinic_eligibility()` correctly discovers every clinic affected by the doctor as owner, assigned service practitioner, or availability practitioner. However, for every affected clinic it publishes `eligible = (bool) $eligible`, where `$eligible` is the verification state of the affected practitioner, not the current discoverability state of the clinic.

That is incorrect for non-owner practitioners. Suspending one delegated/assigned practitioner can publish `eligible=false` for an otherwise active clinic whose owner remains eligible and whose remaining public services remain valid. Reverification can conversely publish `eligible=true` for an inactive clinic or one whose owner is no longer eligible. The direct File 26 owner projection already derives current clinic truth through `WCA_Service::public_clinic_projection()`, so the reconciliation event can contradict the owner projection.

Required correction after freeze: derive File 26 `eligible` from the current File 08 public clinic projection for each affected clinic; keep the affected practitioner's eligibility as separate evidence, and fail closed/retry if current projection truth cannot be read safely.

### R8-D2 — Delegation changes can leave File 26 with a stale clinic/service projection

Current public projection and public slot resolution correctly recheck `WCA_Authorization::doctor_can_serve_clinic()` and therefore stop advertising/booking a practitioner after delegation expiry or revocation when File 08 is queried directly. But the active reconciliation layer only listens to File 09 verification events (`wca_doctor_suspended`, `wca_doctor_revoked`, `wca_doctor_verified`) and the File 26 observer only reacts to clinic activation, service changes, and availability changes.

There is no active File 08 invalidation hook for changes to the `_wca_clinic_delegations` user-meta authority source. Therefore revoking, expiring, deleting, or materially changing a delegation without also changing File 09 state, the service row, or the availability rule can leave a previously indexed File 26 projection stale until an unrelated refresh occurs.

Required correction after freeze: observe add/update/delete changes to `_wca_clinic_delegations`, reconcile all clinics still referencing the affected practitioner through ownership/service/availability, and emit a File 26 refresh whose whole-clinic eligibility is derived from current File 08 projection truth. The hook must ignore unrelated user meta and must not broaden authority.

## Evidence-domain boundary

Repository/source candidate truth only. This review does not establish File 26 live consumer behavior, production cache/index freshness, deployed File 00/File 09 parity, production user-meta values, production DB/schema contents, executed migrations, or live delegation/revocation propagation. Those remain separate evidence domains unless independently evidenced.

## Closure gate

R8 is not closed by freezing this ledger. Both frozen defects must be corrected as one batch, permanent regression coverage must be added, and the resulting exact HEAD must pass canonical PHP 7.4/PHP 8.3 quality gates plus reproducible independently verified candidate packaging before T22 R9 may begin.
