# File 08 — Fourteenth Fresh Sequential 20-Round Review Evidence

Frozen product baseline: `fe10d05bdb4d1ffb726063f657f283097e65abbd` / runtime 1.2.13. Temporary audit-only commits are not product baselines.

Main review findings: R01 consent atomicity; R02 strict IANA timezone validation; R03 strict DOB parsing; R04 minor-claim conflict fail-closed; R05 exact 7/30/90 heatmap windows; R06 required heatmap outcome counters; R07 opaque clinic references for Future24 analytics reads; R08 advisor reason/provenance; R09 bounded public doctor loading; R10 bounded legacy request/admin doctor loading; R11 cryptographically secure UUID fallback; R12 collision-resistant outbox worker identity; R13 branch status/visibility fail-closed; R14 exact country code validation; R15 invalid clinic-status rejection; R16 legacy numeric protected REST retirement/idempotency coverage; R17 public numeric clinic-ID rejection; R18 release/test evidence alignment to 1.2.14.

R19 and R20 are post-final-coding fresh corrected-state reviews and must complete with no new supported defect before release evidence is accepted. Staging/live evidence remains separate.

## Post-main corrective sweep
After the 20 main rounds had each passed its corrected-state QA, a broader fresh sweep found three additional repository defects: FUT-08 configured capacity used UTC weekday arithmetic instead of rule-local timezone/DST/effective-range projection; low-volume granular outcome aggregates had no explicit suppression; and public clinic pagination generated random transient cursors, causing unchanged pages to receive unstable ETags and avoid conditional-cache hits. These were corrected without renumbering the completed 20 main rounds. Because source changed after R20, the required two fresh post-coding reviews must restart and pass before closure.
