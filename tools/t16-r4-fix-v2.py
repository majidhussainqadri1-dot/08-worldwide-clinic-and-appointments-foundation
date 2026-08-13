from pathlib import Path
import runpy
runpy.run_path('tools/t16-r4-fix.py', run_name='__main__')
p=Path('tests/sixteenth-twenty-review-regressions.php')
s=p.read_text()
old='t16h(\'R4 Future24 retains its own mutation guard\',\'includes/class-wca-ten-review-hardening.php\',"strpos( $route, \'/wca/v1/future24/\' )");'
new="t16h('R4 Future24 retains its own mutation guard','includes/class-wca-ten-review-hardening.php','strpos( $route, \\'/wca/v1/future24/\\' )');"
if old not in s:
    raise SystemExit('T16 R4 Future24 assertion quoting target not found')
p.write_text(s.replace(old,new,1))
