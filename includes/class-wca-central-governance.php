<?php
/**
 * Central-plan governance adapter for File 08.
 *
 * Implements the 7 August 2026 governing addendum without taking ownership
 * from Files 00/01-B/17/19/20/24/25/26. File 08 remains the only owner of
 * clinic, appointment, relationship and clinical-boundary facts.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Central_Governance {
	const CONTRACT_VERSION = '1.0.0';
	const GOVERNING_ADDENDUM = 'SSH-F08-CEN-2026-08-07';
	const SABRI_GREEN = '#087A4E';
	const FILE26_PROJECTION_VERSION = '1.0.0';
	const AGE_CLAIM_VERSION = '1.0.0';

	public static function boot() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
		add_action( 'wca_outbox_event', array( __CLASS__, 'observe_outbox_event' ), 20, 1 );
		add_shortcode( 'wca_governing_status', array( __CLASS__, 'status_shortcode' ) );
	}

	/** @return array<string,mixed> */
	public static function manifest() {
		return array(
			'contract'              => 'wca.central-governance',
			'version'               => self::CONTRACT_VERSION,
			'governing_addendum'     => self::GOVERNING_ADDENDUM,
			'file_plan'              => 'SSH-F08-PLAN-2026-v1.0',
			'canonical_owner'        => 'File 08 — Worldwide Clinic and Appointments',
			'brand_primary'          => self::SABRI_GREEN,
			'platform_commission_bps'=> 0,
			'paid_or_donor_boost'    => false,
			'navigation_owner'       => 'File 20',
			'notification_owner'     => 'File 19',
			'communication_owner'    => 'File 17',
			'assurance_owner'        => 'File 24',
			'visual_token_owner'     => 'File 25',
			'search_projection_owner'=> 'File 26',
			'status_model'           => array( 'specified', 'coded', 'packaged', 'automated_qa_green', 'staging_accepted', 'live_deployed', 'operational' ),
			'laws'                   => self::laws(),
			'generated_at'           => gmdate( 'c' ),
		);
	}

	/** @return array<string,string> */
	public static function laws() {
		return array(
			'CEN-GOV-001'    => 'Founder/current governing hierarchy is explicit; runtime evidence reports implementation state only.',
			'CEN-OWN-001'    => 'Every File 08 business fact has one native owner; consumers receive versioned projections/events only.',
			'CEN-BIZ-001'    => 'No File 08 feature is gated by Free/Pro/Premium or a paid AI entitlement.',
			'CEN-DON-001'    => 'Donation state never changes clinic availability, booking, rank, badge, support, fee or reach.',
			'CEN-BRAND-001'  => 'Sabri Green #087A4E is the fallback primary token; File 25 remains the visual-token owner.',
			'CEN-NAV-001'    => 'File 20 remains the sole global navigation/shell owner.',
			'CEN-AGE-001'    => 'Age/guardian claim is rechecked at protected patient actions; minors require a current verified guardian relationship.',
			'CEN-PRI-001'    => 'Clinical and scheduling data are purpose-limited, minimized, private, export/erasure aware and non-surveillance.',
			'CEN-MED-001'    => 'Red flags divert to qualified local urgent/emergency care; File 08 does not claim emergency replacement or automated diagnosis/prescription.',
			'CEN-RANK-001'   => 'File 08 exposes factual discovery signals only; no cure/outcome, donor, paid, outrage or secret ranking boost.',
			'CEN-REL-001'    => 'Live release requires migration/restore/rollback evidence, two fresh corrective reviews, zero known release blockers and Founder approval.',
			'CEN-STATUS-001' => 'Specified, coded, packaged, QA, staging, live and operational are separate evidence states.',
			'CEN-ACC-001'    => 'Keyboard, focus, screen reader, 200–400% zoom/reflow, RTL/LTR, reduced motion, low bandwidth and stale-state disclosure are release gates.',
			'CEN-SEARCH-001' => 'File 26 receives public-safe, freshness-aware projections and remains the only search/index/ranking owner.',
			'F08-CEN-01'     => 'Native File 08 tests enforce canonical ownership, free/green/privacy/safety and File 26 projection boundaries.',
			'F08-CEN-02'     => 'Writes stay inside File 08 owner commands; reads/events/cache/index/notifications use versioned contracts with authorization or public-safety rechecks.',
		);
	}

	/**
	 * Obtain an age/guardian claim without silently inventing adulthood.
	 * File 00 remains the source of truth. Read-only legacy metadata is accepted
	 * only as a compatibility projection when a versioned helper is unavailable.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function age_guardian_claim( $patient_user_id ) {
		$patient_user_id = absint( $patient_user_id );
		if ( ! $patient_user_id || ! get_userdata( $patient_user_id ) ) {
			return new WP_Error( 'wca_age_claim_user', __( 'A valid patient identity is required.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}

		$raw = null;
		$source = '';
		if ( function_exists( 'smc_get_age_guardian_claim' ) ) {
			$raw = smc_get_age_guardian_claim( $patient_user_id );
			$source = 'file00:smc_get_age_guardian_claim';
		} elseif ( function_exists( 'smc_get_membership_claims' ) ) {
			$all = smc_get_membership_claims( $patient_user_id );
			if ( is_array( $all ) ) {
				$raw = array(
					'birth_date' => isset( $all['birth_date'] ) ? $all['birth_date'] : ( isset( $all['date_of_birth'] ) ? $all['date_of_birth'] : '' ),
					'gender'     => isset( $all['gender'] ) ? $all['gender'] : '',
					'is_minor'   => isset( $all['is_minor'] ) ? $all['is_minor'] : null,
					'version'    => isset( $all['version'] ) ? $all['version'] : '',
				);
				$source = 'file00:smc_get_membership_claims';
			}
		}

		$raw = apply_filters( 'wca_age_guardian_claim', $raw, $patient_user_id );
		if ( is_array( $raw ) && ! $source ) {
			$source = 'versioned-filter';
		}

		if ( ! is_array( $raw ) ) {
			$birth = '';
			$gender = '';
			foreach ( array( '_smc_date_of_birth', 'smc_date_of_birth', '_smc_birth_date', 'date_of_birth' ) as $key ) {
				$value = (string) get_user_meta( $patient_user_id, $key, true );
				if ( $value ) { $birth = $value; break; }
			}
			foreach ( array( '_smc_gender', 'smc_gender', 'gender' ) as $key ) {
				$value = (string) get_user_meta( $patient_user_id, $key, true );
				if ( $value ) { $gender = $value; break; }
			}
			if ( $birth || $gender || function_exists( 'smc_user_is_minor' ) ) {
				$raw = array(
					'birth_date' => $birth,
					'gender'     => $gender,
					'is_minor'   => function_exists( 'smc_user_is_minor' ) ? (bool) smc_user_is_minor( $patient_user_id ) : null,
				);
				$source = 'file00:legacy-read-projection';
			}
		}

		if ( ! is_array( $raw ) ) {
			return new WP_Error( 'wca_age_claim_unavailable', __( 'Current age and guardian eligibility could not be verified. Refresh your account verification before booking.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}

		$gender = self::normalize_gender( isset( $raw['gender'] ) ? $raw['gender'] : '' );
		$age = self::age_from_birth_date( isset( $raw['birth_date'] ) ? $raw['birth_date'] : '' );
		$minor = array_key_exists( 'is_minor', $raw ) && null !== $raw['is_minor'] ? (bool) $raw['is_minor'] : null;
		$threshold = 'female' === $gender ? 12 : 15;
		$threshold = max( $threshold, absint( apply_filters( 'wca_guardian_age_threshold', $threshold, $gender, $patient_user_id ) ) );
		if ( null === $minor && null !== $age ) {
			$minor = $age < $threshold;
		}
		if ( null === $minor ) {
			return new WP_Error( 'wca_age_claim_incomplete', __( 'Age eligibility is incomplete and cannot be assumed.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}

		return array(
			'contract'       => 'wca.age-guardian-claim',
			'version'        => self::AGE_CLAIM_VERSION,
			'patient_user_id'=> $patient_user_id,
			'gender'         => $gender,
			'age'            => $age,
			'threshold'      => $threshold,
			'guardian_required' => (bool) $minor,
			'source'         => $source,
			'checked_at_utc' => gmdate( 'c' ),
		);
	}

	/** @return true|WP_Error */
	public static function validate_patient_guardian( $patient_user_id, $guardian_user_id, $actor_user_id ) {
		$patient_user_id  = absint( $patient_user_id );
		$guardian_user_id = absint( $guardian_user_id );
		$actor_user_id    = absint( $actor_user_id );
		$claim = self::age_guardian_claim( $patient_user_id );
		if ( is_wp_error( $claim ) ) { return $claim; }

		if ( ! empty( $claim['guardian_required'] ) ) {
			if ( ! $guardian_user_id || $guardian_user_id !== $actor_user_id || ! WCA_Authorization::is_guardian( $guardian_user_id ) ) {
				return new WP_Error( 'wca_guardian_required', __( 'A current verified guardian must act for this patient.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
			}
			$allowed = function_exists( 'smc_guardian_may_act_for' ) ? (bool) smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ) : (bool) apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );
			if ( ! $allowed ) {
				return new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not currently authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
			}
			return true;
		}

		if ( $patient_user_id === $actor_user_id && ! $guardian_user_id ) {
			return true;
		}
		if ( $guardian_user_id && $guardian_user_id === $actor_user_id && WCA_Authorization::is_guardian( $guardian_user_id ) ) {
			$allowed = function_exists( 'smc_guardian_may_act_for' ) ? (bool) smc_guardian_may_act_for( $guardian_user_id, $patient_user_id ) : (bool) apply_filters( 'wca_guardian_may_act_for_patient', false, $guardian_user_id, $patient_user_id );
			return $allowed ? true : new WP_Error( 'wca_guardian_relationship', __( 'The guardian relationship is not currently authorized.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
		}
		return new WP_Error( 'wca_patient_actor_mismatch', __( 'The current actor may not act for this patient.', 'worldwide-clinic-appointments' ), array( 'status' => 403 ) );
	}

	private static function normalize_gender( $value ) {
		$value = strtolower( trim( (string) $value ) );
		if ( in_array( $value, array( 'female', 'f', 'woman', 'girl', 'خاتون', 'عورت', 'لڑکی' ), true ) ) { return 'female'; }
		if ( in_array( $value, array( 'male', 'm', 'man', 'boy', 'مرد', 'لڑکا' ), true ) ) { return 'male'; }
		return 'unknown';
	}

	private static function age_from_birth_date( $value ) {
		$value = trim( (string) $value );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) { return null; }
		try {
			$birth = new DateTimeImmutable( $value, new DateTimeZone( 'UTC' ) );
			$today = new DateTimeImmutable( 'today', new DateTimeZone( 'UTC' ) );
			if ( $birth > $today ) { return null; }
			return (int) $birth->diff( $today )->y;
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * Public-safe projection that File 26 may index. This does not create an
	 * index or ranking database in File 08.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function file26_clinic_projection( $clinic_ref ) {
		$clinic_ref = sanitize_text_field( $clinic_ref );
		$clinic = WCA_Repository::get_clinic( $clinic_ref, true );
		if ( ! $clinic ) {
			return new WP_Error( 'wca_projection_missing', __( 'Clinic is not publicly eligible.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		$projection = WCA_Service::public_clinic_projection( $clinic_ref );
		if ( ! is_array( $projection ) || empty( $projection['verified_owner'] ) ) {
			return new WP_Error( 'wca_projection_ineligible', __( 'Clinic verification is not current.', 'worldwide-clinic-appointments' ), array( 'status' => 404 ) );
		}
		$slug = sanitize_title( isset( $projection['slug'] ) ? $projection['slug'] : $clinic['slug'] );
		$url = apply_filters( 'wca_canonical_clinic_url', home_url( '/clinic/' . rawurlencode( $slug ) . '/' ), $clinic_ref, $projection );
		return array(
			'contract'       => 'wca.file26-clinic-projection',
			'version'        => self::FILE26_PROJECTION_VERSION,
			'object_type'    => 'clinic',
			'public_ref'     => (string) $clinic['public_ref'],
			'canonical_url'  => esc_url_raw( $url ),
			'title'          => sanitize_text_field( $clinic['name'] ),
			'summary'        => sanitize_textarea_field( $clinic['summary'] ),
			'languages'      => array_values( array_map( 'sanitize_text_field', (array) ( isset( $clinic['languages'] ) ? $clinic['languages'] : array() ) ) ),
			'branches'       => WCA_Repository::list_branches( $clinic['id'], true ),
			'services'       => WCA_Repository::list_services( $clinic['id'], true ),
			'verified_owner' => true,
			'indexable'      => true,
			'paid_boost'     => false,
			'donor_boost'    => false,
			'outcome_rank'   => false,
			'freshness'      => array(
				'version'    => absint( $clinic['version'] ),
				'updated_at' => (string) $clinic['updated_at'],
				'generated_at'=> gmdate( 'c' ),
			),
		);
	}

	public static function observe_outbox_event( $envelope ) {
		if ( ! is_array( $envelope ) ) { return; }
		$topic = isset( $envelope['topic'] ) ? (string) $envelope['topic'] : '';
		if ( ! in_array( $topic, array( 'ClinicActivated.v1', 'ClinicServiceChanged.v1', 'ClinicAvailabilityChanged.v1', 'DoctorSuspended.v1' ), true ) ) {
			return;
		}
		$payload = isset( $envelope['payload'] ) && is_array( $envelope['payload'] ) ? $envelope['payload'] : array();
		$clinic_ref = sanitize_text_field( isset( $payload['clinic_ref'] ) ? $payload['clinic_ref'] : ( isset( $envelope['aggregate_ref'] ) ? $envelope['aggregate_ref'] : '' ) );
		if ( ! $clinic_ref || ! preg_match( '/^[0-9a-f-]{36}$/i', $clinic_ref ) ) { return; }
		$trace = isset( $envelope['trace_id'] ) ? sanitize_text_field( $envelope['trace_id'] ) : WCA_Observability::trace_id();
		WCA_Repository::enqueue( 'File26.SearchProjectionChanged.v1', $clinic_ref, array(
			'contract'     => 'wca.file26-clinic-projection',
			'version'      => self::FILE26_PROJECTION_VERSION,
			'object_type'  => 'clinic',
			'public_ref'   => $clinic_ref,
			'change_source'=> sanitize_text_field( $topic ),
			'owner'        => 'File08',
		), $trace );
	}

	public static function register_routes() {
		register_rest_route( 'wca/v1', '/governance', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'rest_manifest' ),
			'permission_callback' => '__return_true',
		) );
		register_rest_route( 'wca/v1', '/search-projection/clinics/(?P<ref>[0-9a-fA-F-]{36})', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( __CLASS__, 'rest_search_projection' ),
			'permission_callback' => '__return_true',
		) );
	}

	public static function rest_manifest() {
		$response = rest_ensure_response( self::manifest() );
		$response->header( 'Cache-Control', 'public, max-age=300, stale-while-revalidate=300' );
		return $response;
	}

	public static function rest_search_projection( WP_REST_Request $request ) {
		$result = self::file26_clinic_projection( sanitize_text_field( $request['ref'] ) );
		if ( is_wp_error( $result ) ) { return $result; }
		$response = rest_ensure_response( $result );
		$response->header( 'Cache-Control', 'public, max-age=60, stale-while-revalidate=120' );
		return $response;
	}

	public static function status_shortcode() {
		if ( ! current_user_can( 'manage_worldwide_clinic' ) && ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		$manifest = self::manifest();
		return '<section class="wca-card" aria-labelledby="wca-governance-title"><h2 id="wca-governance-title">' . esc_html__( 'File 08 governing status', 'worldwide-clinic-appointments' ) . '</h2><p>' . esc_html__( 'Canonical owner controls are active. Production acceptance remains a separate evidence gate.', 'worldwide-clinic-appointments' ) . '</p><dl><dt>' . esc_html__( 'Contract', 'worldwide-clinic-appointments' ) . '</dt><dd>' . esc_html( $manifest['version'] ) . '</dd><dt>' . esc_html__( 'Primary brand token', 'worldwide-clinic-appointments' ) . '</dt><dd>' . esc_html( self::SABRI_GREEN ) . '</dd><dt>' . esc_html__( 'Platform commission', 'worldwide-clinic-appointments' ) . '</dt><dd>0%</dd></dl></section>';
	}
}
