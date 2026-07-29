<?php
/**
 * Plugin Name: Worldwide Clinic and Appointments Foundation
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Private appointment requests, doctor availability, dashboards, notifications, and clinic management in American English.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Dr. Allama Majid Hussain Sabri
 * License: GPL-2.0-or-later
 * Text Domain: worldwide-clinic
 */
defined( 'ABSPATH' ) || exit;
define( 'SWC_VERSION', '0.1.0' ); define( 'SWC_FILE', __FILE__ ); define( 'SWC_DIR', plugin_dir_path( __FILE__ ) ); define( 'SWC_URL', plugin_dir_url( __FILE__ ) );
require_once SWC_DIR . 'includes/class-swc-helpers.php'; require_once SWC_DIR . 'includes/class-swc-activator.php'; require_once SWC_DIR . 'includes/class-swc-appointments.php'; require_once SWC_DIR . 'includes/class-swc-frontend.php'; require_once SWC_DIR . 'includes/class-swc-admin.php'; require_once SWC_DIR . 'includes/class-swc-privacy.php'; require_once SWC_DIR . 'includes/class-swc-plugin.php';
register_activation_hook( SWC_FILE, array( 'SWC_Activator', 'activate' ) ); register_deactivation_hook( SWC_FILE, array( 'SWC_Activator', 'deactivate' ) );
function swc_start_plugin() { if ( class_exists( 'SPD_Helpers' ) && class_exists( 'SDD_Helpers' ) ) { ( new SWC_Plugin() )->run(); } else { add_action( 'admin_notices', function() { if ( current_user_can( 'activate_plugins' ) ) { echo '<div class="notice notice-error"><p><strong>Worldwide Clinic:</strong> Activate Files 03 and 07 first.</p></div>'; } } ); } } add_action( 'plugins_loaded', 'swc_start_plugin', 30 );

