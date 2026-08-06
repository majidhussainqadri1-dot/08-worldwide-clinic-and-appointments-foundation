<?php
/**
 * Accessible server-rendered frontend for canonical File 08 routes.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Frontend {
	public static function hooks() {
		add_shortcode( 'wca_clinic', array( __CLASS__, 'shortcode_clinic' ) );
		add_shortcode( 'wca_appointments', array( __CLASS__, 'shortcode_appointments' ) );
		add_shortcode( 'wca_clinic_dashboard', array( __CLASS__, 'shortcode_dashboard' ) );
	}

	public static function render_current_route() {
		$route = WCA_Routes::route();
		switch ( $route ) {
			case 'clinic': return self::clinic( WCA_Routes::ref() );
			case 'book': return self::booking( WCA_Routes::ref() );
			case 'appointments': return self::appointments();
			case 'dashboard': return self::dashboard();
			case 'appointment': return self::appointment( WCA_Routes::ref() );
		}
		return self::notice( __( 'The requested clinic page is unavailable.', 'worldwide-clinic-appointments' ), 'error' );
	}

	public static function shortcode_clinic( $atts ) {
		$atts = shortcode_atts( array( 'ref' => '' ), $atts, 'wca_clinic' );
		return self::clinic( sanitize_text_field( $atts['ref'] ) );
	}

	public static function shortcode_appointments() { return self::appointments(); }
	public static function shortcode_dashboard() { return self::dashboard(); }

	private static function clinic( $ref ) {
		$clinic = WCA_Service::public_clinic_projection( $ref );
		if ( ! $clinic ) { return self::notice( __( 'Clinic was not found or is not publicly available.', 'worldwide-clinic-appointments' ), 'error' ); }
		ob_start();
		?>
		<main class="wca-shell wca-clinic" aria-labelledby="wca-clinic-title">
			<header class="wca-hero">
				<p class="wca-eyebrow"><?php esc_html_e( 'Verified worldwide clinic', 'worldwide-clinic-appointments' ); ?></p>
				<h1 id="wca-clinic-title"><?php echo esc_html( $clinic['name'] ); ?></h1>
				<p><?php echo esc_html( $clinic['summary'] ); ?></p>
				<?php if ( ! empty( $clinic['languages'] ) ) : ?><p><strong><?php esc_html_e( 'Languages:', 'worldwide-clinic-appointments' ); ?></strong> <?php echo esc_html( implode( ', ', $clinic['languages'] ) ); ?></p><?php endif; ?>
			</header>
			<section aria-labelledby="wca-services-title">
				<h2 id="wca-services-title"><?php esc_html_e( 'Services', 'worldwide-clinic-appointments' ); ?></h2>
				<div class="wca-grid">
				<?php foreach ( (array) $clinic['services'] as $service ) : ?>
					<article class="wca-card">
						<h3><?php echo esc_html( $service['name'] ); ?></h3>
						<p><?php echo esc_html( sprintf( __( '%1$d minutes · %2$s', 'worldwide-clinic-appointments' ), absint( $service['duration_minutes'] ), ucfirst( $service['consultation_type'] ) ) ); ?></p>
						<p class="wca-price"><?php echo esc_html( self::money( $service['fee_minor'], $service['currency'] ) ); ?></p>
						<a class="wca-button" href="<?php echo esc_url( home_url( '/appointments/book/' . rawurlencode( $clinic['public_ref'] ) . '/?service=' . absint( $service['id'] ) ) ); ?>"><?php esc_html_e( 'Choose appointment', 'worldwide-clinic-appointments' ); ?></a>
					</article>
				<?php endforeach; ?>
				</div>
			</section>
			<section aria-labelledby="wca-branches-title">
				<h2 id="wca-branches-title"><?php esc_html_e( 'Locations', 'worldwide-clinic-appointments' ); ?></h2>
				<?php foreach ( (array) $clinic['branches'] as $branch ) : ?>
					<article class="wca-card"><h3><?php echo esc_html( $branch['name'] ); ?></h3><address><?php echo esc_html( implode( ', ', array_filter( array( $branch['address_public'], $branch['city'], $branch['country_code'] ) ) ) ); ?></address><p><?php echo esc_html( $branch['timezone'] ); ?></p></article>
				<?php endforeach; ?>
			</section>
			<aside class="wca-alert" role="note"><strong><?php esc_html_e( 'Not emergency care.', 'worldwide-clinic-appointments' ); ?></strong> <?php esc_html_e( 'For severe or life-threatening symptoms, contact local emergency services immediately.', 'worldwide-clinic-appointments' ); ?></aside>
		</main>
		<?php
		return ob_get_clean();
	}

	private static function booking( $clinic_ref ) {
		if ( ! is_user_logged_in() ) { return self::notice( __( 'Sign in to book an appointment.', 'worldwide-clinic-appointments' ), 'warning' ); }
		$clinic = WCA_Service::public_clinic_projection( $clinic_ref );
		if ( ! $clinic ) { return self::notice( __( 'Clinic is unavailable.', 'worldwide-clinic-appointments' ), 'error' ); }
		$service_id = absint( $_GET['service'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only route choice.
		ob_start();
		?>
		<main class="wca-shell" aria-labelledby="wca-book-title" data-wca-booking data-clinic-id="<?php echo esc_attr( $clinic['id'] ?? 0 ); ?>" data-service-id="<?php echo esc_attr( $service_id ); ?>">
			<h1 id="wca-book-title"><?php echo esc_html( sprintf( __( 'Book with %s', 'worldwide-clinic-appointments' ), $clinic['name'] ) ); ?></h1>
			<div class="wca-alert" role="alert"><strong><?php esc_html_e( 'Emergency warning:', 'worldwide-clinic-appointments' ); ?></strong> <?php esc_html_e( 'This booking service cannot provide emergency care. Contact local emergency services for urgent danger.', 'worldwide-clinic-appointments' ); ?></div>
			<form class="wca-form" data-wca-booking-form novalidate>
				<label><?php esc_html_e( 'Service', 'worldwide-clinic-appointments' ); ?><select name="service_id" required><?php foreach ( (array) $clinic['services'] as $service ) : ?><option value="<?php echo esc_attr( $service['id'] ); ?>" <?php selected( $service_id, $service['id'] ); ?>><?php echo esc_html( $service['name'] ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Doctor', 'worldwide-clinic-appointments' ); ?><input name="doctor_user_id" type="number" min="1" required inputmode="numeric"></label>
				<label><?php esc_html_e( 'Date from', 'worldwide-clinic-appointments' ); ?><input name="date_from" type="date" required value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
				<label><?php esc_html_e( 'Your time zone', 'worldwide-clinic-appointments' ); ?><input name="timezone" required value="<?php echo esc_attr( wp_timezone_string() ); ?>"></label>
				<button class="wca-button" type="button" data-wca-search-slots><?php esc_html_e( 'Find available times', 'worldwide-clinic-appointments' ); ?></button>
				<div class="wca-slots" data-wca-slots aria-live="polite"></div>
				<label><?php esc_html_e( 'Reason category', 'worldwide-clinic-appointments' ); ?><select name="category"><option value="general"><?php esc_html_e( 'General consultation', 'worldwide-clinic-appointments' ); ?></option><option value="follow_up"><?php esc_html_e( 'Follow-up', 'worldwide-clinic-appointments' ); ?></option></select></label>
				<label><?php esc_html_e( 'Brief reason (do not enter emergency details)', 'worldwide-clinic-appointments' ); ?><textarea name="reason" maxlength="500"></textarea></label>
				<label class="wca-check"><input type="checkbox" name="privacy_consent" required> <?php esc_html_e( 'I accept appointment-processing and privacy terms.', 'worldwide-clinic-appointments' ); ?></label>
				<label class="wca-check"><input type="checkbox" name="emergency_ack" required> <?php esc_html_e( 'I understand this is not emergency care.', 'worldwide-clinic-appointments' ); ?></label>
				<button class="wca-button" type="submit"><?php esc_html_e( 'Request appointment', 'worldwide-clinic-appointments' ); ?></button>
				<p data-wca-status role="status" aria-live="polite"></p>
			</form>
		</main>
		<?php
		return ob_get_clean();
	}

	private static function appointments() {
		if ( ! is_user_logged_in() ) { return self::notice( __( 'Sign in to view appointments.', 'worldwide-clinic-appointments' ), 'warning' ); }
		$user_id = get_current_user_id();
		$ids = get_posts( array(
			'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'posts_per_page' => 100, 'fields' => 'ids',
			'meta_query' => array( 'relation' => 'OR', array( 'key' => '_swc_patient_user_id', 'value' => $user_id ), array( 'key' => '_swc_doctor_id', 'value' => $user_id ), array( 'key' => '_swc_guardian_user_id', 'value' => $user_id ) ),
			'orderby' => 'meta_value', 'meta_key' => '_swc_preferred_at_utc', 'order' => 'DESC',
		) );
		ob_start(); ?>
		<main class="wca-shell" aria-labelledby="wca-appts-title"><h1 id="wca-appts-title"><?php esc_html_e( 'My appointments', 'worldwide-clinic-appointments' ); ?></h1>
		<?php if ( ! $ids ) : ?><p><?php esc_html_e( 'No appointments found.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
		<div class="wca-list"><?php foreach ( $ids as $id ) { echo self::appointment_card( $id ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } ?></div></main>
		<?php return ob_get_clean();
	}

	private static function appointment( $public_ref ) {
		$ids = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_swc_public_ref', 'meta_value' => $public_ref ) );
		$id = $ids ? absint( $ids[0] ) : 0;
		$access = $id ? WCA_Authorization::can_view_appointment( $id ) : new WP_Error( 'missing', 'missing' );
		if ( is_wp_error( $access ) ) { return self::notice( __( 'Appointment was not found or you do not have access.', 'worldwide-clinic-appointments' ), 'error' ); }
		return '<main class="wca-shell" aria-labelledby="wca-appt-title"><h1 id="wca-appt-title">' . esc_html__( 'Appointment', 'worldwide-clinic-appointments' ) . '</h1>' . self::appointment_card( $id, true ) . '</main>';
	}

	private static function appointment_card( $id, $detailed = false ) {
		$status = SWC_Helpers::status( $id );
		$actor = WCA_Authorization::appointment_actor( $id, get_current_user_id() );
		$actions = WCA_Contracts::allowed_transitions( $actor, $status );
		$ref = (string) SWC_Helpers::meta( $id, 'public_ref', 'appointment-' . $id );
		$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
		ob_start(); ?>
		<article class="wca-card wca-appointment" data-wca-appointment-id="<?php echo esc_attr( $id ); ?>" data-wca-version="<?php echo esc_attr( SWC_Helpers::record_version( $id ) ); ?>" data-wca-status="<?php echo esc_attr( $status ); ?>">
			<header><h2><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></h2><p><time datetime="<?php echo esc_attr( $when ? gmdate( 'c', strtotime( $when . ' UTC' ) ) : '' ); ?>"><?php echo esc_html( $when ? get_date_from_gmt( $when, 'F j, Y g:i a' ) : __( 'Time pending', 'worldwide-clinic-appointments' ) ); ?></time></p></header>
			<?php if ( $detailed ) : ?><dl><dt><?php esc_html_e( 'Reference', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( $ref ); ?></dd><dt><?php esc_html_e( 'Consultation', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( (string) SWC_Helpers::meta( $id, 'consultation_type' ) ); ?></dd></dl><?php endif; ?>
			<div class="wca-actions"><?php foreach ( $actions as $action ) : ?><button type="button" class="wca-button wca-button-secondary" data-wca-transition="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $action ) ) ); ?></button><?php endforeach; ?><a class="wca-button wca-button-secondary" href="<?php echo esc_url( rest_url( 'wca/v1/appointments/' . $id . '/calendar.ics' ) ); ?>"><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></a></div>
			<p data-wca-status role="status" aria-live="polite"></p>
		</article>
		<?php return ob_get_clean();
	}

	private static function dashboard() {
		$claims = WCA_Authorization::claims();
		if ( is_wp_error( $claims ) || ! in_array( $claims['role'], array( 'doctor','founder','administrator','clinic_staff' ), true ) ) { return self::notice( __( 'Verified clinic access is required.', 'worldwide-clinic-appointments' ), 'error' ); }
		$clinics = WCA_Repository::list_clinics( array( 'owner_user_id' => get_current_user_id(), 'status' => '' ) );
		ob_start(); ?>
		<main class="wca-shell" aria-labelledby="wca-dashboard-title"><h1 id="wca-dashboard-title"><?php esc_html_e( 'Clinic dashboard', 'worldwide-clinic-appointments' ); ?></h1>
		<p><?php esc_html_e( 'Manage clinic identity, branches, services, fees, availability and appointment operations. Platform commission is always 0%.', 'worldwide-clinic-appointments' ); ?></p>
		<div class="wca-grid"><?php foreach ( $clinics as $clinic ) : ?><article class="wca-card"><h2><?php echo esc_html( $clinic['name'] ); ?></h2><p><?php echo esc_html( ucfirst( $clinic['status'] ) ); ?> · v<?php echo esc_html( $clinic['version'] ); ?></p><a class="wca-button" href="<?php echo esc_url( home_url( '/clinic/' . rawurlencode( $clinic['slug'] ) . '/' ) ); ?>"><?php esc_html_e( 'View public clinic', 'worldwide-clinic-appointments' ); ?></a></article><?php endforeach; ?></div>
		</main><?php return ob_get_clean();
	}

	private static function money( $minor, $currency ) {
		return strtoupper( sanitize_text_field( $currency ) ) . ' ' . number_format_i18n( absint( $minor ) / 100, 2 );
	}

	private static function notice( $message, $type = 'info' ) {
		return '<div class="wca-shell"><div class="wca-alert wca-alert-' . esc_attr( $type ) . '" role="alert">' . esc_html( $message ) . '</div></div>';
	}
}
