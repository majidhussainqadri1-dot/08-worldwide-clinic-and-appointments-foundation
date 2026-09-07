from pathlib import Path
ROOT = Path(__file__).resolve().parents[2]

def read(path):
    return (ROOT / path).read_text()

def write(path, text):
    (ROOT / path).write_text(text)

def replace_once(path, old, new):
    text = read(path)
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{path}: patch anchor count {count}')
    write(path, text.replace(old, new, 1))

# R9-D3: bind the idempotency reservation to normalized provider semantics,
# not merely provider/event/doctor identity.
replace_once(
    'includes/class-wca-calendar-link.php',
    "\t\t$claim = WCA_Repository::claim_idempotency( 'calendar_provider_webhook', $provider . ':' . $event_id, 0, array( 'provider' => $provider, 'event_id' => $event_id, 'doctor_user_id' => $doctor_id ) );\n",
    """\t\t$busy_fingerprint = array();
\t\tforeach ( (array) ( $verified['busy_windows'] ?? array() ) as $window ) {
\t\t\tif ( ! is_array( $window ) ) { return new WP_Error( 'wca_calendar_webhook_window', __( 'Calendar busy-window payload is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\t\t$busy_fingerprint[] = array(
\t\t\t\t'start_utc'    => sanitize_text_field( (string) ( $window['start_utc'] ?? '' ) ),
\t\t\t\t'end_utc'      => sanitize_text_field( (string) ( $window['end_utc'] ?? '' ) ),
\t\t\t\t'calendar_ref' => sanitize_text_field( (string) ( $window['calendar_ref'] ?? '' ) ),
\t\t\t);
\t\t}
\t\tusort( $busy_fingerprint, static function ( $a, $b ) { return strcmp( wp_json_encode( $a ), wp_json_encode( $b ) ); } );
\t\t$fingerprint = array(
\t\t\t'provider'           => $provider,
\t\t\t'event_id'           => $event_id,
\t\t\t'occurred_at'        => $occurred_at,
\t\t\t'doctor_user_id'     => $doctor_id,
\t\t\t'busy_windows'       => $busy_fingerprint,
\t\t\t'appointment_ref'    => strtolower( sanitize_text_field( (string) ( $verified['appointment_ref'] ?? '' ) ) ),
\t\t\t'provider_event_ref' => sanitize_text_field( (string) ( $verified['provider_event_ref'] ?? '' ) ),
\t\t\t'sync_status'        => sanitize_key( (string) ( $verified['sync_status'] ?? 'synced' ) ),
\t\t\t'conflict_status'    => sanitize_key( (string) ( $verified['conflict_status'] ?? 'none' ) ),
\t\t\t'etag'               => sanitize_text_field( (string) ( $verified['etag'] ?? '' ) ),
\t\t);
\t\t$claim = WCA_Repository::claim_idempotency( 'calendar_provider_webhook', $provider . ':' . $event_id, 0, $fingerprint );
"""
)

# R9-D2: surface mapping reconciliation action in the durable audit and response.
replace_once(
    'includes/class-wca-calendar-link.php',
    """\t\t\t$mapping = null;
\t\t\tif ( ! empty( $verified['appointment_ref'] ) || ! empty( $verified['provider_event_ref'] ) ) {
\t\t\t\t$mapping = WCA_Repository::upsert_calendar_mapping_from_provider( $provider, $verified, $doctor_id );
\t\t\t\tif ( is_wp_error( $mapping ) ) { return $mapping; }
\t\t\t}
\t\t\t$trace = WCA_Observability::trace_id();
""",
    """\t\t\t$mapping = null;
\t\t\t$mapping_action = 'none';
\t\t\tif ( ! empty( $verified['appointment_ref'] ) || ! empty( $verified['provider_event_ref'] ) ) {
\t\t\t\t$mapping = WCA_Repository::upsert_calendar_mapping_from_provider( $provider, $verified, $doctor_id );
\t\t\t\tif ( is_wp_error( $mapping ) ) { return $mapping; }
\t\t\t\tif ( is_array( $mapping ) ) {
\t\t\t\t\t$mapping_action = sanitize_key( (string) ( $mapping['_projection_action'] ?? 'applied' ) );
\t\t\t\t\tunset( $mapping['_projection_action'] );
\t\t\t\t}
\t\t\t}
\t\t\t$trace = WCA_Observability::trace_id();
"""
)
replace_once(
    'includes/class-wca-calendar-link.php',
    "'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'trace_id' => $trace",
    "'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'mapping_action' => $mapping_action, 'trace_id' => $trace"
)
replace_once(
    'includes/class-wca-calendar-link.php',
    "'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'canonical_appointment_mutated' => false",
    "'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'mapping_action' => $mapping_action, 'canonical_appointment_mutated' => false"
)

