from pathlib import Path
p=Path('includes/class-wca-rest.php')
s=p.read_text()
old="$payload = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );"
if old not in s: raise SystemExit('cursor payload anchor missing')
s=s.replace(old,"$payload = bin2hex( $json );",1)
s=s.replace("/^([A-Za-z0-9_-]+)\\.([0-9a-f]{64})$/","/^([0-9a-f]+)\\.([0-9a-f]{64})$/",1)
old2="""$encoded = strtr( $matches[1], '-_', '+/' );
		$padding = strlen( $encoded ) % 4; if ( $padding ) { $encoded .= str_repeat( '=', 4 - $padding ); }
		$json = base64_decode( $encoded, true );"""
if old2 not in s: raise SystemExit('cursor decode anchor missing')
s=s.replace(old2,"$json = hex2bin( $matches[1] );",1)
if 'base64_decode(' in s: raise SystemExit('base64_decode remains in REST source')
p.write_text(s)
print('Cursor encoding changed to signed hex payload without forbidden decode primitive.')
