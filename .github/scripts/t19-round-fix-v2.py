from pathlib import Path
import runpy

# R9 frozen-ledger correction wrapper: align historical regression probes with the
# governing patient-own-appointments cursor contract after the complete R9 review.
ROOT = Path(__file__).resolve().parents[2]
runpy.run_path(str(ROOT / '.github/scripts/t19-round-fix.py'), run_name='__main__')

p = ROOT / 'tests/new-plan-hardening.php'
text = p.read_text(encoding='utf-8')
old = "'data-consultation-type', 'wca_page', 'View details',"
new = "'data-consultation-type', 'wca_cursor', 'View details',"
if text.count(old) != 1:
    raise SystemExit('R9 legacy frontend pagination assertion marker missing or duplicated')
p.write_text(text.replace(old, new, 1), encoding='utf-8')

p = ROOT / 'tests/ten-review-regressions.php'
text = p.read_text(encoding='utf-8')
old = "r10has('delegated appointment list',$frontend,\"delegated_clinic_ids( \\\$user_id, 'appointments' )\");"
new = "r10has('canonical patient appointment list',$frontend,'WCA_Query_API::list_patient_appointments');"
if text.count(old) != 1:
    raise SystemExit('R9 legacy delegated appointment-list assertion marker missing or duplicated')
p.write_text(text.replace(old, new, 1), encoding='utf-8')

print('R9 historical regression probes aligned to canonical patient cursor contract.')
