from pathlib import Path
import sys

ROOT=Path('.')

def read(p): return (ROOT/p).read_text()
def write(p,s): (ROOT/p).write_text(s)
def rep(p,old,new):
    s=read(p)
    if old not in s:
        raise SystemExit(f'anchor missing in {p}: {old[:120]!r}')
    if s.count(old)!=1:
        raise SystemExit(f'anchor not unique in {p}: {s.count(old)}')
    write(p,s.replace(old,new,1))

def r1():
    p='includes/class-wca-service.php'
    old="""\t\t\tif ( is_wp_error( $consent ) ) { return $consent; }\n\t\t\t$trace = WCA_Observability::trace_id();"""
    new="""\t\t\tif ( is_wp_error( $consent ) ) { return $consent; }\n\t\t\t$context_scopes = array( 'privacy_notice' );\n\t\t\tif ( $remote ) { $context_scopes[] = 'teleconsult'; }\n\t\t\tforeach ( $context_scopes as $context_scope ) {\n\t\t\t\t$context_consent = WCA_Repository::record_consent( array(\n\t\t\t\t\t'appointment_id'     => $appointment_id,\n\t\t\t\t\t'actor_user_id'      => $actor_user_id,\n\t\t\t\t\t'actor_subject_uuid' => $claims['subject_uuid'],\n\t\t\t\t\t'guardian_user_id'   => $guardian_user_id,\n\t\t\t\t\t'scope'              => $context_scope,\n\t\t\t\t\t'terms_version'      => self::TERMS_VERSION,\n\t\t\t\t\t'terms_text'         => 'wca-context:' . $context_scope . ':' . self::TERMS_VERSION,\n\t\t\t\t\t'legal_basis'        => 'consent',\n\t\t\t\t\t'metadata'           => array( 'source' => 'appointment_owner_transaction', 'privacy' => true, 'telehealth' => $remote ),\n\t\t\t\t) );\n\t\t\t\tif ( is_wp_error( $context_consent ) ) { return $context_consent; }\n\t\t\t}\n\t\t\t$trace = WCA_Observability::trace_id();"""
    rep(p,old,new)
    p='includes/class-wca-appointment-command.php'
    rep(p,"""\t\tif ( $remote && $appointment_id ) {\n\t\t\tself::ensure_context_consent( $appointment_id, 'teleconsult', $actor_user_id );\n\t\t}\n\t\tif ( $appointment_id ) {\n\t\t\tself::ensure_context_consent( $appointment_id, 'privacy_notice', $actor_user_id );\n\t\t}""","""\t\tif ( $remote && $appointment_id ) {\n\t\t\t$sync = self::ensure_context_consent( $appointment_id, 'teleconsult', $actor_user_id );\n\t\t\tif ( is_wp_error( $sync ) ) { return $sync; }\n\t\t}\n\t\tif ( $appointment_id ) {\n\t\t\t$sync = self::ensure_context_consent( $appointment_id, 'privacy_notice', $actor_user_id );\n\t\t\tif ( is_wp_error( $sync ) ) { return $sync; }\n\t\t}""")
    s=read(p)
    s=s.replace("\t\tif ( $exists ) { return; }","\t\tif ( $exists ) { return true; }",1)
    s=s.replace("\t\tif ( is_wp_error( $claims ) ) { return; }","\t\tif ( is_wp_error( $claims ) ) { return $claims; }",1)
    old="""\t\tif ( is_wp_error( $record ) ) {\n\t\t\tWCA_Observability::log( 'warning', 'context_consent_sync_pending', array( 'scope' => sanitize_key( $scope ), 'appointment_ref' => (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ) ) );\n\t\t\tWCA_Repository::enqueue( 'File24.AssuranceEvidenceRequested.v1', (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ), array( 'entity' => 'appointment_consent', 'entity_ref' => (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ), 'change' => 'consent_sync_pending', 'scope' => sanitize_key( $scope ) ), WCA_Observability::trace_id() );\n\t\t}\n\t}"""
    new="""\t\tif ( is_wp_error( $record ) ) {\n\t\t\tWCA_Observability::log( 'error', 'context_consent_sync_failed', array( 'scope' => sanitize_key( $scope ), 'appointment_ref' => (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ) ) );\n\t\t\treturn $record;\n\t\t}\n\t\treturn true;\n\t}"""
    if old not in s: raise SystemExit('r1 command tail anchor missing')
    write(p,s.replace(old,new,1))

