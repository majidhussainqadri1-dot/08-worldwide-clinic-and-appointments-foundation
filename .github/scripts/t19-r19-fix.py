from pathlib import Path

ROOT = Path(__file__).resolve().parents[2]
ROUND = 'R19'


def replace_once(path, old, new):
    p = ROOT / path
    text = p.read_text(encoding='utf-8')
    count = text.count(old)
    if count != 1:
        raise SystemExit(f'{ROUND} {path}: expected exactly one match, found {count}')
    p.write_text(text.replace(old, new, 1), encoding='utf-8')


# R19-D1/D2/D5 — bound owned collection reads, allow lightweight clinic
# discovery rows, and expose aggregate queue/dead-letter inspection.
repo = 'includes/class-wca-repository.php'
replace_once(
    repo,
    "final class WCA_Repository {\n\tprivate static $transaction_depth = 0;",
    "final class WCA_Repository {\n\tconst MAX_COLLECTION_ROWS = 100;\n\n\tprivate static $transaction_depth = 0;"
)
replace_once(
    repo,
    "public static function get_clinic( $id_or_ref, $public_only = false ) {",
    "public static function get_clinic( $id_or_ref, $public_only = false, $hydrate_children = true ) {"
)
replace_once(
    repo,
    "return self::hydrate_clinic( $row, $public_only );",
    "return self::hydrate_clinic( $row, $public_only, (bool) $hydrate_children );"
)
replace_once(
    repo,
    "\t\t$cursor_id = absint( $args['cursor_id'] ?? 0 );\n\t\t$where    = array( '1=1' );",
    "\t\t$cursor_id = absint( $args['cursor_id'] ?? 0 );\n\t\t$hydrate_children = ! array_key_exists( 'hydrate_children', $args ) || (bool) $args['hydrate_children'];\n\t\t$where    = array( '1=1' );"
)
replace_once(
    repo,
    "return array_map( static function ( $row ) use ( $status ) { return self::hydrate_clinic( $row, 'active' === $status ); }, $rows );",
    "return array_map( static function ( $row ) use ( $status, $hydrate_children ) { return self::hydrate_clinic( $row, 'active' === $status, $hydrate_children ); }, $rows );"
)
replace_once(
    repo,
    "\tpublic static function list_branches( $clinic_id, $public_only = false ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['branches'];\n\t\t$sql = \"SELECT * FROM {$table} WHERE clinic_id=%d\" . ( $public_only ? \" AND status='active' AND visibility='public'\" : '' ) . ' ORDER BY name ASC,id ASC';\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( $sql, absint( $clinic_id ) ), ARRAY_A );",
    "\tpublic static function list_branches( $clinic_id, $public_only = false, $limit = self::MAX_COLLECTION_ROWS ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['branches'];\n\t\t$limit = min( self::MAX_COLLECTION_ROWS, max( 1, absint( $limit ) ) );\n\t\t$sql = \"SELECT * FROM {$table} WHERE clinic_id=%d\" . ( $public_only ? \" AND status='active' AND visibility='public'\" : '' ) . ' ORDER BY name ASC,id ASC LIMIT %d';\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( $sql, array( absint( $clinic_id ), $limit ) ), ARRAY_A );"
)
replace_once(
    repo,
    "\tpublic static function list_services( $clinic_id, $public_only = true, $doctor_user_id = 0 ) {\n\t\tglobal $wpdb;\n\t\t$table  = WCA_Schema::tables()['services'];\n\t\t$where  = array( 'clinic_id=%d' );\n\t\t$params = array( absint( $clinic_id ) );",
    "\tpublic static function list_services( $clinic_id, $public_only = true, $doctor_user_id = 0, $limit = self::MAX_COLLECTION_ROWS ) {\n\t\tglobal $wpdb;\n\t\t$table  = WCA_Schema::tables()['services'];\n\t\t$limit  = min( self::MAX_COLLECTION_ROWS, max( 1, absint( $limit ) ) );\n\t\t$where  = array( 'clinic_id=%d' );\n\t\t$params = array( absint( $clinic_id ) );"
)
replace_once(
    repo,
    "\t\t$sql = \"SELECT * FROM {$table} WHERE \" . implode( ' AND ', $where ) . ' ORDER BY name ASC,id ASC';\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );",
    "\t\t$sql = \"SELECT * FROM {$table} WHERE \" . implode( ' AND ', $where ) . ' ORDER BY name ASC,id ASC LIMIT %d';\n\t\t$params[] = $limit;\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A );"
)
replace_once(
    repo,
    "\tpublic static function list_availability_rules( $doctor_user_id, $service_id = 0, $clinic_id = 0 ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['availability'];\n\t\t$where = 'doctor_user_id=%d AND status=%s';\n\t\t$params = array( absint( $doctor_user_id ), 'active' );",
    "\tpublic static function list_availability_rules( $doctor_user_id, $service_id = 0, $clinic_id = 0, $limit = self::MAX_COLLECTION_ROWS ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['availability'];\n\t\t$limit = min( self::MAX_COLLECTION_ROWS, max( 1, absint( $limit ) ) );\n\t\t$where = 'doctor_user_id=%d AND status=%s';\n\t\t$params = array( absint( $doctor_user_id ), 'active' );"
)
replace_once(
    repo,
    "\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC\", $params ), ARRAY_A );",
    "\t\t$params[] = $limit;\n\t\t$rows_raw = $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC LIMIT %d\", $params ), ARRAY_A );"
)
replace_once(
    repo,
    "\tprivate static function hydrate_clinic( $row, $public_only ) {\n\t\t$row['languages'] = self::decode( $row['languages_json'] );\n\t\t$row['contacts']  = self::decode( $row['contacts_json'] );\n\t\t$row['policies']  = self::decode( $row['policies_json'] );\n\t\tunset( $row['languages_json'], $row['contacts_json'], $row['policies_json'] );\n\t\t$row['branches'] = self::list_branches( $row['id'], $public_only );\n\t\t$row['services'] = self::list_services( $row['id'], $public_only );",
    "\tprivate static function hydrate_clinic( $row, $public_only, $hydrate_children = true ) {\n\t\t$row['languages'] = self::decode( $row['languages_json'] );\n\t\t$row['contacts']  = self::decode( $row['contacts_json'] );\n\t\t$row['policies']  = self::decode( $row['policies_json'] );\n\t\tunset( $row['languages_json'], $row['contacts_json'], $row['policies_json'] );\n\t\t$row['branches'] = $hydrate_children ? self::list_branches( $row['id'], $public_only ) : array();\n\t\t$row['services'] = $hydrate_children ? self::list_services( $row['id'], $public_only ) : array();"
)

