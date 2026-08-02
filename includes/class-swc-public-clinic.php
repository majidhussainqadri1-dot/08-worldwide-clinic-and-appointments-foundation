<?php
/**
 * Public clinic projection contract for File 08.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Public_Clinic {
	const CONTRACT_VERSION = '1.0.0';

	/**
	 * Return the bounded public clinic projection for a verified public doctor.
	 *
	 * File 08 owns this projection contract. Identity and profile values remain
	 * authoritative in their native modules, while File 08 owns availability,
	 * clinic presentation assembly, and the decision to expose a clinic section.
	 *
	 * @param int $user_id Doctor user ID.
	 * @return array<string,mixed>
	 */
	public static function get( $user_id ) {
		$user_id = absint( $user_id );
		$user    = $user_id ? get_userdata( $user_id ) : false;
		if ( ! $user || ! SWC_Helpers::is_verified_doctor( $user_id ) || ! self::is_public_doctor( $user_id ) ) {
			return array();
		}

		$availability = SWC_Helpers::availability( $user_id );
		$valid_hours  = SWC_Helpers::availability_is_valid( $availability );
		$name         = self::first_profile_value( $user_id, array( 'clinic_name', 'clinic' ) );
		$address      = self::first_profile_value( $user_id, array( 'clinic_address' ) );
		$country      = SWC_Helpers::profile_value( $user_id, 'country', '' );
		$city         = SWC_Helpers::profile_value( $user_id, 'city', '' );

		$has_clinic_data = '' !== trim( (string) $name )
			|| '' !== trim( (string) $address )
			|| '' !== trim( (string) $country )
			|| '' !== trim( (string) $city )
			|| $valid_hours;
		if ( ! $has_clinic_data ) {
			return array();
		}

		if ( '' === trim( (string) $name ) ) {
			$name = (string) $user->display_name;
		}

		$clinic = array(
			'name'    => self::plain_text( $name, 240 ),
			'address' => self::plain_text( $address, 500 ),
			'country' => self::plain_text( $country, 120 ),
			'city'    => self::plain_text( $city, 120 ),
		);
		if ( $valid_hours ) {
			$clinic['hours']    = self::hours_text( $availability );
			$clinic['timezone'] = self::plain_text( $availability['timezone'], 120 );
		}
		$clinic = array_filter(
			$clinic,
			static function ( $value ) {
				return is_scalar( $value ) && '' !== trim( (string) $value );
			}
		);

		/**
		 * Filters may revoke canonical fields only. Canonical visibility cannot be
		 * widened, fields cannot be created, and canonical values cannot be replaced.
		 *
		 * @param array<string,string> $clinic  Canonical bounded clinic fields.
		 * @param int                  $user_id Doctor user ID.
		 */
		$filtered = apply_filters( 'swc_public_clinic_projection', $clinic, $user_id );
		$filtered = is_array( $filtered ) ? $filtered : array();
		$public   = array();
		$limits   = array(
			'name' => 240, 'address' => 500, 'country' => 120,
			'city' => 120, 'hours' => 500, 'timezone' => 120,
		);
		foreach ( $limits as $field => $limit ) {
			if ( ! array_key_exists( $field, $clinic ) || ! array_key_exists( $field, $filtered ) || ! is_scalar( $filtered[ $field ] ) ) {
				continue;
			}
			$allow = (bool) $filtered[ $field ];
			if ( $allow ) {
				$public[ $field ] = $clinic[ $field ];
			}
		}

		return $public
			? array(
				'contract_version' => self::CONTRACT_VERSION,
				'clinic'           => $public,
			)
			: array();
	}

	/** @return array<string,mixed> */
	public static function contract() {
		return array(
			'contract_version' => self::CONTRACT_VERSION,
			'owner'            => 'file-08',
			'visibility'       => 'verified-public-doctor-only',
			'fields'           => array( 'name', 'address', 'country', 'city', 'hours', 'timezone' ),
			'excludes'         => array( 'phone', 'whatsapp', 'email', 'user_id', 'native_id', 'appointments', 'patient_data' ),
			'writes_data'      => false,
		);
	}

	private static function is_public_doctor( $user_id ) {
		if ( ! class_exists( 'SDD_Helpers' ) || ! method_exists( 'SDD_Helpers', 'is_public' ) || ! method_exists( 'SDD_Helpers', 'is_founder' ) ) {
			return false;
		}
		try {
			$authoritative = SDD_Helpers::is_public( $user_id ) || SDD_Helpers::is_founder( $user_id );
		} catch ( Throwable $e ) {
			return false;
		}
		$filtered = (bool) apply_filters( 'swc_public_clinic_is_public', $authoritative, $user_id );
		return $authoritative && $filtered;
	}

	private static function first_profile_value( $user_id, $keys ) {
		foreach ( $keys as $key ) {
			$value = SWC_Helpers::profile_value( $user_id, $key, '' );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return (string) $value;
			}
		}
		return '';
	}

	private static function hours_text( $availability ) {
		$days = array();
		foreach ( (array) $availability['days'] as $day ) {
			$day = sanitize_key( $day );
			if ( in_array( $day, SWC_Helpers::weekdays(), true ) ) {
				$days[] = ucfirst( $day );
			}
		}
		$days = array_values( array_unique( $days ) );
		return self::plain_text(
			implode( ', ', $days ) . ' · ' . (string) $availability['start'] . '–' . (string) $availability['end'],
			500
		);
	}

	private static function plain_text( $value, $limit ) {
		$value = wp_strip_all_tags( (string) $value, true );
		$value = sanitize_text_field( $value );
		$value = preg_replace( '/\s+/u', ' ', $value );
		$value = is_string( $value ) ? trim( $value ) : '';
		return function_exists( 'mb_substr' )
			? mb_substr( $value, 0, absint( $limit ) )
			: substr( $value, 0, absint( $limit ) );
	}
}

if ( ! function_exists( 'swc_get_public_clinic_projection' ) ) {
	function swc_get_public_clinic_projection( $user_id ) {
		return SWC_Public_Clinic::get( $user_id );
	}
}

if ( ! function_exists( 'swc_public_clinic_projection_contract' ) ) {
	function swc_public_clinic_projection_contract() {
		return SWC_Public_Clinic::contract();
	}
}
