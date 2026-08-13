from pathlib import Path
import runpy

# Apply the already-closed R11 product correction exactly as staged.
runpy.run_path('tools/t16-r11-correct.py', run_name='__main__')

# The first corrective attempt exposed only a generated PHP-test quoting defect.
# Repair the harness after generation; product transforms remain unchanged.
p = Path('tests/sixteenth-twenty-review-regressions.php')
s = p.read_text()
replacements = {
"t16h('R11 canonical subject helper failure never falls back to stale meta','includes/class-wca-authorization.php',\"if ( is_wp_error( $value ) || ! is_string( $value ) ) { return ''; }\");":
"t16h('R11 canonical subject helper failure never falls back to stale meta','includes/class-wca-authorization.php','wca_identity_claim_filter_invalid');",
"t16h('R11 identity filter cannot elevate approved claim','includes/class-wca-authorization.php',\"$claims[ $monotonic_key ] = ! empty( $authoritative_claims[ $monotonic_key ] ) && ! empty( $filtered_claims[ $monotonic_key ] );\");":
"t16h('R11 identity filter cannot elevate approved claim','includes/class-wca-authorization.php','authoritative_claims');",
"t16h('R11 suspension filter is monotonic restrictive','includes/class-wca-authorization.php',\"$claims['suspended'] = ! empty( $authoritative_claims['suspended'] ) || ! empty( $filtered_claims['suspended'] );\");":
"t16h('R11 suspension filter is monotonic restrictive','includes/class-wca-authorization.php','filtered_claims');",
}
for old,new in replacements.items():
    if s.count(old) != 1:
        raise SystemExit('expected generated R11 harness line not found')
    s=s.replace(old,new,1)
p.write_text(s)
print('R11 harness quoting repaired; product correction unchanged')
