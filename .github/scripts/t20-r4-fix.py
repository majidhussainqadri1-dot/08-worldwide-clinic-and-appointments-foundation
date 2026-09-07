from pathlib import Path
ROOT=Path(__file__).resolve().parents[2]
def read(p): return (ROOT/p).read_text()
def write(p,s): (ROOT/p).write_text(s)
def repl(p,old,new,count=1):
    s=read(p)
    if s.count(old)<count: raise SystemExit(f'{p}: missing patch anchor')
    write(p,s.replace(old,new,count))

# R4-D2: a pending proposal may be safely replaced instead of dead-ending after expiry.
p='includes/class-wca-contracts.php'; s=read(p)
s=s.replace("'reschedule_pending' => array( 'confirmed', 'cancelled' ),", "'reschedule_pending' => array( 'confirmed', 'reschedule_pending', 'cancelled' ),", 2)
s=s.replace("'reschedule_pending' => array( 'confirmed', 'declined', 'cancelled' ),", "'reschedule_pending' => array( 'confirmed', 'reschedule_pending', 'declined', 'cancelled' ),", 3)
write(p,s)

# R4-D1: release one unbooked proposal hold explicitly.
p='includes/class-wca-repository.php'; s=read(p)
needle="\tpublic static function release_appointment_slot( $appointment_id, $status = 'released', $except_hold_token = '' ) {\n"
insert="""\t/** Release one unbooked hold without touching an appointment's booked slot. */
\tpublic static function release_slot_hold( $hold_token, $status = 'released' ) {
\t\tglobal $wpdb;
\t\t$table = WCA_Schema::tables()['slot_holds'];
\t\t$status = in_array( $status, array( 'released', 'expired' ), true ) ? $status : 'released';
\t\t$updated = $wpdb->query( $wpdb->prepare(
\t\t\t\"UPDATE {$table} SET status=%s,updated_at=%s WHERE hold_token=%s AND status='held' AND appointment_id=0\",
\t\t\t$status, self::now(), sanitize_text_field( $hold_token )
\t\t) );
\t\tif ( false === $updated ) {
\t\t\treturn new WP_Error( 'wca_hold_release_failed', __( 'The slot hold could not be released safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t\t}
\t\treturn true;
\t}

"""
if needle not in s: raise SystemExit('repository anchor missing')
write(p,s.replace(needle,insert+needle,1))

