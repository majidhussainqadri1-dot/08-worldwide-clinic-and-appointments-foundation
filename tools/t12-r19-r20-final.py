from pathlib import Path
import re
import sys

ROOT = Path('.')

def replace_once(path, old, new, label):
    p = ROOT / path
    s = p.read_text()
    count = s.count(old)
    if count != 1:
        raise SystemExit(f'{label}: anchor count={count} in {path}')
    p.write_text(s.replace(old, new, 1))


def add_test(label, needle):
    p = ROOT / 'tests/twelfth-twenty-review-regressions.php'
    s = p.read_text()
    anchor = "if($fail){fwrite(STDERR,\"File 08 twelfth twenty-round regression gate failed:\\n- \".implode(\"\\n- \",$fail).\"\\n\"); exit(1);}" 
    line = f"t12_has('{label}','{needle[0]}','{needle[1]}');\n"
    if line in s:
        return
    if anchor not in s:
        raise SystemExit('test close anchor missing')
    p.write_text(s.replace(anchor, line + anchor, 1))


def round19():
    service = 'includes/class-wca-service.php'
    repository = 'includes/class-wca-repository.php'

    replace_once(
        service,
        "\tpublic static function valid_hhmm( $value ) {\n\t\treturn 1 === preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', (string) $value );\n\t}\n",
        "\tpublic static function valid_hhmm( $value ) {\n\t\treturn 1 === preg_match( '/^(?:[01]\\d|2[0-3]):[0-5]\\d$/', (string) $value );\n\t}\n\n\t/** Strict integer validator used by canonical persistence roots; never silently clamps caller intent. */\n\tpublic static function strict_int( $value, $min, $max ) {\n\t\tif ( ! is_int( $value ) && ! is_string( $value ) ) { return null; }\n\t\t$validated = filter_var( $value, FILTER_VALIDATE_INT );\n\t\tif ( false === $validated || $validated < $min || $validated > $max ) { return null; }\n\t\treturn (int) $validated;\n\t}\n",
        'R19 strict integer helper'
    )

    old = "\t\t$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['currency'] ?? ( $current['currency'] ?? '' ) ) ) );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { return new WP_Error( 'wca_service_currency', __( 'A valid three-letter currency code is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$data['consultation_type'] = $consultation_type;\n\t\t$data['currency'] = $currency;\n"
    new = "\t\t$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['currency'] ?? ( $current['currency'] ?? '' ) ) ) );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { return new WP_Error( 'wca_service_currency', __( 'A valid three-letter currency code is required.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$duration = self::strict_int( array_key_exists( 'duration_minutes', $data ) ? $data['duration_minutes'] : ( $current['duration_minutes'] ?? 30 ), 10, 480 );\n\t\t$fee_minor = self::strict_int( array_key_exists( 'fee_minor', $data ) ? $data['fee_minor'] : ( $current['fee_minor'] ?? 0 ), 0, PHP_INT_MAX );\n\t\t$fee_max_minor = self::strict_int( array_key_exists( 'fee_max_minor', $data ) ? $data['fee_max_minor'] : ( $current['fee_max_minor'] ?? 0 ), 0, PHP_INT_MAX );\n\t\tif ( null === $duration ) { return new WP_Error( 'wca_service_duration_range', __( 'Service duration must be an integer from 10 through 480 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\tif ( null === $fee_minor || null === $fee_max_minor || ( $fee_max_minor && $fee_max_minor < $fee_minor ) ) { return new WP_Error( 'wca_service_fee_range', __( 'Service fees must be non-negative integers and the maximum fee cannot be below the minimum fee.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$data['consultation_type'] = $consultation_type;\n\t\t$data['currency'] = $currency;\n\t\t$data['duration_minutes'] = $duration;\n\t\t$data['fee_minor'] = $fee_minor;\n\t\t$data['fee_max_minor'] = $fee_max_minor;\n"
    replace_once(service, old, new, 'R19 service numeric validation')

    old = "\t\tif ( ! empty( $rrule['effective_from'] ) && ! empty( $rrule['effective_until'] ) && (string) $rrule['effective_until'] < (string) $rrule['effective_from'] ) { return new WP_Error( 'wca_availability_effective_range', __( 'Availability effective dates are reversed.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$data['timezone'] = $timezone;\n"
    new = "\t\tif ( ! empty( $rrule['effective_from'] ) && ! empty( $rrule['effective_until'] ) && (string) $rrule['effective_until'] < (string) $rrule['effective_from'] ) { return new WP_Error( 'wca_availability_effective_range', __( 'Availability effective dates are reversed.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$interval = self::strict_int( $rrule['interval_minutes'] ?? 30, 10, 1440 );\n\t\t$buffer_before = self::strict_int( $data['buffer_before'] ?? 0, 0, 240 );\n\t\t$buffer_after = self::strict_int( $data['buffer_after'] ?? 0, 0, 240 );\n\t\t$capacity = self::strict_int( $data['capacity'] ?? 1, 1, 50 );\n\t\tif ( null === $interval ) { return new WP_Error( 'wca_availability_interval_range', __( 'Availability interval must be an integer from 10 through 1440 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\tif ( null === $buffer_before || null === $buffer_after ) { return new WP_Error( 'wca_availability_buffer_range', __( 'Availability buffers must be integers from 0 through 240 minutes.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\tif ( null === $capacity ) { return new WP_Error( 'wca_availability_capacity_range', __( 'Availability capacity must be an integer from 1 through 50.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$rrule['interval_minutes'] = $interval;\n\t\t$data['rrule'] = $rrule;\n\t\t$data['buffer_before'] = $buffer_before;\n\t\t$data['buffer_after'] = $buffer_after;\n\t\t$data['capacity'] = $capacity;\n\t\t$data['timezone'] = $timezone;\n"
    replace_once(service, old, new, 'R19 availability service numeric validation')

    old = "\t\tif ( ! in_array( $consultation_type, array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_type', __( 'Service persistence requires a valid consultation type.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$row = array(\n"
    new = "\t\tif ( ! in_array( $consultation_type, array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_type', __( 'Service persistence requires a valid consultation type.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$duration = WCA_Service::strict_int( array_key_exists( 'duration_minutes', $data ) ? $data['duration_minutes'] : 30, 10, 480 );\n\t\t$fee_minor = WCA_Service::strict_int( array_key_exists( 'fee_minor', $data ) ? $data['fee_minor'] : 0, 0, PHP_INT_MAX );\n\t\t$fee_max_minor = WCA_Service::strict_int( array_key_exists( 'fee_max_minor', $data ) ? $data['fee_max_minor'] : 0, 0, PHP_INT_MAX );\n\t\t$status = $data['status'] ?? 'active';\n\t\tif ( null === $duration || null === $fee_minor || null === $fee_max_minor || ( $fee_max_minor && $fee_max_minor < $fee_minor ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_service_numeric_range', __( 'Service persistence received an invalid duration or fee range.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\tif ( ! in_array( $status, array( 'active', 'paused', 'archived' ), true ) ) { return new WP_Error( 'wca_repository_service_status', __( 'Service persistence requires a valid status.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$row = array(\n"
    replace_once(repository, old, new, 'R19 repository service validation prelude')

    replace_once(repository, "\t\t\t'duration_minutes'          => min( 480, max( 10, absint( $data['duration_minutes'] ?? 30 ) ) ),\n\t\t\t'currency'                  => $currency,\n\t\t\t'fee_minor'                 => max( 0, absint( $data['fee_minor'] ?? 0 ) ),\n\t\t\t'fee_max_minor'             => max( 0, absint( $data['fee_max_minor'] ?? 0 ) ),", "\t\t\t'duration_minutes'          => $duration,\n\t\t\t'currency'                  => $currency,\n\t\t\t'fee_minor'                 => $fee_minor,\n\t\t\t'fee_max_minor'             => $fee_max_minor,", 'R19 repository service row values')
    replace_once(repository, "\t\t\t'status'                    => in_array( $data['status'] ?? 'active', array( 'active', 'paused', 'archived' ), true ) ? $data['status'] : 'active',", "\t\t\t'status'                    => $status,", 'R19 repository service status')

    old = "\t\tif ( ! WCA_Service::valid_timezone( $timezone ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_availability_timezone', __( 'Availability persistence requires a valid IANA time zone.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$row = array(\n"
    new = "\t\tif ( ! WCA_Service::valid_timezone( $timezone ) ) {\n\t\t\treturn new WP_Error( 'wca_repository_availability_timezone', __( 'Availability persistence requires a valid IANA time zone.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\t$rrule = (array) ( $data['rrule'] ?? array() );\n\t\t$interval = WCA_Service::strict_int( $rrule['interval_minutes'] ?? 30, 10, 1440 );\n\t\t$buffer_before = WCA_Service::strict_int( $data['buffer_before'] ?? 0, 0, 240 );\n\t\t$buffer_after = WCA_Service::strict_int( $data['buffer_after'] ?? 0, 0, 240 );\n\t\t$capacity = WCA_Service::strict_int( $data['capacity'] ?? 1, 1, 50 );\n\t\t$status = $data['status'] ?? 'active';\n\t\tif ( null === $interval || null === $buffer_before || null === $buffer_after || null === $capacity ) {\n\t\t\treturn new WP_Error( 'wca_repository_availability_numeric_range', __( 'Availability persistence received an invalid interval, buffer, or capacity.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );\n\t\t}\n\t\tif ( ! in_array( $status, array( 'active', 'paused', 'archived' ), true ) ) { return new WP_Error( 'wca_repository_availability_status', __( 'Availability persistence requires a valid status.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$rrule['interval_minutes'] = $interval;\n\t\t$row = array(\n"
    replace_once(repository, old, new, 'R19 repository availability validation prelude')
    replace_once(repository, "\t\t\t'rrule_json'      => self::json( WCA_Service::sanitize_rrule( (array) ( $data['rrule'] ?? array() ) ) ),", "\t\t\t'rrule_json'      => self::json( WCA_Service::sanitize_rrule( $rrule ) ),", 'R19 repository rrule strict input')
    replace_once(repository, "\t\t\t'buffer_before'   => min( 240, absint( $data['buffer_before'] ?? 0 ) ),\n\t\t\t'buffer_after'    => min( 240, absint( $data['buffer_after'] ?? 0 ) ),\n\t\t\t'capacity'        => min( 50, max( 1, absint( $data['capacity'] ?? 1 ) ) ),\n\t\t\t'status'          => in_array( $data['status'] ?? 'active', array( 'active', 'paused', 'archived' ), true ) ? $data['status'] : 'active',", "\t\t\t'buffer_before'   => $buffer_before,\n\t\t\t'buffer_after'    => $buffer_after,\n\t\t\t'capacity'        => $capacity,\n\t\t\t'status'          => $status,", 'R19 repository availability row values')

    add_test('R19 service numeric ranges', (service, 'wca_service_duration_range'))
    add_test('R19 repository availability numeric ranges', (repository, 'wca_repository_availability_numeric_range'))
    print('Applied T12-R19 strict canonical service/availability numeric validation')


