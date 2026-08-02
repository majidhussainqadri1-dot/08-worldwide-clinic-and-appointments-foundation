# File 08 — Worldwide Clinic and Appointments Foundation

This repository preserves the original `0.1.0` source baseline and develops the audited corrective release for the Sabri Social Homeopathy Platform.

## Branch architecture

- `baseline/file-08-original-import` — exact preserved source from the original ZIP.
- `audit/file-08-source-review` — independent review that recorded 32 findings without altering baseline source.
- `fix/file-08-corrective-completion` — corrective version `0.2.1`, regression tests, public clinic projection contract, and staging acceptance controls.

## Corrective result

All 32 source-audit findings have corresponding corrective implementation and traceability in `CORRECTIVE-MATRIX.md`. Version `0.2.1` additionally implements the read-only **File 08 Public Clinic Projection Contract 1.0.0** required by File 25.

The owner API is:

```php
swc_get_public_clinic_projection( int $user_id ): array
```

It returns only verified-public-doctor clinic presentation fields: `name`, `address`, `country`, `city`, `hours`, and `timezone`. It excludes phone, WhatsApp, email, user/native identifiers, appointments, and patient data. Missing verification, public visibility, or real clinic/schedule data fails closed.

Static PHP and JavaScript syntax, lifecycle/date regression checks, privacy-boundary checks, shell-ownership checks, projection-contract tests, and primary contrast checks are automated by **Corrective Quality**.

## Current authorization

Corrective source implementation is complete, but production approval is not granted by source or CI alone. Fresh install, upgrade, rollback, Hostinger staging, LiteSpeed private-cache, File 19/SMTP delivery, browser/mobile accessibility, privacy, concurrency, File 25 runtime integration, and multi-account acceptance remain mandatory.

## Evidence

- `SOURCE-PROVENANCE.md` — original package identity.
- `MANIFEST.md` and `CHECKSUMS.sha256` — original baseline evidence only.
- `AUDIT-REPORT.md` and `AUDIT-EVIDENCE.md` — independent findings.
- `CORRECTIVE-MATRIX.md` — 32/32 traceability.
- `CORRECTIVE-STATUS.md` — current classification.
- `CORRECTIVE-EVIDENCE.md` and `CORRECTIVE-CHECKSUMS.sha256` — corrective source evidence.
- `PUBLIC-CLINIC-PROJECTION-CONTRACT.md` — File 08 → File 25 public projection boundary.
- `STAGING-ACCEPTANCE.md` — required operational gates.

The repository does not store the original release ZIP or a production-approved package.
