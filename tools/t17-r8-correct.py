from pathlib import Path
import re


def one_sub(text, pattern, replacement, label, flags=0):
    out, n = re.subn(pattern, replacement, text, count=1, flags=flags)
    if n != 1:
        raise SystemExit(f"{label}: expected 1 replacement, got {n}")
    return out

# R8 frozen defect ledger:
# D1 legacy SWC privacy eraser bypasses appointment legal hold and cannot safely
#    terminate when held rows remain.
# D2 continuity legal hold is filter-only and does not inherit appointment hold.
# D3 continuity guardian unlink bulk update mutates held intake rows.
# D4 canonical/Future24 hold filters may weaken a native hold instead of being
#    monotonic restrictive.

p = Path('includes/class-wca-privacy.php')
s = p.read_text()
s = one_sub(
    s,
    r"\tpublic static function legal_hold\( \$appointment_id \) \{\n\t\treturn \(bool\) apply_filters\( 'wca_appointment_legal_hold', \(bool\) get_post_meta\( \$appointment_id, '_swc_legal_hold', true \), absint\( \$appointment_id \) \);\n\t\}\n\n\tpublic static function future24_legal_hold\( \$row \) \{\n\t\t\$row = is_array\( \$row \) \? \$row : array\(\);\n\t\t\$default = ! empty\( \$row\['appointment_id'\] \) && self::legal_hold\( absint\( \$row\['appointment_id'\] \) \);\n\t\treturn \(bool\) apply_filters\( 'wca_future24_legal_hold', \$default, \$row \);\n\t\}",
    """\tpublic static function legal_hold( $appointment_id ) {
\t\t$appointment_id = absint( $appointment_id );
\t\t$native = (bool) get_post_meta( $appointment_id, '_swc_legal_hold', true );
\t\t$filtered = (bool) apply_filters( 'wca_appointment_legal_hold', $native, $appointment_id );
\t\t// Extension/assurance integrations may add a hold, never remove owner-native evidence.
\t\treturn $native || $filtered;
\t}

\tpublic static function future24_legal_hold( $row ) {
\t\t$row = is_array( $row ) ? $row : array();
\t\t$native = ! empty( $row['appointment_id'] ) && self::legal_hold( absint( $row['appointment_id'] ) );
\t\t$filtered = (bool) apply_filters( 'wca_future24_legal_hold', $native, $row );
\t\treturn $native || $filtered;
\t}""",
    'monotonic canonical legal hold',
)
p.write_text(s)

