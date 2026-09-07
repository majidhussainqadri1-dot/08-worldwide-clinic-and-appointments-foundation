# File 08 Corrective Evidence

## Corrective identity

- Corrective version: `0.2.1`
- Public clinic projection contract: `1.0.0`
- Authoritative practitioner contract: `1.0.0`
- Corrective branch: `fix/file-08-corrective-completion`
- Original baseline branch: `baseline/file-08-original-import`
- Audit branch: `audit/file-08-source-review`
- Original audit findings mapped: `32/32`

## Source and contract validation

The definitive `Corrective Quality` workflow performs:

- PHP 8.3 syntax checks for runtime, tests, and release-engineering tools;
- JavaScript syntax checks;
- original lifecycle, date, privacy, cache, navigation, ownership, and contrast regressions;
- File 00 assertion-contract and File 09 verification-agreement tests;
- Founder and Doctor authority tests;
- File 03 narrowing-state tests;
- public/private/ineligible/empty clinic projection tests;
- forbidden contact, identifier, appointment, and patient-data exclusion tests;
- revoke-only extension-filter tests;
- corrective SHA-256 verification;
- repository hygiene and secret/archive rejection.

Current contract suites include:

- corrective regression checks: `24/24 PASS`;
- authoritative practitioner checks: `11/11 PASS`;
- public clinic projection checks: `17/17 PASS`;
- primary control contrast: `#FF8A1F` against `#172033` = `6.900:1` — PASS.

## Two review-and-correction rounds

### Round 1 — projection privacy and extension monotonicity

The first fresh review found that a generic account address could be mistaken for a clinic address and that an extension filter could replace canonical public values. The implementation was corrected so that only a dedicated clinic-address value is eligible and projection filters may revoke fields but cannot replace values or add fields.

### Round 2 — authoritative practitioner ownership

The second fresh review found that the new projection still delegated eligibility to a legacy File 08 helper. The projection now consumes a dedicated fail-closed adapter that requires exact File 00 runtime/contract agreement and exact File 09 decision/helper/snapshot agreement for Doctors. The canonical File 00 Founder is handled as an institutional authority without fabricating a Doctor application. Local roles, completion percentages, qualification strings, license dates, and local verification metadata are not accepted as public-projection authority.

## Deterministic candidate engineering

The GitHub workflow builds the runtime candidate twice from one exact commit and `SOURCE_DATE_EPOCH`, compares the ZIP, detached manifest, and detached checksum byte-for-byte, independently verifies the assembled artifact, and then uploads it as a temporary workflow artifact.

The package contains one WordPress plugin root, an embedded `STAGING-MANIFEST.json`, and exactly 15 allow-listed runtime files. The manifest binds version, commit SHA, file hashes, byte sizes, File 08 number, and public clinic contract `1.0.0`; both staging and production acceptance remain false.

Exact-head workflow run, artifact ID, outer digest, inner package digest, size, and expiry are recorded in Draft Pull Request #3 after each final CI run so that evidence updates do not mutate the candidate commit they describe.

## Corrective source checksums

`CORRECTIVE-CHECKSUMS.sha256` covers runtime source, contract tests, release-engineering tools, and corrective documentation. The existing `CHECKSUMS.sha256` and `MANIFEST.md` remain historical evidence for the original `0.1.0` baseline only.

## Limitation

Source tests, deterministic packaging, and green CI do not prove WordPress runtime behavior, real File 00/03/07/09/19/20/25 integration, MySQL migration on the target database, Hostinger/LiteSpeed cache exclusion, real notification delivery, browser rendering, backup restoration, or multi-account end-to-end operation. Those gates remain governed by `STAGING-ACCEPTANCE.md`.
