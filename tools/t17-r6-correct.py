from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

def replace_once(path, old, new, label):
    p = ROOT / path
    s = p.read_text()
    n = s.count(old)
    if n != 1:
        raise SystemExit(f"{label}: expected exactly one match, found {n}")
    p.write_text(s.replace(old, new, 1))
    print(f"patched: {label}")

repo = 'includes/class-wca-repository.php'
svc = 'includes/class-wca-service.php'
test = 'tests/seventeenth-twenty-review-regressions.php'

# Repository-level read failure registration.
replace_once(repo,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d LIMIT 1\", absint( $id ) ), ARRAY_A );\n\t\tif ( ! $row ) { return null; }\n\t\t$row['contacts'] = self::decode( $row['contacts_json'] );",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d LIMIT 1\", absint( $id ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_branch_read_failed', __( 'Branch data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\tif ( ! $row ) { return null; }\n\t\t$row['contacts'] = self::decode( $row['contacts_json'] );",
'branch getter records DB read failure')

replace_once(repo,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d LIMIT 1\", absint( $id ) ), ARRAY_A );\n\t\tif ( ! $row ) { return null; }\n\t\tforeach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) {",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( \"SELECT * FROM {$table} WHERE id=%d LIMIT 1\", absint( $id ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_availability_read_failed', __( 'Availability data could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\tif ( ! $row ) { return null; }\n\t\tforeach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) {",
'availability getter records DB read failure')

replace_once(repo,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );\n\t\treturn $row ?: null;\n\t}\n\n\t/** @return array<string,mixed>|null */\n\tpublic static function get_service_by_ref",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_branch_ref_read_failed', __( 'Branch reference could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\treturn $row ?: null;\n\t}\n\n\t/** @return array<string,mixed>|null */\n\tpublic static function get_service_by_ref",
'branch ref getter records DB read failure')

replace_once(repo,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );\n\t\treturn $row ?: null;\n\t}\n\n\t/** @return array<string,mixed>|null */\n\tpublic static function get_availability_rule_by_ref",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_service_ref_read_failed', __( 'Service reference could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\treturn $row ?: null;\n\t}\n\n\t/** @return array<string,mixed>|null */\n\tpublic static function get_availability_rule_by_ref",
'service ref getter records DB read failure')

replace_once(repo,
"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );\n\t\tif ( ! $row ) { return null; }\n\t\tforeach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) { $row[ $key ] = self::decode( $row[ $key . '_json' ] ); unset( $row[ $key . '_json' ] ); }",
"\t\t$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );\n\t\tif ( null === $row && '' !== (string) $wpdb->last_error ) { self::note_read_error( 'wca_availability_ref_read_failed', __( 'Availability reference could not be read safely.', 'worldwide-clinic-appointments' ) ); }\n\t\tif ( ! $row ) { return null; }\n\t\tforeach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) { $row[ $key ] = self::decode( $row[ $key . '_json' ] ); unset( $row[ $key . '_json' ] ); }",
'availability ref getter records DB read failure')

# Repository persistence roots consume their own authoritative reads.
replace_once(repo,
"\t\t$table  = WCA_Schema::tables()['clinics'];\n\t\t$clinic = self::get_clinic( $clinic_id, false );\n\t\tif ( ! $clinic ) {",
"\t\t$table  = WCA_Schema::tables()['clinics'];\n\t\tself::clear_read_error();\n\t\t$clinic = self::get_clinic( $clinic_id, false );\n\t\t$read_error = self::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\tif ( ! $clinic ) {",
'update clinic consumes authoritative read error')

