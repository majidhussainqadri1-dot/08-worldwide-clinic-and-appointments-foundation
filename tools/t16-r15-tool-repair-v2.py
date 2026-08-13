from pathlib import Path
p=Path(__file__).resolve().parent/'t16-r15-correct.py'
s=p.read_text()
old="""old=\"\"\"\\t\\t$canonical = WCA_Plan_Guard::canonical_slot_hold( $data, $actor_user_id );\n\\t\\tif ( is_wp_error( $canonical ) ) { return $canonical; }\n\\t\\treturn WCA_Repository::hold_slot( $canonical );\n\"\"\""""
new="""old=\"\"\"\\t\\t$canonical = WCA_Plan_Guard::canonical_slot_hold( $data, $patient_user_id );\n\\t\\tif ( is_wp_error( $canonical ) ) { return $canonical; }\n\\t\\treturn WCA_Repository::hold_slot( $canonical );\n\"\"\""""
if old not in s: raise SystemExit('service anchor repair target not found')
p.write_text(s.replace(old,new,1))
print('R15 correction tool service-hold anchor repaired')