queue_method = r'''
	/** Aggregate bounded operator view of current outbox state. @return array<string,mixed>|WP_Error */
	public static function outbox_queue_status() {
		global $wpdb;
		$table = WCA_Schema::tables()['outbox'];
		$wpdb->last_error = '';
		$rows = $wpdb->get_results( "SELECT status,COUNT(*) AS total FROM {$table} WHERE status IN ('pending','retry','processing','dead_letter') GROUP BY status", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $rows || '' !== (string) $wpdb->last_error ) {
			return new WP_Error( 'wca_outbox_status_read_failed', __( 'Outbox queue state could not be inspected safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		$counts = array( 'pending' => 0, 'retry' => 0, 'processing' => 0, 'dead_letter' => 0 );
		foreach ( (array) $rows as $row ) {
			$status = sanitize_key( $row['status'] ?? '' );
			if ( array_key_exists( $status, $counts ) ) { $counts[ $status ] = absint( $row['total'] ?? 0 ); }
		}
		$now = self::now();
		$wpdb->last_error = '';
		$due = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS total,MIN(next_attempt_at) AS oldest FROM {$table} WHERE status IN ('pending','retry') AND next_attempt_at<=%s", $now ), ARRAY_A );
		if ( null === $due || '' !== (string) $wpdb->last_error ) {
			return new WP_Error( 'wca_outbox_due_read_failed', __( 'Due outbox work could not be inspected safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		return array_merge( $counts, array(
			'due'           => absint( $due['total'] ?? 0 ),
			'oldest_due_at' => sanitize_text_field( (string) ( $due['oldest'] ?? '' ) ),
			'db_read_ok'    => true,
			'healthy'       => 0 === $counts['dead_letter'],
		) );
	}

'''
replace_once(
    repo,
    "\t/** @return array<int,array<string,mixed>>|WP_Error */\n\tpublic static function claim_outbox( $limit = 20, $worker = '' ) {",
    queue_method + "\t/** @return array<int,array<string,mixed>>|WP_Error */\n\tpublic static function claim_outbox( $limit = 20, $worker = '' ) {"
)

