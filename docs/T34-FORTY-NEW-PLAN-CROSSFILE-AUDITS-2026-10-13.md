# File 08 T34 — Forty fresh plan/companion source audits, FUT13 policy integrity, and immutable evidence

Date: 2026-10-13. Branch: `review/file08-t34-forty-verified-fut13-owner-parity-20261013`. Main at frozen comparison: `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03` (source File08 1.2.16). Base correction before T34: PR #23 exact HEAD `df5d1c190830ef4df1c6bfaf263b71bef159dd9f`. Prior PR #22 carried an independent FUT13 conflict correction but was **not** integrated into PR #23. No merge/live deployment occurred.

## Sources and levels of evidence

**User-supplied authoritative plans actually opened:** `08-File-08-Worldwide-Clinic-and-Appointments-Complete-Master-Plan-2026-v1.1-Future24-Amended-2026-08-10.docx` table 6 (18 F08-FR-001…018), table 7 (10 F08-NFR-001…010), amended FUT01–24; consolidated `Sabri-Social-Homeopathy-Platform-Three-Central-Plans-Consolidated-Governing-Master-Plan-2026`, plus File07/09/14/25 plans. Actual GitHub source of File00, File09, File17 and previous versioned File19 interface was distinguished from installed-state evidence, which remains unavailable.

**40 NEW audits**, not T33/T32 pass recycling: 40 separate topic-focused passes each independently reloaded **50 runtime PHP/JS/CSS source files**, tested **3 baseline source-path signals**, plus **25 additional presence/absence owner/security guards**, and linted all **44 PHP runtime files**. **145/145 source-guard checks and 1760/1760 PHP -l runs pass**, **zero lint errors**; 40 independent source-tree fingerprints are captured in the machine-readable conversation deliverable `file08-t34-forty-exact-source-audit-2026-10-13.json`, from exact pre-ledger CI-built candidate `64a2b1f41e338fe608cd02f5ac13705af341bae7`. These are static source/lexical/syntax reviews, **not** proof that every plan acceptance journey passes real WordPress/DB/browser/linked app functionality. Never call them forty staging E2E tests.

| Review | Approved plan scope | Distinct checked topic | After fix |
|---|---|---|---|
| 01 | F08-FR001 | Clinic identity, branches, owner claims | Source probes PASS; PHP lint 44/44 |
| 02 | F08-FR002 | Services, fees, zero commission | Source probes PASS; PHP lint 44/44 |
| 03 | F08-FR003 | Availability, timezone, DST | Source probes PASS; PHP lint 44/44 |
| 04 | F08-FR004 | Holds, expiry, idempotent slots | Source probes PASS; PHP lint 44/44 |
| 05 | F08-FR005 | Guardian, consent, booking | Source probes PASS; PHP lint 44/44 |
| 06 | F08-FR006 | Doctor decision/notification | Source probes PASS; PHP lint 44/44 |
| 07 | F08-FR007 | Reschedule locking | Source probes PASS; PHP lint 44/44 |
| 08 | F08-FR008 | Cancellation, no show, audits | Source probes PASS; PHP lint 44/44 |
| 09 | F08-FR009 | Check-in/completed state | Source probes PASS; PHP lint 44/44 |
| 10 | F08-FR010 | Private dashboards and cursors | Source probes PASS; PHP lint 44/44 |
| 11 | F08-FR011 | Emergency diversion | Source probes PASS; PHP lint 44/44 |
| 12 | F08-FR012 | Consent/privacy lifecycle | Source probes PASS; PHP lint 44/44 |
| 13 | F08-FR013 | File17/CF01 relationship boundary | Source probes PASS; PHP lint 44/44 |
| 14 | F08-FR014 | Review eligibility, File03 presentation | Source probes PASS; PHP lint 44/44 |
| 15 | F08-FR015 | Calendar/ICS opaque IDs | Source probes PASS; PHP lint 44/44 |
| 16 | F08-FR016 | Shared finance zero commission | Source probes PASS; PHP lint 44/44 |
| 17 | F08-FR017 | Restricted clinical record | Source probes PASS; PHP lint 44/44 |
| 18 | F08-FR018 | CF02 complaints/appeals | Source probes PASS; PHP lint 44/44 |
| 19 | F08-NFR001 | Per-object actor authorization | Source probes PASS; PHP lint 44/44 |
| 20 | F08-NFR002 | Legal hold, erasure/export | Source probes PASS; PHP lint 44/44 |
| 21 | F08-NFR003 | Outbox receipt, retry, replay | Source probes PASS; PHP lint 44/44 |
| 22 | F08-NFR004 | Pagination, p75/p95 scope | Source probes PASS; PHP lint 44/44 |
| 23 | F08-NFR005 | Keyboard, focus, RTL | Source probes PASS; PHP lint 44/44 |
| 24 | F08-NFR006 | Tracing/alerting/privacy-safe logs | Source probes PASS; PHP lint 44/44 |
| 25 | F08-NFR007 | Migration, schema, rollback | Source probes PASS; PHP lint 44/44 |
| 26 | F08-NFR008 | Health, queues, safe repair | Source probes PASS; PHP lint 44/44 |
| 27 | F08-NFR009 | File00/09/17/19/24 contract boundaries | Source probes PASS; PHP lint 44/44 |
| 28 | F08-NFR010 | Dates, currency, US English/Urdu | Source probes PASS; PHP lint 44/44 |
| 29 | F08-FUT01–02 | Waitlist filters and service windows | Source probes PASS; PHP lint 44/44 |
| 30 | F08-FUT03–04 | Series/resources, capacity | Source probes PASS; PHP lint 44/44 |
| 31 | F08-FUT05–06 | Group scope and safe reschedule | Source probes PASS; PHP lint 44/44 |
| 32 | F08-FUT07–08 | Buffers, heatmap | Source probes PASS; PHP lint 44/44 |
| 33 | F08-FUT09–10 | Advisory/forecast, no clinical AI | Source probes PASS; PHP lint 44/44 |
| 34 | F08-FUT11–12 | Questionnaire and readiness | Source probes PASS; PHP lint 44/44 |
| 35 | F08-FUT13–14 | FUT13 blocking rules, guardian hub | Source probes PASS; PHP lint 44/44 |
| 36 | F08-FUT15–16 | Arrival, queue privacy | Source probes PASS; PHP lint 44/44 |
| 37 | F08-FUT17–18 | Disruptions, participant revocation | Source probes PASS; PHP lint 44/44 |
| 38 | F08-FUT19–20 | Virtual visits/FHIR protected projection | Source probes PASS; PHP lint 44/44 |
| 39 | F08-FUT21–22 | SMART/external busy safeguards | Source probes PASS; PHP lint 44/44 |
| 40 | F08-FUT23–24 | Episode/governance/audit | Source probes PASS; PHP lint 44/44 |

