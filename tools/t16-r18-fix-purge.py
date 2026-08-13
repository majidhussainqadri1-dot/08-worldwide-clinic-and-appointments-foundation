from pathlib import Path

def one(path, old, new):
    p=Path(path); s=p.read_text()
    if s.count(old)!=1: raise SystemExit(f'{path}: anchor count {s.count(old)} for {old[:100]!r}')
    p.write_text(s.replace(old,new,1))

# Privacy-owned destructive-purge preflight: verified backup + all legal holds + storage failures.
p=Path('includes/class-wca-privacy.php'); s=p.read_text()
anchor="\tprivate static function future24_table() {"
if s.count(anchor)!=1: raise SystemExit('privacy future24_table anchor mismatch')
preflight="""\t/**
\t * Destructive purge is forbidden until an external operations/assurance owner
\t * attests that a restorable backup was verified for this exact purge attempt.
\t * No mutation occurs here; the whole legal-hold inventory is read first.
\t *
\t * @return true|WP_Error
\t */
\tpublic static function assert_purge_allowed( $actor_user_id = 0 ) {
\t\tglobal $wpdb;
\t\t$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
\t\t$backup_verified = apply_filters( 'wca_purge_backup_verified', false, $actor_user_id );
\t\tif ( true !== $backup_verified ) {
\t\t\treturn new WP_Error( 'wca_purge_backup_unverified', __( 'Irreversible File 08 purge is blocked until a verified restorable backup is attested by the approved operations/assurance integration.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}

\t\t$cursor = 0;
\t\tdo {
\t\t\t$wpdb->last_error = '';
\t\t\t$ids = $wpdb->get_col( $wpdb->prepare( \"SELECT ID FROM {$wpdb->posts} WHERE post_type=%s AND ID>%d ORDER BY ID ASC LIMIT 200\", SWC_Helpers::TYPE, $cursor ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
\t\t\tif ( null === $ids || '' !== (string) $wpdb->last_error ) {
\t\t\t\treturn new WP_Error( 'wca_purge_hold_inventory_failed', __( 'File 08 purge could not verify appointment legal holds safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t\t\t}
\t\t\tforeach ( (array) $ids as $id_raw ) {
\t\t\t\t$id = absint( $id_raw );
\t\t\t\t$wpdb->last_error = '';
\t\t\t\t$hold_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id DESC LIMIT 1\", $id, '_swc_legal_hold' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\tif ( '' !== (string) $wpdb->last_error ) {
\t\t\t\t\treturn new WP_Error( 'wca_purge_hold_read_failed', __( 'File 08 purge could not verify an appointment legal hold safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t\t\t\t}
\t\t\t\t$held = (bool) apply_filters( 'wca_appointment_legal_hold', (bool) $hold_raw, $id );
\t\t\t\tif ( $held ) {
\t\t\t\t\treturn new WP_Error( 'wca_purge_legal_hold', __( 'Irreversible File 08 purge is blocked because one or more records are under legal hold.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t\t\t}
\t\t\t\t$cursor = max( $cursor, $id );
\t\t\t}
\t\t} while ( 200 === count( (array) $ids ) );

\t\t$table = self::future24_table();
\t\tif ( is_wp_error( $table ) ) { return $table; }
\t\tif ( $table ) {
\t\t\t$cursor = 0;
\t\t\tdo {
\t\t\t\t$wpdb->last_error = '';
\t\t\t\t$rows = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id>%d ORDER BY id ASC LIMIT 200\", $cursor ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\tif ( null === $rows || '' !== (string) $wpdb->last_error ) {
\t\t\t\t\treturn new WP_Error( 'wca_purge_future24_hold_inventory_failed', __( 'File 08 purge could not verify Future24 legal holds safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
\t\t\t\t}
\t\t\t\tforeach ( (array) $rows as $row ) {
\t\t\t\t\tif ( ! empty( $row['appointment_id'] ) ) {
\t\t\t\t\t\t$appointment_id = absint( $row['appointment_id'] );
\t\t\t\t\t\t$wpdb->last_error = '';
\t\t\t\t\t\t$hold_raw = $wpdb->get_var( $wpdb->prepare( \"SELECT meta_value FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key=%s ORDER BY meta_id DESC LIMIT 1\", $appointment_id, '_swc_legal_hold' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\t\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_purge_future24_hold_read_failed', __( 'File 08 purge could not verify a Future24-linked legal hold safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\t\t\t\t\tif ( (bool) apply_filters( 'wca_appointment_legal_hold', (bool) $hold_raw, $appointment_id ) ) { return new WP_Error( 'wca_purge_legal_hold', __( 'Irreversible File 08 purge is blocked because one or more records are under legal hold.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t\t\t}
\t\t\t\t\tif ( (bool) apply_filters( 'wca_future24_legal_hold', false, $row ) ) { return new WP_Error( 'wca_purge_legal_hold', __( 'Irreversible File 08 purge is blocked because one or more Future24 records are under legal hold.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t\t\t\t$cursor = max( $cursor, absint( $row['id'] ?? 0 ) );
\t\t\t\t}
\t\t\t} while ( 200 === count( (array) $rows ) );
\t\t}
\t\treturn true;
\t}

"""
p.write_text(s.replace(anchor,preflight+anchor,1))

