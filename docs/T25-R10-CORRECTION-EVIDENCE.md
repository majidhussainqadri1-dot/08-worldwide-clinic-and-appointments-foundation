# T25 R10 — Correction Evidence

R10 corrections were applied only after `docs/T25-R10-FROZEN-LEDGER.md` froze the complete review findings.

- Current release/status surfaces now identify T25 rather than T24 as the active closure cycle and preserve T24 as historical provenance.
- T25 round truth is summarized as R1/R8/R9/R10 defect-bearing and corrected, with R2–R7 clean.
- README booking-route documentation now matches the governing `/appointments/book/{doctor_or_clinic}` contract.
- The historical T24 currentness regression no longer forces T24 to remain current.
- `tests/t25-r10-release-closure-regressions.php` is bound into the aggregate suite.
- Repository/staging/live evidence states remain separate; no staging or live claim is created by this correction.

Final Packaged / Automated-QA Green status belongs only to the exact corrected HEAD if the restored canonical workflow succeeds on that same HEAD.
