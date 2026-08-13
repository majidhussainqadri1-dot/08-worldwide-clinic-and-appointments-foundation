from pathlib import Path

root = Path(__file__).resolve().parents[1]

def replace(path, old, new):
    p = root / path
    text = p.read_text()
    if old not in text:
        raise SystemExit(f'anchor not found in {path}: {old[:120]!r}')
    p.write_text(text.replace(old, new, 1))

# 1) Canonical payment status vocabulary.
replace('includes/class-wca-contracts.php',
"\tpublic static function consumed_events() { return array( 'DoctorVerified.v1', 'DoctorSuspended.v1', 'PaymentStatusChanged.v1', 'MessageReported.v1' ); }",
"\t/** Payment truth remains CF03-owned; these are File08's accepted local projection states. */\n\tpublic static function payment_statuses() {\n\t\treturn array( 'pending','created','pending_provider','authorized','captured','settled','failed','cancelled','expired','refunded','disputed','uncertain' );\n\t}\n\n\tpublic static function consumed_events() { return array( 'DoctorVerified.v1', 'DoctorSuspended.v1', 'PaymentStatusChanged.v1', 'MessageReported.v1' ); }")

# 2) Appointment-owned immutable fee/policy snapshot at booking time.
replace('includes/class-wca-service.php',
"\t\t$service = $hold['service_id'] ? WCA_Repository::get_service( $hold['service_id'], true ) : null;\n\t\t$clinic  = WCA_Repository::get_clinic( $hold['clinic_id'], true );\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_unavailable', __( 'The clinic is not currently available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }",
"\t\tWCA_Repository::clear_read_error();\n\t\t$service = $hold['service_id'] ? WCA_Repository::get_service( $hold['service_id'], true ) : null;\n\t\t$service_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $service_read_error ) ) { return $service_read_error; }\n\t\tWCA_Repository::clear_read_error();\n\t\t$clinic  = WCA_Repository::get_clinic( $hold['clinic_id'], true );\n\t\t$clinic_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $clinic_read_error ) ) { return $clinic_read_error; }\n\t\tif ( ! $service ) { return new WP_Error( 'wca_service_unavailable', __( 'The appointment service is no longer available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_unavailable', __( 'The clinic is not currently available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }")

replace('includes/class-wca-service.php',
"\t\t\t\t'appointment_duration'   => $service['duration_minutes'] ?? absint( ( strtotime( $hold['end_utc'] ) - strtotime( $hold['start_utc'] ) ) / 60 ),\n\t\t\t\t'reason_category'        => sanitize_key( $data['category'] ?? 'general' ),",
"\t\t\t\t'appointment_duration'   => $service['duration_minutes'] ?? absint( ( strtotime( $hold['end_utc'] ) - strtotime( $hold['start_utc'] ) ) / 60 ),\n\t\t\t\t'service_public_ref_snapshot' => (string) $service['public_ref'],\n\t\t\t\t'service_version_snapshot'    => absint( $service['version'] ),\n\t\t\t\t'fee_currency_snapshot'       => (string) $service['currency'],\n\t\t\t\t'fee_amount_minor_snapshot'   => absint( $service['fee_minor'] ),\n\t\t\t\t'fee_max_minor_snapshot'       => absint( $service['fee_max_minor'] ),\n\t\t\t\t'tax_policy_snapshot'          => (string) $service['tax_policy'],\n\t\t\t\t'refund_policy_snapshot'       => (string) $service['refund_policy'],\n\t\t\t\t'cancellation_policy_snapshot' => (string) $service['cancellation_policy'],\n\t\t\t\t'platform_commission_bps_snapshot' => 0,\n\t\t\t\t'reason_category'        => sanitize_key( $data['category'] ?? 'general' ),")

