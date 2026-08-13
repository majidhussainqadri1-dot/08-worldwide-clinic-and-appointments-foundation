from pathlib import Path
p=Path('includes/class-wca-continuity-secure.php'); s=p.read_text()
def one(old,new):
    global s
    if s.count(old)!=1: raise SystemExit(f'anchor mismatch {s.count(old)}: {old[:90]}')
    s=s.replace(old,new,1)
one("\tprivate static function active_consent( $appointment_id, $scope ) { global $wpdb; $table=WCA_Schema::tables()['consents']; $count=$wpdb->get_var($wpdb->prepare(\"SELECT COUNT(*) FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL\",absint($appointment_id),sanitize_key($scope))); return absint($count)>0; } // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared", """\t/** @return bool|WP_Error */
\tprivate static function active_consent( $appointment_id, $scope ) {
\t\tglobal $wpdb;
\t\t$table = WCA_Schema::tables()['consents'];
\t\t$count = $wpdb->get_var( $wpdb->prepare( \"SELECT COUNT(*) FROM {$table} WHERE appointment_id=%d AND scope=%s AND status='granted' AND revoked_at IS NULL\", absint( $appointment_id ), sanitize_key( $scope ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
\t\tif ( '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_active_consent_read_failed', __( 'Current consent state could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
\t\treturn absint( $count ) > 0;
\t}""")
one("""\t\tif ( $submit && ! self::active_consent( $appointment_id, 'appointment_processing' ) ) {
\t\t\treturn new WP_Error( 'wca_intake_consent', __( 'Current appointment-processing consent is required before intake submission.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
\t\t}""", """\t\tif ( $submit ) {
\t\t\t$active_consent = self::active_consent( $appointment_id, 'appointment_processing' );
\t\t\tif ( is_wp_error( $active_consent ) ) { return $active_consent; }
\t\t\tif ( ! $active_consent ) { return new WP_Error( 'wca_intake_consent', __( 'Current appointment-processing consent is required before intake submission.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t}""")
one("""\t\t$patient_id = self::patient_id( $appointment_id );
\t\t$doctor_id  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) );
\t\treturn array(""", """\t\t$patient_id = self::patient_id( $appointment_id );
\t\t$doctor_id  = absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) );
\t\t$messaging_consent = self::active_consent( $appointment_id, 'messaging' );
\t\t$call_consent      = self::active_consent( $appointment_id, 'teleconsult' );
\t\t$recording_consent = self::active_consent( $appointment_id, 'recording' );
\t\tforeach ( array( $messaging_consent, $call_consent, $recording_consent ) as $consent_state ) { if ( is_wp_error( $consent_state ) ) { return $consent_state; } }
\t\treturn array(""")
for old,new in [
("'messaging_allowed'      => $active && self::active_consent( $appointment_id, 'messaging' ),", "'messaging_allowed'      => $active && $messaging_consent,"),
("'call_allowed'           => $active && self::active_consent( $appointment_id, 'teleconsult' ),", "'call_allowed'           => $active && $call_consent,"),
("'recording_allowed'      => $active && self::active_consent( $appointment_id, 'recording' ),", "'recording_allowed'      => $active && $recording_consent,")]: one(old,new)
one("\t\tif ( ! self::active_consent( $appointment_id, 'followup' ) ) { return new WP_Error( 'wca_followup_consent', __( 'Current follow-up consent is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }", """\t\t$followup_consent = self::active_consent( $appointment_id, 'followup' );
\t\tif ( is_wp_error( $followup_consent ) ) { return $followup_consent; }
\t\tif ( ! $followup_consent ) { return new WP_Error( 'wca_followup_consent', __( 'Current follow-up consent is required.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }""")
p.write_text(s)
