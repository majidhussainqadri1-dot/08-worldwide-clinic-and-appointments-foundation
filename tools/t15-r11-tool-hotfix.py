from pathlib import Path
p=Path('tools/t15-r11-fix.py')
s=p.read_text()
old="""def once(p,a,b):
    s=rd(p); n=s.count(a)
    if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
    wr(p,s.replace(a,b,1))
"""
new="""def once(p,a,b):
    s=rd(p); n=s.count(a)
    allow_first = n == 2 and \"$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;\" in a and \"$table = self::tables()['records'];\" in a
    if n!=1 and not allow_first: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
    wr(p,s.replace(a,b,1))
"""
if old not in s:
    raise SystemExit('R11 selector helper not found')
p.write_text(s.replace(old,new,1))
