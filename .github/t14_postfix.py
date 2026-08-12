from pathlib import Path
import re

root=Path('.')

def rd(p): return (root/p).read_text()
def wr(p,s): (root/p).write_text(s)

# Post-main correction A/B: make FUT-08 rule projection timezone/DST/effective-range aware and suppress low-volume outcome aggregates.
p='includes/class-wca-future24.php'
s=rd(p)
start=s.index('\t/* FUT-08 */\n\tpublic static function heatmap(')
end=s.index('\n\t/* FUT-09 */', start)
new_heatmap=r'''\t/* FUT-08 */
\tpublic static function heatmap( $clinic_id, $days = 30, $actor = 0 ) {
\t\tglobal $wpdb;
\t\t$clinic = self::require_clinic_manager( $clinic_id, $actor );
\t\tif ( is_wp_error( $clinic ) ) { return $clinic; }
\t\t$days = WCA_Service::strict_int( $days, 7, 90 );
\t\tif ( null === $days || ! in_array( $days, array( 7, 30, 90 ), true ) ) { return new WP_Error( 'wca_heatmap_window', __( 'Heatmap window must be exactly 7, 30, or 90 days.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }

\t\t$utc = new DateTimeZone( 'UTC' );
\t\t$window_start = new DateTimeImmutable( gmdate( 'Y-m-d 00:00:00' ), $utc );
\t\t$window_end = $window_start->modify( '+' . ( $days - 1 ) . ' days' )->setTime( 23, 59, 59 );
\t\t$from = $window_start->format( 'Y-m-d H:i:s' );
\t\t$to = $window_end->format( 'Y-m-d H:i:s' );
\t\t$ids = self::clinic_appointments_between_all( $clinic_id, $from, $to );

\t\t$map = array();
\t\tfor ( $i = 0; $i < $days; $i++ ) {
\t\t\t$date = $window_start->modify( '+' . $i . ' days' )->format( 'Y-m-d' );
\t\t\t$map[ $date ] = array(
\t\t\t\t'booked' => 0, 'requested' => 0, 'confirmed' => 0,
\t\t\t\t'completed' => 0, 'cancelled' => 0, 'no_show' => 0,
\t\t\t\t'configured_capacity' => 0, 'free_estimate' => 0,
\t\t\t\t'outcome_counts_suppressed' => false,
\t\t\t);
\t\t}

\t\t$rules_table = WCA_Schema::tables()['availability'];
\t\t$rules = $wpdb->get_results( $wpdb->prepare( "SELECT id,rrule_json,breaks_json,exceptions_json,capacity,status,timezone FROM {$rules_table} WHERE clinic_id=%d AND status='active' ORDER BY id ASC", absint( $clinic_id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( null === $rules ) { return new WP_Error( 'wca_heatmap_rules_query', __( 'Availability capacity could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }

\t\tforeach ( (array) $rules as $rule ) {
\t\t\t$timezone = (string) ( $rule['timezone'] ?? '' );
\t\t\tif ( ! WCA_Service::valid_timezone( $timezone ) ) { return new WP_Error( 'wca_heatmap_rule_timezone', __( 'An active availability rule contains an invalid time zone.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t$tz = new DateTimeZone( $timezone );
\t\t\t$rrule = json_decode( (string) ( $rule['rrule_json'] ?? '' ), true );
\t\t\t$breaks = json_decode( (string) ( $rule['breaks_json'] ?? '[]' ), true );
\t\t\t$exceptions = json_decode( (string) ( $rule['exceptions_json'] ?? '[]' ), true );
\t\t\tif ( ! is_array( $rrule ) || ! is_array( $breaks ) || ! is_array( $exceptions ) ) { return new WP_Error( 'wca_heatmap_rule_payload', __( 'An active availability rule cannot be projected safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t$start_h = (string) ( $rrule['start'] ?? '' );
\t\t\t$end_h = (string) ( $rrule['end'] ?? '' );
\t\t\t$interval = WCA_Service::strict_int( $rrule['interval_minutes'] ?? null, 10, 1440 );
\t\t\t$capacity = WCA_Service::strict_int( $rule['capacity'] ?? null, 1, 50 );
\t\t\tif ( ! WCA_Service::valid_hhmm( $start_h ) || ! WCA_Service::valid_hhmm( $end_h ) || $end_h <= $start_h || null === $interval || null === $capacity ) { return new WP_Error( 'wca_heatmap_rule_invalid', __( 'An active availability rule contains invalid scheduling values.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t$effective_from = (string) ( $rrule['effective_from'] ?? '' );
\t\t\t$effective_until = (string) ( $rrule['effective_until'] ?? '' );
\t\t\tif ( $effective_from && ! WCA_Service::valid_date( $effective_from ) ) { return new WP_Error( 'wca_heatmap_effective_from', __( 'Availability effective-from data is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\tif ( $effective_until && ! WCA_Service::valid_date( $effective_until ) ) { return new WP_Error( 'wca_heatmap_effective_until', __( 'Availability effective-until data is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }

\t\t\t$local_first = $window_start->setTimezone( $tz )->modify( '-1 day' )->format( 'Y-m-d' );
\t\t\t$local_last = $window_end->setTimezone( $tz )->modify( '+1 day' )->format( 'Y-m-d' );
\t\t\t$cursor_date = new DateTimeImmutable( $local_first . ' 00:00:00', $tz );
\t\t\t$last_date = new DateTimeImmutable( $local_last . ' 00:00:00', $tz );
\t\t\twhile ( $cursor_date <= $last_date ) {
\t\t\t\t$local_date = $cursor_date->format( 'Y-m-d' );
\t\t\t\tif ( ( $effective_from && $local_date < $effective_from ) || ( $effective_until && $local_date > $effective_until ) ) { $cursor_date=$cursor_date->modify('+1 day'); continue; }
\t\t\t\t$weekday = strtolower( $cursor_date->format( 'l' ) );
\t\t\t\tif ( ! in_array( $weekday, (array) ( $rrule['days'] ?? array() ), true ) ) { $cursor_date=$cursor_date->modify('+1 day'); continue; }

\t\t\t\t$day_start = $start_h; $day_end = $end_h; $day_capacity = $capacity; $closed = false;
\t\t\t\tforeach ( $exceptions as $exception ) {
\t\t\t\t\tif ( ! is_array( $exception ) || (string) ( $exception['date'] ?? '' ) !== $local_date ) { continue; }
\t\t\t\t\t$type = sanitize_key( $exception['type'] ?? '' );
\t\t\t\t\tif ( 'closed' === $type ) { $closed = true; break; }
\t\t\t\t\tif ( 'capacity' === $type ) { $override = WCA_Service::strict_int( $exception['capacity'] ?? null, 0, 50 ); if ( null !== $override ) { $day_capacity = $override; } }
\t\t\t\t\tif ( 'open' === $type && WCA_Service::valid_hhmm( $exception['start'] ?? '' ) && WCA_Service::valid_hhmm( $exception['end'] ?? '' ) && $exception['end'] > $exception['start'] ) { $day_start=(string)$exception['start']; $day_end=(string)$exception['end']; }
\t\t\t\t}
\t\t\t\tif ( $closed || 0 === $day_capacity ) { $cursor_date=$cursor_date->modify('+1 day'); continue; }

\t\t\t\t$slot = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $local_date . ' ' . $day_start, $tz );
\t\t\t\t$end = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $local_date . ' ' . $day_end, $tz );
\t\t\t\tif ( ! $slot || ! $end || $end <= $slot ) { return new WP_Error( 'wca_heatmap_local_time', __( 'Availability local-time projection failed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t\twhile ( $slot < $end ) {
\t\t\t\t\t$slot_hm = $slot->format( 'H:i' ); $in_break = false;
\t\t\t\t\tforeach ( $breaks as $break ) { if ( is_array( $break ) && WCA_Service::valid_hhmm( $break['start'] ?? '' ) && WCA_Service::valid_hhmm( $break['end'] ?? '' ) && $slot_hm >= $break['start'] && $slot_hm < $break['end'] ) { $in_break=true; break; } }
\t\t\t\t\tif ( ! $in_break ) { $utc_day = $slot->setTimezone( $utc )->format( 'Y-m-d' ); if ( isset( $map[$utc_day] ) ) { $map[$utc_day]['configured_capacity'] += $day_capacity; } }
\t\t\t\t\t$slot = $slot->modify( '+' . $interval . ' minutes' );
\t\t\t\t}
\t\t\t\t$cursor_date = $cursor_date->modify( '+1 day' );
\t\t\t}
\t\t}

\t\tforeach ( $ids as $id ) {
\t\t\t$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' );
\t\t\t$day = substr( $when, 0, 10 );
\t\t\tif ( ! isset( $map[ $day ] ) ) { continue; }
\t\t\t$status = SWC_Helpers::status( $id );
\t\t\tif ( ! in_array( $status, array( 'declined','cancelled','no_show' ), true ) ) { $map[ $day ]['booked']++; }
\t\t\tif ( isset( $map[ $day ][ $status ] ) ) { $map[ $day ][ $status ]++; }
\t\t}
\t\t$privacy_threshold = max( 3, min( 20, absint( apply_filters( 'wca_heatmap_outcome_privacy_threshold', 5, $clinic_id ) ) ) );
\t\tforeach ( $map as $date => $row ) {
\t\t\t$map[ $date ]['free_estimate'] = max( 0, $row['configured_capacity'] - $row['booked'] );
\t\t\t$outcome_n = absint( $row['completed'] ) + absint( $row['cancelled'] ) + absint( $row['no_show'] );
\t\t\tif ( $outcome_n < $privacy_threshold ) { $map[$date]['completed']=null; $map[$date]['cancelled']=null; $map[$date]['no_show']=null; $map[$date]['outcome_counts_suppressed']=true; }
\t\t}
\t\treturn array(
\t\t\t'contract' => 'wca.capacity-heatmap',
\t\t\t'version' => self::CONTRACT_VERSION,
\t\t\t'clinic_ref' => (string) $clinic['public_ref'],
\t\t\t'days' => $map,
\t\t\t'privacy' => 'aggregate_only',
\t\t\t'outcome_privacy_threshold' => $privacy_threshold,
\t\t\t'time_basis' => 'UTC day; configured capacity is projected from each rule local timezone with DST and effective-range handling',
\t\t\t'projection_note' => 'Configured capacity is an operational estimate; current slot search remains authoritative.',
\t\t);
\t}
'''
s=s[:start]+new_heatmap+s[end:]
wr(p,s)