# R19-D2 — collection discovery gets lightweight rows; projection does not
# hydrate private child collections before it actually needs private services.
rest = 'includes/class-wca-rest.php'
replace_once(
    rest,
    "\t\t\t'per_page'     => min( 50, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) ),\n\t\t);",
    "\t\t\t'per_page'     => min( 50, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) ),\n\t\t\t'hydrate_children' => false,\n\t\t);"
)
service = 'includes/class-wca-service.php'
replace_once(
    service,
    "\t\t$private = WCA_Repository::get_clinic( $id_or_slug, false );",
    "\t\t$private = WCA_Repository::get_clinic( $id_or_slug, false, false );"
)
replace_once(
    service,
    "\t\tif ( ! $owner_id || ! SWC_Doctor_Authority::is_eligible( $owner_id ) ) { return array(); }\n\t\tWCA_Repository::clear_read_error();",
    "\t\tif ( ! $owner_id || ! SWC_Doctor_Authority::is_eligible( $owner_id ) ) { return array(); }\n\t\t$private_services = self::repository_read( static function () use ( $private ) { return WCA_Repository::list_services( $private['id'], false ); } );\n\t\tif ( is_wp_error( $private_services ) ) { return $private_services; }\n\t\tWCA_Repository::clear_read_error();"
)
replace_once(
    service,
    "\t\tforeach ( (array) ( $private['services'] ?? array() ) as $private_service ) {",
    "\t\tforeach ( (array) $private_services as $private_service ) {"
)