## T34 proven root cause and fix

**F08-FUT-13 prerequisite policy downgrade/inconsistency (cross-PR parity omission):**
- Baseline PR #23 has `verified_prerequisite_rules` and `save_prerequisites`, but neither rejects duplicated normalized rule keys. Even when a policy is validly stored, `prerequisite_state` overwrote duplicate `$requirements[$key]`, so a later `provisional` could replace `block`.
- Two simultaneously active stored policies with the same rule key could disagree on `block` versus `provisional` and silently choose whichever happened to win iteration order; the plan contains no approved precedence/override rule.
- **Corrected only after full review:** three *exact native Future24 methods* reconciled from isolated PR #22, without overriding PR #23's strengthened FUT01 waitlist date validation, FUT05 group privacy scope, FUT07 buffer policy integrity, FUT11 questionnaire and keyset pagination, File14 1.2.16 outcome observer, or earlier File00/09/17/19/CF01 guards.
- New dedicated suite `tests/t33-fut13-rule-uniqueness-and-policy-version-conflicts.php` tests request/serialized duplicates, legacy stored duplicates, cross-active policy contradictory state, valid same-behavior combination and invalid service scope on the actual PHP methods. Integrated into `tests/run-all.php`.
- GitHub Actions successful pre-ledger exact source HEAD `64a2b1f41e338fe608cd02f5ac13705af341bae7`, run `38427961077`: PHP7.4 PASS, PHP8.3 PASS, reproducible independent candidate packaging PASS. **Native policy conflict regression 18/18 PASS**; existing T33 File14/owner parity 14/14 PASS, T32 FUT13 keyset/nested policy 48/48 PASS, all registered source-level suites PASS with no PHP diagnostics.

**No other new semantic product defect established by the 40 source-presence scans.** External linked-plugin delivery/receipt remains unverified; known follow-up gating is not to be counted as a confirmed implemented fix.

## Final CI ledger gate
This documentation commit changes HEAD. The final PR SHA, exact-head Actions SUCCESS, candidate digest and artifact must be recorded from the *next* GitHub Actions run; earlier success applies to its specific source SHA only.

## Mandatory release integrity / five separate realities
Main repository: `9dcc8d16ea81d0bbd27184e71bc09ba04d30ba03` at audit baseline. T34 correction source is draft/unmerged; File00/03/07/09/14/17/19/20/24/25/26 and CF01/CF02/CF03 real installed versions/permissions/owner receipts, database migrations/options and schema, WordPress/PHP host setup/logs/checksums and package parity are **not verified**. True web acceptance requires staging roles/booking/guardian/verification, actual File14 observer event, File19 preferences and deliverability, File17/26/CF02/CF03 durable ACK, DB corrupt rows/migrations, queue retries, idempotent concurrency, RTL and WCAG, backup/restore, Founder sign-off, deploy and live re-test. No routine live patch until exact deployed source proven.
- Repository HEAD: the final GitHub branch SHA (must be read fresh after docs)
- Deployed Version: **UNVERIFIED**
- DB Version: **UNVERIFIED**
- Migration State: **UNVERIFIED**
- Live Verification Status: **NOT PERFORMED**

**Exact deployed code ابھی unverified ہے؛ repository-based diagnosis provisional ہے۔**