# Post-main correction C: deterministic, signed, stateless opaque clinic cursor so stable pages have stable ETags.
p='includes/class-wca-rest.php'; s=rd(p)
start=s.index('\tpublic static function clinics( WP_REST_Request $request ) {')
end=s.index('\n\tpublic static function clinic( WP_REST_Request $request ) {',start)
new_cursor=r'''\tprivate static function encode_clinic_cursor( $state ) {
\t\t$json = wp_json_encode( $state );
\t\tif ( ! is_string( $json ) || '' === $json ) { return ''; }
\t\t$payload = rtrim( strtr( base64_encode( $json ), '+/', '-_' ), '=' );
\t\t$signature = hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) );
\t\treturn $payload . '.' . $signature;
\t}

\tprivate static function decode_clinic_cursor( $cursor, $filter_hash ) {
\t\t$cursor = trim( (string) $cursor );
\t\tif ( ! preg_match( '/^([A-Za-z0-9_-]+)\\.([0-9a-f]{64})$/', $cursor, $matches ) ) { return new WP_Error( 'wca_cursor_invalid', __( 'The clinic cursor is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\t$expected = hash_hmac( 'sha256', $matches[1], wp_salt( 'nonce' ) );
\t\tif ( ! hash_equals( $expected, $matches[2] ) ) { return new WP_Error( 'wca_cursor_invalid', __( 'The clinic cursor signature is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\t$encoded = strtr( $matches[1], '-_', '+/' );
\t\t$padding = strlen( $encoded ) % 4; if ( $padding ) { $encoded .= str_repeat( '=', 4 - $padding ); }
\t\t$json = base64_decode( $encoded, true );
\t\t$state = is_string( $json ) ? json_decode( $json, true ) : null;
\t\tif ( ! is_array( $state ) || 1 !== absint( $state['v'] ?? 0 ) || ! hash_equals( (string) ( $state['f'] ?? '' ), (string) $filter_hash ) || empty( $state['u'] ) || empty( $state['i'] ) ) { return new WP_Error( 'wca_cursor_invalid', __( 'The clinic cursor does not match this query.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\treturn array( 'updated_at' => sanitize_text_field( $state['u'] ), 'id' => absint( $state['i'] ) );
\t}

\tpublic static function clinics( WP_REST_Request $request ) {
\t\t$rate = self::rate_limit( 'public_clinics', 120, 60 );
\t\tif ( is_wp_error( $rate ) ) { return $rate; }
\t\t$args = array(
\t\t\t'status'       => 'active',
\t\t\t'country_code' => sanitize_text_field( $request->get_param( 'country' ) ),
\t\t\t'city'         => sanitize_text_field( $request->get_param( 'city' ) ),
\t\t\t'search'       => sanitize_text_field( $request->get_param( 'search' ) ),
\t\t\t'per_page'     => min( 50, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) ),
\t\t);
\t\t$filter_hash = hash( 'sha256', wp_json_encode( array( $args['country_code'], $args['city'], $args['search'], $args['per_page'] ) ) );
\t\t$cursor = sanitize_text_field( (string) $request->get_param( 'cursor' ) );
\t\tif ( $cursor ) {
\t\t\t$state = self::decode_clinic_cursor( $cursor, $filter_hash );
\t\t\tif ( is_wp_error( $state ) ) { return $state; }
\t\t\t$args['cursor_updated_at'] = $state['updated_at'];
\t\t\t$args['cursor_id'] = $state['id'];
\t\t}
\t\t$rows = WCA_Repository::list_clinics( $args );
\t\t$items = array();
\t\tforeach ( $rows as $row ) { $projection = WCA_Service::public_clinic_projection( $row['public_ref'] ); if ( $projection ) { $items[] = $projection; } }
\t\t$next_cursor = '';
\t\tif ( count( $rows ) === $args['per_page'] ) {
\t\t\t$last = end( $rows );
\t\t\tif ( is_array( $last ) && ! empty( $last['id'] ) && ! empty( $last['updated_at'] ) ) {
\t\t\t\t$next_cursor = self::encode_clinic_cursor( array( 'v'=>1, 'f'=>$filter_hash, 'u'=>(string)$last['updated_at'], 'i'=>absint($last['id']) ) );
\t\t\t\tif ( '' === $next_cursor ) { return new WP_Error( 'wca_cursor_encode_failed', __( 'Clinic pagination cursor could not be generated safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
\t\t\t}
\t\t}
\t\t$payload = array( 'items' => $items, 'per_page' => $args['per_page'], 'next_cursor' => $next_cursor, 'generated_at' => gmdate( 'c' ) );
\t\t$etag = '"' . hash( 'sha256', wp_json_encode( array( $filter_hash, array_map( static function ( $item ) { return array( $item['public_ref'] ?? '', $item['updated_at'] ?? '', $item['record_version'] ?? 0 ); }, $items ), $next_cursor ) ) ) . '"';
\t\tif ( hash_equals( $etag, (string) $request->get_header( 'If-None-Match' ) ) ) { $response = new WP_REST_Response( null, 304 ); } else { $response = rest_ensure_response( $payload ); }
\t\t$response->header( 'ETag', $etag );
\t\t$response->header( 'Cache-Control', 'public, max-age=60, stale-while-revalidate=120' );
\t\t$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
\t\treturn $response;
\t}
'''
s=s[:start]+new_cursor+s[end:]
wr(p,s)