# R19-D3/D4 — attach Operations to the visible File 08 admin menu and make
# the protected retry operation visible with truthful result handling.
admin = 'includes/class-wca-admin.php'
replace_once(
    admin,
    "add_submenu_page( 'edit.php?post_type=' . SWC_Helpers::TYPE, __( 'File 08 Operations', 'worldwide-clinic-appointments' ), __( 'Operations', 'worldwide-clinic-appointments' ), 'manage_worldwide_clinic', 'wca-operations', array( __CLASS__, 'page' ) );",
    "add_submenu_page( 'clinic-management', __( 'File 08 Operations', 'worldwide-clinic-appointments' ), __( 'Operations', 'worldwide-clinic-appointments' ), 'manage_worldwide_clinic', 'wca-operations', array( __CLASS__, 'page' ) );"
)
old_admin_block = "\t\t\t<h2><?php esc_html_e( 'Dependencies', 'worldwide-clinic-appointments' ); ?></h2><table class=\"widefat striped\"><thead><tr><th><?php esc_html_e( 'Module', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Required', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'State', 'worldwide-clinic-appointments' ); ?></th></tr></thead><tbody><?php foreach ( $deps as $name => $state ) : ?><tr><td><?php echo esc_html( strtoupper( $name ) ); ?></td><td><?php echo $state['required'] ? esc_html__( 'Yes', 'worldwide-clinic-appointments' ) : esc_html__( 'Conditional', 'worldwide-clinic-appointments' ); ?></td><td><?php echo $state['ready'] ? esc_html__( 'Available', 'worldwide-clinic-appointments' ) : esc_html__( 'Unavailable', 'worldwide-clinic-appointments' ); ?></td></tr><?php endforeach; ?></tbody></table>\n\t\t\t<form method=\"post\" action=\"<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>\"><?php wp_nonce_field( 'wca_run_maintenance' ); ?><input type=\"hidden\" name=\"action\" value=\"wca_run_maintenance\"><p><button class=\"button button-primary\"><?php esc_html_e( 'Run maintenance and outbox', 'worldwide-clinic-appointments' ); ?></button></p></form>"
new_admin_block = "\t\t\t<h2><?php esc_html_e( 'Dependencies', 'worldwide-clinic-appointments' ); ?></h2><table class=\"widefat striped\"><thead><tr><th><?php esc_html_e( 'Module', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Required', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'State', 'worldwide-clinic-appointments' ); ?></th></tr></thead><tbody><?php foreach ( $deps as $name => $state ) : ?><tr><td><?php echo esc_html( strtoupper( $name ) ); ?></td><td><?php echo $state['required'] ? esc_html__( 'Yes', 'worldwide-clinic-appointments' ) : esc_html__( 'Conditional', 'worldwide-clinic-appointments' ); ?></td><td><?php echo $state['ready'] ? esc_html__( 'Available', 'worldwide-clinic-appointments' ) : esc_html__( 'Unavailable', 'worldwide-clinic-appointments' ); ?></td></tr><?php endforeach; ?></tbody></table>\n\t\t\t<?php $queue = isset( $health['outbox_queue'] ) && is_array( $health['outbox_queue'] ) ? $health['outbox_queue'] : array(); ?>\n\t\t\t<h2><?php esc_html_e( 'Outbox queue', 'worldwide-clinic-appointments' ); ?></h2><table class=\"widefat striped\"><tbody>\n\t\t\t<?php foreach ( array( 'pending' => 'Pending', 'retry' => 'Retry', 'processing' => 'Processing', 'dead_letter' => 'Dead letter', 'due' => 'Due now' ) as $key => $label ) : ?><tr><th><?php echo esc_html( $label ); ?></th><td><?php echo esc_html( (string) absint( $queue[ $key ] ?? 0 ) ); ?></td></tr><?php endforeach; ?>\n\t\t\t<tr><th><?php esc_html_e( 'Oldest due', 'worldwide-clinic-appointments' ); ?></th><td><?php echo esc_html( (string) ( $queue['oldest_due_at'] ?? '' ) ); ?></td></tr></tbody></table>\n\t\t\t<?php if ( isset( $_GET['wca_outbox'] ) ) : $outbox_state = sanitize_key( wp_unslash( $_GET['wca_outbox'] ) ); ?><p class=\"notice <?php echo 'done' === $outbox_state ? 'notice-success' : 'notice-error'; ?> inline\"><?php echo 'done' === $outbox_state ? esc_html__( 'Due outbox processing completed.', 'worldwide-clinic-appointments' ) : esc_html__( 'Due outbox processing failed. Review the privacy-safe system log and queue state.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>\n\t\t\t<form method=\"post\" action=\"<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>\"><?php wp_nonce_field( 'wca_retry_outbox' ); ?><input type=\"hidden\" name=\"action\" value=\"wca_retry_outbox\"><p><button class=\"button\"><?php esc_html_e( 'Process due outbox', 'worldwide-clinic-appointments' ); ?></button></p></form>\n\t\t\t<form method=\"post\" action=\"<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>\"><?php wp_nonce_field( 'wca_run_maintenance' ); ?><input type=\"hidden\" name=\"action\" value=\"wca_run_maintenance\"><p><button class=\"button button-primary\"><?php esc_html_e( 'Run maintenance and outbox', 'worldwide-clinic-appointments' ); ?></button></p></form>"
replace_once(admin, old_admin_block, new_admin_block)
replace_once(
    admin,
    "\tpublic static function retry_outbox() {\n\t\tself::authorize( 'wca_retry_outbox' );\n\t\tWCA_Outbox::process( 100 );\n\t\twp_safe_redirect( admin_url( 'edit.php?post_type=' . SWC_Helpers::TYPE . '&page=wca-operations' ) );\n\t\texit;\n\t}",
    "\tpublic static function retry_outbox() {\n\t\tself::authorize( 'wca_retry_outbox' );\n\t\t$result = WCA_Outbox::process( 100 );\n\t\t$state = is_wp_error( $result ) ? 'failed' : 'done';\n\t\tif ( is_wp_error( $result ) ) { WCA_Observability::log( 'error', 'manual_outbox_process_failed', array( 'error_code' => $result->get_error_code() ) ); }\n\t\twp_safe_redirect( add_query_arg( array( 'page' => 'wca-operations', 'wca_outbox' => $state, 'wca_processed' => is_wp_error( $result ) ? 0 : absint( $result ) ), admin_url( 'admin.php' ) ) );\n\t\texit;\n\t}"
)

