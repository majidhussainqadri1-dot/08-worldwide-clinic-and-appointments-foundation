# File 08 T32 — Forty new plan/source/cross-file review passes and correction record
Date: 2026-10-13 (Pakistan). **Frozen source baseline:** previous unmerged PR #18 head `258b8e00a4c1ea360802c636c10919bc13a89faa` (File08 candidate runtime 1.2.15, schema 3.4.0, Future24 1.1.0). **Corrected source audited:** `a1af91acdc2b7f2552978415da469fa26b691118`; CI run `38356621317` = SUCCESS (PHP 7.4, PHP 8.3 source QA and independently verified deterministic package), artifact 11791989859, outer archive digest `sha256:e71f3a6edffba939deca91387ee93adff10663ac7622db104bd813220ed1a737`.

## Authorities read and independently checked
1. Attached **File 08 Worldwide Clinic and Appointments v1.1 / Future24 amendment 2026-08-10**, including actual DOCX tables identifying 18 `F08-FR-001…018`, ten `F08-NFR-001…010`, and all 24 approved `F08-FUT-01…24` capabilities. The File 08 plan separates coding-ready specification from actual coded/staged/live evidence.
2. Attached **Three Central Plans Consolidated Governing Master Plan 2026**, `SSH-PMP-2026-v3.0`; File 07 Doctors Directory, File 09 Onboarding and Verification, File 14 Clinic USP and File 25 Global Visual Experience original DOCX. Cross-file canonical ownership is preserved.
3. Fresh GitHub source snapshots checked: File 00 runtime 1.2.44, membership contract 1.2.3/CF01 contract 1.1.0; File 09 1.3.0; File 17 2.1.0; File 07 1.2.1; File 03 1.2.0-rc18. Other consumer plugin runtime configuration, receipt hook registration and actual deployed state **not** evidenced. Historic File19 runtime event API source is in the earlier T27/T28 ledger; do not infer live event receipt.

## Method and strict meaning of 40 rounds
Forty **separate, documented, requirement-scoped static/source/syntax passes**. On EACH round the immutable independently built candidate package's entire **50 runtime PHP/JS/CSS files** was reread, exact referenced plan IDs and two matching code-path signals were searched, and **all 44 runtime PHP files were syntax-linted under local PHP 8.4**. This totals **1,760 php -l executions**. Every pass was distinct in requirement focus (table below). This does **not** mean forty independent complete end-to-end clinical/WordPress/staging/database/browser reviews, nor proof that every requirement's business semantics works. Raw machine-readable pass evidence is stored separately in the task artifact `file08-t32-final-40-review-evidence.json`; runtime tree SHA-256 `c0c26b2b9f631b6ae713952c9982a82cd78672c78d1074356d01eeb886206ec1`. The result is **40/40 source-path presence and syntax PASS**, while manual adversarial code reading and isolated PHP execution found defects below.

| Round | Governing ID(s) | Inspection focus | Corrected exact-source outcome |
|---|---|---|---|
| 01 | F08-FR-001 | Clinics and branches | Source-path PASS; PHP lint PASS |
| 02 | F08-FR-002 | Services/fees | Source-path PASS; PHP lint PASS |
| 03 | F08-FR-003 | Availability/DST | Source-path PASS; PHP lint PASS |
| 04 | F08-FR-004 | Slot search and holds | Source-path PASS; PHP lint PASS |
| 05 | F08-FR-005 | Request/guardian/consent | Source-path PASS; PHP lint PASS |
| 06 | F08-FR-006 | Doctor decision | Source-path PASS; PHP lint PASS |
| 07 | F08-FR-007 | Reschedule | Source-path PASS; PHP lint PASS |
| 08 | F08-FR-008 | Cancellation/no-show | Source-path PASS; PHP lint PASS |
| 09 | F08-FR-009 | Check-in/completion | Source-path PASS; PHP lint PASS |
| 10 | F08-FR-010 | Scoped dashboards | Source-path PASS; PHP lint PASS |
| 11 | F08-FR-011 | Emergency diversion | Source-path PASS; PHP lint PASS |
| 12 | F08-FR-012 | Consent lifecycle | Source-path PASS; PHP lint PASS |
| 13 | F08-FR-013 | Appointment context | Source-path PASS; PHP lint PASS |
| 14 | F08-FR-014 | Review eligibility | Source-path PASS; PHP lint PASS |
| 15 | F08-FR-015 | Calendar/ICS | Source-path PASS; PHP lint PASS |
| 16 | F08-FR-016 | Finance bridge | Source-path PASS; PHP lint PASS |
| 17 | F08-FR-017 | CF01 restricted clinical context | Source-path PASS; PHP lint PASS |
| 18 | F08-FR-018 | Complaint/appeal | Source-path PASS; PHP lint PASS |
| 19 | F08-NFR-001 | Object authorization | Source-path PASS; PHP lint PASS |
| 20 | F08-NFR-002 | Privacy/retention | Source-path PASS; PHP lint PASS |
| 21 | F08-NFR-003 | Outbox/replay | Source-path PASS; PHP lint PASS |
| 22 | F08-NFR-004 | Bounded queries | Source-path PASS; PHP lint PASS |
| 23 | F08-NFR-005 | Accessibility/RTL | Source-path PASS; PHP lint PASS |
| 24 | F08-NFR-006 | Observability/audit | Source-path PASS; PHP lint PASS |
| 25 | F08-NFR-007 | Schema/migration/rollback | Source-path PASS; PHP lint PASS |
| 26 | F08-NFR-008 | Health/operations | Source-path PASS; PHP lint PASS |
| 27 | F08-NFR-009 | Versioned contracts | Source-path PASS; PHP lint PASS |
| 28 | F08-NFR-010 | Localization/timezone/currency | Source-path PASS; PHP lint PASS |
| 29 | F08-FUT-01/02 | Waitlist and flexible windows | Source-path PASS; PHP lint PASS |
| 30 | F08-FUT-03/04 | Series and multi-resource | Source-path PASS; PHP lint PASS |
| 31 | F08-FUT-05/06 | Group booking and safe reschedule | Source-path PASS; PHP lint PASS |
| 32 | F08-FUT-07/08 | Buffer policy and heatmap | Source-path PASS; PHP lint PASS |
| 33 | F08-FUT-09/10 | Advisory/no-show forecast | Source-path PASS; PHP lint PASS |
| 34 | F08-FUT-11/12 | Previsit/readiness | Source-path PASS; PHP lint PASS |
| 35 | F08-FUT-13/14 | Prerequisites/guardian hub | Source-path PASS; PHP lint PASS |
| 36 | F08-FUT-15/16 | Arrival/queue position | Source-path PASS; PHP lint PASS |
| 37 | F08-FUT-17/18 | Disruptions/support person | Source-path PASS; PHP lint PASS |
| 38 | F08-FUT-19/20 | File17 virtual/FHIR | Source-path PASS; PHP lint PASS |
| 39 | F08-FUT-21/22 | SMART/external busy | Source-path PASS; PHP lint PASS |
| 40 | F08-FUT-23/24 | Episodes/governance | Source-path PASS; PHP lint PASS |

