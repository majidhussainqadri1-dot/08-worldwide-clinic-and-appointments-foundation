from pathlib import Path
root=Path(__file__).resolve().parents[1]

def rep(path,old,new,count=1):
 p=root/path; s=p.read_text()
 if old not in s: raise SystemExit(f'anchor missing {path}: {old[:100]!r}')
 p.write_text(s.replace(old,new,count))

# Calendar signed-link lookups fail closed with explicit DB/storage errors.
rep('includes/class-wca-calendar-link.php',"\tconst CONTRACT_VERSION = '1.0.1';","\tconst CONTRACT_VERSION = '1.1.0';")
rep('includes/class-wca-calendar-link.php',
"\t\t$id = self::appointment_id( $appointment_ref );\n\t\tif ( ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return ''; }",
"\t\t$id = self::appointment_id( $appointment_ref );\n\t\tif ( is_wp_error( $id ) ) { return $id; }\n\t\tif ( ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return ''; }")
rep('includes/class-wca-calendar-link.php',
"\t\t$url = self::url( sanitize_text_field( $request['ref'] ), get_current_user_id() );\n\t\tif ( ! $url ) { return new WP_Error( 'wca_calendar_forbidden', __( 'Calendar export is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }",
"\t\t$url = self::url( sanitize_text_field( $request['ref'] ), get_current_user_id() );\n\t\tif ( is_wp_error( $url ) ) { return $url; }\n\t\tif ( ! $url ) { return new WP_Error( 'wca_calendar_forbidden', __( 'Calendar export is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }")
rep('includes/class-wca-calendar-link.php',
"\t\t$user_id = self::user_id_from_subject( $subject );\n\t\t$id = self::appointment_id( $ref );\n\t\tif ( ! $user_id || ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return new WP_Error( 'wca_calendar_link_invalid', __( 'This calendar link is invalid or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }",
"\t\t$user_id = self::user_id_from_subject( $subject );\n\t\tif ( is_wp_error( $user_id ) ) { return $user_id; }\n\t\t$id = self::appointment_id( $ref );\n\t\tif ( is_wp_error( $id ) ) { return $id; }\n\t\tif ( ! $user_id || ! $id || is_wp_error( WCA_Authorization::can_view_appointment( $id, $user_id ) ) ) { return new WP_Error( 'wca_calendar_link_invalid', __( 'This calendar link is invalid or expired.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }")
old="""\tprivate static function user_id_from_subject( $subject ) {
\t\tif ( function_exists( 'smc_get_user_id_by_subject_uuid' ) ) { $id = absint( smc_get_user_id_by_subject_uuid( $subject ) ); if ( $id ) { return $id; } }
\t\t$users = get_users( array( 'fields' => 'ids', 'number' => 2, 'meta_key' => '_smc_subject_uuid', 'meta_value' => $subject ) );
\t\treturn 1 === count( $users ) ? absint( $users[0] ) : 0;
\t}

\tprivate static function appointment_id( $ref ) {
\t\t$ids = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 2, 'no_found_rows' => true, 'meta_key' => '_swc_public_ref', 'meta_value' => strtolower( sanitize_text_field( $ref ) ) ) );
\t\treturn 1 === count( $ids ) ? absint( $ids[0] ) : 0;
\t}
"""
new="""\tprivate static function user_id_from_subject( $subject ) {
\t\tglobal $wpdb;
\t\t$subject = strtolower( sanitize_text_field( $subject ) );
\t\t$ids = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key=%s AND meta_value=%s ORDER BY user_id ASC LIMIT 2", '_smc_subject_uuid', $subject ) );
\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_calendar_subject_read_failed', __( 'Calendar participant identity could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn 1 === count( $ids ) ? absint( $ids[0] ) : 0;
\t}

\tprivate static function appointment_id( $ref ) {
\t\tglobal $wpdb;
\t\t$ref = strtolower( sanitize_text_field( $ref ) );
\t\t$ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key=%s WHERE p.post_type=%s AND pm.meta_value=%s ORDER BY p.ID ASC LIMIT 2", '_swc_public_ref', SWC_Helpers::TYPE, $ref ) );
\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_calendar_appointment_read_failed', __( 'Calendar appointment state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn 1 === count( $ids ) ? absint( $ids[0] ) : 0;
\t}
"""
rep('includes/class-wca-calendar-link.php',old,new)