# 3) Approved provider boundary + immutable appointment fee snapshot for payment intent.
replace('includes/class-wca-service.php',
"\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function create_payment_intent( $appointment_id, $provider = 'manual', $actor_user_id = 0, $idempotency_key = '' ) {",
"\tprivate static function approved_payment_provider( $provider ) {\n\t\t$provider = sanitize_key( (string) $provider );\n\t\t$approved = apply_filters( 'wca_cf03_approved_payment_providers', array( 'manual' ) );\n\t\tif ( ! is_array( $approved ) ) { return ''; }\n\t\t$approved = array_values( array_unique( array_filter( array_map( 'sanitize_key', $approved ) ) ) );\n\t\treturn $provider && in_array( $provider, $approved, true ) ? $provider : '';\n\t}\n\n\tprivate static function appointment_fee_snapshot( $appointment_id ) {\n\t\t$currency = strtoupper( trim( (string) SWC_Helpers::meta( $appointment_id, 'fee_currency_snapshot' ) ) );\n\t\t$amount_raw = SWC_Helpers::meta( $appointment_id, 'fee_amount_minor_snapshot', null );\n\t\t$amount = self::strict_int( is_scalar( $amount_raw ) ? (string) $amount_raw : '', 0, PHP_INT_MAX );\n\t\t$service_ref = (string) SWC_Helpers::meta( $appointment_id, 'service_public_ref_snapshot' );\n\t\t$service_version = absint( SWC_Helpers::meta( $appointment_id, 'service_version_snapshot' ) );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) || null === $amount || ! $service_ref || ! $service_version ) {\n\t\t\treturn new WP_Error( 'wca_payment_snapshot_missing', __( 'The appointment does not have a trustworthy booked fee snapshot. Financial reconciliation is required before payment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\treturn array(\n\t\t\t'currency' => $currency,\n\t\t\t'amount_minor' => $amount,\n\t\t\t'fee_max_minor' => absint( SWC_Helpers::meta( $appointment_id, 'fee_max_minor_snapshot' ) ),\n\t\t\t'service_ref' => $service_ref,\n\t\t\t'service_version' => $service_version,\n\t\t\t'tax_policy' => (string) SWC_Helpers::meta( $appointment_id, 'tax_policy_snapshot' ),\n\t\t\t'refund_policy' => (string) SWC_Helpers::meta( $appointment_id, 'refund_policy_snapshot' ),\n\t\t\t'cancellation_policy' => (string) SWC_Helpers::meta( $appointment_id, 'cancellation_policy_snapshot' ),\n\t\t\t'platform_commission_bps' => 0,\n\t\t);\n\t}\n\n\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function create_payment_intent( $appointment_id, $provider = 'manual', $actor_user_id = 0, $idempotency_key = '' ) {")