# R9-D1 + R9-D2: exact practitioner binding and source-order reconciliation at
# the persistence root, so internal callers cannot bypass either invariant.
replace_once(
    'includes/class-wca-repository.php',
    """\t\t$appointment_id = absint( $ids[0] );
\t\t$actor = WCA_Authorization::appointment_actor( $appointment_id, $doctor_id );
\t\tif ( ! in_array( $actor, array( 'doctor','clinic_staff','admin' ), true ) ) { return new WP_Error( 'wca_calendar_mapping_scope', __( 'Calendar provider event is outside the practitioner appointment scope.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE provider=%s AND provider_event_ref=%s LIMIT 1\", $provider, $provider_event_ref ), ARRAY_A );
""",
    """\t\t$appointment_id = absint( $ids[0] );
\t\tif ( ! SWC_Doctor_Authority::is_eligible( $doctor_id ) || ! SWC_Helpers::can_doctor_manage( $appointment_id, $doctor_id ) || $doctor_id !== absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) ) ) { return new WP_Error( 'wca_calendar_mapping_scope', __( 'Calendar provider event is outside the canonical practitioner appointment scope.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
\t\t$source_event_id = sanitize_text_field( (string) ( $event['event_id'] ?? '' ) );
\t\t$source_occurred_raw = trim( (string) ( $event['occurred_at'] ?? '' ) );
\t\t$source_occurred_at = '';
\t\tif ( preg_match( '/^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(?:\\.\\d+)?(?:Z|[+-]\\d{2}:\\d{2})$/', $source_occurred_raw ) ) {
\t\t\ttry { $source_occurred_at = ( new DateTimeImmutable( $source_occurred_raw ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ); } catch ( Exception $e ) { $source_occurred_at = ''; }
\t\t}
\t\tif ( ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $source_event_id ) || ! $source_occurred_at ) { return new WP_Error( 'wca_calendar_mapping_source_order', __( 'Calendar provider mapping lacks trustworthy source ordering evidence.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE provider=%s AND provider_event_ref=%s LIMIT 1\", $provider, $provider_event_ref ), ARRAY_A );
"""
)
replace_once(
    'includes/class-wca-repository.php',
    """\t\t$metadata = array( 'source' => 'verified_provider_webhook', 'provider_token_stored' => false, 'canonical_appointment_mutated' => false );
\t\t$row = array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'provider_event_ref' => $provider_event_ref, 'etag' => sanitize_text_field( (string) ( $event['etag'] ?? '' ) ), 'last_synced_at' => self::now(), 'sync_status' => $sync_status, 'conflict_status' => $conflict_status, 'metadata_json' => wp_json_encode( $metadata ), 'updated_at' => self::now() );
\t\tif ( $existing ) {
\t\t\tif ( absint( $existing['appointment_id'] ) !== $appointment_id ) { return new WP_Error( 'wca_calendar_mapping_conflict', __( 'Provider event is already mapped to another appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t$changed = $wpdb->update( $table, $row, array( 'id' => absint( $existing['id'] ) ) );
\t\t\tif ( false === $changed ) { return new WP_Error( 'wca_calendar_mapping_write_failed', __( 'Calendar mapping could not be updated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\treturn array_merge( $existing, $row );
\t\t}
\t\t$row['public_ref'] = self::uuid(); $row['created_at'] = self::now();
\t\tif ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_calendar_mapping_write_failed', __( 'Calendar mapping could not be created safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn array_merge( array( 'id' => absint( $wpdb->insert_id ) ), $row );
""",
    """\t\t$metadata = array( 'source' => 'verified_provider_webhook', 'provider_token_stored' => false, 'canonical_appointment_mutated' => false, 'source_event_id' => $source_event_id, 'source_occurred_at' => $source_occurred_at );
\t\t$row = array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'provider_event_ref' => $provider_event_ref, 'etag' => sanitize_text_field( (string) ( $event['etag'] ?? '' ) ), 'last_synced_at' => self::now(), 'sync_status' => $sync_status, 'conflict_status' => $conflict_status, 'metadata_json' => self::json( $metadata ), 'updated_at' => self::now() );
\t\tif ( $existing ) {
\t\t\tif ( absint( $existing['appointment_id'] ) !== $appointment_id ) { return new WP_Error( 'wca_calendar_mapping_conflict', __( 'Provider event is already mapped to another appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t$existing_meta = self::decode( (string) ( $existing['metadata_json'] ?? '{}' ) );
\t\t\t$existing_source_event = sanitize_text_field( (string) ( $existing_meta['source_event_id'] ?? '' ) );
\t\t\t$existing_source_time = sanitize_text_field( (string) ( $existing_meta['source_occurred_at'] ?? '' ) );
\t\t\t$same_semantics = (string) ( $existing['etag'] ?? '' ) === (string) $row['etag'] && (string) ( $existing['sync_status'] ?? '' ) === $sync_status && (string) ( $existing['conflict_status'] ?? '' ) === $conflict_status;
\t\t\tif ( $existing_source_event && hash_equals( $existing_source_event, $source_event_id ) ) {
\t\t\t\tif ( $existing_source_time === $source_occurred_at && $same_semantics ) { $existing['_projection_action'] = 'equivalent_replay'; return $existing; }
\t\t\t\treturn new WP_Error( 'wca_calendar_mapping_source_conflict', __( 'The same calendar provider event identity carries conflicting mapping state.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'reconciliation_required' => true ) );
\t\t\t}
\t\t\tif ( $existing_source_time && $source_occurred_at < $existing_source_time ) { $existing['_projection_action'] = 'stale_ignored'; return $existing; }
\t\t\tif ( $existing_source_time && $source_occurred_at === $existing_source_time ) { return new WP_Error( 'wca_calendar_mapping_source_conflict', __( 'Calendar provider events have ambiguous equal-time ordering.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'reconciliation_required' => true ) ); }
\t\t\t$changed = $wpdb->update( $table, $row, array( 'id' => absint( $existing['id'] ) ) );
\t\t\tif ( false === $changed ) { return new WP_Error( 'wca_calendar_mapping_write_failed', __( 'Calendar mapping could not be updated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t$updated = array_merge( $existing, $row ); $updated['_projection_action'] = 'applied'; return $updated;
\t\t}
\t\t$row['public_ref'] = self::uuid(); $row['created_at'] = self::now();
\t\tif ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_calendar_mapping_write_failed', __( 'Calendar mapping could not be created safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t$created = array_merge( array( 'id' => absint( $wpdb->insert_id ) ), $row ); $created['_projection_action'] = 'applied'; return $created;
"""
)

