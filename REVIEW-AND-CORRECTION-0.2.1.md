# File 08 Version 0.2.1 — Review and Correction Record

## Scope

This record covers the implementation of the File 08 Public Clinic Projection Contract `1.0.0`, its authoritative practitioner boundary, deterministic candidate packaging, and two fresh post-coding review-and-correction rounds.

## Initial implementation

The initial implementation added:

- `swc_get_public_clinic_projection( int $user_id ): array`;
- `swc_public_clinic_projection_contract(): array`;
- the bounded fields `name`, `address`, `country`, `city`, `hours`, and `timezone`;
- fail-closed public-profile and eligibility behavior;
- explicit exclusion of contact, identifiers, appointments, and patient data;
- dedicated contract tests and CI integration;
- version `0.2.1` metadata and documentation.

## Review and correction round 1

### Defect 1 — generic account address exposure

The first implementation permitted a generic profile `address` fallback when the dedicated clinic-address value was absent. That could expose a residential or account address as if it were a public clinic address.

**Correction:** only the dedicated `clinic_address` value is eligible. A generic address cannot create or populate the clinic projection.

### Defect 2 — extension value substitution

The first implementation allowed `swc_public_clinic_projection` to replace canonical values. Although the public allow list blocked new field names, an extension could still replace an authorized clinic name, location, or schedule with unrelated text.

**Correction:** the hook is revoke-only. It may remove an existing field by returning a falsey value, but the emitted value remains the canonical File 08 value. New or forbidden fields are ignored.

### Round 1 regression evidence

Tests now prove:

- generic private address does not fabricate a clinic section;
- canonical values cannot be replaced;
- canonical fields may be revoked;
- contact and native identifiers cannot be added.

## Review and correction round 2

### Defect 3 — legacy practitioner delegation

The corrected projection still delegated eligibility to a legacy File 08 helper whose historical inputs included local role/meta and completion-derived state. That was not sufficient for the current File 00 and File 09 authority model.

**Correction:** a dedicated `SWC_Doctor_Authority` adapter now governs the projection. It requires:

- File 00 runtime `>=1.2.4 <1.3.0`;
- exact File 00 public assertion contract `1.1.2`;
- exact user binding in the returned assertion;
- approved, eligible, non-suspended Doctor membership;
- professional verification and `can_practice` from File 00;
- File 09 runtime `>=1.1.0 <1.2.0`;
- agreement among File 09 structured decision, `gdo_user_is_verified()`, and a non-empty approved snapshot;
- no rejected, suspended, revoked, expired, or unavailable narrowing state from File 03 when that status contract is present.

The canonical Founder follows a separate File 00 institutional-authority path. File 08 does not fabricate a Doctor application for the Founder.

Local roles, `_smc_doctor_verified`, completion percentages, qualification strings, and locally calculated license-expiry state are not accepted by the new public-projection authority contract.

### Defect 4 — non-reproducible release evidence

The branch previously relied on a separately produced development ZIP. That did not bind the candidate to one exact commit through an embedded manifest and independent verification.

**Correction:** GitHub Actions now:

1. resolves the exact source commit and commit timestamp;
2. builds the candidate twice;
3. compares ZIP, manifest, and checksum byte-for-byte;
4. verifies every allow-listed runtime file hash and size;
5. verifies the embedded and detached manifest equality;
6. independently reopens and validates the assembled artifact;
7. uploads a temporary three-file workflow artifact.

### Round 2 regression evidence

Tests now prove:

- File 00/File 09 agreement admits an eligible Doctor;
- decision/helper disagreement fails closed;
- missing approved snapshot fails closed;
- File 00 suspension overrides File 09 approval;
- assertion user mismatch fails closed;
- File 03 revocation narrows eligibility;
- the canonical Founder is admitted through institutional authority;
- filters cannot grant denied practitioner authority and may revoke it;
- local role/meta inference is prohibited.

## Completion boundary

Both code-review rounds and automated corrections are complete. This record does not establish Hostinger staging acceptance, real cross-plugin runtime compatibility, browser/accessibility acceptance, rollback proof, Founder acceptance, production approval, or live-deployment authorization.
