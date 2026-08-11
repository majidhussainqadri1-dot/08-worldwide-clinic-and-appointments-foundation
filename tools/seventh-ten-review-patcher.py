from pathlib import Path
import sys, re
root=Path(__file__).resolve().parents[1]

def read(p): return (root/p).read_text(encoding='utf-8')
def write(p,s): (root/p).write_text(s,encoding='utf-8')
def rep_once(s, old, new, label):
    if old not in s: raise SystemExit(f'marker missing: {label}')
    return s.replace(old,new,1)

def replace_function(path, name, new_body, next_marker):
    s=read(path)
    start=s.find(f'\tpublic static function {name}(')
    if start<0: raise SystemExit(f'{path}: function {name} not found')
    end=s.find(next_marker,start)
    if end<0: raise SystemExit(f'{path}: next marker not found for {name}')
    s=s[:start]+new_body+s[end:]
    write(path,s)

def r1():
    path=Path('includes/class-wca-continuity-secure.php')
    new='''\tpublic static function privacy_eraser( $email_address, $page = 1 ) {\n\t\tglobal $wpdb;\n\t\t$user = get_user_by( 'email', sanitize_email( $email_address ) );\n\t\tif ( ! $user ) { return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true ); }\n\t\t$user_id = absint( $user->ID );\n\t\t$page = max( 1, absint( $page ) );\n\t\t$base = 'wca_continuity_erase_' . substr( hash( 'sha256', strtolower( sanitize_email( $email_address ) ) ), 0, 24 );\n\t\tif ( 1 === $page ) {\n\t\t\tdelete_transient( $base . '_intake' );\n\t\t\tdelete_transient( $base . '_followups' );\n\t\t}\n\t\t$removed = false;\n\t\t$retained = false;\n\t\t$messages = array();\n\t\t$done = true;\n\t\tforeach ( array( 'intake' => 'patient_user_id', 'followups' => 'patient_user_id' ) as $type => $field ) {\n\t\t\t$table = self::tables()[ $type ];\n\t\t\t$cursor_key = $base . '_' . $type;\n\t\t\t$cursor = absint( get_transient( $cursor_key ) );\n\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT %d", $user_id, $cursor, 100 ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t$last = $cursor;\n\t\t\tforeach ( $rows as $row ) {\n\t\t\t\t$last = max( $last, absint( $row['id'] ) );\n\t\t\t\tif ( self::legal_hold( 'followups' === $type ? 'followup' : 'intake', $row ) ) { $retained = true; continue; }\n\t\t\t\tif ( false !== $wpdb->delete( $table, array( 'id' => absint( $row['id'] ) ), array( '%d' ) ) ) { $removed = true; }\n\t\t\t}\n\t\t\tif ( $last > $cursor ) { set_transient( $cursor_key, $last, HOUR_IN_SECONDS ); }\n\t\t\t$more = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE {$field}=%d AND id>%d ORDER BY id ASC LIMIT 1", $user_id, $last ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tif ( $more ) { $done = false; } else { delete_transient( $cursor_key ); }\n\t\t}\n\t\t$intake_table = self::tables()['intake'];\n\t\t$wpdb->update( $intake_table, array( 'guardian_user_id' => 0 ), array( 'guardian_user_id' => $user_id ), array( '%d' ), array( '%d' ) );\n\t\tif ( $retained ) { $messages[] = __( 'Some clinic continuity records are retained under an active legal, safety or professional record hold.', 'worldwide-clinic-appointments' ); }\n\t\treturn array( 'items_removed' => $removed, 'items_retained' => $retained, 'messages' => $messages, 'done' => $done );\n\t}\n\n'''
    replace_function(path,'privacy_eraser',new,'\tpublic static function register_routes()')

