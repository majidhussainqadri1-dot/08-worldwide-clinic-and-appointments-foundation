# File08 T29 — 20 frozen File08/central/companion scoped plan-to-exact-source checks
**Review timestamp:** 2026-10-12 Pakistan. **Frozen inspected parent:** File08 PR #12 SHA `0dbfddc789f295b30b9aa1b71cb7ec4fda443bff`, Actions run `38279807202` GREEN on this exact older HEAD. Separately compared Draft PR#14 SHA `1792903d7fad5c006443830ef2136236fc36d5d1`, green run `38278112659`. **Main still** `70541974ce0ffb16aebef557c3016eb7447662f4`. These are three different source truths; none is deployed.

## Governing written sources
- Supplied *File 08 — Worldwide Clinic and Appointments Complete Master Plan v1.1 Future24 Amended 2026-08-10*; FR001–018, NFR001–010 and F08-FUT001–024, canonical appointment lifecycle, File00/current professional identity, File09 doctor verification, File17 events, File19 notification preferences, CF01 records and release gates.
- Supplied *Sabri Social Homeopathy Platform — Three Central Plans Consolidated Governing Master Plan v3.0* (canonical owner law, dependency truth and Live-First Exact-Deployed-State discipline).
- Supplied companion *File 07 Doctors Directory*, *File 09 Global Doctor Onboarding*, *File14 Global Clinic USP* plans and actual separate File19 GitHub main contract `04078025b643ab7696e4cb4e37826bf152defa18` used for producer/event compatibility. Source documents determine expectations, not installed versions.
- Compared branch content of two independent open drafts: PR12 T28 corrections for File00/09/CF01 and 7 previously-orphan tests; PR14 T27-R2 verified strict signed query cursor. Neither draft merged.

**Method:** 20 **distinct bounded requirement-to-source and cross-repository contract inspections**, all completed before changing any application source on this new branch. In the first 18, named source methods and referenced source contracts are present, **not** full operational acceptances; 19–20 read actual cursor encoder/decoder and compare conflicting PR source SHA. This is not 20 full source-line scans or browser/DB E2E tests. No omitted code is invented from absent search strings.

| Review | Written governing scope | Reviewed source & owner | Frozen result |
|---|---|---|---|
| 01 | F08-FR001 Canonical clinic/branch identity and File07 directory | `WCA_Service create_clinic/create_branch` | Methods present |
| 02 | F08-FR002/016 Service fees, currency and no commission | `WCA_Service save_service / WCA_Contracts` | Methods present |
| 03 | F08-FR003 DST-safe availability and timezone | `WCA_Service set_availability/local_datetime` | Methods present |
| 04 | F08-FR004/005 Slot hold, booking request and idempotency | `WCA_Repository claim_idempotency / Service` | Methods present; real concurrent MySQL not tested |
| 05 | F08-FR006–009 Confirm/decline/reschedule/cancel/check-in/complete | `WCA_Service transition_appointment` | Method and statuses present |
| 06 | F08-FR012 File00 Guardian relationship and consent claims | `WCA_Authorization guardian_context/claims` | Methods present |
| 07 | F08-FR001 File09 Doctor verified, reinstated, renewal due | `SWC_Doctor_Authority state and version range` | T28 improved source contract present |
| 08 | F08-NFR009 File00 SMC identity assertion contract 1.2.3 | `SWC_Doctor_Authority FILE_00_CONTRACT` | T28 exact contract alignment present |
| 09 | F08-FR017 CF01 Restrict clinical record context, SMC CF01 1.1.0 | `SWC_CF01_Care_Context runtime contract` | T28 provider and consumer alignment present |
| 10 | F08-FR001 File07/26 Public clinic projection and data minimization | `SWC_Public_Clinic` | Source owner path present |
| 11 | F08-FR013 File17 Opaque UUID appointment context | `WCA_Service booking events and calendar` | Prior PR12 source correction present |
| 12 | F08-FR010 File19 Canonical producer+ingest notifications | `WCA_Outbox sun_register + sun_ingest` | Prior PR12 current API correction present |
| 13 | F08-FR010 File20 Single shell, private clinician schedule | `WCA_Query_API::render_dashboard/list_clinic_schedule` | Methods present |
| 14 | F08-NFR001 File24 Private/no-store headers, per-appointment read permission | `WCA_Query_API response/can_view_appointment` | Guards observed |
| 15 | F08-CEN File14 Canonical conversion booking route | `WCA_Routes rewrite /appointments/book` | Route present |
| 16 | F08-FR016 Payment provider truth, zero platform commission | `WCA_Contracts payment_statuses/manifest` | Owner contract path present |
| 17 | F08-FUT01–24 Future24 capability registry and workflow ownership | `WCA_Future24::capabilities` | 24 capability registry present |
| 18 | F08-NFR007..010 No-orphan executable test gate and deterministic ZIP | `tests/t28-test-coverage, tools/build-candidate` | T28 guard present; real install unverified |
| 19 | F08-NFR001/004 PR12 vs PR14 signed cursor typed scope identity | `WCA_Query_API::decode_cursor` | T29-D01: strict validated PR14 decoder absent from latest T28 PR12 |
| 20 | F08-NFR004 Encoder / decoder canonical UTC page boundary | `WCA_Query_API::encode_cursor/list_patient/list_clinic_schedule` | T29-D02: latest PR12 signs arbitrary sanitized t; latest PR14 fixes isolated, not in T28 |

