# T28 File 08 — 40 complete source-suite rounds; frozen cross-file defect ledger

**Baseline (source-only):** File08 main `2c668130172bc1c4eee8e79b7009b97eba9974c4`, runtime 1.2.16, plugin schema definition 3.4.0. No deployed-code/database parity is established.

## Forty sequential complete source-level suite executions

GitHub Actions run [39096157116](https://github.com/majidhussainqadri1-dot/08-worldwide-clinic-and-appointments-foundation/actions/runs/39096157116), audit branch SHA `84c03f6a3dbc1acc873caffc904c1e1e0ec21769`: existing entire `php tests/run-all.php` regression/security/source-contract suite was executed **individually 40 times**. The job printed ROUND 01/40 through ROUND 40/40 PASS and a final 40/40 marker; PHP 7.4, PHP 8.3 quality jobs, and deterministic package job also succeeded on that exact audit SHA. No failing File08 source regression was demonstrated in any of the forty passes.

This is *not* forty browser/WordPress end-to-end executions; it repeats the source-level suite, which contains many static assertion tests and does not establish real database migration, other-plugin runtime parity, or production acceptance. Forty identical green suite runs do not prove absence of uncovered functionality.

## Frozen post-review findings (no corrective changes made during the 40 rounds)

**T28-CF-01 — File14 sensitive REST route classification gap (confirmed cross-repository source defect, File14 owner):**
- File08 canonical REST endpoint uses namespace `wca/v1`, `POST /appointments` in `includes/class-wca-rest.php`.
- File08 successful owner mutation calls `do_action('wca_appointment_requested_committed_v1', $result['public_ref'])` after commit in `includes/class-wca-service.php`.
- File14 `GCU_Owner_Outcomes::booking_started()` subscribes, then its `record()` calls `GCU_Privacy::measurement_allowed()`.
- File14 `GCU_Privacy::measurement_allowed()` relies on `GCU_Hardening::is_sensitive_path()`. The latter excludes web page paths starting `/appointments/` but, at its reviewed HEAD `4c717d6543f6524105f2caaa2adeec1e6a1a6fc2`, does **not** classify `/wp-json/wca/v1/appointments` or `?rest_route=/wca/v1/appointments` as sensitive.
- Consequently the requested path-sensitive optional analytics exclusion may be bypassed for native REST booking POSTs when analytics consent exists. This is a *source-level* inference from exact code paths, not a claim that production currently exposes private information; event values remain privacy-minimized and production plugin parity is unknown.
- **Canonical corrective owner: File14**, not File08. Follow explicit opt-in/privacy policy and fail closed on sensitive REST paths. Do not change File08 appointment owner state or dual-write File14 tables. A File14 behavior regression test must cover both REST URL forms.

**T28-CF-02 — Missing cross-file staging acceptance assertion (File08 owned checklist gap):**
- Existing File08 `STAGING-ACCEPTANCE.md` checks File17/19/26 and general privacy but does not explicitly test File14's postcommit owner event with sensitive REST transport, consent/GPC, optional observer failure, and zero effect on appointment committed outcome.
- Add explicit checklist. A checklist is not an executed staging acceptance.

**No newly proven File08 runtime/application-code defect in the forty existing suite passes.**

## Corrective sequence

1. Freeze this ledger before correction.
2. Correct File14's REST sensitive-route guard with behavior tests at the exact File14 HEAD; do not weaken consent/GPC, and accept that native owner event counts may be suppressed under the sensitive-path policy pending a separately authorized privacy-safe design.
3. Amend File08 staging acceptance, keep canonical owner boundaries.
4. Run exact-head quality/forty source passes/package again on File08 after documentation changes; independently verify File14 CI on its final exact HEAD.
5. Keep staging/production **unverified** until actual deployed artifact/version/checksum, DB schema/migrations, companion parity and real-role tests are frozen and accepted.

## Required live-report columns

Repository HEAD: source SHA stated above, later corrections must report their final SHA. Deployed Version: unverified. Live DB Version: unverified. Migration State: unverified. Live Verification Status: not performed.
