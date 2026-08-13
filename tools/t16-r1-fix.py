from pathlib import Path
import re

ROOT=Path('.')

def read(p): return (ROOT/p).read_text()
def write(p,s): (ROOT/p).write_text(s)
def once(p,old,new):
    s=read(p); n=s.count(old)
    if n!=1: raise SystemExit(f'{p}: expected 1 match, got {n}: {old[:100]!r}')
    write(p,s.replace(old,new,1))
def regex_once(p,pat,repl):
    s=read(p); out,n=re.subn(pat,repl,s,count=1,flags=re.S)
    if n!=1: raise SystemExit(f'{p}: regex expected 1, got {n}: {pat[:100]}')
    write(p,out)

# R1-A: continuity privacy eraser must never treat DB read failure as empty/completed.
p='includes/class-wca-continuity-secure.php'
once(p,
"\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d\", $user_id, $cursor, 100 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t$last = $cursor;",
"\t\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d\", $user_id, $cursor, 100 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) {\n\t\t\t\t$messages[] = __( 'Continuity privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' );\n\t\t\t\t$done = false;\n\t\t\t\tcontinue;\n\t\t\t}\n\t\t\t$rows = (array) $rows_raw;\n\t\t\t$last = $cursor;"
)
once(p,
"\t\t\t\t\t$still_exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE id=%d AND {$field}=%d\", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( $still_exists ) { $messages[] = __( 'Continuity privacy erasure could not remove an affected record and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }",
"\t\t\t\t\t$still_exists = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE id=%d AND {$field}=%d\", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\t\tif ( null === $still_exists && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Continuity privacy erasure could not verify a zero-row delete safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }\n\t\t\t\t\tif ( $still_exists ) { $messages[] = __( 'Continuity privacy erasure could not remove an affected record and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }"
)
once(p,
"\t\t\t$more = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT 1\", $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }",
"\t\t\t$more = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT 1\", $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $more && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Continuity privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }\n\t\t\telseif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }"
)
once(p,
"\t\t\t$guardian_remaining = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$intake_table} WHERE guardian_user_id=%d LIMIT 1\", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $guardian_remaining ) { $messages[] = __( 'Guardian continuity references remain linked and will retry.', 'worldwide-clinic-appointments' ); $done = false; }",
"\t\t\t$guardian_remaining = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$intake_table} WHERE guardian_user_id=%d LIMIT 1\", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( null === $guardian_remaining && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Guardian continuity references could not be verified safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }\n\t\t\telseif ( $guardian_remaining ) { $messages[] = __( 'Guardian continuity references remain linked and will retry.', 'worldwide-clinic-appointments' ); $done = false; }"
)

# R1-B: Future24 table probe distinguishes DB failure from legitimate absence.
p='includes/class-wca-privacy.php'
once(p,
"\t\t$table = self::future24_table();\n\t\tif ( $table ) {",
"\t\t$table = self::future24_table();\n\t\tif ( is_wp_error( $table ) ) { return $table; }\n\t\tif ( $table ) {"
)
# second caller is erasure: fail incomplete, do not silently skip.
s=read(p)
needle="\t\t$table = self::future24_table();\n\t\tif ( $table ) {"
idx=s.find(needle)
if idx<0: raise SystemExit('wca-privacy: erasure Future24 caller not found after export replacement')
s=s[:idx]+s[idx:].replace(needle,"\t\t$table = self::future24_table();\n\t\tif ( is_wp_error( $table ) ) { $messages[] = __( 'Future24 privacy storage could not be verified safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; $table = ''; }\n\t\tif ( $table ) {",1)
write(p,s)
# retention caller.
s=read(p)
idx=s.rfind(needle)
if idx<0: raise SystemExit('wca-privacy: retention Future24 caller not found')
s=s[:idx]+s[idx:].replace(needle,"\t\t$table = self::future24_table();\n\t\tif ( is_wp_error( $table ) ) { return $table; }\n\t\tif ( $table ) {",1)
write(p,s)
once(p,
"\tprivate static function future24_table() {\n\t\tglobal $wpdb;\n\t\tif ( ! class_exists( 'WCA_Future24' ) ) { return ''; }\n\t\t$table = $wpdb->prefix . 'wca_future24_records';\n\t\t$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );\n\t\treturn $exists === $table ? $table : '';\n\t}",
"\tprivate static function future24_table() {\n\t\tglobal $wpdb;\n\t\tif ( ! class_exists( 'WCA_Future24' ) ) { return ''; }\n\t\t$table = $wpdb->prefix . 'wca_future24_records';\n\t\t$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );\n\t\tif ( null === $exists && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_privacy_future24_table_read_failed', __( 'Future24 privacy storage could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\treturn $exists === $table ? $table : '';\n\t}"
)

