# File 08 — Worldwide Clinic and Appointments Foundation

This repository preserves the original `0.1.0` source baseline and develops the audited corrective release for the Sabri Social Homeopathy Platform.

## Branch architecture

- `baseline/file-08-original-import` — exact preserved source from the original ZIP.
- `audit/file-08-source-review` — independent review that recorded 32 findings without altering baseline source.
- `fix/file-08-corrective-completion` — corrective version `0.2.1`, authoritative practitioner tests, public clinic projection contract, deterministic candidate engineering, and staging acceptance controls.

## Corrective result

All 32 source-audit findings have corresponding corrective implementation and traceability in `CORRECTIVE-MATRIX.md`. Version `0.2.1` additionally implements the read-only **File 08 Public Clinic Projection Contract 1.0.0** required by File 25.

The owner APIs are:

```php
swc_get_public_clinic_projection( int $user_id ): array
swc_public_clinic_projection_contract(): array
```

The projection returns only authoritative public-practitioner clinic presentation fields: `name`, `address`, `country`, `city`, `hours`, and `timezone`. It excludes phone, WhatsApp, email, user/native identifiers, appointments, and patient data. Missing authority, public visibility, or real clinic/schedule data fails closed.

Doctor eligibility for this projection requires exact File 00 assertion-contract agreement and File 09 decision/helper/snapshot agreement. The canonical Founder uses the File 00 institutional-authority path. Local roles, local verification metadata, completion percentages, qualification strings, and locally calculated license dates are not accepted by the projection contract.

Extension hooks are monotonic: they may revoke authorized visibility or fields, but cannot grant a denied practitioner, replace canonical values, expose a generic private address, or add forbidden fields.

## Automated evidence

The **Corrective Quality** workflow verifies PHP/JavaScript syntax, lifecycle/date/privacy/cache/navigation/ownership contracts, File 00/File 09 authority agreement, public clinic projection behavior, primary contrast, source checksums, and repository hygiene.

A dependent package job builds the candidate twice from one exact commit and `SOURCE_DATE_EPOCH`, compares the ZIP/manifest/checksum byte-for-byte, independently verifies the assembled artifact, and uploads it temporarily. The runtime ZIP contains one plugin root, an embedded manifest, and exactly 15 allow-listed runtime files. Passing package verification does not imply staging or production acceptance.

## Current authorization

Corrective source implementation and candidate engineering are complete, but production approval is not granted by source or CI alone. Fresh install, upgrade, rollback, Hostinger staging, LiteSpeed private-cache, real Files 00/03/07/09/19/20/25 integration, browser/mobile/Urdu RTL accessibility, privacy, concurrency, multi-account workflows, and Founder acceptance remain mandatory.

## Evidence

- `SOURCE-PROVENANCE.md` — original package identity.
- `MANIFEST.md` and `CHECKSUMS.sha256` — original baseline evidence only.
- `AUDIT-REPORT.md` and `AUDIT-EVIDENCE.md` — independent findings.
- `CORRECTIVE-MATRIX.md` — 32/32 traceability.
- `CORRECTIVE-STATUS.md` — current classification.
- `CORRECTIVE-EVIDENCE.md` and `CORRECTIVE-CHECKSUMS.sha256` — corrective source evidence.
- `PUBLIC-CLINIC-PROJECTION-CONTRACT.md` — File 08 → File 25 public projection boundary.
- `REVIEW-AND-CORRECTION-0.2.1.md` — two post-coding review-and-correction rounds.
- `STAGING-ACCEPTANCE.md` — required operational gates.

The repository does not store the original release ZIP or a production-approved package.
