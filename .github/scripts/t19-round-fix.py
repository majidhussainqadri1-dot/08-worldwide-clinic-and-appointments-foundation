from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ROUND = 'R9'

def replace_once(path, old, new):
    p = ROOT / path
    text = p.read_text(encoding='utf-8')
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{ROUND} {path}: expected exactly one match, found {count}')
    p.write_text(text.replace(old, new, 1), encoding='utf-8')

# R9-D1: patient list is patient/guardian audience only; doctor/staff use clinic schedule.
q = 'includes/class-wca-query-api.php'
replace_once(q,
"""\t * Query contract: own participant appointments plus current appointment-scope delegations.\n\t * Output is an opaque, minimum-detail projection with a signed keyset cursor.\n""",
"""\t * Query contract: the current user's own patient appointments plus appointments\n\t * for which that user is the recorded guardian and the current relationship recheck passes.\n\t * Doctor/staff operational views belong to list_clinic_schedule(), not this patient surface.\n\t * Output is an opaque, minimum-detail projection with a signed keyset cursor.\n""")
replace_once(q,
"""\t\t$delegated = WCA_Authorization::delegated_clinic_ids( $actor_user_id, 'appointments' );\n\t\t$rows = self::query_candidate_appointments( $actor_user_id, 0, $delegated, $cursor, $per_page + 1 );\n""",
"""\t\t$rows = self::query_candidate_appointments( $actor_user_id, 0, array(), $cursor, $per_page + 1 );\n""")
replace_once(q,
"""\t\t\t$relations = array(\n\t\t\t\t\"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pa WHERE pa.post_id=p.ID AND pa.meta_key='_swc_patient_user_id' AND CAST(pa.meta_value AS UNSIGNED)=%d)\",\n\t\t\t\t\"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pd WHERE pd.post_id=p.ID AND pd.meta_key='_swc_doctor_id' AND CAST(pd.meta_value AS UNSIGNED)=%d)\",\n\t\t\t\t\"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pg WHERE pg.post_id=p.ID AND pg.meta_key='_swc_guardian_user_id' AND CAST(pg.meta_value AS UNSIGNED)=%d)\",\n\t\t\t);\n\t\t\t$params[] = $actor_user_id; $params[] = $actor_user_id; $params[] = $actor_user_id;\n\t\t\t$delegated_clinic_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $delegated_clinic_ids ) ) ) );\n\t\t\tif ( $delegated_clinic_ids ) {\n\t\t\t\t$placeholders = implode( ',', array_fill( 0, count( $delegated_clinic_ids ), '%d' ) );\n\t\t\t\t$relations[] = \"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pc WHERE pc.post_id=p.ID AND pc.meta_key='_swc_clinic_id' AND CAST(pc.meta_value AS UNSIGNED) IN ({$placeholders}))\";\n\t\t\t\tforeach ( $delegated_clinic_ids as $delegated_id ) { $params[] = $delegated_id; }\n\t\t\t}\n\t\t\t$where[] = '(' . implode( ' OR ', $relations ) . ')';\n""",
"""\t\t\t$relations = array(\n\t\t\t\t\"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pa WHERE pa.post_id=p.ID AND pa.meta_key='_swc_patient_user_id' AND CAST(pa.meta_value AS UNSIGNED)=%d)\",\n\t\t\t\t\"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pg WHERE pg.post_id=p.ID AND pg.meta_key='_swc_guardian_user_id' AND CAST(pg.meta_value AS UNSIGNED)=%d)\",\n\t\t\t);\n\t\t\t$params[] = $actor_user_id; $params[] = $actor_user_id;\n\t\t\t$where[] = '(' . implode( ' OR ', $relations ) . ')';\n""")

# R9-D2: server-rendered patient route consumes the canonical cursor query instead of pre-auth WP pagination.
f = 'includes/class-wca-frontend.php'
old_start = "\tprivate static function appointments() {\n"
old_end = "\n\tprivate static function appointment( $public_ref ) {"
p = ROOT / f
text = p.read_text(encoding='utf-8')
start = text.find(old_start)
end = text.find(old_end, start)
if start < 0 or end < 0:
    raise SystemExit('R9 frontend appointments method boundaries not found')