# Continuity owns two additive tables and its schema marker.
p=Path('includes/class-wca-continuity-secure.php'); s=p.read_text(); anchor="\t/** @return array<string,mixed> */\n\tpublic static function health() {"
if s.count(anchor)!=1: raise SystemExit('continuity purge insertion anchor mismatch')
purge="""\t/** @return true|WP_Error */
\tpublic static function purge_owned_data() {
\t\tglobal $wpdb;
\t\tforeach ( array_reverse( self::tables() ) as $table ) {
\t\t\tif ( false === $wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\t\treturn new WP_Error( 'wca_continuity_purge_table_failed', __( 'A File 08 continuity table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t\t}
\t\t}
\t\t$deleted = SWC_Helpers::delete_option_strict( self::SCHEMA_OPTION, 'wca_continuity_purge_schema_option' );
\t\treturn is_wp_error( $deleted ) ? $deleted : true;
\t}

"""
p.write_text(s.replace(anchor,purge+anchor,1))

# Future24 owns its operational table and schema marker.
p=Path('includes/class-wca-future24.php'); s=p.read_text(); anchor="\tpublic static function register_assets() {"
if s.count(anchor)!=1: raise SystemExit('future24 purge insertion anchor mismatch')
purge="""\t/** @return true|WP_Error */
\tpublic static function purge_owned_data() {
\t\tglobal $wpdb;
\t\t$table = self::tables()['records'];
\t\tif ( false === $wpdb->query( 'DROP TABLE IF EXISTS `' . esc_sql( $table ) . '`' ) ) { // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\t\treturn new WP_Error( 'wca_future24_purge_table_failed', __( 'The File 08 Future24 operational table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
\t\t}
\t\t$deleted = SWC_Helpers::delete_option_strict( self::SCHEMA_OPTION, 'wca_future24_purge_schema_option' );
\t\treturn is_wp_error( $deleted ) ? $deleted : true;
\t}

"""
p.write_text(s.replace(anchor,purge+anchor,1))