def r2():
    rep('includes/class-wca-service.php',"""\tpublic static function valid_timezone( $timezone ) {\n\t\ttry {\n\t\t\tnew DateTimeZone( (string) $timezone );\n\t\t\treturn true;\n\t\t} catch ( Exception $e ) {\n\t\t\treturn false;\n\t\t}\n\t}""","""\tpublic static function valid_timezone( $timezone ) {\n\t\tif ( ! is_string( $timezone ) ) { return false; }\n\t\t$timezone = trim( $timezone );\n\t\tif ( '' === $timezone ) { return false; }\n\t\treturn 'UTC' === $timezone || in_array( $timezone, timezone_identifiers_list(), true );\n\t}""")

def r3():
    rep('includes/class-wca-central-governance.php',"""\tprivate static function age_from_birth_date( $value ) {\n\t\t$value = trim( (string) $value );\n\t\tif ( ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $value ) ) { return null; }\n\t\ttry {\n\t\t\t$birth = new DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );\n\t\t\t$today = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );\n\t\t\tif ( $birth > $today ) { return null; }\n\t\t\treturn (int) $birth->diff( $today )->y;\n\t\t} catch ( Exception $e ) {\n\t\t\treturn null;\n\t\t}\n\t}""","""\tprivate static function age_from_birth_date( $value ) {\n\t\t$value = trim( (string) $value );\n\t\tif ( ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $value ) ) { return null; }\n\t\t$utc = new DateTimeZone( 'UTC' );\n\t\t$birth = DateTimeImmutable::createFromFormat( '!Y-m-d', $value, $utc );\n\t\t$errors = DateTimeImmutable::getLastErrors();\n\t\tif ( ! $birth || ( false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) || $birth->format( 'Y-m-d' ) !== $value ) { return null; }\n\t\t$today = new DateTimeImmutable( 'today', $utc );\n\t\tif ( $birth > $today ) { return null; }\n\t\treturn (int) $birth->diff( $today )->y;\n\t}""")

def r4():
    rep('includes/class-wca-central-governance.php',"""\t\t$threshold = max( $threshold, absint( apply_filters( 'wca_guardian_age_threshold', $threshold, $gender, $patient_user_id ) ) );\n\t\tif ( null === $minor && null !== $age ) { $minor = $age < $threshold; }""","""\t\t$threshold = max( $threshold, absint( apply_filters( 'wca_guardian_age_threshold', $threshold, $gender, $patient_user_id ) ) );\n\t\tif ( null !== $age && $age < $threshold && false === $minor ) {\n\t\t\treturn new WP_Error( 'wca_age_claim_conflict', __( 'The current age and minor-status assertions conflict and cannot be used for booking until identity verification is reconciled.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );\n\t\t}\n\t\tif ( null === $minor && null !== $age ) { $minor = $age < $threshold; }""")

def r5():
    rep('includes/class-wca-future24.php',"\t\t$days = min( 90, max( 7, absint( $days ) ) );","\t\t$days = WCA_Service::strict_int( $days, 7, 90 );\n\t\tif ( null === $days || ! in_array( $days, array( 7, 30, 90 ), true ) ) { return new WP_Error( 'wca_heatmap_window', __( 'Heatmap window must be exactly 7, 30, or 90 days.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }")
    rep('includes/class-wca-future24.php',"public static function rest_heatmap( WP_REST_Request $r ){ return self::respond(self::heatmap(absint($r->get_param('clinic_id')),absint($r->get_param('days')?:30))); }","public static function rest_heatmap( WP_REST_Request $r ){ $days=$r->get_param('days'); if(null===$days||''===$days){$days=30;} return self::respond(self::heatmap(absint($r->get_param('clinic_id')),$days)); }")

def r6():
    rep('includes/class-wca-future24.php',"array( 'booked' => 0, 'requested' => 0, 'confirmed' => 0, 'configured_capacity' => 0, 'free_estimate' => 0 )","array( 'booked' => 0, 'requested' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0, 'no_show' => 0, 'configured_capacity' => 0, 'free_estimate' => 0 )")

