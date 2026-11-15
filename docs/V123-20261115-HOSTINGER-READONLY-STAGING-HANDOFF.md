# File08 V12.3 — Hostinger / Staging Read-Only Reality-Freeze Handoff (2026-11-15)

**Operator:** an authorized host administrator with secure WP-CLI access. This runbook does NOT assign authority to ChatGPT or authorize a site change. **Target:** `www.sabrihomeopathy.com`. **Privacy:** no real patient names, rows, secrets, tokens, passwords, private medical files, unredacted logs or DB dumps are to be returned into public GitHub or ordinary support threads.

## Why no live deployment yet

Jetpack reports `site_disconnected`; WPVibe is not authorized and returned `HTTP 530` attempting to connect. This does not identify a failing host, plugin, Cloudflare status, bot rule or root cause. Repository `main` HEAD and passed CI cannot prove installed version. No live plugin/package checksum, DB version, migration state, provider activation, logs or rollback readiness was obtainable through the current connectors.

## Step P01 — read-only installed state on the host

1. Confirm exact **site identity** and environment (Live vs Staging), host account, current WP/PHP/MariaDB versions and whether **File08 is active**. Do NOT disable security or install a plugin to diagnose the HTTP530 response.
2. Retrieve the **approved source** of `tools/file08-live-reality-freeze.php` from the current exact reviewed File08 GitHub candidate, verify its checksum with a trusted operator, and place it **outside the public webroot** with restricted file permissions. This is a WP-CLI-only read-only script, **not bundled in the release ZIP**; do not expose it as a REST/HTTP endpoint.
3. In the site's existing WP-CLI context (replace `/actual/site` with the verified actual site root, **not a guessed path**), run:

```sh
wp eval-file /private/reviewed/file08-live-reality-freeze.php --path=/actual/site
```

The tool outputs a JSON snapshot including File08 installed entry/tree hashes, declared runtime, active dependency plugin versions, canonical schema option/version states, migration failure flag, and table/column metadata. It intentionally does **not** collect patient rows, credentials, option secrets or runtime logs.
4. Inspect sensitive **runtime error logs separately** using hosting controls. Produce only *redacted* event IDs/error codes and timestamps; retain all raw logs inside the authorized secure host environment. Determine whether any File08 schema upgrade failed or was deferred from real evidence.
5. Check whether all plugin tree hashes/manifest counts correspond to the actual approved candidate ZIP **after extraction**. A GitHub SHA, ZIP digest or PR title alone is not parity. Compare deployed runtime with the **actual binary/source tree bytes and DB migrations**; if different, STOP routine bug debugging and perform a Deployment-Parity Audit.
6. Document read-only backup/restore readiness, private storage and rotation status without sharing secret material. Before *any* Staging installation or Live migration, separately prove tested rollback using a sanitized Staging clone under host control.

## Step P10 — first real staging acceptance, NOT Live

- Deploy only a **signed/verified exact SHA candidate** to controlled staging after dependency and backup verification. Do not install on production directly.
- Check migration on fresh install and upgrade/partial failure, DB table/option agreement, plugin activation and rollback.
- Run real object/field/guardian authorization, 2 simultaneous last-slot requests, DST spring gap/fall fold, canceled reminders/provider outage, File26 public/private discovery, File17 room, CF01 disabled clinical chart, CF03 verified payment and commission 0%, and multilingual/RTL mobile/a11y.
- Record all failed paths with timestamps/trace IDs and the exact installed candidate checksum; zero known critical/high defects, mandatory tests passed, two independent owner/QA reviews and Founder approval before Live. No simulated source-only fixture is a substitute.

## Step P11 — controlled Live acceptance

Only after P01+P10 and authorization: frozen backup, approval, limited deployment, no unexpected migration, exact installed checksums, private/public smoke, controlled rollback availability and subsequent monitoring. Never label live fixes Resolved without **live retest and parity confirmation**.

## Handoff acceptance form (no guessed data)

| Property | Host receipt |
|---|---|
| Repository HEAD | `d086840f7e2ac0ed328006385f064e0e2fb58cb1` (source truth only) |
| Latest source candidate | PR #54; exact final HEAD/ZIP SHA to be re-fetched before install |
| Live plugin version + SHA256 | **UNVERIFIED** |
| Staging plugin version + SHA256 | **UNVERIFIED** |
| Live DB schema + `wp_options` migration markers | **UNVERIFIED** |
| Live dependency versions and state | **UNVERIFIED** |
| Read-only log error categories | **UNVERIFIED** |
| Backup/restore receipt | **UNVERIFIED** |
| Staging role/browser/safety run receipts | **UNVERIFIED** |
| Founder Release Go/No-Go | **NOT GRANTED by this handoff** |
| Live re-test | **NOT RUN** |

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
