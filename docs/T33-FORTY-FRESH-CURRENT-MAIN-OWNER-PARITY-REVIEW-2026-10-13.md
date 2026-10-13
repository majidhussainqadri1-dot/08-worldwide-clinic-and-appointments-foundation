# File 08 T33 — 40 fresh current-main source/plan/companion reviews and reconciliation
Date: 2026-10-13. Main HEAD freeze at start: `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03` (source plugin **1.2.16**). Review branch: `review/file08-t33-40-current-main-crossfile-20261013`, PR #23 (draft). Staging/live was NOT inspected or deployed. **Do not present this repository result as deployed parity or live resolution.**

## Authorities and distinction between evidence types
Actual uploaded File08 Master Plan v1.1 with Future24 2026-08-10 amendment, and original requirement tables: 18 FRs, 10 NFRs, 24 Future24 features; consolidated Three Central Plans master-plan plus File07, File09, File14 and File25 plans. Freshly fetched current 1.2.16 repository source and canonical SHA, its independently generated GitHub CI candidate with manifest exact source identity; File00/03/07/09/17/19 companion GitHub sources/contract evidence from the current and recent project audit. GitHub HEAD alone is not the installed code.

**Forty independently recorded audit sweeps** examined 40 *different* requirement areas. On every round the entire candidate's 50 PHP/JS/CSS implementation files were reread, three source-path/contract signals for the given requirement were sought and all 44 runtime PHP files were parsed with `php -l` under PHP 8.4 CLI. Hence **40×44 = 1,760 individual PHP lint executions, 120/120 positive selected source signals, 0 syntax errors** on the corrected candidate. Evidence is in user-delivered machine-readable `file08-t33-final-40-audit.json`, which includes each pass, source path, detected token count and source SHA. These source-presence signals are **not functional completeness tests**, cannot prove all FR/NFRs met, and do not substitute for WordPress/DB/linked-plugin runtime tests.

| Round | Distinct governing requirement focus | Corrected candidate source/syntax |
|---|---|---|
| 01 | FR001 Clinic identity and activation | source-path 3/3; PHP syntax 44/44 |
| 02 | FR002 Services, fees and commission | source-path 3/3; PHP syntax 44/44 |
| 03 | FR003 Recurrence, DST and availability | source-path 3/3; PHP syntax 44/44 |
| 04 | FR004 Slots and time-bound holds | source-path 3/3; PHP syntax 44/44 |
| 05 | FR005 Patient/guardian consent requests | source-path 3/3; PHP syntax 44/44 |
| 06 | FR006 Doctor decision/notifications | source-path 3/3; PHP syntax 44/44 |
| 07 | FR007 Reschedule/slot safety | source-path 3/3; PHP syntax 44/44 |
| 08 | FR008 Cancellation/no-show and outbox | source-path 3/3; PHP syntax 44/44 |
| 09 | FR009 Check-in/completion lifecycle | source-path 3/3; PHP syntax 44/44 |
| 10 | FR010 Patient/clinic dashboards and signed cursors | source-path 3/3; PHP syntax 44/44 |
| 11 | FR011 Emergency diversion | source-path 3/3; PHP syntax 44/44 |
| 12 | FR012 Terms, consent and withdrawal | source-path 3/3; PHP syntax 44/44 |
| 13 | FR013 File17 context versus clinical relationship | source-path 3/3; PHP syntax 44/44 |
| 14 | FR014 Completed-appointment review eligibility | source-path 3/3; PHP syntax 44/44 |
| 15 | FR015 ICS/external calendar semantics | source-path 3/3; PHP syntax 44/44 |
| 16 | FR016 Payment and shared finance ledger | source-path 3/3; PHP syntax 44/44 |
| 17 | FR017 CF01 restricted records | source-path 3/3; PHP syntax 44/44 |
| 18 | FR018 CF02 complaints and appeals | source-path 3/3; PHP syntax 44/44 |
| 19 | NFR001 Object/field IDOR authorization | source-path 3/3; PHP syntax 44/44 |
| 20 | NFR002 Privacy, legal holds and erasure | source-path 3/3; PHP syntax 44/44 |
| 21 | NFR003 Outbox ack/retry/idempotency | source-path 3/3; PHP syntax 44/44 |
| 22 | NFR004 Bounded query/cursor | source-path 3/3; PHP syntax 44/44 |
| 23 | NFR005 RTL, focus and reduced motion | source-path 3/3; PHP syntax 44/44 |
| 24 | NFR006 Structured safe observability | source-path 3/3; PHP syntax 44/44 |
| 25 | NFR007 Schema, migration, rollback | source-path 3/3; PHP syntax 44/44 |
| 26 | NFR008 System health/repair/queues | source-path 3/3; PHP syntax 44/44 |
| 27 | NFR009 Versioned companion contracts | source-path 3/3; PHP syntax 44/44 |
| 28 | NFR010 en-US, Urdu, timezone and currency | source-path 3/3; PHP syntax 44/44 |
| 29 | FUT01–02 Waitlist and flexible windows | source-path 3/3; PHP syntax 44/44 |
| 30 | FUT03–04 Series, resource capacity | source-path 3/3; PHP syntax 44/44 |
| 31 | FUT05–06 Group scope and reschedule | source-path 3/3; PHP syntax 44/44 |
| 32 | FUT07–08 Buffer enforcement/heatmap | source-path 3/3; PHP syntax 44/44 |
| 33 | FUT09–10 Advisory/no-show forecasting | source-path 3/3; PHP syntax 44/44 |
| 34 | FUT11–12 Questionnaire and readiness | source-path 3/3; PHP syntax 44/44 |
| 35 | FUT13–14 Blocking prerequisites/guardian hub | source-path 3/3; PHP syntax 44/44 |
| 36 | FUT15–16 Queue/arrival privacy | source-path 3/3; PHP syntax 44/44 |
| 37 | FUT17–18 Disruption and participant roles | source-path 3/3; PHP syntax 44/44 |
| 38 | FUT19–20 File17 virtual/FHIR bridge | source-path 3/3; PHP syntax 44/44 |
| 39 | FUT21–22 SMART and external busy | source-path 3/3; PHP syntax 44/44 |
| 40 | FUT23–24 Episodes and governance | source-path 3/3; PHP syntax 44/44 |

