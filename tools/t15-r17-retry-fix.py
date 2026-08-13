from pathlib import Path

# Every remaining 1.2.14 occurrence under tests after the main R17 transform is a
# current-release assertion (the historical changelog is not under tests).
for p in Path('tests').glob('*.php'):
    s = p.read_text()
    if '1.2.14' in s:
        p.write_text(s.replace('1.2.14', '1.2.15'))

# The main R17 transformer intentionally inserts history, but its buffered write
# can restore the pre-R17 readme header/description/install wording. Reassert only
# the current-release locations while preserving the historical 1.2.14 changelog.
p = Path('readme.txt')
s = p.read_text()
s = s.replace('Stable tag: 1.2.14', 'Stable tag: 1.2.15', 1)
s = s.replace('Version 1.2.13 implements the File 08 Complete Master Plan', 'Version 1.2.15 implements the File 08 Complete Master Plan', 1)
s = s.replace('Download the exact CI-generated File 08 v1.2.13 candidate', 'Download the exact CI-generated File 08 v1.2.15 candidate', 1)
p.write_text(s)

# Same buffered-write issue for current STATUS fields; preserve the historical
# Fourteenth closure section at 1.2.14.
p = Path('STATUS.md')
s = p.read_text()
s = s.replace('- Runtime candidate: **1.2.14**', '- Runtime candidate: **1.2.15**', 1)
s = s.replace('| Coded | **Corrected candidate** — fourteenth-cycle source corrections and post-main corrections are present. |', '| Coded | **Corrected candidate** — fifteenth-cycle R1-R17 source/release corrections are present; R18-R20 closure review remains. |', 1)
s = s.replace('| Fresh post-final-code reviews | **Complete at repository/source-review level for Fourteenth closure.** |', '| Fresh post-final-code reviews | **Pending for Fifteenth closure** — R18-R20 have not yet been closed on the final corrected code. |', 1)
s = s.replace('No older v1.2.1 through v1.2.13 artifact or older CI run may be used as evidence for the v1.2.14 candidate.', 'No older v1.2.1 through v1.2.14 artifact or older CI run may be used as evidence for the v1.2.15 candidate.', 1)
p.write_text(s)

# Hard fail if any test still pins the old current release.
stale=[]
for p in Path('tests').glob('*.php'):
    if '1.2.14' in p.read_text(): stale.append(str(p))
if stale:
    raise SystemExit('Stale current-version test pins remain: ' + ', '.join(stale))
