from pathlib import Path
p=Path('tests/sixteenth-twenty-review-regressions.php'); s=p.read_text()
helper="function t16h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label.\"\\n\";}else{$fail[]=$label.' missing: '.$needle;}}"
if s.count(helper)!=1: raise SystemExit('T16 helper anchor mismatch')
extra_helpers="""
function t16n($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false===strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\\n";}else{$fail[]=$label.' forbidden needle present: '.$needle;}}
function t16order($label,$path,$first,$second){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);$a=is_string($s)?strpos($s,$first):false;$b=is_string($s)?strpos($s,$second):false;if(false!==$a&&false!==$b&&$a<$b){echo 'PASS '.(++$pass).': '.$label."\\n";}else{$fail[]=$label.' order invariant failed';}}
"""
s=s.replace(helper,helper+extra_helpers,1)
marker='if($fail){fwrite(STDERR,"T16 regression gate failed:\\n- ".implode("\\n- ",$fail)."\\n");exit(1);} echo "T16 regression assertions passed: {$pass}/{$pass}\\n";'
if s.count(marker)!=1: raise SystemExit('T16 final marker mismatch')
add="""t16n('R18 continuity no longer has an independent activation hook','worldwide-clinic.php',\"register_activation_hook( WCA_FILE, array( 'WCA_Continuity', 'activate' ) )\");
t16n('R18 Future24 no longer has an independent activation hook','worldwide-clinic.php',\"register_activation_hook( WCA_FILE, array( 'WCA_Future24', 'activate' ) )\");
t16h('R18 rollback-aware activation installs continuity schema','includes/class-swc-activator.php','WCA_Continuity::install_schema();');
t16h('R18 rollback-aware activation installs Future24 schema','includes/class-swc-activator.php','WCA_Future24::install_schema();');
t16h('R18 runtime migration failure-state write is checked','worldwide-clinic.php','runtime_migration_failure_state_persistence_failed');
t16h('R18 stale migration-failure marker prevents boot','worldwide-clinic.php','runtime_migration_failure_state_clear_failed');
t16order('R18 migration-state evidence precedes schema-version commit marker','includes/class-wca-schema.php',\"self::OPTION_MIGRATION_STATE, $migration_state\",\"self::OPTION_DB_VERSION, WCA_Contracts::SCHEMA_VERSION\");
t16h('R18 incomplete activation rollback is never called successful','includes/class-swc-activator.php','activation failed and rollback is incomplete');
t16h('R18 deactivation clears daily health cron','includes/class-swc-activator.php',\"wp_clear_scheduled_hook( 'wca_daily_health_snapshot' )\");
t16h('R18 continuity health exposes boolean schema-current','includes/class-wca-continuity-secure.php',\"'schema_current' => self::SCHEMA_VERSION\");
t16h('R18 Future24 has explicit schema health','includes/class-wca-future24.php','public static function health()');
t16h('R18 overall health includes runtime migration failure state','includes/class-wca-observability.php',\"'runtime_failure_absent'\");
t16h('R18 CLI migration rejects WP Error','includes/class-wca-cli.php','if ( is_wp_error( $count ) )');
t16h('R18 destructive purge requires verified backup assertion','includes/class-wca-privacy.php','wca_purge_backup_verified');
t16h('R18 destructive purge blocks legal holds','includes/class-wca-privacy.php','wca_purge_legal_hold');
t16order('R18 purge preflight happens before first deletion batch','includes/class-swc-activator.php','WCA_Privacy::assert_purge_allowed','do {');
t16h('R18 purge removes canonical File08 schema','includes/class-swc-activator.php','WCA_Schema::purge_canonical_data()');
t16h('R18 continuity exposes complete owned-data purge','includes/class-wca-continuity-secure.php','public static function purge_owned_data()');
t16h('R18 Future24 exposes complete owned-data purge','includes/class-wca-future24.php','public static function purge_owned_data()');
t16h('R18 partial destructive purge is observable','includes/class-swc-activator.php','irreversible_purge_partial_failure');
"""
p.write_text(s.replace(marker,add+marker,1))
