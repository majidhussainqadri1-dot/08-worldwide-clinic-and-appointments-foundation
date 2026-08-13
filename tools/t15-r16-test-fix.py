from pathlib import Path
p=Path('tests/fifteenth-twenty-review-regressions.php')
s=p.read_text()
replacements={
"t15h('R16 CLI health fails when unhealthy','includes/class-wca-cli.php',\"empty( $health['ok'] )\");":"t15h('R16 CLI health fails when unhealthy','includes/class-wca-cli.php','empty( $health[\\'ok\\'] )');",
"t15h('R16 health includes cron state','includes/class-wca-observability.php',\"self::all_true( $checks['cron'] )\");":"t15h('R16 health includes cron state','includes/class-wca-observability.php','self::all_true( $checks[\\'cron\\'] )');",
"t15h('R16 health includes legacy checks','includes/class-wca-observability.php',\"self::all_true( $checks['legacy_checks'] )\");":"t15h('R16 health includes legacy checks','includes/class-wca-observability.php','self::all_true( $checks[\\'legacy_checks\\'] )');",
}
for old,new in replacements.items():
    if old not in s: raise SystemExit('R16 literal target missing: '+old)
    s=s.replace(old,new,1)
p.write_text(s)
