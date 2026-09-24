# Hostinger Staging Acceptance — File 08 1.0.0

No box may be checked without dated evidence, tester identity, exact commit/package hash and result artifact.

## Environment and restoration

- [ ] Exact CI candidate and exact Files 00/03/07/09/17/19/20/24/25 and conditional CF packages installed.
- [ ] Fresh installation succeeds on canonical Hostinger staging.
- [ ] Upgrade from exact 0.1.0 and 0.2.2 succeeds with reconciled counts/hashes.
- [ ] Database and files backup is restored successfully; rollback returns one writable owner.
- [ ] LiteSpeed/private REST/page responses never cache or index protected data.

## Identity and authorization

- [ ] Guest, approved patient, minor+verified guardian, Founder, eligible doctor, ineligible doctor, suspended/revoked doctor, clinic staff and administrator journeys pass.
- [ ] Wrong patient/doctor/guardian/cross-clinic/forwarded reference fails without existence leakage.
- [ ] Step-up, suspension and dependency outage fail closed.

## Functional journeys

- [ ] Clinic draft/review/activation/pause/suspend/archive.
- [ ] Branch, service, fee/currency, availability, breaks/exceptions/buffers/capacity.
- [ ] DST-safe slot search, hold, expiry, collision and concurrent booking.
- [ ] Request, confirm, decline, reschedule proposal/acceptance, cancel, check-in, complete and no-show.
- [ ] Emergency red-flag diversion creates no appointment/case.
- [ ] Versioned consent, review eligibility, ICS, File 17 context, File 19 delivery/fallback, CF-02 complaint and CF-03 payment-intent bridge.
- [ ] CF-01 context leaks no narrative/contact data and grants no clinical authority.

## Quality and governance

- [ ] Urdu/English, RTL/LTR, mobile/tablet/desktop, supported browsers, keyboard, screen reader, 200–400% zoom, forced colors and reduced motion.
- [ ] Performance/load and concurrency evidence meets accepted thresholds.
- [ ] Privacy export/erasure/retention/legal hold and security adversarial tests pass.
- [ ] Independent code/security/privacy/professional review has no unresolved blocker.
- [ ] Founder functional, visual, medical-safety, privacy and business acceptance is recorded.
- [ ] Production deployment and post-deployment monitoring plan is approved.

**Staging accepted:** NO  
**Production accepted:** NO  
These values may change only through evidence-backed change control outside the source build.