old_payment = """\t\t$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
\t\tif ( is_wp_error( $access ) ) { return $access; }
\t\t$service_id = absint( SWC_Helpers::meta( $appointment_id, 'service_id' ) );
\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, false ) : null;
\t\tif ( ! $service ) { return new WP_Error( 'wca_service_missing', __( 'Appointment service is unavailable.', 'worldwide-clinic-appointments' ) ); }
\t\t$claim = WCA_Repository::claim_idempotency( 'payment_intent', $idempotency_key, $actor_user_id, array( 'appointment_id' => absint( $appointment_id ), 'provider' => sanitize_key( $provider ), 'service_ref' => (string) $service['public_ref'], 'currency' => (string) $service['currency'], 'amount_minor' => absint( $service['fee_minor'] ) ) );
\t\tif ( is_wp_error( $claim ) ) { return $claim; }
\t\tif ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return $claim['response']; }
\t\tif ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_idempotency_in_progress', __( 'This payment request is already being processed.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) ); }
\t\t$result = WCA_Repository::transaction( function () use ( $appointment_id, $provider, $idempotency_key, $service, $claim ) {
\t\t\t$payment = WCA_Repository::create_payment_intent( array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'request_key' => $idempotency_key, 'currency' => $service['currency'], 'amount_minor' => $service['fee_minor'], 'status' => 'pending', 'metadata' => array( 'service_ref' => $service['public_ref'], 'commission_percent' => 0 ) ) );
\t\t\tif ( is_wp_error( $payment ) ) { return $payment; }
\t\t\t$queued = WCA_Repository::enqueue( 'CF03.PaymentIntentRequested.v1', $payment['public_ref'], array( 'payment_intent_ref' => $payment['public_ref'], 'appointment_ref' => SWC_Helpers::meta( $appointment_id, 'public_ref' ), 'currency' => $payment['currency'], 'amount_minor' => $payment['amount_minor'], 'platform_commission_minor' => 0 ), WCA_Observability::trace_id() );
"""
new_payment = """\t\t$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
\t\tif ( is_wp_error( $access ) ) { return $access; }
\t\t$provider = self::approved_payment_provider( $provider );
\t\tif ( ! $provider ) { return new WP_Error( 'wca_payment_provider_unapproved', __( 'The selected payment provider is not approved by the shared financial owner.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t$snapshot = self::appointment_fee_snapshot( $appointment_id );
\t\tif ( is_wp_error( $snapshot ) ) { return $snapshot; }
\t\t$claim = WCA_Repository::claim_idempotency( 'payment_intent', $idempotency_key, $actor_user_id, array( 'appointment_id' => absint( $appointment_id ), 'provider' => $provider, 'service_ref' => $snapshot['service_ref'], 'service_version' => $snapshot['service_version'], 'currency' => $snapshot['currency'], 'amount_minor' => $snapshot['amount_minor'] ) );
\t\tif ( is_wp_error( $claim ) ) { return $claim; }
\t\tif ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return $claim['response']; }
\t\tif ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_idempotency_in_progress', __( 'This payment request is already being processed.', 'worldwide-clinic-appointments' ), array( 'status' => 409, 'retry_after' => 2 ) ); }
\t\t$result = WCA_Repository::transaction( function () use ( $appointment_id, $provider, $idempotency_key, $snapshot, $claim ) {
\t\t\t$payment = WCA_Repository::create_payment_intent( array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'request_key' => $idempotency_key, 'currency' => $snapshot['currency'], 'amount_minor' => $snapshot['amount_minor'], 'status' => 'pending', 'metadata' => array( 'service_ref' => $snapshot['service_ref'], 'service_version' => $snapshot['service_version'], 'fee_max_minor' => $snapshot['fee_max_minor'], 'tax_policy' => $snapshot['tax_policy'], 'refund_policy' => $snapshot['refund_policy'], 'cancellation_policy' => $snapshot['cancellation_policy'], 'commission_percent' => 0 ) ) );
\t\t\tif ( is_wp_error( $payment ) ) { return $payment; }
\t\t\t$queued = WCA_Repository::enqueue( 'CF03.PaymentIntentRequested.v1', $payment['public_ref'], array( 'payment_intent_ref' => $payment['public_ref'], 'appointment_ref' => SWC_Helpers::meta( $appointment_id, 'public_ref' ), 'provider' => $provider, 'service_ref' => $snapshot['service_ref'], 'service_version' => $snapshot['service_version'], 'currency' => $payment['currency'], 'amount_minor' => $payment['amount_minor'], 'platform_commission_minor' => 0 ), WCA_Observability::trace_id() );
"""
replace('includes/class-wca-service.php', old_payment, new_payment)

# 4) Terminal appointment transition emits policy/reconciliation request without pretending File08 owns refunds.
replace('includes/class-wca-service.php',
"\t\t\t$communication = WCA_Repository::enqueue( 'File17.AppointmentContextChanged.v1', $public_ref, self::file17_context_payload( $appointment_id ), $trace );\n\t\t\tif ( is_wp_error( $communication ) ) { return $communication; }\n\t\t\tif ( 'completed' === $next ) {",
"\t\t\t$communication = WCA_Repository::enqueue( 'File17.AppointmentContextChanged.v1', $public_ref, self::file17_context_payload( $appointment_id ), $trace );\n\t\t\tif ( is_wp_error( $communication ) ) { return $communication; }\n\t\t\tif ( in_array( $next, array( 'declined','cancelled','no_show' ), true ) ) {\n\t\t\t\t$fee_snapshot = self::appointment_fee_snapshot( $appointment_id );\n\t\t\t\t$fee_payload = array(\n\t\t\t\t\t'appointment_ref' => $public_ref,\n\t\t\t\t\t'appointment_status' => $next,\n\t\t\t\t\t'reason_code' => sanitize_key( $data['reason_code'] ?? '' ),\n\t\t\t\t\t'scheduled_at_utc' => (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ),\n\t\t\t\t\t'platform_commission_minor' => 0,\n\t\t\t\t\t'action' => 'evaluate_fee_refund_or_void_policy',\n\t\t\t\t\t'trace_id' => $trace,\n\t\t\t\t);\n\t\t\t\tif ( is_wp_error( $fee_snapshot ) ) {\n\t\t\t\t\t$fee_payload['snapshot_status'] = 'legacy_missing_reconciliation_required';\n\t\t\t\t} else {\n\t\t\t\t\t$fee_payload['snapshot_status'] = 'booked_snapshot';\n\t\t\t\t\t$fee_payload['fee'] = $fee_snapshot;\n\t\t\t\t}\n\t\t\t\t$fee_review = WCA_Repository::enqueue( 'CF03.AppointmentFeePolicyReviewRequested.v1', $public_ref, $fee_payload, $trace );\n\t\t\t\tif ( is_wp_error( $fee_review ) ) { return $fee_review; }\n\t\t\t}\n\t\t\tif ( 'completed' === $next ) {")

