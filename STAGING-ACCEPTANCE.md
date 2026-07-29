# File 08 Staging Acceptance Record

This checklist must be completed against the exact corrective commit and package on Hostinger staging. Production approval is prohibited until every required gate passes and evidence is recorded.

## Package and environment

- [ ] Exact corrective commit SHA recorded.
- [ ] Corrective source/package SHA-256 recorded.
- [ ] Full staging backup created and restoration tested.
- [ ] Files 00, 03, 07, 09, 19, and 20 versions recorded.
- [ ] PHP, WordPress, database, theme, LiteSpeed, and Hostinger environment recorded.

## Installation, upgrade, and rollback

- [ ] Fresh installation activates without warning, fatal error, or partial data.
- [ ] Upgrade from 0.1.0 migrates legacy public records to private and separates legacy doctor notes.
- [ ] Existing unrelated pages are never overwritten.
- [ ] Complete Repair is idempotent.
- [ ] Deactivation does not overwrite administrator-edited pages.
- [ ] Backup restoration and rollback are verified.
- [ ] Explicit purge is tested only on a disposable backup-restored staging copy.

## Identity and authorization

- [ ] Founder eligibility consumes central contracts.
- [ ] Fully verified doctor is eligible.
- [ ] Incomplete, pending, suspended, and ordinary member accounts are denied.
- [ ] Patient sees only their own appointments.
- [ ] Doctor sees only assigned appointments.
- [ ] Administrator capability is required for management and system tools.
- [ ] Generic WordPress/REST/search paths cannot expose appointments or metadata.

## Scheduling and state integrity

- [ ] All allowed patient, doctor, and administrator transitions pass.
- [ ] Every forbidden transition fails.
- [ ] Completed, cancelled, and declined records cannot revive.
- [ ] Invalid dates, DST gaps, and repeated-hour ambiguity fail.
- [ ] Out-of-window and misaligned slots fail.
- [ ] Unsupported consultation mode fails.
- [ ] Simultaneous acceptance of overlapping appointments allows at most one success.
- [ ] Stale forms fail without overwriting current data.
- [ ] Reschedule expiry and future-time checks pass.
- [ ] Reassignment requires patient consent and does not expose data early.

## Privacy, audit, cache, and notifications

- [ ] Private doctor/administrator notes never appear in patient output, email, or File 19 notification body.
- [ ] Patient-visible messages appear correctly.
- [ ] Privacy export contains all applicable records and fields.
- [ ] Privacy erasure removes direct identifiers and reports retained anonymized data accurately.
- [ ] Structured audit timeline records old/new states, assignments, actor, source, reason, and time.
- [ ] Audit failure blocks sensitive writes where required.
- [ ] File 19 in-app notification and delivery queue pass.
- [ ] SMTP fallback is tested with success and intentional failure.
- [ ] LiteSpeed and Hostinger never cache private request or dashboard pages.
- [ ] Private pages return no-store and noindex/noarchive headers.

## Interface and accessibility

- [ ] File 20 renders the only global navigation/header.
- [ ] No duplicate sticky navigation or horizontal overflow.
- [ ] 320–1920 px viewport matrix passes.
- [ ] Keyboard-only operation, focus visibility, field labels, error recovery, and 44 px targets pass.
- [ ] Sabri Orange primary controls retain at least 4.5:1 text contrast.
- [ ] Reduced-motion behavior passes.
- [ ] Public doctor pagination and all private/admin pagination pass.
- [ ] Empty, loading, conflict, stale, expired, and delivery-error states are understandable.

## Final authorization

- [ ] Independent post-correction review completed.
- [ ] Every new finding corrected and retested.
- [ ] Founder acceptance recorded.
- [ ] Production package checksum and manifest recorded.
- [ ] Live deployment explicitly authorized.