replace_once(repo,
"\t\tif ( false === $wpdb->insert( $table, $row, array( '%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s' ) ) ) {\n\t\t\treturn new WP_Error( 'wca_clinic_insert', __( 'Clinic could not be created.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\treturn self::get_clinic( (int) $wpdb->insert_id, false );",
"\t\tif ( false === $wpdb->insert( $table, $row, array( '%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s' ) ) ) {\n\t\t\treturn new WP_Error( 'wca_clinic_insert', __( 'Clinic could not be created.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\tself::clear_read_error();\n\t\t$created = self::get_clinic( (int) $wpdb->insert_id, false );\n\t\t$read_error = self::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\treturn $created ?: new WP_Error( 'wca_clinic_readback_missing', __( 'Clinic creation could not be verified after persistence.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'clinic create verifies readback')

replace_once(repo,
"\t\tif ( false === $wpdb->insert( $table, $row ) ) {\n\t\t\treturn new WP_Error( 'wca_branch_insert', __( 'Branch could not be created.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\treturn self::get_branch( (int) $wpdb->insert_id );",
"\t\tif ( false === $wpdb->insert( $table, $row ) ) {\n\t\t\treturn new WP_Error( 'wca_branch_insert', __( 'Branch could not be created.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\tself::clear_read_error();\n\t\t$created = self::get_branch( (int) $wpdb->insert_id );\n\t\t$read_error = self::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\treturn $created ?: new WP_Error( 'wca_branch_readback_missing', __( 'Branch creation could not be verified after persistence.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'branch create verifies readback')

replace_once(repo,
"\t\tif ( $service_id ) {\n\t\t\t$current = self::get_service( $service_id, false );\n\t\t\tif ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {",
"\t\tif ( $service_id ) {\n\t\t\tself::clear_read_error();\n\t\t\t$current = self::get_service( $service_id, false );\n\t\t\t$read_error = self::consume_read_error();\n\t\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\t\tif ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {",
'service persistence consumes current read error')

replace_once(repo,
"\t\t\t$ok = $wpdb->update( $table, $row, array( 'id' => absint( $service_id ), 'version' => absint( $expected_version ) ) );\n\t\t\tif ( ! $ok ) { return new WP_Error( 'wca_service_update', __( 'Service could not be updated.', 'worldwide-clinic-appointments' ) ); }\n\t\t\treturn self::get_service( $service_id, false );",
"\t\t\t$ok = $wpdb->update( $table, $row, array( 'id' => absint( $service_id ), 'version' => absint( $expected_version ) ) );\n\t\t\tif ( ! $ok ) { return new WP_Error( 'wca_service_update', __( 'Service could not be updated.', 'worldwide-clinic-appointments' ) ); }\n\t\t\tself::clear_read_error();\n\t\t\t$updated = self::get_service( $service_id, false );\n\t\t\t$read_error = self::consume_read_error();\n\t\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\t\treturn $updated ?: new WP_Error( 'wca_service_readback_missing', __( 'Service update could not be verified after persistence.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'service update verifies readback')

replace_once(repo,
"\t\tif ( false === $wpdb->insert( $table, $row ) ) {\n\t\t\treturn new WP_Error( 'wca_service_insert', __( 'Service could not be created.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\treturn self::get_service( (int) $wpdb->insert_id, false );",
"\t\tif ( false === $wpdb->insert( $table, $row ) ) {\n\t\t\treturn new WP_Error( 'wca_service_insert', __( 'Service could not be created.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\tself::clear_read_error();\n\t\t$created = self::get_service( (int) $wpdb->insert_id, false );\n\t\t$read_error = self::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\treturn $created ?: new WP_Error( 'wca_service_readback_missing', __( 'Service creation could not be verified after persistence.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'service create verifies readback')

replace_once(repo,
"\t\tif ( $rule_id ) {\n\t\t\t$current = self::get_availability_rule( $rule_id );\n\t\t\tif ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {",
"\t\tif ( $rule_id ) {\n\t\t\tself::clear_read_error();\n\t\t\t$current = self::get_availability_rule( $rule_id );\n\t\t\t$read_error = self::consume_read_error();\n\t\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\t\tif ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {",
'availability persistence consumes current read error')

replace_once(repo,
"\t\t\t$row['version'] = absint( $current['version'] ) + 1;\n\t\t\t$ok = $wpdb->update( $table, $row, array( 'id' => absint( $rule_id ), 'version' => absint( $expected_version ) ) );\n\t\t\treturn $ok ? self::get_availability_rule( $rule_id ) : new WP_Error( 'wca_availability_update', __( 'Availability could not be updated.', 'worldwide-clinic-appointments' ) );",
"\t\t\t$row['version'] = absint( $current['version'] ) + 1;\n\t\t\t$ok = $wpdb->update( $table, $row, array( 'id' => absint( $rule_id ), 'version' => absint( $expected_version ) ) );\n\t\t\tif ( ! $ok ) { return new WP_Error( 'wca_availability_update', __( 'Availability could not be updated.', 'worldwide-clinic-appointments' ) ); }\n\t\t\tself::clear_read_error();\n\t\t\t$updated = self::get_availability_rule( $rule_id );\n\t\t\t$read_error = self::consume_read_error();\n\t\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\t\treturn $updated ?: new WP_Error( 'wca_availability_readback_missing', __( 'Availability update could not be verified after persistence.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'availability update verifies readback')

replace_once(repo,
"\t\tif ( false === $wpdb->insert( $table, $row ) ) {\n\t\t\treturn new WP_Error( 'wca_availability_insert', __( 'Availability could not be saved.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\treturn self::get_availability_rule( (int) $wpdb->insert_id );",
"\t\tif ( false === $wpdb->insert( $table, $row ) ) {\n\t\t\treturn new WP_Error( 'wca_availability_insert', __( 'Availability could not be saved.', 'worldwide-clinic-appointments' ) );\n\t\t}\n\t\tself::clear_read_error();\n\t\t$created = self::get_availability_rule( (int) $wpdb->insert_id );\n\t\t$read_error = self::consume_read_error();\n\t\tif ( is_wp_error( $read_error ) ) { return $read_error; }\n\t\treturn $created ?: new WP_Error( 'wca_availability_readback_missing', __( 'Availability creation could not be verified after persistence.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );",
'availability create verifies readback')

# Service helper for fail-closed authoritative reads.
replace_once(svc,
"\tpublic static function strict_int( $value, $min, $max ) {\n\t\tif ( ! is_int( $value ) && ! is_string( $value ) ) { return null; }\n\t\t$validated = filter_var( $value, FILTER_VALIDATE_INT );\n\t\tif ( false === $validated || $validated < $min || $validated > $max ) { return null; }\n\t\treturn (int) $validated;\n\t}\n",
"\tpublic static function strict_int( $value, $min, $max ) {\n\t\tif ( ! is_int( $value ) && ! is_string( $value ) ) { return null; }\n\t\t$validated = filter_var( $value, FILTER_VALIDATE_INT );\n\t\tif ( false === $validated || $validated < $min || $validated > $max ) { return null; }\n\t\treturn (int) $validated;\n\t}\n\n\t/** Execute one repository read and propagate any registered storage failure. */\n\tprivate static function repository_read( $callback ) {\n\t\tWCA_Repository::clear_read_error();\n\t\t$result = call_user_func( $callback );\n\t\t$read_error = WCA_Repository::consume_read_error();\n\t\treturn is_wp_error( $read_error ) ? $read_error : $result;\n\t}\n",
'add service repository_read helper')

# Clinic roots.
for label, method_marker in [
    ('submit clinic review read', "public static function submit_clinic_for_review"),
    ('activate clinic read', "public static function activate_clinic"),
    ('create branch clinic read', "public static function create_branch"),
    ('save service clinic read', "public static function save_service"),
    ('set availability clinic read', "public static function set_availability"),
]:
    p = ROOT / svc
    s = p.read_text()
    pos = s.find(method_marker)
    if pos < 0:
        raise SystemExit(f'{label}: method not found')
    target = "\t\t$clinic = WCA_Repository::get_clinic( $clinic_id, false );\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }"
    idx = s.find(target, pos)
    next_method = s.find("\n\tpublic static function ", pos + 1)
    if idx < 0 or (next_method >= 0 and idx > next_method):
        raise SystemExit(f'{label}: target not found in method')
    replacement = "\t\t$clinic = self::repository_read( static function () use ( $clinic_id ) { return WCA_Repository::get_clinic( $clinic_id, false ); } );\n\t\tif ( is_wp_error( $clinic ) ) { return $clinic; }\n\t\tif ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }"
    p.write_text(s[:idx] + replacement + s[idx+len(target):])
    print(f'patched: {label}')

# Review/activation list reads.
replace_once(svc,
"\t\tif ( ! WCA_Repository::list_services( $clinic['id'], false ) || ! WCA_Repository::list_branches( $clinic['id'], false ) ) { return new WP_Error( 'wca_clinic_incomplete', __( 'At least one branch and one service are required before review.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }",
"\t\t$services = self::repository_read( static function () use ( $clinic ) { return WCA_Repository::list_services( $clinic['id'], false ); } );\n\t\tif ( is_wp_error( $services ) ) { return $services; }\n\t\t$branches = self::repository_read( static function () use ( $clinic ) { return WCA_Repository::list_branches( $clinic['id'], false ); } );\n\t\tif ( is_wp_error( $branches ) ) { return $branches; }\n\t\tif ( ! $services || ! $branches ) { return new WP_Error( 'wca_clinic_incomplete', __( 'At least one branch and one service are required before review.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }",
'submit review list reads propagate DB failure')

replace_once(svc,
"\t\tif ( ! WCA_Repository::list_services( $clinic['id'], true ) || ! WCA_Repository::list_branches( $clinic['id'], true ) ) { return new WP_Error( 'wca_clinic_incomplete', __( 'At least one active public branch and one active eligible service are required before activation.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }",
"\t\t$services = self::repository_read( static function () use ( $clinic ) { return WCA_Repository::list_services( $clinic['id'], true ); } );\n\t\tif ( is_wp_error( $services ) ) { return $services; }\n\t\t$branches = self::repository_read( static function () use ( $clinic ) { return WCA_Repository::list_branches( $clinic['id'], true ); } );\n\t\tif ( is_wp_error( $branches ) ) { return $branches; }\n\t\tif ( ! $services || ! $branches ) { return new WP_Error( 'wca_clinic_incomplete', __( 'At least one active public branch and one active eligible service are required before activation.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }",
'activate clinic list reads propagate DB failure')

# Post-update clinic readbacks.
replace_once(svc,
"\t\t\t$current = WCA_Repository::get_clinic( $clinic_id, false );\n\t\t\tif ( ! $current ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found after update.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }",
"\t\t\t$current = self::repository_read( static function () use ( $clinic_id ) { return WCA_Repository::get_clinic( $clinic_id, false ); } );\n\t\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\t\tif ( ! $current ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found after update.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }",
'submit review post-update read propagates DB failure')

replace_once(svc,
"\t\t\t$current = WCA_Repository::get_clinic( $clinic_id, false );\n\t\t\tif ( ! $current ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found after activation.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }",
"\t\t\t$current = self::repository_read( static function () use ( $clinic_id ) { return WCA_Repository::get_clinic( $clinic_id, false ); } );\n\t\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\t\tif ( ! $current ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found after activation.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }",
'activate post-update read propagates DB failure')

# Service and branch scope reads.
replace_once(svc,
"\t\tif ( $service_id ) {\n\t\t\t$current = WCA_Repository::get_service( $service_id, false );\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t}",
"\t\tif ( $service_id ) {\n\t\t\t$current = self::repository_read( static function () use ( $service_id ) { return WCA_Repository::get_service( $service_id, false ); } );\n\t\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_service_scope', __( 'The service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t}",
'service scope read propagates DB failure')

replace_once(svc,
"\t\tif ( ! empty( $data['branch_id'] ) ) { $branch = WCA_Repository::get_branch( absint( $data['branch_id'] ) ); if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_branch_scope', __( 'The branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }",
"\t\tif ( ! empty( $data['branch_id'] ) ) {\n\t\t\t$branch_id = absint( $data['branch_id'] );\n\t\t\t$branch = self::repository_read( static function () use ( $branch_id ) { return WCA_Repository::get_branch( $branch_id ); } );\n\t\t\tif ( is_wp_error( $branch ) ) { return $branch; }\n\t\t\tif ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_branch_scope', __( 'The branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t}",
'service branch scope read propagates DB failure')

# Availability ownership reads.
replace_once(svc,
"\t\tif ( $rule_id ) { $current = WCA_Repository::get_availability_rule( $rule_id ); if ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_scope', __( 'The availability rule does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); } }\n\t\tif ( ! empty( $data['service_id'] ) ) { $service = WCA_Repository::get_service( absint( $data['service_id'] ), false ); if ( ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_service', __( 'The availability service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }\n\t\tif ( ! empty( $data['branch_id'] ) ) { $branch = WCA_Repository::get_branch( absint( $data['branch_id'] ) ); if ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_branch', __( 'The availability branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); } }",
"\t\tif ( $rule_id ) {\n\t\t\t$current = self::repository_read( static function () use ( $rule_id ) { return WCA_Repository::get_availability_rule( $rule_id ); } );\n\t\t\tif ( is_wp_error( $current ) ) { return $current; }\n\t\t\tif ( ! $current || absint( $current['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_scope', __( 'The availability rule does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }\n\t\t}\n\t\tif ( ! empty( $data['service_id'] ) ) {\n\t\t\t$availability_service_id = absint( $data['service_id'] );\n\t\t\t$service = self::repository_read( static function () use ( $availability_service_id ) { return WCA_Repository::get_service( $availability_service_id, false ); } );\n\t\t\tif ( is_wp_error( $service ) ) { return $service; }\n\t\t\tif ( ! $service || absint( $service['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_service', __( 'The availability service does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t}\n\t\tif ( ! empty( $data['branch_id'] ) ) {\n\t\t\t$availability_branch_id = absint( $data['branch_id'] );\n\t\t\t$branch = self::repository_read( static function () use ( $availability_branch_id ) { return WCA_Repository::get_branch( $availability_branch_id ); } );\n\t\t\tif ( is_wp_error( $branch ) ) { return $branch; }\n\t\t\tif ( ! $branch || absint( $branch['clinic_id'] ) !== absint( $clinic['id'] ) ) { return new WP_Error( 'wca_availability_branch', __( 'The availability branch does not belong to this clinic.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ); }\n\t\t}",
'availability scope reads propagate DB failure')

# Permanent T17 static regression coverage.
p = ROOT / test
s = p.read_text()
needle = "    'Future24 prerequisites reject malformed rules' => false !== strpos( $future, 'wca_prerequisite_rule_invalid' ) && false !== strpos( $future, 'wca_prerequisite_behavior_invalid' ),\n"
if s.count(needle) != 1:
    raise SystemExit('T17 test insertion anchor mismatch')
addition = needle + "    'R6 service roots use fail-closed repository read helper' => false !== strpos( $service, 'private static function repository_read' ),\n    'R6 clinic lifecycle propagates repository read errors' => substr_count( $service, 'if ( is_wp_error( $clinic ) ) { return $clinic; }' ) >= 5,\n    'R6 review and activation list reads propagate DB failure' => false !== strpos( $service, 'return WCA_Repository::list_services' ) && false !== strpos( $service, 'return WCA_Repository::list_branches' ),\n    'R6 branch getter records storage failure' => false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_branch_read_failed' ),\n    'R6 availability getter records storage failure' => false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_availability_read_failed' ),\n    'R6 repository mutation roots verify readback' => false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_clinic_readback_missing' ) && false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_service_readback_missing' ) && false !== strpos( file_get_contents( $root . '/includes/class-wca-repository.php' ), 'wca_availability_readback_missing' ),\n"
p.write_text(s.replace(needle, addition, 1))
print('patched: permanent T17 R6 regression coverage')