def r2():
    path=Path('includes/class-wca-continuity-secure.php')
    new='''\tpublic static function apply_retention() {\n\t\tglobal $wpdb;\n\t\t$intake_days   = max( 30, absint( apply_filters( 'wca_intake_retention_days', 365 ) ) );\n\t\t$followup_days = max( 30, absint( apply_filters( 'wca_followup_retention_days', 730 ) ) );\n\t\t$intake_cutoff = gmdate( 'Y-m-d H:i:s', time() - $intake_days * DAY_IN_SECONDS );\n\t\t$follow_cutoff = gmdate( 'Y-m-d H:i:s', time() - $followup_days * DAY_IN_SECONDS );\n\t\t$intake_table  = self::tables()['intake'];\n\t\t$follow_table  = self::tables()['followups'];\n\t\t$batch = 200;\n\t\t$cursor = 0;\n\t\tdo {\n\t\t\t$intakes = (array) $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id FROM {$intake_table} WHERE updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d", $intake_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $intakes as $row ) {\n\t\t\t\t$cursor = max( $cursor, absint( $row['id'] ) );\n\t\t\t\tif ( self::legal_hold( 'intake', $row ) ) { continue; }\n\t\t\t\t$status = SWC_Helpers::status( absint( $row['appointment_id'] ) );\n\t\t\t\tif ( WCA_Contracts::is_terminal( $status ) ) { $wpdb->delete( $intake_table, array( 'id' => absint( $row['id'] ) ), array( '%d' ) ); }\n\t\t\t}\n\t\t} while ( count( $intakes ) === $batch );\n\t\t$cursor = 0;\n\t\tdo {\n\t\t\t$followups = (array) $wpdb->get_results( $wpdb->prepare( "SELECT id,public_ref,appointment_id,status FROM {$follow_table} WHERE updated_at<%s AND status IN ('completed','cancelled') AND id>%d ORDER BY id ASC LIMIT %d", $follow_cutoff, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $followups as $row ) {\n\t\t\t\t$cursor = max( $cursor, absint( $row['id'] ) );\n\t\t\t\tif ( ! self::legal_hold( 'followup', $row ) ) { $wpdb->delete( $follow_table, array( 'id' => absint( $row['id'] ) ), array( '%d' ) ); }\n\t\t\t}\n\t\t} while ( count( $followups ) === $batch );\n\t}\n\n'''
    replace_function(path,'apply_retention',new,'\tpublic static function register_exporter(')

def r3():
    path=Path('includes/class-wca-privacy.php')
    s=read(path)
    old='''\t\t$table = self::future24_table();\n\t\tif ( $table ) {\n\t\t\t$cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, absint( $policy['future24_operational_days'] ) ) * DAY_IN_SECONDS );\n\t\t\t$rows = (array) $wpdb->get_results(\n\t\t\t\t$wpdb->prepare( "SELECT * FROM {$table} WHERE expires_at IS NOT NULL AND expires_at<%s AND updated_at<%s ORDER BY id ASC LIMIT 500", WCA_Repository::now(), $cutoff ),\n\t\t\t\tARRAY_A\n\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) {\n\t\t\t\tif ( self::future24_legal_hold( $row ) ) { continue; }\n\t\t\t\t$wpdb->delete( $table, array( 'id' => absint( $row['id'] ) ), array( '%d' ) );\n\t\t\t}\n\t\t}\n'''
    new='''\t\t$table = self::future24_table();\n\t\tif ( $table ) {\n\t\t\t$cutoff = gmdate( 'Y-m-d H:i:s', time() - max( 1, absint( $policy['future24_operational_days'] ) ) * DAY_IN_SECONDS );\n\t\t\t$cursor = 0;\n\t\t\t$batch = 250;\n\t\t\tdo {\n\t\t\t\t$rows = (array) $wpdb->get_results(\n\t\t\t\t\t$wpdb->prepare( "SELECT * FROM {$table} WHERE expires_at IS NOT NULL AND expires_at<%s AND updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d", WCA_Repository::now(), $cutoff, $cursor, $batch ),\n\t\t\t\t\tARRAY_A\n\t\t\t\t); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\t\tforeach ( $rows as $row ) {\n\t\t\t\t\t$cursor = max( $cursor, absint( $row['id'] ) );\n\t\t\t\t\tif ( self::future24_legal_hold( $row ) ) { continue; }\n\t\t\t\t\t$wpdb->delete( $table, array( 'id' => absint( $row['id'] ) ), array( '%d' ) );\n\t\t\t\t}\n\t\t\t} while ( count( $rows ) === $batch );\n\t\t}\n'''
    write(path,rep_once(s,old,new,'future24 retention'))

def r4():
    path=Path('includes/class-wca-future24.php'); s=read(path)
    old="""\t\t$windows = array();\n\t\t$latest_end = 0;\n\t\t$now = time();\n\t\tforeach ( array_slice( (array) ( isset( $data['windows'] ) ? $data['windows'] : array() ), 0, 12 ) as $window ) {\n"""
    new="""\t\t$raw_windows = (array) ( isset( $data['windows'] ) ? $data['windows'] : array() );\n\t\tif ( count( $raw_windows ) > 12 ) { return new WP_Error( 'wca_windows_limit', __( 'No more than 12 scheduling windows may be saved in one request.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }\n\t\t$windows = array();\n\t\t$latest_end = 0;\n\t\t$now = time();\n\t\tforeach ( $raw_windows as $window ) {\n"""
    write(path,rep_once(s,old,new,'save windows limit'))

