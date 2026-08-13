from pathlib import Path
p=Path('README.md')
s=p.read_text()
old='- Runtime candidate: **1.2.14**'
new='- Runtime candidate: **1.2.15**'
if old not in s:
    raise SystemExit('README current runtime target missing')
s=s.replace(old,new,1)
# Add a concise current-cycle note without rewriting historical cycle records.
anchor='The authoritative repository release identity is the **exact candidate HEAD + exact-head CI run + deterministic manifest + candidate SHA-256**. Repository evidence never proves the current staging or live installation.\n'
addition='\nCurrent fifteenth-cycle source/release alignment is **1.2.15**; core schema remains **3.2.0**, restricted continuity **1.1.0**, and Future24 **1.0.0**. R18-R20 fresh closure reviews and exact-final-head CI/package evidence remain required before repository closure.\n'
if addition.strip() not in s:
    if anchor not in s: raise SystemExit('README release identity anchor missing')
    s=s.replace(anchor,anchor+addition,1)
p.write_text(s)
