from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
 wr(p,s.replace(a,b,1))

p='includes/class-wca-cli.php'
once(p,"\tpublic static function health() { WP_CLI::line( wp_json_encode( WCA_Observability::health(), JSON_PRETTY_PRINT ) ); }","\tpublic static function health() { $health = WCA_Observability::health(); WP_CLI::line( wp_json_encode( $health, JSON_PRETTY_PRINT ) ); if ( empty( $health['ok'] ) ) { WP_CLI::error( 'File 08 health checks are not green.' ); } }")
once(p,"\tpublic static function outbox( $args, $assoc ) { $count = WCA_Outbox::process( absint( $assoc['limit'] ?? 100 ) ); WP_CLI::success( sprintf( '%d outbox messages processed.', $count ) ); }","\tpublic static function outbox( $args, $assoc ) { $count = WCA_Outbox::process( absint( $assoc['limit'] ?? 100 ) ); if ( is_wp_error( $count ) ) { WP_CLI::error( $count->get_error_message() ); } WP_CLI::success( sprintf( '%d outbox messages processed.', $count ) ); }")

p='includes/class-wca-observability.php'
once(p,"\t\t$checks['ok'] = self::all_true( $checks['schema'] ) && (bool) $checks['dependencies'];","\t\t$checks['ok'] = self::all_true( $checks['schema'] ) && (bool) $checks['dependencies'] && self::all_true( $checks['legacy_checks'] ) && self::all_true( $checks['cron'] );")
once(p,"\tprivate static function all_true( $values ) {\n\t\tforeach ( (array) $values as $value ) {\n\t\t\tif ( is_bool( $value ) && ! $value ) {\n\t\t\t\treturn false;\n\t\t\t}\n\t\t}\n\t\treturn true;\n\t}","\tprivate static function all_true( $values ) {\n\t\tforeach ( (array) $values as $value ) {\n\t\t\tif ( is_array( $value ) && ! self::all_true( $value ) ) { return false; }\n\t\t\tif ( is_bool( $value ) && ! $value ) { return false; }\n\t\t}\n\t\treturn true;\n\t}")

p='includes/class-wca-outbox.php'
once(p,"\tpublic static function hooks() {\n\t\tadd_action( self::CRON_HOOK, array( __CLASS__, 'process' ) );\n\t\tadd_action( self::MAINTENANCE_HOOK, array( __CLASS__, 'maintenance' ) );","\tpublic static function hooks() {\n\t\tadd_action( self::CRON_HOOK, array( __CLASS__, 'cron_process' ) );\n\t\tadd_action( self::MAINTENANCE_HOOK, array( __CLASS__, 'cron_maintenance' ) );")
once(p,"\tpublic static function opportunistic_process() {\n\t\tif ( wp_doing_cron() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {","\tpublic static function cron_process() {\n\t\t$result = self::process( self::BATCH_SIZE );\n\t\tif ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'outbox_cron_failed', array( 'error_code' => $result->get_error_code() ) ); }\n\t\treturn $result;\n\t}\n\n\tpublic static function cron_maintenance() {\n\t\t$result = self::maintenance();\n\t\tif ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'maintenance_cron_failed', array( 'error_code' => $result->get_error_code() ) ); }\n\t\treturn $result;\n\t}\n\n\tpublic static function opportunistic_process() {\n\t\tif ( wp_doing_cron() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {")
once(p,"\t\tset_transient( 'wca_outbox_opportunistic_lock', 1, MINUTE_IN_SECONDS );\n\t\tself::process( 5 );\n\t}","\t\tset_transient( 'wca_outbox_opportunistic_lock', 1, MINUTE_IN_SECONDS );\n\t\t$result = self::process( 5 );\n\t\tif ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'outbox_opportunistic_failed', array( 'error_code' => $result->get_error_code() ) ); }\n\t\treturn $result;\n\t}")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R16 CLI outbox fails on WP_Error','includes/class-wca-cli.php','if ( is_wp_error( $count ) )');
t15h('R16 CLI health fails when unhealthy','includes/class-wca-cli.php',"empty( $health['ok'] )");
t15h('R16 health includes cron state','includes/class-wca-observability.php',"self::all_true( $checks['cron'] )");
t15h('R16 health includes legacy checks','includes/class-wca-observability.php',"self::all_true( $checks['legacy_checks'] )");
t15h('R16 cron process wrapper logs worker failure','includes/class-wca-outbox.php','outbox_cron_failed');
t15h('R16 maintenance cron wrapper logs failure','includes/class-wca-outbox.php','maintenance_cron_failed');
t15h('R16 opportunistic worker logs failure','includes/class-wca-outbox.php','outbox_opportunistic_failed');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
wr(p,s.replace(mark,ins+mark,1))

p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R16 — cron / CLI / maintenance / observability review

R16 completed before correction. WP-CLI outbox could report success on a WP_Error, the CLI health command did not fail its exit status on unhealthy state, overall health ignored cron/legacy-system-check failures, and top-level cron/shutdown outbox errors could be returned without a guaranteed operational log. The post-review batch makes CLI outcomes authoritative, folds cron/system checks into health and wraps scheduled/opportunistic execution with explicit failure logging.

R16 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R17.**
"""; wr(p,s)