new_method = r'''\tprivate static function appointments() {
\t\tif ( ! is_user_logged_in() ) { return self::notice( __( 'Sign in to view appointments.', 'worldwide-clinic-appointments' ), 'warning' ); }
\t\t$user_id = get_current_user_id();
\t\t$cursor = sanitize_text_field( wp_unslash( $_GET['wca_cursor'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed read-only cursor.
\t\t$result = WCA_Query_API::list_patient_appointments( $user_id, array( 'cursor' => $cursor, 'per_page' => 30 ) );
\t\tif ( is_wp_error( $result ) ) { return self::notice( __( 'Current appointment data is temporarily unavailable or you no longer have access.', 'worldwide-clinic-appointments' ), 'error' ); }
\t\t$items = (array) ( $result['items'] ?? array() );
\t\tob_start(); ?>
\t\t<main class="wca-shell" aria-labelledby="wca-appts-title"><h1 id="wca-appts-title"><?php esc_html_e( 'My appointments', 'worldwide-clinic-appointments' ); ?></h1>
\t\t<?php if ( ! $items ) : ?><p><?php esc_html_e( 'No appointments found.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
\t\t<div class="wca-list"><?php foreach ( $items as $item ) { echo self::appointment_projection_card( $item ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } ?></div>
\t\t<?php if ( ! empty( $result['next_cursor'] ) ) : $next = add_query_arg( 'wca_cursor', $result['next_cursor'], home_url( '/appointments/' ) ); ?><nav class="wca-pagination" aria-label="<?php esc_attr_e( 'Appointment pages', 'worldwide-clinic-appointments' ); ?>"><a class="wca-button wca-button-secondary" href="<?php echo esc_url( $next ); ?>"><?php esc_html_e( 'Next appointments', 'worldwide-clinic-appointments' ); ?></a></nav><?php endif; ?>
\t\t</main>
\t\t<?php return ob_get_clean();
\t}

\tprivate static function appointment_projection_card( $item ) {
\t\t$item = is_array( $item ) ? $item : array();
\t\t$ref = strtolower( sanitize_text_field( (string) ( $item['public_ref'] ?? '' ) ) );
\t\tif ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) { return ''; }
\t\t$status = sanitize_key( (string) ( $item['status'] ?? '' ) );
\t\t$when = sanitize_text_field( (string) ( $item['scheduled_at_utc'] ?? '' ) );
\t\t$version = absint( $item['record_version'] ?? 0 );
\t\t$actions = array_values( array_filter( array_map( 'sanitize_key', (array) ( $item['allowed_actions'] ?? array() ) ) ) );
\t\tob_start(); ?>
\t\t<article class="wca-card wca-appointment" data-wca-appointment-ref="<?php echo esc_attr( $ref ); ?>" data-wca-version="<?php echo esc_attr( $version ); ?>" data-wca-status="<?php echo esc_attr( $status ); ?>">
\t\t\t<header><h2><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></h2><p><time datetime="<?php echo esc_attr( $when ? gmdate( 'c', strtotime( $when . ' UTC' ) ) : '' ); ?>"><?php echo esc_html( $when ? get_date_from_gmt( $when, 'F j, Y g:i a' ) : __( 'Time pending', 'worldwide-clinic-appointments' ) ); ?></time></p></header>
\t\t\t<div class="wca-actions"><?php foreach ( $actions as $action ) : ?><button type="button" class="wca-button wca-button-secondary" data-wca-transition="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $action ) ) ); ?></button><?php endforeach; ?><a class="wca-button wca-button-secondary" href="<?php echo esc_url( home_url( '/appointment/' . rawurlencode( $ref ) . '/' ) ); ?>"><?php esc_html_e( 'View details', 'worldwide-clinic-appointments' ); ?></a><a class="wca-button wca-button-secondary" href="<?php echo esc_url( rest_url( 'wca/v1/appointment-refs/' . rawurlencode( $ref ) . '/calendar.ics' ) ); ?>"><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></a></div>
\t\t\t<p data-wca-status role="status" aria-live="polite"></p>
\t\t</article>
\t\t<?php return ob_get_clean();
\t}
'''.replace('\\t','\t')
p.write_text(text[:start] + new_method + text[end:], encoding='utf-8')

