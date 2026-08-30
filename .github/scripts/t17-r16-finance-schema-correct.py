from pathlib import Path
import re

# T17 R16 frozen defect ledger (review completed before this script was introduced):
# 1) schema 3.3.0 claimed the R15 slot-capacity migration but slot_holds itself
#    lacked capacity/buffer columns that exact-head runtime reads and writes;
# 2) verified CF03 status facts were idempotent by event ID but had no source
#    ordering/version reconciliation, so a late older trusted event could regress
#    File 08's local financial projection.

contracts = Path('includes/class-wca-contracts.php')
s = contracts.read_text()
old = "const SCHEMA_VERSION                  = '3.3.0';"
new = "const SCHEMA_VERSION                  = '3.4.0';"
if new not in s:
    if s.count(old) != 1:
        raise SystemExit('R16 schema-version anchor is not unique')
    s = s.replace(old, new, 1)
contracts.write_text(s)

schema = Path('includes/class-wca-schema.php')
s = schema.read_text()

# Scope the slot_holds migration to the slot_holds CREATE TABLE block; do not use
# global string-presence checks because availability_rules already has these names.
slot_match = re.search(r'(CREATE TABLE \{\$tables\[\'slot_holds\'\]\} \()(.*?)(\) \{\$collate\};)', s, re.S)
if not slot_match:
    raise SystemExit('R16 slot_holds schema block not found')
slot = slot_match.group(2)
if 'capacity smallint(5) unsigned NOT NULL DEFAULT 1' not in slot:
    anchor = "\t\t\t\tpatient_user_id bigint(20) unsigned NOT NULL,\n"
    addition = anchor + "\t\t\t\tcapacity smallint(5) unsigned NOT NULL DEFAULT 1,\n\t\t\t\tbuffer_before smallint(5) unsigned NOT NULL DEFAULT 0,\n\t\t\t\tbuffer_after smallint(5) unsigned NOT NULL DEFAULT 0,\n"
    if slot.count(anchor) != 1:
        raise SystemExit('R16 slot_holds patient anchor is not unique')
    slot = slot.replace(anchor, addition, 1)
s = s[:slot_match.start(2)] + slot + s[slot_match.end(2):]

payment_match = re.search(r'(CREATE TABLE \{\$tables\[\'payment_intents\'\]\} \()(.*?)(\) \{\$collate\};)', s, re.S)
if not payment_match:
    raise SystemExit('R16 payment_intents schema block not found')
payment = payment_match.group(2)
if 'source_version bigint(20) unsigned NOT NULL DEFAULT 0' not in payment:
    anchor = "\t\t\t\tversion bigint(20) unsigned NOT NULL DEFAULT 1,\n"
    addition = anchor + "\t\t\t\tsource_version bigint(20) unsigned NOT NULL DEFAULT 0,\n\t\t\t\tsource_event_id varchar(191) NOT NULL DEFAULT '',\n\t\t\t\tsource_occurred_at datetime NULL,\n"
    if payment.count(anchor) != 1:
        raise SystemExit('R16 payment version anchor is not unique')
    payment = payment.replace(anchor, addition, 1)
    index_anchor = "\t\t\t\tKEY appointment_id (appointment_id),\n"
    index_add = index_anchor + "\t\t\t\tKEY source_order (source_version,source_occurred_at),\n"
    if payment.count(index_anchor) != 1:
        raise SystemExit('R16 payment index anchor is not unique')
    payment = payment.replace(index_anchor, index_add, 1)
s = s[:payment_match.start(2)] + payment + s[payment_match.end(2):]
schema.write_text(s)

repo = Path('includes/class-wca-repository.php')
s = repo.read_text()
start = s.find("\t/** Trusted CF03 fact -> File08 local projection. Financial ledger truth remains CF03-owned. */\n\tpublic static function project_payment_status(")
if start < 0:
    raise SystemExit('R16 payment projection method start not found')
end_marker = "\n\t/** Provider calendar projection only; appointment truth remains File08 canonical state. */"
end = s.find(end_marker, start)
if end < 0:
    raise SystemExit('R16 payment projection method end not found')
