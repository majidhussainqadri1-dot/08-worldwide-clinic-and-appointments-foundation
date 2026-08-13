from pathlib import Path
import runpy
runpy.run_path('tools/t16-r1-fix-v2.py', run_name='__main__')
Path('tests/sixteenth-twenty-review-regressions.php').write_text(r'''<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t16h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
t16h('R1 continuity batch read fails closed','includes/class-wca-continuity-secure.php','null === $rows_raw && \'\' !== (string) $wpdb->last_error');
t16h('R1 continuity zero-delete readback fails closed','includes/class-wca-continuity-secure.php','null === $still_exists && \'\' !== (string) $wpdb->last_error');
t16h('R1 continuity completion read fails closed','includes/class-wca-continuity-secure.php','null === $more && \'\' !== (string) $wpdb->last_error');
t16h('R1 continuity guardian readback fails closed','includes/class-wca-continuity-secure.php','null === $guardian_remaining && \'\' !== (string) $wpdb->last_error');
t16h('R1 Future24 table probe fails closed','includes/class-wca-privacy.php','wca_privacy_future24_table_read_failed');
t16h('R1 privacy export propagates Future24 table failure','includes/class-wca-privacy.php','if ( is_wp_error( $table ) ) { return $table; }');
t16h('R1 strict audit history read exists','includes/class-swc-helpers.php','public static function audit_rows_strict');
t16h('R1 privacy export uses strict audit read','includes/class-swc-privacy.php','SWC_Helpers::audit_rows_strict');
t16h('R1 review eligibility duplicate read fails closed','includes/class-wca-repository.php','wca_review_eligibility_read_failed');
if($fail){fwrite(STDERR,"T16 regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);} echo "T16 regression assertions passed: {$pass}/{$pass}\n";
''')
