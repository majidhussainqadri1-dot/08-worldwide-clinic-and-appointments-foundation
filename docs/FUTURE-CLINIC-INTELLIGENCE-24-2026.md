# File 08 — Future Clinic Intelligence & Interoperability 24 — 2026

## Governing status

This document records the approved implementation extension requested on 10 August 2026 for **File 08 — Worldwide Clinic and Appointments**. It is additive to `SSH-F08-PLAN-2026-v1.0` and the current central governing plan. It does not transfer canonical ownership from Files 00/07/09/17/19/20/24/25/26 and does not make staging/live claims.

Runtime candidate after this extension: **1.2.0**. Core File 08 schema remains **3.1.0**. Existing restricted continuity schema remains **1.1.0**. Future24 adds an additive operational schema **1.0.0** using `wca_future24_records` for non-clinical operational metadata only.

## Ownership and safety laws

- File 08 owns clinic, availability, appointment, relationship, readiness and scheduling-continuity truth.
- File 00 remains identity/age/guardian owner.
- File 09 remains doctor-verification owner.
- File 17 remains message/call/virtual-room transport owner.
- File 19 remains notification-delivery owner.
- File 20 remains global-shell/navigation owner.
- File 24 remains assurance/security governance owner.
- File 25 remains visual-token owner.
- File 26 remains public search/index/ranking owner.
- Future24 never enables automated diagnosis, automated prescribing, emergency replacement, donor/paid visibility advantage, or hidden patient risk scoring.
- Waitlist, advisor, recurrence and disruption features are offer/suggestion/intention based unless a human user explicitly confirms a scheduling action.

## Implemented Future 24 capabilities

### F08-FUT-01 — Smart Cancellation Waitlist — P0
Patients can create bounded waitlist intents by clinic/service/date window/timezone. Waitlist records expire automatically, never auto-book, and are designed to issue short-lived offers through File 19 rather than silently taking a slot.

### F08-FUT-02 — Flexible Appointment Request Windows — P0
Users can save multiple acceptable UTC appointment windows. The extension validates and stores up to 12 bounded windows as scheduling preferences without altering appointment truth.

### F08-FUT-03 — Recurring / Series Appointments — P0
A completed/active appointment can become the parent of a recurrence intent (`weekly`, `monthly`, `custom_days`) with bounded interval/count. Series are stored as scheduling intents; occurrences are not silently auto-booked.

### F08-FUT-04 — Multi-Resource Scheduling — P0
Clinics can register rooms/devices/equipment/staff-pools/virtual-capacity resources. Resource reservations use database advisory locks plus overlap/capacity checks and remain appointment/clinic scoped.

### F08-FUT-05 — Capacity-Based / Group Appointment Mode — P1
Clinics can create bounded-capacity group sessions. Member joins are idempotent, capacity protected with a lock, and participant identity is not exposed to other participants.

### F08-FUT-06 — One-Tap Safe Reschedule — P0
The extension orchestrates the existing compensation-safe reschedule state machine. A new held slot is proposed first and the existing appointment remains protected if confirmation cannot complete.

### F08-FUT-07 — Smart Buffer & Transition Rules — P0
Clinic managers can centrally set before/after availability buffers and store operational travel-gap / continuous-consultation policies. Active availability rules are version-incremented rather than silently overwritten.

### F08-FUT-08 — Availability Capacity Heatmap — P1
Authorized clinic managers receive privacy-safe day aggregates for total/completed/cancelled/no-show activity. No patient identity or clinical reason is included.

### F08-FUT-09 — Schedule Optimization Advisor — P1
The advisor derives non-clinical scheduling suggestions from aggregate heatmap data. Suggestions are explicitly advisory and never self-apply.

### F08-FUT-10 — Privacy-Safe No-Show Forecasting — P2
Forecasting is aggregate clinic history only. Results are suppressed below a minimum sample threshold (`20`) and never produce patient-level scores, treatment restrictions or ranking penalties.

### F08-FUT-11 — Structured Dynamic Pre-Visit Questionnaire — P0
Clinics can configure service-specific templates using only the approved secure File 08 continuity intake fields. Patient answers remain owned/encrypted by `WCA_Continuity`; the Future24 operational table stores templates, not clinical answers.

### F08-FUT-12 — Appointment Readiness Center — P0
Participants can query a no-store readiness projection covering appointment state, privacy consent, secure pre-visit submission, prerequisite completion and guardian recheck expectation.

