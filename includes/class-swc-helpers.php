<?php
/**
 * Shared contracts and safety helpers for File 08.
 *
 * @package Worldwide_Clinic
 */

defined( 'ABSPATH' ) || exit;

final class SWC_Helpers {
	const TYPE = 'swc_appointment';

	/**
	 * Approved seven-state lifecycle.
	 *
	 * @return array<string,string>
	 */
	public static function statuses() {
		$labels = WCA_Contracts::appointment_statuses();
		foreach ( $labels as $key => $label ) {
			$labels[ $key ] = __( $label, 'worldwide-clinic-appointments' );
		}
		return apply_filters( 'swc_statuses', $labels );
	}

	/** Canonical actor-specific state law. */
	public static function transition_matrix() {
		return apply_filters( 'swc_status_transition_matrix', WCA_Contracts::transition_matrix() );
	}

	public static function allowed_transitions( $actor, $current ) {
		return WCA_Contracts::allowed_transitions( $actor, $current );
	}

	public static function can_transition( $actor, $current, $next ) {
		return WCA_Contracts::can_transition( $actor, $current, $next );
	}

	public static function status( $id ) {
		$status = (string) get_post_meta( absint( $id ), '_swc_status', true );
		return WCA_Contracts::normalize_appointment_status( $status );
	}

	public static function meta( $id, $key, $default = '' ) {
		$value = get_post_meta( absint( $id ), '_swc_' . sanitize_key( $key ), true );
		return '' === $value ? $default : $value;
	}

	public static function doctor_meta( $id, $key, $default = '' ) {
		$value = get_user_meta( absint( $id ), '_swc_' . sanitize_key( $key ), true );
		return '' === $value ? $default : $value;
	}

	/**
	 * File 08 never creates or mutates doctor verification.
	 * It consumes all central verification gates and fails closed.
	 */
	public static function is_verified_doctor( $user_id ) {
		$user_id = absint( $user_id );
		if ( ! $user_id || ! get_userdata( $user_id ) ) {
			return false;
		}
		if ( ! class_exists( 'SPD_Helpers' ) || ! class_exists( 'SDD_Helpers' ) || ! class_exists( 'GDO_Helpers' ) ) {
			return false;
		}
		if ( ! function_exists( 'smc_user_status' ) || ! function_exists( 'smc_is_founder' ) ) {
			return false;
		}

		$is_founder = SDD_Helpers::is_founder( $user_id ) && smc_is_founder( $user_id );
		$smc_ok     = $is_founder || ( 'approved' === smc_user_status( $user_id ) && (bool) get_user_meta( $user_id, '_smc_doctor_verified', true ) );
		$legacy_ok  = SDD_Helpers::is_verified( $user_id );
		$gdo_ok     = $is_founder || 100 === (int) GDO_Helpers::completion( $user_id );

		return $smc_ok && $legacy_ok && $gdo_ok;
	}

	/**
	 * Return all eligible clinic doctor IDs without a silent hard limit.
	 *
	 * @return int[]
	 */
	public static function doctor_ids( $limit = 100, $offset = 0 ) {
		$limit = min( 200, max( 1, absint( $limit ) ) );
		$offset = max( 0, absint( $offset ) );
		$users = get_users(
			array(
				'role__in' => array( 'sabri_doctor_verified', 'sabri_doctor' ),
				'number'   => $limit,
				'offset'   => $offset,
				'fields'   => 'ID',
				'orderby'  => 'display_name',
				'order'    => 'ASC',
			)
		);
		$ids   = array_values(
			array_filter(
				array_map( 'absint', $users ),
				function ( $id ) {
					return self::is_verified_doctor( $id ) && ( SDD_Helpers::is_public( $id ) || SDD_Helpers::is_founder( $id ) );
				}
			)
		);
		$founder = SDD_Helpers::founder_id();
		if ( $founder && self::is_verified_doctor( $founder ) ) {
			array_unshift( $ids, $founder );
		}
		return array_values( array_unique( $ids ) );
	}

