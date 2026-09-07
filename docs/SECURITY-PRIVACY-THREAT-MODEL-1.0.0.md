# Security and Privacy Threat Model — File 08 1.0.0

## Protected assets

Identity and guardian claims; clinic private contacts/addresses; doctor eligibility; patient/doctor appointment ownership; appointment time and mode; scheduling narrative; consent evidence; audit events; slot holds; complaint evidence references; payment intent references; opaque clinical context references.

## Principal threats and controls

| Threat | Control |
|---|---|
| IDOR/cross-clinic access | File 00 claims plus object-level patient/guardian/doctor/clinic/admin authorization; missing and forbidden appointments return non-enumerating denial |
| Impersonated/unverified doctor | exact File 00/File 09 authority boundary and fail-closed verification |
| Stale/replayed writes | nonce-authenticated REST, idempotency keys, record versions, resource locks and state preconditions |
| Double booking | atomic holds, expiry, overlap query, booking transition and slot release |
| State manipulation | canonical actor-specific transition matrix and immutable terminal states |
| Public PII leakage | public allow-list, prohibited-field deny-list, private/public branch split, no appointment/contact narrative in public projection |
| Cache leakage | protected route/REST `private, no-store`, LiteSpeed no-cache and noindex headers |
| Notification leakage | transactional outbox and privacy-minimal fallback email without reason/time/clinical narrative |
| Log leakage | recursive redaction and bounded structured log context |
| Emergency delay | red-flag diversion creates neither an appointment nor delayed support case |
| Clinical privilege escalation | scheduling-only CF-01 context explicitly denies treating relationship, clinical read/write, prescription and break-glass |
| Payment overreach | payment-intent bridge only; no ledger ownership; platform commission fixed at zero |
| Data deletion conflict | legal-hold-aware erasure, retention policy, non-destructive uninstall and verified rollback |
| Supply-chain artifact tampering | exact commit manifest, deterministic double build, detached checksum, path/duplicate/hash/size verification |

## Manual adversarial acceptance

Staging must test logged-out, wrong patient, wrong guardian, wrong doctor, suspended/revoked doctor, cross-clinic staff, forwarded references, stale versions, duplicate idempotency keys, expired/replayed holds, concurrent booking, malformed time zones/DST, cache replay, notification failure, privacy export/erasure, legal hold and rollback restoration.
