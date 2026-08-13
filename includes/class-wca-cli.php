<?php
/** WP-CLI operations for deterministic File 08 management. */
defined( 'ABSPATH' ) || exit;

final class WCA_CLI {
	public static function register() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { return; }
		WP_CLI::add_command( 'wca health', array( __CLASS__, 'health' ) );
		WP_CLI::add_command( 'wca migrate', array( __CLASS__, 'migrate' ) );
		WP_CLI::add_command( 'wca outbox', array( __CLASS__, 'outbox' ) );
		WP_CLI::add_command( 'wca contracts', array( __CLASS__, 'contracts' ) );
	}

	public static function health() { $health = WCA_Observability::health(); WP_CLI::line( wp_json_encode( $health, JSON_PRETTY_PRINT ) ); if ( empty( $health['ok'] ) ) { WP_CLI::error( 'File 08 health checks are not green.' ); } }
	public static function migrate() { WCA_Schema::maybe_upgrade(); $count = WCA_Compatibility::migrate_legacy_statuses( 5000 ); WP_CLI::success( sprintf( 'Schema verified; %d legacy statuses migrated.', $count ) ); }
	public static function outbox( $args, $assoc ) { $count = WCA_Outbox::process( absint( $assoc['limit'] ?? 100 ) ); if ( is_wp_error( $count ) ) { WP_CLI::error( $count->get_error_message() ); } WP_CLI::success( sprintf( '%d outbox messages processed.', $count ) ); }
	public static function contracts() { WP_CLI::line( wp_json_encode( WCA_Contracts::contract_manifest(), JSON_PRETTY_PRINT ) ); }
}