p = Path('includes/class-wca-continuity-secure.php')
s = p.read_text()
s = one_sub(
    s,
    r"\tprivate static function legal_hold\( \$type, \$row \) \{ return \(bool\)apply_filters\('wca_continuity_legal_hold',false,sanitize_key\(\$type\),\(array\)\$row\); \}",
    """\tprivate static function legal_hold( $type, $row ) {
\t\t$row = is_array( $row ) ? $row : array();
\t\t$appointment_id = absint( $row['appointment_id'] ?? 0 );
\t\t$native = $appointment_id && class_exists( 'WCA_Privacy' ) ? WCA_Privacy::legal_hold( $appointment_id ) : false;
\t\t$filtered = (bool) apply_filters( 'wca_continuity_legal_hold', $native, sanitize_key( $type ), $row );
\t\treturn $native || $filtered;
\t}""",
    'continuity inherits appointment legal hold',
)
s = one_sub(
    s,
    r"\t\tif \( 1 === \$page \) \{\n\t\t\tdelete_transient\( \$base \. '_intake' \);\n\t\t\tdelete_transient\( \$base \. '_followups' \);\n\t\t\}",
    """\t\tif ( 1 === $page ) {
\t\t\tdelete_transient( $base . '_intake' );
\t\t\tdelete_transient( $base . '_followups' );
\t\t\tdelete_transient( $base . '_guardian' );
\t\t}""",
    'guardian erasure cursor reset',
)
s = one_sub(
    s,
    r"\t\t\$intake_table = self::tables\(\)\['intake'\];\n\t\t\$guardian_update = \$wpdb->update\( \$intake_table, array\( 'guardian_user_id' => 0 \), array\( 'guardian_user_id' => \$user_id \), array\( '%d' \), array\( '%d' \) \);\n\t\tif \( false === \$guardian_update \) \{ \$messages\[\] = __\( 'Guardian continuity references could not be anonymized safely and will retry\.', 'worldwide-clinic-appointments' \); \$done = false; \}\n\t\telseif \( 0 === \(int\) \$guardian_update \) \{\n\t\t\t\$guardian_remaining = \$wpdb->get_var\( \$wpdb->prepare\( \"SELECT id FROM \{\$intake_table\} WHERE guardian_user_id=%d LIMIT 1\", \$user_id \) \); // phpcs:ignore WordPress\.DB\.PreparedSQL\.NotPrepared\n\t\t\tif \( null === \$guardian_remaining && '' !== \(string\) \$wpdb->last_error \) \{ \$messages\[\] = __\( 'Guardian continuity references could not be verified safely and will retry\.', 'worldwide-clinic-appointments' \); \$done = false; \}\n\t\t\telseif \( \$guardian_remaining \) \{ \$messages\[\] = __\( 'Guardian continuity references remain linked and will retry\.', 'worldwide-clinic-appointments' \); \$done = false; \}\n\t\t\}",
    """\t\t$intake_table = self::tables()['intake'];
\t\t$guardian_cursor_key = $base . '_guardian';
\t\t$guardian_cursor = absint( get_transient( $guardian_cursor_key ) );
\t\t$guardian_rows_raw = $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id FROM {$intake_table} WHERE guardian_user_id=%d AND id>%d ORDER BY id ASC LIMIT 100", $user_id, $guardian_cursor ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $guardian_rows_raw && '' !== (string) $wpdb->last_error ) {
\t\t\t$messages[] = __( 'Guardian continuity references could not be read safely and will retry.', 'worldwide-clinic-appointments' );
\t\t\t$done = false;
\t\t} else {
\t\t\t$guardian_last = $guardian_cursor;
\t\t\tforeach ( (array) $guardian_rows_raw as $guardian_row ) {
\t\t\t\t$row_id = absint( $guardian_row['id'] );
\t\t\t\tif ( self::legal_hold( 'intake', $guardian_row ) ) {
\t\t\t\t\t$retained = true;
\t\t\t\t\t$guardian_last = max( $guardian_last, $row_id );
\t\t\t\t\tcontinue;
\t\t\t\t}
\t\t\t\t$guardian_update = $wpdb->update( $intake_table, array( 'guardian_user_id' => 0 ), array( 'id' => $row_id, 'guardian_user_id' => $user_id ), array( '%d' ), array( '%d', '%d' ) );
\t\t\t\tif ( false === $guardian_update ) {
\t\t\t\t\t$messages[] = __( 'Guardian continuity references could not be anonymized safely and will retry.', 'worldwide-clinic-appointments' );
\t\t\t\t\t$done = false;
\t\t\t\t\tbreak;
\t\t\t\t}
\t\t\t\tif ( 0 === (int) $guardian_update ) {
\t\t\t\t\t$guardian_remaining = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$intake_table} WHERE id=%d AND guardian_user_id=%d", $row_id, $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\t\tif ( null === $guardian_remaining && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Guardian continuity references could not be verified safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }
\t\t\t\t\tif ( $guardian_remaining ) { $messages[] = __( 'Guardian continuity references remain linked and will retry.', 'worldwide-clinic-appointments' ); $done = false; break; }
\t\t\t\t}
\t\t\t\t$guardian_last = max( $guardian_last, $row_id );
\t\t\t\t$removed = true;
\t\t\t}
\t\t\tif ( $guardian_last > $guardian_cursor ) { set_transient( $guardian_cursor_key, $guardian_last, HOUR_IN_SECONDS ); }
\t\t\t$guardian_more = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$intake_table} WHERE guardian_user_id=%d AND id>%d ORDER BY id ASC LIMIT 1", $user_id, $guardian_last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\tif ( null === $guardian_more && '' !== (string) $wpdb->last_error ) { $messages[] = __( 'Guardian continuity references could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $done = false; }
\t\t\telseif ( $guardian_more ) { $done = false; } else { delete_transient( $guardian_cursor_key ); }
\t\t}""",
    'guardian erasure legal-hold-aware cursor',
    flags=re.S,
)
p.write_text(s)