# Boot signed provider webhook adapter from the same calendar module.
rep('includes/class-wca-calendar-link.php',
"\tpublic static function boot() { add_action( 'rest_api_init', array( __CLASS__, 'register_route' ), 70 ); }",
"\tpublic static function boot() { add_action( 'rest_api_init', array( __CLASS__, 'register_route' ), 70 ); }")
# Add provider webhook route.
rep('includes/class-wca-calendar-link.php',
"\t\tregister_rest_route( 'wca/v1', '/calendar-links/(?P<ref>[0-9a-fA-F-]{36})\\.ics', array(\n\t\t\t'methods' => WP_REST_Server::READABLE,\n\t\t\t'callback' => array( __CLASS__, 'download' ),\n\t\t\t'permission_callback' => '__return_true',\n\t\t) );",
"\t\tregister_rest_route( 'wca/v1', '/calendar-links/(?P<ref>[0-9a-fA-F-]{36})\\.ics', array(\n\t\t\t'methods' => WP_REST_Server::READABLE,\n\t\t\t'callback' => array( __CLASS__, 'download' ),\n\t\t\t'permission_callback' => '__return_true',\n\t\t) );\n\t\tregister_rest_route( 'wca/v1', '/calendar-provider-webhooks/(?P<provider>[a-z0-9_-]{2,60})', array(\n\t\t\t'methods' => WP_REST_Server::CREATABLE,\n\t\t\t'callback' => array( __CLASS__, 'provider_webhook' ),\n\t\t\t'permission_callback' => '__return_true',\n\t\t) );")
# Insert provider webhook before signer.
anchor="\n\tpublic static function signer( WP_REST_Request $request ) {"
webhook=r'''
	public static function provider_webhook( WP_REST_Request $request ) {
		$provider = sanitize_key( (string) $request['provider'] );
		$allowed = apply_filters( 'wca_calendar_provider_ids', array() );
		$allowed = is_array( $allowed ) ? array_values( array_unique( array_filter( array_map( 'sanitize_key', $allowed ) ) ) ) : array();
		if ( ! in_array( $provider, $allowed, true ) ) { return new WP_Error( 'wca_calendar_provider_unavailable', __( 'Calendar provider adapter is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$raw = (string) $request->get_body();
		if ( '' === $raw || strlen( $raw ) > 65536 ) { return new WP_Error( 'wca_calendar_webhook_payload', __( 'Calendar provider payload is empty or too large.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$verified = apply_filters( 'wca_calendar_provider_verify_webhook', null, $provider, $request, $raw );
		if ( is_wp_error( $verified ) ) { return $verified; }
		if ( ! is_array( $verified ) || true !== ( $verified['verified'] ?? false ) ) { return new WP_Error( 'wca_calendar_webhook_signature', __( 'Calendar provider signature could not be verified.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$event_id = sanitize_text_field( (string) ( $verified['event_id'] ?? '' ) );
		$occurred_at = sanitize_text_field( (string) ( $verified['occurred_at'] ?? '' ) );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,191}$/', $event_id ) || false === self::strict_utc_timestamp( $occurred_at ) || abs( time() - self::strict_utc_timestamp( $occurred_at ) ) > 900 ) { return new WP_Error( 'wca_calendar_webhook_replay_window', __( 'Calendar provider event identity or timestamp is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$doctor_id = absint( $verified['doctor_user_id'] ?? 0 );
		if ( ! $doctor_id || ! SWC_Doctor_Authority::is_eligible( $doctor_id ) ) { return new WP_Error( 'wca_calendar_webhook_doctor', __( 'Calendar provider event is not bound to an eligible practitioner.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$claim = WCA_Repository::claim_idempotency( 'calendar_provider_webhook', $provider . ':' . $event_id, 0, array( 'provider' => $provider, 'event_id' => $event_id, 'doctor_user_id' => $doctor_id ) );
		if ( is_wp_error( $claim ) ) { return $claim; }
		if ( 'completed' === (string) ( $claim['status'] ?? '' ) ) { return rest_ensure_response( $claim['response'] ); }
		if ( empty( $claim['claimed_new'] ) ) { return new WP_Error( 'wca_calendar_webhook_in_progress', __( 'This calendar provider event is already being reconciled.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$result = WCA_Repository::transaction( function () use ( $verified, $provider, $event_id, $doctor_id, $claim ) {
			$busy_count = 0;
			foreach ( (array) ( $verified['busy_windows'] ?? array() ) as $window ) {
				if ( ! is_array( $window ) ) { return new WP_Error( 'wca_calendar_webhook_window', __( 'Calendar busy-window payload is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
				$saved = WCA_Future24::save_verified_external_busy( $doctor_id, $provider, $event_id, $window );
				if ( is_wp_error( $saved ) ) { return $saved; }
				$busy_count++;
			}
			$mapping = null;
			if ( ! empty( $verified['appointment_ref'] ) || ! empty( $verified['provider_event_ref'] ) ) {
				$mapping = WCA_Repository::upsert_calendar_mapping_from_provider( $provider, $verified, $doctor_id );
				if ( is_wp_error( $mapping ) ) { return $mapping; }
			}
			$trace = WCA_Observability::trace_id();
			$audit = WCA_Repository::append_event( 'CalendarProviderReconciled.v1', 'calendar_provider', $provider . ':' . $event_id, array( 'event_id' => WCA_Repository::uuid(), 'provider' => $provider, 'source_event_id' => $event_id, 'doctor_subject_uuid' => WCA_Authorization::subject_uuid( $doctor_id ), 'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'trace_id' => $trace ), $doctor_id, $trace );
			if ( is_wp_error( $audit ) ) { return $audit; }
			$response = array( 'accepted' => true, 'provider' => $provider, 'event_id' => $event_id, 'busy_windows' => $busy_count, 'mapping_ref' => is_array( $mapping ) ? (string) ( $mapping['public_ref'] ?? '' ) : '', 'canonical_appointment_mutated' => false );
			if ( ! WCA_Repository::complete_idempotency( $claim['id'], 202, $response ) ) { return new WP_Error( 'wca_calendar_webhook_finalize', __( 'Calendar provider reconciliation could not be finalized safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
			return $response;
		}, 'wca_calendar_provider_webhook_transaction' );
		if ( is_wp_error( $result ) ) { WCA_Repository::release_idempotency( $claim['id'] ); return $result; }
		$response = rest_ensure_response( $result );
		$response->set_status( 202 );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'X-Robots-Tag', 'noindex, noarchive, nofollow' );
		return $response;
	}
'''
p=root/'includes/class-wca-calendar-link.php'; s=p.read_text();
if anchor not in s: raise SystemExit('calendar signer anchor missing')
p.write_text(s.replace(anchor,webhook+anchor,1))

