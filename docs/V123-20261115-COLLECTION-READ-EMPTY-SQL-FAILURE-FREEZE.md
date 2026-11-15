# File08 V12.3 — SQL empty-result failure freeze: clinic/service/branch/availability reads

Date: 2026-11-15. Scope: `F08-FR-001`, `F08-FR-002`, `F08-FR-003`, `F08-NFR-003`, `F08-NFR-004`. Exact frozen base PR54 SHA `0e71aa40f18ac0255038ef19ea3eb0c170d6a1ac` (draft, not in main). Main `d086840f7e2ac0ed328006385f064e0e2fb58cb1`.

## Reproduced source root cause before patch

`WCA_Repository::list_clinics`, `list_branches`, `list_services`, and `list_availability_rules` all check `null === $rows_raw && '' !== (string)$wpdb->last_error`, then cast `$rows_raw` to an array. WordPress `wpdb->get_results()` can return **an empty array** on an SQL failure with nonempty `$wpdb->last_error`. In that failure mode the condition fails; an incomplete or invalid database state is misrepresented as no clinics, no services, no branches or no availability. `WCA_Service::repository_read` relies on the repository's error latch to propagate a typed failure; this code path skips it.

Separately, the methods do not reset `$wpdb->last_error` immediately before the query, so stale previous-query error markers can contaminate read classification. Both true empty result and stale preceding diagnostic must be tested separately.

## Minimal coherent correction

For each of the four *collection read* methods only:
1. `$wpdb->last_error = ''` immediately before each `get_results`.
2. Mark error whenever `! is_array($rows_raw)` **or** the new query left nonempty `$wpdb->last_error`.
3. Preserve historical method return type (empty array) so its existing `WCA_Service::repository_read` and `consume_read_error()` callers return the typed 503. Do **not** change external schema, payment, medical data, permissions, search contract, version or migrations.
4. Execute actual repository method with transport-stub `get_results` in these scenarios for every method: valid empty; simulated SQL error returning []; previous stale error followed by a valid []; and malformed non-array read. Also test `WCA_Service::repository_read` integration status where feasible.

## Evidence and safety

This is an exact SOURCE code-path defect; there is no claim it occurred on Hostinger. WordPress Jetpack access remains `site_disconnected`; WPVibe returned HTTP 530; actual installed plugin, DB version, migration, checksum and logs remain unknown. **Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**

STOP: Never merge PR or deploy as a production fix until final exact-SHA CI green, two independent reviews, actual staging with File00/09 and verified Hostinger installed parity, rollback and Founder approval. This issue is separate from existing PR49 FUT08 SQL-read correction and PR51 FUT07 SQL-read correction.