p = Path('includes/class-swc-privacy.php')
s = p.read_text()
s = one_sub(s, r"\tconst PAGE_SIZE = 50;", "\tconst PAGE_SIZE = 50;\n\tconst CURSOR_TTL = HOUR_IN_SECONDS;", 'legacy privacy cursor ttl')
start = s.index("\tpublic function erase( $email_address, $page = 1 )")
end = s.index("\n\tprivate function related_ids( $user_id, $page )", start)
new_erase = r'''\tpublic function erase( $email_address, $page = 1 ) {
\t\tglobal $wpdb;
\t\t$user = get_user_by( 'email', $email_address );
\t\tif ( ! $user ) {
\t\t\treturn array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
\t\t}
\t\t$page = max( 1, absint( $page ) );
\t\t$base = 'swc_privacy_erase_' . substr( hash( 'sha256', strtolower( sanitize_email( $email_address ) ) ), 0, 24 );
\t\t$cursor_key = $base . '_appointments';
\t\tif ( 1 === $page ) { delete_transient( $cursor_key ); }
\t\t$cursor = absint( get_transient( $cursor_key ) );
\t\t$ids = $this->related_ids_after( $user->ID, $cursor, self::PAGE_SIZE );
\t\tif ( is_wp_error( $ids ) ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array( __( 'Appointment privacy erasure could not read the affected record set safely and will retry.', 'worldwide-clinic-appointments' ) ), 'done' => false ); }
\t\t$removed = false;
\t\t$retained = false;
\t\t$messages = array();
\t\t$failed = false;
\t\t$last_id = $cursor;
\t\tforeach ( $ids as $appointment_id ) {
\t\t\tif ( class_exists( 'WCA_Privacy' ) && WCA_Privacy::legal_hold( $appointment_id ) ) {
\t\t\t\t$retained = true;
\t\t\t\t$last_id = max( $last_id, absint( $appointment_id ) );
\t\t\t\tcontinue;
\t\t\t}
\t\t\t$result = WCA_Repository::transaction( function () use ( $appointment_id, $user, $wpdb ) {
\t\t\t\t$is_patient = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) ) === $user->ID;
\t\t\t\t$is_doctor  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) === $user->ID;
\t\t\t\t$changed = false;
\t\t\t\t$retain = false;
\t\t\t\tif ( $is_patient ) {
\t\t\t\t\tforeach ( array( 'country', 'city', 'phone', 'whatsapp', 'reason', 'concern_duration', 'patient_message', 'consent_at', 'consent_version', 'patient_timezone', 'preferred_at_utc', 'proposed_at_utc', 'proposed_timezone', 'proposed_expires_at', 'reassignment_reason', 'reassignment_expires_at' ) as $key ) {
\t\t\t\t\t\t$deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'swc_privacy_meta_delete' );
\t\t\t\t\t\tif ( is_wp_error( $deleted ) ) { return $deleted; }
\t\t\t\t\t}
\t\t\t\t\t$patient_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_patient_user_id', 0, 'swc_privacy_patient_anonymize' );
\t\t\t\t\tif ( is_wp_error( $patient_write ) ) { return $patient_write; }
\t\t\t\t\t$post_update = wp_update_post( array( 'ID' => $appointment_id, 'post_author' => 0, 'post_title' => sprintf( 'Anonymized Appointment #%d', $appointment_id ) ), true );
\t\t\t\t\tif ( is_wp_error( $post_update ) || ! $post_update || 0 !== absint( get_post_field( 'post_author', $appointment_id ) ) ) { return new WP_Error( 'swc_privacy_post_anonymize', __( 'The appointment post could not be anonymized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t\t\tif ( SWC_Helpers::can_transition( 'patient', SWC_Helpers::status( $appointment_id ), 'cancelled' ) ) {
\t\t\t\t\t\t$status_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_status', 'cancelled', 'swc_privacy_status_anonymize' );
\t\t\t\t\t\tif ( is_wp_error( $status_write ) ) { return $status_write; }
\t\t\t\t\t}
\t\t\t\t\t$erased_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_erased', '1', 'swc_privacy_erased_marker' );
\t\t\t\t\tif ( is_wp_error( $erased_write ) ) { return $erased_write; }
\t\t\t\t\t$changed = true; $retain = true;
\t\t\t\t}
\t\t\t\tif ( $is_doctor ) {
\t\t\t\t\t$doctor_write = SWC_Helpers::update_meta_strict( $appointment_id, '_swc_doctor_id', 0, 'swc_privacy_doctor_anonymize' );
\t\t\t\t\tif ( is_wp_error( $doctor_write ) ) { return $doctor_write; }
\t\t\t\t\tforeach ( array( 'doctor_private_note', 'patient_message' ) as $key ) { $deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'swc_privacy_private_meta_delete' ); if ( is_wp_error( $deleted ) ) { return $deleted; } }
\t\t\t\t\t$changed = true; $retain = true;
\t\t\t\t}
\t\t\t\tif ( absint( SWC_Helpers::meta( $appointment_id, 'proposed_doctor_id' ) ) === $user->ID ) {
\t\t\t\t\tforeach ( array( 'proposed_doctor_id', 'reassignment_reason', 'reassignment_expires_at' ) as $key ) { $deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $key, 'swc_privacy_reassignment_delete' ); if ( is_wp_error( $deleted ) ) { return $deleted; } }
\t\t\t\t\t$changed = true;
\t\t\t\t}
\t\t\t\t$audit_update = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}swc_audit_log SET actor_id=0, note='', reason='', details_json='{}' WHERE appointment_id=%d AND actor_id=%d", $appointment_id, $user->ID ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\tif ( false === $audit_update ) { return new WP_Error( 'swc_privacy_audit_anonymize', __( 'Appointment audit identity could not be anonymized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t\treturn array( 'changed' => $changed, 'retained' => $retain );
\t\t\t}, 'swc_privacy_erase_transaction' );
\t\t\tif ( is_wp_error( $result ) ) {
\t\t\t\tclean_post_cache( $appointment_id );
\t\t\t\t$messages[] = __( 'Legacy appointment privacy erasure encountered a storage failure and will retry.', 'worldwide-clinic-appointments' );
\t\t\t\t$failed = true;
\t\t\t\tbreak;
\t\t\t}
\t\t\t$removed = $removed || ! empty( $result['changed'] );
\t\t\t$retained = $retained || ! empty( $result['retained'] );
\t\t\t$last_id = max( $last_id, absint( $appointment_id ) );
\t\t}
\t\tif ( $last_id > $cursor ) { set_transient( $cursor_key, $last_id, self::CURSOR_TTL ); }
\t\t$more = $this->related_ids_after( $user->ID, $last_id, 1 );
\t\tif ( is_wp_error( $more ) ) { $messages[] = __( 'Appointment privacy erasure could not verify completion safely and will retry.', 'worldwide-clinic-appointments' ); $failed = true; $more = array( 1 ); }
\t\t$done = ! $failed && ! $more;
\t\tif ( $done ) { delete_transient( $cursor_key ); }
\t\tif ( $retained ) { $messages[] = __( 'One or more appointment records were retained unchanged under an active legal hold.', 'worldwide-clinic-appointments' ); }
\t\treturn array(
\t\t\t'items_removed'  => $removed,
\t\t\t'items_retained' => $retained,
\t\t\t'messages'       => array_unique( $messages ),
\t\t\t'done'           => $done,
\t\t);
\t}
'''.replace('\\t', '\t').replace('\\n', '\n')
s = s[:start] + new_erase + s[end:]
insert_at = s.index("\n\tprivate function related_ids( $user_id, $page )")
helper = r'''
\tprivate function related_ids_after( $user_id, $cursor, $limit ) {
\t\tglobal $wpdb;
\t\t$limit = min( self::PAGE_SIZE, max( 1, absint( $limit ) ) );
\t\t$sql = "SELECT DISTINCT p.ID
\t\t\tFROM {$wpdb->posts} p
\t\t\tLEFT JOIN {$wpdb->postmeta} patient ON patient.post_id=p.ID AND patient.meta_key='_swc_patient_user_id'
\t\t\tLEFT JOIN {$wpdb->postmeta} doctor ON doctor.post_id=p.ID AND doctor.meta_key='_swc_doctor_id'
\t\t\tLEFT JOIN {$wpdb->postmeta} proposed ON proposed.post_id=p.ID AND proposed.meta_key='_swc_proposed_doctor_id'
\t\t\tWHERE p.post_type=%s AND p.post_status IN ('publish','private') AND p.ID>%d
\t\t\tAND (p.post_author=%d OR patient.meta_value=%s OR doctor.meta_value=%s OR proposed.meta_value=%s)
\t\t\tORDER BY p.ID ASC LIMIT %d";
\t\t$raw = $wpdb->get_col( $wpdb->prepare( $sql, SWC_Helpers::TYPE, absint( $cursor ), absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), (string) absint( $user_id ), $limit ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $raw && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'swc_privacy_related_after_read_failed', __( 'Appointment privacy records could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\treturn array_map( 'absint', (array) $raw );
\t}
'''.replace('\\t', '\t').replace('\\n', '\n')
s = s[:insert_at] + helper + s[insert_at:]
p.write_text(s)