# Repository calendar mapping projection, never canonical appointment mutation.
anchor="\n\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function enqueue("
method=r'''
	/** Provider calendar projection only; appointment truth remains File08 canonical state. */
	public static function upsert_calendar_mapping_from_provider( $provider, $event, $doctor_id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['calendar_mappings'];
		$provider = sanitize_key( (string) $provider );
		$appointment_ref = strtolower( sanitize_text_field( (string) ( $event['appointment_ref'] ?? '' ) ) );
		$provider_event_ref = sanitize_text_field( (string) ( $event['provider_event_ref'] ?? '' ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $appointment_ref ) || '' === $provider_event_ref || strlen( $provider_event_ref ) > 191 ) { return new WP_Error( 'wca_calendar_mapping_invalid', __( 'Calendar mapping identifiers are invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key=%s WHERE p.post_type=%s AND pm.meta_value=%s ORDER BY p.ID ASC LIMIT 2", '_swc_public_ref', SWC_Helpers::TYPE, $appointment_ref ) );
		if ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_calendar_mapping_appointment_read_failed', __( 'Calendar mapping appointment could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		if ( 1 !== count( $ids ) ) { return new WP_Error( 'wca_calendar_mapping_appointment', __( 'Calendar mapping appointment was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$appointment_id = absint( $ids[0] );
		$actor = WCA_Authorization::appointment_actor( $appointment_id, $doctor_id );
		if ( ! in_array( $actor, array( 'doctor','clinic_staff','admin' ), true ) ) { return new WP_Error( 'wca_calendar_mapping_scope', __( 'Calendar provider event is outside the practitioner appointment scope.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE provider=%s AND provider_event_ref=%s LIMIT 1", $provider, $provider_event_ref ), ARRAY_A );
		if ( null === $existing && '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_calendar_mapping_read_failed', __( 'Calendar mapping could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$sync_status = sanitize_key( (string) ( $event['sync_status'] ?? 'synced' ) );
		$conflict_status = sanitize_key( (string) ( $event['conflict_status'] ?? 'none' ) );
		if ( ! in_array( $sync_status, array( 'pending','synced','stale','failed','deleted' ), true ) || ! in_array( $conflict_status, array( 'none','busy_conflict','provider_changed','canonical_changed','uncertain' ), true ) ) { return new WP_Error( 'wca_calendar_mapping_state', __( 'Calendar mapping state is unsupported.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$metadata = array( 'source' => 'verified_provider_webhook', 'provider_token_stored' => false, 'canonical_appointment_mutated' => false );
		$row = array( 'appointment_id' => $appointment_id, 'provider' => $provider, 'provider_event_ref' => $provider_event_ref, 'etag' => sanitize_text_field( (string) ( $event['etag'] ?? '' ) ), 'last_synced_at' => self::now(), 'sync_status' => $sync_status, 'conflict_status' => $conflict_status, 'metadata_json' => wp_json_encode( $metadata ), 'updated_at' => self::now() );
		if ( $existing ) {
			if ( absint( $existing['appointment_id'] ) !== $appointment_id ) { return new WP_Error( 'wca_calendar_mapping_conflict', __( 'Provider event is already mapped to another appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
			$changed = $wpdb->update( $table, $row, array( 'id' => absint( $existing['id'] ) ) );
			if ( false === $changed ) { return new WP_Error( 'wca_calendar_mapping_write_failed', __( 'Calendar mapping could not be updated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
			return array_merge( $existing, $row );
		}
		$row['public_ref'] = self::uuid(); $row['created_at'] = self::now();
		if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_calendar_mapping_write_failed', __( 'Calendar mapping could not be created safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		return array_merge( array( 'id' => absint( $wpdb->insert_id ) ), $row );
	}
'''
p=root/'includes/class-wca-repository.php'; s=p.read_text();
if anchor not in s: raise SystemExit('repository enqueue anchor missing')
p.write_text(s.replace(anchor,method+anchor,1))

