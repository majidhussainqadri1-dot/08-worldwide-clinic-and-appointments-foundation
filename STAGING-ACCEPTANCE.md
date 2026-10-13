# File 08 Staging Acceptance Record

This checklist must be completed against the **exact final File 08 candidate commit and exact CI artifact** on canonical Hostinger staging. Production approval is prohibited until every required gate passes and evidence is recorded. Repository/CI evidence must never be treated as deployed staging or live evidence.

## Package and environment

- [ ] Exact candidate commit SHA recorded.
- [ ] Exact CI run ID and artifact ID recorded.
- [ ] Outer artifact digest and inner candidate SHA-256 recorded.
- [ ] Candidate manifest commit/version/file hashes match the deployed plugin package.
- [ ] Full staging backup created and restoration tested.
- [ ] Exact companion versions recorded for Files 00, 03, 07, 09, 17, 19, 20, 24, 25, and 26.
- [ ] WordPress, PHP, database, theme, LiteSpeed, Hostinger runtime, timezone, cron and mail/provider configuration recorded.
- [ ] File 08 core schema `3.4.0`, restricted continuity schema/contract `1.1.0`, Future24 additive schema/contract `1.1.0`, migration/options state and relevant tables/columns verified from the actual staging database.

## Installation, upgrade, migration and rollback

- [ ] Fresh installation activates without warning, fatal error or partial schema.
- [ ] Real upgrade from the deployed predecessor runs all required migrations exactly once and preserves valid existing data.
- [ ] Existing unrelated pages/options are never overwritten.
- [ ] Repair/upgrade routines are idempotent.
- [ ] Deactivation does not destroy or silently mutate retained data.
- [ ] Backup restoration and rollback are verified against the exact staging database and encryption-key state.
- [ ] Explicit purge/uninstall behavior is tested only on a disposable backup-restored staging copy.

## Identity, guardian and authorization

- [ ] Founder/doctor eligibility consumes current File 00/File 09 truth and fails closed when dependencies are unavailable.
- [ ] Fully verified doctor is eligible; incomplete, pending, suspended/revoked and ordinary-member accounts are denied where required.
- [ ] Age/guardian requirements are re-evaluated against current File 00 guardian authority at protected actions.
- [ ] Patient sees and mutates only their own appointment objects.
- [ ] Current authorized guardian can act only within the permitted patient/appointment scope.
- [ ] Doctor sees only assigned/authorized appointments.
- [ ] Delegated clinic staff see only appointments/clinics covered by the exact active delegation scope.
- [ ] Administrator/clinical-governance access requires the correct capability and purpose limitation.
- [ ] Generic WordPress/REST/search/cache paths cannot expose private appointments, native IDs, private notes, intake or consent evidence.

## Canonical routing and legacy-surface migration

- [ ] Canonical appointment detail route is `/appointment/{public_ref}`.
- [ ] An old `/appointments/{opaque-uuid}` detail URL redirects to the singular canonical route and does not expose native IDs.
- [ ] Legacy numeric browser mutation actions/shortcodes are disabled by default.
- [ ] If an explicit temporary migration filter is enabled, its use is documented, time-bounded and separately security-reviewed before production.

## Scheduling, timezone and state integrity

- [ ] Display-timezone date searches return all valid slots even when practitioner/branch and patient calendar dates differ across UTC boundaries.
- [ ] DST gaps are rejected and repeated-hour ambiguity is handled deterministically/fail-closed according to the current rule contract.
- [ ] Cross-timezone/DST-boundary slot holds revalidate against the current authoritative availability projection.
- [ ] Out-of-window, stale, expired, mismatched-service, mismatched-practitioner and cross-clinic slot evidence fails.
- [ ] Unsupported consultation mode fails.
- [ ] Simultaneous contention for a final slot allows at most one valid booking path.
- [ ] Appointment transition requests require current expected status and record version; stale transitions fail with no overwrite.
- [ ] Every allowed actor transition passes; every forbidden transition fails.
- [ ] Terminal completed/cancelled/declined/no-show records do not revive through ordinary transitions.
- [ ] Compensation-safe rescheduling preserves the original booking if replacement commit fails.

