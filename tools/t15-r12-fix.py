from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
    s=rd(p); n=s.count(a)
    if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
    wr(p,s.replace(a,b,1))

p='includes/class-wca-opaque-api.php'
# Conceal object existence from authenticated non-participants while preserving infrastructure errors.
once(p,"\tpublic static function appointment( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$access = WCA_Authorization::can_view_appointment( $id, 0, sanitize_key( $request->get_header( 'X-WCA-Access-Purpose' ) ) );\n\t\tif ( is_wp_error( $access ) ) { return $access; }","\tpublic static function appointment( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$access = self::appointment_access( $id, sanitize_key( $request->get_header( 'X-WCA-Access-Purpose' ) ) );\n\t\tif ( is_wp_error( $access ) ) { return $access; }")
once(p,"\tpublic static function transition( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$data = self::data( $request );","\tpublic static function transition( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$access = self::appointment_access( $id );\n\t\tif ( is_wp_error( $access ) ) { return $access; }\n\t\t$data = self::data( $request );")
once(p,"\tpublic static function calendar( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$proxy = new WP_REST_Request","\tpublic static function calendar( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$access = self::appointment_access( $id );\n\t\tif ( is_wp_error( $access ) ) { return $access; }\n\t\t$proxy = new WP_REST_Request")
once(p,"\tpublic static function payment_intent( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$proxy = new WP_REST_Request","\tpublic static function payment_intent( WP_REST_Request $request ) {\n\t\t$id = self::appointment_id( $request['ref'] );\n\t\tif ( ! $id ) { return self::not_found(); }\n\t\t$access = self::appointment_access( $id );\n\t\tif ( is_wp_error( $access ) ) { return $access; }\n\t\t$proxy = new WP_REST_Request")
# All opaque-object responses are private by default. Public clinic discovery uses the separate public routes.
once(p,"\t\t$response->header( 'X-WCA-Object-Contract', 'wca.opaque-object-refs/' . self::CONTRACT_VERSION );\n\t\t$response->header( 'X-Request-ID', WCA_Observability::trace_id() );\n\t\treturn $response;","\t\t$response->header( 'X-WCA-Object-Contract', 'wca.opaque-object-refs/' . self::CONTRACT_VERSION );\n\t\t$response->header( 'X-Request-ID', WCA_Observability::trace_id() );\n\t\t$response->header( 'Cache-Control', 'private, no-store, max-age=0' );\n\t\t$response->header( 'Pragma', 'no-cache' );\n\t\t$response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );\n\t\treturn $response;")
once(p,"\tprivate static function appointment_id( $ref ) {","\tprivate static function appointment_access( $id, $purpose = '' ) {\n\t\t$access = WCA_Authorization::can_view_appointment( absint( $id ), 0, sanitize_key( $purpose ) );\n\t\tif ( ! is_wp_error( $access ) ) { return true; }\n\t\t$data = $access->get_error_data();\n\t\t$status = is_array( $data ) ? absint( $data['status'] ?? 0 ) : 0;\n\t\tif ( in_array( $status, array( 401, 403, 404 ), true ) ) { return self::not_found(); }\n\t\treturn $access;\n\t}\n\n\tprivate static function appointment_id( $ref ) {")

# R5's repository strictness was bypassed by application-layer normalization. Preserve caller intent and reject malformed exact codes.
p='includes/class-wca-service.php'
once(p,"\t\t$currency = strtoupper( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['currency'] ?? ( $current['currency'] ?? '' ) ) ) );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) )","\t\t$currency_raw = trim( (string) ( $data['currency'] ?? ( $current['currency'] ?? '' ) ) );\n\t\t$currency = strtoupper( $currency_raw );\n\t\tif ( ! preg_match( '/^[A-Z]{3}$/', $currency ) )")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R12 opaque appointment access concealed','includes/class-wca-opaque-api.php','private static function appointment_access');
t15h('R12 opaque responses no-store','includes/class-wca-opaque-api.php',"header( 'Cache-Control', 'private, no-store, max-age=0' )");
t15h('R12 opaque responses noindex','includes/class-wca-opaque-api.php',"header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' )");
t15h('R12 application currency exact validation','includes/class-wca-service.php','\\$currency_raw = trim');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
wr(p,s.replace(mark,ins+mark,1))

p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R12 — public/private projection, minimization, cache and existence-leak review

R12 completed against the R11-corrected state before any R12 source change. The opaque appointment read route did not itself guarantee private no-store/noindex headers, and an existing-but-unauthorized appointment could return an authorization error distinguishable from a missing opaque reference. The review also caught a remaining application-layer currency normalization path that could transform malformed input before the repository's strict persistence check. The R12 batch makes opaque object responses private/non-indexable, conceals participant-denial existence, and validates exact currency intent at the application root.

R12 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R13.**
"""; wr(p,s)