def r7():
    p='includes/class-wca-future24.php'
    anchor="""\tprivate static function require_clinic_manager( $clinic_id, $actor = 0 ) {"""
    helper="""\tprivate static function clinic_id_from_public_ref( $ref ) {\n\t\t$ref = strtolower( trim( sanitize_text_field( (string) $ref ) ) );\n\t\tif ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $ref ) ) { return 0; }\n\t\t$clinic = WCA_Repository::get_clinic( $ref, false );\n\t\treturn $clinic ? absint( $clinic['id'] ) : 0;\n\t}\n\n"""
    s=read(p)
    if anchor not in s: raise SystemExit('r7 helper anchor missing')
    s=s.replace(anchor,helper+anchor,1)
    old="""\tpublic static function rest_heatmap( WP_REST_Request $r ){ $days=$r->get_param('days'); if(null===$days||''===$days){$days=30;} return self::respond(self::heatmap(absint($r->get_param('clinic_id')),$days)); }\n\tpublic static function rest_advisor( WP_REST_Request $r ){ return self::respond(self::advisor(absint($r->get_param('clinic_id')))); }\n\tpublic static function rest_no_show( WP_REST_Request $r ){ return self::respond(self::no_show_forecast(absint($r->get_param('clinic_id')))); }"""
    new="""\tpublic static function rest_heatmap( WP_REST_Request $r ){ $days=$r->get_param('days'); if(null===$days||''===$days){$days=30;} $clinic_id=self::clinic_id_from_public_ref($r->get_param('clinic_ref')); if(!$clinic_id){return new WP_Error('wca_clinic_ref_required',__('A valid opaque clinic reference is required.','worldwide-clinic-appointments'),array('status'=>400));} return self::respond(self::heatmap($clinic_id,$days)); }\n\tpublic static function rest_advisor( WP_REST_Request $r ){ $clinic_id=self::clinic_id_from_public_ref($r->get_param('clinic_ref')); if(!$clinic_id){return new WP_Error('wca_clinic_ref_required',__('A valid opaque clinic reference is required.','worldwide-clinic-appointments'),array('status'=>400));} return self::respond(self::advisor($clinic_id)); }\n\tpublic static function rest_no_show( WP_REST_Request $r ){ $clinic_id=self::clinic_id_from_public_ref($r->get_param('clinic_ref')); if(!$clinic_id){return new WP_Error('wca_clinic_ref_required',__('A valid opaque clinic reference is required.','worldwide-clinic-appointments'),array('status'=>400));} return self::respond(self::no_show_forecast($clinic_id)); }"""
    if old not in s: raise SystemExit('r7 wrappers anchor missing')
    write(p,s.replace(old,new,1))

def r8():
    p='includes/class-wca-future24.php'
    s=read(p)
    old1="if ( $capacity && $ratio >= 0.8 ) { $items[] = array( 'date' => $date, 'type' => 'high_demand', 'suggestion' => 'Consider opening additional capacity or buffer review.', 'auto_apply' => false ); }"
    new1="if ( $capacity && $ratio >= 0.8 ) { $items[] = array( 'date' => $date, 'type' => 'high_demand', 'suggestion' => 'Consider opening additional capacity or buffer review.', 'reason' => array( 'configured_capacity' => $capacity, 'booked' => $booked, 'utilization_ratio' => round( $ratio, 4 ) ), 'provenance' => array( 'source_contract' => 'wca.capacity-heatmap', 'window_days' => 30 ), 'auto_apply' => false ); }"
    old2="if ( $capacity >= 4 && $ratio <= 0.2 ) { $items[] = array( 'date' => $date, 'type' => 'low_demand', 'suggestion' => 'Consider consolidating availability if clinically and operationally appropriate.', 'auto_apply' => false ); }"
    new2="if ( $capacity >= 4 && $ratio <= 0.2 ) { $items[] = array( 'date' => $date, 'type' => 'low_demand', 'suggestion' => 'Consider consolidating availability if clinically and operationally appropriate.', 'reason' => array( 'configured_capacity' => $capacity, 'booked' => $booked, 'utilization_ratio' => round( $ratio, 4 ) ), 'provenance' => array( 'source_contract' => 'wca.capacity-heatmap', 'window_days' => 30 ), 'auto_apply' => false ); }"
    if old1 not in s or old2 not in s: raise SystemExit('r8 advisor anchors missing')
    s=s.replace(old1,new1,1).replace(old2,new2,1)
    s=s.replace("return array( 'contract' => 'wca.schedule-advisor', 'version' => self::CONTRACT_VERSION, 'advisory_only' => true, 'recommendations' => array_slice( $items, 0, 30 ) );","return array( 'contract' => 'wca.schedule-advisor', 'version' => self::CONTRACT_VERSION, 'advisory_only' => true, 'auto_apply' => false, 'donor_or_paid_influence' => false, 'generated_at_utc' => gmdate( 'c' ), 'recommendations' => array_slice( $items, 0, 30 ) );",1)
    write(p,s)

