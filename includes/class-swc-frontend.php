<?php
/**
 * Public clinic and private role-aware dashboards.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Frontend {
	public function hooks() {
		add_shortcode( 'swc_worldwide_clinic', array( $this, 'clinic' ) );
		add_shortcode( 'swc_request_appointment', array( $this, 'request' ) );
		add_shortcode( 'swc_my_appointments', array( $this, 'patient' ) );
		add_shortcode( 'swc_doctor_appointments', array( $this, 'doctor' ) );
		add_shortcode( 'swc_doctor_availability', array( $this, 'availability' ) );
	}

	public function clinic() {
		$pages    = SWC_Helpers::pages();
		$all      = SWC_Helpers::doctor_ids();
		$paged    = max( 1, isset( $_GET['swc_doctors_page'] ) ? absint( $_GET['swc_doctors_page'] ) : 1 );
		$per_page = 12;
		$doctors  = array_slice( $all, ( $paged - 1 ) * $per_page, $per_page );
		$founder  = class_exists( 'SPD_Helpers' ) ? SPD_Helpers::founder() : array( 'phone' => '', 'whatsapp' => '' );
		$phone    = get_option( 'swc_clinic_phone', $founder['phone'] ?? '' );
		$whatsapp = get_option( 'swc_clinic_whatsapp', $founder['whatsapp'] ?? '' );
		ob_start();
		?>
		<main class="swc-shell">
			<header class="swc-hero">
				<div>
					<span><?php esc_html_e( 'Responsible Global Access', 'worldwide-clinic-appointments' ); ?></span>
					<h1><?php esc_html_e( 'Worldwide Clinic', 'worldwide-clinic-appointments' ); ?></h1>
					<p><?php esc_html_e( 'Request an online or in-person appointment with an eligible verified practitioner. This service is not emergency care and does not guarantee diagnosis, treatment, or outcomes.', 'worldwide-clinic-appointments' ); ?></p>
					<div class="swc-hero-actions">
						<?php if ( ! empty( $pages['request'] ) ) : ?><a class="swc-button" href="<?php echo esc_url( get_permalink( $pages['request'] ) ); ?>"><?php esc_html_e( 'Request an Appointment', 'worldwide-clinic-appointments' ); ?></a><?php endif; ?>
						<?php if ( ! empty( $pages['patient'] ) ) : ?><a class="swc-button swc-light" href="<?php echo esc_url( get_permalink( $pages['patient'] ) ); ?>"><?php esc_html_e( 'My Appointments', 'worldwide-clinic-appointments' ); ?></a><?php endif; ?>
					</div>
					<div class="swc-contact">
						<?php if ( SWC_Helpers::phone( $phone ) ) : ?><a class="swc-button swc-light" href="tel:<?php echo esc_attr( SWC_Helpers::phone( $phone ) ); ?>"><?php esc_html_e( 'Phone', 'worldwide-clinic-appointments' ); ?></a><?php endif; ?>
						<?php if ( SWC_Helpers::whatsapp( $whatsapp ) ) : ?><a class="swc-button swc-light is-whatsapp" href="<?php echo esc_url( SWC_Helpers::whatsapp( $whatsapp ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'WhatsApp', 'worldwide-clinic-appointments' ); ?></a><?php endif; ?>
					</div>
				</div>
				<aside role="alert"><strong><?php esc_html_e( 'Emergency warning', 'worldwide-clinic-appointments' ); ?></strong><p><?php echo esc_html( SWC_Helpers::emergency_notice() ); ?></p></aside>
			</header>
			<section class="swc-steps" aria-label="<?php esc_attr_e( 'Appointment request steps', 'worldwide-clinic-appointments' ); ?>">
				<article><b aria-hidden="true">1</b><h2><?php esc_html_e( 'Choose a Doctor', 'worldwide-clinic-appointments' ); ?></h2><p><?php esc_html_e( 'Review an eligible verified public profile and the published consultation schedule.', 'worldwide-clinic-appointments' ); ?></p></article>
				<article><b aria-hidden="true">2</b><h2><?php esc_html_e( 'Request a Time', 'worldwide-clinic-appointments' ); ?></h2><p><?php esc_html_e( 'Log in and choose a valid slot within the doctor’s published availability.', 'worldwide-clinic-appointments' ); ?></p></article>
				<article><b aria-hidden="true">3</b><h2><?php esc_html_e( 'Receive Confirmation', 'worldwide-clinic-appointments' ); ?></h2><p><?php esc_html_e( 'The doctor may accept, decline, or propose another available time.', 'worldwide-clinic-appointments' ); ?></p></article>
			</section>
			<section class="swc-section">
				<div class="swc-section-head"><div><span><?php esc_html_e( 'Verified Professionals', 'worldwide-clinic-appointments' ); ?></span><h2><?php esc_html_e( 'Available Doctors', 'worldwide-clinic-appointments' ); ?></h2></div></div>
				<div class="swc-grid">
					<?php if ( $doctors ) : foreach ( $doctors as $doctor_id ) : echo $this->doctor_card( $doctor_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					endforeach; else : ?><div class="swc-empty"><?php esc_html_e( 'No eligible verified doctors have published clinic availability yet.', 'worldwide-clinic-appointments' ); ?></div><?php endif; ?>
				</div>
				<?php echo wp_kses_post( $this->pagination( 'swc_doctors_page', $paged, (int) ceil( count( $all ) / $per_page ) ) ); ?>
			</section>
			<p class="swc-disclaimer"><?php esc_html_e( 'Appointments do not establish emergency coverage. Confirm professional licensing, fees, scope, and suitability directly before care.', 'worldwide-clinic-appointments' ); ?></p>
		</main>
		<?php
		return ob_get_clean();
	}

	private function doctor_card( $id ) {
		$user = get_userdata( $id );
		if ( ! $user ) {
			return '';
		}
		$a         = SWC_Helpers::availability( $id );
		$valid     = SWC_Helpers::availability_is_valid( $a );
		$available = $valid && $a['accepting'] && ! $a['unavailable'];
		$pages     = SWC_Helpers::pages();
		$request   = $available && ! empty( $pages['request'] ) ? add_query_arg( 'doctor_ref', SWC_Helpers::practitioner_ref( $id ), get_permalink( $pages['request'] ) ) : '';
		$photo     = absint( SWC_Helpers::profile_value( $id, 'profile_photo_id', 0 ) );
		$days      = array_map( 'ucfirst', $a['days'] );
		$location  = trim( SWC_Helpers::profile_value( $id, 'city' ) . ', ' . SWC_Helpers::profile_value( $id, 'country' ), ', ' );
		ob_start();
		?>
		<article class="swc-card">
			<div class="swc-avatar"><?php echo $photo ? wp_get_attachment_image( $photo, 'thumbnail', false, array( 'alt' => $user->display_name, 'loading' => 'lazy' ) ) : esc_html( SDD_Helpers::initials( $user->display_name ) ); ?></div>
			<span class="swc-badge"><?php esc_html_e( '✓ Verified Doctor', 'worldwide-clinic-appointments' ); ?></span>
			<h3><a href="<?php echo esc_url( SDD_Helpers::profile_url( $id ) ); ?>"><?php echo esc_html( $user->display_name ); ?></a></h3>
			<?php if ( $location ) : ?><p><?php echo esc_html( $location ); ?></p><?php endif; ?>
			<div class="swc-tags">
				<?php if ( $a['online'] ) : ?><span><?php esc_html_e( 'Online', 'worldwide-clinic-appointments' ); ?></span><?php endif; ?>
				<?php if ( $a['in_person'] ) : ?><span><?php esc_html_e( 'In person', 'worldwide-clinic-appointments' ); ?></span><?php endif; ?>
				<span><?php echo esc_html( $a['unavailable'] ? __( 'Temporarily unavailable', 'worldwide-clinic-appointments' ) : ( $available ? __( 'Accepting requests', 'worldwide-clinic-appointments' ) : __( 'Not accepting requests', 'worldwide-clinic-appointments' ) ) ); ?></span>
			</div>
			<?php if ( $valid ) : ?>
				<dl class="swc-availability-summary">
					<div><dt><?php esc_html_e( 'Days', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( implode( ', ', $days ) ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Hours', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( $a['start'] . '–' . $a['end'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Time zone', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( $a['timezone'] ); ?></dd></div>
					<div><dt><?php esc_html_e( 'Duration', 'worldwide-clinic-appointments' ); ?></dt><dd><?php echo esc_html( sprintf( __( '%d minutes', 'worldwide-clinic-appointments' ), $a['duration'] ) ); ?></dd></div>
				</dl>
			<?php endif; ?>
			<?php if ( $available ) : ?><a class="swc-button" href="<?php echo esc_url( $request ); ?>"><?php esc_html_e( 'Request Appointment', 'worldwide-clinic-appointments' ); ?></a><?php else : ?><span class="swc-button is-disabled" aria-disabled="true"><?php esc_html_e( 'Requests Unavailable', 'worldwide-clinic-appointments' ); ?></span><?php endif; ?>
		</article>
		<?php
		return ob_get_clean();
	}

	public function request() {
		if ( ! is_user_logged_in() ) {
			return '<div class="swc-notice"><h2>' . esc_html__( 'Log in to request an appointment', 'worldwide-clinic-appointments' ) . '</h2><p>' . esc_html__( 'You may browse the clinic publicly, but an account is required to submit private contact information.', 'worldwide-clinic-appointments' ) . '</p><a class="swc-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log In', 'worldwide-clinic-appointments' ) . '</a></div>';
		}
		$ids          = SWC_Helpers::requestable_doctor_ids();
		$selected_ref = isset( $_GET['doctor_ref'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['doctor_ref'] ) ) ) : '';
		$selected     = $selected_ref ? SWC_Helpers::practitioner_id( $selected_ref ) : 0;
		if ( ! in_array( $selected, $ids, true ) ) {
			$selected = 0;
		}
		$user = wp_get_current_user();
		$zone = SWC_Helpers::user_timezone( $user->ID );
		ob_start();
		?>
		<main class="swc-shell">
			<header class="swc-page-head"><span><?php esc_html_e( 'Private Appointment Request', 'worldwide-clinic-appointments' ); ?></span><h1><?php esc_html_e( 'Request an Appointment', 'worldwide-clinic-appointments' ); ?></h1><p><?php esc_html_e( 'Provide only the minimum information needed to review scheduling.', 'worldwide-clinic-appointments' ); ?></p></header>
			<div class="swc-emergency" role="alert"><strong><?php esc_html_e( 'Emergency warning', 'worldwide-clinic-appointments' ); ?></strong><p><?php echo esc_html( SWC_Helpers::emergency_notice() ); ?></p></div>
			<?php if ( ! $ids ) : ?><div class="swc-empty"><?php esc_html_e( 'No eligible verified doctor is currently accepting appointment requests.', 'worldwide-clinic-appointments' ); ?></div><?php else : ?>
			<form class="swc-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="swc_submit_appointment"><?php wp_nonce_field( 'swc_submit_appointment', 'swc_nonce' ); ?>
				<label><?php esc_html_e( 'Verified doctor', 'worldwide-clinic-appointments' ); ?><select id="swc-doctor-select" name="doctor_ref" required><option value=""><?php esc_html_e( 'Choose a doctor', 'worldwide-clinic-appointments' ); ?></option><?php foreach ( $ids as $id ) : $a = SWC_Helpers::availability( $id ); ?><option value="<?php echo esc_attr( SWC_Helpers::practitioner_ref( $id ) ); ?>" data-online="<?php echo $a['online'] ? '1' : '0'; ?>" data-in-person="<?php echo $a['in_person'] ? '1' : '0'; ?>" <?php selected( $selected, $id ); ?>><?php echo esc_html( get_the_author_meta( 'display_name', $id ) ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Consultation type', 'worldwide-clinic-appointments' ); ?><select id="swc-consultation-type" name="consultation_type" required><option value=""><?php esc_html_e( 'Choose a consultation type', 'worldwide-clinic-appointments' ); ?></option><option value="online"><?php esc_html_e( 'Online consultation', 'worldwide-clinic-appointments' ); ?></option><option value="in-person"><?php esc_html_e( 'In-person consultation', 'worldwide-clinic-appointments' ); ?></option></select></label>
				<label><?php esc_html_e( 'Preferred date', 'worldwide-clinic-appointments' ); ?><input type="date" name="preferred_date" min="<?php echo esc_attr( gmdate( 'Y-m-d', time() + DAY_IN_SECONDS ) ); ?>" required></label>
				<label><?php esc_html_e( 'Preferred time', 'worldwide-clinic-appointments' ); ?><input type="time" name="preferred_time" required></label>
				<label><?php esc_html_e( 'Patient time zone', 'worldwide-clinic-appointments' ); ?><select id="swc-patient-timezone" name="patient_timezone" required><?php foreach ( SWC_Helpers::timezones() as $timezone ) : ?><option value="<?php echo esc_attr( $timezone ); ?>" <?php selected( $zone, $timezone ); ?>><?php echo esc_html( $timezone ); ?></option><?php endforeach; ?></select></label>
				<label><?php esc_html_e( 'Country', 'worldwide-clinic-appointments' ); ?><input name="country" maxlength="100" value="<?php echo esc_attr( SWC_Helpers::profile_value( $user->ID, 'country' ) ); ?>" required></label>
				<label><?php esc_html_e( 'City', 'worldwide-clinic-appointments' ); ?><input name="city" maxlength="100" value="<?php echo esc_attr( SWC_Helpers::profile_value( $user->ID, 'city' ) ); ?>"></label>
				<label><?php esc_html_e( 'Phone number', 'worldwide-clinic-appointments' ); ?><input name="phone" inputmode="tel" maxlength="18" value="<?php echo esc_attr( SWC_Helpers::profile_value( $user->ID, 'phone' ) ); ?>" placeholder="+12025550123" required></label>
				<label><?php esc_html_e( 'WhatsApp number', 'worldwide-clinic-appointments' ); ?><input name="whatsapp" inputmode="tel" maxlength="18" value="<?php echo esc_attr( SWC_Helpers::profile_value( $user->ID, 'whatsapp' ) ); ?>" placeholder="+12025550123" required></label>
				<label><?php esc_html_e( 'Duration of concern', 'worldwide-clinic-appointments' ); ?><input name="concern_duration" maxlength="120" placeholder="<?php esc_attr_e( 'For example: three days', 'worldwide-clinic-appointments' ); ?>"></label>
				<label class="swc-wide"><?php esc_html_e( 'General reason for consultation', 'worldwide-clinic-appointments' ); ?><textarea name="reason" maxlength="1500" required placeholder="<?php esc_attr_e( 'Briefly describe the scheduling concern. Do not upload records or include unnecessary sensitive details.', 'worldwide-clinic-appointments' ); ?>"></textarea></label>
				<label class="swc-check swc-wide"><input type="checkbox" name="emergency_confirm" value="1" required> <?php esc_html_e( 'I understand that this service is not for emergencies.', 'worldwide-clinic-appointments' ); ?></label>
				<label class="swc-check swc-wide"><input type="checkbox" name="consent" value="1" required> <?php esc_html_e( 'I consent to sharing this request and my contact details with the selected doctor and authorized clinic administrators. A different doctor requires my separate consent.', 'worldwide-clinic-appointments' ); ?></label>
				<button class="swc-button" type="submit"><?php esc_html_e( 'Submit Appointment Request', 'worldwide-clinic-appointments' ); ?></button>
			</form>
			<?php endif; ?>
		</main>
		<?php
		return ob_get_clean();
	}

	public function patient() {
		if ( ! is_user_logged_in() ) {
			return '<div class="swc-notice"><a class="swc-button" href="' . esc_url( wp_login_url( get_permalink() ) ) . '">' . esc_html__( 'Log In to View Appointments', 'worldwide-clinic-appointments' ) . '</a></div>';
		}
		return $this->dashboard_query( __( 'My Appointments', 'worldwide-clinic-appointments' ), 'patient' );
	}

	public function doctor() {
		if ( ! is_user_logged_in() || ! SWC_Helpers::is_verified_doctor( get_current_user_id() ) ) {
			return '<div class="swc-notice">' . esc_html__( 'Eligible verified doctor access is required.', 'worldwide-clinic-appointments' ) . '</div>';
		}
		return $this->dashboard_query( __( 'Doctor Appointments', 'worldwide-clinic-appointments' ), 'doctor' );
	}

	private function dashboard_query( $title, $role ) {
		$paged = max( 1, isset( $_GET['swc_page'] ) ? absint( $_GET['swc_page'] ) : 1 );
		$args  = array(
			'post_type'      => SWC_Helpers::TYPE,
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => 20,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		if ( 'patient' === $role ) {
			$args['author'] = get_current_user_id();
		} else {
			$args['meta_key']   = '_swc_doctor_id';
			$args['meta_value'] = get_current_user_id();
		}
		$query = new WP_Query( $args );
		ob_start();
		?>
		<main class="swc-shell"><header class="swc-page-head"><span><?php esc_html_e( 'Private Clinic Dashboard', 'worldwide-clinic-appointments' ); ?></span><h1><?php echo esc_html( $title ); ?></h1></header><div class="swc-list">
		<?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); echo $this->appointment_card( get_post(), $role ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		endwhile; wp_reset_postdata(); else : ?><div class="swc-empty"><?php esc_html_e( 'No appointments found.', 'worldwide-clinic-appointments' ); ?></div><?php endif; ?>
		</div><?php echo wp_kses_post( $this->pagination( 'swc_page', $paged, (int) $query->max_num_pages ) ); ?></main>
		<?php
		return ob_get_clean();
	}

	private function appointment_card( $appointment, $role ) {
		$id      = $appointment->ID;
		$doctor  = absint( SWC_Helpers::meta( $id, 'doctor_id' ) );
		$patient = absint( $appointment->post_author );
		$status  = SWC_Helpers::status( $id );
		$version = SWC_Helpers::record_version( $id );
		$zone    = 'doctor' === $role ? SWC_Helpers::doctor_meta( $doctor, 'timezone', 'UTC' ) : SWC_Helpers::meta( $id, 'patient_timezone', 'UTC' );
		ob_start();
		?>
		<article class="swc-appointment">
			<header><span class="swc-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( SWC_Helpers::statuses()[ $status ] ); ?></span><strong><?php echo esc_html( sprintf( __( 'Appointment #%d', 'worldwide-clinic-appointments' ), $id ) ); ?></strong></header>
			<div class="swc-appointment-grid">
				<p><b><?php esc_html_e( 'Doctor', 'worldwide-clinic-appointments' ); ?></b><?php echo esc_html( get_the_author_meta( 'display_name', $doctor ) ); ?></p>
				<p><b><?php esc_html_e( 'Patient', 'worldwide-clinic-appointments' ); ?></b><?php echo esc_html( get_the_author_meta( 'display_name', $patient ) ); ?></p>
				<p><b><?php esc_html_e( 'Requested time', 'worldwide-clinic-appointments' ); ?></b><?php echo esc_html( SWC_Helpers::display_time( SWC_Helpers::meta( $id, 'preferred_at_utc' ), $zone ) ); ?></p>
				<p><b><?php esc_html_e( 'Consultation', 'worldwide-clinic-appointments' ); ?></b><?php echo esc_html( ucwords( str_replace( '-', ' ', SWC_Helpers::meta( $id, 'consultation_type' ) ) ) ); ?></p>
				<p><b><?php esc_html_e( 'Phone', 'worldwide-clinic-appointments' ); ?></b><a href="tel:<?php echo esc_attr( SWC_Helpers::meta( $id, 'phone' ) ); ?>"><?php echo esc_html( SWC_Helpers::meta( $id, 'phone' ) ); ?></a></p>
				<p><b><?php esc_html_e( 'WhatsApp', 'worldwide-clinic-appointments' ); ?></b><a href="<?php echo esc_url( SWC_Helpers::whatsapp( SWC_Helpers::meta( $id, 'whatsapp' ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open WhatsApp', 'worldwide-clinic-appointments' ); ?></a></p>
			</div>
			<details><summary><?php esc_html_e( 'Private request details', 'worldwide-clinic-appointments' ); ?></summary><p><?php echo nl2br( esc_html( SWC_Helpers::meta( $id, 'reason' ) ) ); ?></p><p><b><?php esc_html_e( 'Duration of concern:', 'worldwide-clinic-appointments' ); ?></b> <?php echo esc_html( SWC_Helpers::meta( $id, 'concern_duration', __( 'Not provided', 'worldwide-clinic-appointments' ) ) ); ?></p>
			<?php if ( 'doctor' === $role && SWC_Helpers::meta( $id, 'doctor_private_note' ) ) : ?><p class="swc-private-note"><b><?php esc_html_e( 'Private doctor note:', 'worldwide-clinic-appointments' ); ?></b> <?php echo nl2br( esc_html( SWC_Helpers::meta( $id, 'doctor_private_note' ) ) ); ?></p><?php endif; ?>
			<?php if ( SWC_Helpers::meta( $id, 'patient_message' ) ) : ?><p><b><?php esc_html_e( 'Doctor message:', 'worldwide-clinic-appointments' ); ?></b> <?php echo nl2br( esc_html( SWC_Helpers::meta( $id, 'patient_message' ) ) ); ?></p><?php endif; ?>
			</details>
			<?php if ( 'patient' === $role ) : ?>
				<?php echo $this->patient_actions( $id, $status, $version ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php else : ?>
				<?php echo $this->doctor_form( $id, $status, $version ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			<?php endif; ?>
		</article>
		<?php
		return ob_get_clean();
	}

	private function patient_actions( $id, $status, $version ) {
		ob_start();
		$proposed_doctor = absint( SWC_Helpers::meta( $id, 'proposed_doctor_id' ) );
		if ( $proposed_doctor ) : ?>
			<section class="swc-proposal"><h3><?php esc_html_e( 'Doctor reassignment proposal', 'worldwide-clinic-appointments' ); ?></h3><p><?php echo esc_html( sprintf( __( 'Proposed doctor: %s', 'worldwide-clinic-appointments' ), get_the_author_meta( 'display_name', $proposed_doctor ) ) ); ?></p><p><?php echo esc_html( SWC_Helpers::meta( $id, 'reassignment_reason' ) ); ?></p><div class="swc-inline-actions">
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="swc_patient_accept_reassignment"><input type="hidden" name="appointment_id" value="<?php echo absint( $id ); ?>"><input type="hidden" name="expected_version" value="<?php echo absint( $version ); ?>"><?php wp_nonce_field( 'swc_patient_accept_reassignment_' . $id ); ?><button class="swc-button" type="submit"><?php esc_html_e( 'Accept Reassignment', 'worldwide-clinic-appointments' ); ?></button></form>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="swc_patient_decline_reassignment"><input type="hidden" name="appointment_id" value="<?php echo absint( $id ); ?>"><input type="hidden" name="expected_version" value="<?php echo absint( $version ); ?>"><?php wp_nonce_field( 'swc_patient_decline_reassignment_' . $id ); ?><button class="swc-link-button" type="submit"><?php esc_html_e( 'Decline Reassignment', 'worldwide-clinic-appointments' ); ?></button></form>
			</div></section>
		<?php endif;
		if ( 'reschedule_pending' === $status ) :
			$zone = SWC_Helpers::meta( $id, 'patient_timezone', 'UTC' ); ?>
			<section class="swc-proposal"><h3><?php esc_html_e( 'Reschedule proposal', 'worldwide-clinic-appointments' ); ?></h3><p><?php echo esc_html( SWC_Helpers::display_time( SWC_Helpers::meta( $id, 'proposed_at_utc' ), $zone ) ); ?></p><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="swc_patient_accept_reschedule"><input type="hidden" name="appointment_id" value="<?php echo absint( $id ); ?>"><input type="hidden" name="expected_status" value="<?php echo esc_attr( $status ); ?>"><input type="hidden" name="expected_version" value="<?php echo absint( $version ); ?>"><?php wp_nonce_field( 'swc_patient_accept_' . $id ); ?><button class="swc-button" type="submit"><?php esc_html_e( 'Accept Proposed Time', 'worldwide-clinic-appointments' ); ?></button></form></section>
		<?php endif;
		if ( SWC_Helpers::can_transition( 'patient', $status, 'cancelled' ) ) : ?>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="swc_patient_cancel"><input type="hidden" name="appointment_id" value="<?php echo absint( $id ); ?>"><input type="hidden" name="expected_status" value="<?php echo esc_attr( $status ); ?>"><input type="hidden" name="expected_version" value="<?php echo absint( $version ); ?>"><?php wp_nonce_field( 'swc_patient_cancel_' . $id ); ?><button class="swc-link-button" type="submit"><?php esc_html_e( 'Cancel Request', 'worldwide-clinic-appointments' ); ?></button></form>
		<?php endif;
		return ob_get_clean();
	}

	private function doctor_form( $id, $current, $version ) {
		$allowed = SWC_Helpers::allowed_transitions( 'doctor', $current );
		$zone    = SWC_Helpers::doctor_meta( get_current_user_id(), 'timezone', 'UTC' );
		ob_start();
		if ( ! $allowed ) : ?><p class="swc-terminal"><?php esc_html_e( 'This appointment is terminal and cannot be revived.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
		<form class="swc-doctor-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="swc_doctor_update"><input type="hidden" name="appointment_id" value="<?php echo absint( $id ); ?>"><input type="hidden" name="expected_status" value="<?php echo esc_attr( $current ); ?>"><input type="hidden" name="expected_version" value="<?php echo absint( $version ); ?>"><?php wp_nonce_field( 'swc_doctor_update_' . $id ); ?>
		<label><?php esc_html_e( 'Status', 'worldwide-clinic-appointments' ); ?><select name="status"><option value="<?php echo esc_attr( $current ); ?>"><?php echo esc_html( SWC_Helpers::statuses()[ $current ] ); ?></option><?php foreach ( $allowed as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( SWC_Helpers::statuses()[ $status ] ); ?></option><?php endforeach; ?></select></label>
		<label><?php esc_html_e( 'Patient-visible message', 'worldwide-clinic-appointments' ); ?><textarea name="patient_message" maxlength="1000"><?php echo esc_textarea( SWC_Helpers::meta( $id, 'patient_message' ) ); ?></textarea></label>
		<label><?php esc_html_e( 'Private doctor/administrator note', 'worldwide-clinic-appointments' ); ?><textarea name="doctor_private_note" maxlength="1000"><?php echo esc_textarea( SWC_Helpers::meta( $id, 'doctor_private_note' ) ); ?></textarea><small><?php esc_html_e( 'This note is never rendered in the patient dashboard or notification.', 'worldwide-clinic-appointments' ); ?></small></label>
		<div class="swc-reschedule" hidden><label><?php esc_html_e( 'Proposed date', 'worldwide-clinic-appointments' ); ?><input type="date" name="new_date"></label><label><?php esc_html_e( 'Proposed time', 'worldwide-clinic-appointments' ); ?><input type="time" name="new_time"></label><label><?php esc_html_e( 'Time zone', 'worldwide-clinic-appointments' ); ?><select name="new_timezone"><?php foreach ( SWC_Helpers::timezones() as $timezone ) : ?><option value="<?php echo esc_attr( $timezone ); ?>" <?php selected( $zone, $timezone ); ?>><?php echo esc_html( $timezone ); ?></option><?php endforeach; ?></select></label></div>
		<button class="swc-button" type="submit"><?php esc_html_e( 'Update Appointment', 'worldwide-clinic-appointments' ); ?></button></form>
		<?php
		return ob_get_clean();
	}

	public function availability() {
		if ( ! is_user_logged_in() || ! SWC_Helpers::is_verified_doctor( get_current_user_id() ) ) {
			return '<div class="swc-notice">' . esc_html__( 'Eligible verified doctor access is required.', 'worldwide-clinic-appointments' ) . '</div>';
		}
		$id   = get_current_user_id();
		$data = SWC_Helpers::availability( $id );
		ob_start();
		?>
		<main class="swc-shell"><header class="swc-page-head"><span><?php esc_html_e( 'Clinic Schedule', 'worldwide-clinic-appointments' ); ?></span><h1><?php esc_html_e( 'Doctor Availability', 'worldwide-clinic-appointments' ); ?></h1><p><?php esc_html_e( 'Publish an enforceable appointment window. Requests outside this schedule are rejected.', 'worldwide-clinic-appointments' ); ?></p></header>
		<form class="swc-form" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="swc_save_availability"><?php wp_nonce_field( 'swc_save_availability', 'swc_nonce' ); ?>
		<fieldset class="swc-wide"><legend><?php esc_html_e( 'Available days', 'worldwide-clinic-appointments' ); ?></legend><div class="swc-days"><?php foreach ( SWC_Helpers::weekdays() as $day ) : ?><label><input type="checkbox" name="days[]" value="<?php echo esc_attr( $day ); ?>" <?php checked( in_array( $day, $data['days'], true ) ); ?>><?php echo esc_html( ucfirst( $day ) ); ?></label><?php endforeach; ?></div></fieldset>
		<label><?php esc_html_e( 'Start time', 'worldwide-clinic-appointments' ); ?><input type="time" name="start_time" value="<?php echo esc_attr( $data['start'] ); ?>" required></label><label><?php esc_html_e( 'End time', 'worldwide-clinic-appointments' ); ?><input type="time" name="end_time" value="<?php echo esc_attr( $data['end'] ); ?>" required></label>
		<label><?php esc_html_e( 'Time zone', 'worldwide-clinic-appointments' ); ?><select name="timezone"><?php foreach ( SWC_Helpers::timezones() as $timezone ) : ?><option value="<?php echo esc_attr( $timezone ); ?>" <?php selected( $data['timezone'], $timezone ); ?>><?php echo esc_html( $timezone ); ?></option><?php endforeach; ?></select></label>
		<label><?php esc_html_e( 'Appointment duration in minutes', 'worldwide-clinic-appointments' ); ?><input type="number" name="duration" min="10" max="180" value="<?php echo absint( $data['duration'] ); ?>" required></label>
		<div class="swc-checks swc-wide"><label><input type="checkbox" name="online" value="1" <?php checked( $data['online'] ); ?>> <?php esc_html_e( 'Online consultation available', 'worldwide-clinic-appointments' ); ?></label><label><input type="checkbox" name="in_person" value="1" <?php checked( $data['in_person'] ); ?>> <?php esc_html_e( 'In-person consultation available', 'worldwide-clinic-appointments' ); ?></label><label><input type="checkbox" name="accepting" value="1" <?php checked( $data['accepting'] ); ?>> <?php esc_html_e( 'Accepting new appointment requests', 'worldwide-clinic-appointments' ); ?></label><label><input type="checkbox" name="unavailable" value="1" <?php checked( $data['unavailable'] ); ?>> <?php esc_html_e( 'Temporarily unavailable', 'worldwide-clinic-appointments' ); ?></label></div>
		<button class="swc-button" type="submit"><?php esc_html_e( 'Save Availability', 'worldwide-clinic-appointments' ); ?></button></form></main>
		<?php
		return ob_get_clean();
	}

	private function pagination( $key, $current, $total ) {
		if ( $total <= 1 ) {
			return '';
		}
		return (string) paginate_links(
			array(
				'base'      => add_query_arg( $key, '%#%' ),
				'format'    => '',
				'current'   => $current,
				'total'     => $total,
				'prev_text' => __( 'Previous', 'worldwide-clinic-appointments' ),
				'next_text' => __( 'Next', 'worldwide-clinic-appointments' ),
			)
		);
	}
}
