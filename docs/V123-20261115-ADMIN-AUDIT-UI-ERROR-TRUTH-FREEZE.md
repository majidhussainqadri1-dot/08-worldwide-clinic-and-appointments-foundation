# File08 V12.3 — Appointment Admin audit-history false-empty display freeze (2026-11-15)

**Baseline:** Draft PR64 exact SHA `1c259b101bfa3ace78e3018b25f1f8c26e069814` (strict audit read source guard); main `d086840f7e2ac0ed328006385f064e0e2fb58cb1`. Neither is Hostinger deployed truth. V12.3 F08-FR-008 audit/history, FR010 role dashboards, F08-NFR-006 observability, NFR-002 privacy accountability.

## Proven source UI root cause before patch
`SWC_Admin::audit_timeline($id)` calls `SWC_Helpers::audit_rows($id)` which explicitly converts any `WP_Error` from `audit_rows_strict` into an empty `[]` for legacy compatibility. The admin renderer then displays `No audit entries were found.` even when the private audit database could not be read. This is false operational truth: a transient SQL fault is not an absence of audit events.

Local PHP 8.4 reproduction invokes the actual private `SWC_Admin::audit_timeline` with synthetic strict-read `WP_Error`: displayed `No audit entries were found.` instead of a retriable unavailable state. No real patient details or user accounts involved.

## Minimal correction and tests
- **Only** the Admin audit history renderer method: call `SWC_Helpers::audit_rows_strict` instead of legacy convenience wrapper, display an explicit localized, accessible error message (`role=alert`) when `is_wp_error`, otherwise preserve existing actual-empty and valid-event output.
- Do not print raw SQL errors to HTML; do not change audit event persistence, permissions, clinic actions, DB schema, user roles, payment, provider or plugin version.
- Actual method fixture with 3 cases: SQL read failure -> temporarily unavailable and no false 'no entries'; valid empty -> 'No audit entries'; real row -> event+UTC display. Full exact-HEAD PHP7.4/8.3, 40 source rounds, disposable WP 7.0.1/7.0.8/7.1.4 + MariaDB and reproducible ZIP required. Two AI source reviews are not two independent human reviews.
- No live claim. Jetpack site disconnected; WPVibe no site linked/previous HTTP530; deployed version/DB/migration/ checksum unknown.

**Scoped counting:** previous 24 candidate source defect groups through PR64 plus 1 separate dashboard observability symptom = **25 scoped groups** if final source tests pass. Main merged 0, Hostinger Staging accepted 0, Live-Resolved 0. This is NOT an exhaustive defect inventory.

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**