## Replay, idempotency, rate and abuse controls

- [ ] Canonical core mutation requests require an explicit idempotency key.
- [ ] Same key + same full request replays the original successful response without duplicate state effects.
- [ ] Same key + materially changed route/URL/query/body is rejected as a conflict.
- [ ] Concurrent duplicate requests do not create duplicate clinics/branches/services/holds/appointments/complaints/transitions/payment intents.
- [ ] Failed mutations release or safely expire their idempotency claim rather than permanently poisoning retries.
- [ ] Bounded rate limits return a controlled `429` with retry guidance and do not leak protected data.
- [ ] File08 Future24/continuity routes retain their own canonical guards without conflicting double ownership from the core-mutation guard.

## Clinic, branch, availability and payment scope

- [ ] Availability cannot be assigned to a globally eligible doctor who has no current authority to serve the selected clinic.
- [ ] Clinic owner, self-doctor, scoped delegation and explicit platform-administrator cases behave according to policy.
- [ ] Branch creation produces `ClinicBranchChanged.v1` audit/domain evidence and a File 26 search-projection invalidation request without writing File 26 tables directly.
- [ ] Payment-intent creation is allowed only for the patient or current authorized guardian of the appointment and still requires object authorization.
- [ ] Doctor/staff/admin viewing authority alone is insufficient to create a payer intent.
- [ ] Platform commission remains `0%`; donation state never changes visibility or booking authority.

## Privacy, audit, cache and integrations

- [ ] Private doctor/admin notes never appear in patient output, email, File 19 notification bodies or File 26 public projection.
- [ ] Structured audit/event evidence records relevant actor/object/state/trace data without leaking prohibited clinical narrative.
- [ ] Privacy export contains all applicable user records/fields; erasure traverses the complete ownership graph and accurately reports retained legally held/anonymized data.
- [ ] Retention jobs use real schema fields and respect active legal holds.
- [ ] File 17 receives appointment/participant/virtual-room context only within its transport ownership boundary.
- [ ] File 19 receives privacy-minimal notification intents and provider failures follow retry/dead-letter policy.
- [ ] File 26 receives public-safe factual projection changes only; no donor/paid/outcome ranking signal is introduced.
- [ ] LiteSpeed/Hostinger/browser caches never cache protected request, appointment, dashboard, continuity or Future24 private pages/responses.
- [ ] Protected pages/responses carry effective `private, no-store` and `noindex` controls.

## Interface and accessibility

- [ ] File 20 renders the only global navigation/header; no duplicate shell is introduced by File 08.
- [ ] Sabri Green design token `#087A4E` is used where File 08 owns local fallback styling; superseded orange primary guidance is not used.
- [ ] 320–1920 px viewport matrix, mobile-first reflow and horizontal-overflow checks pass.
- [ ] Urdu/Arabic RTL and American English LTR layouts pass.
- [ ] Keyboard-only operation, visible focus, accessible names, field labels, error recovery and minimum 44 px targets pass.
- [ ] Text/control contrast meets applicable accessibility requirements, including forced-colors/high-contrast modes.
- [ ] 200–400% zoom/reflow and reduced-motion behavior pass.
- [ ] Public and private/admin pagination pass, including delegated-staff datasets.
- [ ] Empty/loading/conflict/stale/expired/rate-limited/provider-error states are understandable and recoverable.

## Final authorization

- [ ] Two fresh post-final-runtime-code review rounds are complete after the final source change, with any finding corrected before the next round.
- [ ] Exact-final-HEAD GitHub Actions source tests and deterministic package job are green.
- [ ] Founder acceptance recorded.
- [ ] Production package checksum/manifest and deployment/rollback plan recorded.
- [ ] Live deployment explicitly authorized.
- [ ] After deployment, exact deployed version/files/checksum, DB/schema/migration state and active configuration are frozen and compared to the approved candidate.
- [ ] Live real-role re-test and parity confirmation pass before the release is marked Operational/Resolved.


