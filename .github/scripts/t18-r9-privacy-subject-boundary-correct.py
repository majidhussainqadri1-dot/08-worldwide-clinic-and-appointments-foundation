from pathlib import Path

root = Path(__file__).resolve().parents[2]
wca = root / 'includes/class-wca-privacy.php'
s = wca.read_text()

old = """\t\tforeach ( $ids as $id ) {\n\t\t\tif ( self::legal_hold( $id ) ) {\n\t\t\t\t$retained = true;\n\t\t\t\t$last_id = max( $last_id, $id );\n\t\t\t\tcontinue;\n\t\t\t}\n\t\t\t$erase_error = null;\n\t\t\tforeach ( array( 'reason','patient_message','phone','whatsapp','country','city','doctor_private_note','transition_reason_code' ) as $key ) {\n\t\t\t\t$deleted = SWC_Helpers::delete_meta_strict( $id, '_swc_' . $key, 'wca_privacy_meta_delete' );\n\t\t\t\tif ( is_wp_error( $deleted ) ) { $erase_error = $deleted; break; }\n\t\t\t}\n\t\t\tif ( ! $erase_error ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_privacy_erased_at', WCA_Repository::now(), 'wca_privacy_erased_marker' ); }\n\t\t\tif ( ! $erase_error && absint( SWC_Helpers::meta( $id, 'patient_user_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_patient_user_id', 0, 'wca_privacy_patient_anonymize' ); }\n\t\t\tif ( ! $erase_error && absint( SWC_Helpers::meta( $id, 'guardian_user_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_guardian_user_id', 0, 'wca_privacy_guardian_anonymize' ); }\n\t\t\tif ( ! $erase_error && absint( SWC_Helpers::meta( $id, 'doctor_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_doctor_id', 0, 'wca_privacy_doctor_anonymize' ); }\n"""
new = """\t\tforeach ( $ids as $id ) {\n\t\t\tif ( self::legal_hold( $id ) ) {\n\t\t\t\t$retained = true;\n\t\t\t\t$last_id = max( $last_id, $id );\n\t\t\t\tcontinue;\n\t\t\t}\n\t\t\t$is_patient = absint( SWC_Helpers::meta( $id, 'patient_user_id', 0 ) ) === $user_id || absint( get_post_field( 'post_author', $id ) ) === $user_id;\n\t\t\t$is_guardian = absint( SWC_Helpers::meta( $id, 'guardian_user_id', 0 ) ) === $user_id;\n\t\t\t$is_doctor = absint( SWC_Helpers::meta( $id, 'doctor_id', 0 ) ) === $user_id;\n\t\t\t$erase_error = null;\n\t\t\tif ( $is_patient ) {\n\t\t\t\tforeach ( array( 'reason','patient_message','phone','whatsapp','country','city' ) as $key ) {\n\t\t\t\t\t$deleted = SWC_Helpers::delete_meta_strict( $id, '_swc_' . $key, 'wca_privacy_patient_meta_delete' );\n\t\t\t\t\tif ( is_wp_error( $deleted ) ) { $erase_error = $deleted; break; }\n\t\t\t\t}\n\t\t\t}\n\t\t\tif ( ! $erase_error && $is_doctor ) { $erase_error = SWC_Helpers::delete_meta_strict( $id, '_swc_doctor_private_note', 'wca_privacy_doctor_meta_delete' ); }\n\t\t\tif ( ! $erase_error ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_privacy_erased_at', WCA_Repository::now(), 'wca_privacy_erased_marker' ); }\n\t\t\tif ( ! $erase_error && $is_patient && absint( SWC_Helpers::meta( $id, 'patient_user_id', 0 ) ) === $user_id ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_patient_user_id', 0, 'wca_privacy_patient_anonymize' ); }\n\t\t\tif ( ! $erase_error && $is_guardian ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_guardian_user_id', 0, 'wca_privacy_guardian_anonymize' ); }\n\t\t\tif ( ! $erase_error && $is_doctor ) { $erase_error = SWC_Helpers::update_meta_strict( $id, '_swc_doctor_id', 0, 'wca_privacy_doctor_anonymize' ); }\n"""
if old not in s:
    raise SystemExit('R9 appointment erasure needle missing')
s = s.replace(old, new, 1)

old = "$payload = is_array( $payload ) ? self::scrub_future24_payload( $payload, $subject_uuid ) : array();"
new = "$payload = is_array( $payload ) ? self::scrub_future24_payload( $payload, $user_id, $subject_uuid ) : array();"
if old not in s:
    raise SystemExit('R9 future payload call needle missing')
s = s.replace(old, new, 1)

