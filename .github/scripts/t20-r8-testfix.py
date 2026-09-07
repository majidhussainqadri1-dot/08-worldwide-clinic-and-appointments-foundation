from pathlib import Path
root=Path(__file__).resolve().parents[2]
p=root/'tests/t20-r8-payment-currency-persistence-regressions.php'
t=p.read_text()
t=t.replace('false!==strpos($repo,"WCA_Service::valid_currency( $row[\'currency\'] )")', "false!==strpos($repo, \"WCA_Service::valid_currency( \\$row['currency'] )\")")
t=t.replace('false===strpos($repo,"preg_match( \'/^[A-Z]{3}$/\', $row[\'currency\'] )")', "false===strpos($repo, \"preg_match( '/^[A-Z]{3}$/', \\$row['currency'] )\")")
p.write_text(t)
