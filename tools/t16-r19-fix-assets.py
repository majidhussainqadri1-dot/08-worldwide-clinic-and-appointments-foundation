from pathlib import Path

def replace_once(path, old, new):
    p=Path(path); s=p.read_text()
    if s.count(old)!=1:
        raise SystemExit(f'{path}: anchor count {s.count(old)} for {old[:120]!r}')
    p.write_text(s.replace(old,new,1))

# Canonical assets: File 08 routes or explicit File 08 shortcodes only.
p=Path('includes/class-wca-plugin.php'); s=p.read_text()
old="\t\twp_register_script( 'wca-clinic', WCA_URL . 'assets/js/clinic.js', array(), WCA_VERSION, true );"
new="\t\twp_register_script( 'wca-clinic', WCA_URL . 'assets/js/clinic.js', array( 'wp-i18n' ), WCA_VERSION, true );\n\t\twp_set_script_translations( 'wca-clinic', 'worldwide-clinic-appointments', WCA_DIR . 'languages' );"
if s.count(old)!=1: raise SystemExit('WCA register script anchor mismatch')
s=s.replace(old,new,1)
old="""\tpublic static function assets() {
\t\tif ( ! WCA_Routes::route() && ! is_singular() ) { return; }
\t\twp_enqueue_style( 'wca-clinic' );"""
new="""\tpublic static function assets() {
\t\tglobal $post;
\t\t$needed = (bool) WCA_Routes::route();
\t\tif ( ! $needed && $post instanceof WP_Post ) {
\t\t\tforeach ( array( 'wca_clinic', 'wca_appointments', 'wca_clinic_dashboard', 'wca_future24_center', 'wca_previsit_intake', 'wca_followup_plan' ) as $shortcode ) {
\t\t\t\tif ( has_shortcode( (string) $post->post_content, $shortcode ) ) { $needed = true; break; }
\t\t\t}
\t\t}
\t\tif ( ! $needed ) { return; }
\t\twp_enqueue_style( 'wca-clinic' );"""
if s.count(old)!=1: raise SystemExit('WCA assets scope anchor mismatch')
s=s.replace(old,new,1)
old="\t\t\t'nonce'     => wp_create_nonce( 'wp_rest' ),"
new="\t\t\t'nonce'     => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',"
if s.count(old)!=1: raise SystemExit('WCA localized nonce anchor mismatch')
s=s.replace(old,new,1)
p.write_text(s)

# Legacy File 08 surfaces use the same client and must load the translation runtime too.
p=Path('includes/class-swc-plugin.php'); s=p.read_text()
old="\t\twp_enqueue_script( 'swc-clinic', SWC_URL . 'assets/js/clinic.js', array(), SWC_VERSION, true );"
new="\t\twp_enqueue_script( 'swc-clinic', SWC_URL . 'assets/js/clinic.js', array( 'wp-i18n' ), SWC_VERSION, true );\n\t\twp_set_script_translations( 'swc-clinic', 'worldwide-clinic-appointments', SWC_DIR . 'languages' );"
if s.count(old)!=1: raise SystemExit('SWC script dependency anchor mismatch')
s=s.replace(old,new,1)
p.write_text(s)

# Continuity client translation runtime.
p=Path('includes/class-wca-continuity-secure.php'); s=p.read_text()
old="\t\twp_enqueue_script( 'wca-continuity', WCA_URL . 'assets/js/continuity.js', array(), WCA_VERSION, true );\n\t\twp_localize_script( 'wca-continuity', 'WCAContinuity', array( 'root' => esc_url_raw( rest_url( 'wca/v1/continuity/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );"
new="\t\twp_enqueue_script( 'wca-continuity', WCA_URL . 'assets/js/continuity.js', array( 'wp-i18n' ), WCA_VERSION, true );\n\t\twp_set_script_translations( 'wca-continuity', 'worldwide-clinic-appointments', WCA_DIR . 'languages' );\n\t\twp_localize_script( 'wca-continuity', 'WCAContinuity', array( 'root' => esc_url_raw( rest_url( 'wca/v1/continuity/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );"
if s.count(old)!=1: raise SystemExit('continuity asset anchor mismatch')
s=s.replace(old,new,1)
p.write_text(s)

# Future24 client translation runtime.
p=Path('includes/class-wca-future24.php'); s=p.read_text()
old="\t\twp_register_script( 'wca-future24', WCA_URL . 'assets/js/future24.js', array(), WCA_VERSION, true );"
new="\t\twp_register_script( 'wca-future24', WCA_URL . 'assets/js/future24.js', array( 'wp-i18n' ), WCA_VERSION, true );\n\t\twp_set_script_translations( 'wca-future24', 'worldwide-clinic-appointments', WCA_DIR . 'languages' );"
if s.count(old)!=1: raise SystemExit('Future24 script dependency anchor mismatch')
s=s.replace(old,new,1)
p.write_text(s)
