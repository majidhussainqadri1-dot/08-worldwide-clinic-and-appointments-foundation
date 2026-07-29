# File 08 Corrective Evidence

## Corrective identity

- Corrective version: `0.2.0`
- Corrective branch: `fix/file-08-corrective-completion`
- Original baseline branch: `baseline/file-08-original-import`
- Audit branch: `audit/file-08-source-review`
- Audit findings mapped: `32/32`

## Local source validation

- PHP syntax: all corrective PHP files passed under PHP 8.4.
- JavaScript syntax: all corrective JavaScript files passed under Node.js 22.
- Corrective regression checks: `21/21 PASS`.
- Invalid calendar date rejection: PASS.
- DST-gap rejection: PASS.
- Repeated-hour ambiguity rejection: PASS.
- Terminal-state non-revival checks: PASS.
- Central verification ownership check: PASS.
- File 20 navigation ownership check: PASS.
- Private doctor-note rendering boundary check: PASS.
- WordPress/LiteSpeed private-cache contract check: PASS.
- Primary control contrast: `#FF8A1F` against `#172033` = `6.900:1` — PASS.
- High-confidence secret filename/indicator scan: no credential or private-key artifact found.

## Reproducible development-candidate package

The package was generated outside the repository from runtime files only. It is evidence for reproducibility and is **not** production-approved.

- Package name: `08-worldwide-clinic-and-appointments-foundation-0.2.0-development-candidate.zip`
- Package SHA-256: `b3ef86dec70d42ecbe5c9445a3509c6a9f90c8c5666fcb69f37c9c37444b375d`
- Package bytes: `41164`
- Runtime files: `13`
- PHP files: `9`
- JavaScript files: `1`
- CSS files: `2`

## Corrective source checksums

`CORRECTIVE-CHECKSUMS.sha256` covers runtime source, tests, workflows, and corrective documentation. The existing `CHECKSUMS.sha256` and `MANIFEST.md` remain historical evidence for the original `0.1.0` baseline only.

## Limitation

These results do not prove WordPress runtime behavior, MySQL migration on the target database, Hostinger/LiteSpeed cache exclusion, real File 19/SMTP delivery, browser rendering, backup restoration, or multi-account end-to-end operation. Those gates are defined in `STAGING-ACCEPTANCE.md`.
