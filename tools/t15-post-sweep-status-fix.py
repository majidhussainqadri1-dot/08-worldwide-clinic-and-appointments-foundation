from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def replace_required(s,old,new,label):
    n=s.count(old)
    if n!=1: raise SystemExit(f'{label}: expected 1 got {n}')
    return s.replace(old,new,1)

p=R/'STATUS.md'; s=p.read_text()
s=replace_required(s,
'| Coded | **Corrected candidate** — fifteenth-cycle R1-R18 corrections/evidence cleanup are present; R19-R20 fresh closure reviews remain. |',
'| Coded | **Corrected candidate** — all fifteenth-cycle R1-R20 main reviews are complete; no new product-code defect was found in R19 or R20. |','STATUS coded row')
s=replace_required(s,
'| Fresh post-final-code reviews | **Pending for Fifteenth closure** — R19-R20 have not yet been closed on the R18-corrected state. |',
'| Fresh post-final-code reviews | **Main R19/R20 closure reviews complete** — post-correction verification was restarted after Sweep B found and corrected this status-document contradiction. |','STATUS review row')
s=replace_required(s,
'- R18 completed broad plan-to-code/repository-evidence review and its three findings were corrected/retested; R19-R20 remain required fresh corrected-state closure reviews. This checkpoint is not a claim of final repository closure, staging acceptance, live deployment or operational status.',
'- R18 completed broad plan-to-code/repository-evidence review and its three findings were corrected/retested; R19 was clean; R20 found only closure-evidence/PR metadata lag and no new product-code defect. Post-correction verification is being repeated after the status contradiction found by Sweep B. This is not a claim of staging acceptance, live deployment or operational status.','STATUS R17 checkpoint')
s += '''

## Post-R20 verification restart

- Corrected main-cycle HEAD after R20 evidence update: `0ddc816cba623404e79cf130491e6b77553b12c7`.
- Post-correction Sweep A: **PASS** — compare from R18 source HEAD showed only documentation/permanent-regression evidence changed; no runtime PHP/JS file changed, and only the canonical quality workflow remained.
- Post-correction Sweep B: **finding** — the lower main-cycle closure section was correct, but the upper status classification and R17 checkpoint still said R19/R20 were pending because a buffered documentation write restored stale text.
- This status-only contradiction is corrected here. Because verification evidence changed, two final fresh read-only sweeps are restarted on the new corrected exact HEAD before exact-final-head CI/package closure.
- Runtime remains **1.2.15**; schemas remain core **3.2.0**, continuity **1.1.0**, Future24 **1.0.0**.
'''
p.write_text(s)

p=R/'FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=p.read_text(); s += '''

## Post-R20 verification restart record

- Sweep A on `0ddc816cba623404e79cf130491e6b77553b12c7`: PASS. R20 changed only closure documentation/permanent regression evidence relative to the R18-reviewed product tree; no runtime PHP/JS changed and only the canonical quality workflow remained.
- Sweep B on the same HEAD: found one evidence-only contradiction inside `STATUS.md`: its lower Fifteenth main-cycle closure was current, while the upper classification table and R17 checkpoint still said R19/R20 were pending.
- The contradiction is corrected after Sweep B. No product runtime or schema change is made. Two final fresh read-only verification sweeps are restarted on the corrected status HEAD and are not counted among the 20 main review rounds.
'''; p.write_text(s)
