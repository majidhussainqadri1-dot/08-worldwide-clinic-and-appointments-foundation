# File 08 Master-Plan Traceability — 1.0.0

**Plan:** SSH-F08-PLAN-2026-v1.0  
**Runtime candidate:** 1.0.0  
**Date:** 2026-08-06

| Requirement | Implemented source ownership | Primary evidence | Automated gate |
|---|---|---|---|
| F08-FR-001 Clinic identity | Canonical clinics and branches, draft/review/active/paused/suspended/archived lifecycle | `WCA_Schema`, `WCA_Repository`, `WCA_Service`, REST/routes | static contract + schema tests |
| F08-FR-002 Services and fees | Duration, mode, currency, fee range, tax/refund/cancellation and immutable 0% commission | services table/repository/service command | plan test + zero-commission assertion |
| F08-FR-003 Availability | Time zone, recurrence, breaks, exceptions, buffers, capacity, optimistic versions | availability schema and commands | source contract tests |
| F08-FR-004 Slot search | DST-safe bounded projection, collision checks and freshness version | `WCA_Service::search_slots()` | syntax/static/security tests |
| F08-FR-005 Appointment request | Atomic hold, idempotency, verified doctor, consent and audited request | slot-hold/idempotency tables and command | state/security tests |
| F08-FR-006 Doctor decision | Actor-specific confirmed/declined transitions | `WCA_Contracts`, authorization and transition command | state matrix tests |
| F08-FR-007 Reschedule | Replacement hold, expiry, patient acceptance, slot release/rebook | transition command | state contract tests |
| F08-FR-008 Cancel/no-show | Actor-specific transitions, reason code, slot release and events | transition command | terminal-state tests |
| F08-FR-009 Check-in/completion | Check-in timestamp, actual mode, completion precondition | transition command | state contract tests |
| F08-FR-010 Dashboards | Protected patient appointment and clinic dashboards | canonical routes/frontend/REST | route/access static tests |
| F08-FR-011 Emergency safety | Red-flag diversion; no appointment or delayed case is created | `emergency_red_flag()` | source assertion |
| F08-FR-012 Consent | Version, hash, scope, actor/guardian, legal basis and revocation | consents table/repository | schema/privacy tests |
| F08-FR-013 Clinical relationship | Appointment never asserts treating relationship or chart authority | CF-01 scheduling-context contract | false-authority assertions |
| F08-FR-014 Review eligibility | Granted only after verified completion; single-use/revocable | review eligibility table/command/event | source assertion |
| F08-FR-015 Calendar integration | Private ICS; provider mapping schema and outbox adapter boundary | ICS command/calendar mappings | source assertion/package gate |
| F08-FR-016 Payment bridge | Optional payment intent to CF-03; File 08 owns no ledger; commission zero | payment intents and event bridge | zero-commission test |
| F08-FR-017 Clinical boundary | Opaque, short-lived scheduling context; no narrative/contact leakage | CF-01 provider and inventory | contract/security tests |
| F08-FR-018 Complaint/dispute | Purpose-limited complaint record and CF-02 case request | complaints table/command/outbox | schema/source test |
| F08-NFR-001 Authorization | File 00 claims, File 09 doctor authority, object/field/state checks, step-up | `WCA_Authorization` | REST/security tests |
| F08-NFR-002 Privacy | Minimization, public allow-list, export, erasure, retention, legal hold | privacy classes/contracts | privacy/security tests |
| F08-NFR-003 Reliability | locks, versions, idempotency, outbox retries/dead-letter, slot expiry | repository/outbox/service | source/security tests |
| F08-NFR-004 Performance | bounded pagination/search horizon, indexed schema, async delivery | schema/query limits/outbox | static and staging load gate |
| F08-NFR-005 Accessibility | semantic server rendering, focus, 44px controls, RTL, zoom-friendly CSS, reduced motion/forced colors | frontend/CSS | static + manual staging gate |
| F08-NFR-006 Observability | request IDs, redacted structured logs, metrics, health and circuit breakers | `WCA_Observability` | source + staging operations gate |
| F08-NFR-007 Migration/rollback | schema snapshot, idempotent dbDelta, legacy state migration, metadata rollback, data preservation | schema/compatibility/runbook | CI + staging rehearsal |
| F08-NFR-008 Operability | admin operations, WP-CLI, maintenance, outbox, health | admin/CLI/outbox | CI + staging gate |
| F08-NFR-009 Compatibility | SWC adapters, canonical WCA APIs, versioned manifests/events | compatibility/bootstrap/contracts | source test + cross-repo staging |
| F08-NFR-010 Localization | canonical text domain, translatable strings, RTL CSS | runtime/frontend/CSS | source + Urdu manual gate |

## Definition-of-Done classification

Source implementation, static traceability, syntax, deterministic packaging and automated source checks are governed by CI. Environment-dependent gates remain false until evidence is produced: exact Hostinger staging installation, migration from 0.1.0/0.2.2, restore/rollback, real dependency integration, actual role journeys, delivery/cache, browser/mobile/Urdu RTL/manual WCAG, load, independent privacy/security/professional review, Founder acceptance and production deployment.
