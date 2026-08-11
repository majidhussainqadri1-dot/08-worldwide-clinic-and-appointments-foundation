from pathlib import Path
import sys

ROOT = Path.cwd()

def read(rel):
    return (ROOT / rel).read_text(encoding='utf-8')

def write(rel, text):
    (ROOT / rel).write_text(text, encoding='utf-8')

def must_replace(rel, old, new, count=1):
    s = read(rel)
    actual = s.count(old)
    if actual < count:
        raise SystemExit(f'{rel}: expected at least {count} occurrence(s), found {actual}: {old[:120]!r}')
    write(rel, s.replace(old, new, count))

def round1():
    must_replace('includes/class-wca-future24.php',
        "\t\t$service = WCA_Repository::get_service( $service_ref, true );",
        "\t\t$service = WCA_Repository::get_service_by_ref( $service_ref, true );")

def round2():
    rel='includes/class-wca-authorization.php'
    s=read(rel)
    marker="\tpublic static function has_active_clinic_delegation( $user_id = 0 ) {"
    if 'function doctor_can_serve_clinic' in s:
        raise SystemExit('doctor_can_serve_clinic already exists unexpectedly')
    helper="""\t/** Return whether a doctor has a current File 08 serving relationship with a clinic. */
\tpublic static function doctor_can_serve_clinic( $clinic, $doctor_user_id, $actor_user_id = 0 ) {
\t\t$clinic = is_array( $clinic ) ? $clinic : WCA_Repository::get_clinic( absint( $clinic ), false );
\t\t$doctor_user_id = absint( $doctor_user_id );
\t\t$actor_user_id  = absint( $actor_user_id );
\t\tif ( ! $clinic || ! $doctor_user_id ) { return false; }
\t\t$clinic_id = absint( $clinic['id'] ?? 0 );
\t\tif ( ! $clinic_id ) { return false; }
\t\tif ( $doctor_user_id === absint( $clinic['owner_user_id'] ?? 0 ) ) { return true; }
\t\t$delegated = array_merge(
\t\t\tself::delegated_clinic_ids( $doctor_user_id, 'schedule' ),
\t\t\tself::delegated_clinic_ids( $doctor_user_id, 'clinic_manage' )
\t\t);
\t\t$allowed = in_array( $clinic_id, array_map( 'absint', $delegated ), true );
\t\treturn (bool) apply_filters( 'wca_doctor_may_serve_clinic', $allowed, $doctor_user_id, $clinic_id, $actor_user_id );
\t}

"""
    if marker not in s: raise SystemExit(f'{rel}: marker missing')
    write(rel,s.replace(marker,helper+marker,1))

    rel='includes/class-wca-service.php'; s=read(rel)
    start="\t/** A globally eligible doctor still requires current authority to serve this clinic. */"
    end="\n\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function save_service"
    a=s.find(start); b=s.find(end,a)
    if a<0 or b<0: raise SystemExit(f'{rel}: doctor helper section missing')
    new="""\t/** A globally eligible doctor still requires a current clinic-serving relationship. */
\tprivate static function doctor_may_serve_clinic( $clinic, $doctor_id, $actor_user_id ) {
\t\treturn WCA_Authorization::doctor_can_serve_clinic( $clinic, $doctor_id, $actor_user_id );
\t}
"""
    write(rel,s[:a]+new+s[b:])

    rel='includes/class-wca-plan-guard.php'; s=read(rel)
    needle="""\t\tif ( absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) ) {
\t\t\treturn new WP_Error( 'wca_slot_scope', __( 'The selected service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
"""
    insert=needle+"""\t\tif ( ! WCA_Authorization::doctor_can_serve_clinic( $clinic, $doctor_id ) ) {
\t\t\treturn new WP_Error( 'wca_slot_doctor_scope', __( 'The selected practitioner no longer has authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
"""
    if needle not in s: raise SystemExit(f'{rel}: public slot scope marker missing')
    s=s.replace(needle,insert,1)
    needle2="""\t\tif ( ! $clinic || ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) || ! SWC_Doctor_Authority::is_eligible( absint( $hold['doctor_user_id'] ?? 0 ) ) ) {
\t\t\treturn new WP_Error( 'wca_hold_scope', __( 'The clinic, service, or practitioner is no longer eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
"""
    insert2=needle2+"""\t\tif ( ! WCA_Authorization::doctor_can_serve_clinic( $clinic, absint( $hold['doctor_user_id'] ?? 0 ) ) ) {
\t\t\treturn new WP_Error( 'wca_hold_doctor_scope', __( 'The slot practitioner no longer has authority to serve this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
"""
    if needle2 not in s: raise SystemExit(f'{rel}: hold scope marker missing')
    write(rel,s.replace(needle2,insert2,1))