	public static function doctor_is_requestable( $doctor_id ) {
		$availability = self::availability( $doctor_id );
		return self::is_verified_doctor( $doctor_id )
			&& self::availability_is_valid( $availability )
			&& ! empty( $availability['accepting'] )
			&& empty( $availability['unavailable'] );
	}

	public static function requestable_doctor_ids( $limit = 100, $offset = 0 ) {
		return array_values( array_filter( self::doctor_ids( $limit, $offset ), array( __CLASS__, 'doctor_is_requestable' ) ) );
	}

	/** Public/browser doctor references must remain opaque. */
	public static function practitioner_ref( $doctor_id ) {
		return class_exists( 'WCA_Plan_Guard' ) ? WCA_Plan_Guard::practitioner_ref( absint( $doctor_id ) ) : '';
	}

	public static function practitioner_id( $ref ) {
		return class_exists( 'WCA_Plan_Guard' ) ? WCA_Plan_Guard::practitioner_id( sanitize_text_field( $ref ) ) : 0;
	}

	public static function can_doctor_manage( $appointment_id, $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id
			&& self::TYPE === get_post_type( absint( $appointment_id ) )
			&& absint( self::meta( $appointment_id, 'doctor_id' ) ) === $user_id
			&& self::is_verified_doctor( $user_id );
	}

	public static function can_patient_manage( $appointment_id, $user_id = 0 ) {
		$user_id = $user_id ? absint( $user_id ) : get_current_user_id();
		return $user_id
			&& self::TYPE === get_post_type( absint( $appointment_id ) )
			&& absint( get_post_field( 'post_author', $appointment_id ) ) === $user_id;
	}

	public static function can_view( $id ) {
		return self::can_patient_manage( $id ) || self::can_doctor_manage( $id ) || current_user_can( 'manage_worldwide_clinic' );
	}

	public static function timezones() {
		return timezone_identifiers_list();
	}

	public static function valid_timezone( $timezone ) {
		return is_string( $timezone ) && in_array( $timezone, self::timezones(), true );
	}