## T29 mandatory installed-state parity acceptance after T28/T27 cursor integration
**Source-only T29** baseline PR#12 latest `0dbfddc789f295b30b9aa1b71cb7ec4fda443bff` with PR#14 strict cursor code; this is NOT an installed release. Before touching production, independently inventory active Hostinger File08 ZIP/root/source hash and PHP/WordPress runtime, DB physical tables/columns/indexes/wp_options/migration checkpoints/rows, backup and isolated restore; check exact File00 (SMC 1.2.3 + CF01 1.1.0), File09 valid reinstated/renewal_due (with live sanctions/revocation), and File17/19/03/07/14/20/24/25/26/CF01 installed versions and contracts. Repo version cannot stand for deployed plugin or schema state.

On genuinely installed staging with two independently fresh adversarial cycles: real patient/guardian/doctor/delegated-staff authenticated private appointments and doctor/clinic schedules; HMAC wrong-scope, cross-user, cross-clinic, boolean/fractional/negative/string page IDs, forged signature; canonical leap-day UTC & invalid source timestamp/no partial pagination; verify no private/IDOR leakage and no-store/noindex. Continue prior T28 98+ executable PHP cases with explicit File00/09/CF01 contract parity; File17 no native appointment ID fallback and File19 registered producer ack, durable duplicate, opt-out, outage retry. Real MySQL concurrent hold/reschedule, File09 suspension, File00 guardians, File25 RTL/mobile, rollback and backups. Require Founder authorization and exact ZIP/live parity plus post-deploy retest before declaring resolved.


## T30 — Private Appointment/Clinic Query Shape & Cursor Parity Gate
On genuine installed WordPress staging after exact File08/dependency/DB/version parity freeze, test actual authenticated File00 patient/guardian/verified doctor/delegated clinic member `/wca/v1/appointment-refs`, `/wca/v1/clinic-refs/{uuid}/schedule` and `/clinic/dashboard/` with `cursor[]=...`, JSON cursor object, scalar non-string/boolean, whitespace-padded signed cursor, HTML-wrapped signed cursor, garbage and oversized cursor. Verify **explicit 400 fail closed before SQL/private response**, no PHP 7.4/8.3 diagnostics and no opaque pagination rebinding. Test `per_page[]=2`, `per_page=abc`, `per_page=0`, fractions, negative and noncanonical values; ensure explicit 400 rather than silently querying 20; omitted/default valid and 51 bounded to 50. Dashboard malformed schedule_cursor or clinic_ref should show an error without switching silently to a different clinic. Retest valid signed cursor two-page pagination, current File00/09/CF01 authorized owners, File17/19 events, private no-store/noindex, IDOR and rate limits, timezone/DST, RTL/accessible mobile. Verify physical wp_options/schema/migrations, backups and independent restore, complete two fresh adversarial staging rounds, Founder sign-off, approved deployment and live post-deploy re-test. GitHub CI alone is not acceptance.

**T30 post-freeze PR16 external-owner parity addendum:** On installed staging verify actual current File00 `SMC_CF01_Contract::ensure_subject_uuid` returns an owner-issued UUID for eligible actors; do not accept a local File08 subject metadata fallback. Verify real File17 context and File26 projection, CF02 support and CF03 finance consumer subscriptions/acknowledgements on event enqueue/retry: no outbound event may mark the File08 outbox complete without a durable true external owner receipt; absent providers must remain retriable with bounded attempts/dead letter, not spin or claim delivered. Inspect File19 current ingestion status separately. For optional providers not installed, release must decide if degraded queued outbox is acceptable; confirm actual installed File00/17/19/26/CF02/CF03 versions, wp_options/migration/backup and user consent. Repeat in two separate fresh staging review rounds before Founder signoff.