old = """\tprivate static function scrub_future24_payload( $value, $subject_uuid ) {\n\t\tif ( ! is_array( $value ) ) { return $value; }\n\t\t$out = array();\n\t\tforeach ( $value as $key => $item ) {\n\t\t\t$key_string = is_string( $key ) ? sanitize_key( $key ) : $key;\n\t\t\tif ( is_string( $key_string ) && in_array( $key_string, array( 'subject_uuid','patient_user_id','guardian_user_id','recipient_user_id' ), true ) ) { continue; }\n\t\t\tif ( is_array( $item ) ) { $out[ $key ] = self::scrub_future24_payload( $item, $subject_uuid ); continue; }\n\t\t\tif ( $subject_uuid && is_string( $item ) && hash_equals( $subject_uuid, strtolower( sanitize_text_field( $item ) ) ) ) { continue; }\n\t\t\t$out[ $key ] = $item;\n\t\t}\n\t\treturn $out;\n\t}\n"""
new = """\tprivate static function scrub_future24_payload( $value, $user_id, $subject_uuid ) {\n\t\tif ( ! is_array( $value ) ) { return $value; }\n\t\t$user_id = absint( $user_id );\n\t\t$out = array();\n\t\tforeach ( $value as $key => $item ) {\n\t\t\t$key_string = is_string( $key ) ? sanitize_key( $key ) : $key;\n\t\t\tif ( is_array( $item ) ) { $out[ $key ] = self::scrub_future24_payload( $item, $user_id, $subject_uuid ); continue; }\n\t\t\tif ( is_string( $key_string ) && in_array( $key_string, array( 'patient_user_id','guardian_user_id','recipient_user_id','actor_user_id','subject_user_id' ), true ) && $user_id && absint( $item ) === $user_id ) { continue; }\n\t\t\tif ( 'subject_uuid' === $key_string && $subject_uuid && is_string( $item ) && hash_equals( $subject_uuid, strtolower( sanitize_text_field( $item ) ) ) ) { continue; }\n\t\t\tif ( $subject_uuid && is_string( $item ) && hash_equals( $subject_uuid, strtolower( sanitize_text_field( $item ) ) ) ) { continue; }\n\t\t\t$out[ $key ] = $item;\n\t\t}\n\t\treturn $out;\n\t}\n"""
if old not in s:
    raise SystemExit('R9 scrub helper needle missing')
s = s.replace(old, new, 1)

# Clear stale DB errors at privacy read boundaries before examining last_error.
needles = [
    "$future_rows_raw = $wpdb->get_results(",
    "$rows_raw = $wpdb->get_results(\n\t\t\t\t$wpdb->prepare(\n\t\t\t\t\t\"SELECT * FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d)",
    "$current = $wpdb->get_row( $wpdb->prepare( \"SELECT actor_user_id,subject_user_id FROM {$table} WHERE id=%d\"",
    "$more = $wpdb->get_var( $wpdb->prepare( \"SELECT id FROM {$table} WHERE (actor_user_id=%d OR subject_user_id=%d)",
    "$raw = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared",
    "$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );",
    "$rows_raw = $wpdb->get_results(\n\t\t\t\t\t$wpdb->prepare( \"SELECT * FROM {$table} WHERE expires_at IS NOT NULL",
]
for needle in needles:
    if needle not in s:
        raise SystemExit('R9 DB boundary needle missing: ' + needle[:70])
    indent = '\t\t\t' if needle.startswith('$future_rows_raw') else None
    # derive indentation from the actual line containing needle
    pos = s.index(needle)
    line_start = s.rfind('\n', 0, pos) + 1
    prefix = s[line_start:pos]
    s = s[:line_start] + prefix + "$wpdb->last_error = '';\n" + s[line_start:]

wca.write_text(s)

swc = root / 'includes/class-swc-privacy.php'
s2 = swc.read_text()
for needle in [
    "$raw = $wpdb->get_col( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $cursor )",
    "$raw = $wpdb->get_col( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id )",
    "$raw = $wpdb->get_var( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $user_id )",
]:
    if needle not in s2:
        raise SystemExit('R9 legacy privacy DB needle missing')
    pos = s2.index(needle)
    line_start = s2.rfind('\n', 0, pos) + 1
    prefix = s2[line_start:pos]
    s2 = s2[:line_start] + prefix + "$wpdb->last_error = '';\n" + s2[line_start:]
swc.write_text(s2)

test = root / 'tests/t18-r9-privacy-subject-boundary-regressions.php'
test.write_text(r'''<?php
$root=dirname(__DIR__);
$w=file_get_contents($root.'/includes/class-wca-privacy.php');
$l=file_get_contents($root.'/includes/class-swc-privacy.php');
$checks=array(
 'appointment erasure is relationship-aware'=>strpos($w,'$is_patient =')!==false && strpos($w,'$is_guardian =')!==false && strpos($w,'$is_doctor =')!==false,
 'patient fields deleted only in patient branch'=>strpos($w,"if ( $is_patient ) {\n\t\t\t\tforeach ( array( 'reason','patient_message','phone','whatsapp','country','city' )")!==false,
 'doctor private note deleted only for doctor'=>strpos($w,"if ( ! $erase_error && $is_doctor ) { $erase_error = SWC_Helpers::delete_meta_strict( $id, '_swc_doctor_private_note'")!==false,
 'future payload scrub receives requester id'=>strpos($w,'scrub_future24_payload( $payload, $user_id, $subject_uuid )')!==false,
 'future numeric identifiers removed only on requester-value match'=>strpos($w,"absint( $item ) === $user_id")!==false,
 'legacy unconditional future identifier strip removed'=>strpos($w,"in_array( $key_string, array( 'subject_uuid','patient_user_id','guardian_user_id','recipient_user_id' ), true ) { continue;")===false,
 'WCA privacy clears DB stale errors'=>substr_count($w,"$wpdb->last_error = '';")>=10,
 'SWC privacy clears DB stale errors'=>substr_count($l,"$wpdb->last_error = '';")>=3,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R9 FAIL: {$n}\n");exit(1);}}
echo "T18 R9 privacy subject-boundary regressions: PASS\n";
''')

run = root / 'tests/run-all.php'
r = run.read_text()
needle = "'t18-r8-external-calendar-degraded-regressions.php' );"
if needle not in r:
    raise SystemExit('R9 run-all insertion needle missing')
if "'t18-r9-privacy-subject-boundary-regressions.php'" not in r:
    r = r.replace(needle, "'t18-r8-external-calendar-degraded-regressions.php', 't18-r9-privacy-subject-boundary-regressions.php' );", 1)
run.write_text(r)
