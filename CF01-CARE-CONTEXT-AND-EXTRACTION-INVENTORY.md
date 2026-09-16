# File 08 ↔ CF-01 Care-Context and Extraction Inventory

**Status:** Coding and contract-review candidate. This document does not authorize migration, patient tables, clinical routes, real clinical data, staging acceptance or production use.

## 1. Canonical boundary

File 08 remains the canonical owner of clinics/doctor scheduling surfaces, appointment requests, availability, assignment/reassignment, appointment status, scheduling times, consultation mode, scheduling notifications and appointment audit history.

CF-01 may become the canonical owner of longitudinal patient charts, treating relationships, clinical intake/history, encounter notes, observations, prescriptions, follow-up outcomes, clinical attachments, clinical consent/directives, access history and clinical retention only after all C1-A activation gates are accepted.

**An appointment—even when accepted or completed—is evidence of scheduling/contact only. It is not a treating relationship, clinical consent, prescription authority or chart-access grant.**

## 2. Current storage and write-path inventory

### 2.1 Appointment object

Current canonical appointment object:

- WordPress post type: `swc_appointment`;
- private/publish post record and `post_author` patient reference;
- appointment metadata under `_swc_*`;
- optimistic concurrency through `_swc_record_version`;
- status transitions through File 08 handlers and helper state law;
- audit table: `${wpdb->prefix}swc_audit_log`.

### 2.2 Scheduling fields that remain File 08 truth

| Field/store | Meaning | Future owner decision |
|---|---|---|
| post ID / `_swc_patient_user_id` | appointment participant reference | File 08 scheduling truth; CF-01 consumes opaque subject references only |
| `_swc_doctor_id` / `_swc_proposed_doctor_id` | assigned/proposed practitioner | File 08 scheduling truth; practitioner eligibility remains Files 00/03/07/09 |
| `_swc_status` | appointment lifecycle | File 08 |
| `_swc_consultation_type` | online or in-person scheduling mode | File 08 |
| `_swc_preferred_at_utc`, `_swc_patient_timezone` | selected appointment time/context | File 08 |
| `_swc_proposed_at_utc`, `_swc_proposed_timezone`, `_swc_proposed_expires_at` | reschedule proposal | File 08 |
| `_swc_appointment_duration` | scheduled duration | File 08 |
| `_swc_reassignment_reason`, `_swc_reassignment_expires_at` | scheduling reassignment workflow | File 08; reason must remain scheduling-only |
| `_swc_record_version` | File 08 concurrency version | File 08 |
| doctor availability user meta `_swc_available_days`, `_swc_start_time`, `_swc_end_time`, `_swc_timezone`, `_swc_duration`, `_swc_online`, `_swc_in_person`, `_swc_accepting`, `_swc_unavailable` | published availability | File 08 |
| audit status/assignment/version facts | scheduling accountability | File 08, privacy-minimized |

### 2.3 Contact/location fields

`_swc_country`, `_swc_city`, `_swc_phone` and `_swc_whatsapp` currently support appointment communication and locality. They are not clinical chart facts. Their future normalization against canonical profile/contact owners requires a separate migration decision; this candidate neither duplicates nor migrates them.

### 2.4 Clinical-like or mixed-purpose fields requiring controlled extraction review

| Field | Current risk/classification | Extraction decision |
|---|---|---|
| `_swc_reason` | presenting-concern narrative; potentially clinical | candidate for CF-01 intake after lawful activation; never returned by the care-context contract |
| `_swc_concern_duration` | symptom/concern duration; potentially clinical | candidate for CF-01 intake after lawful activation |
| `_swc_doctor_private_note` | free-text private note; may contain clinical assessment | high-priority extraction/decommission candidate; no automatic conversion to signed encounter |
| `_swc_patient_message` | mixed scheduling/clinical free text | requires classification/redaction; message text is not automatically a chart note |
| audit `note`, `reason`, `details_json` | may inherit free-text scheduling or clinical-like content | requires row-by-row classification and privacy-minimized projection |

No clinical-like field may be copied by a generic backfill. Each record requires an approved source-field mapping, provenance, consent/legal basis, treating-context decision, target type and reconciliation result.

## 3. Consent law

Current `_swc_consent_at` and `_swc_consent_version` record appointment-processing consent/acknowledgement only.