# 5) Repository validates local status, exposes latest projection, and atomically projects trusted CF03 status facts.
replace('includes/class-wca-repository.php',
"\t\t$row = array(\n\t\t\t'public_ref'                 => self::uuid(),",
"\t\t$status = sanitize_key( $data['status'] ?? 'pending' );\n\t\tif ( ! in_array( $status, WCA_Contracts::payment_statuses(), true ) ) { return new WP_Error( 'wca_payment_status_invalid', __( 'Payment intent status is not recognized.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$row = array(\n\t\t\t'public_ref'                 => self::uuid(),")
replace('includes/class-wca-repository.php',
"\t\t\t'status'                     => sanitize_key( $data['status'] ?? 'pending' ),",
"\t\t\t'status'                     => $status,")

anchor = "\t\treturn array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );\n\t}\n\n\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function enqueue("
insert = """\t\treturn array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
\t}

\t/** @return array<string,mixed>|null|WP_Error */
\tpublic static function latest_payment_for_appointment( $appointment_id ) {
\t\tglobal $wpdb;
\t\t$table = WCA_Schema::tables()['payment_intents'];
\t\t$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE appointment_id=%d ORDER BY id DESC LIMIT 1", absint( $appointment_id ) ), ARRAY_A );
\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_payment_projection_read_failed', __( 'Payment status could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn $row ?: null;
\t}

\t/** Trusted CF03 fact -> File08 local projection. Financial ledger truth remains CF03-owned. */
\tpublic static function project_payment_status( $payment_ref, $status, $provider_ref = '' ) {
\t\tglobal $wpdb;
\t\t$table = WCA_Schema::tables()['payment_intents'];
\t\t$status = sanitize_key( $status );
\t\tif ( ! in_array( $status, WCA_Contracts::payment_statuses(), true ) || 'pending' === $status ) { return new WP_Error( 'wca_payment_status_untrusted', __( 'The financial owner supplied an unsupported payment status.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1 FOR UPDATE", sanitize_text_field( $payment_ref ) ), ARRAY_A );
\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_payment_status_read_failed', __( 'Payment projection could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\tif ( ! $row ) { return new WP_Error( 'wca_payment_status_not_found', __( 'Payment intent was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
\t\t$provider_ref = sanitize_text_field( $provider_ref );
\t\tif ( $provider_ref && ! empty( $row['provider_ref'] ) && ! hash_equals( (string) $row['provider_ref'], $provider_ref ) ) { return new WP_Error( 'wca_payment_provider_ref_conflict', __( 'Payment provider identity does not match the existing projection.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\tif ( (string) $row['status'] === $status && ( ! $provider_ref || (string) $row['provider_ref'] === $provider_ref ) ) { return $row; }
\t\t$update = array( 'status' => $status, 'version' => absint( $row['version'] ) + 1, 'updated_at' => self::now() );
\t\tif ( $provider_ref && empty( $row['provider_ref'] ) ) { $update['provider_ref'] = $provider_ref; }
\t\t$changed = $wpdb->update( $table, $update, array( 'id' => absint( $row['id'] ), 'version' => absint( $row['version'] ) ) );
\t\tif ( false === $changed ) { return new WP_Error( 'wca_payment_status_write_failed', __( 'Payment status projection could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\tif ( 1 !== (int) $changed ) { return new WP_Error( 'wca_payment_status_stale', __( 'Payment status changed concurrently. Reconciliation is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t$updated = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", absint( $row['id'] ) ), ARRAY_A );
\t\tif ( null === $updated && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_payment_status_readback_failed', __( 'Updated payment status could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn $updated ?: new WP_Error( 'wca_payment_status_readback_missing', __( 'Updated payment projection could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t}

\t/** @return array<string,mixed>|WP_Error */
\tpublic static function enqueue("""
replace('includes/class-wca-repository.php', anchor, insert)