### F08-FUT-13 — Prerequisite & Document Rules — P1
Clinics can define service prerequisite labels/types and choose provisional or blocking policy. Actual file storage remains outside this operational table; only evidence references/counts are considered.

### F08-FUT-14 — Family / Guardian Appointment Hub — P0
Verified guardians can receive an appointment hub limited to appointments where they are the recorded guardian. Every protected action still relies on current File 00 guardian/age validation.

### F08-FUT-15 — Digital Check-In & Arrival Queue — P1
Patients/guardians can announce arrival/readiness without changing the clinical appointment state. The queue record is an operational arrival signal only; doctor/staff retain authority for actual `checked_in` lifecycle transition.

### F08-FUT-16 — Privacy-Preserving Live Queue Position — P1
Participants can see only how many appointments are ahead and an approximate delay; other patient identities, reasons and appointment types are not returned.

### F08-FUT-17 — Doctor Delay / Clinic Disruption State — P0
Authorized clinic managers can create bounded disruption windows and rebooking-offer policy. File 08 records the disruption and requests notifications from File 19; it does not silently cancel appointments.

### F08-FUT-18 — Consultation Support Person / Interpreter Role — P1
Patients/guardians can add an appointment-bound, expiring, revocable support/interpreter subject reference. The feature does not grant general account, chart or public-profile authority.

### F08-FUT-19 — Secure Virtual-Room Provisioning Contract — P0
For online/hybrid appointments, File 08 can create a short-lived virtual-room request and emit `File17.VirtualRoomRequested.v1`. File 17 remains the transport owner, and recording is never assumed.

### F08-FUT-20 — FHIR Interoperability Adapter — P1
The extension exposes privacy-safe `Appointment` and `HealthcareService` style DTO projections without replacing File 08’s internal WordPress schema. No external clinical record ownership is assumed.

### F08-FUT-21 — SMART Scheduling Links Compatibility — P1
The adapter provides `find`, `hold`, and `book` compatibility boundaries over canonical File 08 slot search, hold and governed appointment command. External-calendar busy windows are removed from `find` results without becoming source of truth.

### F08-FUT-22 — External Calendar Two-Way Reconciliation — P1
Verified doctors can submit busy-time projections identified by a canonical practitioner reference. Provider tokens are never stored. Busy windows are expiring operational data and used for conflict filtering in the SMART compatibility path.

### F08-FUT-23 — Clinical Episode / Follow-Up Chain — P1
Authorized appointment refs can be linked into a private episode chain. The chain stores linkage only: no clinical narrative, no public timeline and no automated treatment generation.

### F08-FUT-24 — Appointment Intelligence & Interoperability Governance Layer — P0
Every Future24 capability is versioned, purpose-limited and audit-event compatible. The governance contract explicitly declares: no automatic diagnosis, no automatic prescribing, no emergency replacement, no paid/donor visibility advantage, no cross-file table ownership and human-professional control for clinical actions.

## API surface

The additive namespace is `/wp-json/wca/v1/future24/` and includes routes for manifest, waitlist, flexible windows, recurrence, resources/reservations, group sessions, safe reschedule, buffers, heatmap/advisor/no-show forecast, questionnaires, readiness, prerequisites, family hub, arrival/queue, disruptions, support participants, virtual-room requests, FHIR projections, SMART find/hold/book, external busy windows, episode chains and governance status.

All protected Future24 responses use private/no-store/noindex headers and WordPress authenticated nonce flow.

## Calendar browser hardening carried into this extension

The existing signed calendar helper is now loaded by the canonical bootstrap and exposes an authenticated short-lived signing endpoint plus a signed no-store ICS download endpoint. `future24.js` intercepts the legacy browser calendar link and first requests a short-lived participant-bound signed URL, avoiding reliance on a REST nonce in ordinary browser navigation.

## Migration / rollback

- `wca_future24_records` is additive and created through `dbDelta`.
- No existing File 08 core table is destructively changed by the Future24 schema.
- Existing availability rows may be versioned when a clinic manager applies FUT-07 buffer policy.
- Deactivation remains non-destructive.
- Production rollback still requires the exact staging-tested artifact, database backup/restore and companion-package parity evidence.

## Repository acceptance boundary

Implementation in GitHub and green source CI can establish only **Specified + Coded + Packaged + Automated-QA Green** for this candidate. Hostinger staging, exact deployed migration, real companion integration, cache/provider/browser/accessibility testing, Founder acceptance, production deployment and live operational verification remain separate mandatory evidence states.
