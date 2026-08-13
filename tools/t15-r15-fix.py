from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
 wr(p,s.replace(a,b,1))

p='includes/class-wca-repository.php'
once(p,"final class WCA_Repository {\n\tprivate static $transaction_depth = 0;","final class WCA_Repository {\n\tprivate static $transaction_depth = 0;\n\tprivate static $read_error = null;\n\n\tpublic static function clear_read_error() { self::$read_error = null; }\n\tpublic static function consume_read_error() { $error = self::$read_error; self::$read_error = null; return $error; }\n\tprivate static function note_read_error( $code, $message ) { if ( ! self::$read_error ) { self::$read_error = new WP_Error( sanitize_key( $code ), $message, array( 'status' => 503 ) ); } }")
# get_clinic raw read.
once(p,"\t\t$row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( ! $row || ( $public_only && 'active' !== $row['status'] ) ) {","\t\t$row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_clinic_read_failed', __( 'Clinic data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\tif ( ! $row || ( $public_only && 'active' !== $row['status'] ) ) {")
# list clinics raw read.
once(p,"\t\t$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t$rows = (array) $wpdb->get_results( $prepared, ARRAY_A );\n\t\treturn array_map","\t\t$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t$rows_raw = $wpdb->get_results( $prepared, ARRAY_A );\n\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_clinic_list_read_failed', __( 'Clinic discovery data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\t$rows = (array) $rows_raw;\n\t\treturn array_map")
# public branches read.
once(p,"\t\t$sql = \"SELECT * FROM {$table} WHERE clinic_id=%d\" . ( $public_only ? \" AND status='active' AND visibility='public'\" : '' ) . ' ORDER BY name ASC,id ASC';\n\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, absint( $clinic_id ) ), ARRAY_A );\n\t\treturn array_map","\t\t$sql = \"SELECT * FROM {$table} WHERE clinic_id=%d\" . ( $public_only ? \" AND status='active' AND visibility='public'\" : '' ) . ' ORDER BY name ASC,id ASC';\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( $sql, absint( $clinic_id ) ), ARRAY_A );\n\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_branch_list_read_failed', __( 'Clinic branch data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\t$rows = (array) $rows_raw;\n\t\treturn array_map")
# services read before subsequent get_clinic can overwrite DB error state.
once(p,"\t\t$sql = \"SELECT * FROM {$table} WHERE \" . implode( ' AND ', $where ) . ' ORDER BY name ASC,id ASC';\n\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( ! $public_only ) { return $rows; }","\t\t$sql = \"SELECT * FROM {$table} WHERE \" . implode( ' AND ', $where ) . ' ORDER BY name ASC,id ASC';\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_service_list_read_failed', __( 'Clinic service data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\t$rows = (array) $rows_raw;\n\t\tif ( ! $public_only ) { return $rows; }")
# service single read for slot query.
once(p,"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\treturn $row ?: null;","\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_service_read_failed', __( 'Service data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\treturn $row ?: null;")
# availability list read.
once(p,"\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC\", $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\treturn array_map","\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC\", $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\tif ( null === $rows_raw && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_availability_list_read_failed', __( 'Availability data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\t$rows = (array) $rows_raw;\n\t\treturn array_map")

p='includes/class-wca-service.php'
# Strict public projection consumes all nested repository read failures.
once(p,"\tpublic static function public_clinic_projection( $id_or_slug ) {\n\t\t$private = WCA_Repository::get_clinic( $id_or_slug, false );","\tpublic static function public_clinic_projection( $id_or_slug ) {\n\t\tWCA_Repository::clear_read_error();\n\t\t$private = WCA_Repository::get_clinic( $id_or_slug, false );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }")
once(p,"\t\t$clinic = WCA_Repository::get_clinic( $private['id'], true );\n\t\tif ( ! $clinic ) { return array(); }","\t\tWCA_Repository::clear_read_error();\n\t\t$clinic = WCA_Repository::get_clinic( $private['id'], true );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\tif ( ! $clinic ) { return array(); }")
# Slot query: service/availability read failures must never become scope/empty results.
once(p,"\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, true ) : null;\n\t\tif ( $service_id && ( ! $service || ( $clinic_id && absint( $service['clinic_id'] ) !== $clinic_id ) ) ) {","\t\tWCA_Repository::clear_read_error();\n\t\t$service = $service_id ? WCA_Repository::get_service( $service_id, true ) : null;\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\tif ( $service_id && ( ! $service || ( $clinic_id && absint( $service['clinic_id'] ) !== $clinic_id ) ) ) {")
once(p,"\t\t$rules = WCA_Repository::list_availability_rules( $doctor_id, $service_id, $clinic_id );\n\t\tif ( ! $rules ) {","\t\tWCA_Repository::clear_read_error();\n\t\t$rules = WCA_Repository::list_availability_rules( $doctor_id, $service_id, $clinic_id );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\tif ( ! $rules ) {")

p='includes/class-wca-rest.php'
# Public clinic collection must not cache a database failure as an empty successful page.
once(p,"\t\t$rows = WCA_Repository::list_clinics( $args );\n\t\t$items = array();\n\t\tforeach ( $rows as $row ) { $projection = WCA_Service::public_clinic_projection( $row['public_ref'] ); if ( $projection ) { $items[] = $projection; } }","\t\tWCA_Repository::clear_read_error();\n\t\t$rows = WCA_Repository::list_clinics( $args );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\t$items = array();\n\t\tforeach ( $rows as $row ) {\n\t\t\t$projection = WCA_Service::public_clinic_projection( $row['public_ref'] );\n\t\t\tif ( is_wp_error( $projection ) ) { return $projection; }\n\t\t\tif ( $projection ) { $items[] = $projection; }\n\t\t}")
once(p,"\t\t$projection = WCA_Service::public_clinic_projection( $identifier );\n\t\treturn $projection ? self::respond( $projection ) : new WP_Error( 'wca_clinic_not_found'","\t\t$projection = WCA_Service::public_clinic_projection( $identifier );\n\t\tif ( is_wp_error( $projection ) ) { return $projection; }\n\t\treturn $projection ? self::respond( $projection ) : new WP_Error( 'wca_clinic_not_found'")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R15 repository captures clinic discovery read failure','includes/class-wca-repository.php','wca_clinic_list_read_failed');
t15h('R15 repository captures branch read failure','includes/class-wca-repository.php','wca_branch_list_read_failed');
t15h('R15 repository captures service read failure','includes/class-wca-repository.php','wca_service_list_read_failed');
t15h('R15 repository captures availability read failure','includes/class-wca-repository.php','wca_availability_list_read_failed');
t15h('R15 public collection propagates repository read errors','includes/class-wca-rest.php','WCA_Repository::consume_read_error');
t15h('R15 public projection propagates nested read errors','includes/class-wca-service.php','WCA_Repository::consume_read_error');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('T15 gate marker missing')
wr(p,s.replace(mark,ins+mark,1))

p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R15 — public clinic discovery / cursor / cache / slot-search review

R15 completed before correction. Guest rate limiting was verified as per-user/per-IP and the signed keyset cursor matches `updated_at DESC, id DESC`. The supported defect was a repository-read failure family: clinic collection, clinic/branch/service projection and availability-rule reads could flatten SQL failure into empty/null state. Public discovery could therefore cache a false empty 200, return a false 404/partial projection, or slot search could advertise no availability after a database failure. The post-review batch records repository read failures across nested hydration and makes public discovery/projection and slot search propagate them explicitly rather than cache or project false absence.

R15 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R16.**
"""; wr(p,s)
