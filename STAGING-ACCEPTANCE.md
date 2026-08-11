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
- [ ] File 08 core schema `3.2.0`, restricted continuity schema `1.1.0`, Future24 additive schema `1.0.0`, migration/options state and relevant tables/columns verified from the actual staging database.

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