# Enforce the preflight inside the low-level purge so UI/programmatic callers cannot bypass it,
# and delete all File 08-owned schema stores rather than only the legacy foundation.
p=Path('includes/class-swc-activator.php'); s=p.read_text()
old="""\tpublic static function purge_all_data() {
\t\tglobal $wpdb;
\t\tdo {"""
new="""\tpublic static function purge_all_data() {
\t\tglobal $wpdb;
\t\t$allowed = WCA_Privacy::assert_purge_allowed( get_current_user_id() );
\t\tif ( is_wp_error( $allowed ) ) { return $allowed; }
\t\tWCA_Observability::log( 'warning', 'irreversible_purge_started', array( 'actor_user_id' => absint( get_current_user_id() ) ) );
\t\tdo {"""
if s.count(old)!=1: raise SystemExit('activator purge preflight anchor mismatch')
s=s.replace(old,new,1)
old="""\t\tif ( false === $wpdb->query( \"DROP TABLE IF EXISTS {$wpdb->prefix}swc_audit_log\" ) ) { return new WP_Error( 'swc_purge_audit_table', __( 'The File 08 audit table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( false === $wpdb->query( \"DROP TABLE IF EXISTS {$wpdb->prefix}swc_rate_limits\" ) ) { return new WP_Error( 'swc_purge_rate_table', __( 'The File 08 rate-limit table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared"""
new="""\t\t$canonical = WCA_Schema::purge_canonical_data();
\t\tif ( is_wp_error( $canonical ) ) { WCA_Observability::log( 'critical', 'irreversible_purge_partial_failure', array( 'scope' => 'canonical', 'code' => $canonical->get_error_code() ) ); return $canonical; }
\t\t$continuity = WCA_Continuity::purge_owned_data();
\t\tif ( is_wp_error( $continuity ) ) { WCA_Observability::log( 'critical', 'irreversible_purge_partial_failure', array( 'scope' => 'continuity', 'code' => $continuity->get_error_code() ) ); return $continuity; }
\t\t$future24 = WCA_Future24::purge_owned_data();
\t\tif ( is_wp_error( $future24 ) ) { WCA_Observability::log( 'critical', 'irreversible_purge_partial_failure', array( 'scope' => 'future24', 'code' => $future24->get_error_code() ) ); return $future24; }
\t\tif ( false === $wpdb->query( \"DROP TABLE IF EXISTS {$wpdb->prefix}swc_audit_log\" ) ) { return new WP_Error( 'swc_purge_audit_table', __( 'The File 08 audit table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( false === $wpdb->query( \"DROP TABLE IF EXISTS {$wpdb->prefix}swc_rate_limits\" ) ) { return new WP_Error( 'swc_purge_rate_table', __( 'The File 08 rate-limit table could not be removed during purge.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared"""
if s.count(old)!=1: raise SystemExit('activator complete purge anchor mismatch')
s=s.replace(old,new,1)
old="\t\t$options = array( 'swc_page_map', 'swc_version', 'swc_db_version', 'swc_clinic_phone', 'swc_clinic_whatsapp', 'swc_emergency_notice', 'swc_activation_snapshot', 'swc_legacy_record_migration_cursor', WCA_Compatibility::MIGRATION_OPTION, 'swc_last_audit_error', 'swc_last_delivery_error' );"
new="\t\t$options = array( 'swc_page_map', 'swc_version', 'swc_db_version', 'swc_clinic_phone', 'swc_clinic_whatsapp', 'swc_emergency_notice', 'swc_activation_snapshot', 'swc_legacy_record_migration_cursor', WCA_Compatibility::MIGRATION_OPTION, 'swc_last_audit_error', 'swc_last_delivery_error', 'wca_runtime_migration_failure' );"
if s.count(old)!=1: raise SystemExit('activator purge options anchor mismatch')
s=s.replace(old,new,1)
old="\t\tself::remove_capabilities();\n\t\treturn true;"
new="\t\tself::remove_capabilities();\n\t\tWCA_Observability::log( 'warning', 'irreversible_purge_completed', array( 'actor_user_id' => absint( get_current_user_id() ) ) );\n\t\treturn true;"
# Last occurrence belongs to purge_all_data; replace from the right to avoid touching another method.
pos=s.rfind(old)
if pos<0: raise SystemExit('activator purge completion anchor missing')
s=s[:pos]+new+s[pos+len(old):]
p.write_text(s)