They do **not** prove:

- clinical treatment consent;
- guardian clinical consent;
- consent to publish a case;
- research consent;
- consent to AI processing;
- consent to transfer records;
- consent to prescribe or share clinical attachments.

The care-context contract therefore returns `clinical_treatment_consent=false` and `publication_consent=false` regardless of appointment status.

## 4. Contract output law

Contract `swc.cf01.care-context` version `1.0.0` may return only:

- opaque appointment reference;
- File 08 record version and appointment state;
- File 00 opaque patient/practitioner subject UUIDs;
- consultation mode and bounded scheduling time where accepted/completed;
- explicit scheduling-only relationship state;
- explicit false clinical-authority flags;
- appointment-processing consent scope and version.

It never returns reason, concern duration, private note, patient message, phone, WhatsApp, city/country, audit narrative or other clinical/contact content.

## 5. Current structural gaps

The current File 08 baseline has no independent canonical clinic/location entity referenced by appointment UUID. Clinic/location presentation is distributed across doctor/profile metadata and appointment locality fields. Therefore the contract returns empty `clinic_reference` and `location_reference` with `clinic_location_modeled=false`; inventing a reference is prohibited.

The current File 08 baseline also has no canonical treating-relationship state machine. That state must be created by CF-01 after activation and may not be inferred from `requested`, `accepted` or `completed` appointment status.

## 6. Controlled extraction plan

### E0 — inventory freeze

- enumerate every appointment post, `_swc_*` metadata key, audit column, user-meta write path, shortcode/admin-post route and privacy lifecycle;
- record counts, nullability, malformed values, duplicates and owner decision;
- take no migration action.

### E1 — mapping and fixtures

- define versioned source-to-target mappings for each clinical-like field;
- create synthetic fixtures only;
- distinguish scheduling message, patient-reported intake, clinician-authored note and inadmissible/unclassified content;
- define provenance and entered-by/time/source evidence.

### E2 — dry run

- read-only dry run against staging copy;
- no target writes unless the Founder-approved extraction change record explicitly permits a disposable staging target;
- produce counts, hashes, rejection reasons and unresolved classifications.

### E3 — idempotent backfill candidate

- write through CF-01 owner commands only;
- source appointment/reference + source field + source version form the idempotency key;
- no direct target-table write;
- no automatic signed encounter or prescription creation.

### E4 — shadow read and reconciliation

- File 08 remains source truth during comparison;
- compare per-record field mappings, counts, hashes, authorization and deletion/hold state;
- unexplained divergence must be zero before cutover.

### E5 — cutover

- disable File 08 writes to fields whose ownership transfers;
- preserve File 08 scheduling fields;
- expose only opaque CF-01 references where approved;
- deploy with rollback window and monitored reconciliation.

### E6 — rollback/decommission

- rollback restores one writable owner without duplicating truth;
- no revoked access or deleted content is resurrected;
- legacy clinical-like fields become read-only, redacted or securely purged only under approved retention/legal-hold rules;
- audit evidence records every decision.

## 7. Required acceptance tests

1. appointment-only context never grants clinical read/write/prescription/break-glass;
2. accepted/completed appointment still returns `treating_relationship_asserted=false`;
3. appointment-processing consent never becomes clinical/publication consent;
4. wrong patient, wrong doctor, logged-out actor, cross-clinic actor and forwarded reference fail without object-existence leakage;
5. stale File 08 record version fails closed;
6. unavailable/incompatible File 00 identity contract returns `unknown`;
7. contract contains no clinical-like/contact free text;
8. cancelled/declined context is ended and non-authorizing;
9. patient/practitioner UUIDs come only from File 00 provider assertions;
10. missing clinic/location entity remains explicitly unmodeled;
11. replayed or duplicated extraction commands are idempotent;
12. rollback cannot create two writable clinical owners.

## 8. Current decision

The File 08 care-context provider is a scheduling-context candidate only. Clinical-like source fields are inventoried but not migrated. CF01-A-010 remains blocked until the File 08 owner accepts the contract, current repository/data inventory is evidenced, File 00/09 dependencies are immutable, producer/consumer tests and staging migration rehearsal pass, and Founder change control explicitly authorizes extraction or C1-B/C1-G work.
