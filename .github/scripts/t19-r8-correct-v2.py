from pathlib import Path
import runpy

ROOT = Path(__file__).resolve().parents[2]
runpy.run_path(str(ROOT / '.github/scripts/t19-r8-correct.py'), run_name='__main__')

p = ROOT / 'tests/t19-r8-service-projection-reconciliation-regressions.php'
text = p.read_text(encoding='utf-8')
text = text.replace('as $preserve_field" ),', 'as \\$preserve_field" ),')
text = text.replace("=> $public_services\" ),", "=> \\$public_services\" ),")
p.write_text(text, encoding='utf-8')
print('R8 regression probes made PHP-warning-clean.')
