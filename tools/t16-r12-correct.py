from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

def replace_once(path, old, new):
    p = ROOT / path
    text = p.read_text()
    if old not in text:
        raise SystemExit(f'missing expected block in {path}: {old[:120]!r}')
    if text.count(old) != 1:
        raise SystemExit(f'expected one match in {path}, got {text.count(old)}')
    p.write_text(text.replace(old, new, 1))

# R12-1/2: malformed client preconditions and corrupt persisted state must never normalize to requested.
replace_once(
    'includes/class-swc-helpers.php',
    "\tpublic static function assert_expected( $id, $expected_status, $expected_version ) {\n\t\tif ( self::status( $id ) !== $expected_status || self::record_version( $id ) !== absint( $expected_version ) ) {\n\t\t\treturn new WP_Error( 'swc_stale', __( 'This appointment changed after the form was opened. Refresh before saving.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\treturn true;\n\t}\n",
    "\t/** Return the persisted appointment state only when it is a recognized canonical/legacy state. */\n\tpublic static function status_strict( $id ) {\n\t\t$raw = strtolower( trim( (string) get_post_meta( absint( $id ), '_swc_status', true ) ) );\n\t\tif ( ! WCA_Contracts::is_appointment_status( $raw, true ) ) {\n\t\t\treturn new WP_Error( 'swc_appointment_state_corrupt', __( 'The persisted appointment state is invalid and cannot be mutated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );\n\t\t}\n\t\treturn WCA_Contracts::normalize_appointment_status( $raw );\n\t}\n\n\tpublic static function assert_expected( $id, $expected_status, $expected_version ) {\n\t\t$raw_expected = strtolower( trim( (string) $expected_status ) );\n\t\tif ( ! WCA_Contracts::is_appointment_status( $raw_expected, true ) ) {\n\t\t\treturn new WP_Error( 'swc_invalid_expected_status', __( 'A recognized current appointment status is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$current = self::status_strict( $id );\n\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\tif ( $current !== WCA_Contracts::normalize_appointment_status( $raw_expected ) || self::record_version( $id ) !== absint( $expected_version ) ) {\n\t\t\treturn new WP_Error( 'swc_stale', __( 'This appointment changed after the form was opened. Refresh before saving.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\treturn true;\n\t}\n"
)

replace_once(
    'includes/class-wca-service.php',
    "\t\tif ( empty( $data['expected_status'] ) || ! isset( $data['expected_version'] ) || absint( $data['expected_version'] ) < 1 ) {\n\t\t\treturn new WP_Error( 'wca_transition_precondition_required', __( 'Current appointment status and positive record version are required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$expected_status  = WCA_Contracts::normalize_appointment_status( $data['expected_status'] );\n\t\t$expected_version = absint( $data['expected_version'] );\n",
    "\t\tif ( empty( $data['expected_status'] ) || ! isset( $data['expected_version'] ) || absint( $data['expected_version'] ) < 1 ) {\n\t\t\treturn new WP_Error( 'wca_transition_precondition_required', __( 'Current appointment status and positive record version are required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\t$raw_expected_status = strtolower( trim( (string) $data['expected_status'] ) );\n\t\tif ( ! WCA_Contracts::is_appointment_status( $raw_expected_status, true ) ) {\n\t\t\treturn new WP_Error( 'wca_transition_expected_status_invalid', __( 'A recognized current appointment status is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$expected_status  = WCA_Contracts::normalize_appointment_status( $raw_expected_status );\n\t\t$expected_version = absint( $data['expected_version'] );\n"
)

