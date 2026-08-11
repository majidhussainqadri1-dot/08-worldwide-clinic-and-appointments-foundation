<?php
/**
 * Canonical File 08 persistence repository.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Repository {
	public static function uuid() {
		return function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000, mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
		);
	}

	public static function now() {
		return current_time( 'mysql', true );
	}

	private static function json( $value ) {
		$encoded = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return is_string( $encoded ) ? $encoded : '{}';
	}

	private static function decode( $value, $default = array() ) {
		$data = json_decode( (string) $value, true );
		return is_array( $data ) ? $data : $default;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_clinic( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['clinics'];
		$now   = self::now();
		$row   = array(
			'public_ref'         => self::uuid(),
			'slug'               => sanitize_title( $data['slug'] ?? $data['name'] ?? '' ),
			'owner_user_id'      => absint( $data['owner_user_id'] ?? 0 ),
			'owner_subject_uuid' => sanitize_text_field( $data['owner_subject_uuid'] ?? '' ),
			'name'               => sanitize_text_field( $data['name'] ?? '' ),
			'summary'            => sanitize_textarea_field( $data['summary'] ?? '' ),
			'languages_json'     => self::json( array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $data['languages'] ?? array() ) ) ) ) ),
			'contacts_json'      => self::json( self::sanitize_contacts( (array) ( $data['contacts'] ?? array() ) ) ),
			'policies_json'      => self::json( self::sanitize_policies( (array) ( $data['policies'] ?? array() ) ) ),
			'status'             => sanitize_key( $data['status'] ?? 'draft' ),
			'version'            => 1,
			'created_at'         => $now,
			'updated_at'         => $now,
		);
		if ( ! $row['slug'] || ! $row['name'] || ! $row['owner_user_id'] ) {
			return new WP_Error( 'wca_clinic_required', __( 'Clinic owner, name, and slug are required.', 'worldwide-clinic-appointments' ) );
		}
		if ( ! in_array( $row['status'], WCA_Contracts::lifecycles()['clinic'], true ) ) {
			return new WP_Error( 'wca_clinic_status', __( 'Invalid clinic status.', 'worldwide-clinic-appointments' ) );
		}
		if ( false === $wpdb->insert( $table, $row, array( '%s','%s','%d','%s','%s','%s','%s','%s','%s','%s','%d','%s','%s' ) ) ) {
			return new WP_Error( 'wca_clinic_insert', __( 'Clinic could not be created.', 'worldwide-clinic-appointments' ) );
		}
		return self::get_clinic( (int) $wpdb->insert_id, false );
	}

	/** @return array<string,mixed>|null */
	public static function get_clinic( $id_or_ref, $public_only = false ) {
		global $wpdb;
		$table = WCA_Schema::tables()['clinics'];
		if ( is_numeric( $id_or_ref ) ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", absint( $id_or_ref ) );
		} elseif ( preg_match( '/^[0-9a-f-]{36}$/i', (string) $id_or_ref ) ) {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1", (string) $id_or_ref );
		} else {
			$sql = $wpdb->prepare( "SELECT * FROM {$table} WHERE slug=%s LIMIT 1", sanitize_title( $id_or_ref ) );
		}
		$row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row || ( $public_only && 'active' !== $row['status'] ) ) {
			return null;
		}
		return self::hydrate_clinic( $row, $public_only );
	}

	/** @return array<int,array<string,mixed>> */
	public static function list_clinics( $args = array() ) {
		global $wpdb;
		$table    = WCA_Schema::tables()['clinics'];
		$per_page = min( 100, max( 1, absint( $args['per_page'] ?? $args['limit'] ?? 20 ) ) );
		$page     = max( 1, absint( $args['page'] ?? 1 ) );
		$offset   = isset( $args['offset'] ) ? max( 0, absint( $args['offset'] ) ) : ( $page - 1 ) * $per_page;
		$status   = sanitize_key( $args['status'] ?? 'active' );
		$owner_id = absint( $args['owner_user_id'] ?? 0 );
		$search   = sanitize_text_field( $args['search'] ?? '' );
		$country  = strtoupper( substr( sanitize_text_field( $args['country_code'] ?? '' ), 0, 2 ) );
		$city     = sanitize_text_field( $args['city'] ?? '' );
		$where    = array( '1=1' );
		$params   = array();
		$join     = '';
		if ( $status ) { $where[] = 'c.status=%s'; $params[] = $status; }
		if ( $owner_id ) { $where[] = 'c.owner_user_id=%d'; $params[] = $owner_id; }
		if ( $search ) { $like = '%' . $wpdb->esc_like( $search ) . '%'; $where[] = '(c.name LIKE %s OR c.summary LIKE %s)'; $params[] = $like; $params[] = $like; }
		if ( $country || $city ) {
			$join = " INNER JOIN " . WCA_Schema::tables()['branches'] . " b ON b.clinic_id=c.id AND b.status='active'";
			if ( $country ) { $where[] = 'b.country_code=%s'; $params[] = $country; }
			if ( $city ) { $where[] = 'b.city=%s'; $params[] = $city; }
		}
		$sql = "SELECT DISTINCT c.* FROM {$table} c{$join} WHERE " . implode( ' AND ', $where ) . ' ORDER BY c.updated_at DESC,c.id DESC LIMIT %d OFFSET %d';
		$params[] = $per_page; $params[] = $offset;
		$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = (array) $wpdb->get_results( $prepared, ARRAY_A );
		return array_map( static function ( $row ) use ( $status ) { return self::hydrate_clinic( $row, 'active' === $status ); }, $rows );
	}

	/** @return true|WP_Error */
	public static function update_clinic( $clinic_id, $expected_version, $data ) {
		global $wpdb;
		$table  = WCA_Schema::tables()['clinics'];
		$clinic = self::get_clinic( $clinic_id, false );
		if ( ! $clinic ) {
			return new WP_Error( 'wca_clinic_missing', __( 'Clinic was not found.', 'worldwide-clinic-appointments' ) );
		}
		if ( absint( $expected_version ) !== absint( $clinic['version'] ) ) {
			return new WP_Error( 'wca_stale', __( 'Clinic data changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		$allowed = array();
		if ( isset( $data['name'] ) ) { $allowed['name'] = sanitize_text_field( $data['name'] ); }
		if ( isset( $data['summary'] ) ) { $allowed['summary'] = sanitize_textarea_field( $data['summary'] ); }
		if ( isset( $data['languages'] ) ) { $allowed['languages_json'] = self::json( array_values( array_filter( array_map( 'sanitize_text_field', (array) $data['languages'] ) ) ) ); }
		if ( isset( $data['contacts'] ) ) { $allowed['contacts_json'] = self::json( self::sanitize_contacts( (array) $data['contacts'] ) ); }
		if ( isset( $data['policies'] ) ) { $allowed['policies_json'] = self::json( self::sanitize_policies( (array) $data['policies'] ) ); }
		if ( isset( $data['status'] ) && in_array( sanitize_key( $data['status'] ), WCA_Contracts::lifecycles()['clinic'], true ) ) { $allowed['status'] = sanitize_key( $data['status'] ); }
		$allowed['version']    = absint( $clinic['version'] ) + 1;
		$allowed['updated_at'] = self::now();
		$updated = $wpdb->update( $table, $allowed, array( 'id' => absint( $clinic_id ), 'version' => absint( $expected_version ) ) );
		return false === $updated || 0 === $updated ? new WP_Error( 'wca_clinic_update', __( 'Clinic update failed or was stale.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) ) : true;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_branch( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['branches'];
		$row   = array(
			'public_ref'      => self::uuid(),
			'clinic_id'       => absint( $data['clinic_id'] ?? 0 ),
			'name'            => sanitize_text_field( $data['name'] ?? '' ),
			'country_code'    => strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) ( $data['country_code'] ?? '' ) ), 0, 2 ) ),
			'region'          => sanitize_text_field( $data['region'] ?? '' ),
			'city'            => sanitize_text_field( $data['city'] ?? '' ),
			'address_public'  => sanitize_textarea_field( $data['address_public'] ?? '' ),
			'address_private' => sanitize_textarea_field( $data['address_private'] ?? '' ),
			'timezone'        => WCA_Service::valid_timezone( $data['timezone'] ?? 'UTC' ) ? (string) $data['timezone'] : 'UTC',
			'contacts_json'   => self::json( self::sanitize_contacts( (array) ( $data['contacts'] ?? array() ) ) ),
			'visibility'      => in_array( $data['visibility'] ?? 'public', array( 'public', 'restricted', 'private' ), true ) ? $data['visibility'] : 'public',
			'status'          => in_array( $data['status'] ?? 'active', array( 'active', 'paused', 'archived' ), true ) ? $data['status'] : 'active',
			'version'         => 1,
			'created_at'      => self::now(),
			'updated_at'      => self::now(),
		);
		if ( ! $row['clinic_id'] || ! $row['name'] ) {
			return new WP_Error( 'wca_branch_required', __( 'Branch clinic and name are required.', 'worldwide-clinic-appointments' ) );
		}
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_branch_insert', __( 'Branch could not be created.', 'worldwide-clinic-appointments' ) );
		}
		return self::get_branch( (int) $wpdb->insert_id );
	}

	/** @return array<string,mixed>|null */
	public static function get_branch( $id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['branches'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A );
		if ( ! $row ) { return null; }
		$row['contacts'] = self::decode( $row['contacts_json'] );
		unset( $row['contacts_json'] );
		return $row;
	}

	/** @return array<int,array<string,mixed>> */
	public static function list_branches( $clinic_id, $public_only = false ) {
		global $wpdb;
		$table = WCA_Schema::tables()['branches'];
		$sql = "SELECT * FROM {$table} WHERE clinic_id=%d" . ( $public_only ? " AND status='active' AND visibility='public'" : '' ) . ' ORDER BY name ASC,id ASC';
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, absint( $clinic_id ) ), ARRAY_A );
		return array_map( static function ( $row ) use ( $public_only ) {
			$row['contacts'] = self::decode( $row['contacts_json'] );
			unset( $row['contacts_json'] );
			if ( ! $public_only ) { return $row; }
			return array(
				'public_ref'     => (string) $row['public_ref'],
				'name'           => (string) $row['name'],
				'country_code'   => (string) $row['country_code'],
				'region'         => (string) $row['region'],
				'city'           => (string) $row['city'],
				'address_public' => (string) $row['address_public'],
				'timezone'       => (string) $row['timezone'],
				'contacts'       => array_intersect_key( (array) $row['contacts'], array_flip( array( 'public_phone', 'public_email', 'public_website', 'public_whatsapp' ) ) ),
				'updated_at'     => (string) $row['updated_at'],
				'record_version' => absint( $row['version'] ),
			);
		}, $rows );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function save_service( $data, $service_id = 0, $expected_version = 0 ) {
		global $wpdb;
		$table = WCA_Schema::tables()['services'];
		$currency = strtoupper( preg_replace( '/[^A-Z]/', '', (string) ( $data['currency'] ?? 'PKR' ) ) );
		$row = array(
			'clinic_id'                 => absint( $data['clinic_id'] ?? 0 ),
			'branch_id'                 => absint( $data['branch_id'] ?? 0 ),
			'doctor_user_id'            => absint( $data['doctor_user_id'] ?? 0 ),
			'name'                      => sanitize_text_field( $data['name'] ?? '' ),
			'consultation_type'         => in_array( $data['consultation_type'] ?? '', array( 'online', 'in_person', 'hybrid', 'home_visit' ), true ) ? $data['consultation_type'] : 'online',
			'duration_minutes'          => min( 480, max( 10, absint( $data['duration_minutes'] ?? 30 ) ) ),
			'currency'                  => 3 === strlen( $currency ) ? $currency : 'PKR',
			'fee_minor'                 => max( 0, absint( $data['fee_minor'] ?? 0 ) ),
			'fee_max_minor'             => max( 0, absint( $data['fee_max_minor'] ?? 0 ) ),
			'tax_policy'                => sanitize_textarea_field( $data['tax_policy'] ?? '' ),
			'refund_policy'             => sanitize_textarea_field( $data['refund_policy'] ?? '' ),
			'cancellation_policy'       => sanitize_textarea_field( $data['cancellation_policy'] ?? '' ),
			'platform_commission_bps'   => 0,
			'status'                    => in_array( $data['status'] ?? 'active', array( 'active', 'paused', 'archived' ), true ) ? $data['status'] : 'active',
			'updated_at'                => self::now(),
		);
		if ( ! $row['clinic_id'] || ! $row['name'] ) {
			return new WP_Error( 'wca_service_required', __( 'Service clinic and name are required.', 'worldwide-clinic-appointments' ) );
		}
		if ( $row['fee_max_minor'] && $row['fee_max_minor'] < $row['fee_minor'] ) {
			return new WP_Error( 'wca_service_fee', __( 'Maximum fee cannot be lower than the minimum fee.', 'worldwide-clinic-appointments' ) );
		}
		if ( $service_id ) {
			$current = self::get_service( $service_id, false );
			if ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {
				return new WP_Error( 'wca_stale', __( 'Service data changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
			$row['version'] = absint( $current['version'] ) + 1;
			$ok = $wpdb->update( $table, $row, array( 'id' => absint( $service_id ), 'version' => absint( $expected_version ) ) );
			if ( ! $ok ) { return new WP_Error( 'wca_service_update', __( 'Service could not be updated.', 'worldwide-clinic-appointments' ) ); }
			return self::get_service( $service_id, false );
		}
		$row['public_ref'] = self::uuid();
		$row['version']    = 1;
		$row['created_at'] = self::now();
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_service_insert', __( 'Service could not be created.', 'worldwide-clinic-appointments' ) );
		}
		return self::get_service( (int) $wpdb->insert_id, false );
	}

	/** @return array<string,mixed>|null */
	public static function get_service( $id, $public_only = true ) {
		global $wpdb;
		$table = WCA_Schema::tables()['services'];
		$sql = "SELECT * FROM {$table} WHERE id=%d" . ( $public_only ? " AND status='active'" : '' ) . ' LIMIT 1';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, absint( $id ) ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $row ?: null;
	}

	/** @return array<int,array<string,mixed>> */
	public static function list_services( $clinic_id, $public_only = true, $doctor_user_id = 0 ) {
		global $wpdb;
		$table  = WCA_Schema::tables()['services'];
		$where  = array( 'clinic_id=%d' );
		$params = array( absint( $clinic_id ) );
		if ( $public_only ) { $where[] = "status='active'"; }
		if ( $doctor_user_id ) { $where[] = '(doctor_user_id=0 OR doctor_user_id=%d)'; $params[] = absint( $doctor_user_id ); }
		$sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where ) . ' ORDER BY name ASC,id ASC';
		$rows = (array) $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $public_only ) { return $rows; }
		$clinic = self::get_clinic( $clinic_id, false );
		return array_values( array_filter( array_map( static function ( $row ) use ( $clinic ) {
			$doctor_id = absint( $row['doctor_user_id'] ) ?: absint( $clinic['owner_user_id'] ?? 0 );
			$practitioner_ref = WCA_Plan_Guard::practitioner_ref( $doctor_id );
			if ( ! $practitioner_ref ) { return null; }
			return array(
				'public_ref'          => (string) $row['public_ref'],
				'practitioner_ref'    => $practitioner_ref,
				'name'                => (string) $row['name'],
				'consultation_type'   => (string) $row['consultation_type'],
				'duration_minutes'    => absint( $row['duration_minutes'] ),
				'currency'            => (string) $row['currency'],
				'fee_minor'           => absint( $row['fee_minor'] ),
				'fee_max_minor'       => absint( $row['fee_max_minor'] ),
				'tax_policy'          => (string) $row['tax_policy'],
				'refund_policy'       => (string) $row['refund_policy'],
				'cancellation_policy' => (string) $row['cancellation_policy'],
				'updated_at'          => (string) $row['updated_at'],
				'record_version'      => absint( $row['version'] ),
			);
		}, $rows ) ) );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function save_availability_rule( $data, $rule_id = 0, $expected_version = 0 ) {
		global $wpdb;
		$table = WCA_Schema::tables()['availability'];
		$row = array(
			'clinic_id'      => absint( $data['clinic_id'] ?? 0 ),
			'branch_id'      => absint( $data['branch_id'] ?? 0 ),
			'service_id'     => absint( $data['service_id'] ?? 0 ),
			'doctor_user_id' => absint( $data['doctor_user_id'] ?? 0 ),
			'timezone'       => WCA_Service::valid_timezone( $data['timezone'] ?? '' ) ? (string) $data['timezone'] : 'UTC',
			'rrule_json'      => self::json( WCA_Service::sanitize_rrule( (array) ( $data['rrule'] ?? array() ) ) ),
			'breaks_json'     => self::json( WCA_Service::sanitize_time_ranges( (array) ( $data['breaks'] ?? array() ) ) ),
			'exceptions_json' => self::json( WCA_Service::sanitize_exceptions( (array) ( $data['exceptions'] ?? array() ) ) ),
			'buffer_before'   => min( 240, absint( $data['buffer_before'] ?? 0 ) ),
			'buffer_after'    => min( 240, absint( $data['buffer_after'] ?? 0 ) ),
			'capacity'        => min( 50, max( 1, absint( $data['capacity'] ?? 1 ) ) ),
			'status'          => in_array( $data['status'] ?? 'active', array( 'active', 'paused', 'archived' ), true ) ? $data['status'] : 'active',
			'updated_at'      => self::now(),
		);
		if ( ! $row['clinic_id'] || ! $row['doctor_user_id'] ) {
			return new WP_Error( 'wca_availability_required', __( 'Clinic and doctor are required for availability.', 'worldwide-clinic-appointments' ) );
		}
		if ( $rule_id ) {
			$current = self::get_availability_rule( $rule_id );
			if ( ! $current || absint( $current['version'] ) !== absint( $expected_version ) ) {
				return new WP_Error( 'wca_stale', __( 'Availability changed. Refresh and try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
			$row['version'] = absint( $current['version'] ) + 1;
			$ok = $wpdb->update( $table, $row, array( 'id' => absint( $rule_id ), 'version' => absint( $expected_version ) ) );
			return $ok ? self::get_availability_rule( $rule_id ) : new WP_Error( 'wca_availability_update', __( 'Availability could not be updated.', 'worldwide-clinic-appointments' ) );
		}
		$row['public_ref'] = self::uuid();
		$row['version']    = 1;
		$row['created_at'] = self::now();
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_availability_insert', __( 'Availability could not be saved.', 'worldwide-clinic-appointments' ) );
		}
		return self::get_availability_rule( (int) $wpdb->insert_id );
	}

	/** @return array<string,mixed>|null */
	public static function get_availability_rule( $id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['availability'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A );
		if ( ! $row ) { return null; }
		foreach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) {
			$row[ $key ] = self::decode( $row[ $key . '_json' ] );
			unset( $row[ $key . '_json' ] );
		}
		return $row;
	}

	/** @return array<int,array<string,mixed>> */
	public static function list_availability_rules( $doctor_user_id, $service_id = 0 ) {
		global $wpdb;
		$table = WCA_Schema::tables()['availability'];
		$where = 'doctor_user_id=%d AND status=%s';
		$params = array( absint( $doctor_user_id ), 'active' );
		if ( $service_id ) { $where .= ' AND (service_id=0 OR service_id=%d)'; $params[] = absint( $service_id ); }
		$rows = (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE {$where} ORDER BY id ASC", $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( static function ( $row ) {
			foreach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) { $row[ $key ] = self::decode( $row[ $key . '_json' ] ); unset( $row[ $key . '_json' ] ); }
			return $row;
		}, $rows );
	}


	/** @return array<string,mixed>|null */
	public static function get_branch_by_ref( $ref, $public_only = false ) {
		global $wpdb;
		$table = WCA_Schema::tables()['branches'];
		$sql = "SELECT * FROM {$table} WHERE public_ref=%s" . ( $public_only ? " AND status='active' AND visibility='public'" : '' ) . ' LIMIT 1';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );
		return $row ?: null;
	}

	/** @return array<string,mixed>|null */
	public static function get_service_by_ref( $ref, $public_only = true ) {
		global $wpdb;
		$table = WCA_Schema::tables()['services'];
		$sql = "SELECT * FROM {$table} WHERE public_ref=%s" . ( $public_only ? " AND status='active'" : '' ) . ' LIMIT 1';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );
		return $row ?: null;
	}

	/** @return array<string,mixed>|null */
	public static function get_availability_rule_by_ref( $ref, $active_only = false ) {
		global $wpdb;
		$table = WCA_Schema::tables()['availability'];
		$sql = "SELECT * FROM {$table} WHERE public_ref=%s" . ( $active_only ? " AND status='active'" : '' ) . ' LIMIT 1';
		$row = $wpdb->get_row( $wpdb->prepare( $sql, sanitize_text_field( $ref ) ), ARRAY_A );
		if ( ! $row ) { return null; }
		foreach ( array( 'rrule', 'breaks', 'exceptions' ) as $key ) { $row[ $key ] = self::decode( $row[ $key . '_json' ] ); unset( $row[ $key . '_json' ] ); }
		return $row;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function hold_slot( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		self::expire_slot_holds();
		$start = WCA_Plan_Guard::strict_utc( $data['start_utc'] ?? '' );
		$end   = WCA_Plan_Guard::strict_utc( $data['end_utc'] ?? '' );
		$doctor_id = absint( $data['doctor_user_id'] ?? 0 );
		$patient_id = absint( $data['patient_user_id'] ?? 0 );
		$clinic_id = absint( $data['clinic_id'] ?? 0 );
		$service_id = absint( $data['service_id'] ?? 0 );
		$idempotency_plain = sanitize_text_field( $data['idempotency_key'] ?? '' );
		if ( ! $idempotency_plain ) {
			return new WP_Error( 'wca_idempotency_required', __( 'An idempotency key is required to hold a slot.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) );
		}
		$idempotency_key = hash( 'sha256', $idempotency_plain );
		if ( ! $start || ! $end || ! $doctor_id || ! $patient_id || strtotime( $end . ' UTC' ) <= strtotime( $start . ' UTC' ) ) {
			return new WP_Error( 'wca_slot_time', __( 'Invalid canonical slot request.', 'worldwide-clinic-appointments' ) );
		}

		$replay = static function ( $row ) use ( $clinic_id, $service_id, $doctor_id, $patient_id, $start, $end ) {
			if ( ! $row ) { return null; }
			$same = absint( $row['clinic_id'] ) === $clinic_id
				&& absint( $row['service_id'] ) === $service_id
				&& absint( $row['doctor_user_id'] ) === $doctor_id
				&& absint( $row['patient_user_id'] ) === $patient_id
				&& (string) $row['start_utc'] === $start
				&& (string) $row['end_utc'] === $end;
			return $same ? $row : new WP_Error( 'wca_idempotency_conflict', __( 'This idempotency key was already used for a different slot request.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		};

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1", $idempotency_key ), ARRAY_A );
		$existing = $replay( $existing );
		if ( is_wp_error( $existing ) || is_array( $existing ) ) { return $existing; }

		$lock_name = 'wca-slot-' . substr( hash( 'sha256', $doctor_id . '|' . substr( $start, 0, 10 ) ), 0, 48 );
		$locked = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s,5)', $lock_name ) );
		if ( 1 !== $locked ) {
			return new WP_Error( 'wca_slot_lock', __( 'The scheduling resource is busy. Please try again.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
		}
		try {
			/* Recheck inside the lock so concurrent retries replay the winner instead of failing or duplicating. */
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1", $idempotency_key ), ARRAY_A );
			$existing = $replay( $existing );
			if ( is_wp_error( $existing ) || is_array( $existing ) ) { return $existing; }
			$conflict = $wpdb->get_var( $wpdb->prepare(
				"SELECT id FROM {$table} WHERE doctor_user_id=%d AND status IN ('held','booked') AND expires_at>%s AND start_utc<%s AND end_utc>%s LIMIT 1",
				$doctor_id, self::now(), $end, $start
			) );
			$duration = max( 1, (int) round( ( strtotime( $end . ' UTC' ) - strtotime( $start . ' UTC' ) ) / 60 ) );
			if ( $conflict || SWC_Helpers::has_conflict( $doctor_id, $start, $duration, 0 ) ) {
				return new WP_Error( 'wca_slot_conflict', __( 'The selected slot is no longer available.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
			$hold_token = hash( 'sha256', self::uuid() . '|' . wp_salt( 'nonce' ) . '|' . microtime( true ) );
			$row = array(
				'hold_token'      => $hold_token,
				'idempotency_key' => $idempotency_key,
				'clinic_id'       => $clinic_id,
				'service_id'      => $service_id,
				'doctor_user_id'  => $doctor_id,
				'patient_user_id' => $patient_id,
				'start_utc'       => $start,
				'end_utc'         => $end,
				'status'          => 'held',
				'appointment_id'  => 0,
				'expires_at'      => gmdate( 'Y-m-d H:i:s', time() + min( 1800, max( 300, absint( $data['ttl'] ?? 600 ) ) ) ),
				'created_at'      => self::now(),
				'updated_at'      => self::now(),
			);
			if ( false === $wpdb->insert( $table, $row ) ) {
				$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE idempotency_key=%s LIMIT 1", $idempotency_key ), ARRAY_A );
				$existing = $replay( $existing );
				if ( is_wp_error( $existing ) || is_array( $existing ) ) { return $existing; }
				return new WP_Error( 'wca_slot_hold', __( 'The slot could not be held.', 'worldwide-clinic-appointments' ) );
			}
			return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock_name ) );
		}
	}

	/** @return array<string,mixed>|null */
	public static function get_slot_hold( $token ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE hold_token=%s LIMIT 1", sanitize_text_field( $token ) ), ARRAY_A );
		return $row ?: null;
	}

	/** @return true|WP_Error */
	public static function book_slot( $token, $appointment_id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		$updated = $wpdb->query( $wpdb->prepare(
			"UPDATE {$table} SET status='booked',appointment_id=%d,updated_at=%s,expires_at=%s WHERE hold_token=%s AND status='held' AND appointment_id=0 AND expires_at>%s",
			absint( $appointment_id ), self::now(), gmdate( 'Y-m-d H:i:s', time() + YEAR_IN_SECONDS ), sanitize_text_field( $token ), self::now()
		) );
		return 1 === (int) $updated ? true : new WP_Error( 'wca_hold_stale', __( 'The slot hold changed or expired before booking.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
	}

	public static function release_appointment_slot( $appointment_id, $status = 'released', $except_hold_token = '' ) {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		$status = in_array( $status, array( 'released', 'expired' ), true ) ? $status : 'released';
		$where = array( 'appointment_id' => absint( $appointment_id ) );
		if ( ! $except_hold_token ) {
			return false !== $wpdb->update( $table, array( 'status' => $status, 'updated_at' => self::now() ), $where );
		}
		return false !== $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status=%s,updated_at=%s WHERE appointment_id=%d AND hold_token<>%s", $status, self::now(), absint( $appointment_id ), sanitize_text_field( $except_hold_token ) ) );
	}

	public static function expire_slot_holds() {
		global $wpdb;
		$table = WCA_Schema::tables()['slot_holds'];
		return $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='expired',updated_at=%s WHERE status='held' AND expires_at<=%s", self::now(), self::now() ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @return array<string,mixed>|WP_Error */
	public static function record_consent( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['consents'];
		$terms = (string) ( $data['terms_text'] ?? '' );
		$row = array(
			'public_ref'         => self::uuid(),
			'appointment_id'     => absint( $data['appointment_id'] ?? 0 ),
			'actor_user_id'      => absint( $data['actor_user_id'] ?? 0 ),
			'actor_subject_uuid' => sanitize_text_field( $data['actor_subject_uuid'] ?? '' ),
			'guardian_user_id'   => absint( $data['guardian_user_id'] ?? 0 ),
			'scope'              => sanitize_key( $data['scope'] ?? 'appointment_processing' ),
			'terms_version'      => sanitize_text_field( $data['terms_version'] ?? '1.0' ),
			'terms_hash'         => hash( 'sha256', $terms ),
			'legal_basis'        => sanitize_key( $data['legal_basis'] ?? 'consent' ),
			'status'             => 'granted',
			'granted_at'         => self::now(),
			'metadata_json'      => self::json( (array) ( $data['metadata'] ?? array() ) ),
		);
		if ( ! $row['appointment_id'] || ! $row['actor_user_id'] || ! $terms ) {
			return new WP_Error( 'wca_consent_required', __( 'Consent actor, appointment, and terms are required.', 'worldwide-clinic-appointments' ) );
		}
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_consent_insert', __( 'Consent could not be recorded.', 'worldwide-clinic-appointments' ) );
		}
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	public static function revoke_consent( $appointment_id, $actor_user_id, $scope ) {
		global $wpdb;
		$table = WCA_Schema::tables()['consents'];
		return false !== $wpdb->query( $wpdb->prepare( "UPDATE {$table} SET status='revoked',revoked_at=%s WHERE appointment_id=%d AND actor_user_id=%d AND scope=%s AND status='granted'", self::now(), absint( $appointment_id ), absint( $actor_user_id ), sanitize_key( $scope ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/** @return array<string,mixed>|WP_Error */
	public static function append_event( $event_type, $aggregate_type, $aggregate_ref, $payload, $actor_user_id = 0, $trace_id = '' ) {
		global $wpdb;
		$table = WCA_Schema::tables()['events'];
		$schema = WCA_Contracts::event_schemas()[ $event_type ] ?? array( 'class' => 'restricted' );
		$row = array(
			'event_id'        => self::uuid(),
			'event_type'      => sanitize_text_field( $event_type ),
			'aggregate_type'  => sanitize_key( $aggregate_type ),
			'aggregate_ref'   => sanitize_text_field( $aggregate_ref ),
			'actor_user_id'   => absint( $actor_user_id ),
			'trace_id'        => $trace_id ?: WCA_Observability::trace_id(),
			'payload_json'    => self::json( WCA_Observability::redact( (array) $payload ) ),
			'privacy_class'   => sanitize_key( $schema['class'] ?? 'restricted' ),
			'occurred_at'     => self::now(),
			'created_at'      => self::now(),
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_event_insert', __( 'Event could not be recorded.', 'worldwide-clinic-appointments' ) );
		}
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function grant_review_eligibility( $appointment_id, $reviewer_user_id, $doctor_user_id, $clinic_id = 0 ) {
		global $wpdb;
		$table = WCA_Schema::tables()['review_eligibility'];
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE appointment_id=%d AND reviewer_user_id=%d LIMIT 1", absint( $appointment_id ), absint( $reviewer_user_id ) ), ARRAY_A );
		if ( $existing ) { return $existing; }
		$row = array(
			'public_ref'       => self::uuid(),
			'appointment_id'   => absint( $appointment_id ),
			'reviewer_user_id' => absint( $reviewer_user_id ),
			'doctor_user_id'   => absint( $doctor_user_id ),
			'clinic_id'        => absint( $clinic_id ),
			'status'           => 'eligible',
			'eligibility_hash' => hash_hmac( 'sha256', absint( $appointment_id ) . '|' . absint( $reviewer_user_id ) . '|' . absint( $doctor_user_id ), wp_salt( 'auth' ) ),
			'granted_at'       => self::now(),
			'expires_at'       => gmdate( 'Y-m-d H:i:s', time() + WCA_Plan_Guard::REVIEW_ELIGIBILITY_DAYS * DAY_IN_SECONDS ),
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_review_eligibility', __( 'Review eligibility could not be granted.', 'worldwide-clinic-appointments' ) );
		}
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	public static function consume_review_eligibility( $public_ref, $reviewer_user_id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['review_eligibility'];
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE public_ref=%s AND reviewer_user_id=%d LIMIT 1", sanitize_text_field( $public_ref ), absint( $reviewer_user_id ) ), ARRAY_A );
		if ( ! $row || 'eligible' !== $row['status'] || strtotime( (string) $row['expires_at'] . ' UTC' ) <= time() ) {
			if ( $row && 'eligible' === $row['status'] ) { $wpdb->update( $table, array( 'status' => 'revoked', 'revoked_at' => self::now(), 'revocation_reason' => 'expired' ), array( 'id' => absint( $row['id'] ), 'status' => 'eligible' ) ); }
			return new WP_Error( 'wca_review_not_eligible', __( 'Review eligibility is unavailable or expired.', 'worldwide-clinic-appointments' ) );
		}
		$ok = $wpdb->update( $table, array( 'status' => 'used', 'used_at' => self::now() ), array( 'id' => absint( $row['id'] ), 'status' => 'eligible' ) );
		return $ok ? true : new WP_Error( 'wca_review_stale', __( 'Review eligibility changed before use.', 'worldwide-clinic-appointments' ) );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_clinical_context( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['clinical_context'];
		$row = array(
			'public_ref'                    => self::uuid(),
			'appointment_id'                => absint( $data['appointment_id'] ?? 0 ),
			'patient_subject_uuid'          => sanitize_text_field( $data['patient_subject_uuid'] ?? '' ),
			'practitioner_subject_uuid'     => sanitize_text_field( $data['practitioner_subject_uuid'] ?? '' ),
			'purpose'                       => sanitize_key( $data['purpose'] ?? 'scheduling_context' ),
			'access_status'                 => 'scheduling_only',
			'treating_relationship_asserted'=> 0,
			'clinical_read'                 => 0,
			'clinical_write'                => 0,
			'prescription_authority'        => 0,
			'break_glass'                   => 0,
			'version'                       => 1,
			'expires_at'                    => gmdate( 'Y-m-d H:i:s', time() + min( HOUR_IN_SECONDS, max( 60, absint( $data['ttl'] ?? 300 ) ) ) ),
			'created_at'                    => self::now(),
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			return new WP_Error( 'wca_context_insert', __( 'Clinical boundary context could not be created.', 'worldwide-clinic-appointments' ) );
		}
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_complaint( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['complaints'];
		$row = array(
			'public_ref'         => self::uuid(),
			'appointment_id'     => absint( $data['appointment_id'] ?? 0 ),
			'clinic_id'          => absint( $data['clinic_id'] ?? 0 ),
			'complainant_user_id'=> absint( $data['complainant_user_id'] ?? 0 ),
			'category'           => sanitize_key( $data['category'] ?? 'service' ),
			'summary'            => sanitize_textarea_field( $data['summary'] ?? '' ),
			'evidence_refs_json' => self::json( array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $data['evidence_refs'] ?? array() ) ) ) ) ),
			'purpose_limit'      => sanitize_text_field( $data['purpose_limit'] ?? 'case_resolution_only' ),
			'status'             => 'submitted',
			'assigned_user_id'   => 0,
			'outcome_json'       => '{}',
			'version'            => 1,
			'created_at'         => self::now(),
			'updated_at'         => self::now(),
		);
		if ( ! $row['complainant_user_id'] || ! $row['summary'] ) { return new WP_Error( 'wca_complaint_required', __( 'Complaint summary is required.', 'worldwide-clinic-appointments' ) ); }
		if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_complaint_insert', __( 'Complaint could not be submitted.', 'worldwide-clinic-appointments' ) ); }
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function create_payment_intent( $data ) {
		global $wpdb;
		$table = WCA_Schema::tables()['payment_intents'];
		$row = array(
			'public_ref'                 => self::uuid(),
			'appointment_id'             => absint( $data['appointment_id'] ?? 0 ),
			'provider'                   => sanitize_key( $data['provider'] ?? 'manual' ),
			'provider_ref'               => sanitize_text_field( $data['provider_ref'] ?? '' ),
			'currency'                   => strtoupper( sanitize_text_field( $data['currency'] ?? 'PKR' ) ),
			'amount_minor'               => max( 0, absint( $data['amount_minor'] ?? 0 ) ),
			'platform_commission_minor'  => 0,
			'status'                     => sanitize_key( $data['status'] ?? 'pending' ),
			'version'                    => 1,
			'metadata_json'              => self::json( WCA_Observability::redact( (array) ( $data['metadata'] ?? array() ) ) ),
			'created_at'                 => self::now(),
			'updated_at'                 => self::now(),
		);
		if ( ! $row['appointment_id'] || 3 !== strlen( $row['currency'] ) ) { return new WP_Error( 'wca_payment_required', __( 'Valid appointment and currency are required.', 'worldwide-clinic-appointments' ) ); }
		if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_payment_insert', __( 'Payment intent could not be recorded.', 'worldwide-clinic-appointments' ) ); }
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function enqueue( $topic, $aggregate_ref, $payload, $trace_id = '' ) {
		global $wpdb;
		$table = WCA_Schema::tables()['outbox'];
		$row = array(
			'message_id'      => self::uuid(),
			'topic'           => sanitize_text_field( $topic ),
			'aggregate_ref'   => sanitize_text_field( $aggregate_ref ),
			'payload_json'    => self::json( WCA_Observability::redact( (array) $payload ) ),
			'status'          => 'pending',
			'attempts'        => 0,
			'next_attempt_at' => self::now(),
			'locked_by'       => '',
			'last_error'      => '',
			'trace_id'        => $trace_id ?: WCA_Observability::trace_id(),
			'created_at'      => self::now(),
			'updated_at'      => self::now(),
		);
		if ( false === $wpdb->insert( $table, $row ) ) { return new WP_Error( 'wca_outbox_insert', __( 'Background event could not be queued.', 'worldwide-clinic-appointments' ) ); }
		return array_merge( array( 'id' => (int) $wpdb->insert_id ), $row );
	}

	/** @return array<int,array<string,mixed>> */
	public static function claim_outbox( $limit = 20, $worker = '' ) {
		global $wpdb;
		$table  = WCA_Schema::tables()['outbox'];
		$limit  = min( 100, max( 1, absint( $limit ) ) );
		$worker = sanitize_text_field( $worker ?: 'worker-' . substr( md5( wp_salt( 'nonce' ) . microtime( true ) ), 0, 12 ) );
		$ids = (array) $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$table} WHERE status IN ('pending','retry') AND next_attempt_at<=%s AND (locked_at IS NULL OR locked_at<%s) ORDER BY id ASC LIMIT %d", self::now(), gmdate( 'Y-m-d H:i:s', time() - 300 ), $limit ) );
		$claimed = array();
		foreach ( $ids as $id ) {
			$ok = $wpdb->update( $table, array( 'status' => 'processing', 'locked_at' => self::now(), 'locked_by' => $worker, 'updated_at' => self::now() ), array( 'id' => absint( $id ) ) );
			if ( $ok ) {
				$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d LIMIT 1", absint( $id ) ), ARRAY_A );
				if ( $row ) { $row['payload'] = self::decode( $row['payload_json'] ); unset( $row['payload_json'] ); $claimed[] = $row; }
			}
		}
		return $claimed;
	}

	public static function complete_outbox( $id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['outbox'];
		return false !== $wpdb->update( $table, array( 'status' => 'delivered', 'delivered_at' => self::now(), 'updated_at' => self::now(), 'locked_at' => null, 'locked_by' => '' ), array( 'id' => absint( $id ) ) );
	}

	public static function fail_outbox( $id, $error, $attempts ) {
		global $wpdb;
		$table   = WCA_Schema::tables()['outbox'];
		$attempts = absint( $attempts ) + 1;
		$status  = $attempts >= 8 ? 'dead_letter' : 'retry';
		$delay   = min( DAY_IN_SECONDS, (int) pow( 2, min( 10, $attempts ) ) * 60 );
		return false !== $wpdb->update( $table, array( 'status' => $status, 'attempts' => $attempts, 'last_error' => substr( sanitize_text_field( $error ), 0, 500 ), 'next_attempt_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ), 'updated_at' => self::now(), 'locked_at' => null, 'locked_by' => '' ), array( 'id' => absint( $id ) ) );
	}

	/** @return array<string,mixed>|WP_Error */
	public static function claim_idempotency( $scope, $key, $actor_user_id, $request ) {
		global $wpdb;
		$table = WCA_Schema::tables()['idempotency'];
		$scope = sanitize_key( $scope );
		$key_hash = hash( 'sha256', (string) $key );
		$request_hash = hash( 'sha256', self::json( $request ) );
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1", $scope, $key_hash, absint( $actor_user_id ) ), ARRAY_A );
		if ( $existing ) {
			if ( ! hash_equals( (string) $existing['request_hash'], $request_hash ) ) {
				return new WP_Error( 'wca_idempotency_conflict', __( 'This idempotency key was used for a different request.', 'worldwide-clinic-appointments' ), array( 'status' => 409 ) );
			}
			$existing['response'] = self::decode( $existing['response_json'] );
			$existing['claimed_new'] = false;
			/* Fail closed on stale processing reservations: the domain side effect may already have committed. */
			if ( 'processing' === (string) $existing['status'] && strtotime( (string) $existing['updated_at'] . ' UTC' ) <= time() - 2 * MINUTE_IN_SECONDS ) {
				$existing['stale_processing'] = true;
				if ( class_exists( 'WCA_Observability' ) ) {
					WCA_Observability::metric( 'idempotency_stale_processing_total', 1, array( 'scope' => $scope ) );
				}
			}
			return $existing;
		}
		$row = array(
			'scope'          => $scope,
			'key_hash'       => $key_hash,
			'actor_user_id'  => absint( $actor_user_id ),
			'request_hash'   => $request_hash,
			'response_code'  => 0,
			'response_json'  => '{}',
			'status'         => 'processing',
			'expires_at'     => gmdate( 'Y-m-d H:i:s', time() + DAY_IN_SECONDS ),
			'created_at'     => self::now(),
			'updated_at'     => self::now(),
		);
		if ( false === $wpdb->insert( $table, $row ) ) {
			/* A concurrent request can win the unique key race after our initial read. Resolve it as an in-progress replay, not a 500. */
			$existing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE scope=%s AND key_hash=%s AND actor_user_id=%d LIMIT 1", $scope, $key_hash, absint( $actor_user_id ) ), ARRAY_A );
			if ( $existing && hash_equals( (string) $existing['request_hash'], $request_hash ) ) {
				$existing['response'] = self::decode( $existing['response_json'] );
				$existing['claimed_new'] = false;
				return $existing;
			}
			return new WP_Error( 'wca_idempotency_insert', __( 'The request could not be reserved.', 'worldwide-clinic-appointments' ) );
		}
		return array_merge( array( 'id' => (int) $wpdb->insert_id, 'response' => array(), 'claimed_new' => true ), $row );
	}

	public static function release_idempotency( $id ) {
		global $wpdb;
		$table = WCA_Schema::tables()['idempotency'];
		return false !== $wpdb->delete( $table, array( 'id' => absint( $id ), 'status' => 'processing' ) );
	}

	public static function complete_idempotency( $id, $response_code, $response ) {
		global $wpdb;
		$table = WCA_Schema::tables()['idempotency'];
		return false !== $wpdb->update( $table, array( 'status' => 'completed', 'response_code' => absint( $response_code ), 'response_json' => self::json( $response ), 'updated_at' => self::now() ), array( 'id' => absint( $id ) ) );
	}

	public static function metric( $key, $value = 1, $dimensions = array() ) {
		global $wpdb;
		$table = WCA_Schema::tables()['metrics'];
		$bucket = gmdate( 'Y-m-d H:00:00' );
		$dimensions = WCA_Observability::redact( (array) $dimensions );
		$json = self::json( $dimensions );
		$hash = hash( 'sha256', $json );
		$value = (float) $value;
		$sql = $wpdb->prepare(
			"INSERT INTO {$table} (metric_key,metric_bucket,count_value,sum_value,min_value,max_value,dimensions_hash,dimensions_json,updated_at)
			 VALUES (%s,%s,1,%f,%f,%f,%s,%s,%s)
			 ON DUPLICATE KEY UPDATE count_value=count_value+1,sum_value=sum_value+VALUES(sum_value),min_value=LEAST(min_value,VALUES(min_value)),max_value=GREATEST(max_value,VALUES(max_value)),updated_at=VALUES(updated_at)",
			sanitize_key( $key ), $bucket, $value, $value, $value, $hash, $json, self::now()
		);
		return false !== $wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function hydrate_clinic( $row, $public_only ) {
		$row['languages'] = self::decode( $row['languages_json'] );
		$row['contacts']  = self::decode( $row['contacts_json'] );
		$row['policies']  = self::decode( $row['policies_json'] );
		unset( $row['languages_json'], $row['contacts_json'], $row['policies_json'] );
		$row['branches'] = self::list_branches( $row['id'], $public_only );
		$row['services'] = self::list_services( $row['id'], $public_only );
		if ( $public_only ) {
			unset( $row['owner_user_id'], $row['owner_subject_uuid'], $row['archived_at'] );
			$row['contacts'] = array_intersect_key( $row['contacts'], array_flip( array( 'public_phone', 'public_email', 'public_website', 'public_whatsapp' ) ) );
		}
		return $row;
	}

	private static function sanitize_contacts( $contacts ) {
		$allowed = array( 'public_phone', 'public_email', 'public_website', 'public_whatsapp', 'private_phone', 'private_email' );
		$out = array();
		foreach ( $allowed as $key ) {
			if ( ! isset( $contacts[ $key ] ) ) { continue; }
			$value = trim( (string) $contacts[ $key ] );
			if ( false !== strpos( $key, 'email' ) ) { $value = sanitize_email( $value ); }
			elseif ( false !== strpos( $key, 'website' ) ) { $value = esc_url_raw( $value ); }
			else { $value = sanitize_text_field( $value ); }
			if ( '' !== $value ) { $out[ $key ] = $value; }
		}
		return $out;
	}

	private static function sanitize_policies( $policies ) {
		$allowed = array( 'booking', 'cancellation', 'refund', 'privacy', 'telehealth', 'emergency', 'guardian', 'complaints', 'tax' );
		$out = array();
		foreach ( $allowed as $key ) {
			if ( isset( $policies[ $key ] ) ) { $out[ $key ] = sanitize_textarea_field( $policies[ $key ] ); }
		}
		$out['platform_commission_percent'] = 0;
		$out['donation_required']           = false;
		return $out;
	}
}
