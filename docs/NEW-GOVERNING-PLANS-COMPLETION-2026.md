# File 08 — New Governing Plans Completion 2026

## Governing scope

This addendum records the repository implementation performed after the newly supplied central governing plan and the newly supplied **File 08 — Worldwide Clinic and Appointments Complete Master Plan 2026** were re-read as fresh governing sources.

It does **not** redefine staging, live deployment or operational acceptance. Those remain separate evidence states.

## Source baseline

- Previous complete candidate: `6e9367430bc8f1ad956d4fe32b6223ba4eeb7727`
- Previous runtime header: `1.0.1`
- Previous core schema: `3.1.0`
- New implementation branch: `codex/file08-new-governing-plans-completion-2026`
- New continuity extension schema: `1.0.0` (additive File 08-owned restricted subdomain)

The runtime header remains `1.0.1` while this is an unaccepted source candidate; the exact commit SHA and generated artifact are the release identity. A later release/version promotion must be a controlled decision after staging acceptance.

## Gap closure against the new plans

### Central laws

| Requirement | Repository implementation |
|---|---|
| CEN-GOV-001 | `WCA_Central_Governance::manifest()` publishes the governing addendum identity and evidence-state separation. |
| CEN-OWN-001 | File 08 writes only its own clinic/appointment/continuity domain; companion modules receive versioned events/projections. |
| CEN-BIZ-001 | No Free/Pro/Premium/paywall gate is introduced in File 08. |
| CEN-DON-001 | File 26 projection explicitly declares `paid_boost=false` and `donor_boost=false`; File 08 actions do not inspect donor state. |
| CEN-BRAND-001 | Fallback primary token changed to exact `#087A4E`; File 25 remains visual-token owner. |
| CEN-NAV-001 | No second navigation/shell was created; File 20 remains owner. |
| CEN-AGE-001 | Protected patient/guardian actions now recheck a File 00 age/guardian claim and fail closed when age eligibility cannot be established. |
| CEN-PRI-001 | New continuity payloads are encrypted at rest, no-store/noindex, participant scoped and purpose limited. |
| CEN-MED-001 | Pre-visit intake reuses the native emergency red-flag diversion before sensitive data is persisted. |
| CEN-RANK-001 | File 08 exports factual projection fields only; no cure/outcome, donor, paid or secret ranking signal. |
| CEN-REL-001 | CI, deterministic packaging and two fresh post-change review rounds are required before repository completion is reported; staging remains external. |
| CEN-STATUS-001 | Documentation keeps coded/package/QA/staging/live/operational states separate. |
| CEN-ACC-001 | Continuity surfaces use semantic controls, live status regions, reduced-motion inherited CSS, RTL-safe layout and responsive controls. |
| CEN-SEARCH-001 | `wca.file26-clinic-projection` provides public-safe freshness data and emits `File26.SearchProjectionChanged.v1`; File 08 creates no search/ranking database. |
| F08-CEN-01 | `tests/central-plan-2026.php` asserts central owner/free/green/privacy/safety/File26 boundaries. |
| F08-CEN-02 | Native continuity writes remain File 08-owned; cross-file behavior is events/helpers only. |

### Central-value clinic capabilities

| Capability | Closure |
|---|---|
| CV-188 Clinic discovery | File 26 projection contains verified public clinic identity, languages, branches/services, canonical route and freshness with no clinical-outcome ranking. |
| CV-189 Appointment lifecycle | Existing authoritative state machine, holds, idempotency, reschedule/cancel/check-in/complete/no-show remains canonical. |
| CV-190 Timezone/calendar | Existing DST-safe slots and private ICS calendar remain canonical. |
| CV-191 Pre-visit intake | Added encrypted purpose-limited intake with emergency diversion, participant authorization, draft/submit lifecycle and no-store REST/UI. |
| CV-192 Consent/context | Added teleconsult, recording, messaging, privacy and follow-up consent grant/revoke with versioned scope. |
| CV-193 Consultation room context | Added File 17 context contract with appointment participants, relationship state and separate messaging/call/recording authorization; File 17 still owns transport. |
| CV-194 Follow-up/reminders | Added doctor-defined encrypted follow-up plan, approved resource references and privacy-minimal File 19 reminder event. |
| CV-195 Emergency diversion | Existing booking diversion is extended to pre-visit intake. |
| CV-196 Review eligibility | Existing completed-appointment review eligibility remains authoritative and single-use/expiring. |

## File 08 master-plan parity

The previous candidate already covered all `F08-FR-001` through `F08-FR-018` and `F08-NFR-001` through `F08-NFR-010`. This change does not replace that implementation. It closes the newer cross-plan additions that were not present in the earlier candidate, specifically age/guardian recheck, exact Sabri Green fallback, File 26 projection, encrypted pre-visit intake, expanded context consent, File 17 consultation authorization and follow-up/reminder continuity.

## Privacy and clinical boundary

New restricted continuity data is never exposed through public clinic projections. The new tables store ciphertext rather than plaintext clinical payloads. Public search exports no patient, appointment, intake, consent, follow-up or clinical-context content. File 17 receives authorization context only and remains the sole owner of messages/calls. File 19 receives a privacy-minimal reminder request only and remains notification owner.

## Migration and rollback

The continuity extension is additive and idempotent through `dbDelta`. It does not destructively alter existing File 08 tables. Deactivation is non-destructive. Destructive purge is not added. Real restore, rollback, key-decrypt and upgrade rehearsals remain staging gates and must use the exact candidate artifact.

## Acceptance evidence still external

Repository source and automated CI cannot prove Hostinger behavior, real companion package parity, real database migration, cache behavior, SMTP/provider behavior, browser/device accessibility, backup restoration, rollback or Founder acceptance. Therefore successful CI may classify this branch as **coded + packaged + automated-QA green**, not staging-accepted/live-deployed/operational.
