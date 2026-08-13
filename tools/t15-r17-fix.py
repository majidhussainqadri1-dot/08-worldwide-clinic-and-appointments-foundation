from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
    s=rd(p); n=s.count(a)
    if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
    wr(p,s.replace(a,b,1))

# Canonical runtime identity.
p='worldwide-clinic.php'; s=rd(p)
if s.count('Version: 1.2.14') != 1 or s.count("define( 'WCA_VERSION', '1.2.14' )") != 1: raise SystemExit('plugin runtime 1.2.14 identity not unique')
s=s.replace('Version: 1.2.14','Version: 1.2.15',1).replace("define( 'WCA_VERSION', '1.2.14' )","define( 'WCA_VERSION', '1.2.15' )",1); wr(p,s)
once('includes/class-wca-contracts.php',"const RUNTIME_VERSION                 = '1.2.14';","const RUNTIME_VERSION                 = '1.2.15';")

# Release package contract follows the exact current runtime.
p='tests/release-package-contract.php'; s=rd(p)
if s.count('1.2.14') != 2: raise SystemExit(f'release-package current-version occurrences expected 2 got {s.count("1.2.14")}')
wr(p,s.replace('1.2.14','1.2.15'))

# Historical T14 gate keeps its historical label but asserts the current superseding release identity.
p='tests/fourteenth-twenty-review-regressions.php'; s=rd(p)
once(p,"t14has('R18 plugin 1.2.14','worldwide-clinic.php','Version: 1.2.14');","t14has('R18+ current plugin release remains aligned','worldwide-clinic.php','Version: 1.2.15');")
once(p,"t14has('R18 runtime 1.2.14','includes/class-wca-contracts.php',\"RUNTIME_VERSION                 = '1.2.14'\");","t14has('R18+ current runtime release remains aligned','includes/class-wca-contracts.php',\"RUNTIME_VERSION                 = '1.2.15'\");")

# Readme current release identity and installation evidence.
p='readme.txt'; s=rd(p)
once(p,'Stable tag: 1.2.14','Stable tag: 1.2.15')
once(p,'Version 1.2.13 implements the File 08 Complete Master Plan','Version 1.2.15 implements the File 08 Complete Master Plan')
once(p,'Download the exact CI-generated File 08 v1.2.13 candidate','Download the exact CI-generated File 08 v1.2.15 candidate')
marker='== Changelog ==\n\n= 1.2.13 ='
entry="""== Changelog ==

= 1.2.15 =
* Fifteenth fresh 20-round corrective cycle aligned the current release after R1-R16 repository corrections: authoritative SQL-read failure handling, privacy/retention truthfulness, outbox/idempotency recovery, exact money validation, object authorization, strict File00/File09 claims, opaque REST replay/privacy, migration snapshots, Future24 transaction/read/scope hardening, browser replay/deep-link safety, cross-file projection reliability, public discovery read safety, and operational health/CLI failure propagation.
* Runtime 1.2.15; core schema remains 3.2.0, continuity 1.1.0, Future24 1.0.0. R18-R20 fresh closure reviews and exact-final-head package/CI evidence remain separate requirements before repository closure; staging/live acceptance remains separate.

= 1.2.14 =
* Fourteenth fresh 20-round corrective closure plus post-main heatmap timezone/DST/privacy and deterministic public-cursor corrections.
* Runtime 1.2.14; core schema 3.2.0; continuity 1.1.0; Future24 1.0.0. Repository evidence remained distinct from staging/live evidence.

= 1.2.13 ="""
if marker not in s: raise SystemExit('readme changelog insertion marker missing')
s=s.replace(marker,entry,1); wr(p,s)

# Formal changelog.
p='CHANGELOG.md'; s=rd(p)
marker='## 1.2.14 — 2026-08-12'
entry="""## 1.2.15 — 2026-08-13

- Fifteenth fresh 20-round corrective audit advanced the runtime identity after R1-R16 substantive corrections: fail-closed authoritative reads, privacy/retention completion integrity, outbox/idempotency recovery, exact currency validation, stricter object/cross-file authorization, migration snapshot provenance, Future24 nested transaction/read/scope safety, opaque REST privacy/replay, browser replay/deep-link safety, public discovery read failure propagation, and operational health/CLI error handling.
- R17 aligns runtime/tests/docs/package identity to 1.2.15 without schema inflation. R18-R20 remain fresh corrected-state closure reviews; exact-final-head package/CI and staging/live evidence remain separate.
- Core schema remains 3.2.0; continuity 1.1.0; Future24 1.0.0.

## 1.2.14 — 2026-08-12"""
if marker not in s: raise SystemExit('CHANGELOG insertion marker missing')
s=s.replace(marker,entry,1); wr(p,s)

# Candidate status: accurate current repository identity, but do not prematurely claim final fresh closure.
p='STATUS.md'; s=rd(p)
once(p,'- Runtime candidate: **1.2.14**','- Runtime candidate: **1.2.15**')
once(p,'| Coded | **Corrected candidate** — fourteenth-cycle source corrections and post-main corrections are present. |','| Coded | **Corrected candidate** — fifteenth-cycle R1-R17 source/release corrections are present; R18-R20 closure review remains. |')
once(p,'| Fresh post-final-code reviews | **Complete at repository/source-review level for Fourteenth closure.** |','| Fresh post-final-code reviews | **Pending for Fifteenth closure** — R18-R20 have not yet been closed on the final corrected code. |')
once(p,'No older v1.2.1 through v1.2.13 artifact or older CI run may be used as evidence for the v1.2.14 candidate.','No older v1.2.1 through v1.2.14 artifact or older CI run may be used as evidence for the v1.2.15 candidate.')
s += """

## Fifteenth fresh 20-round audit — R17 release alignment checkpoint

- Governing method: each review round completes before any correction; all findings from that round are then corrected together and fully retested before the next round.
- R1-R16 completed; R4 and R9 were clean, while supported findings in the other closed rounds were corrected and retested.
- R17 identified stale runtime/release/document/package identity after the substantive corrections and aligns the current candidate to runtime **1.2.15** without schema inflation.
- Core schema: **3.2.0**; continuity schema: **1.1.0**; Future24 schema: **1.0.0**.
- R18-R20 remain required corrected-state/closure reviews. This checkpoint is not a claim of final repository closure, staging acceptance, live deployment or operational status.
"""; wr(p,s)

# Permanent T15 R17 assertions. Use single-quoted needles to avoid PHP interpolation.
p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R17 plugin release 1.2.15','worldwide-clinic.php','Version: 1.2.15');
t15h('R17 runtime release 1.2.15','includes/class-wca-contracts.php',\"RUNTIME_VERSION                 = '1.2.15'\");
t15h('R17 readme stable tag 1.2.15','readme.txt','Stable tag: 1.2.15');
t15h('R17 package contract 1.2.15','tests/release-package-contract.php',\"Version: 1.2.15\");
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
s=s.replace(mark,ins+mark,1); wr(p,s)

# Round evidence.
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R17 — package / version / documentation / release-evidence review

R17 completed against the R16-corrected state before any R17 source change. The deterministic builder/verifier remained exact-commit/runtime-derived, but source, contracts and package tests still declared 1.2.14 while readme description/install guidance still referred to 1.2.13. The corrected source therefore lacked a truthful new release identity. The post-review batch advances the runtime to 1.2.15, aligns package tests/readme/STATUS/CHANGELOG and leaves schemas unchanged. R18-R20 remain required fresh closure reviews.

R17 result: **SUPPORTED RELEASE/EVIDENCE DEFECT FOUND — corrected together after review completion; full retest required before R18.**
"""; wr(p,s)