# R19-D5 — surface queue state in health with a fail-closed DB-read flag and
# a dead-letter health signal.
obs = 'includes/class-wca-observability.php'
replace_once(
    obs,
    "\tpublic static function health() {\n\t\t$runtime_failure = get_option( 'wca_runtime_migration_failure', false );\n\t\t$checks = array(",
    "\tpublic static function health() {\n\t\t$runtime_failure = get_option( 'wca_runtime_migration_failure', false );\n\t\t$queue = WCA_Repository::outbox_queue_status();\n\t\tif ( is_wp_error( $queue ) ) { $queue = array( 'pending' => 0, 'retry' => 0, 'processing' => 0, 'dead_letter' => 0, 'due' => 0, 'oldest_due_at' => '', 'db_read_ok' => false, 'healthy' => false ); }\n\t\t$checks = array("
)
replace_once(
    obs,
    "\t\t\t'cron'            => array(\n\t\t\t\t'outbox'      => (bool) wp_next_scheduled( WCA_Outbox::CRON_HOOK ),\n\t\t\t\t'maintenance' => (bool) wp_next_scheduled( WCA_Outbox::MAINTENANCE_HOOK ),\n\t\t\t),\n\t\t\t'circuit_breakers' =>",
    "\t\t\t'cron'            => array(\n\t\t\t\t'outbox'      => (bool) wp_next_scheduled( WCA_Outbox::CRON_HOOK ),\n\t\t\t\t'maintenance' => (bool) wp_next_scheduled( WCA_Outbox::MAINTENANCE_HOOK ),\n\t\t\t),\n\t\t\t'outbox_queue'     => $queue,\n\t\t\t'circuit_breakers' =>"
)
replace_once(
    obs,
    "&& self::all_true( $checks['legacy_checks'] ) && self::all_true( $checks['cron'] );",
    "&& self::all_true( $checks['legacy_checks'] ) && self::all_true( $checks['cron'] ) && self::all_true( $checks['outbox_queue'] );"
)

# R19-D6 — keep CLI recovery available when runtime migration itself fails,
# and make repair/migrate cover every File 08-owned schema layer.
cli = ROOT / 'includes/class-wca-cli.php'
cli.write_text(r'''<?php
/** WP-CLI operations for deterministic File 08 management. */
defined( 'ABSPATH' ) || exit;

final class WCA_CLI {
	private static $registered = false;

	public static function register() {
		if ( self::$registered || ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }
		self::$registered = true;
		WP_CLI::add_command( 'wca health', array( __CLASS__, 'health' ) );
		WP_CLI::add_command( 'wca migrate', array( __CLASS__, 'migrate' ) );
		WP_CLI::add_command( 'wca outbox', array( __CLASS__, 'outbox' ) );
		WP_CLI::add_command( 'wca queue', array( __CLASS__, 'queue' ) );
		WP_CLI::add_command( 'wca contracts', array( __CLASS__, 'contracts' ) );
	}

	public static function health() {
		$health = WCA_Observability::health();
		WP_CLI::line( wp_json_encode( $health, JSON_PRETTY_PRINT ) );
		if ( empty( $health['ok'] ) ) { WP_CLI::error( 'File 08 health checks are not green.' ); }
	}

	public static function migrate() {
		try {
			SWC_Activator::install_schema();
			WCA_Schema::install();
			WCA_Continuity::install_schema();
			WCA_Future24::install_schema();
			$migrated = SWC_Activator::migrate_existing_records();
			$legacy = WCA_Compatibility::migrate_legacy_statuses( 5000 );
			if ( is_wp_error( $legacy ) ) { WP_CLI::error( $legacy->get_error_message() ); return; }
			foreach ( array( 'swc_db_version' => SWC_Activator::DB_VERSION, 'swc_version' => SWC_VERSION ) as $option => $value ) {
				$written = SWC_Helpers::update_option_strict( $option, $value, 'wca_cli_migration_marker_write' );
				if ( is_wp_error( $written ) ) { WP_CLI::error( $written->get_error_message() ); return; }
			}
			$failure_cleared = SWC_Helpers::delete_option_strict( 'wca_runtime_migration_failure', 'wca_cli_migration_failure_clear' );
			if ( is_wp_error( $failure_cleared ) ) { WP_CLI::error( $failure_cleared->get_error_message() ); return; }
			WP_CLI::success( sprintf( 'All File 08 schema layers verified; %d appointment records and %d legacy statuses reconciled.', absint( $migrated ), absint( $legacy ) ) );
		} catch ( Throwable $error ) {
			WP_CLI::error( 'File 08 migration/repair failed: ' . $error->getMessage() );
		}
	}

	public static function outbox( $args, $assoc ) {
		$count = WCA_Outbox::process( absint( $assoc['limit'] ?? 100 ) );
		if ( is_wp_error( $count ) ) { WP_CLI::error( $count->get_error_message() ); return; }
		WP_CLI::success( sprintf( '%d outbox messages processed.', $count ) );
	}

	public static function queue() {
		$status = WCA_Repository::outbox_queue_status();
		if ( is_wp_error( $status ) ) { WP_CLI::error( $status->get_error_message() ); return; }
		WP_CLI::line( wp_json_encode( $status, JSON_PRETTY_PRINT ) );
		if ( empty( $status['healthy'] ) ) { WP_CLI::warning( 'File 08 outbox has dead-letter work requiring operator investigation.' ); }
	}

	public static function contracts() { WP_CLI::line( wp_json_encode( WCA_Contracts::contract_manifest(), JSON_PRETTY_PRINT ) ); }
}
''', encoding='utf-8')