## T31 Future24 Group Membership / File00 canonical subject / historical payload acceptance
On independently frozen installed Hostinger staging File08 exact package SHA, File00 installed SMC_CF01_Contract, File09 verified doctor and CF01/Future24 schema versions:
1. Authenticated patient and eligible guardian join valid Future24 group with live File00-issued canonical subject UUID; inspect private records to show member_ref exactly equals File00 canonical ID, group capacity/idempotency and audit recorded; no raw WordPress user ID/other patient's private identity leaked.
2. Disable/deny File00 canonical subject issuer in an isolated staged failure simulation (without modifying production roles). A failed subject provision or malformed/empty returned UUID must produce explicit 503 with no group member row, no occupied group capacity, no semantic lock leaks and no synthetic File08 UUID fallback. Retest valid patient and guardian session after repair.
3. Safely seed disposable Future24 historical records with valid {} and [], valid named operational JSON, malformed/scalar/nonempty positional-list JSON. All corrupt rows must return a safe 503/no partial private/group metadata and a privacy-minimal logged audit, without silently rewriting data; legitimate empty and valid maps must persist and project normally.
4. Exercise waitlist/group/resource and other shared public_record DTO call sites to ensure fail-closed error propagation, no unexpected 500/PHP diagnostic, no false capacity/booking state, no File17/19 notification side effect. Test real MySQL transactions and concurrency, multilingual RTL/browser accessibility, aged guardian revocation and restricted privacy.
5. Freeze physical tables/columns/rows/wp_options/migration state before diagnosis. Backup and independently restore, perform two fresh post-fix staging review/retest cycles, get Founder approval, then deploy/checksum parity and live re-test before claiming Resolved. Source PHP CI cannot establish any of these live facts.


## T32 FUT11/13 stored policy integrity, blocked readiness and keyset performance — 13 October 2026
On the **exact installed** File08, File00/09/17/19/20/24/25/26/CF01/CF02/CF03 staging package and physical DB frozen separately from GitHub, verify:
1. With authorized clinic owner and actual File00 current guardian/doctor claims, save a FUT13 rule policy with 1 *blocking* document and 1 provisional nonclinical prerequisite. Inspect actual `wca_future24_records.payload_json`: both approved 4-field rule maps survive the sanitizer, no clinical narratives added, audit/transaction committed. Read actual appointment readiness with zero evidence: `complete=false`, missing blocking key present; matching valid evidence clears only the matching key and cannot be fabricated by a total count.
2. Seed only disposable isolated staging historical active FUT13 records with empty requirements `[]`, invalid JSON, scalar or malformed rule members, and FUT11 template records with malformed JSON, empty/duplicate/disallowed fields. Verify fail closed with privacy-safe 503 and **no default “ready” or valid empty questionnaire**. Do not rewrite or delete historical live rows without separate Founder-authorized migration decision. Inspect production data prevalence before any new package deployment.
3. Exercise >100 genuinely ordered Future24 policy rows via real indexed SQL keyset, invalid/duplicate/nonascending id simulations in test fixture and DB read errors. Verify bounded paging makes progress, does not drop requirements, no partial private state and no indefinite loop; measure p75/p95 and memory. Rows and index/DDL version must be verified physically, not from the repository schema constant.
4. Real browser/REST guardian, patient, clinic manager, staff authorization, paid/donor equality, File17/19 notices, CF01 encrypted intake boundary, RTL/low-bandwidth, schedule live booking/reschedule, rollback/idempotency and negative IDOR tests.
5. Run independent backup+isolated restore and two **fresh** post-final-code staging review/fix/retest cycles with zero known blockers, Founder release approval, exact installer SHA and deployed checksum parity. Then and only then controlled live deployment, logs and parity/retest. CI/source cannot establish deployed DB/migration state.