def r5():
    path=Path('includes/class-wca-future24.php'); s=read(path)
    old="""\t\t$requirements = array();\n\t\tforeach ( array_slice( (array) ( $data['requirements'] ?? array() ), 0, 20 ) as $item ) {\n"""
    new="""\t\t$raw_requirements = (array) ( $data['requirements'] ?? array() );\n\t\tif ( count( $raw_requirements ) > 20 ) { return new WP_Error( 'wca_prerequisite_rules_limit', __( 'No more than 20 prerequisite rules may be saved in one policy.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }\n\t\t$requirements = array();\n\t\tforeach ( $raw_requirements as $item ) {\n"""
    write(path,rep_once(s,old,new,'prerequisite rule limit'))

def r6():
    path=Path('includes/class-wca-continuity-secure.php'); s=read(path)
    old="""\tprivate static function sanitize_followup( $data ) {\n\t\t$out = array(\n\t\t\t'purpose'      => self::bounded_text( isset( $data['purpose'] ) ? $data['purpose'] : '', 191 ),\n\t\t\t'instructions' => self::bounded_textarea( isset( $data['instructions'] ) ? $data['instructions'] : '', 5000 ),\n\t\t\t'limitations'  => self::bounded_textarea( isset( $data['limitations'] ) ? $data['limitations'] : '', 1500 ),\n\t\t\t'resources'    => self::sanitize_resource_refs( isset( $data['resources'] ) ? $data['resources'] : array() ),\n\t\t);\n"""
    new="""\tprivate static function sanitize_followup( $data ) {\n\t\t$resources = (array) ( isset( $data['resources'] ) ? $data['resources'] : array() );\n\t\tif ( count( $resources ) > 20 ) { return new WP_Error( 'wca_followup_resource_limit', __( 'No more than 20 follow-up resources may be saved in one plan.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }\n\t\t$out = array(\n\t\t\t'purpose'      => self::bounded_text( isset( $data['purpose'] ) ? $data['purpose'] : '', 191 ),\n\t\t\t'instructions' => self::bounded_textarea( isset( $data['instructions'] ) ? $data['instructions'] : '', 5000 ),\n\t\t\t'limitations'  => self::bounded_textarea( isset( $data['limitations'] ) ? $data['limitations'] : '', 1500 ),\n\t\t\t'resources'    => self::sanitize_resource_refs( $resources ),\n\t\t);\n"""
    s=rep_once(s,old,new,'followup resource validation')
    s=rep_once(s,"\t\tforeach ( array_slice( (array) $resources, 0, 20 ) as $resource ) {\n","\t\tforeach ( (array) $resources as $resource ) {\n",'resource slice removal')
    write(path,s)

def r7():
    path=Path('includes/class-wca-future24.php'); s=read(path)
    s=rep_once(s,"\t\tforeach ( array_slice( $raw_evidence, 0, 100, true ) as $key => $item ) {\n","\t\tforeach ( $raw_evidence as $key => $item ) {\n",'evidence cap')
    write(path,s)

def r8():
    path=Path('includes/class-wca-future24.php'); s=read(path)
    old="""\t\t$refs = array();\n\t\t$scope = null;\n\t\tforeach ( array_slice( (array) ( isset( $data['appointment_refs'] ) ? $data['appointment_refs'] : array() ), 0, 50 ) as $ref ) {\n"""
    new="""\t\t$raw_refs = (array) ( isset( $data['appointment_refs'] ) ? $data['appointment_refs'] : array() );\n\t\tif ( count( $raw_refs ) > 50 ) { return new WP_Error( 'wca_episode_appointment_limit', __( 'No more than 50 appointments may be linked in one episode.', 'worldwide-clinic-appointments' ), array( 'status' => 413 ) ); }\n\t\t$refs = array();\n\t\t$scope = null;\n\t\tforeach ( $raw_refs as $ref ) {\n"""
    write(path,rep_once(s,old,new,'episode refs cap'))

def r9():
    path=Path('includes/class-wca-future24.php'); s=read(path)
    write(path,rep_once(s,"\t\t$ids = self::clinic_appointments_between( $clinic_id, $from, $to, 2000 );\n","\t\t$ids = self::clinic_appointments_between_all( $clinic_id, $from, $to );\n",'heatmap cap'))

def r10():
    path=Path('includes/class-wca-future24.php'); s=read(path)
    old="\t\t$ids = self::clinic_appointments( $clinic_id, 365, 2000 );\n"
    new="\t\t$ids = self::clinic_appointments_between_all( $clinic_id, gmdate( 'Y-m-d H:i:s', time() - 365 * DAY_IN_SECONDS ), gmdate( 'Y-m-d H:i:s', time() + HOUR_IN_SECONDS ) );\n"
    write(path,rep_once(s,old,new,'no-show cap'))

rounds=[None,r1,r2,r3,r4,r5,r6,r7,r8,r9,r10]
r=int(sys.argv[1]); rounds[r](); print('round',r,'ok')
