from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
    s=rd(p); n=s.count(a)
    if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:160]!r}')
    wr(p,s.replace(a,b,1))

# Current packaged readme: main 20 rounds are complete; post-correction sweeps and exact-final-head CI remain.
p='readme.txt'; s=rd(p)
once(p,"* Runtime 1.2.15; core schema remains 3.2.0, continuity 1.1.0, Future24 1.0.0. R18-R20 fresh closure reviews and exact-final-head package/CI evidence remain separate requirements before repository closure; staging/live acceptance remains separate.","* Runtime 1.2.15; core schema remains 3.2.0, continuity 1.1.0, Future24 1.0.0. All 20 fifteenth-cycle main reviews are complete: R19 found no new supported defect; R20 found no new product-code defect but corrected closure-document/PR evidence lag. Two extra post-correction verification sweeps plus exact-final-head package/CI evidence remain before repository closure; staging/live acceptance remains separate.")

# Repository README current checkpoint.
p='README.md'; s=rd(p)
once(p,"Current fifteenth-cycle runtime alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. R18 broad adversarial/evidence review is corrected; R19-R20 fresh closure reviews and exact-final-head CI/package evidence remain required before repository closure.","Current fifteenth-cycle runtime alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. All 20 main reviews are complete. R19 was clean; R20 found no new product-code defect and corrected closure-evidence lag. Two extra post-correction verification sweeps and exact-final-head CI/package evidence remain before repository closure.")

# Formal changelog current release summary.
p='CHANGELOG.md'; s=rd(p)
once(p,"- R17 aligns runtime/tests/docs/package identity to 1.2.15 without schema inflation. R18-R20 remain fresh corrected-state closure reviews; exact-final-head package/CI and staging/live evidence remain separate.","- R17 aligned runtime/tests/docs/package identity to 1.2.15 without schema inflation; R18 corrected repository/evidence hygiene, R19 was a clean corrected-state review, and R20 found no new product-code defect while correcting stale closure/PR evidence. Two extra post-correction verification sweeps and exact-final-head package/CI remain required; staging/live evidence remains separate.")

# STATUS current classification and fifteenth-cycle main-round closure.
p='STATUS.md'; s=rd(p)
once(p,"| Coded | **Corrected candidate** — fifteenth-cycle R1-R18 corrections/evidence cleanup are present; R19-R20 fresh closure reviews remain. |","| Coded | **Corrected candidate** — all fifteenth-cycle R1-R20 main reviews are complete; R20 closure-evidence corrections are present and no new R19/R20 product-code defect was found. |")
once(p,"| Fresh post-final-code reviews | **Pending for Fifteenth closure** — R19-R20 have not yet been closed on the R18-corrected state. |","| Fresh post-final-code reviews | **Main closure reviews complete** — R19 was clean and R20 found no new product-code defect; two extra post-correction verification sweeps are pending because R20 updated closure evidence. |")
once(p,"- R18 completed broad plan-to-code/repository-evidence review and its three findings were corrected/retested; R19-R20 remain required fresh corrected-state closure reviews. This checkpoint is not a claim of final repository closure, staging acceptance, live deployment or operational status.","- R18 completed broad plan-to-code/repository-evidence review and its three findings were corrected/retested; R19 was clean; R20 found only closure-evidence/PR metadata lag and no new product-code defect. Two extra post-correction verification sweeps and exact-final-head CI/package evidence remain. This checkpoint is not a claim of staging acceptance, live deployment or operational status.")
s += """

## Fifteenth fresh 20-round main-cycle closure

- Runtime: **1.2.15**; core schema **3.2.0**; continuity **1.1.0**; Future24 **1.0.0**.
- Main review method: each round completed fully before any correction; all supported findings from that closed round were then corrected together and fully retested before the next round.
- Main defect/gap rounds: **R1, R2, R3, R5, R6, R7, R8, R10, R11, R12, R13, R14, R15, R16, R17, R18, R20**.
- Main clean rounds: **R4, R9, R19**.
- R20 finding class was closure/release evidence only; no new product-code/security/privacy/concurrency defect was proven in R19 or R20.
- Because R20 changes closure evidence, two extra fresh post-correction verification sweeps will be completed before exact-final-head repository closure.
- Staging-Accepted: **not claimed**; Live-Deployed: **unverified/not claimed**; Operational: **not claimed**.
"""; wr(p,s)

# Permanent closure-document assertions.
p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R20 packaged readme records all main rounds complete','readme.txt','All 20 fifteenth-cycle main reviews are complete');
t15h('R20 repository README records all main rounds complete','README.md','All 20 main reviews are complete');
t15h('R20 STATUS records main-cycle closure','STATUS.md','Fifteenth fresh 20-round main-cycle closure');
t15h('R20 changelog records clean R19','CHANGELOG.md','R19 was a clean corrected-state review');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
s=s.replace(mark,ins+mark,1); wr(p,s)

# Main-round evidence: R19 and R20 were reviewed before this correction.
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R19 — first fresh corrected-state closure review

R19 completed on exact R18-corrected HEAD `7894adbd9d19b58956342f66bd7f00e8226413ce` before any R19 change. Source/security/concurrency/privacy/integration/release hygiene were freshly re-traced; only the canonical quality workflow remained, the historical 0.2.1 status was clearly labeled historical, current version traceability was 1.2.15, and no new supported product/repository defect was proven. No correction was required and the exact source state was preserved for R20.

R19 result: **CLEAN — no correction required.**

## R20 — second fresh closure review

R20 completed against the same exact corrected product/source state before any R20 correction. No new product-code, authorization, privacy, security, concurrency or package-builder defect was proven. The review did find closure/release evidence lag: `readme.txt`, `README.md` and `STATUS.md` still described already-completed closure rounds as pending, and PR #7 still advertised v1.2.13 / the thirteenth cycle / obsolete HEAD and artifact evidence. The repository-document findings are corrected together after R20 closure; PR metadata is aligned separately after the corrected commit because PR metadata is not repository source.

R20 result: **CLOSURE-EVIDENCE DEFECTS FOUND; NO NEW PRODUCT DEFECT — repository evidence corrected together after review completion; full retest required.**

## Main 20-round result

- Defect/gap rounds: **R1, R2, R3, R5, R6, R7, R8, R10, R11, R12, R13, R14, R15, R16, R17, R18, R20**.
- Clean rounds: **R4, R9, R19**.
- Runtime after main cycle: **1.2.15**; schemas unchanged at core **3.2.0**, continuity **1.1.0**, Future24 **1.0.0**.
- Two extra post-correction verification sweeps are required because R20 changes closure evidence; they are not counted as additional main rounds.
"""; wr(p,s)
