# File08 V12.3 — Two distinct post-integration source review passes (2026-11-14)

**IMPORTANT: These are two separate methodological reviews by the same assistant. They are NOT two independent human/owner approvals and do not satisfy the real Staging/Live or independent-review gates by themselves.** No exact deployed artifact or DB has been obtained.

## Immutable subject and baseline
- Repository main at source freeze: `d086840f7e2ac0ed328006385f064e0e2fb58cb1`; runtime candidate 1.2.16; declared WCA schema 3.4.0. Exact Live unknown.
- PR49 correction source: `1ad072db46f440fb91184aa8d9dec311a90ed705` (FUT08 Heatmap and paged appointment completeness; 7 grouped source defects).
- PR50 correction source: `bec3b6204a93559c2db2bbc45c65e85c9f1e237f` (booking UI stale context and request truth; exact-source CI #43617456113 success).
- PR51 correction source: `cc47cef0f7dc832ca73c8a27ff0b337bd8b0a481` (FUT07 SQL/policy failures, protected REST propagation, UTC-midnight neighboring appointments; exact-source CI #43618489363 success).
- PR53 integrated candidate HEAD is whatever `git rev-parse HEAD` reports in **the final successful GitHub CI run**, not a frozen SHA written before this file's final commit.

## Review A — implementation and requirements traceability

| Evidence | Verification | Outcome |
|---|---|---|
| FUT08/FUT10/FUT17 main-lineage fixes | PR53 `includes/class-wca-future24.php` source blob SHA `4accb9de2812715278bc454c36786febf082dd11` exactly equals PR51, which descends from PR49. No partial overwriting of the seven prior fixes | PASS: exact byte parity |
| Booking controller | PR53 `assets/js/clinic.js` blob SHA `4ff7ebfb12361ceb3a06ad7a4d49429b946fb26f` equals PR50 exactly | PASS: exact byte parity |
| Booking PHP interface | PR53 `includes/class-wca-frontend.php` blob SHA `cedc62458d6e935aa3db7e3f9f5d05af5bb10e88` equals PR50 exactly | PASS: exact byte parity |
| FUT07 native suite | PR53 `tests/v123-fut07-policy-query-native.php` blob SHA `f9d1bc26fb6bf900a4c8464262b4c93c92de9433` equals PR51 | PASS: exact byte parity |
| Booking native suite | PR53 Node fixture `d195f5761233f7edc53804fdee1bfdc6b91d6d12` and PHP pending-request fixture `2f2631f049185b55730f1543baea201355c2fe9d` equal PR50 | PASS: exact byte parity |
| Test merger | `tests/run-all.php` includes both V12.1 regression fixtures, V12.3 FUT07, V12.3 pending-request truth and V12.3 54-ID register; workflow includes Node fixture and PR49 disposable WordPress/MariaDB matrix | PASS: source wiring |
| Governing IDs | FR18, NFR10, CEN2, FUT24; source locations and next-gate documented. IDs alone do not prove behavior | PASS: traceability only |
| Live deployment | No comparable Hostinger installed plugin SHA, wp_options/schema/migrations or real end-to-end journey | **BLOCKED** |

### Review A finding

The integration preserved exact tested source bytes across the three correction families. No unexplained runtime-file replacement was observed. This is **not** a claim that all 54 V12.3 behaviors are accepted, that the Site uses this source, or that any of the 34 open PRs are resolved.

## Review B — adversarial state/error/security and release discipline

| Risk scenario | Method/proof in integrated source | Assessment |
|---|---|---|
| Failed FUT07 policy query returns null | `slot_allowed_by_policy` clears/inspects `$wpdb->last_error`, emits typed 503 | Corrective source path present; real DB fault integration still pending |
| Corrupt active buffer policy | Four required numeric bounds validated through strict integer limits; malformed JSON rejected with 503 | Corrective source path present; historical rows/host schema unknown |
| Late failed policy WP_Query page | Per-page read checked; invalid ID and 250-page ceiling have typed errors | Native fixture exercises first/second page faults, malformed IDs and bound |
| REST slot/hold propagates `WP_Error` | `guard_slot_hold_data`, `apply_slot_policies`, `apply_slot_policies_to_rest` check `is_wp_error` before boolean truthiness | Source protection present; actual REST/browsers still need multi-plugin E2E |
| UTC-midnight previous-day consultation | Buffer query window expanded one UTC day on either side of candidate start/end, bounded by read guard | Native regression: previous-day appointment ends 10 minutes before candidate start under 30m buffer |
| Stale service/date/timezone search results | Filter-change increments both generation tokens, removes old buttons and expires UI selection | Executable Node deferred-response test |
| Premature booking confirmation claim | PHP interface clearly labels **request** requiring practitioner/staff acceptance; client says request submitted | Source string test; user comprehension testing pending |
| Private user/medical data | Native code and no-cache claims exist; no Hostinger test or live private account audit | **UNVERIFIED** |
| Real concurrency, guardian revocation, suspended doctor, notification/provider failure, backup/restore, migration parity | No real Hostinger staging or direct provider evidence | **BLOCKED** |
| Accessibility, Urdu/Arabic RTL, responsive appearance, p75/p95 measured latency | Static CSS/i18n paths exist, real browser and performance receipts absent | **UNVERIFIED** |

### Review B limitations and next gates

1. Source tests can pass even when runtime dependencies, schemas, credentials, or host caches differ; prohibit false Live-Resolved claims.
2. Slot buffer policy now reads a three-day UTC window, possibly increasing query cost. Measure p75/p95 at staging scale, do not assume performance meets V12.3 NFR04.
3. Existing historical legacy appointments with malformed time metadata require read-only schema/record profiling before any behavior or migration change. Avoid blind patching.
4. Endpoint error serialization and privacy tests need real WordPress auth/role/browser journeys; fixture-only PHP is not enough.
5. Exactly **13 scoped source defect groups** have correction candidates in draft lineage; **0** are merged into main, stage-verified or live-resolved.
6. Source reviews A/B are not independent approvers. Require separate owner/QA review after final SHA CI and before Founder deployment authorization.
7. Do not merge PR53 automatically. PR44/File26 owner contract, PR42 consent/policy, Issue #52 File07 discovery, Issue #47 branch protection and Issue #4 CF01 remain separate.

## CI evidence interpretation

- PR49 SHA `1ad072db...`: run #43380112964 6/6 PASS.
- PR50 SHA `bec3b620...`: run #43617456113 PASS; source/Node, not Staging.
- PR51 SHA `cc47cef...`: run #43618489363 PASS; source + disposable WP/MariaDB, not Staging.
- **PR53: only the workflow whose exact `head_sha` equals the final review-and-registry commit may be reported green.** Earlier canceled or successful runs for different SHAs are not accepted as final release evidence.

**Go/No-Go:** NO-GO for Hostinger production. Read-only Live Reality Freeze + deployment parity and separate controlled staging acceptance are missing. No confidential patient data was processed in these checks.

**Mandatory terminal fields:** Repository HEAD / Integrated PR candidate exact SHA / Deployed Version / DB Version / Migration State / Live Verification Status / Found 13 / Candidate source-corrected 13 / Main merged 0 / Stage 0 / Live-resolved 0 / remaining blockers.
