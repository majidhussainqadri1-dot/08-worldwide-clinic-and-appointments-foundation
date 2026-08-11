from pathlib import Path

p = Path('includes/class-wca-service.php')
s = p.read_text()
old = """\t\t$start = (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' );
\t\t$end = (string) SWC_Helpers::meta( $appointment_id, 'appointment_end_utc' );
\t\tif ( ! $start ) { return new WP_Error( 'wca_calendar_time', __( 'Appointment time is unavailable.', 'worldwide-clinic-appointments' ) ); }
\t\tif ( ! $end ) { $end = gmdate( 'Y-m-d H:i:s', strtotime( $start . ' UTC' ) + max( 10, absint( SWC_Helpers::meta( $appointment_id, 'appointment_duration', 30 ) ) ) * 60 ); }
"""
new = """\t\t$start = (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' );
\t\t$end = (string) SWC_Helpers::meta( $appointment_id, 'appointment_end_utc' );
\t\tif ( ! $start ) { return new WP_Error( 'wca_calendar_time', __( 'Appointment time is unavailable.', 'worldwide-clinic-appointments' ) ); }
\t\t$start_ts = self::strict_utc_timestamp( $start );
\t\tif ( false === $start_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment start time is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\tif ( $end ) {
\t\t\t$end_ts = self::strict_utc_timestamp( $end );
\t\t\tif ( false === $end_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment end time is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
\t\t} else {
\t\t\t$end_ts = $start_ts + max( 10, absint( SWC_Helpers::meta( $appointment_id, 'appointment_duration', 30 ) ) ) * 60;
\t\t}
\t\tif ( $end_ts <= $start_ts ) { return new WP_Error( 'wca_calendar_time_invalid', __( 'Stored appointment end time must be after the start time.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }
"""
if old not in s:
    raise SystemExit('R6 calendar source block not found')
s = s.replace(old, new, 1)
s = s.replace("'DTSTART:' . gmdate( 'Ymd\\THis\\Z', strtotime( $start . ' UTC' ) ),", "'DTSTART:' . gmdate( 'Ymd\\THis\\Z', $start_ts ),", 1)
s = s.replace("'DTEND:' . gmdate( 'Ymd\\THis\\Z', strtotime( $end . ' UTC' ) ),", "'DTEND:' . gmdate( 'Ymd\\THis\\Z', $end_ts ),", 1)
marker = "\tprivate static function ics_escape( $value ) {\n"
helper = """\tprivate static function strict_utc_timestamp( $value ) {
\t\t$value = trim( (string) $value );
\t\tif ( '' === $value ) { return false; }
\t\t$utc = new DateTimeZone( 'UTC' );
\t\tforeach ( array( array( '!Y-m-d H:i:s', 'Y-m-d H:i:s' ), array( '!Y-m-d H:i', 'Y-m-d H:i' ), array( '!Y-m-d\\TH:i:s\\Z', 'Y-m-d\\TH:i:s\\Z' ), array( '!Y-m-d\\TH:i\\Z', 'Y-m-d\\TH:i\\Z' ) ) as $entry ) {
\t\t\t$dt = DateTimeImmutable::createFromFormat( $entry[0], $value, $utc );
\t\t\t$errors = DateTimeImmutable::getLastErrors();
\t\t\tif ( $dt && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) && $dt->format( $entry[1] ) === $value ) { return $dt->getTimestamp(); }
\t\t}
\t\treturn false;
\t}

"""
if marker not in s:
    raise SystemExit('R6 ICS helper insertion point not found')
s = s.replace(marker, helper + marker, 1)
p.write_text(s)