	/**
	 * Strict local date/time parser.
	 * Invalid dates, DST gaps, and repeated-hour ambiguity are rejected.
	 */
	public static function to_utc( $date, $time, $timezone ) {
		if ( ! is_string( $date ) || ! is_string( $time ) || ! self::valid_timezone( $timezone ) ) {
			return '';
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) || ! preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time ) ) {
			return '';
		}
		try {
			$zone = new DateTimeZone( $timezone );
			$dt   = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $date . ' ' . $time, $zone );
			$err  = DateTimeImmutable::getLastErrors();
			if ( ! $dt || ( is_array( $err ) && ( $err['warning_count'] || $err['error_count'] ) ) ) {
				return '';
			}
			if ( $dt->format( 'Y-m-d H:i' ) !== $date . ' ' . $time ) {
				return '';
			}
			if ( self::is_ambiguous_local_time( $date, $time, $zone, $dt ) ) {
				return '';
			}
			return $dt->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return '';
		}
	}

	private static function is_ambiguous_local_time( $date, $time, DateTimeZone $zone, DateTimeImmutable $candidate ) {
		$local_epoch = gmmktime(
			(int) substr( $time, 0, 2 ),
			(int) substr( $time, 3, 2 ),
			0,
			(int) substr( $date, 5, 2 ),
			(int) substr( $date, 8, 2 ),
			(int) substr( $date, 0, 4 )
		);
		$transitions = $zone->getTransitions( $candidate->getTimestamp() - DAY_IN_SECONDS, $candidate->getTimestamp() + DAY_IN_SECONDS );
		if ( ! is_array( $transitions ) || count( $transitions ) < 2 ) {
			return false;
		}
		$previous = $transitions[0];
		foreach ( array_slice( $transitions, 1 ) as $transition ) {
			$old_offset = (int) $previous['offset'];
			$new_offset = (int) $transition['offset'];
			if ( $old_offset > $new_offset ) {
				$repeat_start = (int) $transition['ts'] + $new_offset;
				$repeat_end   = (int) $transition['ts'] + $old_offset;
				if ( $local_epoch >= $repeat_start && $local_epoch < $repeat_end ) {
					return true;
				}
			}
			$previous = $transition;
		}
		return false;
	}

	public static function display_time( $utc, $timezone ) {
		if ( ! $utc || ! self::valid_timezone( $timezone ) ) {
			return __( 'Not scheduled', 'worldwide-clinic-appointments' );
		}
		try {
			$dt = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', (string) $utc, new DateTimeZone( 'UTC' ) );
			$er = DateTimeImmutable::getLastErrors();
			if ( ! $dt || ( is_array( $er ) && ( $er['warning_count'] || $er['error_count'] ) ) ) {
				return __( 'Time unavailable', 'worldwide-clinic-appointments' );
			}
			return $dt->setTimezone( new DateTimeZone( $timezone ) )->format( 'M j, Y · g:i A' ) . ' (' . $timezone . ')';
		} catch ( Exception $e ) {
			return __( 'Time unavailable', 'worldwide-clinic-appointments' );
		}
	}

	public static function local_parts( $utc, $timezone ) {
		if ( ! $utc || ! self::valid_timezone( $timezone ) ) {
			return array( '', '' );
		}
		try {
			$dt = new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) );
			$dt = $dt->setTimezone( new DateTimeZone( $timezone ) );
			return array( $dt->format( 'Y-m-d' ), $dt->format( 'H:i' ) );
		} catch ( Exception $e ) {
			return array( '', '' );
		}
	}

	public static function phone( $value ) {
		$value = class_exists( 'SPD_Helpers' ) ? SPD_Helpers::clean_phone( $value ) : preg_replace( '/[^0-9+]/', '', (string) $value );
		return self::valid_phone( $value ) ? $value : '';
	}

	public static function valid_phone( $value ) {
		return is_string( $value ) && (bool) preg_match( '/^\+?[0-9]{8,18}$/', $value );
	}

	public static function whatsapp( $value ) {
		return class_exists( 'SPD_Helpers' ) ? SPD_Helpers::whatsapp_url( $value ) : '';
	}

	public static function limit_text( $value, $length, $textarea = false ) {
		$value = $textarea ? sanitize_textarea_field( wp_unslash( $value ) ) : sanitize_text_field( wp_unslash( $value ) );
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, absint( $length ) );
		}
		return substr( $value, 0, absint( $length ) );
	}

	public static function pages() {
		$map = (array) get_option( 'swc_page_map', array() );
		foreach ( $map as $key => $id ) {
			$page = get_post( absint( $id ) );
			if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || 'trash' === $page->post_status ) {
				$map[ $key ] = 0;
			}
		}
		return $map;
	}

	public static function profile_value( $user_id, $key, $default = '' ) {
		if ( function_exists( 'smc_profile_value' ) ) {
			$value = smc_profile_value( absint( $user_id ), $key, '' );
			if ( '' !== $value ) {
				return $value;
			}
		}
		if ( class_exists( 'SPD_Helpers' ) ) {
			return SPD_Helpers::get( absint( $user_id ), $key, $default );
		}
		return $default;
	}

	public static function user_timezone( $user_id ) {
		$candidates = array(
			get_user_meta( absint( $user_id ), '_swc_patient_timezone', true ),
			get_user_meta( absint( $user_id ), '_swc_timezone', true ),
			wp_timezone_string(),
			'UTC',
		);
		foreach ( $candidates as $zone ) {
			if ( self::valid_timezone( $zone ) ) {
				return $zone;
			}
		}
		return 'UTC';
	}

	public static function availability( $doctor_id ) {
		$days = array_values( array_intersect( (array) self::doctor_meta( $doctor_id, 'available_days', array() ), self::weekdays() ) );
		return array(
			'days'        => $days,
			'start'       => (string) self::doctor_meta( $doctor_id, 'start_time', '' ),
			'end'         => (string) self::doctor_meta( $doctor_id, 'end_time', '' ),
			'timezone'    => (string) self::doctor_meta( $doctor_id, 'timezone', 'UTC' ),
			'duration'    => min( 180, max( 10, absint( self::doctor_meta( $doctor_id, 'duration', 30 ) ) ) ),
			'online'      => '1' === self::doctor_meta( $doctor_id, 'online', '0' ),
			'in_person'   => '1' === self::doctor_meta( $doctor_id, 'in_person', '0' ),
			'accepting'   => '1' === self::doctor_meta( $doctor_id, 'accepting', '0' ),
			'unavailable' => '1' === self::doctor_meta( $doctor_id, 'unavailable', '0' ),
		);
	}

	public static function weekdays() {
		return array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );
	}

	public static function valid_time( $time ) {
		return is_string( $time ) && (bool) preg_match( '/^(?:[01]\d|2[0-3]):[0-5]\d$/', $time );
	}

	public static function availability_is_valid( $availability ) {
		if ( empty( $availability['days'] ) || ! self::valid_time( $availability['start'] ) || ! self::valid_time( $availability['end'] ) ) {
			return false;
		}
		if ( $availability['start'] >= $availability['end'] || ! self::valid_timezone( $availability['timezone'] ) ) {
			return false;
		}
		if ( empty( $availability['online'] ) && empty( $availability['in_person'] ) ) {
			return false;
		}
		if ( ! empty( $availability['accepting'] ) && ! empty( $availability['unavailable'] ) ) {
			return false;
		}
		return true;
	}

	public static function doctor_accepts_mode( $doctor_id, $mode ) {
		$a = self::availability( $doctor_id );
		return ( 'online' === $mode && ! empty( $a['online'] ) ) || ( 'in-person' === $mode && ! empty( $a['in_person'] ) );
	}

	public static function slot_is_available( $doctor_id, $utc, $duration = 0, $exclude_id = 0 ) {
		if ( ! self::is_verified_doctor( $doctor_id ) || ! $utc ) {
			return false;
		}
		$a = self::availability( $doctor_id );
		if ( ! self::availability_is_valid( $a ) || empty( $a['accepting'] ) || ! empty( $a['unavailable'] ) ) {
			return false;
		}
		$duration = $duration ? min( 180, max( 10, absint( $duration ) ) ) : $a['duration'];
		try {
			$start_utc = new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) );
			$local     = $start_utc->setTimezone( new DateTimeZone( $a['timezone'] ) );
			$weekday   = strtolower( $local->format( 'l' ) );
			$local_hm  = $local->format( 'H:i' );
			$end_local = $local->modify( '+' . $duration . ' minutes' );
			if ( ! in_array( $weekday, $a['days'], true ) || $local_hm < $a['start'] || $end_local->format( 'Y-m-d' ) !== $local->format( 'Y-m-d' ) || $end_local->format( 'H:i' ) > $a['end'] ) {
				return false;
			}
			$window_start = DateTimeImmutable::createFromFormat( '!Y-m-d H:i', $local->format( 'Y-m-d' ) . ' ' . $a['start'], new DateTimeZone( $a['timezone'] ) );
			if ( ! $window_start ) {
				return false;
			}
			$minutes = (int) floor( ( $local->getTimestamp() - $window_start->getTimestamp() ) / 60 );
			if ( $minutes < 0 || 0 !== $minutes % $a['duration'] ) {
				return false;
			}
			return ! self::has_conflict( $doctor_id, $utc, $duration, $exclude_id );
		} catch ( Exception $e ) {
			return false;
		}
	}

	public static function has_conflict( $doctor_id, $utc, $duration, $exclude_id = 0 ) {
		global $wpdb;
		try {
			$start = new DateTimeImmutable( $utc, new DateTimeZone( 'UTC' ) );
			$end   = $start->modify( '+' . absint( $duration ) . ' minutes' );
			$from  = $start->modify( '-180 minutes' )->format( 'Y-m-d H:i:s' );
			$to    = $end->format( 'Y-m-d H:i:s' );
		} catch ( Exception $e ) {
			return true;
		}
		$sql = "SELECT p.ID, t.meta_value AS appointment_time, d.meta_value AS appointment_duration
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} s ON s.post_id=p.ID AND s.meta_key='_swc_status' AND s.meta_value IN ('confirmed','checked_in','reschedule_pending')
			INNER JOIN {$wpdb->postmeta} doc ON doc.post_id=p.ID AND doc.meta_key='_swc_doctor_id' AND doc.meta_value=%d
			INNER JOIN {$wpdb->postmeta} t ON t.post_id=p.ID AND t.meta_key='_swc_preferred_at_utc' AND t.meta_value BETWEEN %s AND %s
			LEFT JOIN {$wpdb->postmeta} d ON d.post_id=p.ID AND d.meta_key='_swc_appointment_duration'
			WHERE p.post_type=%s AND p.ID<>%d";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, absint( $doctor_id ), $from, $to, self::TYPE, absint( $exclude_id ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $rows || '' !== (string) $wpdb->last_error ) { return true; }
		foreach ( (array) $rows as $row ) {
			try {
				$other_start = new DateTimeImmutable( $row->appointment_time, new DateTimeZone( 'UTC' ) );
				$other_end   = $other_start->modify( '+' . min( 180, max( 10, absint( $row->appointment_duration ) ) ) . ' minutes' );
				if ( $start < $other_end && $end > $other_start ) {
					return true;
				}
			} catch ( Exception $e ) {
				return true;
			}
		}
		return false;
	}

	public static function appointment_public_ref( $id ) {
		$ref = strtolower( (string) self::meta( absint( $id ), 'public_ref', '' ) );
		return preg_match( '/^[0-9a-f-]{36}$/', $ref ) ? $ref : '';
	}

	public static function record_version( $id ) {
		return max( 1, absint( self::meta( $id, 'record_version', 1 ) ) );
	}

	public static function bump_version( $id ) {
		$version = self::record_version( $id ) + 1;
		update_post_meta( absint( $id ), '_swc_record_version', $version );
		return $version;
	}

	/** Fail closed when a module-owned option does not persist with the requested value. */
	public static function update_option_strict( $option, $value, $error_code = 'swc_option_write_failed' ) {
		$option = sanitize_key( (string) $option );
		$updated = update_option( $option, $value, false );
		$current = get_option( $option, null );
		$same = maybe_serialize( $current ) === maybe_serialize( $value );
		if ( false !== $updated || $same ) { return true; }
		return new WP_Error( sanitize_key( $error_code ), __( 'The File 08 setting could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'option' => $option ) );
	}

	/** Fail closed when a module-owned option remains after a required deletion. */
	public static function delete_option_strict( $option, $error_code = 'swc_option_delete_failed' ) {
		$option = sanitize_key( (string) $option );
		if ( false === get_option( $option, false ) ) { return true; }
		$deleted = delete_option( $option );
		if ( false !== $deleted || false === get_option( $option, false ) ) { return true; }
		return new WP_Error( sanitize_key( $error_code ), __( 'The File 08 setting could not be removed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'option' => $option ) );
	}

	/** Fail closed when module-owned user metadata does not persist with the requested value. */
	public static function update_user_meta_strict( $user_id, $key, $value, $error_code = 'swc_user_meta_write_failed' ) {
		$user_id = absint( $user_id );
		$key = (string) $key;
		$updated = update_user_meta( $user_id, $key, $value );
		$current = get_user_meta( $user_id, $key, true );
		if ( false !== $updated || maybe_serialize( $current ) === maybe_serialize( $value ) ) { return true; }
		return new WP_Error( sanitize_key( $error_code ), __( 'The File 08 user setting could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'meta_key' => sanitize_key( $key ) ) );
	}

	/** Fail closed when an authoritative appointment meta write does not persist. */
	public static function update_meta_strict( $id, $key, $value, $error_code = 'swc_meta_write_failed' ) {
		$id = absint( $id );
		$key = (string) $key;
		$updated = update_post_meta( $id, $key, $value );
		if ( false !== $updated ) { return true; }
		$current = get_post_meta( $id, $key, true );
		$same = ( is_scalar( $current ) || null === $current ) && ( is_scalar( $value ) || null === $value )
			? (string) $current === (string) $value
			: maybe_serialize( $current ) === maybe_serialize( $value );
		if ( $same ) { return true; }
		return new WP_Error( sanitize_key( $error_code ), __( 'The appointment state could not be persisted safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
	}

	/** Fail closed when a requested appointment meta deletion leaves the key behind. */
	public static function delete_meta_strict( $id, $key, $error_code = 'swc_meta_delete_failed' ) {
		$id = absint( $id );
		$key = (string) $key;
		if ( ! metadata_exists( 'post', $id, $key ) ) { return true; }
		$deleted = delete_post_meta( $id, $key );
		if ( false !== $deleted && ! metadata_exists( 'post', $id, $key ) ) { return true; }
		if ( ! metadata_exists( 'post', $id, $key ) ) { return true; }
		return new WP_Error( sanitize_key( $error_code ), __( 'The obsolete appointment state could not be removed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
	}

	/** Apply a bounded set of authoritative post-meta changes with fail-closed readback semantics. */
	public static function apply_meta_mutations( $id, $updates = array(), $deletes = array(), $error_code = 'swc_meta_mutation_failed' ) {
		foreach ( (array) $updates as $key => $value ) {
			$written = self::update_meta_strict( $id, (string) $key, $value, $error_code . '_write' );
			if ( is_wp_error( $written ) ) { return $written; }
		}
		foreach ( (array) $deletes as $key ) {
			$deleted = self::delete_meta_strict( $id, (string) $key, $error_code . '_delete' );
			if ( is_wp_error( $deleted ) ) { return $deleted; }
		}
		return true;
	}

	/** Increment the authoritative record version only when the new value is durable. */
	public static function bump_version_strict( $id ) {
		$version = self::record_version( $id ) + 1;
		$written = self::update_meta_strict( absint( $id ), '_swc_record_version', $version, 'swc_version_write_failed' );
		return is_wp_error( $written ) ? $written : $version;
	}

	/**
	 * Database-backed lock using WordPress' unique option name constraint.
	 *
	 * @return mixed|WP_Error
	 */
	public static function with_lock( $id, $callback ) {
		$appointment_id = absint( $id );
		$doctor_id      = absint( self::meta( $appointment_id, 'doctor_id' ) );
		return self::with_resource_lock(
			'appointment-' . $appointment_id,
			function () use ( $appointment_id, $doctor_id, $callback ) {
				$mutation = function () use ( $appointment_id, $callback ) {
					return self::with_database_transaction( $appointment_id, $callback );
				};
				if ( $doctor_id ) {
					return self::with_resource_lock( 'doctor-' . $doctor_id, $mutation );
				}
				return call_user_func( $mutation );
			}
		);
	}

	/**
	 * Keep an appointment mutation and its mandatory audit/outbox side effects atomic.
	 * WP_Error means fail-closed and rolls back the SQL mutation before the lock is released.
	 */
	private static function with_database_transaction( $appointment_id, $callback ) {
		global $wpdb;
		$started = $wpdb->query( 'START TRANSACTION' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		if ( false === $started ) {
			return new WP_Error( 'swc_transaction_start_failed', __( 'The appointment transaction could not be started safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
		}
		try {
			$result = call_user_func( $callback );
			if ( is_wp_error( $result ) ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				wp_cache_delete( absint( $appointment_id ), 'post_meta' );
				return $result;
			}
			$committed = $wpdb->query( 'COMMIT' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			if ( false === $committed ) {
				$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				wp_cache_delete( absint( $appointment_id ), 'post_meta' );
				return new WP_Error( 'swc_transaction_commit_failed', __( 'The appointment transaction could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
			}
			return $result;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			wp_cache_delete( absint( $appointment_id ), 'post_meta' );
			return new WP_Error( 'swc_transaction_failed', __( 'The appointment update could not be committed safely.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
		}
	}

	/**
	 * Short-lived option lock for an appointment, doctor schedule, or other resource.
	 * A conflicting writer fails safely rather than racing through availability checks.
	 *
	 * @return mixed|WP_Error
	 */
	public static function with_resource_lock( $resource, $callback ) {
		global $wpdb;
		$resource = substr( preg_replace( '/[^a-z0-9_-]/', '-', strtolower( (string) $resource ) ), 0, 120 );
		$key      = 'swc_lock_' . md5( $resource );
		$token    = wp_generate_uuid4();
		$now      = time();
		$value    = wp_json_encode( array( 'token' => $token, 'time' => $now, 'resource' => $resource ) );
		if ( ! add_option( $key, $value, '', false ) ) {
			$existing_raw = (string) get_option( $key, '' );
			$existing = json_decode( $existing_raw, true );
			if ( is_array( $existing ) && ! empty( $existing['time'] ) && $now - absint( $existing['time'] ) > 300 ) {
				$swapped = $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s", $value, $key, $existing_raw ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				wp_cache_delete( $key, 'options' );
				if ( 1 !== (int) $swapped ) {
					return new WP_Error( 'swc_locked', __( 'This scheduling resource is being updated. Refresh and try again.', 'worldwide-clinic-appointments' ) );
				}
			}
		}
		if ( (string) get_option( $key, '' ) !== $value ) {
			return new WP_Error( 'swc_locked', __( 'This scheduling resource is being updated. Refresh and try again.', 'worldwide-clinic-appointments' ) );
		}
		try {
			return call_user_func( $callback );
		} finally {
			if ( (string) get_option( $key, '' ) === $value ) {
				delete_option( $key );
			}
		}
	}

	public static function assert_expected( $id, $expected_status, $expected_version ) {
		if ( self::status( $id ) !== $expected_status || self::record_version( $id ) !== absint( $expected_version ) ) {
			return new WP_Error( 'swc_stale', __( 'This appointment changed after the form was opened. Refresh before saving.', 'worldwide-clinic-appointments' ) );
		}
		return true;
	}

	/**
	 * Structured, viewable audit log. Returns false on database failure.
	 */
	public static function audit( $appointment, $event, $args = array() ) {
		global $wpdb;
		$defaults = array(
			'actor_role'     => self::actor_role(),
			'old_status'     => '',
			'new_status'     => '',
			'old_doctor_id'  => 0,
			'new_doctor_id'  => 0,
			'source'         => 'web',
			'reason'         => '',
			'details'        => array(),
		);
		$args = wp_parse_args( $args, $defaults );
		$data = array(
			'appointment_id' => absint( $appointment ),
			'actor_id'       => get_current_user_id(),
			'actor_role'     => sanitize_key( $args['actor_role'] ),
			'action'         => sanitize_key( $event ),
			'event'          => sanitize_key( $event ),
			'old_status'     => sanitize_key( $args['old_status'] ),
			'new_status'     => sanitize_key( $args['new_status'] ),
			'old_doctor_id'  => absint( $args['old_doctor_id'] ),
			'new_doctor_id'  => absint( $args['new_doctor_id'] ),
			'source'         => sanitize_key( $args['source'] ),
			'note'           => self::limit_text( $args['reason'], 1000, true ),
			'reason'         => self::limit_text( $args['reason'], 1000, true ),
			'details_json'   => wp_json_encode( is_array( $args['details'] ) ? $args['details'] : array() ),
			'created_at'     => current_time( 'mysql', true ),
		);
		$ok = $wpdb->insert(
			$wpdb->prefix . 'swc_audit_log',
			$data,
			array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( false === $ok ) {
			update_option( 'swc_last_audit_error', self::limit_text( $wpdb->last_error, 500, false ), false );
			error_log( 'File 08 audit failure: ' . $wpdb->last_error ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return false;
		}
		return true;
	}

	public static function audit_rows( $appointment_id ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}swc_audit_log WHERE appointment_id=%d ORDER BY id DESC",
				absint( $appointment_id )
			)
		);
	}

	private static function actor_role() {
		if ( current_user_can( 'manage_worldwide_clinic' ) ) {
			return 'admin';
		}
		if ( self::is_verified_doctor( get_current_user_id() ) ) {
			return 'doctor';
		}
		return is_user_logged_in() ? 'patient' : 'system';
	}

	public static function emergency_notice() {
		$default = __( 'If you may be experiencing a medical emergency, contact your local emergency services immediately. Do not wait for an online appointment.', 'worldwide-clinic-appointments' );
		$notice  = get_option( 'swc_emergency_notice', $default );
		return trim( (string) $notice ) ? (string) $notice : $default;
	}

	/**
	 * Unified File 19 notification first, checked email fallback second.
	 */
	public static function notify_user( $user_id, $event, $title, $body, $appointment_id, $link = '', $priority = 'normal' ) {
		$user_id = absint( $user_id );
		$user    = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		$appointment_ref = self::appointment_public_ref( $appointment_id );
		$args = array(
			'user_id'       => $user_id,
			'actor_user_id' => get_current_user_id(),
			'category'      => 'appointments',
			'type'          => sanitize_key( $event ),
			'priority'      => sanitize_key( $priority ),
			'title'         => sanitize_text_field( $title ),
			'body'          => sanitize_textarea_field( $body ),
			'link'          => esc_url_raw( $link ),
			'entity_type'   => 'appointment',
			'entity_ref'    => $appointment_ref,
			'source'        => 'file08',
			'source_ref'    => $appointment_ref,
			'dedupe_key'    => 'file08|' . sanitize_key( $event ) . '|' . $appointment_ref . '|' . self::record_version( $appointment_id ),
			'context'       => array( 'appointment_ref' => $appointment_ref ),
		);
		if ( class_exists( 'SUN_Core' ) ) {
			return (bool) SUN_Core::create( $args );
		}
		if ( has_action( 'sabri_notify' ) ) {
			do_action( 'sabri_notify', $args );
			return true;
		}
		$subject = __( 'Worldwide Clinic Appointment Update', 'worldwide-clinic-appointments' );
		$message = sanitize_textarea_field( $body ) . "\n\n" . ( $appointment_ref ? sprintf( __( 'Appointment reference: %s', 'worldwide-clinic-appointments' ), $appointment_ref ) . "\n" : '' ) . __( 'Do not send sensitive medical information by email.', 'worldwide-clinic-appointments' );
		$sent    = is_email( $user->user_email ) && wp_mail( $user->user_email, $subject, $message );
		if ( ! $sent ) {
			update_option(
				'swc_last_delivery_error',
				array(
					'user_id'        => $user_id,
					'appointment_id' => absint( $appointment_id ),
					'event'          => sanitize_key( $event ),
					'time'           => current_time( 'mysql', true ),
				),
				false
			);
			self::audit( $appointment_id, 'notification-failed', array( 'source' => 'email-fallback', 'reason' => 'wp_mail returned false' ) );
		}
		return $sent;
	}

	/**
	 * Atomic per-user/per-IP rate counter.
	 */
	public static function rate_limit_hit( $scope_or_user, $user_or_limit = 5, $limit_or_window = HOUR_IN_SECONDS, $window = null ) {
		global $wpdb;
		if ( null === $window ) {
			$scope   = 'legacy';
			$user_id = absint( $scope_or_user );
			$limit   = absint( $user_or_limit );
			$window  = absint( $limit_or_window );
		} else {
			$scope   = sanitize_key( $scope_or_user );
			$user_id = absint( $user_or_limit );
			$limit   = absint( $limit_or_window );
			$window  = absint( $window );
		}
		$window = max( 60, $window );
		$bucket = floor( time() / $window );
		$ip     = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$key    = hash( 'sha256', $scope . '|' . $user_id . '|' . $ip . '|' . $bucket );
		$table  = $wpdb->prefix . 'swc_rate_limits';
		$now    = current_time( 'mysql', true );
		$expiry = gmdate( 'Y-m-d H:i:s', time() + $window );
		$sql    = "INSERT INTO {$table} (key_hash,hits,window_started,expires_at) VALUES (%s,1,%s,%s)
			ON DUPLICATE KEY UPDATE hits=LAST_INSERT_ID(hits+1), expires_at=VALUES(expires_at)";
		$result = $wpdb->query( $wpdb->prepare( $sql, $key, $now, $expiry ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( false === $result ) { return true; }
		if ( 1 === (int) $result ) {
			$hits = 1;
		} else {
			$hits_raw = $wpdb->get_var( 'SELECT LAST_INSERT_ID()' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( null === $hits_raw || '' !== (string) $wpdb->last_error ) { return true; }
			$hits = (int) $hits_raw;
		}
		return $hits > max( 1, $limit );
	}
}
