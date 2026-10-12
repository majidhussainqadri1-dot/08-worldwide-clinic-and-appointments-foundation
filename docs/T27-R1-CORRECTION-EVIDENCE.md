# File 08 T27-R1 — post-freeze correction and pending exact-source QA evidence
**Date:** 12 October 2026 (Pakistan). **Frozen reviewed main SHA:** `70541974ce0ffb16aebef557c3016eb7447662f4`. Review ledger committed **before source patch**: `docs/T27-R1-TWENTY-REVIEW-FROZEN-LEDGER.md`. Earlier green main Actions run cannot establish correctness of the corrected development branch.

## Corrections after completion of all 20 source reviews
- **T27-D01** `WCA_Query_API::decode_cursor`: preserved existing HMAC/context binding, but stopped converting signed malformed JSON version, actor and last row ID through `absint`. Decoder now requires a **JSON object with exactly the six keys emitted by encode_cursor**, integer version 1, positive integer exact actor ID and exact positive row ID, string scope and exact bound filter hash. A signed legacy malformed cursor cannot be silently rebound to another patient's/scoped schedule position.
- **T27-D02** Native `cursor_time_valid`: require **exact canonical, real UTC `YYYY-MM-DD HH:mm:ss`** including calendar validity, no sanitizer-dependent changes, no arbitrary date string, no oversized/noncanonical hex. The two native list/schedule endpoints already rely on this shared decoder and private/no-store scope; no duplicate File20 shell, File00 identity, File26 discovery or File17 communications data created.
- **Test** `tests/t27-r1-query-cursor-integrity-regressions.php` invokes actual `encode_cursor`, `decode_cursor`, `cursor_time_valid` through Reflection with PHP7.4-compatible fixture. Checks valid patient/clinic round-trip, denied signed wrong-type/negative/string/float/boolean IDs and sort times, wrong actor/scope/filter, surplus fields/root lists, invalid calendar dates, HMAC tampering, oversized and alternate encodings. Wired into **canonical `tests/run-all.php`**. Isolated source PHP assertions, **not** actual WordPress/Hostinger testing.
- Software package candidate retains existing project `1.2.15` **until governed version promotion**; immutable commit and generated candidate manifest/checksum independently bind each changed source. Source Core File08 schema `3.4.0`; Restricted Continuity and Future24 contracts `1.1.0`. No DB schema/API version, data rows, migration, companion files or deployed site changed.
- Prior T26 CI and package are **historical** after this source change. Exact new SHA plus complete PHP 7.4/8.3 test run, JavaScript, source hygiene and **deterministic double build + independent package verifier** needed for a new green status. CI results will be recorded in the draft PR only after the GitHub runner completes.

## Mandatory Real Staging and Live acceptance
1. Freeze active Hostinger File08 plugin version/folder/runtime hash (and File00/01/03/07/09/17/19/20/24/25/26, CF01), actual PHP/WP configuration and logs.
2. Inspect `wp_options`, physical DB/table schema/version, migration status/rows, backup and isolated restore; establish installed candidate parity before debugging any reported live symptom.
3. Verify **actual logged-in** owner/patient/guardian/doctor/delegated staff schedules; HMAC tamper, cross-user/cross-clinic/cross-purpose cursor, malformed type/date inputs, rollback, timezone/DST, RTL and low-bandwidth browser behavior with real test data.
4. Two **fresh** staged review/fix/retest rounds after final code, zero known blockers, documented Founder release approval, exact installer package checksum, and production post-deploy smoke/monitoring/parity before any Resolved claim.

| Required live-first truth | Status |
|---|---|
| Repository HEAD | Frozen main `70541974ce0ffb16aebef557c3016eb7447662f4`; T27 final branch SHA/checksum will be frozen by CI/PR |
| Deployed Version | UNVERIFIED |
| Physical DB Version | UNVERIFIED; repository schema 3.4.0 is not proof of installed DB |
| Migration State | UNVERIFIED |
| Live Verification Status | NOT PERFORMED |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