def _add_semantic_helpers(s):
    marker="\t/** Generic operational record writer. Never stores clinical narrative. */\n\tprivate static function put_record"
    helper="""\t/** Acquire a short MySQL advisory lock for semantic de-duplication across different replay keys. */
\tprivate static function semantic_lock( $scope, $identity ) {
\t\tglobal $wpdb;
\t\t$lock = 'wca-f24-' . sanitize_key( $scope ) . '-' . substr( hash( 'sha256', (string) $identity ), 0, 32 );
\t\tif ( 1 !== (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 3)', $lock ) ) ) {
\t\t\treturn new WP_Error( 'wca_future24_busy', __( 'This scheduling operation is already being updated. Try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
\t\treturn $lock;
\t}

\tprivate static function release_semantic_lock( $lock ) {
\t\tglobal $wpdb;
\t\tif ( is_string( $lock ) && '' !== $lock ) { $wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) ); }
\t}

"""
    if marker not in s: raise SystemExit('future24 put_record marker missing')
    return s.replace(marker,helper+marker,1)

def round3():
    rel='includes/class-wca-future24.php'; s=read(rel)
    if 'function semantic_lock' in s: raise SystemExit('semantic lock unexpectedly already present')
    s=_add_semantic_helpers(s)
    a=s.find("\tpublic static function arrive( $appointment_ref, $actor = 0 ) {")
    b=s.find("\n\t/* FUT-16 */",a)
    if a<0 or b<0: raise SystemExit('arrival section missing')
    new="""\tpublic static function arrive( $appointment_ref, $actor = 0 ) {
\t\tglobal $wpdb;
\t\t$actor_id = absint( $actor ?: get_current_user_id() );
\t\t$id = self::require_appointment( $appointment_ref, $actor_id );
\t\tif ( is_wp_error( $id ) ) { return $id; }
\t\t$who = WCA_Authorization::appointment_actor( $id, $actor_id );
\t\tif ( ! in_array( $who, array( 'patient','guardian' ), true ) ) { return new WP_Error( 'wca_arrival_actor', __( 'Only the patient or verified guardian may announce arrival.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) ); }
\t\tif ( ! in_array( SWC_Helpers::status( $id ), array( 'confirmed','reschedule_pending' ), true ) ) {
\t\t\treturn new WP_Error( 'wca_arrival_state', __( 'Arrival may only be announced for a confirmed or pending-reschedule appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
\t\t$start = self::utc( SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) );
\t\t$end = self::utc( SWC_Helpers::meta( $id, 'appointment_end_utc', '' ) );
\t\tif ( ! $start || ! $end ) { return new WP_Error( 'wca_arrival_time', __( 'The appointment time is unavailable.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t$now = time();
\t\tif ( $now < strtotime( $start . ' UTC' ) - 4 * HOUR_IN_SECONDS || $now > strtotime( $end . ' UTC' ) + 6 * HOUR_IN_SECONDS ) {
\t\t\treturn new WP_Error( 'wca_arrival_window', __( 'Arrival may only be announced near the scheduled appointment time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}
\t\t$lock = self::semantic_lock( 'arrival', $id . '|' . $actor_id );
\t\tif ( is_wp_error( $lock ) ) { return $lock; }
\t\ttry {
\t\t\t$table = self::tables()['records'];
\t\t\t$existing = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-15' AND appointment_id=%d AND subject_user_id=%d AND status='arrived' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\", $id, $actor_id, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\tif ( $existing ) { return self::public_record( $existing ); }
\t\t\treturn self::put_record( 'F08-FUT-15', array(
\t\t\t\t'clinic_id' => absint( SWC_Helpers::meta( $id, 'clinic_id', 0 ) ),
\t\t\t\t'appointment_id' => $id,
\t\t\t\t'subject_user_id' => $actor_id,
\t\t\t\t'status' => 'arrived',
\t\t\t\t'starts_at' => WCA_Repository::now(),
\t\t\t\t'expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( $end . ' UTC' ) + 6 * HOUR_IN_SECONDS ),
\t\t\t\t'payload' => array( 'queue_token' => substr( hash( 'sha256', strtolower( $appointment_ref ) . '|' . wp_salt( 'nonce' ) ), 0, 12 ), 'clinical_checkin' => false, 'operational_signal_only' => true ),
\t\t\t), $actor_id );
\t\t} finally {
\t\t\tself::release_semantic_lock( $lock );
\t\t}
\t}
"""
    write(rel,s[:a]+new+s[b:])