replacement = r'''	/** Trusted CF03 fact -> File08 local projection. Financial ledger truth remains CF03-owned. */
	public static function project_payment_status( $payment_ref, $status, $provider_ref = '', $source_version = 0, $source_event_id = '', $source_occurred_at = '' ) {
		global $wpdb;
		$table = WCA_Schema::tables()['payment_intents'];
		$status = sanitize_key( $status );
		$source_version = WCA_Service::strict_id( $source_version );
		$source_event_id = sanitize_text_field( $source_event_id );
		$source_occurred_at = WCA_Plan_Guard::strict_utc( $source_occurred_at );
		if ( ! in_array( $status, WCA_Contracts::payment_statuses(), true ) || 'pending' === $status ) { return new WP_Error( 'wca_payment_status_untrusted', __( 'The financial owner supplied an unsupported payment status.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		if ( null === $source_version || ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $source_event_id ) || ! $source_occurred_at ) { return new WP_Error( 'wca_payment_source_order_invalid', __( 'The financial owner supplied incomplete payment ordering evidence.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1 FOR UPDATE", sanitize_text_field( $payment_ref ) ), ARRAY_A );
		if ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_payment_status_read_failed', __( 'Payment projection could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( ! $row ) { return new WP_Error( 'wca_payment_status_not_found', __( 'Payment intent was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$provider_ref = sanitize_text_field( $provider_ref );
		if ( $provider_ref && ! empty( $row['provider_ref'] ) && ! hash_equals( (string) $row['provider_ref'], $provider_ref ) ) { return new WP_Error( 'wca_payment_provider_ref_conflict', __( 'Payment provider identity does not match the existing projection.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$current_source_version = absint( $row['source_version'] ?? 0 );
		if ( $source_version < $current_source_version ) {
			$row['_projection_action'] = 'stale_ignored';
			return $row;
		}
		if ( $source_version === $current_source_version && $current_source_version > 0 ) {
			$same_status = (string) $row['status'] === $status;
			$same_provider = ! $provider_ref || ! (string) $row['provider_ref'] || hash_equals( (string) $row['provider_ref'], $provider_ref );
			if ( $same_status && $same_provider ) {
				$row['_projection_action'] = 'same_version_replay';
				return $row;
			}
			return new WP_Error( 'wca_payment_source_version_conflict', __( 'The same financial source version carries conflicting payment state. Reconciliation is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'reconciliation_required' => true ) );
		}
		$update = array(
			'status'             => $status,
			'version'            => absint( $row['version'] ) + 1,
			'source_version'     => $source_version,
			'source_event_id'    => $source_event_id,
			'source_occurred_at' => $source_occurred_at,
			'updated_at'         => self::now(),
		);
		if ( $provider_ref && empty( $row['provider_ref'] ) ) { $update['provider_ref'] = $provider_ref; }
		$changed = $wpdb->update( $table, $update, array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ), 'source_version' => $current_source_version ) );
		if ( false === $changed ) { return new WP_Error( 'wca_payment_status_write_failed', __( 'Payment status projection could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( 1 !== (int) $changed ) { return new WP_Error( 'wca_payment_status_stale', __( 'Payment status changed concurrently. Reconciliation is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$updated = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", absint( $row['id'] ) ), ARRAY_A );
		if ( null === $updated && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_payment_status_readback_failed', __( 'Updated payment status could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( ! $updated ) { return new WP_Error( 'wca_payment_status_readback_missing', __( 'Updated payment projection could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$updated['_projection_action'] = 'applied';
		return $updated;
	}
'''
s = s[:start] + replacement + s[end:]
repo.write_text(s)

service = Path('includes/class-wca-service.php')
s = service.read_text()
method_start = s.find("\tpublic static function consume_payment_status_event( $event ) {")
if method_start < 0:
    raise SystemExit('R16 consume payment event method not found')
method_end_marker = "\n\tprivate static function approved_payment_provider( $provider )"
method_end = s.find(method_end_marker, method_start)
if method_end < 0:
    raise SystemExit('R16 consume payment event end not found')