p = Path('tests/run-all.php')
s = p.read_text()
needle = "'seventeenth-r7-delegation-consent-regressions.php'"
if needle not in s:
    raise SystemExit('run-all R7 anchor missing')
s = s.replace(needle, needle + ", 'seventeenth-r8-privacy-legal-hold-regressions.php'", 1)
p.write_text(s)

Path('tests/seventeenth-r8-privacy-legal-hold-regressions.php').write_text(r'''<?php
$root = dirname( __DIR__ );
$wca = file_get_contents( $root . '/includes/class-wca-privacy.php' );
$swc = file_get_contents( $root . '/includes/class-swc-privacy.php' );
$continuity = file_get_contents( $root . '/includes/class-wca-continuity-secure.php' );
$checks = array(
    'canonical hold preserves native true after filters' => false !== strpos( $wca, 'return $native || $filtered;' ),
    'Future24 hold is monotonic restrictive' => substr_count( $wca, 'return $native || $filtered;' ) >= 2,
    'legacy eraser checks canonical appointment legal hold' => false !== strpos( $swc, "WCA_Privacy::legal_hold( $appointment_id )" ),
    'legacy eraser uses monotonic cursor rather than destructive offset paging' => false !== strpos( $swc, 'related_ids_after( $user->ID, $cursor' ),
    'legacy held rows advance the cursor without mutation' => false !== strpos( $swc, '$last_id = max( $last_id, absint( $appointment_id ) );' ),
    'legacy eraser reports unchanged legal-hold retention' => false !== strpos( $swc, 'retained unchanged under an active legal hold' ),
    'continuity hold inherits appointment hold' => false !== strpos( $continuity, 'WCA_Privacy::legal_hold( $appointment_id )' ),
    'continuity hold cannot be weakened by extension filter' => false !== strpos( $continuity, 'return $native || $filtered;' ),
    'continuity guardian erasure has its own cursor' => false !== strpos( $continuity, "$base . '_guardian'" ),
    'continuity guardian rows are legal-hold checked before unlink' => false !== strpos( $continuity, "self::legal_hold( 'intake', $guardian_row )" ),
    'continuity guardian unlink is row-scoped' => false !== strpos( $continuity, "array( 'id' => $row_id, 'guardian_user_id' => $user_id )" ),
    'legacy blanket guardian unlink removed' => false === strpos( $continuity, "array( 'guardian_user_id' => $user_id ), array( '%d' ), array( '%d' )" ),
);
$pass = 0;
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "[FAIL] {$label}\n" ); exit( 1 ); }
    echo "[PASS] {$label}\n";
    $pass++;
}
echo "R8 privacy/legal-hold assertions: {$pass}/" . count( $checks ) . " PASS\n";
''')