def round4():
    rel='includes/class-wca-future24.php'; s=read(rel)
    a=s.find("\tpublic static function request_virtual_room( $appointment_ref, $actor = 0 ) {")
    b=s.find("\n\tprivate static function subject_user_id",a)
    if a<0 or b<0: raise SystemExit('virtual-room section missing')
    new="""\tpublic static function request_virtual_room( $appointment_ref, $actor = 0 ) {
\t\tglobal $wpdb;
\t\t$actor_id=absint($actor?:get_current_user_id()); $id=self::require_appointment($appointment_ref,$actor_id); if(is_wp_error($id)){return $id;}
\t\tif(!in_array(SWC_Helpers::status($id),array('confirmed','reschedule_pending','checked_in'),true)){return new WP_Error('wca_virtual_room_state',__('A virtual room requires a confirmed, pending-reschedule, or checked-in appointment.','worldwide-clinic-appointments'),array('status'=>409));}
\t\t$type=sanitize_key(SWC_Helpers::meta($id,'consultation_type','')); if(!in_array($type,array('online','hybrid'),true)){return new WP_Error('wca_virtual_room_mode',__('A virtual room is only available for online or hybrid appointments.','worldwide-clinic-appointments'),array('status'=>409));}
\t\t$consent=class_exists('WCA_Continuity_Guards')?WCA_Continuity_Guards::consent_state($appointment_ref,$actor_id):new WP_Error('wca_virtual_room_consent_state',__('Current teleconsult consent could not be verified.','worldwide-clinic-appointments'));
\t\tif(is_wp_error($consent)||empty($consent['scopes']['teleconsult'])||'granted'!==$consent['scopes']['teleconsult']['status']){return new WP_Error('wca_virtual_room_consent',__('Current teleconsult consent is required before requesting a virtual room.','worldwide-clinic-appointments'),array('status'=>409));}
\t\t$lock=self::semantic_lock('virtual-room',$id);
\t\tif(is_wp_error($lock)){return $lock;}
\t\ttry {
\t\t\t$table=self::tables()['records'];
\t\t\t$existing=$wpdb->get_row($wpdb->prepare(\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-19' AND appointment_id=%d AND status='room_requested' AND (expires_at IS NULL OR expires_at>%s) ORDER BY id DESC LIMIT 1\",$id,WCA_Repository::now()),ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\tif($existing){return self::public_record($existing);}
\t\t\t$record=self::put_record('F08-FUT-19',array('appointment_id'=>$id,'clinic_id'=>absint(SWC_Helpers::meta($id,'clinic_id',0)),'subject_user_id'=>$actor_id,'status'=>'room_requested','expires_at'=>gmdate('Y-m-d H:i:s',time()+HOUR_IN_SECONDS),'payload'=>array('appointment_ref'=>strtolower($appointment_ref),'transport_owner'=>'File17','recording_assumed'=>false,'teleconsult_consent_verified'=>true,'idempotent_request'=>true)),$actor_id);
\t\t\tif(!is_wp_error($record)){WCA_Repository::enqueue('File17.VirtualRoomRequested.v1',strtolower($appointment_ref),array('appointment_ref'=>strtolower($appointment_ref),'request_ref'=>$record['public_ref'],'recording_allowed'=>false,'teleconsult_consent_verified'=>true),WCA_Observability::trace_id());}
\t\t\treturn $record;
\t\t} finally {
\t\t\tself::release_semantic_lock($lock);
\t\t}
\t}
"""
    write(rel,s[:a]+new+s[b:])

