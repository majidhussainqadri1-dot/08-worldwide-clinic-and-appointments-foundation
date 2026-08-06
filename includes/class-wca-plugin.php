<?php
/**
 * Canonical File 08 runtime coordinator.
 *
 * @package Worldwide_Clinic_Appointments
 */
defined( 'ABSPATH' ) || exit;

final class WCA_Plugin {
	public static function boot() {
		load_plugin_textdomain( 'worldwide-clinic-appointments', false, dirname( plugin_basename( WCA_FILE ) ) . '/languages' );
		add_filter( 'cron_schedules', array( 'WCA_Outbox', 'cron_schedules' ) );
		WCA_Observability::hooks();
		WCA_Compatibility::hooks();
		WCA_Service::hooks();
		WCA_REST::hooks();
		WCA_Routes::hooks();
		WCA_Frontend::hooks();
		WCA_Admin::hooks();
		WCA_Privacy::hooks();
		WCA_Outbox::hooks();
		WCA_CLI::register();
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
		add_action( 'init', array( __CLASS__, 'register_assets_only_on_routes' ), 100 );
		add_action( 'send_headers', array( 'WCA_Observability', 'trace_header' ) );
	}

	public static function register_assets_only_on_routes() {
		wp_register_style( 'wca-clinic', WCA_URL . 'assets/css/clinic.css', array(), WCA_VERSION );
		wp_register_script( 'wca-clinic', WCA_URL . 'assets/js/clinic.js', array(), WCA_VERSION, true );
	}

	public static function assets() {
		if ( ! WCA_Routes::route() && ! is_singular() ) { return; }
		wp_enqueue_style( 'wca-clinic' );
		wp_enqueue_script( 'wca-clinic' );
		wp_localize_script( 'wca-clinic', 'wcaRuntime', array(
			'restUrl'   => esc_url_raw( rest_url( 'wca/v1/' ) ),
			'nonce'     => wp_create_nonce( 'wp_rest' ),
			'timezone'  => wp_timezone_string(),
			'loggedIn'  => is_user_logged_in(),
			'i18n'      => array(
				'loading' => __( 'Loading…', 'worldwide-clinic-appointments' ),
				'error'   => __( 'The request could not be completed.', 'worldwide-clinic-appointments' ),
				'success' => __( 'Saved successfully.', 'worldwide-clinic-appointments' ),
			),
		) );
	}

	public static function admin_assets( $hook ) {
		if ( false !== strpos( (string) $hook, 'wca-operations' ) || false !== strpos( (string) $hook, 'clinic' ) ) {
			wp_enqueue_style( 'wca-admin', WCA_URL . 'assets/css/admin.css', array(), WCA_VERSION );
		}
	}
}