## Newly demonstrated defects (NOT already-counted T27–T31 fixes)

**D1 — F08-FUT-07; F08-FR-003/004; F08-NFR-001/003.**
`WCA_Future24::slot_allowed_by_policy()` queried a persisted `policy_active` record but converted corrupt JSON into `[]`, interpreting all buffers as zero and returning true. Under a currently required manager-configured buffer this is an unauthorized fail-open booking/hold path. **Before patch**, an independent PHP test on the immutable T31 package gave: `T32 PASS: Absent policy may use basic slot rules`; then `T32 FAIL: Malformed active policy must block slot`. **Fix after review freeze:** active policy JSON must be an object with `enforcement=server_slot_projection_and_hold_guard` and all four integer, in-range fields or it is blocked, privacy-safe error logging provided. No-policy case preserves normal basic scheduling; an explicit valid zero-buffer policy also remains allowed.

**D2 — F08-FUT-05; privacy/guardian and File00 separation.**
`WCA_Future24::join_group_session()` also treated corrupt persisted group-session JSON as missing service reference and continued with no service/visibility validation. **Fix:** demand a JSON object with string `service_ref` and strict `visibility=participants_private`, or return HTTP 503 `wca_group_session_payload_corrupt` *before* group membership lock or write. Existing File00 canonical UUID and guardian guards remain intact.

**D3 — F08-FUT-01; service/date-window waitlist integrity.**
Waitlist offering read corrupt persisted `payload_json` into an empty object and could offer a released slot to unrelated service/date cohorts. **Fix:** skip and safely log corrupted entries; require object JSON, required service/date fields with correct string types and valid ordered YYYY-MM-DD date window. No automatic booking is introduced.

**Permanent regression:** `tests/t32-persisted-scheduling-policy-integrity-regressions.php` is registered in `tests/run-all.php`, uses runtime reflection against the actual Future24 policy method to check no-policy, corrupt JSON, list-root, missing fields, explicit zero policy, negative buffer, and actual malformed group-join call plus waitlist source guard. The existing T28 zero-orphan gate remains enforced. CI checks PHP 7.4/8.3, JS syntax, source contracts, package reproducibility and independent verification.

**Release-metadata reconciliation only:** README, STATUS, and `docs/RELEASE-STATUS-1.0.0.md` now consistently identify the active T32 review branch. Historical review evidence is not retroactively replaced.

## Strict release and integration boundary
- **This is Draft PR #20, not merged.** Branch derives from still-unmerged T31; main baseline remains `70541974ce0ffb16aebef557c3016eb7447662f4`. All code and package conclusions are source-branch facts only.
- Staging installation, File00/03/07/09/17/19/20/24/25/26 and CF01/CF02/CF03 actual owner-side hooks, File17/File26 durable receipt handlers, query/DB schema and migration options, signed calendar, appointment real-role scenarios, policy corrupt DB fixtures, production caching, 320–1920px/400% RTL accessibility, backup-restore and rollback, founder acceptance, deployed package SHA parity, and **live retest are not verified** and remain release blockers.
- **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
- Incident matrix: Repository HEAD = corrected branch as above; Deployed Version = UNVERIFIED; DB Version = UNVERIFIED; Migration State = UNVERIFIED; Live Verification Status = NOT PERFORMED. No live defect is called resolved.