def round5():
    rel='includes/class-wca-future24.php'; s=read(rel)
    a=s.find("\tpublic static function family_hub( $actor = 0 ) {")
    b=s.find("\n\t/* FUT-15 */",a)
    if a<0 or b<0: raise SystemExit('family hub section missing')
    new="""\tpublic static function family_hub( $actor = 0 ) {
\t\t$actor = absint( $actor ?: get_current_user_id() );
\t\t$claims = WCA_Authorization::claims( $actor ); if ( is_wp_error( $claims ) ) { return $claims; }
\t\tif ( empty( $claims['guardian'] ) ) { return array( 'contract' => 'wca.family-hub', 'version' => self::CONTRACT_VERSION, 'guardian' => false, 'appointments' => array() ); }
\t\t$out = array();
\t\t$page = 1;
\t\t$batch = 200;
\t\tdo {
\t\t\t$q = new WP_Query( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'fields' => 'ids', 'posts_per_page' => $batch, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC', 'no_found_rows' => true, 'meta_key' => '_swc_guardian_user_id', 'meta_value' => $actor ) );
\t\t\t$ids = array_map( 'absint', (array) $q->posts );
\t\t\tforeach ( $ids as $id ) {
\t\t\t\t$patient_id = absint( SWC_Helpers::meta( $id, 'patient_user_id', get_post_field( 'post_author', $id ) ) );
\t\t\t\t$guard = class_exists( 'WCA_Central_Governance' ) ? WCA_Central_Governance::validate_patient_guardian( $patient_id, $actor, $actor ) : new WP_Error( 'wca_guardian_recheck_unavailable', 'unavailable' );
\t\t\t\tif ( is_wp_error( $guard ) ) { continue; }
\t\t\t\t$out[] = array( 'appointment_ref' => self::appointment_ref( $id ), 'status' => SWC_Helpers::status( $id ), 'scheduled_at_utc' => (string) SWC_Helpers::meta( $id, 'preferred_at_utc', '' ) );
\t\t\t}
\t\t\t$page++;
\t\t} while ( count( $ids ) === $batch );
\t\treturn array( 'contract' => 'wca.family-hub', 'version' => self::CONTRACT_VERSION, 'guardian' => true, 'appointments' => $out, 'relationship_recheck' => 'performed_for_each_returned_appointment', 'pagination_complete' => true );
\t}
"""
    write(rel,s[:a]+new+s[b:])

def round6():
    rel='includes/class-wca-future24.php'; s=read(rel)
    old="foreach(self::clinic_appointments_between($clinic_id,$effective_start,$end,1000) as $appointment_id){"
    if old not in s: raise SystemExit('disruption 1000 cap marker missing')
    s=s.replace(old,"foreach(self::clinic_appointments_between_all($clinic_id,$effective_start,$end) as $appointment_id){",1)
    marker="\tprivate static function clinic_appointments_between( $clinic_id, $from, $to, $limit ) {"
    helper="""\t/** Return the complete affected appointment set in bounded pages; no silent fixed-count truncation. */
\tprivate static function clinic_appointments_between_all( $clinic_id, $from, $to ) {
\t\t$out = array();
\t\t$page = 1;
\t\t$batch = 200;
\t\tdo {
\t\t\t$q = new WP_Query( array(
\t\t\t\t'post_type' => SWC_Helpers::TYPE,
\t\t\t\t'post_status' => array( 'private','publish' ),
\t\t\t\t'fields' => 'ids',
\t\t\t\t'posts_per_page' => $batch,
\t\t\t\t'paged' => $page,
\t\t\t\t'orderby' => 'ID',
\t\t\t\t'order' => 'ASC',
\t\t\t\t'no_found_rows' => true,
\t\t\t\t'meta_query' => array(
\t\t\t\t\tarray( 'key' => '_swc_clinic_id', 'value' => absint( $clinic_id ), 'compare' => '=' ),
\t\t\t\t\tarray( 'key' => '_swc_preferred_at_utc', 'value' => array( self::utc( $from ), self::utc( $to ) ), 'compare' => 'BETWEEN', 'type' => 'DATETIME' ),
\t\t\t\t),
\t\t\t) );
\t\t\t$ids = array_map( 'absint', (array) $q->posts );
\t\t\t$out = array_merge( $out, $ids );
\t\t\t$page++;
\t\t} while ( count( $ids ) === $batch );
\t\treturn array_values( array_unique( $out ) );
\t}

"""
    if marker not in s: raise SystemExit('appointment-between helper marker missing')
    write(rel,s.replace(marker,helper+marker,1))

