from pathlib import Path
import runpy

# R9 frozen-ledger correction wrapper: align historical regression probes with the
# governing patient-own-appointments cursor contract after the complete R9 review.
ROOT = Path(__file__).resolve().parents[2]
runpy.run_path(str(ROOT / '.github/scripts/t19-round-fix.py'), run_name='__main__')

p = ROOT / 'tests/new-plan-hardening.php'
text = p.read_text(encoding='utf-8')
old = "'data-consultation-type', 'wca_page', 'View details',"
new = "'data-consultation-type', 'wca_cursor', 'View details',"
if text.count(old) != 1:
    raise SystemExit('R9 legacy frontend pagination assertion marker missing or duplicated')
p.write_text(text.replace(old, new, 1), encoding='utf-8')

p = ROOT / 'tests/ten-review-regressions.php'
text = p.read_text(encoding='utf-8')
old = r'''r10has('delegated appointment list',$frontend,"delegated_clinic_ids( \$user_id, 'appointments' )");'''
new = "r10has('canonical patient appointment list',$frontend,'WCA_Query_API::list_patient_appointments');"
if text.count(old) != 1:
    raise SystemExit('R9 legacy delegated appointment-list assertion marker missing or duplicated')
p.write_text(text.replace(old, new, 1), encoding='utf-8')

# T18 R2 originally asserted that the server-rendered frontend itself performed
# claims/object checks. R9 centralizes those checks in WCA_Query_API.
p = ROOT / 'tests/t18-r2-appointment-list-authorization-regressions.php'
p.write_text(r'''<?php
$root = dirname(__DIR__);
$query = file_get_contents($root . '/includes/class-wca-query-api.php');
$frontend = file_get_contents($root . '/includes/class-wca-frontend.php');
if (!is_string($query) || !is_string($frontend)) { fwrite(STDERR, "T18 R2 source read failed\n"); exit(1); }
$checks = array(
    'appointment list revalidates current claims' => strpos($query, '$claims = WCA_Authorization::claims( $actor_user_id );') !== false,
    'each candidate appointment is reauthorized' => strpos($query, 'WCA_Authorization::can_view_appointment( $id, $actor_user_id )') !== false,
    'stale candidate is skipped after current authorization' => strpos($query, 'if ( is_wp_error( $access ) )') !== false && strpos($query, '$consumed = $row;') !== false,
    'authorization infrastructure failures fail closed' => strpos($query, 'if ( $status >= 500 ) { return $access; }') !== false,
    'frontend consumes canonical authorized query contract' => strpos($frontend, 'WCA_Query_API::list_patient_appointments( $user_id') !== false,
    'frontend no longer duplicates pre-authorization WP pagination' => strpos($frontend, 'new WP_Query') === false,
);
foreach ($checks as $name => $ok) { if (!$ok) { fwrite(STDERR, "T18 R2 FAIL: {$name}\n"); exit(1); } }
echo "T18 R2 appointment-list authorization regressions: PASS\n";
''', encoding='utf-8')

# T19 R4 predated the R9 audience split and intentionally treated doctor/staff
# scope as part of the patient collection. Keep its cursor/security checks while
# asserting the new canonical separation instead.
p = ROOT / 'tests/t19-r4-query-contract-regressions.php'
text = p.read_text(encoding='utf-8')
old = "\t'patient query scopes participant identities' => false !== strpos( $query, \"_swc_patient_user_id\" ) && false !== strpos( $query, \"_swc_doctor_id\" ) && false !== strpos( $query, \"_swc_guardian_user_id\" ),\n\t'patient query includes appointment delegations' => false !== strpos( $query, \"delegated_clinic_ids( \\$actor_user_id, 'appointments' )\" ),"
new = "\t'patient query scopes patient and guardian identities' => false !== strpos( $query, \"_swc_patient_user_id\" ) && false !== strpos( $query, \"_swc_guardian_user_id\" ),\n\t'patient query excludes staff delegation expansion' => false !== strpos( $query, 'query_candidate_appointments( $actor_user_id, 0, array(), $cursor, $per_page + 1 )' ),"
if text.count(old) != 1:
    raise SystemExit('R9 T19 R4 participant/delegation assertion marker missing or duplicated')
p.write_text(text.replace(old, new, 1), encoding='utf-8')

print('R9 historical regression probes aligned to canonical patient/staff cursor and authorization separation.')