# R12-3: slot-hold DB failures must retain storage-error semantics rather than masquerading as stale/invalid business state.
replace_once(
    'includes/class-wca-repository.php',
    "\t/** @return array<string,mixed>|null */\n\tpublic static function get_slot_hold( $token ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['slot_holds'];\n\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE hold_token=%s LIMIT 1\", sanitize_text_field( $token ) ), ARRAY_A );\n\t\treturn $row ?: null;\n\t}\n\n\t/** @return true|WP_Error */\n\tpublic static function book_slot( $token, $appointment_id ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['slot_holds'];\n\t\t$updated = $wpdb->query( $wpdb->prepare(\n\t\t\t\"UPDATE {$table} SET status='booked',appointment_id=%d,updated_at=%s,expires_at=%s WHERE hold_token=%s AND status='held' AND appointment_id=0 AND expires_at>%s\",\n\t\t\tabsint( $appointment_id ), self::now(), gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS ), sanitize_text_field( $token ), self::now()\n\t\t) );\n\t\treturn 1 === (int) $updated ? true : new WP_Error( 'wca_hold_stale', __( 'The slot hold changed or expired before booking.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t}\n",
    "\t/** @return array<string,mixed>|null|WP_Error */\n\tpublic static function get_slot_hold( $token ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['slot_holds'];\n\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE hold_token=%s LIMIT 1\", sanitize_text_field( $token ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) {\n\t\t\treturn new WP_Error( 'wca_slot_hold_read_failed', __( 'Current slot-hold state could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );\n\t\t}\n\t\treturn $row ?: null;\n\t}\n\n\t/** @return true|WP_Error */\n\tpublic static function book_slot( $token, $appointment_id ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['slot_holds'];\n\t\t$updated = $wpdb->query( $wpdb->prepare(\n\t\t\t\"UPDATE {$table} SET status='booked',appointment_id=%d,updated_at=%s,expires_at=%s WHERE hold_token=%s AND status='held' AND appointment_id=0 AND expires_at>%s\",\n\t\t\tabsint( $appointment_id ), self::now(), gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS ), sanitize_text_field( $token ), self::now()\n\t\t) );\n\t\tif ( false === $updated ) {\n\t\t\treturn new WP_Error( 'wca_hold_book_failed', __( 'The slot hold could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );\n\t\t}\n\t\treturn 1 === (int) $updated ? true : new WP_Error( 'wca_hold_stale', __( 'The slot hold changed or expired before booking.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t}\n"
)

service = ROOT / 'includes/class-wca-service.php'
text = service.read_text()
old = "\t\t$hold = WCA_Repository::get_slot_hold( (string) ( $data['hold_token'] ?? '' ) );\n\t\t$hold_check = WCA_Plan_Guard::validate_bookable_hold( $hold, $patient_user_id );"
new = "\t\t$hold = WCA_Repository::get_slot_hold( (string) ( $data['hold_token'] ?? '' ) );\n\t\tif ( is_wp_error( $hold ) ) { return $hold; }\n\t\t$hold_check = WCA_Plan_Guard::validate_bookable_hold( $hold, $patient_user_id );"
if text.count(old) != 1: raise SystemExit('request hold callsite mismatch')
text = text.replace(old, new, 1)
old = "\t\t\t\t$hold = WCA_Repository::get_slot_hold( $hold_token );\n\t\t\t\t$hold_check = WCA_Plan_Guard::validate_reschedule_hold( $hold, $appointment_id, $actor_user_id );"
new = "\t\t\t\t$hold = WCA_Repository::get_slot_hold( $hold_token );\n\t\t\t\tif ( is_wp_error( $hold ) ) { return $hold; }\n\t\t\t\t$hold_check = WCA_Plan_Guard::validate_reschedule_hold( $hold, $appointment_id, $actor_user_id );"
if text.count(old) != 1: raise SystemExit('reschedule proposal hold callsite mismatch')
text = text.replace(old, new, 1)
old = "\t\t\t\t$hold = WCA_Repository::get_slot_hold( $token );\n\t\t\t\tif ( ! $hold || 'held' !== $hold['status']"
new = "\t\t\t\t$hold = WCA_Repository::get_slot_hold( $token );\n\t\t\t\tif ( is_wp_error( $hold ) ) { return $hold; }\n\t\t\t\tif ( ! $hold || 'held' !== $hold['status']"
if text.count(old) != 1: raise SystemExit('reschedule acceptance hold callsite mismatch')
text = text.replace(old, new, 1)
service.write_text(text)

# Permanent R12 regression gate.
test = ROOT / 'tests/sixteenth-twenty-review-regressions.php'
t = test.read_text()
marker = 'if($fail){fwrite(STDERR,'
if marker not in t: raise SystemExit('T16 marker missing')
checks = """t16h('R12 malformed expected status is rejected before normalization','includes/class-wca-service.php','wca_transition_expected_status_invalid');
t16h('R12 persisted appointment state has strict validator','includes/class-swc-helpers.php','public static function status_strict');
t16h('R12 corrupt persisted appointment state fails closed','includes/class-swc-helpers.php','swc_appointment_state_corrupt');
t16h('R12 strict expected-state check rejects malformed status','includes/class-swc-helpers.php','swc_invalid_expected_status');
t16h('R12 slot-hold read DB failure is explicit','includes/class-wca-repository.php','wca_slot_hold_read_failed');
t16h('R12 slot booking DB write failure is explicit','includes/class-wca-repository.php','wca_hold_book_failed');
t16h('R12 appointment request propagates slot-hold read errors','includes/class-wca-service.php','if ( is_wp_error( $hold ) ) { return $hold; }');
"""
t = t.replace(marker, checks + marker, 1)
test.write_text(t)

print('R12 closed ledger applied')
