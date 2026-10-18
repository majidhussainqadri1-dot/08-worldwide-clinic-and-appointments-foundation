# File 08 — T27 forty scoped source checks (2026-10-18)

## Method and limit

Baseline: repository main commit `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03`, runtime `1.2.16`. Compared File 08 governing plan (F08-FR-001–018, F08-NFR-001–010, Future24 amendment), consolidated platform master plan and File 00/03/07/09/14/17/19/20/24/25/26/CF-01 ownership boundaries. These are **40 distinct focused code-presence/contract-path inspections, not 40 complete end-to-end implementation audits or 40 production executions**. A located source path is not proof of functional acceptance. The exact-head GitHub CI and real WordPress staging/live evidence are separate.

| Check | Plan / owner | Inspected coverage | Located source paths | Result |
|---|---|---|---|---|
| 01 | F08-FR-001 | Clinic/branch identity | `includes/class-wca-schema.php`; `includes/class-wca-repository.php` | Path located; runtime unverified |
| 02 | F08-FR-002 | Service/fee and zero-commission path | `includes/class-wca-repository.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 03 | F08-FR-003 | Availability/timezone/DST path | `includes/class-wca-repository.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 04 | F08-FR-004 | Slot search/hold path | `includes/class-wca-repository.php`; `includes/class-wca-rest.php` | Path located; runtime unverified |
| 05 | F08-FR-005 | Appointment request | `includes/class-wca-appointment-command.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 06 | F08-FR-006 | Doctor decision state | `includes/class-wca-service.php`; `includes/class-wca-authorization.php` | Path located; runtime unverified |
| 07 | F08-FR-007 | Rescheduling and compensation hooks | `includes/class-wca-service.php`; `includes/class-wca-repository.php` | Path located; runtime unverified |
| 08 | F08-FR-008 | Cancellation/release state | `includes/class-wca-service.php`; `includes/class-wca-repository.php` | Path located; runtime unverified |
| 09 | F08-FR-009 | Check-in/complete/no-show state | `includes/class-wca-service.php`; `includes/class-wca-repository.php` | Path located; runtime unverified |
| 10 | F08-FR-010 + integrity | Dashboards and idempotent concurrency | `includes/class-wca-idempotency.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 11 | F08-FR-011 | Emergency diversion | `includes/class-wca-frontend.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 12 | F08-FR-012 | Versioned consent | `includes/class-wca-continuity-secure.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 13 | F08-FR-013 | Clinical relationship context | `includes/class-wca-service.php`; `includes/class-wca-continuity-secure.php` | Path located; runtime unverified |
| 14 | F08-FR-014 | Real appointment review eligibility | `includes/class-wca-service.php` | Path located; runtime unverified |
| 15 | F08-FR-015 | Calendar/ICS/provider hooks | `includes/class-wca-calendar-link.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 16 | F08-FR-016 | Fee and payment adapter | `includes/class-wca-service.php`; `includes/class-wca-opaque-api.php` | Path located; runtime unverified |
| 17 | F08-FR-017 | Restricted clinical/continuity boundary | `includes/class-wca-continuity-secure.php`; `includes/class-wca-continuity-guards.php` | Path located; runtime unverified |
| 18 | F08-FR-018 | Complaint/dispute path | `includes/class-wca-service.php` | Path located; runtime unverified |
| 19 | FR cross-cutting | Protected native read authorization | `includes/class-wca-authorization.php`; `includes/class-wca-opaque-api.php` | Path located; runtime unverified |
| 20 | FR cross-cutting | Erasure/legal-hold state | `includes/class-wca-privacy.php`; `includes/class-wca-retention-policy.php` | Path located; runtime unverified |
| 21 | F08-NFR-001 | Object/field permission path | `includes/class-wca-authorization.php`; `includes/class-wca-query-api.php` | Path located; runtime unverified |
| 22 | F08-NFR-002 | Privacy lifecycle | `includes/class-wca-privacy.php` | Path located; runtime unverified |
| 23 | F08-NFR-003 | Retry/outbox/idempotency | `includes/class-wca-outbox.php`; `includes/class-wca-idempotency.php` | Path located; runtime unverified |
| 24 | F08-NFR-004 | Bounded pagination | `includes/class-wca-query-api.php`; `includes/class-wca-repository.php` | Path located; runtime unverified |
| 25 | F08-NFR-005 | Accessibility and RTL surface | `includes/class-wca-frontend.php`; `assets/js/clinic.js` | Path located; runtime unverified |
| 26 | F08-NFR-006 | Logs/metrics/trace/health | `includes/class-wca-observability.php` | Path located; runtime unverified |
| 27 | F08-NFR-007 | Migration and core schema | `includes/class-wca-schema.php`; `includes/class-swc-activator.php` | Path located; runtime unverified |
| 28 | F08-NFR-008 | Operational CLI and repair | `includes/class-wca-cli.php`; `includes/class-wca-observability.php` | Path located; runtime unverified |
| 29 | F08-NFR-009 | WordPress/PHP compatibility metadata | `worldwide-clinic.php`; `.github/workflows/file08-complete-quality.yml` | Path located; runtime unverified |
| 30 | F08-NFR-010 | Localization/timezone presentation | `includes/class-wca-frontend.php`; `assets/js/clinic.js` | Path located; runtime unverified |
| 31 | File00 | Identity/guardian claims | `includes/class-wca-central-governance.php`; `includes/class-wca-service.php` | Path located; runtime unverified |
| 32 | File03 | Public profile adapter | `includes/class-wca-file03-adapter.php` | Path located; runtime unverified |
| 33 | File07 | Directory compatibility | `includes/class-wca-compatibility.php` | Path located; runtime unverified |
| 34 | File09 | Doctor verification reconciliation | `includes/class-wca-verification-reconciliation.php` | Path located; runtime unverified |
| 35 | File14 | Opaque post-commit appointment-start outcome | `includes/class-wca-service.php`; `tests/t27-r1-file14-owner-outcome-regressions.php` | Path located; runtime unverified |
| 36 | File17 | Appointment-bound virtual-room context | `includes/class-wca-future24.php` | Path located; runtime unverified |
| 37 | File19 | Notification ownership/outbox | `includes/class-wca-outbox.php`; `includes/class-wca-compatibility.php` | Path located; runtime unverified |
| 38 | File20/25 | Shell/visual-token boundary | `includes/class-wca-central-governance.php`; `includes/class-wca-future24.php` | Path located; runtime unverified |
| 39 | File24 | Privacy/security assurance boundary | `includes/class-wca-central-governance.php`; `includes/class-wca-privacy.php` | Path located; runtime unverified |
| 40 | File26/CF-01 | Discovery invalidation and care context | `includes/class-wca-verification-reconciliation.php`; `includes/class-swc-cf01-care-context.php` | Path located; runtime unverified |

## Additional Future24 inventory check

The canonical `includes/class-wca-future24.php` contains all 24 approved `F08-FUT-01` through `F08-FUT-24` identifiers. This establishes trace presence, **not** independent proof that all 24 workflows satisfy their acceptance and negative-test obligations on Hostinger.

## Frozen defect ledger before correction

- **T27-D1 — Release-currentness drift:** plugin header/constant and GitHub exact HEAD were `1.2.16`, while README/STATUS/CHANGELOG/readme.txt/release status and traceability still represented `1.2.15` as current in parts. Public install instructions even requested the older candidate. Risk: wrong package and false parity conclusion.
- **T27-D2 — Obsolete test/current-cycle assertion:** T26, although historical after the 1.2.16 File14 integration, was still described as current and enforced by historical tests. Risk: false release-truth gates and failures after valid new integration.
- **T27-D3 — Legacy test branch assumptions:** historical T18/T19 tests assumed a fixed `review/file08-*` working branch rather than canonical main exact HEAD. Risk: spurious failure when repository release origin changes.

## Correction and evidence law

Correct affected current-facing docs, preserve historical T26 rounds, retire stale historical test expectations, add permanent v1.2.16 current-release regression, and require exact corrected-head CI+deterministic package. Source audit does **not** authorize production deployment, claim migration completion or supply DB/version information from live. `Repository HEAD / Deployed Version / DB Version / Migration State / Live Verification` must be reported separately. No new appointment business logic or live deployment is authorized by this ledger.
