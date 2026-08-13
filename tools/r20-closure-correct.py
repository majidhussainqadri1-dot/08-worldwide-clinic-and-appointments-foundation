from pathlib import Path

p = Path('README.md')
s = p.read_text()
old = "Current fifteenth-cycle runtime alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. All 20 main reviews are complete. R19 was clean; R20 found no new product-code defect and corrected closure-evidence lag. Two extra post-correction verification sweeps and exact-final-head CI/package evidence remain before repository closure."
new = "Current sixteenth-cycle runtime alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. All 20 sequential T16 reviews are complete. Defects were found in **R1, R4, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19 and R20**; **R2, R3 and R5** were clean. Every defect round was corrected only after its review closed and was fully retested before the next round. R20 found repository/release-evidence closure defects, not a new functional PHP/JavaScript defect. Exact-final-head canonical CI/package evidence remains an external release gate; staging/live evidence remains separate."
if s.count(old) != 1:
    raise SystemExit(f'README current-cycle anchor mismatch: {s.count(old)}')
p.write_text(s.replace(old, new, 1))

p = Path('STATUS.md')
s = p.read_text()
old = "| Coded | **Corrected candidate** — all fifteenth-cycle R1-R20 main reviews are complete; no new product-code defect was found in R19 or R20. |\n| Fresh post-final-code reviews | **Main R19/R20 closure reviews complete** — post-correction verification was restarted after Sweep B found and corrected this status-document contradiction. |"
new = "| Coded | **Corrected T16 candidate** — all sixteenth-cycle R1-R20 sequential reviews are complete; R20 found closure/release-evidence defects but no new functional PHP/JavaScript defect. |\n| Fresh post-final-code reviews | **R20 correction verification required on the corrected tree** — two fresh read-only verification sweeps plus exact-final-head canonical CI/package evidence are release gates. |"
if s.count(old) != 1:
    raise SystemExit(f'STATUS classification anchor mismatch: {s.count(old)}')
s = s.replace(old, new, 1)
marker = '## Thirteenth fresh 20-round corrected-state closure'
section = """## Sixteenth fresh 20-round sequential audit — repository closure checkpoint

- Governing sequence: each round was reviewed to completion before any correction; the closed round's supported findings were corrected together and fully retested before the next round.
- Defect rounds: **R1, R4, R6, R7, R8, R9, R10, R11, R12, R13, R14, R15, R16, R17, R18, R19, R20**.
- Clean rounds: **R2, R3, R5**.
- R20 classification: repository/release-evidence closure defects only; the final R20 review proved full source QA green and deterministic packaging before cleanup, with no new functional PHP/JavaScript defect.
- Runtime: **1.2.15**; core schema **3.2.0**; continuity **1.1.0**; Future24 **1.0.0**.
- Temporary T16 correction workflows/scripts/diagnostic evidence are removed by the R20 correction batch and guarded by a permanent closure-hygiene regression.
- Exact-final-head canonical CI, candidate manifest/artifact/SHA-256 and PR evidence are external gates and must identify the same final commit.
- Staging-Accepted: **not claimed**; Live-Deployed: **unverified/not claimed**; Operational: **not claimed**.

"""
if s.count(marker) != 1:
    raise SystemExit(f'STATUS insertion anchor mismatch: {s.count(marker)}')
p.write_text(s.replace(marker, section + marker, 1))

p = Path('CHANGELOG.md')
s = p.read_text()
marker = '## 1.2.15 — 2026-08-13\n\n'
addition = """- Sixteenth fresh sequential 20-round audit completed with strict review-then-batch-fix sequencing. Defect rounds: R1, R4 and R6-R20; clean rounds: R2, R3 and R5. R14-R19 closed payment snapshot/status, calendar webhook/busy-state, complaint lifecycle, guardian/consent degraded-state, migration/rollback/purge integrity, and frontend localization/race defects. R20 closed repository/release-evidence hygiene without finding a new functional PHP/JavaScript defect.
- R20 removes one-shot T16 correction workflows/scripts/diagnostic residue, adds permanent closure-hygiene regression coverage, and makes the canonical quality workflow emit the exact candidate SHA-256. Exact-final-head CI/package evidence remains distinct from staging/live evidence.
"""
if s.count(marker) != 1:
    raise SystemExit(f'CHANGELOG anchor mismatch: {s.count(marker)}')
p.write_text(s.replace(marker, marker + addition, 1))

p = Path('.github/workflows/file08-complete-quality.yml')
s = p.read_text()
old = "      - name: Independent verification\n        run: php tools/verify-candidate.php --artifact=build/a --commit='${{ steps.source.outputs.sha }}'"
new = "      - name: Independent verification\n        run: |\n          php tools/verify-candidate.php --artifact=build/a --commit='${{ steps.source.outputs.sha }}'\n          cat build/a/*-candidate.sha256"
if s.count(old) != 1:
    raise SystemExit(f'canonical checksum evidence anchor mismatch: {s.count(old)}')
p.write_text(s.replace(old, new, 1))

Path('tests/sixteenth-cycle-closure-hygiene.php').write_text("""<?php
$root = dirname( __DIR__ );
$fail = array();
$patterns = array(
    $root . '/tools/t16-*',
    $root . '/.github/workflows/t16-*',
    $root . '/review-evidence/t16-*',
);
foreach ( $patterns as $pattern ) {
    foreach ( glob( $pattern ) ?: array() as $path ) {
        $fail[] = str_replace( $root . '/', '', $path );
    }
}
$readme = file_get_contents( $root . '/README.md' );
$status = file_get_contents( $root . '/STATUS.md' );
if ( false === strpos( (string) $readme, 'Current sixteenth-cycle runtime alignment' ) ) {
    $fail[] = 'README missing current sixteenth-cycle evidence label';
}
if ( false === strpos( (string) $status, 'Sixteenth fresh 20-round sequential audit' ) ) {
    $fail[] = 'STATUS missing current sixteenth-cycle closure section';
}
if ( $fail ) {
    fwrite( STDERR, "Sixteenth-cycle closure hygiene failed:\n- " . implode( "\n- ", $fail ) . "\n" );
    exit( 1 );
}
echo "Sixteenth-cycle closure hygiene: PASS\n";
""")

p = Path('tests/run-all.php')
s = p.read_text()
old = "'sixteenth-twenty-review-regressions.php' );"
new = "'sixteenth-twenty-review-regressions.php', 'sixteenth-cycle-closure-hygiene.php' );"
if s.count(old) != 1:
    raise SystemExit(f'run-all closure-test anchor mismatch: {s.count(old)}')
p.write_text(s.replace(old, new, 1))
