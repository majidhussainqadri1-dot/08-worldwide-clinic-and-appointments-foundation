from pathlib import Path
p=Path('tests/sixteenth-twenty-review-regressions.php'); s=p.read_text()
marker='if($fail){fwrite(STDERR,"T16 regression gate failed:\\n- ".implode("\\n- ",$fail)."\\n");exit(1);} echo "T16 regression assertions passed: {$pass}/{$pass}\\n";'
if s.count(marker)!=1: raise SystemExit(f'final marker mismatch {s.count(marker)}')
add="""t16h('R17 canonical minor assertion rejects non-boolean provider values','includes/class-wca-central-governance.php',\"$minor_raw, array( true, false, 1, 0, '1', '0' )\");
t16h('R17 guardian verification provider failure is degraded','includes/class-wca-central-governance.php','wca_guardian_verification_provider_failure');
t16h('R17 guardian relationship provider failure is degraded','includes/class-wca-central-governance.php','wca_guardian_relationship_provider_failure');
t16h('R17 active consent DB failure is explicit','includes/class-wca-continuity-secure.php','wca_active_consent_read_failed');
t16h('R17 intake propagates consent read failure','includes/class-wca-continuity-secure.php','$active_consent = self::active_consent');
t16h('R17 File17 context pre-reads consent strictly','includes/class-wca-continuity-secure.php','$messaging_consent = self::active_consent');
t16h('R17 follow-up propagates consent read failure','includes/class-wca-continuity-secure.php','$followup_consent = self::active_consent');
"""
p.write_text(s.replace(marker,add+marker,1))