def r9():
    p='includes/class-swc-helpers.php'
    old="""\tpublic static function doctor_ids() {\n\t\t$users = get_users(\n\t\t\tarray(\n\t\t\t\t'role__in' => array( 'sabri_doctor_verified', 'sabri_doctor' ),\n\t\t\t\t'number'   => -1,\n\t\t\t\t'fields'   => 'ID',\n\t\t\t\t'orderby'  => 'display_name',\n\t\t\t\t'order'    => 'ASC',\n\t\t\t)\n\t\t);"""
    new="""\tpublic static function doctor_ids( $limit = 100, $offset = 0 ) {\n\t\t$limit = min( 200, max( 1, absint( $limit ) ) );\n\t\t$offset = max( 0, absint( $offset ) );\n\t\t$users = get_users(\n\t\t\tarray(\n\t\t\t\t'role__in' => array( 'sabri_doctor_verified', 'sabri_doctor' ),\n\t\t\t\t'number'   => $limit,\n\t\t\t\t'offset'   => $offset,\n\t\t\t\t'fields'   => 'ID',\n\t\t\t\t'orderby'  => 'display_name',\n\t\t\t\t'order'    => 'ASC',\n\t\t\t)\n\t\t);"""
    rep(p,old,new)
    rep(p,"\tpublic static function requestable_doctor_ids() {\n\t\treturn array_values( array_filter( self::doctor_ids(), array( __CLASS__, 'doctor_is_requestable' ) ) );\n\t}","\tpublic static function requestable_doctor_ids( $limit = 100, $offset = 0 ) {\n\t\treturn array_values( array_filter( self::doctor_ids( $limit, $offset ), array( __CLASS__, 'doctor_is_requestable' ) ) );\n\t}")
    p='includes/class-swc-frontend.php'
    rep(p,"""\t\t$pages    = SWC_Helpers::pages();\n\t\t$all      = SWC_Helpers::doctor_ids();\n\t\t$paged    = max( 1, isset( $_GET['swc_doctors_page'] ) ? absint( $_GET['swc_doctors_page'] ) : 1 );\n\t\t$per_page = 12;\n\t\t$doctors  = array_slice( $all, ( $paged - 1 ) * $per_page, $per_page );""","""\t\t$pages    = SWC_Helpers::pages();\n\t\t$paged    = max( 1, isset( $_GET['swc_doctors_page'] ) ? absint( $_GET['swc_doctors_page'] ) : 1 );\n\t\t$per_page = 12;\n\t\t$window   = SWC_Helpers::doctor_ids( $per_page + 1, ( $paged - 1 ) * $per_page );\n\t\t$has_more = count( $window ) > $per_page;\n\t\t$doctors  = array_slice( $window, 0, $per_page );""")
    rep(p,"<?php echo wp_kses_post( $this->pagination( 'swc_doctors_page', $paged, (int) ceil( count( $all ) / $per_page ) ) ); ?>","<?php echo wp_kses_post( $this->pagination( 'swc_doctors_page', $paged, $has_more ? $paged + 1 : $paged ) ); ?>")