# R4-D3: appointment-scoped hold path lets an authorized doctor/staff actor propose time without arbitrary patient authority.
p='includes/class-wca-service.php'; s=read(p)
needle="\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function request_appointment( $data, $actor_user_id = 0 ) {\n"
method="""\t/** @return array<string,mixed>|WP_Error */
\tpublic static function hold_reschedule_slot( $appointment_id, $data, $actor_user_id = 0 ) {
\t\t$appointment_id = self::strict_id( $appointment_id );
\t\t$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
\t\t$data = is_array( $data ) ? $data : array();
\t\tif ( null === $appointment_id || ! $actor_user_id ) { return new WP_Error( 'wca_reschedule_hold_identity', __( 'A valid appointment and actor are required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
\t\t$current = SWC_Helpers::status_strict( $appointment_id );
\t\tif ( is_wp_error( $current ) ) { return $current; }
\t\t$auth = WCA_Authorization::can_transition_appointment( $appointment_id, 'reschedule_pending', $actor_user_id );
\t\tif ( is_wp_error( $auth ) ) { return $auth; }
\t\t$actor = WCA_Authorization::appointment_actor( $appointment_id, $actor_user_id );
\t\tif ( ! WCA_Contracts::can_transition( $actor, $current, 'reschedule_pending' ) ) { return new WP_Error( 'wca_reschedule_hold_state', __( 'A replacement slot cannot be proposed in the current appointment state.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t$patient_user_id = absint( SWC_Helpers::meta( $appointment_id, 'patient_user_id', get_post_field( 'post_author', $appointment_id ) ) );
\t\t$data['patient_user_id'] = $patient_user_id;
\t\t$canonical = WCA_Plan_Guard::canonical_slot_hold( $data, $patient_user_id );
\t\tif ( is_wp_error( $canonical ) ) { return $canonical; }
\t\tif ( absint( $canonical['doctor_user_id'] ) !== absint( SWC_Helpers::meta( $appointment_id, 'doctor_id' ) ) || absint( $canonical['clinic_id'] ) !== absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) ) || absint( $canonical['service_id'] ) !== absint( SWC_Helpers::meta( $appointment_id, 'service_id' ) ) ) { return new WP_Error( 'wca_reschedule_hold_scope', __( 'The replacement slot does not belong to this appointment.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\tif ( class_exists( 'WCA_Future24' ) ) {
\t\t\t$external = WCA_Future24::external_busy_conflict( sanitize_text_field( $data['practitioner_ref'] ?? '' ), $canonical['start_utc'], $canonical['end_utc'] );
\t\t\tif ( is_wp_error( $external ) ) { return $external; }
\t\t\tif ( $external ) { return new WP_Error( 'wca_external_calendar_busy', __( 'The selected time conflicts with the practitioner external calendar.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t}
\t\treturn WCA_Repository::hold_slot( $canonical );
\t}

"""
if needle not in s: raise SystemExit('service method anchor missing')
s=s.replace(needle,method+needle,1)
old="""\t\t\t\t$hold_check = WCA_Plan_Guard::validate_reschedule_hold( $hold, $appointment_id, $actor_user_id );
\t\t\t\tif ( is_wp_error( $hold_check ) ) { return $hold_check; }
\t\t\t\tforeach ( array(
"""
new="""\t\t\t\t$hold_check = WCA_Plan_Guard::validate_reschedule_hold( $hold, $appointment_id, $actor_user_id );
\t\t\t\tif ( is_wp_error( $hold_check ) ) { return $hold_check; }
\t\t\t\tif ( 'reschedule_pending' === $current ) {
\t\t\t\t\t$previous_token = (string) SWC_Helpers::meta( $appointment_id, 'proposed_hold_token', '' );
\t\t\t\t\tif ( $previous_token && ! hash_equals( $previous_token, $hold_token ) ) {
\t\t\t\t\t\t$released_previous = WCA_Repository::release_slot_hold( $previous_token );
\t\t\t\t\t\tif ( is_wp_error( $released_previous ) ) { return $released_previous; }
\t\t\t\t\t}
\t\t\t\t}
\t\t\t\tforeach ( array(
"""
if old not in s: raise SystemExit('proposal anchor missing')
s=s.replace(old,new,1)
old="""\t\t\tif ( in_array( $next, array( 'cancelled','declined','no_show' ), true ) ) {
\t\t\t\t$released_slot = WCA_Repository::release_appointment_slot( $appointment_id );
"""
new="""\t\t\tif ( in_array( $next, array( 'cancelled','declined','no_show' ), true ) ) {
\t\t\t\tif ( 'reschedule_pending' === $current ) {
\t\t\t\t\t$proposal_token = (string) SWC_Helpers::meta( $appointment_id, 'proposed_hold_token', '' );
\t\t\t\t\tif ( $proposal_token ) {
\t\t\t\t\t\t$released_proposal = WCA_Repository::release_slot_hold( $proposal_token );
\t\t\t\t\t\tif ( is_wp_error( $released_proposal ) ) { return $released_proposal; }
\t\t\t\t\t}
\t\t\t\t\tforeach ( array( 'proposed_at_utc','proposed_end_utc','proposed_branch_id','proposed_hold_token','proposed_by_user_id','proposed_expires_at' ) as $proposal_key ) {
\t\t\t\t\t\t$deleted = SWC_Helpers::delete_meta_strict( $appointment_id, '_swc_' . $proposal_key, 'wca_terminal_reschedule_cleanup' );
\t\t\t\t\t\tif ( is_wp_error( $deleted ) ) { return $deleted; }
\t\t\t\t\t}
\t\t\t\t}
\t\t\t\t$released_slot = WCA_Repository::release_appointment_slot( $appointment_id );
"""
if old not in s: raise SystemExit('terminal anchor missing')
write(p,s.replace(old,new,1))