## Proved current-main reconciliation defects and corrected code

1. **File 00 canonical platform identity**: current 1.2.16 source used obsolete `smc_get_subject_uuid`/`_smc_subject_uuid`. Reconciled to canonical File00 `SMC_CF01_Contract::ensure_subject_uuid`, true UUID validation and fail-closed protected claims. No File08-owned replacement IDs invented.
2. **File09 verified professionals / File00 membership contract**: current File09 1.3.0 allows verified, reinstated and renewal-due under real-positive active verification; stale File08 <1.2.0 range rejected valid practitioners. Corrected bounded <1.4.0, current File00 membership 1.2.3 and CF01 1.1.0 contracts without weakening denial/suspension checks.
3. **File19 notification owner**: obsolete `sn_notify_users` and direct `wp_mail` bypassed canonical notification preferences. Reconciled to real `sun_register_notification_producer` and `sun_ingest_domain_event`, durable message ID, minimal envelope, processed/duplicate receipt and safe retry. File19 delivery still needs real staging evidence.
4. **File17 and calendar public reference**: internal appointment numeric-ID fallback could contaminate emitted File17/ICS state. Required valid canonical opaque public ref, checked before transactional enqueue and ICS. **Main's new File14 `wca_appointment_requested_committed_v1` post-commit observer is explicitly PRESERVED** with its own mainline regression.
5. **File17/File26/CF02/CF03 outbox acknowledgements and dependency health**: handlers previously optional/assumed success. Added positive durable receiver receipt and no successful delivery when no owner reply; updated File00/03/07/17/19 readiness checks to real interfaces, without inferring owner readiness from File08's own hooks. Staging consumer registration/receipt not proved; safe Retry/Dead-letter remains pending until actual owner integration.
6. **Private query cursor, page size and retry-boundary**: adopted previously verified strict cursor producer/consumer, malformed incoming cursor/page rejection without silently broadening query.
7. **Future24 corrupt persisted operations (FUT01, FUT05, FUT07, FUT13)**: waitlist filters, group service/visibility, active buffer policy were previously interpreted as unrestricted/empty when storage corrupted. Validity, temporal bounds and fail-closed 503/skip now explicit. Structured FUT13 blocking prerequisites now preserve nested bounded rule representation with live read and keyset-page integrity; questionnaire stored scope is fail-closed on malformed rows.
8. **CF01 source/manifest parity and QA coverage**: immutable release builder and independent packaged verifier both check CF01 runtime/canonical context contract 1.1.0; restored unexecuted legacy CF01, doctor, public-clinic, File03 and privacy tests, added T27–T32 Native Regression Tests and permanent orphan-test inventory guard. Added T33 explicit File14+owner-version source guard, while retaining mainline File14 test.

## Exact-head verification
Earlier exact corrected source branch package at SHA `3a7eada4d1824ba3a0d612fb9873ea04bca6732e` successfully passed GitHub Actions `38366198160` (PHP 7.4, PHP 8.3 and reproducible independently verified package). Runtime 1.2.16, schema remains 3.4.0. **Source documentation/test commits after that change HEAD; new final exact-head CI must be run and recorded before release completion.**

## Not resolved by a source-level review (mandatory acceptance)
- File17 and File26 external receipt owner handlers, CF02/CF03 consumers, actual File19 preference/delivery, current File00/09 installed verification and File14 event listener **not runtime tested**; testable provider-side adapters/registrations may still need separate owner-repo coding.
- Physical live/staging database, `wp_options` and migration state, installed plugin list/version/checksum, WordPress/PHP configuration/logs, real concurrency, GDPR/legal hold, File14 consent and conversion, WCAG 2.2 AA keyboard/RTL/400% zoom, patient/doctor/guardian complete journeys, backups/rollback and owner signoff not observed.
- **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
- Live-first incident matrix: Repository HEAD = this proposed branch; Deployed Version = UNVERIFIED; DB Version = UNVERIFIED; Migration State = UNVERIFIED; Live Verification Status = NOT PERFORMED. Never claim live resolved without staging/deploy + parity + re-test.
