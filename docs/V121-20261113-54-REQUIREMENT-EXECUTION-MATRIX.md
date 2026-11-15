# File08 V12.1 — 54 direct requirements execution and acceptance matrix

Frozen source date: 2026-11-13. Canonical governing document: *Sabri Social Homeopathy Platform Master Plan V12.1* pp. 104–110, plus File08 clauses and central privacy/security rules. Source repository baseline `main d086840f7e2ac0ed328006385f064e0e2fb58cb1`. This document is NOT a deployment evidence report.

## Evidence codes
`SRC`: identifier exists in canonical `WCA_Contracts` and source implementation claims; **not equivalent to passing its runtime journey**. `QA`: exact corrected HEAD source CI must be green. `WP`: multi-plugin WordPress/MariaDB staging must be tested. `LIVE`: exact deployed version, DB/migration and real-user smoke/parity required. All rows are currently `SRC; QA varies by branch; WP unverified; LIVE unverified`; DO NOT infer 54/54 operational.

## FR 18
| ID | V12.1 capability | Required real-world acceptance | Evidence state |
|---|---|---|---|
| F08-FR-001 | Clinic identity | verified clinic identity/branch authority | Source catalogued; WP/LIVE pending |
| F08-FR-002 | Services and fees | service, currency, zero commission and policies | Source catalogued; WP/LIVE pending |
| F08-FR-003 | Availability rules | recurring hours, breaks, DST, exceptions and timezones | Source catalogued; WP/LIVE pending |
| F08-FR-004 | Slot search | fresh slots, atomic hold, expiry, concurrency | Source catalogued; WP/LIVE pending |
| F08-FR-005 | Appointment request | idempotent request and same-key completed replay | Source catalogued; WP/LIVE pending |
| F08-FR-006 | Doctor decision | doctor confirm, decline and proposal | Source catalogued; WP/LIVE pending |
| F08-FR-007 | Reschedule | safe reschedule with rollback on failure | Source catalogued; WP/LIVE pending |
| F08-FR-008 | Cancellation/no-show | authorized cancellation, slot release and no-show | Source catalogued; WP/LIVE pending |
| F08-FR-009 | Check-in/completion | check-in, completion, terminal state and review fact | Source catalogued; WP/LIVE pending |
| F08-FR-010 | Dashboards | patient/guardian/doctor/delegate scope and history | Source catalogued; WP/LIVE pending |
| F08-FR-011 | Emergency safety | emergency red-flag immediate diversion | Source catalogued; WP/LIVE pending |
| F08-FR-012 | Consent | versioned consent, revocation and lawful purpose | Source catalogued; WP/LIVE pending |
| F08-FR-013 | Clinical relationship | care relationship limited to appointment scope and CF01 | Source catalogued; WP/LIVE pending |
| F08-FR-014 | Review eligibility | one eligible review after real completed appointment | Source catalogued; WP/LIVE pending |
| F08-FR-015 | Calendar integration | ICS, signed webhook and provider failure | Source catalogued; WP/LIVE pending |
| F08-FR-016 | Fees/payment bridge | CF03 verified payment state, payer scope, 0% commission | Source catalogued; WP/LIVE pending |
| F08-FR-017 | Clinical record boundary | private continuity, patient data isolation and retention | Source catalogued; WP/LIVE pending |
| F08-FR-018 | Complaint/dispute | complaint reason, escalation, evidence and appeals | Source catalogued; WP/LIVE pending |

## NFR 10 — mandatory release gates
| ID | Area | Required acceptance | Evidence state |
|---|---|---|---|
| F08-NFR-001 | Object/field authorization | IDOR/field-level denied paths across actors | Source catalogued; WP/LIVE pending |
| F08-NFR-002 | Privacy lifecycle | export, erasure, consent withdrawal and legal hold | Source catalogued; WP/LIVE pending |
| F08-NFR-003 | Reliability | replay, outbox, dead-letter and recovery | Source catalogued; WP/LIVE pending |
| F08-NFR-004 | Performance | bounded SQL, p75/p95 and cache behaviors | Source catalogued; WP/LIVE pending |
| F08-NFR-005 | Accessibility | WCAG 2.2 AA, keyboard, RTL and 400% zoom | Source catalogued; WP/LIVE pending |
| F08-NFR-006 | Observability | redacted logs, metrics and alerts | Source catalogued; WP/LIVE pending |
| F08-NFR-007 | Migration/rollback | fresh/upgrade, migration markers, backup and rollback | Source catalogued; WP/LIVE pending |
| F08-NFR-008 | Operability | health, WP-CLI, repair and operational runbook | Source catalogued; WP/LIVE pending |
| F08-NFR-009 | Compatibility | real WP/PHP/DB and exact companion parity | Source catalogued; WP/LIVE pending |
| F08-NFR-010 | Localization | Urdu/Arabic/en-US, timezone, dates and currency | Source catalogued; WP/LIVE pending |

