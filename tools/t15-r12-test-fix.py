from pathlib import Path
p = Path('tests/fifteenth-twenty-review-regressions.php')
s = p.read_text()
old = "t15h('R12 application currency exact validation','includes/class-wca-service.php','\\$currency_raw = trim');"
new = "t15h('R12 application currency exact validation','includes/class-wca-service.php','$currency_raw = trim');"
if old not in s:
    raise SystemExit('R12 regression literal target missing')
p.write_text(s.replace(old, new, 1))