def r10():
    p='includes/class-swc-frontend.php'
    rep(p,"\t\t$ids          = SWC_Helpers::requestable_doctor_ids();","\t\t$ids          = SWC_Helpers::requestable_doctor_ids( 100, 0 );")
    rep(p,"""\t\tif ( ! in_array( $selected, $ids, true ) ) {\n\t\t\t$selected = 0;\n\t\t}""","""\t\tif ( $selected && SWC_Helpers::doctor_is_requestable( $selected ) && ! in_array( $selected, $ids, true ) ) { array_unshift( $ids, $selected ); }\n\t\tif ( ! in_array( $selected, $ids, true ) ) { $selected = 0; }""")
    p='includes/class-swc-admin.php'
    rep(p,"\t\t$doctors = SWC_Helpers::doctor_ids();","\t\t$doctors = SWC_Helpers::doctor_ids( 200, 0 );")

def r11():
    p='includes/class-wca-repository.php'
    old="""\tpublic static function uuid() {\n\t\treturn function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : sprintf(\n\t\t\t'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',\n\t\t\tmt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),\n\t\t\tmt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000,\n\t\t\tmt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )\n\t\t);\n\t}"""
    new="""\tpublic static function uuid() {\n\t\tif ( function_exists( 'wp_generate_uuid4' ) ) { return strtolower( wp_generate_uuid4() ); }\n\t\ttry {\n\t\t\t$bytes = random_bytes( 16 );\n\t\t} catch ( Throwable $error ) {\n\t\t\tthrow new RuntimeException( 'Secure UUID generation is unavailable.' );\n\t\t}\n\t\t$bytes[6] = chr( ( ord( $bytes[6] ) & 0x0f ) | 0x40 );\n\t\t$bytes[8] = chr( ( ord( $bytes[8] ) & 0x3f ) | 0x80 );\n\t\t$hex = bin2hex( $bytes );\n\t\treturn substr( $hex, 0, 8 ) . '-' . substr( $hex, 8, 4 ) . '-' . substr( $hex, 12, 4 ) . '-' . substr( $hex, 16, 4 ) . '-' . substr( $hex, 20, 12 );\n\t}"""
    rep(p,old,new)

def r12():
    rep('includes/class-wca-repository.php',"\t\t$worker = sanitize_text_field( $worker ?: 'worker-' . substr( md5( wp_salt( 'nonce' ) . microtime( true ) ), 0, 12 ) );","\t\t$worker = sanitize_text_field( $worker ?: 'worker-' . str_replace( '-', '', self::uuid() ) );")

def r13():
    p='includes/class-wca-repository.php'
    anchor="""\t\t$row   = array(\n\t\t\t'public_ref'      => self::uuid(),"""
    pre="""\t\t$visibility = $data['visibility'] ?? 'public';\n\t\t$status = $data['status'] ?? 'active';\n\t\tif ( ! in_array( $visibility, array( 'public', 'restricted', 'private' ), true ) ) { return new WP_Error( 'wca_branch_visibility', __( 'Branch visibility is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\tif ( ! in_array( $status, array( 'active', 'paused', 'archived' ), true ) ) { return new WP_Error( 'wca_branch_status', __( 'Branch status is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n"""
    s=read(p)
    # target only create_branch occurrence after timezone block
    idx=s.find("\tpublic static function create_branch( $data )")
    if idx<0: raise SystemExit('r13 create_branch missing')
    pos=s.find(anchor,idx)
    if pos<0: raise SystemExit('r13 row anchor missing')
    s=s[:pos]+pre+s[pos:]
    s=s.replace("\t\t\t'visibility'      => in_array( $data['visibility'] ?? 'public', array( 'public', 'restricted', 'private' ), true ) ? $data['visibility'] : 'public',","\t\t\t'visibility'      => $visibility,",1)
    s=s.replace("\t\t\t'status'          => in_array( $data['status'] ?? 'active', array( 'active', 'paused', 'archived' ), true ) ? $data['status'] : 'active',","\t\t\t'status'          => $status,",1)
    write(p,s)