## FUT 24 — feature-specific proof (P0/P1/P2 rings govern promotion)
| ID | Capability | Critical acceptance | Evidence state |
|---|---|---|---|
| F08-FUT-01 | Smart Cancellation Waitlist | expired waitlist offers, no auto-book and guardian scope | Source catalogued; WP/LIVE pending |
| F08-FUT-02 | Flexible Appointment Request Windows | bounded flexible UTC windows and overlap | Source catalogued; WP/LIVE pending |
| F08-FUT-03 | Recurring / Series Appointments | per-occurrence approvals and series cancellation | Source catalogued; WP/LIVE pending |
| F08-FUT-04 | Multi-Resource Scheduling | atomic resource capacity, overlap and rollback | Source catalogued; WP/LIVE pending |
| F08-FUT-05 | Capacity-Based / Group Appointment Mode | group join/leave privacy and finite capacity | Source catalogued; WP/LIVE pending |
| F08-FUT-06 | One-Tap Safe Reschedule | keep old slot if reschedule fails | Source catalogued; WP/LIVE pending |
| F08-FUT-07 | Smart Buffer & Transition Rules | buffers, travel transition and bounded rules | Source catalogued; WP/LIVE pending |
| F08-FUT-08 | Availability Capacity Heatmap | aggregate-only capacity, SQL failure, partial breaks and DST | Source catalogued; WP/LIVE pending; corrective draft #45 in QA |
| F08-FUT-09 | Schedule Optimization Advisor | no auto-apply; human advisor only | Source catalogued; WP/LIVE pending |
| F08-FUT-10 | Privacy-Safe No-Show Forecasting | small-cohort suppression, no patient score | Source catalogued; WP/LIVE pending |
| F08-FUT-11 | Structured Dynamic Pre-Visit Questionnaire | encrypted approved intake and consent | Source catalogued; WP/LIVE pending |
| F08-FUT-12 | Appointment Readiness Center | patient-specific readiness currentness | Source catalogued; WP/LIVE pending |
| F08-FUT-13 | Prerequisite & Document Rules | blocking prerequisites not bypassable | Source catalogued; WP/LIVE pending |
| F08-FUT-14 | Family / Guardian Appointment Hub | current File00 guardian validity every action | Source catalogued; WP/LIVE pending |
| F08-FUT-15 | Digital Check-In & Arrival Queue | arrival signal separate from checked-in state | Source catalogued; WP/LIVE pending |
| F08-FUT-16 | Privacy-Preserving Live Queue Position | queue position without peer identifiers | Source catalogued; WP/LIVE pending |
| F08-FUT-17 | Doctor Delay / Clinic Disruption State | outage notices without automatic cancellation | Source catalogued; WP/LIVE pending |
| F08-FUT-18 | Consultation Support Person / Interpreter Role | time-limited verified interpreter/support scope | Source catalogued; WP/LIVE pending |
| F08-FUT-19 | Secure Virtual-Room Provisioning Contract | File17 short-lived virtual room and recording false default | Source catalogued; WP/LIVE pending |
| F08-FUT-20 | FHIR Interoperability Adapter | FHIR bounded read DTO, opaque refs only | Source catalogued; WP/LIVE pending |
| F08-FUT-21 | SMART Scheduling Links Compatibility | SMART Find/Hold/Book gates, no bypass | Source catalogued; WP/LIVE pending |
| F08-FUT-22 | External Calendar Two-Way Reconciliation | external calendar busy projection, never canonical truth | Source catalogued; WP/LIVE pending |
| F08-FUT-23 | Clinical Episode / Follow-Up Chain | episode linkage same patient/clinic/doctor | Source catalogued; WP/LIVE pending |
| F08-FUT-24 | Appointment Intelligence & Interoperability Governance Layer | 24/24 governance manifest and human control | Source catalogued; WP/LIVE pending |

## Central File08 commands
| ID | Critical acceptance | Evidence state |
|---|---|---|
| F08-CEN-01 | Canonical File08 ownership, 0% commission, green style, privacy/safety and File26 discovery owner contracts | Source/cross-file review pending real staging |
| F08-CEN-02 | Versioned owner create/update/delete/events, read/index/cache auth recheck, outbox deduplication | Source/cross-file review pending real staging |

## Concrete ordered execution tickets
1. **P0**: Live artifact checksum/version, DB/schema/migrations, Hostinger access/log evidence. Absent; no live conclusion.
2. **P1**: 30 legacy PRs classified in `V121-20261113-OPEN-PR-TRIAGE-REGISTER.md`; current source divergence frozen in related ledger. Do not bulk merge.
3. **P2–P3**: Validate source claims per row and isolate unsupported code. The current T44–T47 Heatmap correction is a draft #45, not production.
4. **P4**: Final exact-head CI, 40 entire suite passes, package manifest+hash and two fresh reviews after final runtime source change.
5. **P5–P7**: Real multi-plugin WordPress/MariaDB, roles, cache, payment/notification provider outages, DST, accessible browser and privacy/backup/restore.
6. **P8–P9**: Staging accepted by founder, controlled deploy, live runtime parity, signed acceptance and post-deploy monitoring.

## Truth columns for every later status
`Requirement ID / Source file+line+SHA / Behavior test ID+run / Staging evidence ID / Live evidence ID / Defect ID+root cause / Corrected exact SHA / Status (Unverified, Failed, Corrected-Source, Staging-Pass, Live-Resolved)`.
