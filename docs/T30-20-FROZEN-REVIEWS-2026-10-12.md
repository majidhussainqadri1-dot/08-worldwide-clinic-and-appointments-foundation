# File08 T30: twenty frozen File08 / central / companion source checks BEFORE corrections
**Audit date:** 2026-10-12. Frozen newest integrated File08 Draft PR#15 exact development HEAD `03a376e3aab37e191eeb32fbf50910e644095f30`; previous exact SHA green GitHub Actions `38280748850`. Main `70541974ce0ffb16aebef557c3016eb7447662f4` remains distinct and previous Draft PRs #12–14 unmerged. GitHub source != installed Hostinger code.

## Governing documents (actually supplied)
**File08 Worldwide Clinic and Appointments Complete Master Plan v1.1 / Future24 Amended 2026-08-10**, FR001–018, NFR001–010; particularly **F08-FR010** owned, private, object-scoped patient/doctor/staff appointment dashboards, **F08-NFR001** object/field authorization with File00 claims, **F08-NFR004** bounded query and cursor pagination, invalid/duplicate/degraded safe failure, no cache leakage. **Sabri Social Homeopathy Platform Three Central Plans Consolidated Governing Master Plan** version 3.0: File00 identity, File07 discovery, File09 verified doctor state, File17 communication, File19 notifications, File20 shell, File24 privacy/security, File25 UI, File26 search, CF01 restricted clinical data, independent exact deployed reality. Provided companion File07/File09/File14 plans consulted for canonical discovery, verification and booking CTA contracts. Source presence is not a deployed version parity test.

**Methodology:** 20 **distinct bounded topical** source-to-plan checks on exact PR#15 HEAD, completed BEFORE any application-code changes. Rounds 1–18 inspect relevant native owner code paths and current contract versions, not 18 full end-to-end workflow executions. Rounds 19–20 examine actual PHP input flow through REST, dashboard, native list methods, signed HMAC cursor and bounded page-size parser. No unsupported claims of 20 complete independent full-code audits.

| # | Governing owner | Source focus | Frozen result |
|---|---|---|---|
| 01 | F08-FR001 File07/09 | Clinic and branch identity/doctor authority / `WCA_Service create/activate and SWC_Doctor_Authority` | Owner code present; runtime not verified |
| 02 | F08-FR002/016 | Services, currency, fee zero commission / `WCA_Service save_service and WCA_Contracts` | Core code present; provider validation outstanding |
| 03 | F08-FR003/ NFR010 | Provider/patient time zone DST and availability / `WCA_Service set_availability/local_datetime` | Code present; browser+DST staging outstanding |
| 04 | F08-FR004/005 | Bounded holds, idempotency, request admission / `WCA_Repository hold / WCA_Appointment_Command` | Code present; real MySQL race tests outstanding |
| 05 | F08-FR006–009 | Appointment status transition, reschedule, no-show / `WCA_Service transition_appointment` | Code present; real workflow staging outstanding |
| 06 | F08-FR012 File00 | Verified guardian, current File00 relation / `WCA_Authorization guardian_context/claims` | Code present; installed File00 unverified |
| 07 | F08-FR001 File09 | Verified/reinstated/renewal_due doctor authority / `SWC_Doctor_Authority File09 bound v1.3.0` | Current repository owner contract aligned |
| 08 | F08-NFR009 File00 | Current SMC identity contract and professional role / `SWC_Doctor_Authority File00 contract v1.2.3` | Current repository owner contract aligned |
| 09 | F08-FR017 CF01 | Clinical record ownership and care context 1.1.0 / `SWC_CF01_Care_Context` | Provider/consumer source versions aligned |
| 10 | F08-FR013 File17 | Opaque appointment reference and restricted room context / `WCA_Service and WCA_Calendar_Link` | PR12/15 opaque correction retained |
| 11 | F08-FR016 File19 | Canonical notification producer/ingestion / `WCA_Outbox` | PR12/15 contract retained; actual installed File19 unverified |
| 12 | F08-FR010 File20 | Private schedule and dashboard views / `WCA_Query_API list_clinic_schedule/render_dashboard` | Methods exist, input shape reviewed |
| 13 | F08-NFR001 File24 | Current object/field authorization, private cache / `WCA_Authorization and WCA_Query_API response` | Guards exist; IDOR staging outstanding |
| 14 | F08-FR001 File07/26 | Public clinic only, discovery ranking owner separate / `SWC_Public_Clinic` | Owner projection exists |
| 15 | F08-CEN File14 | Public clinic booking CTA to canonical booking / `WCA_Routes /appointments/book/` | Route exists |
| 16 | F08-FUT001–024 | Future24 capability registry and schema boundaries / `WCA_Future24::capabilities` | 24 capabilities present; not independently proven complete |
| 17 | F08-NFR007–010 | Source/core schema 3.4.0 and Future24 1.1.0 / `WCA_Contracts, migrations` | Repository source version only, DB unverified |
| 18 | F08-NFR005 | No-orphan 100 PHP suites and versioned reproducible ZIP / `T28 test coverage & independent candidate verifier` | Exact prior T29 HEAD CI green; no live evidence |
| 19 | F08-NFR001/004 File24/20 | Opaque private query cursor from REST and dashboard / `WCA_Query_API REST and dashboard + decode_cursor` | T30-D01: untyped cursor cast/sanitize/trim converts bad input before signed-validation or silently drops it |
| 20 | F08-NFR004 bounded queries | per_page malformed structured/zero/garbage inputs / `WCA_Query_API page_size() and public list methods` | T30-D02: filter_var false silently substitutes default 20; invalid page-size becomes a valid signed context |

