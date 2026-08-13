from pathlib import Path
p=Path(__file__).resolve().parents[1]/'tests/sixteenth-twenty-review-regressions.php'
s=p.read_text()
s=s.replace("t16h('R14 outbound payment intent carries provider','includes/class-wca-service.php',\"'provider' => $provider\");", "t16h('R14 outbound payment intent carries provider','includes/class-wca-service.php',\"'provider' => \" . '$' . \"provider\");")
s=s.replace("t16h('R14 authorized appointment includes safe payment projection','includes/class-wca-rest.php',\"'payment'           => $payment_projection\");", "t16h('R14 authorized appointment includes safe payment projection','includes/class-wca-rest.php',\"'payment'           => \" . '$' . \"payment_projection\");")
p.write_text(s)
print('R14 regression harness interpolation warnings repaired')
