# Mandatory Post-Import Review

The baseline import is evidence preservation, not approval. Under the project QA and change-control rule, every defect found during review must be corrected, retested, and accepted before work proceeds to the next File 08 stage.

## Required review gates

1. **Archive and source integrity** — confirm the extracted repository files match `MANIFEST.md` and `CHECKSUMS.sha256`.
2. **Dependency contract** — verify the runtime dependency on Files 03 and 07 and the wider integration expectation for Files 01 through 07.
3. **Activation and page creation** — test idempotent activation, page mapping, capabilities, rewrite behavior, and deactivation boundaries.
4. **Appointment access control** — test patient ownership, assigned-doctor access, administrator access, direct-object reference attacks, unauthorized status changes, and private-page visibility.
5. **Doctor verification** — confirm that only institutionally verified and publicly eligible doctors can appear or receive appointment requests.
6. **Workflow lifecycle** — verify all seven declared states: requested, under-review, accepted, reschedule-requested, declined, cancelled, and completed.
7. **Time-zone correctness** — test UTC storage, invalid zones, daylight-saving transitions, future-date validation, and patient/doctor display conversion.
8. **Privacy and medical safety** — test consent, emergency warnings, noindex/noarchive, no-cache behavior, email content minimization, export, erasure, and audit-history retention.
9. **Rate limiting and abuse resistance** — verify request throttling, nonce enforcement, input bounds, spam behavior, and administrative filtering.
10. **Staging acceptance** — fresh install, upgrade, rollback, uninstall boundary, separate patient/doctor/admin accounts, email delivery, responsive layouts, accessibility, and regression checks.

## Acceptance rule

No File 08 defect, regression, privacy issue, security issue, incomplete workflow, or blocker discovered by this review may be deferred merely to continue development. It must be corrected and independently retested before the module can be classified as a development or staging candidate.
