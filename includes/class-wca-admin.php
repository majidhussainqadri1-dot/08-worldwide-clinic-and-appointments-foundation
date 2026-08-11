<?php
/**
 * Operational administration, health, and governance controls.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Admin {
	public static function hooks() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 40 );
		add_action( 'admin_post_wca_run_maintenance', array( __CLASS__, 'run_maintenance' ) );
		add_action( 'admin_post_wca_retry_outbox', array( __CLASS__, 'retry_outbox' ) );
	}

	public static function menu() {
		add_submenu_page( 'edit.php?post_type=' . SWC_Helpers::TYPE, __( 'File 08 Operations', 'worldwide-clinic-appointments' ), __( 'Operations', 'worldwide-clinic-appointments' ), 'manage_worldwide_clinic', 'wca-operations', array( __CLASS__, 'page' ) );
	}

	public static function page() {
		if ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'worldwide-clinic-appointments' ) ); }
		$health = WCA_Observability::health();
		$deps = WCA_Compatibility::dependency_health();
		?>
		<div class="wrap wca-admin-wrap"><h1><?php esc_html_e( 'File 08 Operations', 'worldwide-clinic-appointments' ); ?></h1>
			<p><strong><?php esc_html_e( 'Runtime:', 'worldwide-clinic-appointments' ); ?></strong> <?php echo esc_html( WCA_VERSION ); ?> · <strong><?php esc_html_e( 'Schema:', 'worldwide-clinic-appointments' ); ?></strong> <?php echo esc_html( WCA_Contracts::SCHEMA_VERSION ); ?> · <strong><?php esc_html_e( 'Commission:', 'worldwide-clinic-appointments' ); ?></strong> 0%</p>
			<h2><?php esc_html_e( 'Health', 'worldwide-clinic-appointments' ); ?></h2><pre><?php echo esc_html( wp_json_encode( $health, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ); ?></pre>
			<h2><?php esc_html_e( 'Dependencies', 'worldwide-clinic-appointments' ); ?></h2><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Module', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Required', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'State', 'worldwide-clinic-appointments' ); ?></th></tr></thead><tbody><?php foreach ( $deps as $name => $state ) : ?><tr><td><?php echo esc_html( strtoupper( $name ) ); ?></td><td><?php echo $state['required'] ? esc_html__( 'Yes', 'worldwide-clinic-appointments' ) : esc_html__( 'Conditional', 'worldwide-clinic-appointments' ); ?></td><td><?php echo $state['ready'] ? esc_html__( 'Available', 'worldwide-clinic-appointments' ) : esc_html__( 'Unavailable', 'worldwide-clinic-appointments' ); ?></td></tr><?php endforeach; ?></tbody></table>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'wca_run_maintenance' ); ?><input type="hidden" name="action" value="wca_run_maintenance"><p><button class="button button-primary"><?php esc_html_e( 'Run maintenance and outbox', 'worldwide-clinic-appointments' ); ?></button></p></form>
			<p class="description"><?php esc_html_e( 'Source and automated checks do not replace Hostinger staging, rollback restoration, accessibility, security, privacy and Founder acceptance gates.', 'worldwide-clinic-appointments' ); ?></p>
		</div>
		<?php
	}

	public static function run_maintenance() {
		self::authorize( 'wca_run_maintenance' );
		$result = WCA_Outbox::maintenance();
		$state = is_wp_error( $result ) ? 'failed' : 'done';
		if ( is_wp_error( $result ) ) {
			WCA_Observability::log( 'error', 'manual_maintenance_failed', array( 'error_code' => $result->get_error_code() ) );
		}
		wp_safe_redirect( add_query_arg( 'wca_maintenance', $state, admin_url( 'edit.php?post_type=' . SWC_Helpers::TYPE . '&page=wca-operations' ) ) );
		exit;
	}

	public static function retry_outbox() {
		self::authorize( 'wca_retry_outbox' );
		WCA_Outbox::process( 100 );
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . SWC_Helpers::TYPE . '&page=wca-operations' ) );
		exit;
	}

	private static function authorize( $nonce ) {
		if ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Permission denied.', 'worldwide-clinic-appointments' ) ); }
		check_admin_referer( $nonce );
	}
}
