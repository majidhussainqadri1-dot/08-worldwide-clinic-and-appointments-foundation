# File 08 Public Clinic Projection Contract 1.0.0

## Governing boundary

File 08 owns clinic discovery, availability, appointment scheduling, and the public clinic projection supplied to File 25. File 25 remains a read-only visual consumer and must not query File 08 storage or derive clinic truth independently.

## Owner API

```php
swc_get_public_clinic_projection( int $user_id ): array
swc_public_clinic_projection_contract(): array
```

## Successful response

```php
array(
    'contract_version' => '1.0.0',
    'clinic' => array(
        'name'     => 'Example Clinic',
        'address'  => 'Public clinic address',
        'country'  => 'Pakistan',
        'city'     => 'Gujrat',
        'hours'    => 'Monday, Wednesday · 09:00–13:00',
        'timezone' => 'Asia/Karachi',
    ),
)
```

Fields without authoritative public values are omitted. If no truthful clinic or availability data exists, the response is empty.

## Eligibility

A projection is returned only when all of the following are true:

1. the user exists;
2. File 08 recognizes the account as an eligible verified doctor;
3. the native Doctors Directory marks the profile public, or the account is the canonical Founder;
4. at least one real clinic/location field or valid File 08 availability schedule exists.

Missing dependencies, exceptions, private visibility, unverified identity, or empty data fail closed.

## Public allow list

Only these scalar presentation fields may be emitted:

- `name`
- `address`
- `country`
- `city`
- `hours`
- `timezone`

## Explicit exclusions

The contract never emits:

- phone, WhatsApp, or email;
- user IDs, native IDs, verification evidence, or private status data;
- appointments, patient information, private notes, audit records, or analytics;
- payment, transaction, prescription, clinical-record, or message data.

File 03 remains the owner of profile contact values and public-contact consent. File 25 must apply that separate contact contract when it renders contact actions.

## Extension law

`swc_public_clinic_projection` may alter or remove already-authorized presentation values. It cannot create fields absent from the canonical projection and cannot add non-allow-listed fields.

`swc_public_clinic_is_public` is monotonic: an extension may revoke public eligibility, but it cannot grant eligibility denied by the authoritative native visibility decision.

## Data and mutation law

The projection is read-only. It creates no posts, options, user metadata, appointments, audit events, notifications, or cache records.

## Acceptance boundary

Source tests and green CI establish code-level contract evidence only. Real Files 00/03/07/08/09/20/25 packages, public/private Doctor accounts, availability states, Urdu RTL, responsive layout, accessibility, cache behavior, upgrade, rollback, and Hostinger staging acceptance remain mandatory before production approval.