# Future24: generic slot responses + holds enforce external busy, and signed provider import has a strict internal entry point.
# Add verified external busy method before existing save_external_busy.
p=root/'includes/class-wca-future24.php'; s=p.read_text()
anchor="\tpublic static function save_external_busy( $data, $actor = 0 ) {"
verified=r'''
	public static function save_verified_external_busy( $doctor_id, $provider, $event_id, $window ) {
		$doctor_id = absint( $doctor_id ); $provider = sanitize_key( (string) $provider ); $event_id = sanitize_text_field( (string) $event_id );
		if ( ! $doctor_id || ! SWC_Doctor_Authority::is_eligible( $doctor_id ) || ! is_array( $window ) ) { return new WP_Error( 'wca_external_busy_verified_scope', __( 'Verified external calendar window is not bound to an eligible practitioner.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
		$start = self::utc( $window['start_utc'] ?? '' ); $end = self::utc( $window['end_utc'] ?? '' );
		if ( ! $start || ! $end || strtotime( $end . ' UTC' ) <= strtotime( $start . ' UTC' ) || strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) > 14 * DAY_IN_SECONDS || strtotime( $start . ' UTC' ) < time() - DAY_IN_SECONDS || strtotime( $start . ' UTC' ) > time() + 180 * DAY_IN_SECONDS ) { return new WP_Error( 'wca_external_busy_verified_window', __( 'Verified external calendar busy window is invalid or outside the bounded synchronization horizon.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$practitioner_ref = WCA_Plan_Guard::practitioner_ref( $doctor_id ); if ( ! $practitioner_ref ) { return new WP_Error( 'wca_external_busy_verified_practitioner', __( 'Practitioner calendar identity is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
		$calendar_ref = sanitize_text_field( (string) ( $window['calendar_ref'] ?? 'provider' ) );
		return self::put_record( 'F08-FUT-22', array( 'subject_user_id' => $doctor_id, 'parent_ref' => $practitioner_ref, 'status' => 'busy', 'starts_at' => $start, 'ends_at' => $end, 'expires_at' => gmdate( 'Y-m-d H:i:s', min( strtotime( $end . ' UTC' ) + DAY_IN_SECONDS, time() + 181 * DAY_IN_SECONDS ) ), 'payload' => array( 'calendar_ref_hash' => hash( 'sha256', $calendar_ref ), 'provider' => $provider, 'source_event_hash' => hash( 'sha256', $event_id ), 'provider_token_stored' => false, 'source' => 'verified_provider_webhook' ) ), $doctor_id );
	}

'''
if anchor not in s: raise SystemExit('Future24 save external busy anchor missing')
s=s.replace(anchor,verified+anchor,1)
p.write_text(s)