p='includes/class-wca-opaque-api.php'; s=read(p)
route="""\t\tregister_rest_route( 'wca/v1', '/appointment-refs/(?P<ref>[0-9a-fA-F-]{36})/transitions', array(
\t\t\t'methods'             => WP_REST_Server::CREATABLE,
\t\t\t'callback'            => array( __CLASS__, 'transition' ),
\t\t\t'permission_callback' => array( 'WCA_REST', 'authenticated' ),
\t\t) );
"""
add=route+"""\t\tregister_rest_route( 'wca/v1', '/appointment-refs/(?P<ref>[0-9a-fA-F-]{36})/reschedule-holds', array(
\t\t\t'methods'             => WP_REST_Server::CREATABLE,
\t\t\t'callback'            => array( __CLASS__, 'reschedule_hold' ),
\t\t\t'permission_callback' => array( 'WCA_REST', 'authenticated' ),
\t\t) );
"""
if route not in s: raise SystemExit('opaque route anchor missing')
s=s.replace(route,add,1)
needle="\tpublic static function calendar( WP_REST_Request $request ) {\n"
handler="""\tpublic static function reschedule_hold( WP_REST_Request $request ) {
\t\t$id = self::appointment_id( $request['ref'] );
\t\tif ( is_wp_error( $id ) ) { return $id; }
\t\tif ( ! $id ) { return self::not_found(); }
\t\treturn self::respond( WCA_Service::hold_reschedule_slot( $id, self::data( $request ), get_current_user_id() ), 201 );
\t}

"""
if needle not in s: raise SystemExit('opaque handler anchor missing')
write(p,s.replace(needle,handler+needle,1))

p='includes/class-wca-ten-review-hardening.php'; s=read(p)
old="'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents)$#',"
new="'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents|reschedule-holds)$#',"
if old not in s: raise SystemExit('hardening anchor missing')
write(p,s.replace(old,new,1))

# Permanent R4 regression.
test=ROOT/'tests/t20-r4-reschedule-lifecycle-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ ); $failures=array(); $checks=0;
function t20r4_check($name,$condition){global $failures,$checks;$checks++;if(!$condition){$failures[]=$name;}}
$contracts=file_get_contents($root.'/includes/class-wca-contracts.php'); $service=file_get_contents($root.'/includes/class-wca-service.php'); $repo=file_get_contents($root.'/includes/class-wca-repository.php'); $opaque=file_get_contents($root.'/includes/class-wca-opaque-api.php'); $hard=file_get_contents($root.'/includes/class-wca-ten-review-hardening.php');
foreach(array($contracts,$service,$repo,$opaque,$hard) as $source){t20r4_check('source readable',is_string($source));}
t20r4_check('pending proposal replace path',substr_count($contracts,"'reschedule_pending' => array( 'confirmed', 'reschedule_pending'")>=5);
t20r4_check('unbooked proposal release primitive',false!==strpos($repo,'function release_slot_hold')&&false!==strpos($repo,"status='held' AND appointment_id=0"));
t20r4_check('reproposal releases old hold',false!==strpos($service,'release_slot_hold( $previous_token )'));
t20r4_check('terminal transition releases proposal hold',false!==strpos($service,'release_slot_hold( $proposal_token )'));
t20r4_check('terminal proposal metadata cleanup',false!==strpos($service,'wca_terminal_reschedule_cleanup'));
t20r4_check('appointment-scoped reschedule hold service',false!==strpos($service,'function hold_reschedule_slot'));
t20r4_check('reschedule hold locked to appointment scope',false!==strpos($service,'wca_reschedule_hold_scope'));
t20r4_check('opaque reschedule hold route',false!==strpos($opaque,'/reschedule-holds')&&false!==strpos($opaque,'hold_reschedule_slot'));
t20r4_check('reschedule hold joins HTTP idempotency',false!==strpos($hard,'transitions|payment-intents|reschedule-holds'));
if($failures){fwrite(STDERR,"T20 R4 reschedule lifecycle regressions failed:\n- ".implode("\n- ",$failures)."\n");exit(1);} echo "T20 R4 reschedule lifecycle regressions: PASS {$checks}/{$checks}.\n";
''')

p='tests/run-all.php'; s=read(p)
old="'t20-r3-appointment-idempotency-header-regressions.php' );"
new="'t20-r3-appointment-idempotency-header-regressions.php', 't20-r4-reschedule-lifecycle-regressions.php' );"
if old not in s: raise SystemExit('run-all anchor missing')
write(p,s.replace(old,new,1))