# Extend permanent regression gate with post-main findings.
p='tests/fourteenth-twenty-review-regressions.php'; s=rd(p)
needle="if($fail){fwrite(STDERR,\"File 08 fourteenth twenty-round regression gate failed:"
idx=s.index(needle)
extra=r'''t14has('post-main heatmap timezone projection','includes/class-wca-future24.php',"'time_basis' => 'UTC day; configured capacity is projected from each rule local timezone with DST and effective-range handling'");
t14has('post-main heatmap effective range','includes/class-wca-future24.php',"$effective_from && $local_date < $effective_from");
t14has('post-main low-volume suppression','includes/class-wca-future24.php','wca_heatmap_outcome_privacy_threshold');
t14has('post-main signed stateless cursor','includes/class-wca-rest.php',"hash_hmac( 'sha256', $payload, wp_salt( 'nonce' ) )");
t14has('post-main cursor filter binding','includes/class-wca-rest.php',"'f'=>$filter_hash");
t14lacks('post-main no transient cursor state','includes/class-wca-rest.php',"set_transient( 'wca_clinic_cursor_'");
'''
s=s[:idx]+extra+s[idx:]
wr(p,s)

# Permanent evidence note.
p='FOURTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p)
note='''\n## Post-main corrective sweep\nAfter the 20 main rounds had each passed its corrected-state QA, a broader fresh sweep found three additional repository defects: FUT-08 configured capacity used UTC weekday arithmetic instead of rule-local timezone/DST/effective-range projection; low-volume granular outcome aggregates had no explicit suppression; and public clinic pagination generated random transient cursors, causing unchanged pages to receive unstable ETags and avoid conditional-cache hits. These were corrected without renumbering the completed 20 main rounds. Because source changed after R20, the required two fresh post-coding reviews must restart and pass before closure.\n'''
if '## Post-main corrective sweep' not in s: s+=note
wr(p,s)
print('Applied T14 post-main heatmap/privacy/cursor corrections')