def round20():
    path = ROOT / 'includes/class-wca-future24.php'
    s = path.read_text()
    pattern = re.compile(r"\t\t\$record=self::put_record\('F08-FUT-17'.*?\t\t\$record\['affected_count'\]=count\(\$affected\); return \$record;", re.S)
    m = pattern.search(s)
    if not m:
        raise SystemExit('R20 disruption anchor missing')
    old = m.group(0)
    new = """\t\treturn WCA_Repository::transaction( function () use ( $clinic_id, $effective_start, $end, $end_ts, $affected, $actor, $clinic ) {\n\t\t\t$record=self::put_record('F08-FUT-17',array(\n\t\t\t\t'clinic_id'=>$clinic_id,'status'=>'disruption_active','starts_at'=>$effective_start,'ends_at'=>$end,\n\t\t\t\t'expires_at'=>gmdate('Y-m-d H:i:s',$end_ts+DAY_IN_SECONDS),\n\t\t\t\t'payload'=>array('reason_code'=>sanitize_key($data['reason_code'] ?? 'operational_delay'),'rebooking_mode'=>'offer_only','auto_cancel'=>false,'affected_count'=>count($affected),'affected_states'=>array('requested','confirmed','reschedule_pending'),'past_appointments_excluded'=>true),\n\t\t\t),$actor);\n\t\t\tif(is_wp_error($record)){return $record;}\n\t\t\t$trace=WCA_Observability::trace_id();\n\t\t\tforeach($affected as $appointment_id){\n\t\t\t\t$recipients=array_values(array_unique(array_filter(array(\n\t\t\t\t\tabsint(SWC_Helpers::meta($appointment_id,'patient_user_id',get_post_field('post_author',$appointment_id))),\n\t\t\t\t\tabsint(SWC_Helpers::meta($appointment_id,'guardian_user_id',0)),\n\t\t\t\t\tabsint(SWC_Helpers::meta($appointment_id,'doctor_id',0)),\n\t\t\t\t))));\n\t\t\t\t$queued=WCA_Repository::enqueue('File19.NotificationRequested.v1',self::appointment_ref($appointment_id),array(\n\t\t\t\t\t'event'=>'clinic_disruption','appointment_ref'=>self::appointment_ref($appointment_id),'clinic_ref'=>(string)$clinic['public_ref'],\n\t\t\t\t\t'disruption_ref'=>$record['public_ref'],'recipients'=>$recipients,'delivery_owner'=>'File19','auto_cancel'=>false,\n\t\t\t\t),$trace);\n\t\t\t\tif(is_wp_error($queued)){return $queued;}\n\t\t\t}\n\t\t\t$record['affected_count']=count($affected); return $record;\n\t\t}, 'wca_disruption_transaction' );"""
    # callback needs original request data to preserve reason_code
    new = new.replace("function () use ( $clinic_id, $effective_start, $end, $end_ts, $affected, $actor, $clinic )", "function () use ( $clinic_id, $effective_start, $end, $end_ts, $affected, $actor, $clinic, $data )")
    path.write_text(s[:m.start()] + new + s[m.end():])
    add_test('R20 disruption transaction', ('includes/class-wca-future24.php', 'wca_disruption_transaction'))
    add_test('R20 disruption enqueue fail closed', ('includes/class-wca-future24.php', 'if(is_wp_error($queued)){return $queued;}'))
    print('Applied T12-R20 atomic disruption/File19 projection correction')

mode = sys.argv[1] if len(sys.argv) > 1 else ''
if mode == '19':
    round19()
elif mode == '20':
    round20()
else:
    raise SystemExit('usage: t12-r19-r20-final.py 19|20')
