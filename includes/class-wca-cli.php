<?php
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