## Collected proven gaps, correction AFTER review ledger
**T29-D01 — Latest T28/PR12 no longer shares old PR14 cursor protection.** Source `PR#12 includes/class-wca-query-api.php` (blob `30020b23153cea0464d7d9f128b16317b8d4a5d4`) still calls `absint` on signed JSON values v/a/i, normalizes t, and admits non-canonical HMAC payloads. PR14 hardened strict signed JSON and two private endpoints, but was branched from older PR12 before T28 verified File00+File09+CF01 parity and all-orphan-test coverage. No single green candidate contains all code. Fix: use **latest T28 PR12 head** and transplant PR14's identical tested query file with strict decode/encode and no-partial pagination behaviour, without copying old PR14 versions of unrelated File00/09/CF01 code.

**T29-D02 — Signed cursor encoder/source unsafe relative to decoder in latest PR12.** `encode_cursor` signs a nonempty t after `sanitize_text_field`, potentially constructing a signed cursor from a malformed persisted timestamp whose strict decoder rejects. Import PR14's raw-before-sanitizer calendar validation, and both list functions' 503 fail-closed branch when a required next cursor cannot safely be issued. New tests must execute actual PHP methods with intentionally malformed row fixtures, including cross-user/scope/actor denial. Not proof that Hostinger has malformed rows.

## After-review implementation and release process
- Preserve latest PR12 **exact** File00 1.2.3 identity contract, File09 >=1.1.0 <1.4.0 eligible doctor states and snapshot checks, File00 CF01 1.1.0, File08 CF01 provider/canonical 1.1.0, File17 opaque ref and File19 canonical producer, T28 98 registered tests and no-orphan suite/hardening.
- Include PR14 signed private patient and clinic pagination HMAC/UTC source guards and permanent 48 + 17 native fixture assertions; add one T29 cross-branch regression testing both T28 and T27 simultaneously, capture exact HEAD CI, byte-identical double ZIP and independent verification. Do not falsely mark 20 audits as real live e2e.
- No physical DB migration. Source software `1.2.15`, core source DB schema `3.4.0`, Future24/Continuity `1.1.0` unchanged. Deployed WordPress/Hostinger PHP versions/plugins, physical DB/wp_options/migrations, File00/03/07/09/17/19/20/24/25/26/CF01 parity, real booking/payment/calendar provider/outbox/IDOR/RTL, backups+two fresh staging rounds+Founder acceptance+deploy/live re-test remain unverified.

| Mandatory separate truth | State |
|---|---|
| Repository HEAD | Frozen PR12 T28 `0dbfddc789f295b30b9aa1b71cb7ec4fda443bff`, PR14 `1792903d7fad5c006443830ef2136236fc36d5d1`; T29 new exact SHA pending |
| Deployed Version | UNVERIFIED |
| Physical DB Version | UNVERIFIED (source-only schema 3.4.0) |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**