# R9-D3: purpose-limited institutional administrator must pass the opaque transition edge.
o = 'includes/class-wca-opaque-api.php'
replace_once(o,
"\t\t$access = self::appointment_access( $id );\n\t\tif ( is_wp_error( $access ) ) { return $access; }\n\t\t$data = self::data( $request );\n",
"\t\t$purpose = user_can( get_current_user_id(), 'manage_worldwide_clinic' ) ? 'operations' : '';\n\t\t$access = self::appointment_access( $id, $purpose );\n\t\tif ( is_wp_error( $access ) ) { return $access; }\n\t\t$data = self::data( $request );\n")

# Permanent R9 regression gate.
test = ROOT / 'tests/t19-r9-participant-query-authority-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ );
$q = file_get_contents( $root . '/includes/class-wca-query-api.php' );
$f = file_get_contents( $root . '/includes/class-wca-frontend.php' );
$o = file_get_contents( $root . '/includes/class-wca-opaque-api.php' );
$fail = array(); $pass = 0;
function r9ok( $label, $ok ) { global $fail, $pass; if ( $ok ) { echo 'PASS ' . (++$pass) . ': ' . $label . "\n"; } else { $fail[] = $label; } }
$patient_start = strpos( $q, 'public static function list_patient_appointments' );
$patient_end = strpos( $q, 'public static function list_clinic_schedule', $patient_start );
$patient = false !== $patient_start && false !== $patient_end ? substr( $q, $patient_start, $patient_end - $patient_start ) : '';
$candidate_start = strpos( $q, 'private static function query_candidate_appointments' );
$candidate_end = strpos( $q, 'private static function appointment_projection', $candidate_start );
$candidate = false !== $candidate_start && false !== $candidate_end ? substr( $q, $candidate_start, $candidate_end - $candidate_start ) : '';
$frontend_start = strpos( $f, 'private static function appointments()' );
$frontend_end = strpos( $f, 'private static function appointment( $public_ref )', $frontend_start );
$frontend = false !== $frontend_start && false !== $frontend_end ? substr( $f, $frontend_start, $frontend_end - $frontend_start ) : '';
r9ok( 'patient list no longer loads delegated clinic scope', false === strpos( $patient, 'delegated_clinic_ids' ) );
r9ok( 'patient candidate relation excludes doctor assignment', false === strpos( $candidate, "meta_key='_swc_doctor_id'" ) );
r9ok( 'patient candidate relation excludes delegated clinic expansion', false === strpos( $candidate, 'delegated_clinic_ids = array_values' ) );
r9ok( 'patient route consumes canonical cursor query', false !== strpos( $frontend, 'WCA_Query_API::list_patient_appointments' ) );
r9ok( 'patient route no longer pre-paginates a direct WP_Query', false === strpos( $frontend, 'new WP_Query' ) );
r9ok( 'patient route renders canonical projection cards', false !== strpos( $frontend, 'appointment_projection_card' ) );
r9ok( 'opaque transition supplies operations purpose for institutional admin', false !== strpos( $o, "user_can( get_current_user_id(), 'manage_worldwide_clinic' ) ? 'operations' : ''" ) );
r9ok( 'opaque transition precheck passes explicit purpose', false !== strpos( $o, 'self::appointment_access( $id, $purpose )' ) );
if ( $fail ) { fwrite( STDERR, "T19 R9 regression gate failed:\n- " . implode( "\n- ", $fail ) . "\n" ); exit( 1 ); }
echo 'T19 R9 participant/query authority regressions: PASS ' . $pass . '/' . $pass . "\n";
''', encoding='utf-8')

run = ROOT / 'tests/run-all.php'
rt = run.read_text(encoding='utf-8')
needle = "'t19-r8-service-projection-reconciliation-regressions.php' );"
replacement = "'t19-r8-service-projection-reconciliation-regressions.php', 't19-r9-participant-query-authority-regressions.php' );"
if rt.count(needle) != 1:
    raise SystemExit('R9 run-all insertion point not found')
run.write_text(rt.replace(needle, replacement, 1), encoding='utf-8')
print('T19 R9 frozen ledger corrections applied.')