def round7():
    rel='includes/class-wca-future24.php'; s=read(rel)
    a=s.find("\tprivate static function utc( $value ) {")
    b=s.find("\n\tprivate static function audit",a)
    if a<0 or b<0: raise SystemExit('future24 utc section missing')
    new="""\tprivate static function utc( $value ) {
\t\t$value = trim( sanitize_text_field( (string) $value ) );
\t\tif ( '' === $value ) { return null; }
\t\t$utc = new DateTimeZone( 'UTC' );
\t\t$formats = array(
\t\t\tarray( '!Y-m-d H:i:s', 'Y-m-d H:i:s', $utc ),
\t\t\tarray( '!Y-m-d H:i', 'Y-m-d H:i', $utc ),
\t\t\tarray( '!Y-m-d\\TH:i:s\\Z', 'Y-m-d\\TH:i:s\\Z', $utc ),
\t\t\tarray( '!Y-m-d\\TH:i\\Z', 'Y-m-d\\TH:i\\Z', $utc ),
\t\t\tarray( '!Y-m-d\\TH:i:sP', 'Y-m-d\\TH:i:sP', None ),
\t\t\tarray( '!Y-m-d\\TH:iP', 'Y-m-d\\TH:iP', None ),
\t\t);
\t\tforeach ( $formats as $entry ) {
\t\t\t$dt = null === $entry[2] ? DateTimeImmutable::createFromFormat( $entry[0], $value ) : DateTimeImmutable::createFromFormat( $entry[0], $value, $entry[2] );
\t\t\t$errors = DateTimeImmutable::getLastErrors();
\t\t\tif ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) {
\t\t\t\treturn $dt->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
\t\t\t}
\t\t}
\t\treturn null;
\t}
""".replace('None','null')
    s=s[:a]+new+s[b:]
    old="if ( ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $date_from ) || ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $date_to ) || $date_from < $today || $date_to < $date_from || $date_to > $max_date ) {"
    if old not in s: raise SystemExit('waitlist date marker missing')
    s=s.replace(old,"if ( ! WCA_Service::valid_date( $date_from ) || ! WCA_Service::valid_date( $date_to ) || $date_from < $today || $date_to < $date_from || $date_to > $max_date ) {",1)
    write(rel,s)

    rel='includes/class-wca-second-ten-review-hardening.php'; s=read(rel)
    old="\t\tif ( $depth > 7 || ! is_array( $value ) ) { return true; }"
    if old not in s: raise SystemExit('calendar depth marker missing')
    s=s.replace(old,"\t\tif ( ! is_array( $value ) ) { return true; }\n\t\tif ( $depth > 7 ) { return new WP_Error( 'wca_future24_payload_depth', __( 'Future24 calendar payload nesting is too deep.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }",1)
    old="if ( self::is_date_key( $key_string ) && preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $text ) && ! WCA_Service::valid_date( $text ) ) {"
    if old not in s: raise SystemExit('calendar date marker missing')
    s=s.replace(old,"if ( self::is_date_key( $key_string ) && ! WCA_Service::valid_date( $text ) ) {",1)
    old="\t\tif ( $depth > 8 || ! is_array( $value ) ) { return $value; }"
    if old not in s: raise SystemExit('public payload depth marker missing')
    s=s.replace(old,"\t\tif ( ! is_array( $value ) ) { return $value; }\n\t\tif ( $depth > 8 ) { return array(); }",1)
    write(rel,s)

def round8():
    rel='includes/class-wca-future24.php'; s=read(rel)
    fn=s.find("\tprivate static function slot_allowed_by_policy( $slot ) {")
    start=s.find("\t\t$q = new WP_Query( array(\n\t\t\t'post_type' => SWC_Helpers::TYPE,",fn)
    end=s.find("\n\t\treturn ! $maximum || $near < $maximum;",start)
    if start<0 or end<0: raise SystemExit('slot policy query section missing')
    new="""\t\t$near = 0;
\t\t$slot_branch = strtolower( sanitize_text_field( isset( $slot['branch_ref'] ) ? $slot['branch_ref'] : '' ) );
\t\t$page = 1;
\t\t$batch = 200;
\t\tdo {
\t\t\t$q = new WP_Query( array(
\t\t\t\t'post_type' => SWC_Helpers::TYPE,
\t\t\t\t'post_status' => array( 'private', 'publish' ),
\t\t\t\t'fields' => 'ids',
\t\t\t\t'posts_per_page' => $batch,
\t\t\t\t'paged' => $page,
\t\t\t\t'orderby' => 'ID',
\t\t\t\t'order' => 'ASC',
\t\t\t\t'no_found_rows' => true,
\t\t\t\t'meta_query' => array(
\t\t\t\t\tarray( 'key' => '_swc_doctor_id', 'value' => $doctor_id, 'compare' => '=' ),
\t\t\t\t\tarray( 'key' => '_swc_preferred_at_utc', 'value' => array( $day_start, $day_end ), 'compare' => 'BETWEEN', 'type' => 'DATETIME' ),
\t\t\t\t),
\t\t\t) );
\t\t\t$ids = array_map( 'absint', (array) $q->posts );
\t\t\tforeach ( $ids as $appointment_id ) {
\t\t\t\t$status = SWC_Helpers::status( $appointment_id );
\t\t\t\tif ( in_array( $status, array( 'declined','cancelled','no_show' ), true ) ) { continue; }
\t\t\t\t$a_start = self::utc( SWC_Helpers::meta( $appointment_id, 'preferred_at_utc', '' ) );
\t\t\t\t$a_end = self::utc( SWC_Helpers::meta( $appointment_id, 'appointment_end_utc', '' ) );
\t\t\t\tif ( ! $a_start || ! $a_end ) { continue; }
\t\t\t\tif ( $maximum && abs( strtotime( $a_start . ' UTC' ) - strtotime( $start . ' UTC' ) ) <= 6 * HOUR_IN_SECONDS ) { $near++; }
\t\t\t\t$gap_before = strtotime( $start . ' UTC' ) - strtotime( $a_end . ' UTC' );
\t\t\t\t$gap_after = strtotime( $a_start . ' UTC' ) - strtotime( $end . ' UTC' );
\t\t\t\tif ( $before && $gap_before >= 0 && $gap_before < $before * MINUTE_IN_SECONDS ) { return false; }
\t\t\t\tif ( $after && $gap_after >= 0 && $gap_after < $after * MINUTE_IN_SECONDS ) { return false; }
\t\t\t\tif ( $travel ) {
\t\t\t\t\t$branch_id = absint( SWC_Helpers::meta( $appointment_id, 'branch_id', 0 ) );
\t\t\t\t\t$branches_table = WCA_Schema::tables()['branches'];
\t\t\t\t\t$appointment_branch = $branch_id ? strtolower( (string) $wpdb->get_var( $wpdb->prepare( \"SELECT public_ref FROM {$branches_table} WHERE id=%d LIMIT 1\", $branch_id ) ) ) : ''; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\t\tif ( $appointment_branch && $slot_branch && ! hash_equals( $appointment_branch, $slot_branch ) ) {
\t\t\t\t\t\tif ( ( $gap_before >= 0 && $gap_before < $travel * MINUTE_IN_SECONDS ) || ( $gap_after >= 0 && $gap_after < $travel * MINUTE_IN_SECONDS ) ) { return false; }
\t\t\t\t\t}
\t\t\t\t}
\t\t\t}
\t\t\t$page++;
\t\t} while ( count( $ids ) === $batch );
"""
    write(rel,s[:start]+new+s[end:])