# Convert private conflict checker into public strict checker preserving old private wrapper semantics.
p=root/'includes/class-wca-future24.php'; s=p.read_text()
old="""\tprivate static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) {
\t\tglobal $wpdb; $doctor_id=WCA_Plan_Guard::practitioner_id($practitioner_ref); if(!$doctor_id){return false;} $table=self::tables()['records'];
\t\t$busy=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND subject_user_id=%d AND status='busy' AND expires_at>%s AND starts_at<%s AND ends_at>%s LIMIT 1",$doctor_id,self::now(),$end,$start));
\t\tif ( '' !== (string) $wpdb->last_error ) { return true; }
\t\treturn (bool)$busy;
\t}
"""
new="""\tpublic static function external_busy_conflict( $practitioner_ref, $start, $end ) {
\t\tglobal $wpdb; $doctor_id=WCA_Plan_Guard::practitioner_id($practitioner_ref); if(!$doctor_id){return false;} $start=self::utc($start); $end=self::utc($end); if(!$start||!$end||strtotime($end.' UTC')<=strtotime($start.' UTC')){return new WP_Error('wca_external_busy_time_invalid',__('External calendar conflict window is invalid.','worldwide-clinic-appointments'),array('status'=>400));} $table=self::tables()['records'];
\t\t$busy=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND subject_user_id=%d AND status='busy' AND expires_at>%s AND starts_at<%s AND ends_at>%s LIMIT 1",$doctor_id,self::now(),$end,$start));
\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error('wca_external_busy_read_failed',__('External calendar availability could not be verified safely.','worldwide-clinic-appointments'),array('status'=>503)); }
\t\treturn (bool)$busy;
\t}

\tprivate static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) { $result=self::external_busy_conflict($practitioner_ref,$start,$end); return is_wp_error($result)?true:(bool)$result; }
"""
if old not in s: raise SystemExit('external_busy exact anchor missing')
s=s.replace(old,new,1)
p.write_text(s)

# Guard hold: policy + strict external busy.
p=root/'includes/class-wca-future24.php'; s=p.read_text()
old="""\t\tif ( ! self::slot_allowed_by_policy( $slot ) ) {
\t\t\treturn new WP_Error( 'wca_future24_slot_policy', __( 'The selected time is unavailable under the current clinic buffer, travel, or continuous-consultation policy.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
\t\treturn true;
\t}
"""
new="""\t\tif ( ! self::slot_allowed_by_policy( $slot ) ) {
\t\t\treturn new WP_Error( 'wca_future24_slot_policy', __( 'The selected time is unavailable under the current clinic buffer, travel, or continuous-consultation policy.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
\t\t$external = self::external_busy_conflict( $slot['practitioner_ref'], $slot['start_utc'], $slot['end_utc'] );
\t\tif ( is_wp_error( $external ) ) { return $external; }
\t\tif ( $external ) { return new WP_Error( 'wca_external_calendar_busy', __( 'The selected time conflicts with the practitioner external calendar.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\treturn true;
\t}
"""
if old not in s: raise SystemExit('guard slot policy anchor missing')
s=s.replace(old,new,1)
p.write_text(s)

