<?php
/**
 * Restricted administration, audit visibility, repair, and data controls.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Admin {
	public function hooks() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_post_swc_admin_update', array( $this, 'update' ) );
		add_action( 'admin_post_swc_save_clinic_settings', array( $this, 'save_settings' ) );
		add_action( 'admin_post_swc_complete_repair', array( $this, 'repair' ) );
		add_action( 'admin_post_swc_purge_data', array( $this, 'purge' ) );
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	public function menu() {
		add_menu_page(
			__( 'Clinic Management', 'worldwide-clinic-appointments' ),
			__( 'Clinic Management', 'worldwide-clinic-appointments' ),
			'manage_worldwide_clinic',
			'clinic-management',
			array( $this, 'page' ),
			'dashicons-calendar-alt',
			29
		);
		add_submenu_page( 'clinic-management', __( 'Appointments', 'worldwide-clinic-appointments' ), __( 'Appointments', 'worldwide-clinic-appointments' ), 'manage_worldwide_clinic', 'clinic-management', array( $this, 'page' ) );
		add_submenu_page( 'clinic-management', __( 'Clinic Settings', 'worldwide-clinic-appointments' ), __( 'Clinic Settings', 'worldwide-clinic-appointments' ), 'manage_worldwide_clinic', 'clinic-settings', array( $this, 'settings' ) );
		add_submenu_page( 'clinic-management', __( 'System Check', 'worldwide-clinic-appointments' ), __( 'System Check', 'worldwide-clinic-appointments' ), 'manage_worldwide_clinic', 'clinic-system-check', array( $this, 'system_check' ) );
	}

	public function page() {
		$this->guard();
		$status   = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$doctor   = isset( $_GET['doctor'] ) ? absint( $_GET['doctor'] ) : 0;
		$paged    = max( 1, isset( $_GET['swc_paged'] ) ? absint( $_GET['swc_paged'] ) : 1 );
		$per_page = 25;
		$args     = array(
			'post_type'      => SWC_Helpers::TYPE,
			'post_status'    => array( 'private', 'publish' ),
			'posts_per_page' => $per_page,
			'paged'          => $paged,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);
		$meta_query = array( 'relation' => 'AND' );
		if ( $status && isset( SWC_Helpers::statuses()[ $status ] ) ) {
			$meta_query[] = array( 'key' => '_swc_status', 'value' => $status );
		}
		if ( $doctor ) {
			$meta_query[] = array( 'key' => '_swc_doctor_id', 'value' => $doctor );
		}
		if ( count( $meta_query ) > 1 ) {
			$args['meta_query'] = $meta_query;
		}
		$query   = new WP_Query( $args );
		$doctors = SWC_Helpers::doctor_ids();
		?>
		<div class="wrap swc-admin">
			<h1><?php esc_html_e( 'Clinic Management', 'worldwide-clinic-appointments' ); ?></h1>
			<p><?php esc_html_e( 'Private scheduling information is limited to the patient, the assigned verified doctor, and authorized clinic administrators.', 'worldwide-clinic-appointments' ); ?></p>
			<form class="swc-admin-filter" method="get">
				<input type="hidden" name="page" value="clinic-management">
				<label class="screen-reader-text" for="swc-filter-status"><?php esc_html_e( 'Filter by status', 'worldwide-clinic-appointments' ); ?></label>
				<select id="swc-filter-status" name="status">
					<option value=""><?php esc_html_e( 'All statuses', 'worldwide-clinic-appointments' ); ?></option>
					<?php foreach ( SWC_Helpers::statuses() as $key => $label ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select>
				<label class="screen-reader-text" for="swc-filter-doctor"><?php esc_html_e( 'Filter by doctor', 'worldwide-clinic-appointments' ); ?></label>
				<select id="swc-filter-doctor" name="doctor">
					<option value="0"><?php esc_html_e( 'All doctors', 'worldwide-clinic-appointments' ); ?></option>
					<?php foreach ( $doctors as $doctor_id ) : ?>
						<option value="<?php echo absint( $doctor_id ); ?>" <?php selected( $doctor, $doctor_id ); ?>><?php echo esc_html( get_the_author_meta( 'display_name', $doctor_id ) ); ?></option>
					<?php endforeach; ?>
				</select>
				<button class="button"><?php esc_html_e( 'Filter', 'worldwide-clinic-appointments' ); ?></button>
			</form>
			<table class="widefat striped">
				<thead><tr><th><?php esc_html_e( 'Reference', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Patient', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Doctor', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Requested time', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Status', 'worldwide-clinic-appointments' ); ?></th><th><?php esc_html_e( 'Management', 'worldwide-clinic-appointments' ); ?></th></tr></thead>
				<tbody>
				<?php if ( $query->have_posts() ) : ?>
					<?php while ( $query->have_posts() ) : $query->the_post(); $id = get_the_ID(); $current = SWC_Helpers::status( $id ); $version = SWC_Helpers::record_version( $id ); ?>
					<tr>
						<td>#<?php echo absint( $id ); ?><br><small><?php echo esc_html( get_the_date( 'M j, Y', $id ) ); ?></small></td>
						<td><?php echo esc_html( get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $id ) ) ); ?><br><?php echo esc_html( SWC_Helpers::meta( $id, 'phone' ) ); ?></td>
						<td><?php echo esc_html( get_the_author_meta( 'display_name', absint( SWC_Helpers::meta( $id, 'doctor_id' ) ) ) ); ?></td>
						<td><?php echo esc_html( SWC_Helpers::display_time( SWC_Helpers::meta( $id, 'preferred_at_utc' ), 'UTC' ) ); ?></td>
						<td><?php echo esc_html( SWC_Helpers::statuses()[ $current ] ); ?><br><small><?php echo esc_html( sprintf( __( 'Version %d', 'worldwide-clinic-appointments' ), $version ) ); ?></small></td>
						<td>
							<?php $this->management_form( $id, $current, $version, $doctors ); ?>
							<?php $this->audit_timeline( $id ); ?>
						</td>
					</tr>
					<?php endwhile; wp_reset_postdata(); ?>
				<?php else : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No appointments found.', 'worldwide-clinic-appointments' ); ?></td></tr>
				<?php endif; ?>
				</tbody>
			</table>
			<?php
			echo wp_kses_post(
				paginate_links(
					array(
						'base'      => add_query_arg( 'swc_paged', '%#%' ),
						'format'    => '',
						'current'   => $paged,
						'total'     => max( 1, (int) $query->max_num_pages ),
						'prev_text' => __( 'Previous', 'worldwide-clinic-appointments' ),
						'next_text' => __( 'Next', 'worldwide-clinic-appointments' ),
					)
				)
			);
			?>
		</div>
		<?php
	}

	private function management_form( $id, $current, $version, $doctors ) {
		$current_doctor = absint( SWC_Helpers::meta( $id, 'doctor_id' ) );
		$allowed        = SWC_Helpers::allowed_transitions( 'admin', $current );
		?>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="swc_admin_update">
			<input type="hidden" name="appointment_id" value="<?php echo absint( $id ); ?>">
			<input type="hidden" name="expected_status" value="<?php echo esc_attr( $current ); ?>">
			<input type="hidden" name="expected_version" value="<?php echo absint( $version ); ?>">
			<?php wp_nonce_field( 'swc_admin_update_' . $id ); ?>
			<label><span><?php esc_html_e( 'Assigned doctor', 'worldwide-clinic-appointments' ); ?></span>
				<select name="doctor_id">
					<?php foreach ( $doctors as $doctor_id ) : ?>
						<option value="<?php echo absint( $doctor_id ); ?>" <?php selected( $current_doctor, $doctor_id ); ?>><?php echo esc_html( get_the_author_meta( 'display_name', $doctor_id ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<label><span><?php esc_html_e( 'Status', 'worldwide-clinic-appointments' ); ?></span>
				<select name="status">
					<option value="<?php echo esc_attr( $current ); ?>"><?php echo esc_html( SWC_Helpers::statuses()[ $current ] ); ?></option>
					<?php foreach ( $allowed as $key ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( SWC_Helpers::statuses()[ $key ] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<div class="swc-admin-reschedule">
				<label><span><?php esc_html_e( 'Proposed date', 'worldwide-clinic-appointments' ); ?></span><input type="date" name="new_date"></label>
				<label><span><?php esc_html_e( 'Proposed time', 'worldwide-clinic-appointments' ); ?></span><input type="time" name="new_time"></label>
				<label><span><?php esc_html_e( 'Time zone', 'worldwide-clinic-appointments' ); ?></span><select name="new_timezone"><?php foreach ( SWC_Helpers::timezones() as $timezone ) : ?><option value="<?php echo esc_attr( $timezone ); ?>" <?php selected( 'UTC', $timezone ); ?>><?php echo esc_html( $timezone ); ?></option><?php endforeach; ?></select></label>
			</div>
			<label><span><?php esc_html_e( 'Required internal reason', 'worldwide-clinic-appointments' ); ?></span><textarea name="note" maxlength="1000" required></textarea></label>
			<p class="description"><?php esc_html_e( 'Changing the doctor creates a patient-consent proposal; it does not immediately disclose the request to the proposed doctor.', 'worldwide-clinic-appointments' ); ?></p>
			<button class="button button-primary"><?php esc_html_e( 'Save', 'worldwide-clinic-appointments' ); ?></button>
		</form>
		<?php
	}

	private function audit_timeline( $id ) {
		$rows = SWC_Helpers::audit_rows( $id );
		?>
		<details class="swc-audit"><summary><?php esc_html_e( 'Audit history', 'worldwide-clinic-appointments' ); ?></summary>
			<?php if ( $rows ) : ?><ol>
				<?php foreach ( $rows as $row ) : ?>
					<li><strong><?php echo esc_html( $row->event ? $row->event : $row->action ); ?></strong> — <?php echo esc_html( $row->created_at ); ?> UTC<br><small><?php echo esc_html( trim( $row->old_status . ' → ' . $row->new_status, ' →' ) ); ?><?php echo $row->reason ? ' · ' . esc_html( $row->reason ) : ''; ?></small></li>
				<?php endforeach; ?>
			</ol><?php else : ?><p><?php esc_html_e( 'No audit entries were found.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
		</details>
		<?php
	}

	public function update() {
		$this->guard();
		$id = isset( $_POST['appointment_id'] ) ? absint( $_POST['appointment_id'] ) : 0;
		check_admin_referer( 'swc_admin_update_' . $id );
		if ( SWC_Helpers::TYPE !== get_post_type( $id ) ) {
			wp_die( esc_html__( 'Invalid appointment.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		$next             = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$doctor           = isset( $_POST['doctor_id'] ) ? absint( $_POST['doctor_id'] ) : 0;
		$expected_status  = isset( $_POST['expected_status'] ) ? sanitize_key( wp_unslash( $_POST['expected_status'] ) ) : '';
		$expected_version = isset( $_POST['expected_version'] ) ? absint( $_POST['expected_version'] ) : 0;
		$note             = isset( $_POST['note'] ) ? SWC_Helpers::limit_text( $_POST['note'], 1000, true ) : '';
		if ( '' === trim( $note ) || ! isset( SWC_Helpers::statuses()[ $next ] ) || ! SWC_Helpers::doctor_is_requestable( $doctor ) ) {
			wp_die( esc_html__( 'Provide a valid doctor, status, and internal reason.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}

		$result = SWC_Helpers::with_lock(
			$id,
			function () use ( $id, $next, $doctor, $expected_status, $expected_version, $note ) {
				$check = SWC_Helpers::assert_expected( $id, $expected_status, $expected_version );
				if ( is_wp_error( $check ) ) {
					return $check;
				}
				$current        = SWC_Helpers::status( $id );
				$current_doctor = absint( SWC_Helpers::meta( $id, 'doctor_id' ) );
				$doctor_changed = $doctor !== $current_doctor;
				$status_changed = $next !== $current;

				if ( $doctor_changed ) {
					if ( $status_changed ) {
						return new WP_Error( 'swc_combined', __( 'Propose reassignment separately from a status change.', 'worldwide-clinic-appointments' ) );
					}
					$proposal_write = SWC_Helpers::apply_meta_mutations( $id, array( '_swc_proposed_doctor_id' => $doctor, '_swc_reassignment_reason' => $note, '_swc_reassignment_expires_at' => gmdate( 'Y-m-d H:i:s', strtotime( '+7 days' ) ) ), array(), 'swc_admin_reassignment' );
					if ( is_wp_error( $proposal_write ) ) { return $proposal_write; }
					$version = SWC_Helpers::bump_version_strict( $id );
					if ( is_wp_error( $version ) ) { return $version; }
					if ( ! SWC_Helpers::audit( $id, 'reassignment-proposed', array( 'old_doctor_id' => $current_doctor, 'new_doctor_id' => $doctor, 'reason' => $note ) ) ) {
						return new WP_Error( 'swc_audit', __( 'The reassignment proposal could not be audited.', 'worldwide-clinic-appointments' ) );
					}
					return array( 'event' => 'reassignment-proposed', 'old_doctor' => $current_doctor, 'new_doctor' => $doctor );
				}

				if ( $status_changed && ! SWC_Helpers::can_transition( 'admin', $current, $next ) ) {
					return new WP_Error( 'swc_transition', __( 'That status transition is not permitted. Terminal appointments cannot be revived.', 'worldwide-clinic-appointments' ) );
				}
				$duration = absint( SWC_Helpers::meta( $id, 'appointment_duration', 30 ) );
				$details  = array();
				if ( 'confirmed' === $next && $status_changed ) {
					$utc = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
					if ( ! SWC_Helpers::slot_is_available( $current_doctor, $utc, $duration, $id ) ) {
						return new WP_Error( 'swc_conflict', __( 'The appointment time is no longer available.', 'worldwide-clinic-appointments' ) );
					}
				}
				if ( 'completed' === $next && $status_changed ) {
					$utc = (string) SWC_Helpers::meta( $id, 'preferred_at_utc' );
					if ( ! $utc || strtotime( $utc . ' UTC' ) > time() ) {
						return new WP_Error( 'swc_future', __( 'A future appointment cannot be marked completed.', 'worldwide-clinic-appointments' ) );
					}
				}
				if ( 'reschedule_pending' === $next ) {
					$date = isset( $_POST['new_date'] ) ? SWC_Helpers::limit_text( $_POST['new_date'], 10 ) : '';
					$time = isset( $_POST['new_time'] ) ? SWC_Helpers::limit_text( $_POST['new_time'], 5 ) : '';
					$zone = isset( $_POST['new_timezone'] ) ? SWC_Helpers::limit_text( $_POST['new_timezone'], 100 ) : 'UTC';
					$new  = SWC_Helpers::to_utc( $date, $time, $zone );
					if ( ! $new || strtotime( $new . ' UTC' ) <= time() || ! SWC_Helpers::slot_is_available( $current_doctor, $new, $duration, $id ) ) {
						return new WP_Error( 'swc_reschedule', __( 'Provide a future, unambiguous, available reschedule time.', 'worldwide-clinic-appointments' ) );
					}
					$expiry = min( strtotime( '+48 hours' ), strtotime( $new . ' UTC' ) - HOUR_IN_SECONDS );
					if ( $expiry <= time() ) {
						return new WP_Error( 'swc_expiry', __( 'The proposed time is too soon for patient confirmation.', 'worldwide-clinic-appointments' ) );
					}
					$proposal_write = SWC_Helpers::apply_meta_mutations( $id, array( '_swc_proposed_at_utc' => $new, '_swc_proposed_timezone' => $zone, '_swc_proposed_expires_at' => gmdate( 'Y-m-d H:i:s', $expiry ) ), array(), 'swc_admin_reschedule' );
					if ( is_wp_error( $proposal_write ) ) { return $proposal_write; }
					$details['proposed_at_utc'] = $new;
				}

				$updates = array(); $deletes = array();
				if ( $status_changed ) {
					$updates['_swc_status'] = $next;
					if ( 'reschedule_pending' !== $next ) { $deletes = array( '_swc_proposed_at_utc', '_swc_proposed_timezone', '_swc_proposed_expires_at' ); }
				}
				$state_write = SWC_Helpers::apply_meta_mutations( $id, $updates, $deletes, 'swc_admin_update' );
				if ( is_wp_error( $state_write ) ) { return $state_write; }
				$version = SWC_Helpers::bump_version_strict( $id );
				if ( is_wp_error( $version ) ) { return $version; }
				$event = $status_changed ? 'admin-status-updated' : 'admin-note-recorded';
				if ( ! SWC_Helpers::audit( $id, $event, array( 'old_status' => $current, 'new_status' => $next, 'old_doctor_id' => $current_doctor, 'new_doctor_id' => $current_doctor, 'reason' => $note, 'details' => $details ) ) ) {
					return new WP_Error( 'swc_audit', __( 'The administrator update could not be audited.', 'worldwide-clinic-appointments' ) );
				}
				return array( 'event' => $event, 'old_doctor' => $current_doctor, 'new_doctor' => $current_doctor );
			}
		);
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 409 ) );
		}

		$patient = absint( get_post_field( 'post_author', $id ) );
		$pages   = SWC_Helpers::pages();
		if ( 'reassignment-proposed' === $result['event'] ) {
			SWC_Helpers::notify_user( $patient, 'reassignment-proposed', __( 'Doctor reassignment requires your consent', 'worldwide-clinic-appointments' ), __( 'An administrator proposed a different doctor. Review and accept or decline the proposal in My Appointments.', 'worldwide-clinic-appointments' ), $id, ! empty( $pages['patient'] ) ? get_permalink( $pages['patient'] ) : home_url( '/' ), 'high' );
			SWC_Helpers::notify_user( absint( $result['old_doctor'] ), 'reassignment-proposed', __( 'Doctor reassignment proposed', 'worldwide-clinic-appointments' ), __( 'An administrator proposed reassignment. The current assignment remains unchanged until the patient consents.', 'worldwide-clinic-appointments' ), $id, ! empty( $pages['doctor'] ) ? get_permalink( $pages['doctor'] ) : home_url( '/' ), 'normal' );
		} else {
			SWC_Helpers::notify_user( $patient, 'appointment-admin-updated', __( 'Appointment updated', 'worldwide-clinic-appointments' ), __( 'An authorized clinic administrator updated your appointment.', 'worldwide-clinic-appointments' ), $id, ! empty( $pages['patient'] ) ? get_permalink( $pages['patient'] ) : home_url( '/' ), 'high' );
			SWC_Helpers::notify_user( absint( $result['new_doctor'] ), 'appointment-admin-updated', __( 'Appointment updated', 'worldwide-clinic-appointments' ), __( 'An authorized clinic administrator updated an assigned appointment.', 'worldwide-clinic-appointments' ), $id, ! empty( $pages['doctor'] ) ? get_permalink( $pages['doctor'] ) : home_url( '/' ), 'high' );
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'clinic-management', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function settings() {
		$this->guard();
		?>
		<div class="wrap swc-admin"><h1><?php esc_html_e( 'Clinic Settings', 'worldwide-clinic-appointments' ); ?></h1>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
			<input type="hidden" name="action" value="swc_save_clinic_settings"><?php wp_nonce_field( 'swc_save_clinic_settings' ); ?>
			<table class="form-table"><tr><th><label for="swc_phone"><?php esc_html_e( 'Clinic phone', 'worldwide-clinic-appointments' ); ?></label></th><td><input id="swc_phone" name="clinic_phone" maxlength="18" value="<?php echo esc_attr( get_option( 'swc_clinic_phone', '' ) ); ?>" placeholder="+12025550123"></td></tr>
			<tr><th><label for="swc_whatsapp"><?php esc_html_e( 'Clinic WhatsApp', 'worldwide-clinic-appointments' ); ?></label></th><td><input id="swc_whatsapp" name="clinic_whatsapp" maxlength="18" value="<?php echo esc_attr( get_option( 'swc_clinic_whatsapp', '' ) ); ?>" placeholder="+12025550123"></td></tr>
			<tr><th><label for="swc_emergency"><?php esc_html_e( 'Emergency notice', 'worldwide-clinic-appointments' ); ?></label></th><td><textarea id="swc_emergency" name="emergency_notice" rows="4" maxlength="1000" class="large-text"><?php echo esc_textarea( SWC_Helpers::emergency_notice() ); ?></textarea></td></tr></table>
			<button class="button button-primary"><?php esc_html_e( 'Save Clinic Settings', 'worldwide-clinic-appointments' ); ?></button>
		</form></div>
		<?php
	}

	public function save_settings() {
		$this->guard();
		check_admin_referer( 'swc_save_clinic_settings' );
		$phone     = isset( $_POST['clinic_phone'] ) ? SWC_Helpers::phone( wp_unslash( $_POST['clinic_phone'] ) ) : '';
		$whatsapp  = isset( $_POST['clinic_whatsapp'] ) ? SWC_Helpers::phone( wp_unslash( $_POST['clinic_whatsapp'] ) ) : '';
		$emergency = isset( $_POST['emergency_notice'] ) ? SWC_Helpers::limit_text( $_POST['emergency_notice'], 1000, true ) : '';
		if ( ( isset( $_POST['clinic_phone'] ) && '' !== trim( (string) $_POST['clinic_phone'] ) && ! $phone ) || ( isset( $_POST['clinic_whatsapp'] ) && '' !== trim( (string) $_POST['clinic_whatsapp'] ) && ! $whatsapp ) || '' === trim( $emergency ) ) {
			wp_die( esc_html__( 'Provide valid clinic contact numbers and a nonempty emergency notice.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}
		foreach ( array( 'swc_clinic_phone' => $phone, 'swc_clinic_whatsapp' => $whatsapp, 'swc_emergency_notice' => $emergency ) as $option => $value ) {
			$written = SWC_Helpers::update_option_strict( $option, $value, 'swc_settings_write_failed' );
			if ( is_wp_error( $written ) ) { wp_die( esc_html( $written->get_error_message() ), '', array( 'response' => 500 ) ); }
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'clinic-settings', 'updated' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function system_check() {
		$this->guard();
		$checks = SWC_Activator::system_checks();
		?>
		<div class="wrap swc-admin"><h1><?php esc_html_e( 'File 08 System Check', 'worldwide-clinic-appointments' ); ?></h1>
		<table class="widefat striped"><tbody><?php foreach ( $checks as $label => $passed ) : ?><tr><th><?php echo esc_html( $label ); ?></th><td><?php echo $passed ? '<strong class="swc-pass">PASS</strong>' : '<strong class="swc-fail">FAIL</strong>'; ?></td></tr><?php endforeach; ?></tbody></table>
		<?php if ( get_option( 'swc_last_audit_error' ) ) : ?><p class="notice notice-error inline"><?php echo esc_html( sprintf( __( 'Last audit error: %s', 'worldwide-clinic-appointments' ), get_option( 'swc_last_audit_error' ) ) ); ?></p><?php endif; ?>
		<?php if ( get_option( 'swc_last_delivery_error' ) ) : ?><p class="notice notice-warning inline"><?php esc_html_e( 'A notification fallback recently failed. Review File 19 and transactional email configuration.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'swc_complete_repair' ); ?><input type="hidden" name="action" value="swc_complete_repair"><button class="button button-primary"><?php esc_html_e( 'Run Complete Repair', 'worldwide-clinic-appointments' ); ?></button></form>
		<hr><h2><?php esc_html_e( 'Irreversible Data Purge', 'worldwide-clinic-appointments' ); ?></h2><p><?php esc_html_e( 'Create and verify a full backup first. Purge deletes every File 08 appointment, audit entry, rate-limit record, option, and user availability field.', 'worldwide-clinic-appointments' ); ?></p>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'swc_purge_data' ); ?><input type="hidden" name="action" value="swc_purge_data"><label><?php esc_html_e( 'Type PURGE FILE 08 to confirm', 'worldwide-clinic-appointments' ); ?><input name="confirmation" autocomplete="off" required></label> <button class="button button-secondary"><?php esc_html_e( 'Purge File 08 Data', 'worldwide-clinic-appointments' ); ?></button></form></div>
		<?php
	}

	public function repair() {
		$this->guard();
		check_admin_referer( 'swc_complete_repair' );
		SWC_Activator::add_capabilities();
		SWC_Activator::install_schema();
		SWC_Activator::repair_pages();
		SWC_Activator::migrate_existing_records();
		update_option( 'swc_db_version', SWC_Activator::DB_VERSION, false );
		global $wpdb;
		$cleaned = $wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}swc_rate_limits WHERE expires_at < %s", current_time( 'mysql', true ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $cleaned ) { wp_die( esc_html__( 'Repair could not complete the expired rate-limit cleanup safely.', 'worldwide-clinic-appointments' ), '', array( 'response' => 500 ) ); }
		wp_safe_redirect( add_query_arg( array( 'page' => 'clinic-system-check', 'repaired' => '1' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public function purge() {
		$this->guard();
		check_admin_referer( 'swc_purge_data' );
		$confirmation = isset( $_POST['confirmation'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmation'] ) ) : '';
		if ( 'PURGE FILE 08' !== $confirmation ) {
			wp_die( esc_html__( 'The confirmation phrase did not match. No data was deleted.', 'worldwide-clinic-appointments' ), '', array( 'response' => 400 ) );
		}
		$purged = SWC_Activator::purge_all_data();
		if ( is_wp_error( $purged ) ) { wp_die( esc_html( $purged->get_error_message() ), '', array( 'response' => 500 ) ); }
		wp_safe_redirect( add_query_arg( array( 'page' => 'plugins.php', 'swc_purged' => '1' ), admin_url() ) );
		exit;
	}

	private function guard() {
		if ( ! current_user_can( 'manage_worldwide_clinic' ) ) {
			wp_die( esc_html__( 'You are not allowed to manage the clinic.', 'worldwide-clinic-appointments' ), '', array( 'response' => 403 ) );
		}
	}

	public function notice() {
		if ( current_user_can( 'activate_plugins' ) && get_transient( 'swc_activation_notice' ) ) {
			delete_transient( 'swc_activation_notice' );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Worldwide Clinic is active. Review System Check, Clinic Settings, and doctor availability before staging acceptance.', 'worldwide-clinic-appointments' ) . '</p></div>';
		}
	}
}