# R1-C: strict audit-history read for privacy export; legacy callers retain array contract.
p='includes/class-swc-helpers.php'
once(p,
"\tpublic static function audit_rows( $appointment_id ) {\n\t\tglobal $wpdb;\n\t\treturn $wpdb->get_results(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT * FROM {$wpdb->prefix}swc_audit_log WHERE appointment_id=%d ORDER BY id DESC\",\n\t\t\t\tabsint( $appointment_id )\n\t\t\t)\n\t\t);\n\t}",
"\tpublic static function audit_rows_strict( $appointment_id ) {\n\t\tglobal $wpdb;\n\t\t$rows = $wpdb->get_results(\n\t\t\t$wpdb->prepare(\n\t\t\t\t\"SELECT * FROM {$wpdb->prefix}swc_audit_log WHERE appointment_id=%d ORDER BY id DESC\",\n\t\t\t\tabsint( $appointment_id )\n\t\t\t)\n\t\t);\n\t\tif ( null === $rows && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'swc_audit_read_failed', __( 'Appointment audit history could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\treturn (array) $rows;\n\t}\n\n\tpublic static function audit_rows( $appointment_id ) {\n\t\t$rows = self::audit_rows_strict( $appointment_id );\n\t\treturn is_wp_error( $rows ) ? array() : $rows;\n\t}"
)
p='includes/class-swc-privacy.php'
once(p,
"\t\t\t$audit = array();\n\t\t\tforeach ( SWC_Helpers::audit_rows( $appointment_id ) as $row ) {",
"\t\t\t$audit = array();\n\t\t\t$audit_rows = SWC_Helpers::audit_rows_strict( $appointment_id );\n\t\t\tif ( is_wp_error( $audit_rows ) ) { return $audit_rows; }\n\t\t\tforeach ( $audit_rows as $row ) {"
)

# R1-D: authoritative review-eligibility reads fail closed on DB errors.
p='includes/class-wca-repository.php'
once(p,
"\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE appointment_id=%d AND reviewer_user_id=%d LIMIT 1\", absint( $appointment_id ), absint( $reviewer_user_id ) ), ARRAY_A );\n\t\tif ( $existing ) { return $existing; }",
"\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE appointment_id=%d AND reviewer_user_id=%d LIMIT 1\", absint( $appointment_id ), absint( $reviewer_user_id ) ), ARRAY_A );\n\t\tif ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_review_eligibility_read_failed', __( 'Current review eligibility could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( $existing ) { return $existing; }"
)
once(p,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE public_ref=%s AND reviewer_user_id=%d LIMIT 1\", sanitize_text_field( $public_ref ), absint( $reviewer_user_id ) ), ARRAY_A );\n\t\tif ( ! $row || 'eligible' !== $row['status']",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE public_ref=%s AND reviewer_user_id=%d LIMIT 1\", sanitize_text_field( $public_ref ), absint( $reviewer_user_id ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_review_eligibility_read_failed', __( 'Current review eligibility could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }\n\t\tif ( ! $row || 'eligible' !== $row['status']"
)

# Permanent T16 regression gate.
test=r'''<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t16h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
t16h('R1 continuity batch read fails closed','includes/class-wca-continuity-secure.php',"null === $rows_raw && '' !== (string) $wpdb->last_error");
t16h('R1 continuity zero-delete readback fails closed','includes/class-wca-continuity-secure.php',"null === $still_exists && '' !== (string) $wpdb->last_error");
t16h('R1 continuity completion read fails closed','includes/class-wca-continuity-secure.php',"null === $more && '' !== (string) $wpdb->last_error");
t16h('R1 continuity guardian readback fails closed','includes/class-wca-continuity-secure.php',"null === $guardian_remaining && '' !== (string) $wpdb->last_error");
t16h('R1 Future24 table probe fails closed','includes/class-wca-privacy.php',"wca_privacy_future24_table_read_failed");
t16h('R1 privacy export propagates Future24 table failure','includes/class-wca-privacy.php',"if ( is_wp_error( $table ) ) { return $table; }");
t16h('R1 strict audit history read exists','includes/class-swc-helpers.php',"public static function audit_rows_strict");
t16h('R1 privacy export uses strict audit read','includes/class-swc-privacy.php',"SWC_Helpers::audit_rows_strict");
t16h('R1 review eligibility duplicate read fails closed','includes/class-wca-repository.php',"wca_review_eligibility_read_failed");
if($fail){fwrite(STDERR,"T16 regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);} echo "T16 regression assertions passed: {$pass}/{$pass}\n";
'''
write('tests/sixteenth-twenty-review-regressions.php',test)
p='tests/run-all.php'
once(p,
"'fifteenth-twenty-review-regressions.php' );",
"'fifteenth-twenty-review-regressions.php', 'sixteenth-twenty-review-regressions.php' );"
)
