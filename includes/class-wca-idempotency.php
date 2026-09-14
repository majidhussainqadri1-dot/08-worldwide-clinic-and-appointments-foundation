<?php
/**
 * Fail-closed mutation idempotency reservations for File 08.
 *
 * A stale "processing" reservation is deliberately never stolen automatically:
 * the domain side effect may already have committed even if the worker died before
 * recording the response. Known failures release their own claim; ambiguous claims
 * require expiry/reconciliation rather than permitting a duplicate mutation.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Idempotency {
	/** @return array<string,mixed>|WP_Error */
	public static function claim( $scope, $key, $actor_user_id, $request ) {
		global $wpdb;
		$table        = WCA_Schema::tables()['idempotency'];
		$scope        = sanitize_key( $scope );
		$actor_user_id= absint( $actor_user_id );
		$key_hash     = hash( 'sha256', (string) $key );
		$request_json = wp_json_encode( $request, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$request_hash = hash( 'sha256', is_string( $request_json ) ? $request_json : '{}' );

		$existing = self::find( $table, $scope, $key_hash, $actor_user_id );
		if ( $existing ) {
			return self::existing_result( $existing, $request_hash, $scope );
		}

		$now = current_time( 'mysql', true );
		$row = array(
			'scope'         => $scope,
			'key_hash'      => $key_hash,
			'actor_user_id' => $actor_user_id,
			'request_hash'  => $request_hash,
			'response_code' => 0,
			'response_json' => '{}',
			'status'        => 'processing',
			'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			'created_at'    => $now,
			'updated_at'    => $now,
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			// A concurrent request may have won the unique-key insert race.
			$existing = self::find( $table, $scope, $key_hash, $actor_user_id );
			if ( $existing ) {
				return self::existing_result( $existing, $request_hash, $scope );
			}
			return new WP_Error( 'wca_idempotency_insert', __( 'The request could not be reserved.', 'worldwide-clinic-appointments' ) );
		}
		return array_merge( array( 'id' => (int) $wpdb->insert_id, 'response' => array(), 'claimed_new' => true ), $row );
	}

	/** @return array<string,mixed>|null */
	private static function find( $table, $scope, $key_hash, $actor_user_id ) {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1",
				$scope,
				$key_hash,
				$actor_user_id
			),
			ARRAY_A
		);
		return $row ?: null;
	}

	/** @return array<string,mixed>|WP_Error */
	private static function existing_result( $existing, $request_hash, $scope ) {
		if ( ! hash_equals( (string) $existing['request_hash'], (string) $request_hash ) ) {
			return new WP_Error( 'wca_idempotency_conflict', __( 'This idempotency key was used for a different request.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$response = json_decode( (string) $existing['response_json'], true );
		$existing['response']    = is_array( $response ) ? $response : array();
		$existing['claimed_new'] = false;
		if ( 'processing' === (string) $existing['status'] && strtotime( (string) $existing['updated_at'] . ' UTC' ) <= time() - 2 * MINUTE_IN_SECONDS ) {
			$existing['stale_processing'] = true;
			if ( class_exists( 'WCA_Observability' ) ) {
				WCA_Observability::metric( 'idempotency_stale_processing_total', 1, array( 'scope' => sanitize_key( $scope ) ) );
			}
		}
		return $existing;
	}
}
