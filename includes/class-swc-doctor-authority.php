<?php
/**
 * Authoritative doctor and Founder eligibility adapter for File 08.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Doctor_Authority {
	const FILE_00_MIN_VERSION = '1.2.4';
	const FILE_00_MAX_VERSION = '1.3.0';
	const FILE_00_CONTRACT    = '1.1.2';
	const FILE_09_MIN_VERSION = '1.1.0';
	const FILE_09_MAX_VERSION = '1.2.0';

	/**
	 * Determine whether a user is an authoritative clinic practitioner.
	 *
	 * File 00 owns membership, institutional identity, sanctions, professional
	 * eligibility, and practice authority. File 09 owns Doctor verification.
	 * File 08 does not infer either state from roles, local metadata, completion
	 * percentages, qualification strings, or license dates.
	 *
	 * @param int $user_id WordPress user ID.
	 * @return bool
	 */
	public static function is_eligible( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_userdata( $user_id ) || ! self::file_00_available() ) {
			return false;
		}

		try {
			$assertions = SMC_Contracts::assertions( $user_id );
		} catch ( Throwable $e ) {
			return false;
		}
		if ( ! self::valid_assertions( $assertions, $user_id ) ) {
			return false;
		}
		if ( empty( $assertions['approved'] ) || empty( $assertions['eligible'] ) || ! empty( $assertions['suspended'] ) ) {
			return false;
		}

		$founder = false;
		try {
			$founder_raw = function_exists( 'smc_is_founder' ) ? smc_is_founder( $user_id ) : false;
			$founder = true === $founder_raw || 1 === $founder_raw || '1' === $founder_raw;
		} catch ( Throwable $e ) {
			$founder = false;
		}
		if ( $founder ) {
			$eligible = ! empty( $assertions['institutional_account'] )
				&& 'founder' === sanitize_key( (string) ( $assertions['account_class'] ?? '' ) );
			return self::monotonic_filter( $eligible, $user_id, 'founder' );
		}

		$membership_type = sanitize_key( (string) ( $assertions['membership_type'] ?? '' ) );
		$eligible = 'doctor' === $membership_type
			&& ! empty( $assertions['professional_verified'] )
			&& ! empty( $assertions['can_practice'] )
			&& self::file_09_verified( $user_id )
			&& self::file_03_not_blocked( $user_id );

		return self::monotonic_filter( $eligible, $user_id, 'doctor' );
	}

	/** @return array<string,mixed> */
	public static function contract() {
		return array(
			'contract_version'       => '1.0.0',
			'owner'                  => 'file-08',
			'file_00_runtime_range'  => '>=' . self::FILE_00_MIN_VERSION . ' <' . self::FILE_00_MAX_VERSION,
			'file_00_contract'       => self::FILE_00_CONTRACT,
			'file_09_runtime_range'  => '>=' . self::FILE_09_MIN_VERSION . ' <' . self::FILE_09_MAX_VERSION,
			'local_role_inference'   => false,
			'local_meta_inference'   => false,
			'fail_closed'            => true,
		);
	}

	private static function file_00_available() {
		return defined( 'SMC_VERSION' )
			&& defined( 'SMC_CONTRACT_VERSION' )
			&& self::version_in_range( (string) SMC_VERSION, self::FILE_00_MIN_VERSION, self::FILE_00_MAX_VERSION )
			&& hash_equals( self::FILE_00_CONTRACT, trim( (string) SMC_CONTRACT_VERSION ) )
			&& class_exists( 'SMC_Contracts' )
			&& method_exists( 'SMC_Contracts', 'assertions' )
			&& function_exists( 'smc_is_founder' );
	}

	private static function valid_assertions( $assertions, $user_id ) {
		return is_array( $assertions )
			&& hash_equals( self::FILE_00_CONTRACT, trim( (string) ( $assertions['contract_version'] ?? '' ) ) )
			&& absint( $assertions['user_id'] ?? 0 ) === absint( $user_id );
	}

	private static function file_09_verified( $user_id ) {
		if ( ! defined( 'GDO_VERSION' )
			|| ! self::version_in_range( (string) GDO_VERSION, self::FILE_09_MIN_VERSION, self::FILE_09_MAX_VERSION )
			|| ! function_exists( 'gdo_get_verification_decision' )
			|| ! function_exists( 'gdo_get_approved_snapshot' )
			|| ! function_exists( 'gdo_user_is_verified' )
		) {
			return false;
		}

		try {
			$decision = gdo_get_verification_decision( $user_id );
			$snapshot = gdo_get_approved_snapshot( $user_id );
			$verified_raw = gdo_user_is_verified( $user_id );
			$verified = true === $verified_raw || 1 === $verified_raw || '1' === $verified_raw;
		} catch ( Throwable $e ) {
			return false;
		}
		if ( ! is_array( $decision ) || ! is_array( $snapshot ) || empty( $snapshot ) || ! $verified ) {
			return false;
		}
		$state = sanitize_key( (string) ( $decision['state'] ?? '' ) );
		return ! empty( $decision['verified'] ) && in_array( $state, array( 'verified', 'approved' ), true );
	}

	private static function file_03_not_blocked( $user_id ) {
		if ( ! class_exists( 'SPD_Helpers' ) || ! method_exists( 'SPD_Helpers', 'verification_status' ) ) {
			return true;
		}
		try {
			$status = sanitize_key( (string) SPD_Helpers::verification_status( $user_id ) );
		} catch ( Throwable $e ) {
			return false;
		}
		return ! in_array( $status, array( 'rejected', 'suspended', 'revoked', 'expired', 'unavailable' ), true );
	}

	private static function monotonic_filter( $authoritative, $user_id, $account_class ) {
		$filtered = (bool) apply_filters(
			'swc_authoritative_practitioner_eligible',
			(bool) $authoritative,
			absint( $user_id ),
			sanitize_key( $account_class )
		);
		return (bool) $authoritative && $filtered;
	}

	private static function version_in_range( $version, $minimum, $maximum_exclusive ) {
		$version = trim( (string) $version );
		return 1 === preg_match( '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/', $version )
			&& version_compare( $version, $minimum, '>=' )
			&& version_compare( $version, $maximum_exclusive, '<' );
	}
}
