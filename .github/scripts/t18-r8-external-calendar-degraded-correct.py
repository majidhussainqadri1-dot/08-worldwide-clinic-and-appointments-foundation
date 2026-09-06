from pathlib import Path

root = Path(__file__).resolve().parents[2]
f = root / 'includes/class-wca-future24.php'
s = f.read_text()
old = "\t\tforeach(self::apply_slot_policies((array)($result['slots'] ?? array())) as $slot){if(!self::external_busy_conflict_ref($slot['practitioner_ref'] ?? '',$slot['start_utc'] ?? '',$slot['end_utc'] ?? '')){$items[]=$slot;}}"
new = "\t\tforeach(self::apply_slot_policies((array)($result['slots'] ?? array())) as $slot){$external=self::external_busy_conflict($slot['practitioner_ref'] ?? '',$slot['start_utc'] ?? '',$slot['end_utc'] ?? ''); if(is_wp_error($external)){return $external;} if(!$external){$items[]=$slot;}}"
if old not in s:
    raise SystemExit('R8 smart_find needle missing')
s = s.replace(old, new, 1)
old2 = "\t\tglobal $wpdb; $doctor_id=WCA_Plan_Guard::practitioner_id($practitioner_ref); if(!$doctor_id){return false;} $start=self::utc($start); $end=self::utc($end); if(!$start||!$end||strtotime($end.' UTC')<=strtotime($start.' UTC')){return new WP_Error('wca_external_busy_time_invalid',__('External calendar conflict window is invalid.','worldwide-clinic-appointments'),array('status'=>400));} $table=self::tables()['records'];\n\t\t$busy=$wpdb->get_var($wpdb->prepare(\"SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND subject_user_id=%d AND status='busy' AND expires_at>%s AND starts_at<%s AND ends_at>%s LIMIT 1\",$doctor_id,self::now(),$end,$start));"
new2 = "\t\tglobal $wpdb; $doctor_id=WCA_Plan_Guard::practitioner_id($practitioner_ref); if(!$doctor_id){return false;} $start=self::utc($start); $end=self::utc($end); if(!$start||!$end||strtotime($end.' UTC')<=strtotime($start.' UTC')){return new WP_Error('wca_external_busy_time_invalid',__('External calendar conflict window is invalid.','worldwide-clinic-appointments'),array('status'=>400));} $table=self::tables()['records'];\n\t\t$wpdb->last_error = '';\n\t\t$busy=$wpdb->get_var($wpdb->prepare(\"SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND subject_user_id=%d AND status='busy' AND expires_at>%s AND starts_at<%s AND ends_at>%s LIMIT 1\",$doctor_id,self::now(),$end,$start));"
if old2 not in s:
    raise SystemExit('R8 external conflict needle missing')
s = s.replace(old2, new2, 1)
old3 = "\n\tprivate static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) { $result=self::external_busy_conflict($practitioner_ref,$start,$end); return is_wp_error($result)?true:(bool)$result; }\n"
if old3 not in s:
    raise SystemExit('R8 helper needle missing')
s = s.replace(old3, "\n", 1)
f.write_text(s)

test = root / 'tests/t18-r8-external-calendar-degraded-regressions.php'
test.write_text("""<?php
$root=dirname(__DIR__);
$f=file_get_contents($root.'/includes/class-wca-future24.php');
$checks=array(
 'SMART find propagates external-calendar read failure'=>strpos($f,'if(is_wp_error($external)){return $external;}')!==false,
 'external busy query clears stale DB error'=>strpos($f,"$wpdb" . "->last_error = '';\n\t\t" . "$busy" . "=$wpdb" . "->get_var")!==false,
 'lossy conflict helper removed'=>strpos($f,'external_busy_conflict_ref')===false,
 'canonical external conflict remains fail closed'=>strpos($f,'wca_external_busy_read_failed')!==false,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R8 FAIL: {$n}\n");exit(1);}}
echo "T18 R8 external-calendar degradation regressions: PASS\n";
""")
run = root / 'tests/run-all.php'
r = run.read_text()
needle = "'seventeenth-r20-warning-clean-regressions.php' );"
if needle not in r:
    raise SystemExit('R8 run-all insertion needle missing')
if "'t18-r8-external-calendar-degraded-regressions.php'" not in r:
    r = r.replace(needle, "'seventeenth-r20-warning-clean-regressions.php', 't18-r8-external-calendar-degraded-regressions.php' );", 1)
run.write_text(r)