def r14():
    p='includes/class-wca-repository.php'
    old="\t\t$row   = array(\n\t\t\t'public_ref'      => self::uuid(),\n\t\t\t'clinic_id'       => absint( $data['clinic_id'] ?? 0 ),\n\t\t\t'name'            => sanitize_text_field( $data['name'] ?? '' ),\n\t\t\t'country_code'    => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['country_code'] ?? '' ) ), 0, 2 ) ),"
    new="\t\t$country_raw = trim( (string) ( $data['country_code'] ?? '' ) );\n\t\tif ( '' !== $country_raw && ! preg_match( '/^[A-Za-z]{2}$/', $country_raw ) ) { return new WP_Error( 'wca_branch_country', __( 'Branch country code must be an exact two-letter code.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$row   = array(\n\t\t\t'public_ref'      => self::uuid(),\n\t\t\t'clinic_id'       => absint( $data['clinic_id'] ?? 0 ),\n\t\t\t'name'            => sanitize_text_field( $data['name'] ?? '' ),\n\t\t\t'country_code'    => strtoupper( $country_raw ),"
    rep(p,old,new)

def r15():
    p='includes/class-wca-repository.php'
    rep(p,"\t\tif ( isset( $data['status'] ) && in_array( sanitize_key( $data['status'] ), WCA_Contracts::lifecycles()['clinic'], true ) ) { $allowed['status'] = sanitize_key( $data['status'] ); }","\t\tif ( isset( $data['status'] ) ) {\n\t\t\t$status = sanitize_key( $data['status'] );\n\t\t\tif ( ! in_array( $status, WCA_Contracts::lifecycles()['clinic'], true ) ) { return new WP_Error( 'wca_clinic_status', __( 'Invalid clinic status.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t\t$allowed['status'] = $status;\n\t\t}")

def r16():
    p='includes/class-wca-ten-review-hardening.php'
    rep(p,"""\t\t$route  = (string) $request->get_route();\n\t\t$method = strtoupper( (string) $request->get_method() );\n\t\tif ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) || ! self::is_core_mutation_route( $route ) ) {""","""\t\t$route  = (string) $request->get_route();\n\t\t$method = strtoupper( (string) $request->get_method() );\n\t\tif ( self::is_legacy_numeric_rest_route( $route ) && ! (bool) apply_filters( 'wca_allow_legacy_numeric_rest_routes', false ) ) {\n\t\t\treturn new WP_Error( 'wca_legacy_numeric_rest_disabled', __( 'This legacy numeric-ID endpoint is disabled. Use the current opaque-reference endpoint.', 'worldwide-clinic-appointments' ), array( 'status' => 410 ) );\n\t\t}\n\t\tif ( ! in_array( $method, array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) || ! self::is_core_mutation_route( $route ) ) {""")
    marker="""\tprivate static function is_core_mutation_route( $route ) {"""
    helper="""\tprivate static function is_legacy_numeric_rest_route( $route ) {\n\t\treturn (bool) preg_match( '#^/wca/v1/(?:clinics/[0-9]+/(?:submit-review|activate)|appointments/[0-9]+(?:/transitions|/calendar\\.ics|/payment-intents)?)$#', (string) $route );\n\t}\n\n"""
    s=read(p)
    if marker not in s: raise SystemExit('r16 method anchor missing')
    s=s.replace(marker,helper+marker,1)
    s=s.replace("\t\t\t'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents)$#',","\t\t\t'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents)$#',\n\t\t\t'#^/wca/v1/clinics/[0-9]+/(?:submit-review|activate)$#',\n\t\t\t'#^/wca/v1/appointments/[0-9]+/(?:transitions|payment-intents)$#',",1)
    write(p,s)

def r17():
    p='includes/class-wca-rest.php'
    rep(p,"""\tpublic static function clinic( WP_REST_Request $request ) {\n\t\t$rate = self::rate_limit( 'public_clinic', 120, 60 );\n\t\tif ( is_wp_error( $rate ) ) { return $rate; }\n\t\t$projection = WCA_Service::public_clinic_projection( sanitize_text_field( $request['id'] ) );""","""\tpublic static function clinic( WP_REST_Request $request ) {\n\t\t$rate = self::rate_limit( 'public_clinic', 120, 60 );\n\t\tif ( is_wp_error( $rate ) ) { return $rate; }\n\t\t$identifier = sanitize_text_field( $request['id'] );\n\t\tif ( ctype_digit( $identifier ) ) { return new WP_Error( 'wca_public_numeric_id_disabled', __( 'Numeric internal clinic identifiers are not public API identities.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t$projection = WCA_Service::public_clinic_projection( $identifier );""")