# 6) Trusted CF03 inbound adapter; unverified legacy action can never mutate local state.
replace('includes/class-wca-service.php',
"\t\tadd_action( 'wca_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed' ), 10, 2 );",
"\t\tadd_action( 'wca_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed' ), 10, 2 );\n\t\tadd_action( 'cf03_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed_event' ), 10, 1 );")

replace('includes/class-wca-service.php',
"\tpublic static function handle_payment_status_changed( $payment_ref, $status ) {\n\t\tWCA_Observability::log( 'info', 'payment_status_changed', array( 'payment_ref' => $payment_ref, 'status' => sanitize_key( $status ) ) );\n\t}\n}",
"\tpublic static function handle_payment_status_changed( $payment_ref, $status ) {\n\t\tWCA_Observability::metric( 'unverified_payment_status_ignored_total', 1 );\n\t\tWCA_Observability::log( 'warning', 'unverified_payment_status_ignored', array( 'payment_ref' => sanitize_text_field( $payment_ref ), 'status' => sanitize_key( $status ) ) );\n\t\treturn new WP_Error( 'wca_payment_status_unverified', __( 'Unverified payment status input was ignored.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );\n\t}\n\n\tpublic static function handle_payment_status_changed_event( $event ) {\n\t\t$result = self::consume_payment_status_event( $event );\n\t\tif ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'payment_status_projection_failed', array( 'code' => $result->get_error_code() ) ); }\n\t\treturn $result;\n\t}\n\n\tpublic static function consume_payment_status_event( $event ) {\n\t\tif ( ! is_array( $event ) || true !== ( $event['verified'] ?? false ) || 'CF03' !== (string) ( $event['source'] ?? '' ) ) { return new WP_Error( 'wca_payment_status_unverified', __( 'Only a verified CF03 payment fact may update File 08 payment status.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }\n\t\t$event_id = sanitize_text_field( $event['event_id'] ?? '' );\n\t\t$payment_ref = sanitize_text_field( $event['payment_intent_ref'] ?? '' );\n\t\t$status = sanitize_key( $event['status'] ?? '' );\n\t\t$provider_ref = sanitize_text_field( $event['provider_ref'] ?? '' );\n\t\tif ( ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $event_id ) || ! preg_match( '/^[0-9a-fA-F-]{36}$/', $payment_ref ) || ! in_array( $status, WCA_Contracts::payment_statuses(), true ) || 'pending' === $status ) { return new WP_Error( 'wca_payment_status_event_invalid', __( 'The verified financial event is malformed or unsupported.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$claim = WCA_Repository::claim_idempotency( 'payment_status_event', $event_id, 0, array( 'payment_intent_ref' => $payment_ref, 'status' => $status, 'provider_ref' => $provider_ref ) );\n\t\tif ( is_wp_error( $claim ) ) { return $claim; }\n\t\tif ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return $claim['response']; }\n\t\tif ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_payment_status_event_in_progress', __( 'This payment status fact is already being reconciled.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t$result = WCA_Repository::transaction( function () use ( $payment_ref, $status, $provider_ref, $event_id, $claim ) {\n\t\t\t$projected = WCA_Repository::project_payment_status( $payment_ref, $status, $provider_ref );\n\t\t\tif ( is_wp_error( $projected ) ) { return $projected; }\n\t\t\t$trace = WCA_Observability::trace_id();\n\t\t\t$audit = WCA_Repository::append_event( 'PaymentStatusProjected.v1', 'payment_intent', $payment_ref, array( 'event_id' => WCA_Repository::uuid(), 'source_event_id' => $event_id, 'payment_intent_ref' => $payment_ref, 'status' => $status, 'trace_id' => $trace ), 0, $trace );\n\t\t\tif ( is_wp_error( $audit ) ) { return $audit; }\n\t\t\t$response = array_intersect_key( $projected, array_flip( array( 'public_ref','status','currency','amount_minor','platform_commission_minor','version','updated_at' ) ) );\n\t\t\tif ( ! WCA_Repository::complete_idempotency( $claim['id'], 200, $response ) ) { return new WP_Error( 'wca_payment_status_idempotency_complete', __( 'Payment status reconciliation could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }\n\t\t\treturn $response;\n\t\t}, 'wca_payment_status_projection_transaction' );\n\t\tif ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim['id'] ); }\n\t\treturn $result;\n\t}\n}")