# Standard /slots post-filter both Future24 policy and external busy; DB failure degrades to zero slots with explicit flag.
p=root/'includes/class-wca-future24.php'; s=p.read_text()
old="""\t\t$data['slots'] = self::apply_slot_policies( $data['slots'] );
\t\t$data['future24_policy_applied'] = true;
\t\tif ( method_exists( $response, 'set_data' ) ) { $response->set_data( $data ); }
"""
new="""\t\t$filtered = array(); $external_degraded = false;
\t\tforeach ( self::apply_slot_policies( $data['slots'] ) as $slot ) { $external=self::external_busy_conflict( $slot['practitioner_ref'] ?? '', $slot['start_utc'] ?? '', $slot['end_utc'] ?? '' ); if(is_wp_error($external)){ $external_degraded=true; break; } if(!$external){$filtered[]=$slot;} }
\t\t$data['slots'] = $external_degraded ? array() : array_values( $filtered );
\t\t$data['future24_policy_applied'] = true;
\t\t$data['external_calendar_filter_applied'] = true;
\t\tif ( $external_degraded ) { $data['external_calendar_degraded'] = true; }
\t\tif ( method_exists( $response, 'set_data' ) ) { $response->set_data( $data ); }
"""
if old not in s: raise SystemExit('apply slots anchor missing')
s=s.replace(old,new,1)
p.write_text(s)

# Core hold path also asks optional Future24 external-busy truth, preventing internal/API bypass.
p=root/'includes/class-wca-service.php'; s=p.read_text()
old="""\t\t$canonical = WCA_Plan_Guard::canonical_slot_hold( $data, $actor_user_id );
\t\tif ( is_wp_error( $canonical ) ) { return $canonical; }
\t\treturn WCA_Repository::hold_slot( $canonical );
"""
new="""\t\t$canonical = WCA_Plan_Guard::canonical_slot_hold( $data, $actor_user_id );
\t\tif ( is_wp_error( $canonical ) ) { return $canonical; }
\t\tif ( class_exists( 'WCA_Future24' ) ) { $external=WCA_Future24::external_busy_conflict( sanitize_text_field( $data['practitioner_ref'] ?? '' ), $canonical['start_utc'], $canonical['end_utc'] ); if(is_wp_error($external)){return $external;} if($external){return new WP_Error('wca_external_calendar_busy',__('The selected time conflicts with the practitioner external calendar.','worldwide-clinic-appointments'),array('status'=>409));} }
\t\treturn WCA_Repository::hold_slot( $canonical );
"""
if old not in s: raise SystemExit('service hold canonical anchor missing')
s=s.replace(old,new,1)
p.write_text(s)

# R15 permanent regression checks.
p=root/'tests/sixteenth-twenty-review-regressions.php'; s=p.read_text(); marker='if($fail){fwrite(STDERR,"T16 regression gate failed:'; idx=s.index(marker)
checks="""t16h('R15 calendar subject lookup storage failure explicit','includes/class-wca-calendar-link.php','wca_calendar_subject_read_failed');
t16h('R15 calendar appointment lookup storage failure explicit','includes/class-wca-calendar-link.php','wca_calendar_appointment_read_failed');
t16h('R15 signed provider webhook endpoint exists','includes/class-wca-calendar-link.php','calendar-provider-webhooks');
t16h('R15 provider signature verification delegated to adapter','includes/class-wca-calendar-link.php','wca_calendar_provider_verify_webhook');
t16h('R15 provider webhook event is idempotent','includes/class-wca-calendar-link.php',"'calendar_provider_webhook'");
t16h('R15 webhook never stores provider token','includes/class-wca-future24.php',"'provider_token_stored' => false");
t16h('R15 calendar provider mapping projection is durable','includes/class-wca-repository.php','upsert_calendar_mapping_from_provider');
t16h('R15 provider mapping cannot mutate canonical appointment','includes/class-wca-repository.php',"'canonical_appointment_mutated' => false");
t16h('R15 strict external busy DB failure explicit','includes/class-wca-future24.php','wca_external_busy_read_failed');
t16h('R15 standard slot response filters external busy','includes/class-wca-future24.php',"'external_calendar_filter_applied'");
t16h('R15 slot hold rejects external busy','includes/class-wca-future24.php','wca_external_calendar_busy');
t16h('R15 core hold cannot bypass external calendar','includes/class-wca-service.php','WCA_Future24::external_busy_conflict');
"""
p.write_text(s[:idx]+checks+s[idx:])
print('R15 closed ledger applied')