test_path = ROOT / 'tests/t20-r9-calendar-provider-regressions.php'
test_path.write_text(r'''<?php
$root = dirname( __DIR__ );
$calendar = file_get_contents( $root . '/includes/class-wca-calendar-link.php' );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
$fail = array(); $n = 0;
function t20r9( $name, $ok ) { global $fail, $n; $n++; if ( ! $ok ) { $fail[] = $name; } }
t20r9( 'sources readable', is_string( $calendar ) && is_string( $repo ) );
t20r9( 'provider mapping requires exact canonical practitioner', false !== strpos( $repo, 'SWC_Helpers::can_doctor_manage( $appointment_id, $doctor_id )' ) && false !== strpos( $repo, "$doctor_id !== absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) )" ) );
t20r9( 'provider mapping no longer authorizes staff/admin actor classes', false === strpos( $repo, "array( 'doctor','clinic_staff','admin' )" ) );
t20r9( 'provider mapping stores source ordering evidence', false !== strpos( $repo, "'source_event_id' => $source_event_id" ) && false !== strpos( $repo, "'source_occurred_at' => $source_occurred_at" ) );
t20r9( 'stale provider mappings are ignored', false !== strpos( $repo, "['_projection_action'] = 'stale_ignored'" ) );
t20r9( 'same event conflicting mapping requires reconciliation', false !== strpos( $repo, 'wca_calendar_mapping_source_conflict' ) && false !== strpos( $repo, "'reconciliation_required' => true" ) );
t20r9( 'webhook idempotency fingerprints semantic payload', false !== strpos( $calendar, '$busy_fingerprint' ) && false !== strpos( $calendar, "'appointment_ref'" ) && false !== strpos( $calendar, "'provider_event_ref'" ) && false !== strpos( $calendar, "'sync_status'" ) && false !== strpos( $calendar, "'conflict_status'" ) && false !== strpos( $calendar, "'etag'" ) && false !== strpos( $calendar, "claim_idempotency( 'calendar_provider_webhook', $provider . ':' . $event_id, 0, $fingerprint )" ) );
t20r9( 'mapping action is audited and returned', substr_count( $calendar, "'mapping_action' => $mapping_action" ) >= 2 );
if ( $fail ) { fwrite( STDERR, "T20 R9 calendar/provider regressions failed:\n- " . implode( "\n- ", $fail ) . "\n" ); exit( 1 ); }
echo "T20 R9 calendar/provider regressions: PASS {$n}/{$n}.\n";
''')

run = 'tests/run-all.php'
text = read(run)
old = "'t20-r8-payment-currency-persistence-regressions.php' );"
new = "'t20-r8-payment-currency-persistence-regressions.php', 't20-r9-calendar-provider-regressions.php' );"
if text.count(old) != 1:
    raise SystemExit('run-all R9 anchor mismatch')
write(run, text.replace(old, new, 1))