## Two source-level defects proven, to be fixed AFTER ledger freeze
**T30-D01 — malformed private cursor normalization / non-scalar query input.** `WCA_Query_API::rest_appointments` and `rest_clinic_schedule` cast arbitrary `get_param('cursor')` to string then sanitize. Dashboard `render_dashboard` feeds `wp_unslash($_GET['schedule_cursor'])` to `sanitize_text_field`, and `decode_cursor` trims a signed cursor. Consequently structured arrays/objects can trigger PHP diagnostics or be silently turned into an empty/default cursor and signed-but-noncanonical whitespace/markup can be normalized before verification. This is not an HMAC forgery or proof of live unauthorized access; it breaks exact private query identity and fail-closed NFR001/NFR004 handling. Fix: validate cursor original input as either absent/empty or a proper unchanged bounded canonical string; never cast or sanitize the supplied cursor, and refuse malformed query shape before any database-read. Preserve valid generated signed cursor.

**T30-D02 — malformed page-size fallback.** Native `WCA_Query_API::page_size` uses `filter_var( $value,FILTER_VALIDATE_INT )` and returns 20 on failure. A submitted nested array, malformed string or non-integer parameter silently runs and signs the default 20-row query rather than returning an explicit client error. Fix: preserve omitted/null default and valid bounded positive canonical integers; reject malformed structured/text/negative/fractional inputs with `wca_query_page_size_invalid` 400 in both REST and native endpoints, before SQL. Explicit oversize handling follows existing bound (max 50) so no unbounded query.

**After-review corrective scope:** only Query API input boundary; no changes to File00 professional authority/CF01/File09/File17/File19/SQL migrations. Add PHP7.4/8.3 compatible actual-method regression for REST and source paths, malformed arrays/objects, valid cursor round trips, valid default/limited page size, early no-query behavior; include in 100-suite T28 no-orphan runner, exact-head full CI and double identical ZIP + independent verifier.

## Release blockade and separate truths
No File08 plugin deployed, merged or DB migrated by this review. Repository software `1.2.15`, repository Core schema `3.4.0`, Continuity/Future24 schema/contracts `1.1.0` unchanged. Actual active Hostinger File08/00/03/07/09/17/19/20/24/25/26/CF01 plugin checksums/runtime/logs, physical MySQL/wp_options/schema/migration state, real IDOR patient/guardian/staff/doctor, RTL/low-bandwidth browser, restore, 2 fresh staging cycles, Founder approval/deploy/live retest all unverified.
Five separate realities: Repository PR15 exact HEAD frozen; Deployed Version UNVERIFIED; Physical DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification Status NOT PERFORMED.
**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
