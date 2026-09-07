from pathlib import Path
root = Path(__file__).resolve().parents[2]
path = root / 'tests/t20-r5-idempotency-header-parity-regressions.php'
text = path.read_text()
text = text.replace('false !== strpos( $hard, "$request->get_header( \'Idempotency-Key\' )" )', "false !== strpos( $hard, '$request->get_header( \\'Idempotency-Key\\' )' )")
text = text.replace('false !== strpos( $rest, "$header_key = trim( (string) $request->get_header( \'Idempotency-Key\' ) )" )', "false !== strpos( $rest, '$header_key = trim( (string) $request->get_header( \\'Idempotency-Key\\' ) )' )")
text = text.replace('false !== strpos( $rest, "$data[\'idempotency_key\'] = $header_key" )', "false !== strpos( $rest, '$data[\\'idempotency_key\\'] = $header_key' )")
text = text.replace('false !== strpos( $opaque, "$header_key = trim( (string) $request->get_header( \'Idempotency-Key\' ) )" )', "false !== strpos( $opaque, '$header_key = trim( (string) $request->get_header( \\'Idempotency-Key\\' ) )' )")
text = text.replace('false !== strpos( $opaque, "$data[\'idempotency_key\'] = $header_key" )', "false !== strpos( $opaque, '$data[\\'idempotency_key\\'] = $header_key' )")
text = text.replace('false !== strpos( $guard, "empty( $data[\'idempotency_key\'] )" )', "false !== strpos( $guard, 'empty( $data[\\'idempotency_key\\'] )' )")
path.write_text(text)