# 7) Authorized appointment projection exposes only safe local payment status projection.
replace('includes/class-wca-rest.php',
"\t\tWCA_Repository::clear_read_error();\n\t\t$service = WCA_Repository::get_service( absint( SWC_Helpers::meta( $id, 'service_id' ) ), false );\n\t\t$service_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $service_read_error ) ) { return $service_read_error; }\n\t\treturn array(",
"\t\tWCA_Repository::clear_read_error();\n\t\t$service = WCA_Repository::get_service( absint( SWC_Helpers::meta( $id, 'service_id' ) ), false );\n\t\t$service_read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $service_read_error ) ) { return $service_read_error; }\n\t\t$payment = WCA_Repository::latest_payment_for_appointment( $id );\n\t\tif ( is_wp_error( $payment ) ) { return $payment; }\n\t\t$payment_projection = $payment ? array_intersect_key( $payment, array_flip( array( 'public_ref','provider','currency','amount_minor','platform_commission_minor','status','version','created_at','updated_at' ) ) ) : null;\n\t\treturn array(")
replace('includes/class-wca-rest.php',
"\t\t\t'clinical_authority'=> false,",
"\t\t\t'payment'           => $payment_projection,\n\t\t\t'clinical_authority'=> false,")

# Permanent R14 regression gate.
p = root / 'tests/sixteenth-twenty-review-regressions.php'
s = p.read_text()
marker = 'if($fail){fwrite(STDERR,"T16 regression gate failed:'
idx = s.index(marker)
checks = """t16h('R14 appointment captures fee currency snapshot','includes/class-wca-service.php',"'fee_currency_snapshot'");
t16h('R14 appointment captures fee amount snapshot','includes/class-wca-service.php',"'fee_amount_minor_snapshot'");
t16h('R14 appointment captures policy snapshots','includes/class-wca-service.php',"'cancellation_policy_snapshot'");
t16h('R14 payment uses appointment-owned fee snapshot','includes/class-wca-service.php','appointment_fee_snapshot( $appointment_id )');
t16h('R14 payment provider must be approved by CF03 boundary','includes/class-wca-service.php','wca_cf03_approved_payment_providers');
t16h('R14 outbound payment intent carries provider','includes/class-wca-service.php',"'provider' => $provider");
t16h('R14 cancellation/no-show emits fee policy review','includes/class-wca-service.php','CF03.AppointmentFeePolicyReviewRequested.v1');
t16h('R14 unverified payment status is ignored','includes/class-wca-service.php','unverified_payment_status_ignored');
t16h('R14 verified CF03 status event is idempotent','includes/class-wca-service.php',"'payment_status_event'");
t16h('R14 payment status projection is durable','includes/class-wca-repository.php','public static function project_payment_status');
t16h('R14 unknown payment projection status rejected','includes/class-wca-repository.php','wca_payment_status_untrusted');
t16h('R14 authorized appointment includes safe payment projection','includes/class-wca-rest.php',"'payment'           => $payment_projection");
"""
p.write_text(s[:idx] + checks + s[idx:])

print('R14 closed ledger applied')