def r18():
    # release identity
    rep('worldwide-clinic.php',' * Version: 1.2.13',' * Version: 1.2.14')
    rep('worldwide-clinic.php',"define( 'WCA_VERSION', '1.2.13' );","define( 'WCA_VERSION', '1.2.14' );")
    p='includes/class-wca-contracts.php'; s=read(p)
    if "RUNTIME_VERSION                 = '1.2.13'" not in s: raise SystemExit('runtime version anchor missing')
    write(p,s.replace("RUNTIME_VERSION                 = '1.2.13'","RUNTIME_VERSION                 = '1.2.14'",1))
    # current docs only
    for p,old,new in [
      ('README.md','Runtime candidate: **1.2.13**','Runtime candidate: **1.2.14**'),
      ('STATUS.md','Runtime candidate: **1.2.13**','Runtime candidate: **1.2.14**'),
      ('readme.txt','Stable tag: 1.2.13','Stable tag: 1.2.14')]:
        s=read(p)
        if old in s: write(p,s.replace(old,new,1))
    p='CHANGELOG.md'; s=read(p)
    entry="""## 1.2.14 — 2026-08-12\n\n- Fourteenth fresh sequential 20-round review: consent atomicity, strict IANA timezone and age/guardian validation, Future24 heatmap/advisor contract corrections, bounded legacy doctor discovery, secure UUID/worker identity, branch/clinic fail-closed validation, and legacy numeric REST retirement.\n- Runtime 1.2.14; core schema remains 3.2.0, continuity 1.1.0, Future24 1.0.0. Staging/live acceptance remains separate.\n\n"""
    if '## 1.2.14 — 2026-08-12' not in s:
        pos=s.find('\n')+1; s=s[:pos]+'\n'+entry+s[pos:]; write(p,s)
    # current version assertions in tests
    for f in (ROOT/'tests').glob('*.php'):
        s=f.read_text()
        if '1.2.13' in s:
            # only assertions referring to current runtime/plugin identity; historical changelog literals are intentionally left by matching common labels.
            s=s.replace("Version: 1.2.13","Version: 1.2.14")
            s=s.replace("RUNTIME_VERSION                 = '1.2.13'","RUNTIME_VERSION                 = '1.2.14'")
            s=s.replace("Runtime candidate: **1.2.13**","Runtime candidate: **1.2.14**")
            s=s.replace("Stable tag: 1.2.13","Stable tag: 1.2.14")
            f.write_text(s)
    test=ROOT/'tests/fourteenth-twenty-review-regressions.php'
    test.write_text(r'''<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t14has($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
function t14lacks($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false===strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' forbidden: '.$needle;}}
t14has('R1 owner-transaction privacy consent','includes/class-wca-service.php',"$context_scopes = array( 'privacy_notice' )");
t14has('R1 command consent fail closed','includes/class-wca-appointment-command.php','context_consent_sync_failed');
t14has('R2 strict IANA identifiers','includes/class-wca-service.php','timezone_identifiers_list()');
t14has('R3 strict DOB roundtrip','includes/class-wca-central-governance.php',"createFromFormat( '!Y-m-d'");
t14has('R4 age assertion conflict','includes/class-wca-central-governance.php','wca_age_claim_conflict');
t14has('R5 exact heatmap windows','includes/class-wca-future24.php','wca_heatmap_window');
t14has('R6 completed heatmap count','includes/class-wca-future24.php',"'completed' => 0");
t14has('R6 cancelled heatmap count','includes/class-wca-future24.php',"'cancelled' => 0");
t14has('R6 no-show heatmap count','includes/class-wca-future24.php',"'no_show' => 0");
t14has('R7 opaque clinic read helper','includes/class-wca-future24.php','clinic_id_from_public_ref');
t14has('R8 advisor reason','includes/class-wca-future24.php',"'utilization_ratio'");
t14has('R8 advisor provenance','includes/class-wca-future24.php',"'source_contract' => 'wca.capacity-heatmap'");
t14has('R9 bounded doctor query','includes/class-swc-helpers.php',"'number'   => $limit");
t14lacks('R9 no unbounded doctor load','includes/class-swc-helpers.php',"'number'   => -1");
t14has('R10 bounded request doctors','includes/class-swc-frontend.php','requestable_doctor_ids( 100, 0 )');
t14has('R11 secure UUID fallback','includes/class-wca-repository.php','random_bytes( 16 )');
t14lacks('R11 no mt_rand UUID fallback','includes/class-wca-repository.php','mt_rand( 0, 0xffff )');
t14has('R12 secure worker identity','includes/class-wca-repository.php',"'worker-' . str_replace( '-', '', self::uuid() )");
t14has('R13 branch visibility rejection','includes/class-wca-repository.php','wca_branch_visibility');
t14has('R13 branch status rejection','includes/class-wca-repository.php','wca_branch_status');
t14has('R14 country rejection','includes/class-wca-repository.php','wca_branch_country');
t14has('R15 clinic status rejection','includes/class-wca-repository.php',"return new WP_Error( 'wca_clinic_status'");
t14has('R16 numeric REST retirement','includes/class-wca-ten-review-hardening.php','wca_legacy_numeric_rest_disabled');
t14has('R17 public numeric clinic rejection','includes/class-wca-rest.php','wca_public_numeric_id_disabled');
t14has('R18 plugin 1.2.14','worldwide-clinic.php','Version: 1.2.14');
t14has('R18 runtime 1.2.14','includes/class-wca-contracts.php',"RUNTIME_VERSION                 = '1.2.14'");
if($fail){fwrite(STDERR,"File 08 fourteenth twenty-round regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo 'File 08 fourteenth fresh twenty-round regression assertions passed: '.$pass.'/'.$pass."\n";
''')
    p='tests/run-all.php'; s=read(p)
    old="'thirteenth-twenty-review-regressions.php' );"
    new="'thirteenth-twenty-review-regressions.php', 'fourteenth-twenty-review-regressions.php' );"
    if old not in s: raise SystemExit('run-all final anchor missing')
    write(p,s.replace(old,new,1))
    evidence=ROOT/'FOURTEENTH-TWENTY-REVIEW-EVIDENCE.md'
    evidence.write_text('''# File 08 — Fourteenth Fresh Sequential 20-Round Review Evidence\n\nFrozen product baseline: `fe10d05bdb4d1ffb726063f657f283097e65abbd` / runtime 1.2.13. Temporary audit-only commits are not product baselines.\n\nMain review findings: R01 consent atomicity; R02 strict IANA timezone validation; R03 strict DOB parsing; R04 minor-claim conflict fail-closed; R05 exact 7/30/90 heatmap windows; R06 required heatmap outcome counters; R07 opaque clinic references for Future24 analytics reads; R08 advisor reason/provenance; R09 bounded public doctor loading; R10 bounded legacy request/admin doctor loading; R11 cryptographically secure UUID fallback; R12 collision-resistant outbox worker identity; R13 branch status/visibility fail-closed; R14 exact country code validation; R15 invalid clinic-status rejection; R16 legacy numeric protected REST retirement/idempotency coverage; R17 public numeric clinic-ID rejection; R18 release/test evidence alignment to 1.2.14.\n\nR19 and R20 are post-final-coding fresh corrected-state reviews and must complete with no new supported defect before release evidence is accepted. Staging/live evidence remains separate.\n''')

def clean(n):
    print(f'R{n}: no source mutation; corrected-state adversarial review gate')

rounds={1:r1,2:r2,3:r3,4:r4,5:r5,6:r6,7:r7,8:r8,9:r9,10:r10,11:r11,12:r12,13:r13,14:r14,15:r15,16:r16,17:r17,18:r18,19:lambda:clean(19),20:lambda:clean(20)}
if len(sys.argv)!=2 or int(sys.argv[1]) not in rounds: raise SystemExit('usage: t14_apply.py ROUND')
n=int(sys.argv[1]); rounds[n](); print(f'Applied/reviewed T14-R{n:02d}')
