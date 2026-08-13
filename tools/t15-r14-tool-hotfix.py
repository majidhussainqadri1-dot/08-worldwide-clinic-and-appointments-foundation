from pathlib import Path
p=Path('tools/t15-r14-fix.py')
s=p.read_text()
old='once(p,"\\t\\t\\tselectedHold = null;","\\t\\t\\tselectedHold = null;\\n\\t\\t\\tappointmentRequestKey = null;")'
new='once(p,"\\t\\t\\tslotsNode.replaceChildren();\\n\\t\\t\\tselectedHold = null;","\\t\\t\\tslotsNode.replaceChildren();\\n\\t\\t\\tselectedHold = null;\\n\\t\\t\\tappointmentRequestKey = null;")'
if old not in s:
    raise SystemExit('R14 ambiguous selector target missing')
p.write_text(s.replace(old,new,1))
