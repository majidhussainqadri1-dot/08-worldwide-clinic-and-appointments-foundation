from pathlib import Path
import subprocess
import sys

p = Path('TWELFTH-TWENTY-REVIEW-EVIDENCE.md')
s = p.read_text()
old = 'Rounds T12-R19–T12-R20 remain unreviewed in this sequential cycle at this commit.\n'
new = "| T12-R19 | DEFECT CORRECTED | Canonical service/availability numeric values could still be silently clamped/normalized at service and repository persistence roots. |\n| T12-R20 | DEFECT CORRECTED | Clinic disruption state could persist while one or more required File19 notification projections failed. |\n\nAll 20 rounds contained a supported repository defect/gap and each correction passed full corrected-state QA before the next accepted product round.\n"
if old in s:
    s = s.replace(old, new, 1)
elif 'T12-R20 | DEFECT CORRECTED' not in s:
    raise SystemExit('twelfth evidence close anchor missing')
p.write_text(s)
subprocess.check_call([sys.executable, 'tools/twelfth-twenty-review-patcher.py', 'release'])
print('Twelfth release identity/evidence closure applied')
