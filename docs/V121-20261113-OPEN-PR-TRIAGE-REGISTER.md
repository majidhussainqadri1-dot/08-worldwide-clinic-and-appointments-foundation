# File08 V12.1 open-PR reconciliation registry (initial triage, 2026-11-13)

## Evidence
Frozen base: `d086840f7e2ac0ed328006385f064e0e2fb58cb1`. GitHub API `GET /pulls?state=open` returned **30 pre-existing open PRs** when work started. This execution added separate draft PR #45. Counts below exclude #45. Exact deployed code/DB/migrations unavailable. PR open/green does not mean merged or deployed.

## Status law
- **Fresh candidate**: may be compared, reviewed and tested; not automatically approved.
- **Historical diverged**: substantial old branch changes versus current `main`, do not bulk merge.
- **Policy hold**: product/clinical consent decision before code collection.
- **Historical audit**: keep provenance; close only when supersession proven.
All 30 remain open pending individually evidenced disposition; no automatic closure or bulk merge.

| PR | Title | Triage class | Next controlled action |
|---|---|---|---|
| #44 | File08→File26: canonical verified public clinic search without patient slots | Fresh candidate | Do not merge until File26 owner contract, public/private field test and staging acceptance |
| #42 | Draft — File08 privacy-safe requested-appointment cohort proposal; no analytics collection | Policy hold | Do not enable analytics or merge without owner consent/revocation ledger, cohort suppression and founder approval |
| #38 | File08 T47 — 40 plan source reviews; invalid opening and canonical DST capacity safety | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #37 | File08 T46 — 40 current-plan checks; complete-slot and opened-weekday capacity parity | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #36 | File08 T45 — 40 plan/source reviews, full-slot break and bounded closed heatmap parity | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #35 | File08 T44 — 40 crossplan source reviews; heatmap DB and capacity exception fail-closed | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #34 | File08 T43 — 40 cross-plan reviews; strict flexible windows and stale resource safety | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #33 | File08 T42: 40 cross-plan source checks, fail-closed waitlist and File17 room replay | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #32 | File08 T41: 40 cross-plan reviews and private group join/leave identity verification | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #31 | File08 T40 — 40 plan/cross-owner reviews; reject unknown practitioner and untraceable calendar events | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #30 | File08 T39 — 40 cross-plan reviews; current-clinic queue isolation and strict FUT18 roles | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #29 | File08 T38 — 40 plan and companion reviews; fix FUT18 File00 subject identity and replay | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #28 | File08 T37: 40 cross-plan checks, fail-closed disruption paging and participant revocation | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #27 | File08 T36 — 40 scoped master-plan source checks; reconcile FUT04/FUT23 and fix stale queue peers | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #26 | File08 T35 — 40 governing checks; fail closed on stale live queues and incomplete guardian pages | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #25 | File08 T35: 40 fresh plan reviews — FUT04 resource and FUT23 episode scope integrity | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #24 | File08 T34: 40 fresh plan/owner reviews — block FUT13 duplicate and conflicting policies | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #23 | File08 T33: 40 fresh current-main reviews — reconcile 1.2.16 and preserve File14 | Historical diverged T33–T47 chain | Extract only validated delta against current HEAD; do not merge whole chain |
| #22 | File08 T33 — 40 governed checks; block duplicate FUT13 rules and conflicting active policies | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #20 | File08 T32: 40 new plan/companion reviews — fail closed on corrupt Future24 stored state | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #19 | File08 T32 — 40 governing reviews; preserve FUT13 blocking policies and validate Future24 stored pages | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #18 | File08 T31 — 20 plan/owner checks; fail-closed Future24 File00 group identity and persisted JSON | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #17 | File08 T30 — 20 governing checks; reject malformed private query cursors and page sizes | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #16 | File 08 T30: 20 new full-source checks — File00 identity, companion readiness, durable outbox | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #15 | File08 T29 — 20 governing reviews; unify latest T28 File00/09/CF01 with strict T27 appointment cursors | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #14 | File08 T27-R2 — 20 scoped plan checks; combine File17/19 fixes with strict cursor/UTC integrity | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #13 | File08 T27-R1 — 20 governed source checks, strict patient/clinic signed cursors | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #12 | File 08 T28: 20 full-source audits, File00/09/19/CF01 integration and QA corrections | Older diverged T27–T33 chain | Determine source supersession; independently rebase only necessary security behavior |
| #8 | File 08 — nineteenth fresh 20-round Review → Ledger Freeze → Fix cycle | Historical audit | Retain immutable evidence; close only after documented supersession decision |
| #2 | Audit: File 08 independent source review | Historical audit | Retain immutable evidence; close only after documented supersession decision |

## Compare-to-main findings (verified)
- #44: `ahead=2`, `behind=0`, 2 changed files (`worldwide-clinic.php`, `includes/class-wca-file26-connector.php`). Still needs File26 companion parity.
- #38: `ahead=219`, `behind=14`, 88 changed files. Direct merge would mix unrelated old branch changes; this execution's draft #45 extracts the isolated T44–T47 heatmap logic onto present main instead.
- #37–#23: all diverged from historical `9dcc8d16…`, typically 40–206 commits ahead and 14 behind.
- #22–#12: diverged from historical `70541974…`, 14–89 commits ahead and 35 behind.
- #8: about 2 ahead / 332 behind, no current changed file shown in compare summary; historic.
- #2: about 0 ahead / 1348 behind, no changed file shown; historic.

## Remaining exit gates
1. For every old PR, compare precise changed application files against exact current main; record **present / superseded / missing / conflict / requires policy approval**, not merely commit counts.
2. For a missing validated correction, reproduce first with a failing current-main test; freeze root cause; make a narrowly scoped candidate; prove exact corrected-head CI and integrated behavior.
3. Require two fresh post-runtime-change reviews; avoid circular test claims.
4. No staging/live equivalence without deployed package hash, DB schema/migration and runtime evidence.
5. Privacy proposal #42 must not be enabled by historical PR; no individual or small-cohort booking analytics.
6. Issue #4 keeps care-context/CF01 extraction separate. No patient chart from booking rights alone.

## Exact retained release truth
Repository HEAD baseline above; source plugin 1.2.16, WCA core schema declared 3.4.0, legacy SWC schema marker separately 3.1.0. **Deployed Version: unknown; Live DB Version: unknown; Migration State: unknown; Live Verification: blocked.**
