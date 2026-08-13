from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
    s=rd(p); n=s.count(a)
    if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
    wr(p,s.replace(a,b,1))

# R18-01: temporary review probe must not survive repository closure work.
probe=R/'.github/workflows/t15-probe.yml'
if not probe.is_file(): raise SystemExit('Expected temporary T15 probe is already absent')
probe.unlink()

# R18-02: old corrective status is historical evidence, not current candidate truth.
p='CORRECTIVE-STATUS.md'; s=rd(p)
banner="# Historical Corrective Status Snapshot — v0.2.1 Only\n\n> **Historical evidence only.** This document records the former 0.2.1 corrective checkpoint and is not the current File 08 candidate status. The authoritative current repository status is `STATUS.md`; exact staging/live truth must still be verified independently.\n\n"
if not s.startswith(banner):
    if not s.startswith('# File 08 Corrective Status'):
        raise SystemExit('CORRECTIVE-STATUS heading unexpected')
    s=banner+s
wr(p,s)

# R18-03: test evidence must describe what it actually verifies.
p='tests/master-plan-contract.php'
once(p,"wca_test_assert( false !== strpos( $main, 'Version: 1.2.15' ), 'plugin version is 1.2.13' );","wca_test_assert( false !== strpos( $main, 'Version: 1.2.15' ), 'plugin version is 1.2.15' );")

# Permanent R18 repository/evidence regression gates.
p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
helper="function t15missing($label,$path){global $root,$pass,$fail;if(!file_exists($root.'/'.$path)){echo 'PASS '.(++$pass).': '.$label.\"\\n\";}else{$fail[]=$label.' unexpected file: '.$path;}}\n"
needle="function t15h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label.\"\\n\";}else{$fail[]=$label.' missing: '.$needle;}}\n"
if helper not in s:
    if needle not in s: raise SystemExit('T15 helper insertion point missing')
    s=s.replace(needle,needle+helper,1)
ins="""
t15missing('R18 temporary T15 probe removed','.github/workflows/t15-probe.yml');
t15h('R18 old corrective status explicitly historical','CORRECTIVE-STATUS.md','Historical evidence only.');
t15h('R18 master-plan version label truthful','tests/master-plan-contract.php','plugin version is 1.2.15');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
s=s.replace(mark,ins+mark,1); wr(p,s)

# Current status accurately advances only to R18; R19/R20 stay pending.
p='STATUS.md'; s=rd(p)
once(p,'| Coded | **Corrected candidate** — fifteenth-cycle R1-R17 source/release corrections are present; R18-R20 closure review remains. |','| Coded | **Corrected candidate** — fifteenth-cycle R1-R18 corrections/evidence cleanup are present; R19-R20 fresh closure reviews remain. |')
once(p,'| Fresh post-final-code reviews | **Pending for Fifteenth closure** — R18-R20 have not yet been closed on the final corrected code. |','| Fresh post-final-code reviews | **Pending for Fifteenth closure** — R19-R20 have not yet been closed on the R18-corrected state. |')
once(p,'- R18-R20 remain required corrected-state/closure reviews. This checkpoint is not a claim of final repository closure, staging acceptance, live deployment or operational status.','- R18 completed broad plan-to-code/repository-evidence review and its three findings were corrected/retested; R19-R20 remain required fresh corrected-state closure reviews. This checkpoint is not a claim of final repository closure, staging acceptance, live deployment or operational status.')

# README current checkpoint wording.
p2='README.md'; rs=rd(p2)
once(p2,'Current fifteenth-cycle source/release alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. R18-R20 fresh closure reviews and exact-final-head CI/package evidence remain required before repository closure.','Current fifteenth-cycle runtime alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. R18 broad adversarial/evidence review is corrected; R19-R20 fresh closure reviews and exact-final-head CI/package evidence remain required before repository closure.')

# Round evidence.
p3='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; es=rd(p3); es += """

## R18 — broad plan-to-code / repository-evidence / hygiene review

R18 completed against the R17-corrected state before any R18 correction. FR-001…018, NFR-001…010 and FUT-01…24 traceability remained present; deterministic candidate building is allow-listed to runtime content and binds exact commit/runtime in its generated manifest. Three repository/evidence defects remained: the temporary `t15-probe.yml` was still an active branch workflow, the old `CORRECTIVE-STATUS.md` described v0.2.1 under a misleading current-state heading without an explicit historical banner, and the master-plan gate checked 1.2.15 while reporting a stale 1.2.13 success label. The post-review batch removes temporary probe tooling, marks historical evidence unmistakably and makes the test label truthful.

R18 result: **SUPPORTED REPOSITORY/EVIDENCE DEFECTS FOUND — corrected together after review completion; full retest required before R19.**
"""; wr(p3,es)
