# File08 T27-R2 — FROZEN twenty-domain plan-to-exact-source audit BEFORE corrections
Date: 12 October 2026 PKT. **The 20 checks below were frozen before ANY T27-R2 source correction**. Not twenty independent exhaustive line-by-line audits, nor real staging/production testing.

## Three distinct GitHub truths
- Exact repository `main`: `70541974ce0ffb16aebef557c3016eb7447662f4`.
- Draft PR **#12** (current cross-file fixes File17/File19), head `1134d81a74a30ae399baf2fd1e28877bef6a09d3`; last exact-head success run `38267822032`.
- Draft PR **#13** (independent patient/clinic query HMAC cursor fix), head `091e69b51aceaf72c9974138fcc336b1f4dacd50`; last exact-head success `38266366395`.
Neither draft PR is merged or deployed. **One PR's green build does not include the other PR's changes.** Frozen audit baseline = PR#12 exact head, compared PR#13 exact changed file and real File19 main `04078025b643ab7696e4cb4e37826bf152defa18`.

## Governing materials
Actually checked the uploaded `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx` and `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026`, with companion File07, File09 and File14 uploaded plans plus GitHub File08/19 source. Governing File08 FR001–018, NFR001–010 and FUT001–024; File08 is sole clinic/appointments owner; File00 claim truth, File09 verification, File07/26 discovery, File17 communications, File19 notifications, File20 shell, File24 assurance, File25 visuals and CF01 clinical records remain independent. No paid/donor visibility advantage, patient public exposure or unapproved deploy.

| # | Plan/owner | Inspected source/check | Frozen result |
|---|---|---|---|
| 01 | F08-FR-001..018 | Clinic identity/services/appointments core method and contract registry | Source source owner methods found; no full E2E claim |
| 02 | F08-FUT-001..024 | Future24 advanced appointment feature manifest and owner boundary | 24 numbered capability declarations present; runtime acceptance unverified |
| 03 | F08-FR-012/FUT-014 & File00 | Age, guardian claims, consent ownership | WCA_Authorization and governance source paths found |
| 04 | F08-FR-006 & File09 | Verified doctor, suspended status reconciliation | Doctor authority source paths found; installed File09 unverified |
| 05 | F08-FR-001 & File07 | Directory projection only; no second doctor registry | Public clinic projection methods found; actual consumer unverified |
| 06 | F08-FR-010 & File03 | Delegated clinic schedule and provider profile adapter | WCA_File03_Adapter method found; installed contract unverified |
| 07 | F08-CEN-01 & File14 | Clinic USP/action deep-link to canonical booking | WCA_Routes public contract found; browser integration unverified |
| 08 | F08-FUT-019 & File17 | Opaque-only appointment and virtual room context | PR12 opaque UUID fixes present in WCA_Service / Calendar; live not verified |
| 09 | F08-FR-016 & File19 | Notification preferences and delivery owner current API | PR12 uses current File19 main producer+ingest APIs; deployed version unverified |
| 10 | F08-NFR-005 & File20 | No duplicate shell, global navigation owner | File08 route content only source path present |
| 11 | F08-NFR-001..003 & File24 | Restricted clinical data no-store, encryption, privacy | Owner guards/continuity source paths found; staging IDOR unresolved |
| 12 | F08-NFR-005 & File25 | RTL/green/accessible tokens and states | CSS/JS source exists; real WCAG verification outstanding |
| 13 | F08-CEN-01 & File26 | Index/discovery projection freshness; no ranking owner copy | Search changed/reconciliation source found; real index recheck outstanding |
| 14 | F08-FR-017 & CF01 | Scheduling relation vs clinical record owner separation | CF01 care context adapter/contract exists |
| 15 | F08-FR-002/016 | 0% commission/provider-ledger owner | Service/payment and contracts source markers found |
| 16 | F08-FR-004..009 | Atomic hold, reschedule and appointment lifecycle | Native service/repository paths found; concurrency unverified |
| 17 | F08-NFR-010 | UTC patient/doctor timezone, DST localization | Availability/UTC methods found; browser DST unverified |
| 18 | F08-NFR-007..009 | Core 3.4.0/Continuity+Future24 1.1.0 and migrator | Repository source versions found; physical installed DB unverified |
| 19 | F08-NFR-001/004 | HMAC patient/clinic cursor integrity across PR12/PR13 | D-R2-01: PR12 latest review candidate still has old unsafe typed decoder; PR13 strict fix isolated in separate unmerged branch |
| 20 | F08-NFR-004 | Cursor producer-consumer parity for historical malformed UTC source state | D-R2-02: PR13 strict decoder rejects malformed signed sort_time while encoder still emits it; both lists can present unusable next cursor |

## Proven code-state discrepancies to correct after this ledger
**T27-R2-D01 (deployment/repository branch integration):** PR #12's File17 opaque-ID and File19 notification fixes and PR #13's signed HMAC cursor fixes are separate green-but-unmerged candidate branches. PR #12 still runs `absint()` on signed JSON version, actor ID and row ID and unbounded/normalized time. This is not proof of HMAC forgery or current Hostinger vulnerability. Correction: combine both source changes on a new PR branch from exact PR#12, retaining File17 and current File19 integrations and restoring all strict query cursor regression tests from PR#13. No automatic merge into main/live.

**T27-R2-D02 (native cursor producer/consumer contract conflict):** PR #13's `WCA_Query_API::decode_cursor` strictly validates canonical UTC `YYYY-MM-DD HH:mm:ss`, but `encode_cursor` still unconditionally signs any nonempty `t` after sanitization. If historical malformed `_swc_preferred_at_utc` or `post_date_gmt` leaks to cursor generation, the page returns a signed **next_cursor that cannot be consumed**, contradicting F08-NFR-004 stable pagination; no claim that actual DB currently contains malformed records. Correction: ensure both producer and decoder share strict canonical calendar rules, and if source row cannot produce a valid cursor under `has_more`, return an explicit degraded/no-partial-projection error rather than a deceptive successful next link. Test malformed historical source row with native PHP method/test fixture.

## Release constraints
R2 must keep software 1.2.15, source core schema 3.4.0, Continuity & Future24 schema 1.1.0; no DB migration. Frozen review -> one correction batch -> new native test + historic full suite -> exact R2 SHA CI PHP7.4/8.3/byte-identical double ZIP build/independent verifier; new PR draft. Installed Hostinger plugin/file hash PHP/WP, physical DB/wp_options/migration, real guardian/doctor/notifications / calendar/IDOR/RTL, companion exact deployed parity, two independent staged reviews, backups/restore, Founder approval, deployment & live re-test all NOT VERIFIED.

Required five: Repository HEAD known as three independent source truths above; **Deployed Version UNVERIFIED; DB Version UNVERIFIED; Migration State UNVERIFIED; Live Verification Status NOT PERFORMED.** 

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
