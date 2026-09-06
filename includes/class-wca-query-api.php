<?php
/**
 * Canonical cursor query contracts for File 08 appointment lists and clinic schedules.
 *
 * @package Worldwide_Clinic_Appointments
 */
defined( 'ABSPATH' ) || exit;

final class WCA_Query_API {
	const CONTRACT_VERSION = '1.0.0';
	const MAX_PAGE_SIZE    = 50;

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ), 45 );
		add_filter( 'template_include', array( __CLASS__, 'dashboard_template' ), 100 );
		// Replace the earlier canonical dashboard shortcode with the complete schedule-aware renderer.
		add_shortcode( 'wca_clinic_dashboard', array( __CLASS__, 'render_dashboard' ) );
	}

	public static function register_routes() {
		register_rest_route( 'wca/v1', '/appointment-refs', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'rest_appointments' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
		register_rest_route( 'wca/v1', '/clinic-refs/(?P<ref>[0-9a-fA-F-]{36})/schedule', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'rest_clinic_schedule' ),
			'permission_callback' => array( 'WCA_REST', 'authenticated' ),
		) );
	}

	public static function dashboard_template( $template ) {
		if ( class_exists( 'WCA_Routes' ) && 'dashboard' === WCA_Routes::route() ) {
			$file = WCA_DIR . 'templates/dashboard.php';
			if ( is_file( $file ) ) { return $file; }
		}
		return $template;
	}

	private static function rate_limit( $scope, $actor_user_id, $limit = 120, $window = 60 ) {
		$hit = SWC_Helpers::rate_limit_hit( 'rest_' . sanitize_key( $scope ), absint( $actor_user_id ), absint( $limit ), absint( $window ) );
		return $hit ? new WP_Error( 'wca_rate_limit', __( 'Too many requests. Please try again later.', 'worldwide-clinic-appointments' ), array( 'status' => 429, 'retry_after' => absint( $window ) ) ) : true;
	}

	private static function response( $payload ) {
		if ( is_wp_error( $payload ) ) { return $payload; }
		$response = rest_ensure_response( $payload );
		$response->header( 'X-WCA-Query-Contract', 'wca.appointment-queries/' . self::CONTRACT_VERSION );
		$response->header( 'X-Request-ID', WCA_Observability::trace_id() );
		$response->header( 'Cache-Control', 'private, no-store, max-age=0' );
		$response->header( 'Pragma', 'no-cache' );
		$response->header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' );
		return $response;
	}

	public static function rest_appointments( WP_REST_Request $request ) {
		$actor = get_current_user_id();
		$rate = self::rate_limit( 'appointment_list', $actor );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$result = self::list_patient_appointments( $actor, array(
			'cursor'   => sanitize_text_field( (string) $request->get_param( 'cursor' ) ),
			'per_page' => $request->get_param( 'per_page' ),
		) );
		return self::response( $result );
	}

	public static function rest_clinic_schedule( WP_REST_Request $request ) {
		$actor = get_current_user_id();
		$rate = self::rate_limit( 'clinic_schedule', $actor );
		if ( is_wp_error( $rate ) ) { return $rate; }
		$result = self::list_clinic_schedule( sanitize_text_field( $request['ref'] ), $actor, array(
			'cursor'   => sanitize_text_field( (string) $request->get_param( 'cursor' ) ),
			'per_page' => $request->get_param( 'per_page' ),
		) );
		return self::response( $result );
	}

	/**
	 * Query contract: own participant appointments plus current appointment-scope delegations.
	 * Output is an opaque, minimum-detail projection with a signed keyset cursor.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function list_patient_appointments( $actor_user_id = 0, $args = array() ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		$per_page = self::page_size( $args['per_page'] ?? 20 );
		$filter_hash = hash( 'sha256', wp_json_encode( array( 'actor' => $actor_user_id, 'scope' => 'own_appointments', 'per_page' => $per_page ) ) );
		$cursor = self::decode_cursor( (string) ( $args['cursor'] ?? '' ), 'appointment_list', $actor_user_id, $filter_hash );
		if ( is_wp_error( $cursor ) ) { return $cursor; }
		$delegated = WCA_Authorization::delegated_clinic_ids( $actor_user_id, 'appointments' );
		$rows = self::query_candidate_appointments( $actor_user_id, 0, $delegated, $cursor, $per_page + 1 );
		if ( is_wp_error( $rows ) ) { return $rows; }

		$items = array();
		$consumed = array();
		foreach ( $rows as $row ) {
			$id = absint( $row['ID'] ?? 0 );
			if ( ! $id ) { continue; }
			$access = WCA_Authorization::can_view_appointment( $id, $actor_user_id );
			if ( is_wp_error( $access ) ) {
				$status = self::error_status( $access );
				if ( $status >= 500 ) { return $access; }
				$consumed = $row;
				continue;
			}
			$projection = self::appointment_projection( $id, false );
			if ( is_wp_error( $projection ) ) { return $projection; }
			$items[] = $projection;
			$consumed = $row;
			if ( count( $items ) >= $per_page ) { break; }
		}

		$has_more = count( $rows ) > $per_page;
		$next = '';
		if ( $has_more && $consumed ) {
			$next = self::encode_cursor( 'appointment_list', $actor_user_id, $filter_hash, array(
				't' => (string) ( $consumed['sort_time'] ?? '' ),
				'i' => absint( $consumed['ID'] ?? 0 ),
			) );
		}
		return array(
			'contract'    => 'wca.list-patient-appointments',
			'version'     => self::CONTRACT_VERSION,
			'items'       => $items,
			'next_cursor' => $next,
			'per_page'    => $per_page,
			'trace_id'    => WCA_Observability::trace_id(),
		);
	}

	/**
	 * Query contract: delegated/owned clinic schedule with privacy-filtered reason fields.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function list_clinic_schedule( $clinic_ref, $actor_user_id = 0, $args = array() ) {
		$actor_user_id = absint( $actor_user_id ?: get_current_user_id() );
		$claims = WCA_Authorization::claims( $actor_user_id );
		if ( is_wp_error( $claims ) ) { return $claims; }
		WCA_Repository::clear_read_error();
		$clinic = WCA_Repository::get_clinic( sanitize_text_field( $clinic_ref ), false );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		if ( ! $clinic ) { return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) ); }
		$manage = WCA_Authorization::can_manage_clinic( $clinic, $actor_user_id );
		$appointment_scope = in_array( absint( $clinic['id'] ), WCA_Authorization::delegated_clinic_ids( $actor_user_id, 'appointments' ), true );
		if ( is_wp_error( $manage ) && ! $appointment_scope ) {
			return new WP_Error( 'wca_clinic_schedule_forbidden', __( 'You cannot view this clinic schedule.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}

		$per_page = self::page_size( $args['per_page'] ?? 20 );
		$filter_hash = hash( 'sha256', wp_json_encode( array( 'actor' => $actor_user_id, 'clinic_ref' => strtolower( (string) $clinic['public_ref'] ), 'per_page' => $per_page ) ) );
		$cursor = self::decode_cursor( (string) ( $args['cursor'] ?? '' ), 'clinic_schedule', $actor_user_id, $filter_hash );
		if ( is_wp_error( $cursor ) ) { return $cursor; }
		$rows = self::query_candidate_appointments( $actor_user_id, absint( $clinic['id'] ), array(), $cursor, $per_page + 1 );
		if ( is_wp_error( $rows ) ) { return $rows; }
		$has_more = count( $rows ) > $per_page;
		$page_rows = array_slice( $rows, 0, $per_page );
		$items = array();
		foreach ( $page_rows as $row ) {
			$projection = self::appointment_projection( absint( $row['ID'] ?? 0 ), true );
			if ( is_wp_error( $projection ) ) { return $projection; }
			$items[] = $projection;
		}
		$next = '';
		if ( $has_more && $page_rows ) {
			$last = end( $page_rows );
			$next = self::encode_cursor( 'clinic_schedule', $actor_user_id, $filter_hash, array( 't' => (string) $last['sort_time'], 'i' => absint( $last['ID'] ) ) );
		}
		return array(
			'contract'    => 'wca.list-clinic-schedule',
			'version'     => self::CONTRACT_VERSION,
			'clinic_ref'  => strtolower( (string) $clinic['public_ref'] ),
			'items'       => $items,
			'next_cursor' => $next,
			'per_page'    => $per_page,
			'trace_id'    => WCA_Observability::trace_id(),
		);
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function query_candidate_appointments( $actor_user_id, $clinic_id, $delegated_clinic_ids, $cursor, $limit ) {
		global $wpdb;
		$actor_user_id = absint( $actor_user_id );
		$clinic_id = absint( $clinic_id );
		$limit = min( self::MAX_PAGE_SIZE + 1, max( 2, absint( $limit ) ) );
		$where = array(
			'p.post_type=%s',
			"p.post_status IN ('private','publish')",
		);
		$params = array( SWC_Helpers::TYPE );
		if ( $clinic_id ) {
			$where[] = "EXISTS (SELECT 1 FROM {$wpdb->postmeta} pc WHERE pc.post_id=p.ID AND pc.meta_key='_swc_clinic_id' AND CAST(pc.meta_value AS UNSIGNED)=%d)";
			$params[] = $clinic_id;
		} else {
			$relations = array(
				"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pa WHERE pa.post_id=p.ID AND pa.meta_key='_swc_patient_user_id' AND CAST(pa.meta_value AS UNSIGNED)=%d)",
				"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pd WHERE pd.post_id=p.ID AND pd.meta_key='_swc_doctor_id' AND CAST(pd.meta_value AS UNSIGNED)=%d)",
				"EXISTS (SELECT 1 FROM {$wpdb->postmeta} pg WHERE pg.post_id=p.ID AND pg.meta_key='_swc_guardian_user_id' AND CAST(pg.meta_value AS UNSIGNED)=%d)",
			);
			$params[] = $actor_user_id; $params[] = $actor_user_id; $params[] = $actor_user_id;
			$delegated_clinic_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $delegated_clinic_ids ) ) ) );
			if ( $delegated_clinic_ids ) {
				$placeholders = implode( ',', array_fill( 0, count( $delegated_clinic_ids ), '%d' ) );
				$relations[] = "EXISTS (SELECT 1 FROM {$wpdb->postmeta} pc WHERE pc.post_id=p.ID AND pc.meta_key='_swc_clinic_id' AND CAST(pc.meta_value AS UNSIGNED) IN ({$placeholders}))";
				foreach ( $delegated_clinic_ids as $delegated_id ) { $params[] = $delegated_id; }
			}
			$where[] = '(' . implode( ' OR ', $relations ) . ')';
		}

		$sort_expression = "COALESCE(NULLIF(MAX(pt.meta_value),''),p.post_date_gmt)";
		$having = '';
		if ( is_array( $cursor ) && ! empty( $cursor['t'] ) && ! empty( $cursor['i'] ) ) {
			$having = " HAVING ({$sort_expression}<%s OR ({$sort_expression}=%s AND p.ID<%d))";
			$params[] = (string) $cursor['t']; $params[] = (string) $cursor['t']; $params[] = absint( $cursor['i'] );
		}
		$sql = "SELECT p.ID, {$sort_expression} AS sort_time FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pt ON pt.post_id=p.ID AND pt.meta_key='_swc_preferred_at_utc' WHERE " . implode( ' AND ', $where ) . " GROUP BY p.ID,p.post_date_gmt{$having} ORDER BY sort_time DESC,p.ID DESC LIMIT %d";
		$params[] = $limit;
		$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->last_error = '';
		$rows = $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $rows || '' !== (string) $wpdb->last_error ) {
			return new WP_Error( 'wca_appointment_list_read_failed', __( 'Appointment list data could not be read safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) );
		}
		return array_values( (array) $rows );
	}

	/** @return array<string,mixed>|WP_Error */
	private static function appointment_projection( $appointment_id, $clinic_schedule = false ) {
		$appointment_id = absint( $appointment_id );
		if ( ! $appointment_id ) { return new WP_Error( 'wca_appointment_projection_invalid', __( 'Appointment projection could not be created safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) ); }
		$ref = strtolower( (string) SWC_Helpers::meta( $appointment_id, 'public_ref', '' ) );
		if ( ! preg_match( '/^[0-9a-f-]{36}$/i', $ref ) ) { return new WP_Error( 'wca_appointment_public_ref_missing', __( 'Appointment projection is missing its public reference.', 'worldwide-clinic-appointments' ), array( 'status' => 503 ) ); }
		$status = SWC_Helpers::status( $appointment_id );
		$projection = array(
			'public_ref'        => $ref,
			'status'            => $status,
			'record_version'    => SWC_Helpers::record_version( $appointment_id ),
			'scheduled_at_utc'  => (string) SWC_Helpers::meta( $appointment_id, 'preferred_at_utc' ),
			'end_at_utc'        => (string) SWC_Helpers::meta( $appointment_id, 'appointment_end_utc' ),
			'timezone'          => (string) SWC_Helpers::meta( $appointment_id, 'patient_timezone' ),
			'consultation_type' => sanitize_key( (string) SWC_Helpers::meta( $appointment_id, 'consultation_type' ) ),
			'clinical_authority'=> false,
		);
		if ( $clinic_schedule ) {
			// Deliberately expose category only; free-text patient reason stays out of staff schedule lists.
			$projection['reason_category'] = sanitize_key( (string) SWC_Helpers::meta( $appointment_id, 'reason_category', 'general' ) );
		} else {
			$actor = WCA_Authorization::appointment_actor( $appointment_id, get_current_user_id() );
			$projection['allowed_actions'] = WCA_Contracts::allowed_transitions( $actor, $status );
		}
		return $projection;
	}

	private static function page_size( $value ) {
		$raw = filter_var( $value, FILTER_VALIDATE_INT );
		return false === $raw ? 20 : min( self::MAX_PAGE_SIZE, max( 1, (int) $raw ) );
	}

	private static function error_status( $error ) {
		if ( ! is_wp_error( $error ) ) { return 0; }
		$data = $error->get_error_data();
		return is_array( $data ) ? absint( $data['status'] ?? 0 ) : 0;
	}

	private static function encode_cursor( $scope, $actor_user_id, $filter_hash, $state ) {
		$payload = array(
			'v' => 1,
			's' => sanitize_key( $scope ),
			'a' => absint( $actor_user_id ),
			'f' => (string) $filter_hash,
			't' => sanitize_text_field( $state['t'] ?? '' ),
			'i' => absint( $state['i'] ?? 0 ),
		);
		$json = wp_json_encode( $payload );
		if ( ! is_string( $json ) || ! $payload['t'] || ! $payload['i'] ) { return ''; }
		$encoded = bin2hex( $json );
		return $encoded . '.' . hash_hmac( 'sha256', $encoded, wp_salt( 'nonce' ) );
	}

	/** @return array<string,mixed>|WP_Error|null */
	private static function decode_cursor( $cursor, $scope, $actor_user_id, $filter_hash ) {
		$cursor = trim( (string) $cursor );
		if ( '' === $cursor ) { return null; }
		if ( ! preg_match( '/^([0-9a-f]+)\.([0-9a-f]{64})$/i', $cursor, $matches ) ) { return new WP_Error( 'wca_cursor_invalid', __( 'The pagination cursor is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$expected = hash_hmac( 'sha256', strtolower( $matches[1] ), wp_salt( 'nonce' ) );
		if ( ! hash_equals( $expected, strtolower( $matches[2] ) ) ) { return new WP_Error( 'wca_cursor_invalid', __( 'The pagination cursor signature is invalid.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }
		$json = hex2bin( $matches[1] );
		$state = is_string( $json ) ? json_decode( $json, true ) : null;
		if ( ! is_array( $state ) || 1 !== absint( $state['v'] ?? 0 ) || sanitize_key( $scope ) !== (string) ( $state['s'] ?? '' ) || absint( $actor_user_id ) !== absint( $state['a'] ?? 0 ) || ! hash_equals( (string) $filter_hash, (string) ( $state['f'] ?? '' ) ) || empty( $state['t'] ) || empty( $state['i'] ) ) {
			return new WP_Error( 'wca_cursor_invalid', __( 'The pagination cursor does not match this query.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		return array( 't' => sanitize_text_field( $state['t'] ), 'i' => absint( $state['i'] ) );
	}

	/** Render canonical /clinic/dashboard with appointment schedule/requests, not only clinic cards. */
	public static function render_dashboard() {
		$claims = WCA_Authorization::claims();
		if ( is_wp_error( $claims ) || ! in_array( $claims['role'] ?? '', array( 'doctor','founder','administrator','clinic_staff' ), true ) ) {
			return self::notice( __( 'Verified clinic access is required.', 'worldwide-clinic-appointments' ), 'error' );
		}
		$user_id = get_current_user_id();
		$clinics = self::manageable_clinics( $user_id );
		if ( is_wp_error( $clinics ) ) { return self::notice( __( 'Clinic dashboard data is temporarily unavailable. Please try again.', 'worldwide-clinic-appointments' ), 'error' ); }
		$selected_ref = strtolower( sanitize_text_field( wp_unslash( $_GET['clinic_ref'] ?? '' ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only selection.
		if ( ! $selected_ref && $clinics ) { $selected_ref = strtolower( (string) $clinics[0]['public_ref'] ); }
		$cursor = sanitize_text_field( wp_unslash( $_GET['schedule_cursor'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- signed read-only cursor.
		$schedule = $selected_ref ? self::list_clinic_schedule( $selected_ref, $user_id, array( 'cursor' => $cursor, 'per_page' => 20 ) ) : array( 'items' => array(), 'next_cursor' => '' );
		if ( is_wp_error( $schedule ) ) { return self::notice( __( 'Clinic schedule is temporarily unavailable or you no longer have access.', 'worldwide-clinic-appointments' ), 'error' ); }
		ob_start(); ?>
		<main class="wca-shell" aria-labelledby="wca-dashboard-title">
			<h1 id="wca-dashboard-title"><?php esc_html_e( 'Clinic dashboard', 'worldwide-clinic-appointments' ); ?></h1>
			<p><?php esc_html_e( 'Manage clinic scheduling, appointment requests and completion actions within your current owner or delegated scope. Platform commission is always 0%.', 'worldwide-clinic-appointments' ); ?></p>
			<?php if ( ! $clinics ) : ?><p><?php esc_html_e( 'No manageable clinics were found.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
			<div class="wca-grid">
			<?php foreach ( $clinics as $clinic ) :
				$link = add_query_arg( 'clinic_ref', strtolower( (string) $clinic['public_ref'] ), home_url( '/clinic/dashboard/' ) ); ?>
				<article class="wca-card"><h2><?php echo esc_html( $clinic['name'] ); ?></h2><p><?php echo esc_html( ucfirst( $clinic['status'] ) ); ?> · v<?php echo esc_html( $clinic['version'] ); ?></p><a class="wca-button" href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'Open schedule', 'worldwide-clinic-appointments' ); ?></a></article>
			<?php endforeach; ?>
			</div>
			<?php if ( $selected_ref ) : ?>
			<section aria-labelledby="wca-schedule-title"><h2 id="wca-schedule-title"><?php esc_html_e( 'Appointment schedule and requests', 'worldwide-clinic-appointments' ); ?></h2>
				<?php if ( empty( $schedule['items'] ) ) : ?><p><?php esc_html_e( 'No appointments were found for this clinic.', 'worldwide-clinic-appointments' ); ?></p><?php endif; ?>
				<div class="wca-list">
				<?php foreach ( (array) $schedule['items'] as $item ) : ?>
					<article class="wca-card"><h3><?php echo esc_html( ucfirst( str_replace( '_', ' ', (string) $item['status'] ) ) ); ?></h3><p><time datetime="<?php echo esc_attr( (string) $item['scheduled_at_utc'] ); ?>"><?php echo esc_html( (string) $item['scheduled_at_utc'] ); ?></time></p><p><?php echo esc_html( sprintf( __( 'Reason category: %s', 'worldwide-clinic-appointments' ), (string) $item['reason_category'] ) ); ?></p><a class="wca-button wca-button-secondary" href="<?php echo esc_url( home_url( '/appointment/' . rawurlencode( (string) $item['public_ref'] ) . '/' ) ); ?>"><?php esc_html_e( 'View appointment', 'worldwide-clinic-appointments' ); ?></a></article>
				<?php endforeach; ?>
				</div>
				<?php if ( ! empty( $schedule['next_cursor'] ) ) : $next = add_query_arg( array( 'clinic_ref' => $selected_ref, 'schedule_cursor' => $schedule['next_cursor'] ), home_url( '/clinic/dashboard/' ) ); ?><p><a class="wca-button wca-button-secondary" href="<?php echo esc_url( $next ); ?>"><?php esc_html_e( 'Next appointments', 'worldwide-clinic-appointments' ); ?></a></p><?php endif; ?>
			</section>
			<?php endif; ?>
		</main>
		<?php return ob_get_clean();
	}

	/** @return array<int,array<string,mixed>>|WP_Error */
	private static function manageable_clinics( $user_id ) {
		$user_id = absint( $user_id );
		WCA_Repository::clear_read_error();
		$owned = WCA_Repository::list_clinics( array( 'owner_user_id' => $user_id, 'status' => '', 'per_page' => 100 ) );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) ) { return $read_error; }
		$out = array(); $seen = array();
		foreach ( (array) $owned as $clinic ) { $id = absint( $clinic['id'] ?? 0 ); if ( $id ) { $seen[ $id ] = true; $out[] = $clinic; } }
		$delegated = array_unique( array_merge( WCA_Authorization::delegated_clinic_ids( $user_id, 'clinic_manage' ), WCA_Authorization::delegated_clinic_ids( $user_id, 'appointments' ) ) );
		foreach ( $delegated as $clinic_id ) {
			$clinic_id = absint( $clinic_id ); if ( ! $clinic_id || isset( $seen[ $clinic_id ] ) ) { continue; }
			WCA_Repository::clear_read_error();
			$clinic = WCA_Repository::get_clinic( $clinic_id, false );
			$read_error = WCA_Repository::consume_read_error();
			if ( is_wp_error( $read_error ) ) { return $read_error; }
			if ( $clinic ) { $out[] = $clinic; $seen[ $clinic_id ] = true; }
		}
		return $out;
	}

	private static function notice( $message, $type = 'info' ) {
		return '<div class="wca-shell"><div class="wca-alert wca-alert-' . esc_attr( $type ) . '" role="alert">' . esc_html( $message ) . '</div></div>';
	}
}
