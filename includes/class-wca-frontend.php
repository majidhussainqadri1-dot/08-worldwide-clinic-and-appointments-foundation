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
		if ( is_wp_error( $clinic ) ) { return self::notice( __( 'Clinic information is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }
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
							<a class="wca-button" href="<?php echo esc_url( home_url( '/appointments/book/' . rawurlencode( $clinic['public_ref'] ) . '/?service=' . rawurlencode( $service['public_ref'] ) ) ); ?>"><?php esc_html_e( 'Choose appointment', 'worldwide-clinic-appointments' ); ?></a>
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
		if ( is_wp_error( $clinic ) ) { return self::notice( __( 'Clinic information is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }
		if ( ! $clinic ) { return self::notice( __( 'Clinic is unavailable.', 'worldwide-clinic-appointments' ), 'error' ); }
		$service_ref = sanitize_text_field( wp_unslash( $_GET['service'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only route choice.
		$default_timezone = wp_timezone_string();
		if ( ! WCA_Service::valid_timezone( $default_timezone ) ) { $default_timezone = 'UTC'; }
		ob_start();
		?>
		<main class="wca-shell" aria-labelledby="wca-book-title" data-wca-booking data-clinic-ref="<?php echo esc_attr( $clinic['public_ref'] ); ?>" data-service-ref="<?php echo esc_attr( $service_ref ); ?>">
			<h1 id="wca-book-title"><?php echo esc_html( sprintf( __( 'Book with %s', 'worldwide-clinic-appointments' ), $clinic['name'] ) ); ?></h1>
			<div class="wca-alert" role="alert"><strong><?php esc_html_e( 'Emergency warning:', 'worldwide-clinic-appointments' ); ?></strong> <?php esc_html_e( 'This booking service cannot provide emergency care. Contact local emergency services for urgent danger.', 'worldwide-clinic-appointments' ); ?></div>
			<form class="wca-form" data-wca-booking-form novalidate>
				<label><?php esc_html_e( 'Service and verified practitioner', 'worldwide-clinic-appointments' ); ?><select name="service_ref" required><?php foreach ( (array) $clinic['services'] as $service ) : ?><option value="<?php echo esc_attr( $service['public_ref'] ); ?>" data-practitioner-ref="<?php echo esc_attr( $service['practitioner_ref'] ); ?>" data-consultation-type="<?php echo esc_attr( $service['consultation_type'] ); ?>" <?php selected( $service_ref, $service['public_ref'] ); ?>><?php echo esc_html( $service['name'] ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Date from', 'worldwide-clinic-appointments' ); ?><input name="date_from" type="date" required value="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></label>
				<label><?php esc_html_e( 'Your time zone', 'worldwide-clinic-appointments' ); ?><input name="timezone" required value="<?php echo esc_attr( $default_timezone ); ?>"></label>
				<button class="wca-button" type="button" data-wca-search-slots><?php esc_html_e( 'Find available times', 'worldwide-clinic-appointments' ); ?></button>
				<div class="wca-slots" data-wca-slots aria-live="polite"></div>
				<label><?php esc_html_e( 'Reason category', 'worldwide-clinic-appointments' ); ?><select name="category"><option value="general"><?php esc_html_e( 'General consultation', 'worldwide-clinic-appointments' ); ?></option><option value="follow_up"><?php esc_html_e( 'Follow-up', 'worldwide-clinic-appointments' ); ?></option></select></label>
				<label><?php esc_html_e( 'Brief reason (do not enter emergency details)', 'worldwide-clinic-appointments' ); ?><textarea name="reason" maxlength="500"></textarea></label>
				<label class="wca-check"><input type="checkbox" name="privacy_consent" required> <?php esc_html_e( 'I accept appointment-processing and privacy terms.', 'worldwide-clinic-appointments' ); ?></label>
				<label class="wca-check"><input type="checkbox" name="emergency_ack" required> <?php esc_html_e( 'I understand this is not emergency care.', 'worldwide-clinic-appointments' ); ?></label>
				<label class="wca-check"><input type="checkbox" name="telehealth_consent"> <?php esc_html_e( 'If I select an online or hybrid service, I consent to the remote-consultation context. Recording is separate and is never assumed.', 'worldwide-clinic-appointments' ); ?></label>
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
		$cursor = sanitize_text_field( wp_unslash( $_GET['wca_cursor'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed read-only cursor.
		$result = WCA_Query_API::list_patient_appointments( $user_id, array( 'cursor' => $cursor, 'per_page' => 30 ) );
		if ( is_wp_error( $result ) ) { return self::notice( __( 'Current appointment data is temporarily unavailable or you no longer have access.', 'worldwide-clinic-appointments' ), 'error' ); }
		$items = (array) ( $result['items'] ?? array() );
		ob_start(); ?>
		<main class="wca-shell" aria-labelledby="wca-appts-title"><h1 id="wca-appts-title"><?php esc_html_e( 'My appointments', 'worldwide-clinic-appointments' ); ?></h1>
		<?php if ( ! $items ) : ?><p><?php esc_html_e( 'No appointments found.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
		<div class="wca-list"><?php foreach ( $items as $item ) { echo self::appointment_projection_card( $item ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ } ?></div>
		<?php if ( ! empty( $result['next_cursor'] ) ) : $next = add_query_arg( 'wca_cursor', $result['next_cursor'], home_url( '/appointments/' ) ); ?><nav class="wca-pagination" aria-label="<?php esc_attr_e( 'Appointment pages', 'worldwide-clinic-appointments' ); ?>"><a class="wca-button wca-button-secondary" href="<?php echo esc_url( $next ); ?>"><?php esc_html_e( 'Next appointments', 'worldwide-clinic-appointments' ); ?></a></nav><?php endif; ?>
		</main>
		<?php return ob_get_clean();
	}

	private static function appointment_projection_card( $item ) {
		$item = is_array( $item ) ? $item : array();
		$ref = strtolower( sanitize_text_field( (string) ( $item['public_ref'] ?? '' ) ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/', $ref ) ) { return ''; }
		$status = sanitize_key( (string) ( $item['status'] ?? '' ) );
		$when = sanitize_text_field( (string) ( $item['scheduled_at_utc'] ?? '' ) );
		$timezone = sanitize_text_field( (string) ( $item['timezone'] ?? 'UTC' ) );
		$display_when = self::appointment_time_label( $when, $timezone );
		$version = absint( $item['record_version'] ?? 0 );
		$actions = array_values( array_filter( array_map( 'sanitize_key', (array) ( $item['allowed_actions'] ?? array() ) ) ) );
		ob_start(); ?>
		<article class="wca-card wca-appointment" data-wca-appointment-ref="<?php echo esc_attr( $ref ); ?>" data-wca-version="<?php echo esc_attr( $version ); ?>" data-wca-status="<?php echo esc_attr( $status ); ?>">
			<header><h2><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></h2><p><time datetime="<?php echo esc_attr( $when ? gmdate( 'c', strtotime( $when . ' UTC' ) ) : '' ); ?>"><?php echo esc_html( $display_when ); ?></time></p></header>
			<div class="wca-actions"><?php foreach ( $actions as $action ) : ?><button type="button" class="wca-button wca-button-secondary" data-wca-transition="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $action ) ) ); ?></button><?php endforeach; ?><a class="wca-button wca-button-secondary" href="<?php echo esc_url( home_url( '/appointment/' . rawurlencode( $ref ) . '/' ) ); ?>"><?php esc_html_e( 'View details', 'worldwide-clinic-appointments' ); ?></a><button type="button" class="wca-button wca-button-secondary" data-wca-calendar-download><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></button></div>
			<p data-wca-status role="status" aria-live="polite"></p>
		</article>
		<?php return ob_get_clean();
	}

	private static function appointment( $public_ref ) {
		$public_ref = sanitize_text_field( $public_ref );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $public_ref ) ) { return self::notice( __( 'Appointment was not found or you do not have access.', 'worldwide-clinic-appointments' ), 'error' ); }
		$ids = get_posts( array( 'post_type' => SWC_Helpers::TYPE, 'post_status' => array( 'private','publish' ), 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => '_swc_public_ref', 'meta_value' => $public_ref ) );
		$id = 1 === count( $ids ) ? absint( $ids[0] ) : 0;
		$access = $id ? WCA_Authorization::can_view_appointment( $id ) : new WP_Error( 'missing', 'missing' );
		if ( is_wp_error( $access ) ) { return self::notice( __( 'Appointment was not found or you do not have access.', 'worldwide-clinic-appointments' ), 'error' ); }
		return '<main class="wca-shell" aria-labelledby="wca-appt-title"><h1 id="wca-appt-title">' . esc_html__( 'Appointment', 'worldwide-clinic-appointments' ) . '</h1>' . self::appointment_card( $id, true ) . '</main>';
	}

	private static function appointment_card( $id, $detailed = false ) {
		$status = SWC_Helpers::status( $id );
		$actor = WCA_Authorization::appointment_actor( $id, get_current_user_id() );
		$actions = WCA_Contracts::allowed_transitions( $actor, $status );
		$ref = (string) SWC_Helpers::meta( $id, 'public_ref', '' );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return ''; }
		$when = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
		$timezone = (string) SWC_Helpers::meta( $id, 'patient_timezone', 'UTC' );
		$display_when = self::appointment_time_label( $when, $timezone );
		ob_start(); ?>
		<article class="wca-card wca-appointment" data-wca-appointment-ref="<?php echo esc_attr( strtolower( $ref ) ); ?>" data-wca-version="<?php echo esc_attr( SWC_Helpers::record_version( $id ) ); ?>" data-wca-status="<?php echo esc_attr( $status ); ?>">
			<header><h2><?php echo esc_html( ucfirst( str_replace( '_', ' ', $status ) ) ); ?></h2><p><time datetime="<?php echo esc_attr( $when ? gmdate( 'c', strtotime( $when . ' UTC' ) ) : '' ); ?>"><?php echo esc_html( $display_when ); ?></time></p></header>
			<?php if ( $detailed ) : ?><dl><dt><?php esc_html_e( 'Reference', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( strtolower( $ref ) ); ?></dd><dt><?php esc_html_e( 'Consultation', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( (string) SWC_Helpers::meta( $id, 'consultation_type' ) ); ?></dd></dl><?php endif; ?>
			<div class="wca-actions"><?php foreach ( $actions as $action ) : ?><button type="button" class="wca-button wca-button-secondary" data-wca-transition="<?php echo esc_attr( $action ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $action ) ) ); ?></button><?php endforeach; ?><?php if ( ! $detailed ) : ?><a class="wca-button wca-button-secondary" href="<?php echo esc_url( home_url( '/appointment/' . rawurlencode( strtolower( $ref ) ) . '/' ) ); ?>"><?php esc_html_e( 'View details', 'worldwide-clinic-appointments' ); ?></a><?php endif; ?><button type="button" class="wca-button wca-button-secondary" data-wca-calendar-download><?php esc_html_e( 'Calendar file', 'worldwide-clinic-appointments' ); ?></button></div>
			<p data-wca-status role="status" aria-live="polite"></p>
		</article>
		<?php return ob_get_clean();
	}

	private static function dashboard() {
		$claims = WCA_Authorization::claims();
		if ( is_wp_error( $claims ) || ! in_array( $claims['role'], array( 'doctor','founder','administrator','clinic_staff' ), true ) ) { return self::notice( __( 'Verified clinic access is required.', 'worldwide-clinic-appointments' ), 'error' ); }
		$user_id = get_current_user_id();
		WCA_Repository::clear_read_error();
		$clinics = WCA_Repository::list_clinics( array( 'owner_user_id' => $user_id, 'status' => '', 'per_page' => 50 ) );
		$clinic_list_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $clinic_list_error ) ) { return self::notice( __( 'Clinic dashboard data is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }
		$seen = array();
		foreach ( $clinics as $clinic ) { $seen[ absint( $clinic['id'] ) ] = true; }
		foreach ( WCA_Authorization::delegated_clinic_ids( $user_id, 'clinic_manage' ) as $clinic_id ) {
			$clinic_id = absint( $clinic_id );
			if ( ! $clinic_id || isset( $seen[ $clinic_id ] ) ) { continue; }
			WCA_Repository::clear_read_error();
			$clinic = WCA_Repository::get_clinic( $clinic_id, false );
			$clinic_read_error = WCA_Repository::consume_read_error();
			if ( is_wp_error( $clinic_read_error ) ) { return self::notice( __( 'Clinic dashboard data is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }
			if ( ! $clinic || is_wp_error( WCA_Authorization::can_manage_clinic( $clinic, $user_id ) ) ) { continue; }
			$clinics[] = $clinic;
			$seen[ $clinic_id ] = true;
		}
		ob_start(); ?>
		<main class="wca-shell" aria-labelledby="wca-dashboard-title"><h1 id="wca-dashboard-title"><?php esc_html_e( 'Clinic dashboard', 'worldwide-clinic-appointments' ); ?></h1>
		<p><?php esc_html_e( 'Manage only clinics you own or for which you hold a current explicit clinic-management delegation. Platform commission is always 0%.', 'worldwide-clinic-appointments' ); ?></p>
		<?php if ( ! $clinics ) : ?><p><?php esc_html_e( 'No manageable clinics were found.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
		<div class="wca-grid"><?php foreach ( $clinics as $clinic ) : ?><article class="wca-card"><h2><?php echo esc_html( $clinic['name'] ); ?></h2><p><?php echo esc_html( ucfirst( $clinic['status'] ) ); ?> · v<?php echo esc_html( $clinic['version'] ); ?></p><a class="wca-button" href="<?php echo esc_url( home_url( '/clinic/' . rawurlencode( $clinic['slug'] ) . '/' ) ); ?>"><?php esc_html_e( 'View public clinic', 'worldwide-clinic-appointments' ); ?></a></article><?php endforeach; ?></div>
		</main><?php return ob_get_clean();
	}


	private static function appointment_time_label( $when, $timezone ) {
		$when = trim( (string) $when );
		$timezone = trim( (string) $timezone );
		if ( '' === $when ) { return __( 'Time pending', 'worldwide-clinic-appointments' ); }
		if ( ! WCA_Service::valid_timezone( $timezone ) ) { $timezone = 'UTC'; }
		try {
			$utc = new DateTimeZone( 'UTC' );
			$target = new DateTimeZone( $timezone );
			$moment = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $when, $utc );
			$errors = DateTimeImmutable::getLastErrors();
			if ( ! $moment || ( is_array( $errors ) && ( ! empty( $errors['warning_count'] ) || ! empty( $errors['error_count'] ) ) ) ) {
				return __( 'Time pending', 'worldwide-clinic-appointments' );
			}
			return $moment->setTimezone( $target )->format( 'F j, Y g:i a' ) . ' ' . $timezone;
		} catch ( Exception $e ) {
			return __( 'Time pending', 'worldwide-clinic-appointments' );
		}
	}

	private static function currency_fraction_digits( $currency ) {
		$currency = strtoupper( sanitize_text_field( $currency ) );
		$zero = array( 'BIF','CLP','DJF','GNF','ISK','JPY','KMF','KRW','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF' );
		$three = array( 'BHD','IQD','JOD','KWD','LYD','OMR','TND' );
		$four = array( 'CLF','UYW' );
		if ( in_array( $currency, $zero, true ) ) { return 0; }
		if ( in_array( $currency, $three, true ) ) { return 3; }
		if ( in_array( $currency, $four, true ) ) { return 4; }
		return 2;
	}

	private static function money( $minor, $currency ) {
		$currency = strtoupper( sanitize_text_field( $currency ) );
		$digits = self::currency_fraction_digits( $currency );
		$divisor = 10 ** $digits;
		return $currency . ' ' . number_format_i18n( absint( $minor ) / $divisor, $digits );
	}

	private static function notice( $message, $type = 'info' ) {
		return '<div class="wca-shell"><div class="wca-alert wca-alert-' . esc_attr( $type ) . '" role="alert">' . esc_html( $message ) . '</div></div>';
	}
}