method = r'''	public static function consume_payment_status_event( $event ) {
		if ( ! is_array( $event ) || true !== ( $event['verified'] ?? false ) || 'CF03' !== (string) ( $event['source'] ?? '' ) ) { return new WP_Error( 'wca_payment_status_unverified', __( 'Only a verified CF03 payment fact may update File 08 payment status.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$event_id = sanitize_text_field( $event['event_id'] ?? '' );
		$payment_ref = sanitize_text_field( $event['payment_intent_ref'] ?? '' );
		$status = sanitize_key( $event['status'] ?? '' );
		$provider_ref = sanitize_text_field( $event['provider_ref'] ?? '' );
		$source_version = self::strict_id( $event['source_version'] ?? null );
		$occurred_raw = trim( (string) ( $event['occurred_at'] ?? '' ) );
		$source_occurred_at = '';
		if ( preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:Z|[+-]\d{2}:\d{2})$/', $occurred_raw ) ) {
			try { $source_occurred_at = ( new DateTimeImmutable( $occurred_raw ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ); } catch ( Exception $e ) { $source_occurred_at = ''; }
		}
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $event_id ) || ! preg_match( '/^[0-9a-fA-F-]{36}$/', $payment_ref ) || ! in_array( $status, WCA_Contracts::payment_statuses(), true ) || 'pending' === $status || null === $source_version || ! $source_occurred_at ) { return new WP_Error( 'wca_payment_status_event_invalid', __( 'The verified financial event is malformed, unsupported, or lacks ordering evidence.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$fingerprint = array( 'payment_intent_ref' => $payment_ref, 'status' => $status, 'provider_ref' => $provider_ref, 'source_version' => $source_version, 'occurred_at' => $source_occurred_at );
		$claim = WCA_Repository::claim_idempotency( 'payment_status_event', $event_id, 0, $fingerprint );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return $claim['response']; }
		if ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_payment_status_event_in_progress', __( 'This payment status fact is already being reconciled.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$result = WCA_Repository::transaction( function () use ( $payment_ref, $status, $provider_ref, $event_id, $source_version, $source_occurred_at, $claim ) {
			$projected = WCA_Repository::project_payment_status( $payment_ref, $status, $provider_ref, $source_version, $event_id, $source_occurred_at );
			if ( is_wp_error( $projected ) ) { return $projected; }
			$action = sanitize_key( $projected['_projection_action'] ?? 'applied' );
			unset( $projected['_projection_action'] );
			$trace = WCA_Observability::trace_id();
			$audit_type = 'stale_ignored' === $action ? 'PaymentStatusStaleIgnored.v1' : ( 'same_version_replay' === $action ? 'PaymentStatusEquivalentReplay.v1' : 'PaymentStatusProjected.v1' );
			$audit = WCA_Repository::append_event( $audit_type, 'payment_intent', $payment_ref, array( 'event_id' => WCA_Repository::uuid(), 'source_event_id' => $event_id, 'source_version' => $source_version, 'source_occurred_at' => $source_occurred_at, 'payment_intent_ref' => $payment_ref, 'status' => $status, 'projection_action' => $action, 'trace_id' => $trace ), 0, $trace );
			if ( is_wp_error( $audit ) ) { return $audit; }
			$response = array_intersect_key( $projected, array_flip( array( 'public_ref','status','currency','amount_minor','platform_commission_minor','version','source_version','source_occurred_at','updated_at' ) ) );
			$response['projection_action'] = $action;
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], 200, $response ) ) { return new WP_Error( 'wca_payment_status_idempotency_complete', __( 'Payment status reconciliation could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			return $response;
		}, 'wca_payment_status_projection_transaction' );
		if ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim['id'] ); }
		return $result;
	}
'''
s = s[:method_start] + method + s[method_end:]
service.write_text(s)

