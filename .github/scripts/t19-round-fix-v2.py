from pathlib import Path
import runpy

# Trigger marker: R9 frozen ledger correction retry after legacy pagination-gate alignment.
ROOT = Path(__file__).resolve().parents[2]
runpy.run_path(str(ROOT / '.github/scripts/t19-round-fix.py'), run_name='__main__')

p = ROOT / 'tests/new-plan-hardening.php'
text = p.read_text(encoding='utf-8')
old = "'data-consultation-type', 'wca_page', 'View details',"
new = "'data-consultation-type', 'wca_cursor', 'View details',"
if text.count(old) != 1:
    raise SystemExit('R9 legacy frontend pagination assertion marker missing or duplicated')
p.write_text(text.replace(old, new, 1), encoding='utf-8')
print('R9 legacy hardening pagination assertion aligned to canonical cursor contract.')
