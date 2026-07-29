<?php
/**
 * File 08 runtime coordinator.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Plugin {
	/**
	 * Register the complete runtime without taking ownership of File 20 navigation.
	 */
	public function run() {
		load_plugin_textdomain( 'worldwide-clinic', false, dirname( plugin_basename( SWC_FILE ) ) . '/languages' );
		SWC_Activator::maybe_upgrade();

		( new SWC_Appointments() )->hooks();
		( new SWC_Frontend() )->hooks();
		( new SWC_Admin() )->hooks();
		( new SWC_Privacy() )->hooks();

		add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'template_redirect', array( $this, 'private_headers' ), 0 );
		add_filter( 'wp_robots', array( $this, 'robots' ) );
		add_filter( 'map_meta_cap', array( $this, 'map_appointment_caps' ), 10, 4 );
	}

	public function assets() {
		global $post;
		$map    = SWC_Helpers::pages();
		$needed = $post instanceof WP_Post && (
			in_array( absint( $post->ID ), array_map( 'absint', $map ), true )
			|| false !== strpos( (string) $post->post_content, '[swc_' )
		);
		if ( ! $needed ) {
			return;
		}
		wp_enqueue_style( 'swc-clinic', SWC_URL . 'assets/css/clinic.css', array(), SWC_VERSION );
		wp_enqueue_script( 'swc-clinic', SWC_URL . 'assets/js/clinic.js', array(), SWC_VERSION, true );
		wp_localize_script(
			'swc-clinic',
			'swcClinic',
			array(
				'browserTimezone' => wp_timezone_string(),
				'reschedule'      => __( 'Reschedule fields are required for this status.', 'worldwide-clinic' ),
			)
		);
	}

	public function admin_assets( $hook ) {
		if ( false !== strpos( (string) $hook, 'clinic-management' ) || false !== strpos( (string) $hook, 'clinic-settings' ) || false !== strpos( (string) $hook, 'clinic-system-check' ) ) {
			wp_enqueue_style( 'swc-admin', SWC_URL . 'assets/css/admin.css', array(), SWC_VERSION );
			wp_enqueue_script( 'swc-clinic-admin', SWC_URL . 'assets/js/clinic.js', array(), SWC_VERSION, true );
		}
	}

	private function is_private_page() {
		$map = SWC_Helpers::pages();
		foreach ( array( 'request', 'patient', 'doctor', 'availability' ) as $key ) {
			if ( ! empty( $map[ $key ] ) && is_page( absint( $map[ $key ] ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Fail closed against WordPress, LiteSpeed, object, page, and browser caches.
	 */
	public function private_headers() {
		if ( ! $this->is_private_page() ) {
			return;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		if ( ! defined( 'DONOTCACHEOBJECT' ) ) {
			define( 'DONOTCACHEOBJECT', true );
		}
		if ( ! defined( 'DONOTCACHEDB' ) ) {
			define( 'DONOTCACHEDB', true );
		}
		do_action( 'litespeed_control_set_nocache', 'File 08 private clinic workflow' );
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0', true );
		header( 'Pragma: no-cache', true );
		header( 'Expires: Wed, 11 Jan 1984 05:00:00 GMT', true );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
	}

	public function robots( $robots ) {
		if ( $this->is_private_page() ) {
			$robots['noindex']   = true;
			$robots['nofollow']  = true;
			$robots['noarchive'] = true;
			$robots['nosnippet'] = true;
		}
		return $robots;
	}

	/**
	 * Protect sensitive appointment posts even if a third-party plugin exposes a generic edit path.
	 */
	public function map_appointment_caps( $caps, $cap, $user_id, $args ) {
		if ( ! in_array( $cap, array( 'read_swc_appointment', 'edit_swc_appointment', 'delete_swc_appointment' ), true ) ) {
			return $caps;
		}
		$appointment_id = isset( $args[0] ) ? absint( $args[0] ) : 0;
		if ( ! $appointment_id || SWC_Helpers::TYPE !== get_post_type( $appointment_id ) ) {
			return array( 'do_not_allow' );
		}
		if ( user_can( $user_id, 'manage_worldwide_clinic' ) ) {
			return array( 'manage_worldwide_clinic' );
		}
		if ( 'read_swc_appointment' === $cap && ( SWC_Helpers::can_patient_manage( $appointment_id, $user_id ) || SWC_Helpers::can_doctor_manage( $appointment_id, $user_id ) ) ) {
			return array( 'read' );
		}
		return array( 'do_not_allow' );
	}
}