# Exact-scope regression: prevents another false green caused by finding similarly
# named columns in a different CREATE TABLE block.
test = Path('tests/seventeenth-r16-finance-schema-reconciliation-regressions.php')
test.write_text(r'''<?php
$contracts = file_get_contents( __DIR__ . '/../includes/class-wca-contracts.php' );
$schema = file_get_contents( __DIR__ . '/../includes/class-wca-schema.php' );
$repo = file_get_contents( __DIR__ . '/../includes/class-wca-repository.php' );
$service = file_get_contents( __DIR__ . '/../includes/class-wca-service.php' );
if ( false === $contracts || false === $schema || false === $repo || false === $service ) { fwrite( STDERR, "T17 R16 source missing.\n" ); exit( 1 ); }
if ( false === strpos( $contracts, "SCHEMA_VERSION                  = '3.4.0'" ) ) { fwrite( STDERR, "T17 R16 corrective schema version is not active.\n" ); exit( 1 ); }
if ( ! preg_match( "/CREATE TABLE \\{\\\$tables\\['slot_holds'\\]\\} \\((.*?)\\) \\{\\\$collate\\};/s", $schema, $slot ) ) { fwrite( STDERR, "T17 R16 slot_holds schema block unavailable.\n" ); exit( 1 ); }
foreach ( array( 'rule_ref char(36)', 'capacity smallint(5) unsigned NOT NULL DEFAULT 1', 'buffer_before smallint(5) unsigned NOT NULL DEFAULT 0', 'buffer_after smallint(5) unsigned NOT NULL DEFAULT 0', 'KEY rule_window (rule_ref,start_utc,end_utc,status)' ) as $needle ) {
    if ( false === strpos( $slot[1], $needle ) ) { fwrite( STDERR, "T17 R16 slot_holds column/index missing: {$needle}\n" ); exit( 1 ); }
}
if ( ! preg_match( "/CREATE TABLE \\{\\\$tables\\['payment_intents'\\]\\} \\((.*?)\\) \\{\\\$collate\\};/s", $schema, $payment ) ) { fwrite( STDERR, "T17 R16 payment_intents schema block unavailable.\n" ); exit( 1 ); }
foreach ( array( 'source_version bigint(20) unsigned NOT NULL DEFAULT 0', "source_event_id varchar(191) NOT NULL DEFAULT ''", 'source_occurred_at datetime NULL', 'KEY source_order (source_version,source_occurred_at)' ) as $needle ) {
    if ( false === strpos( $payment[1], $needle ) ) { fwrite( STDERR, "T17 R16 payment ordering column/index missing: {$needle}\n" ); exit( 1 ); }
}
$required_repo = array(
    'public static function project_payment_status( $payment_ref, $status, $provider_ref = \'\', $source_version = 0, $source_event_id = \'\', $source_occurred_at = \'\' )',
    'if ( $source_version < $current_source_version )',
    "'_projection_action'] = 'stale_ignored'",
    "'wca_payment_source_version_conflict'",
    "'source_version' => $current_source_version",
);
foreach ( $required_repo as $needle ) { if ( false === strpos( $repo, $needle ) ) { fwrite( STDERR, "T17 R16 repository ordering invariant missing: {$needle}\n" ); exit( 1 ); } }
$required_service = array( "'source_version'", "'occurred_at'", 'PaymentStatusStaleIgnored.v1', 'PaymentStatusEquivalentReplay.v1', "'projection_action'" );
foreach ( $required_service as $needle ) { if ( false === strpos( $service, $needle ) ) { fwrite( STDERR, "T17 R16 service reconciliation invariant missing: {$needle}\n" ); exit( 1 ); } }
echo "T17 R16 finance/schema reconciliation regressions passed.\n";
''')

run = Path('tests/run-all.php')
r = run.read_text()
anchor = "'seventeenth-r15-capacity-concurrency-regressions.php'"
addition = anchor + ", 'seventeenth-r16-finance-schema-reconciliation-regressions.php'"
if 'seventeenth-r16-finance-schema-reconciliation-regressions.php' not in r:
    if r.count(anchor) != 1:
        raise SystemExit('R16 run-all anchor is not unique')
    run.write_text(r.replace(anchor, addition, 1))
