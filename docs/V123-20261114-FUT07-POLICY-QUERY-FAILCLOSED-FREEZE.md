# File08 V12.3 — FUT07 operational policy fail-closed freeze (2026-11-14)

## Exact isolated repository state

- Base: PR49 exact source candidate `1ad072db46f440fb91184aa8d9dec311a90ed705`, based on main `d086840f7e2ac0ed328006385f064e0e2fb58cb1`.
- Main/PR49 plugin version `1.2.16`; declared WCA schema `3.4.0` (not a claim about Live).
- Requirement: **V12.3 F08-FUT-07 Smart Buffer & Transition Rules** (Master Plan pp.153–154), core F08-FR-003/004, F08-NFR-003/004 and UX-04. Invalid operational scheduling policy and failed appointment enumeration must not fabricate a bookable slot.
- Live-source attempt: WordPress.com Jetpack `site_disconnected`; WPVibe `HTTP 530`; exact installed plugin/DB/migration/logs unavailable. No live diagnosis and no production modification.

## Frozen code proof on PR49, before correction

1. `WCA_Future24::slot_allowed_by_policy` uses `WP_Query(...fields='ids', paged=...)` for every active slot buffer policy but maps `(array)$q->posts` to IDs without checking `$wpdb->last_error` or the returned array shape. A failed query may yield no IDs and return true, silently bypassing the active policy.
2. A paginated read has no upper bound and no typed error on interrupted later pages. The unrelated corrected `clinic_appointments_between_all` in PR49 does not protect this second independent query path.
3. `json_decode($row['payload_json'])` failure silently becomes `array()`; four configured policy limits all become zero and the slot is accepted. Noncanonical numeric payload values are also converted by `absint/min`, discarding integrity errors.
4. Callers `guard_slot_hold_data` and `apply_slot_policies` currently expect boolean. Simply returning a `WP_Error` from the private method without propagating it would be **fail-open**, because objects are truthy in PHP. Both caller paths and public `apply_slot_policies_to_rest` must propagate the same typed error.
5. `slot_allowed_by_policy` runs in REST slot search and slot-hold guard; the hazard is a patient-visible false bookability signal, not an asserted observed Live incident.

## Smallest coherent correction boundary

- Touch only `includes/class-wca-future24.php`, a new isolated native PHP behavior regression, `tests/run-all.php`, and this freeze document.
- Clear/inspect `$wpdb->last_error` before and after each source read; validate policy JSON and numeric bounds; reject malformed query post IDs; bound synchronous pagination (250 pages maximum) with explicit controlled error; preserve legitimate no-policy and empty-page cases.
- Propagate errors through `apply_slot_policies`, `apply_slot_policies_to_rest`, and `guard_slot_hold_data`; do not silently substitute zero slots or allow a hold.
- No schema/version/fee/medical/provider/deployed changes.
- Test success, corrupted policy, first-page SQL error, later-page SQL error, invalid row shape/ID, bounded pagination, REST propagation.
- New branch stays Draft until exact-HEAD CI, two fresh reviews, true staging/rollback and authorized parity-confirmed release. Source-fixed != merged != deployed != Live-resolved.

## Acceptance register
`FUT07-POL-01` DB query fail-open; `FUT07-POL-02` corrupt policy drops all constraints; `FUT07-POL-03` error propagation through both public APIs.

Required terminal truth: Repository HEAD / Candidate SHA / Deployed Version / DB Version / Migration State / Live Verification Status.

## Review B — additional UTC-midnight buffer discontinuity before correction

A second independent code-path inspection of the same frozen method finds **FUT07-POL-04**, separate from policy read failures. Its appointment query binds `_swc_preferred_at_utc` to the calendar date of the candidate slot exclusively (`YYYY-MM-DD 00:00:00` through `23:59:59`). Appointments starting on the preceding UTC date but finishing shortly before a new-day slot, or next-day appointments shortly after a late-night slot, are not returned; therefore buffer/travel bounds can be silently bypassed at UTC midnight. A bounded three-UTC-date candidate window is necessary; calculate from the validated candidate start/end, expand by one UTC day either side, and keep page limit and fail-closed SQL guarantees. Native reproduction should include a prior-day appointment whose end is 10 minutes before the next day's slot and a 30-minute before buffer. This is source evidence, not a live incident.

Do not confuse `FUT07-POL-04` with DST behavior; appointment times and SQL query values here are canonical UTC.