def round9():
    must_replace('worldwide-clinic.php',' * Version: 1.2.4',' * Version: 1.2.5')
    must_replace('worldwide-clinic.php',"define( 'WCA_VERSION', '1.2.4' );","define( 'WCA_VERSION', '1.2.5' );")
    must_replace('includes/class-wca-contracts.php',"const RUNTIME_VERSION                 = '1.2.4';","const RUNTIME_VERSION                 = '1.2.5';")

    tests=ROOT/'tests'
    for path in tests.glob('*.php'):
        txt=path.read_text(encoding='utf-8')
        if '1.2.4' in txt:
            path.write_text(txt.replace('1.2.4','1.2.5'),encoding='utf-8')

    rel='README.md'; s=read(rel)
    s=s.replace('Runtime candidate: **1.2.4**','Runtime candidate: **1.2.5**',1)
    fifth="""The **fifth fresh 10-round corrective audit** closes a further set of canonical-root and scale/concurrency gaps: Future24 service references now resolve through the public-ref repository path; doctor-to-clinic serving authority is actor-independent and rechecked at public slot/hold booking edges; arrival and virtual-room semantic de-duplication is serialized across distinct replay keys; guardian-family and disruption affected sets are fully paged; Future24 UTC/date parsing fails closed at the canonical root; nested calendar/DTO depth no longer fails open; and slot buffer/travel/continuous-consultation policy scans are no longer silently capped at 100 appointments.\n\n"""
    marker='## Canonical routes\n'
    if marker not in s: raise SystemExit('README canonical routes marker missing')
    s=s.replace(marker,fifth+marker,1)
    write(rel,s)

    rel='STATUS.md'; s=read(rel)
    s=s.replace('Runtime candidate: **1.2.4**','Runtime candidate: **1.2.5**',1)
    start='## Fourth fresh 10-round corrective audit\n'
    end='## Evidence-state classification\n'
    section="""## Fifth fresh 10-round corrective audit

A fifth fresh sequential 10-round review-and-correct cycle was run against the corrected v1.2.4 repository state. Findings were corrected before the next round proceeded. The cycle hardens:

- Future24 public service-reference resolution at every clinic-scoped capability;
- actor-independent current doctor-to-clinic serving authority, including public slot search and held-slot booking rechecks;
- cross-key semantic concurrency for patient arrival and File17 virtual-room requests;
- complete paged guardian-family and disruption affected-set traversal;
- strict canonical Future24 UTC/date parsing and fail-closed nested calendar/DTO depth handling;
- complete paged slot buffer/travel/continuous-consultation policy evaluation;
- release/test/document identity for runtime candidate 1.2.5 while retaining core schema 3.2.0, continuity schema 1.1.0, and Future24 schema 1.0.0.

Supported public/cross-file mutation paths remain authorization-, idempotency-, rate-, scope-, consent-, and state-guarded. Repository evidence still does not prove Hostinger staging or live state.

"""
    a=s.find(start); b=s.find(end,a)
    if a<0 or b<0: raise SystemExit('STATUS review section markers missing')
    s=s[:a]+section+s[b:]
    s=s.replace('**Corrected candidate** — fourth-cycle source corrections are present.','**Corrected candidate** — fifth-cycle source corrections are present.')
    write(rel,s)

    rel='readme.txt'; s=read(rel)
    s=s.replace('Stable tag: 1.2.4','Stable tag: 1.2.5',1)
    s=s.replace('Version 1.2.4 implements','Version 1.2.5 implements',1)
    fifth_desc="""\nThe fifth fresh corrective audit fixes Future24 public service-reference resolution, actor-independent doctor-to-clinic serving authority and held-slot rechecks, cross-key semantic concurrency for arrival/virtual-room requests, complete paged guardian/disruption/policy scans, and strict fail-closed Future24 calendar parsing/depth handling.\n"""
    platform_marker='\nPlatform commission is always 0%.'
    if platform_marker not in s: raise SystemExit('readme platform marker missing')
    s=s.replace(platform_marker,fifth_desc+platform_marker,1)
    changelog_marker='\n= 1.2.4 =\n'
    newlog="""\n= 1.2.5 =
* Completed a fifth fresh sequential 10-round corrective audit on the v1.2.4 exact repository state.
* Corrected Future24 clinic-scoped public service-reference resolution.
* Made current doctor-to-clinic serving authority actor-independent and rechecked it at slot search and held-slot booking boundaries.
* Serialized semantic arrival and virtual-room de-duplication across distinct idempotency keys.
* Removed silent 100/1000-record truncation from guardian family, disruption affected-set and slot-policy evaluation paths through bounded pagination.
* Replaced permissive Future24 root timestamp parsing with strict round-trip parsing; impossible waitlist dates and excessive nested calendar/DTO payloads now fail closed.
* Runtime is 1.2.5; core schema remains 3.2.0; continuity schema remains 1.1.0; Future24 schema remains 1.0.0.
* Repository/CI/package evidence remains distinct from staging/live evidence.
"""
    if changelog_marker not in s: raise SystemExit('readme 1.2.4 changelog marker missing')
    s=s.replace(changelog_marker,newlog+changelog_marker,1)
    write(rel,s)

    rel='CHANGELOG.md'; s=read(rel)
    marker='## 1.2.4 — 2026-08-11\n'
    new="""## 1.2.5 — 2026-08-11

- Completed a fifth fresh sequential 10-round review-and-correct cycle against exact v1.2.4 repository state.
- Corrected Future24 public service-reference lookup and actor-independent doctor-to-clinic serving authority, including slot/hold rechecks.
- Added semantic MySQL advisory locks for arrival and virtual-room de-duplication across different replay keys.
- Removed fixed-count truncation from guardian-family, disruption affected-set, and slot-policy evaluation by bounded paging.
- Replaced permissive Future24 canonical timestamp parsing with strict round-trip parsing; tightened waitlist dates and nested REST calendar/DTO fail-closed depth behavior.
- Advanced runtime identity to 1.2.5 without schema inflation: core 3.2.0, continuity 1.1.0, Future24 1.0.0.
- Added a permanent fifth-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.

"""
    if marker not in s: raise SystemExit('CHANGELOG v1.2.4 marker missing')
    s=s.replace(marker,new+marker,1)
    write(rel,s)

    path=ROOT/'STAGING-ACCEPTANCE.md'
    if path.exists():
        txt=path.read_text(encoding='utf-8')
        path.write_text(txt.replace('1.2.4','1.2.5'),encoding='utf-8')

    test=r'''<?php
/** File 08 fifth fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t510src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t510has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t510lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$bootstrap=t510src('worldwide-clinic.php');
$contracts=t510src('includes/class-wca-contracts.php');
$future=t510src('includes/class-wca-future24.php');
$auth=t510src('includes/class-wca-authorization.php');
$service=t510src('includes/class-wca-service.php');
$guard=t510src('includes/class-wca-plan-guard.php');
$hardening=t510src('includes/class-wca-second-ten-review-hardening.php');
$readme=t510src('readme.txt');
$repo_readme=t510src('README.md');
$status=t510src('STATUS.md');
$changelog=t510src('CHANGELOG.md');

t510has('service public-ref resolver',$future,'WCA_Repository::get_service_by_ref( $service_ref, true )');
t510lacks('no numeric resolver on service ref',$future,'WCA_Repository::get_service( $service_ref, true )');
t510has('doctor clinic authority helper',$auth,'function doctor_can_serve_clinic');
t510has('doctor delegated schedule',$auth,"delegated_clinic_ids( $doctor_user_id, 'schedule' )");
t510has('doctor delegated manage',$auth,"delegated_clinic_ids( $doctor_user_id, 'clinic_manage' )");
t510has('service canonical helper',$service,'WCA_Authorization::doctor_can_serve_clinic');
t510lacks('no global-admin assignment bypass',$service,"user_can( $actor_user_id, 'manage_worldwide_clinic' ) || $doctor_id === $actor_user_id");
t510has('slot-search doctor scope',$guard,'wca_slot_doctor_scope');
t510has('held-slot doctor scope',$guard,'wca_hold_doctor_scope');
t510has('semantic lock helper',$future,'function semantic_lock');
t510has('semantic release helper',$future,'function release_semantic_lock');
t510has('arrival semantic lock',$future,"semantic_lock( 'arrival'");
t510has('arrival finally release',$future,'self::release_semantic_lock( $lock )');
t510has('virtual room semantic lock',$future,"semantic_lock('virtual-room'");
t510has('virtual room file17 event',$future,'File17.VirtualRoomRequested.v1');
t510has('family paged traversal',$future,"'posts_per_page' => $batch, 'paged' => $page");
t510has('family completion marker',$future,"'pagination_complete' => true");
t510lacks('no family hard 100',$future,"'posts_per_page' => 100, 'no_found_rows' => true, 'meta_key' => '_swc_guardian_user_id'");
t510has('disruption all helper',$future,'function clinic_appointments_between_all');
t510has('disruption uses all helper',$future,'clinic_appointments_between_all($clinic_id,$effective_start,$end)');
t510lacks('no disruption hard 1000',$future,'clinic_appointments_between($clinic_id,$effective_start,$end,1000)');
t510has('canonical strict datetime',$future,'DateTimeImmutable::createFromFormat');
t510lacks('canonical no permissive strtotime parser',$future,"$ts = strtotime( $value");
t510has('waitlist valid date from',$future,'WCA_Service::valid_date( $date_from )');
t510has('waitlist valid date to',$future,'WCA_Service::valid_date( $date_to )');
t510has('calendar depth error',$hardening,'wca_future24_payload_depth');
t510has('date key strict validity',$hardening,'self::is_date_key( $key_string ) && ! WCA_Service::valid_date( $text )');
t510has('deep public payload drops',$hardening,'if ( $depth > 8 ) { return array(); }');
t510has('slot policy batch',$future,'$batch = 200;');
t510has('slot policy page',$future,"'paged' => $page");
t510has('slot policy stable order',$future,"'orderby' => 'ID'");
t510has('plugin 1.2.5',$bootstrap,'Version: 1.2.5');
t510has('runtime 1.2.5',$contracts,"RUNTIME_VERSION                 = '1.2.5'");
t510has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
t510has('readme stable 1.2.5',$readme,'Stable tag: 1.2.5');
t510has('repository readme 1.2.5',$repo_readme,'Runtime candidate: **1.2.5**');
t510has('status 1.2.5',$status,'Runtime candidate: **1.2.5**');
t510has('changelog 1.2.5',$changelog,'## 1.2.5 — 2026-08-11');
t510has('zero commission',$contracts,"'commission_percent' => 0");
t510has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t510has('no automated prescribing',$contracts,"'automated_prescribing' => false");
$runtime=implode("\n",array($bootstrap,$contracts,$future,$auth,$service,$guard,$hardening));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t510lacks('forbidden runtime primitive',$runtime,$token); }
if($failures){ fwrite(STDERR,"File 08 fifth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'File 08 fifth fresh ten-round regression assertions passed: ' . $checks . '/' . $checks . ".\n";
'''
    write('tests/fifth-ten-review-regressions.php',test)
    rel='tests/run-all.php'; s=read(rel)
    marker="'fourth-ten-review-regressions.php'"
    if marker not in s: raise SystemExit('run-all fourth test marker missing')
    s=s.replace(marker,marker+", 'fifth-ten-review-regressions.php'",1)
    write(rel,s)

def round10():
    pass

rounds={1:round1,2:round2,3:round3,4:round4,5:round5,6:round6,7:round7,8:round8,9:round9,10:round10}
if len(sys.argv)!=2 or int(sys.argv[1]) not in rounds:
    raise SystemExit('usage: patcher.py <1..10>')
r=int(sys.argv[1]); rounds[r](); print(f'round {r} patch complete')