plugin = 'worldwide-clinic.php'
replace_once(
    plugin,
    "\tif ( ! SWC_Activator::dependencies_ready() ) {\n\t\tadd_action( 'admin_notices', function () {",
    "\tif ( ! SWC_Activator::dependencies_ready() ) {\n\t\tadd_action( 'admin_notices', function () {"
)
replace_once(
    plugin,
    "\t\treturn;\n\t}\n\ttry {\n\t\t$legacy_upgrade = SWC_Activator::maybe_upgrade();",
    "\t\treturn;\n\t}\n\t// Register recovery CLI before migrations so a failed migration remains operable.\n\tWCA_CLI::register();\n\ttry {\n\t\t$legacy_upgrade = SWC_Activator::maybe_upgrade();"
)

swc_admin = 'includes/class-swc-admin.php'
replace_once(
    swc_admin,
    "\t\t\tSWC_Activator::install_schema();\n\t\t\tSWC_Activator::repair_pages();\n\t\t\tSWC_Activator::migrate_existing_records();\n\t\t\t$written = SWC_Helpers::update_option_strict( 'swc_db_version', SWC_Activator::DB_VERSION, 'swc_repair_db_version_write' );\n\t\t\tif ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 repair version state could not be persisted.' ); }",
    "\t\t\tSWC_Activator::install_schema();\n\t\t\tWCA_Schema::install();\n\t\t\tWCA_Continuity::install_schema();\n\t\t\tWCA_Future24::install_schema();\n\t\t\tWCA_Outbox::schedule();\n\t\t\tSWC_Activator::repair_pages();\n\t\t\tSWC_Activator::migrate_existing_records();\n\t\t\tforeach ( array( 'swc_db_version' => SWC_Activator::DB_VERSION, 'swc_version' => SWC_VERSION ) as $option => $value ) {\n\t\t\t\t$written = SWC_Helpers::update_option_strict( $option, $value, 'swc_repair_version_write' );\n\t\t\t\tif ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 repair version state could not be persisted.' ); }\n\t\t\t}"
)

