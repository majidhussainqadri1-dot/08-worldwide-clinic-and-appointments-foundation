# T29 — Forty distinct plan/cross-file source inspections, frozen before correction

**Audit baseline:** File08 main `33146c4c969209b37e606bd7a2d11ec723773426` (runtime 1.2.16). Governing basis: uploaded File08 Master Plan v1.1/Future24 amendment, its explicit FR-001..018 and NFR-001..010 catalogue and the consolidated central plan (File08-CEN-01/02, AJ-21/22), plus File00/03/07/09/14/17/19/20/24/25/26/CF01 integration boundaries. This is **40 distinct focused source-path and critical-edge inspections**, not 40 real WordPress installations. Historical T28 already runs the whole automated source suite 40 times; green coverage is not completeness proof.

## Forty inspected axes and freeze results

| ID | Governing scope | Review dimension | Evidence / outcome |
|---|---|---|---|
| R01 | FR01 | Clinic/branch identity | WCA_Repository clinic and branch authority paths located |
| R02 | FR02 | Services/fees | WCA_Service fee/currency snapshot and zero commission located |
| R03 | FR03 | Availability | WCA_Service DateTimeZone, recurring availability/DST source located |
| R04 | FR04 | Slot/holds | WCA_Repository atomic hold, expiry/capacity located |
| R05 | FR05 | Appointment request | **DEFECT D1**: successful replay reaches hold-status checks before completed idempotency proof |
| R06 | FR06 | Doctor decision | WCA_Service confirm/decline transitions located |
| R07 | FR07 | Reschedule | transactional/compensation pathways located |
| R08 | FR08 | Cancel/no-show | release/cancel/no-show pathways located |
| R09 | FR09 | Check-in/completion | completed/checked_in state paths located |
| R10 | FR10 | Dashboards | object-aware protected appointment read paths located |
| R11 | FR11 | Emergency safety | emergency_red_flag / wca_emergency_diversion located |
| R12 | FR12 | Consent | record_consent, versioned snapshots, revoke paths located |
| R13 | FR13 | Care relationship | SWC_CF01_Care_Context class and guardian context located |
| R14 | FR14 | Review eligibility | completed appointment eligibility source located |
| R15 | FR15 | Calendar | signed ICS and provider webhook source located |
| R16 | FR16 | Payment bridge | snapshot-based payment intent source located |
| R17 | FR17 | Restricted records | continuity, legal hold and privacy boundaries located |
| R18 | FR18 | Complaints | appeal/transition/audit paths located |
| R19 | CEN01 | Zero commission | zero-commission fee snapshot path located |
| R20 | CEN02 | Owner command/event | owner event and authorization path located |
| R21 | NFR01 | Object authorization | WCA_Authorization patient/guardian checks located |
| R22 | NFR02 | Privacy lifecycle | WCA_Privacy export/erasure/legal-hold located |
| R23 | NFR03 | Retry/outbox | WCA_Outbox dead-letter and idempotency source located |
| R24 | NFR04 | Bounded performance | query/cursor source located; real p75/p95 unverified |
| R25 | NFR05 | Accessibility | ARIA/keyboard source located; browser/manual validation pending |
| R26 | NFR06 | Observability | redacted metrics/trace source located |
| R27 | NFR07 | Migration/rollback | WCA_Schema and runbook path located; live state unverified |
| R28 | NFR08 | Operability | WP-CLI/health and runbook path located |
| R29 | NFR09 | Compatibility | CI PHP 7.4/8.3 green baseline; WP7.0.1 real host unverified |
| R30 | NFR10 | Localization | RTL CSS source, timezone and i18n paths located |
| R31 | File00 | Identity/guardian | central claims and dynamic guardian source located |
| R32 | File03 | Doctor profile | File03 adapter located |
| R33 | File07 | Directory/discovery | WCA_Compatibility::dependency_health file07 gdd_get_doctor_projection located |
| R34 | File09 | Verification | verified doctor checks and reconciler located |
| R35 | File14 | Owner outcome | post-commit opaque event; no clinical payload located |
| R36 | File17 | Virtual room | Future24 context, File17 transport boundary located |
| R37 | File19 | Notifications | File19.NotificationRequested outbox located |
| R38 | File20/25 | Shell/UI tokens | single-shell and Sabri Green token contract located |
| R39 | File24/CF01 | Clinical assurance | care context, privacy purpose checks located |
| R40 | File26 | Search projection | owner invalidation path located |

## Frozen defect register (after all forty focused inspections)

**T29-D1 — Idempotent booked-appointment replay incorrectly blocked (repository source-confirmed).** `WCA_Service::request_appointment` validates `WCA_Plan_Guard::validate_bookable_hold()` **before** `WCA_Repository::claim_idempotency('request_appointment', ...)`. A successful booking changes the hold from `held` to `booked`; `validate_bookable_hold` strictly rejects non-`held`. Consequently a subsequent identical request cannot reach its stored completed response. `WCA_Appointment_Command::request` also reads current service/hold before the service, so replay may fail when the original service becomes inactive. This violates File08 FR05 and NFR03 and the documented same-key/same-request replay guarantee. The finding is an exact code-order proof, *not* staging/live evidence.

**Acceptance correction design:** add read-only fingerprint-bound completed-response lookup before volatile slot/service checks, with actor/guardian/current consent and request fingerprint safeguards; no new reservation/side effect for existing completed keys; malformed DB state and mismatched key must fail closed; preserve the mutable availability/doctor/teleconsult safeguards for new requests. Add behavior tests for identical replay, hash mismatch, SQL failure, processing and malformed completed response. No live patch until exact deployed artifact is verified.

**No other runtime defect proven** by these 40 targeted source presence inspections. R13, R30 and R33 initially yielded restricted-file search misses; broader exact files `includes/class-swc-cf01-care-context.php`, `assets/css/clinic.css`, and `WCA_Compatibility::dependency_health` resolved those search omissions, not product defects. Real role journeys, cross-plugin execution and production DB migrations remain unverified.

## Stage truth

Repository HEAD at freeze: `33146c4c969209b37e606bd7a2d11ec723773426`. Exact deployed version/checksum: unverified. DB/schema version: unverified. Migration state: unverified. Live verification: unverified. Corrective work may now begin only against this repository candidate and must be followed by fresh exact-head CI/package, staging and live parity checks.
