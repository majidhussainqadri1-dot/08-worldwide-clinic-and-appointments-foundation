from pathlib import Path
p=Path(__file__).resolve().parent/'t16-r15-correct.py'
s=p.read_text()
old='''old="""\\tprivate static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) {\n\\t\\tglobal $wpdb; $doctor_id=WCA_Plan_Guard::practitioner_id($practitioner_ref); if(!$doctor_id){return false;} $table=self::tables()['records'];\n\\t\\t$busy=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND subject_user_id=%d AND status='busy' AND expires_at>%s AND starts_at<%s AND ends_at>%s LIMIT 1",$doctor_id,self::now(),$end,$start));\n\\t\\tif ( '' !== (string) $wpdb->last_error ) { return true; }\n\\t\\treturn (bool)$busy;\n\\t}\n"""'''
new='''old="""\\tprivate static function external_busy_conflict_ref( $practitioner_ref, $start, $end ) {\n\\t\\tglobal $wpdb; $start=self::utc($start); $end=self::utc($end); $practitioner_ref=sanitize_text_field($practitioner_ref); if(!$practitioner_ref||!$start||!$end){return false;} $table=self::tables()['records'];\n\\t\\t$busy = $wpdb->get_var( $wpdb->prepare( \\"SELECT id FROM {$table} WHERE feature_id='F08-FUT-22' AND parent_ref=%s AND status='busy' AND (expires_at IS NULL OR expires_at>%s) AND starts_at<%s AND ends_at>%s LIMIT 1\\", $practitioner_ref, WCA_Repository::now(), $end, $start ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\\t\\tif ( '' !== (string) $wpdb->last_error ) { return true; }\n\\t\\treturn (bool) $busy;\n\\t}\n"""'''
if old not in s:
    raise SystemExit('repair target not found')
p.write_text(s.replace(old,new,1))
print('R15 correction tool external-busy anchor repaired')
