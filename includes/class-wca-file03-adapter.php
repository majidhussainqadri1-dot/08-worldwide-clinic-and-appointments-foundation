<?php
/**
 * Exact File 03 profile/clinic integration adapters.
 *
 * File 08 remains the canonical clinic/appointment owner. These callbacks
 * publish only public-safe projections and revalidate File 03 delegation at use time.
 */
defined( 'ABSPATH' ) || exit;

final class WCA_File03_Adapter {
	const CONTRACT_VERSION = '1.0.0';
	private static $profile_delegate_provider = null;

	public static function register_hooks() {
		add_filter( 'sabri_file08_public_clinic_projection_v1', array( __CLASS__, 'public_clinic_claim' ), 10, 4 );
		add_filter( 'sabri_file08_profile_reviews_projection_v1', array( __CLASS__, 'reviews_claim' ), 10, 4 );
		add_action( 'sabri_file08_register_profile_delegation_provider', array( __CLASS__, 'register_profile_delegate_provider' ), 10, 2 );
	}

	public static function register_profile_delegate_provider( $owner, $callback ) {
		if ( 'file03' === sanitize_key( (string) $owner ) && is_callable( $callback ) ) {
			self::$profile_delegate_provider = $callback;
		}
	}

	public static function delegate_can_schedule( $owner_user_id, $delegate_user_id ) {
		if ( ! is_callable( self::$profile_delegate_provider ) ) { return false; }
		try {
			return true === call_user_func( self::$profile_delegate_provider, absint( $owner_user_id ), absint( $delegate_user_id ), 'clinic_schedule_request' );
		} catch ( Throwable $e ) {
			return false;
		}
	}

	public static function public_clinic_claim( $claim, $doctor_user_id, $viewer_id = 0, $consumer_contract = '' ) {
		unset( $claim, $viewer_id, $consumer_contract );
		$doctor_user_id = absint( $doctor_user_id );
		if ( ! $doctor_user_id || ! SWC_Doctor_Authority::is_eligible( $doctor_user_id ) ) { return array(); }
		WCA_Repository::clear_read_error();
		$clinics = WCA_Repository::list_clinics( array( 'owner_user_id' => $doctor_user_id, 'status' => 'active', 'per_page' => 10 ) );
		$read_error = WCA_Repository::consume_read_error();
		if ( is_wp_error( $read_error ) || ! is_array( $clinics ) || empty( $clinics ) ) { return array(); }
		$clinic = reset( $clinics );
		$projection = WCA_Service::public_clinic_projection( $clinic['public_ref'] ?? 0 );
		if ( is_wp_error( $projection ) || ! is_array( $projection ) || empty( $projection['public_ref'] ) ) { return array(); }
		$services = array();
		foreach ( array_slice( (array) ( $projection['services'] ?? array() ), 0, 50 ) as $service ) {
			if ( ! is_array( $service ) ) { continue; }
			$name = sanitize_text_field( (string) ( $service['name'] ?? $service['title'] ?? '' ) );
			if ( '' !== $name ) { $services[] = $name; }
		}
		$languages = array();
		foreach ( (array) ( $projection['languages'] ?? array() ) as $language ) {
			if ( is_scalar( $language ) && '' !== trim( (string) $language ) ) { $languages[] = sanitize_text_field( (string) $language ); }
		}
		$now = time();
		$slug = sanitize_title( (string) ( $projection['slug'] ?? '' ) );
		return array(
			'contract_version' => self::CONTRACT_VERSION,
			'generated_at' => gmdate( 'c', $now ),
			'valid_until' => gmdate( 'c', $now + 180 ),
			'doctor_user_id' => $doctor_user_id,
			'status' => 'active',
			'visibility' => 'public',
			'owner_version' => (string) max( 1, absint( $projection['record_version'] ?? $clinic['version'] ?? 1 ) ),
			'name' => sanitize_text_field( (string) ( $projection['name'] ?? '' ) ),
			'languages' => array_values( array_unique( $languages ) ),
			'services' => array_values( array_unique( $services ) ),
			'url' => $slug ? home_url( user_trailingslashit( 'clinic/' . rawurlencode( $slug ) ) ) : '',
			'appointment_url' => $slug ? home_url( user_trailingslashit( 'appointments/book/' . rawurlencode( $slug ) ) ) : '',
		);
	}

	/**
	 * File 08 currently owns eligibility rather than public review text storage.
	 * Publish a truthful empty current projection instead of fabricating review content.
	 */
	public static function reviews_claim( $claim, $doctor_user_id, $viewer_id = 0, $consumer_contract = '' ) {
		unset( $claim, $viewer_id, $consumer_contract );
		$doctor_user_id = absint( $doctor_user_id );
		if ( ! $doctor_user_id || ! SWC_Doctor_Authority::is_eligible( $doctor_user_id ) ) { return array(); }
		$now = time();
		return array(
			'contract_version' => self::CONTRACT_VERSION,
			'generated_at' => gmdate( 'c', $now ),
			'valid_until' => gmdate( 'c', $now + 180 ),
			'doctor_user_id' => $doctor_user_id,
			'owner_version' => 'eligibility-' . WCA_VERSION,
			'items' => array(),
		);
	}
}
