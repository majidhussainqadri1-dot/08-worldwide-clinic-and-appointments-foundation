<?php
/**
 * Plugin Name: Worldwide Clinic and Appointments Foundation
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Privacy-safe worldwide clinic discovery, enforceable doctor availability, appointment requests, role-based dashboards, audited scheduling, and unified notifications.
 * Version: 0.2.1
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * License: GPL-2.0-or-later
 * Text Domain: worldwide-clinic
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'SWC_VERSION', '0.2.1' );
define( 'SWC_PUBLIC_CLINIC_CONTRACT_VERSION', '1.0.0' );
define( 'SWC_FILE', __FILE__ );
define( 'SWC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWC_URL', plugin_dir_url( __FILE__ ) );

require_once SWC_DIR . 'includes/class-swc-helpers.php';
require_once SWC_DIR . 'includes/class-swc-doctor-authority.php';
require_once SWC_DIR . 'includes/class-swc-public-clinic.php';
require_once SWC_DIR . 'includes/class-swc-activator.php';
require_once SWC_DIR . 'includes/class-swc-appointments.php';
require_once SWC_DIR . 'includes/class-swc-frontend.php';
require_once SWC_DIR . 'includes/class-swc-admin.php';
require_once SWC_DIR . 'includes/class-swc-privacy.php';
require_once SWC_DIR . 'includes/class-swc-plugin.php';

register_activation_hook( SWC_FILE, array( 'SWC_Activator', 'activate' ) );
register_deactivation_hook( SWC_FILE, array( 'SWC_Activator', 'deactivate' ) );

function swc_start_plugin() {
	if ( SWC_Activator::dependencies_ready() ) {
		( new SWC_Plugin() )->run();
		return;
	}
	add_action(
		'admin_notices',
		function () {
			if ( current_user_can( 'activate_plugins' ) ) {
				echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'Worldwide Clinic:', 'worldwide-clinic' ) . '</strong> ' . esc_html( SWC_Activator::dependency_message() ) . '</p></div>';
			}
		}
	);
}
add_action( 'plugins_loaded', 'swc_start_plugin', 30 );
