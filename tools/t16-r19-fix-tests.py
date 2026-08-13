from pathlib import Path
p=Path('tests/sixteenth-twenty-review-regressions.php'); s=p.read_text()
marker='if($fail){fwrite(STDERR,"T16 regression gate failed:\\n- ".implode("\\n- ",$fail)."\\n");exit(1);} echo "T16 regression assertions passed: {$pass}/{$pass}\\n";'
if s.count(marker)!=1: raise SystemExit(f'T16 final marker mismatch: {s.count(marker)}')
add="""t16h('R19 canonical assets are shortcode/route scoped','includes/class-wca-plugin.php','has_shortcode( (string) $post->post_content, $shortcode )');
t16n('R19 canonical assets no longer load on every singular page','includes/class-wca-plugin.php','! WCA_Routes::route() && ! is_singular()');
t16h('R19 canonical client loads WordPress i18n runtime','includes/class-wca-plugin.php',\"array( 'wp-i18n' )\");
t16h('R19 continuity client loads WordPress i18n runtime','includes/class-wca-continuity-secure.php',\"'wca-continuity', WCA_URL . 'assets/js/continuity.js', array( 'wp-i18n' )\");
t16h('R19 Future24 client loads WordPress i18n runtime','includes/class-wca-future24.php',\"'wca-future24', WCA_URL . 'assets/js/future24.js', array( 'wp-i18n' )\");
t16h('R19 booking client has translation bridge','assets/js/clinic.js','function tr(message)');
t16h('R19 continuity client has translation bridge','assets/js/continuity.js','function tr(message)');
t16h('R19 Future24 client has translation bridge','assets/js/future24.js','function tr(message)');
t16h('R19 booking search has stale-response generation fence','assets/js/clinic.js','generation !== searchGeneration');
t16h('R19 slot hold has stale-response generation fence','assets/js/clinic.js','requestGeneration !== holdGeneration');
t16h('R19 failed alternate hold clears stale selected hold','assets/js/clinic.js','selectedHold = null; appointmentRequestKey = null; setStatus');
t16h('R19 booking detail change invalidates selected hold','assets/js/clinic.js',\"field.addEventListener('change', invalidateSelection)\");
t16h('R19 concurrent slot buttons are disabled during hold','assets/js/clinic.js',\"item.setAttribute('aria-pressed', 'false'); item.disabled = true;\");
"""
p.write_text(s.replace(marker,add+marker,1))