# Permanent R19 regression gate.
test = ROOT / 'tests/t19-r19-operability-performance-regressions.php'
test.write_text(r'''<?php
$root = dirname( __DIR__ );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$admin = file_get_contents( $root . '/includes/class-wca-admin.php' );
$obs = file_get_contents( $root . '/includes/class-wca-observability.php' );
$cli = file_get_contents( $root . '/includes/class-wca-cli.php' );
$plugin = file_get_contents( $root . '/worldwide-clinic.php' );
$legacy_admin = file_get_contents( $root . '/includes/class-swc-admin.php' );
foreach ( array( $repo,$rest,$service,$admin,$obs,$cli,$plugin,$legacy_admin ) as $source ) { if ( ! is_string( $source ) ) { fwrite( STDERR, "T19 R19 source read failed\n" ); exit( 1 ); } }
$checks = array(
    'repository hard-caps owned child collections' => false !== strpos( $repo, 'const MAX_COLLECTION_ROWS = 100;' ),
    'branch collection SQL is bounded' => false !== strpos( $repo, "ORDER BY name ASC,id ASC LIMIT %d" ),
    'service collection appends a hard-capped limit' => false !== strpos( $repo, '$params[] = $limit;') && false !== strpos( $repo, "ORDER BY name ASC,id ASC LIMIT %d" ),
    'availability collection SQL is bounded' => false !== strpos( $repo, "ORDER BY id ASC LIMIT %d" ),
    'clinic collection supports lightweight discovery rows' => false !== strpos( $repo, "array_key_exists( 'hydrate_children', $args )") && false !== strpos( $rest, "'hydrate_children' => false" ),
    'public projection avoids private child hydration before service authorization' => false !== strpos( $service, 'get_clinic( $id_or_slug, false, false )') && false !== strpos( $service, '$private_services = self::repository_read'),
    'operations screen is attached to visible clinic admin parent' => false !== strpos( $admin, "add_submenu_page( 'clinic-management'") && false === strpos( $admin, "add_submenu_page( 'edit.php?post_type=' . SWC_Helpers::TYPE" ),
    'operations screen exposes protected due-outbox control' => false !== strpos( $admin, "wp_nonce_field( 'wca_retry_outbox' )") && false !== strpos( $admin, 'Process due outbox'),
    'manual outbox processing propagates failure state' => false !== strpos( $admin, "is_wp_error( $result ) ? 'failed' : 'done'") && false !== strpos( $admin, 'manual_outbox_process_failed'),
    'queue inspection exposes dead-letter and due state' => false !== strpos( $repo, 'public static function outbox_queue_status()') && false !== strpos( $repo, "'dead_letter'") && false !== strpos( $repo, "'oldest_due_at'"),
    'health consumes queue inspection' => false !== strpos( $obs, 'WCA_Repository::outbox_queue_status()') && false !== strpos( $obs, "'outbox_queue'     => $queue" ),
    'CLI registration is idempotent' => false !== strpos( $cli, 'private static $registered = false;') && false !== strpos( $cli, 'self::$registered = true;'),
    'recovery CLI is registered before runtime migration attempt' => ( $p = strpos( $plugin, 'WCA_CLI::register();') ) !== false && ( $t = strpos( $plugin, "try {\n\t\t$legacy_upgrade" ) ) !== false && $p < $t,
    'CLI migrate repairs all owned schema layers' => false !== strpos( $cli, 'SWC_Activator::install_schema();') && false !== strpos( $cli, 'WCA_Schema::install();') && false !== strpos( $cli, 'WCA_Continuity::install_schema();') && false !== strpos( $cli, 'WCA_Future24::install_schema();'),
    'admin complete repair repairs all owned schema layers' => false !== strpos( $legacy_admin, 'WCA_Schema::install();') && false !== strpos( $legacy_admin, 'WCA_Continuity::install_schema();') && false !== strpos( $legacy_admin, 'WCA_Future24::install_schema();'),
    'CLI exposes queue inspection command' => false !== strpos( $cli, "WP_CLI::add_command( 'wca queue'") && false !== strpos( $cli, 'public static function queue()'),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R19 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R19 operability/performance regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
''', encoding='utf-8')

run = ROOT / 'tests/run-all.php'
rt = run.read_text(encoding='utf-8')
needle = "'t19-r18-frontend-calendar-timezone-regressions.php' );"
replacement = "'t19-r18-frontend-calendar-timezone-regressions.php', 't19-r19-operability-performance-regressions.php' );"
if rt.count(needle) != 1:
    raise SystemExit('R19 run-all insertion point not found')
run.write_text(rt.replace(needle, replacement, 1), encoding='utf-8')

print('T19 R19 frozen ledger corrections applied.')
