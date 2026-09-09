from pathlib import Path
import re

path = Path('includes/class-wca-schema.php')
text = path.read_text(encoding='utf-8')
pattern = re.compile(r"\n\t/\*\* Verify a dbDelta CREATE TABLE definition before any schema-version marker is advanced\. @return true\|WP_Error \*/\n\tpublic static function verify_definition_sql\( \$sql \) \{.*?\n\t\}\n\n\tpublic static function maybe_upgrade\(\)", re.S)
replacement = r'''
	/** Verify a dbDelta CREATE TABLE definition before any schema-version marker is advanced. @return true|WP_Error */
	public static function verify_definition_sql( $sql ) {
		global $wpdb;
		if ( ! preg_match( '/CREATE\\s+TABLE\\s+([^\\s(]+)\\s*\\((.*)\\)\\s*[^;]*;?$/is', trim( (string) $sql ), $match ) ) {
			return new WP_Error( 'wca_schema_definition_invalid', __( 'A File 08 schema definition could not be parsed for verification.', 'worldwide-clinic-appointments' ), array( 'status' => 500 ) );
		}
		$table = trim( $match[1], "` \\t\\r\\n" );
		$body  = (string) $match[2];

		$expected_columns = array();
		$expected_indexes = array();
		foreach ( preg_split( '/\\r?\\n/', $body ) as $line ) {
			$line = trim( rtrim( trim( $line ), ',' ) );
			if ( '' === $line ) { continue; }
			if ( preg_match( '/^PRIMARY\\s+KEY\\s*\\(([^)]+)\\)/i', $line, $idx ) ) {
				$expected_indexes['primary'] = array( 'unique' => true, 'columns' => self::parse_index_columns( $idx[1] ) );
				continue;
			}
			if ( preg_match( '/^(UNIQUE\\s+KEY|KEY)\\s+`?([A-Za-z0-9_]+)`?\\s*\\(([^)]+)\\)/i', $line, $idx ) ) {
				$expected_indexes[ strtolower( $idx[2] ) ] = array( 'unique' => 0 === stripos( $idx[1], 'UNIQUE' ), 'columns' => self::parse_index_columns( $idx[3] ) );
				continue;
			}
			if ( ! preg_match( '/^`?([A-Za-z0-9_]+)`?\\s+([^\\s]+)(.*)$/i', $line, $col ) ) { continue; }
			$tail = trim( $col[3] );
			$has_default = (bool) preg_match( '/\\bDEFAULT\\s+(NULL|\\'[^\\']*\\'|"[^"]*"|[^\\s,]+)/i', $tail, $default_match );
			$default = null;
			if ( $has_default ) {
				$raw_default = trim( $default_match[1] );
				if ( 0 !== strcasecmp( $raw_default, 'NULL' ) ) {
					if ( ( "'" === substr( $raw_default, 0, 1 ) && "'" === substr( $raw_default, -1 ) ) || ( '"' === substr( $raw_default, 0, 1 ) && '"' === substr( $raw_default, -1 ) ) ) {
						$raw_default = substr( $raw_default, 1, -1 );
					}
					$default = stripslashes( $raw_default );
				}
			}
			$expected_columns[ strtolower( $col[1] ) ] = array(
				'type'        => self::normalize_schema_type( $col[2] ),
				'null'        => preg_match( '/\\bNOT\\s+NULL\\b/i', $tail ) ? 'NO' : 'YES',
				'has_default' => $has_default,
				'default'     => $default,
				'extra'       => preg_match( '/\\bAUTO_INCREMENT\\b/i', $tail ) ? 'auto_increment' : '',
			);
		}

		$wpdb->last_error = '';
		$columns_raw = $wpdb->get_results( 'SHOW FULL COLUMNS FROM `' . esc_sql( $table ) . '`', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $columns_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_schema_columns_read_failed', __( 'File 08 schema columns could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'table' => sanitize_text_field( $table ) ) ); }
		$columns = array();
		foreach ( (array) $columns_raw as $row ) {
			if ( isset( $row['Field'] ) ) { $columns[ strtolower( (string) $row['Field'] ) ] = $row; }
		}
		$missing_columns = array_values( array_diff( array_keys( $expected_columns ), array_keys( $columns ) ) );
		if ( $missing_columns ) { return new WP_Error( 'wca_schema_columns_missing', __( 'File 08 schema verification found missing columns.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'columns' => $missing_columns ) ); }
		foreach ( $expected_columns as $name => $expected ) {
			$actual = $columns[ $name ];
			if ( self::normalize_schema_type( isset( $actual['Type'] ) ? $actual['Type'] : '' ) !== $expected['type'] ) {
				return new WP_Error( 'wca_schema_column_type_mismatch', __( 'File 08 schema verification found a column type mismatch.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'column' => $name ) );
			}
			if ( strtoupper( (string) ( isset( $actual['Null'] ) ? $actual['Null'] : '' ) ) !== $expected['null'] ) {
				return new WP_Error( 'wca_schema_column_nullability_mismatch', __( 'File 08 schema verification found a column nullability mismatch.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'column' => $name ) );
			}
			if ( $expected['has_default'] ) {
				$actual_default = array_key_exists( 'Default', $actual ) ? $actual['Default'] : null;
				if ( null === $expected['default'] ? null !== $actual_default : (string) $actual_default !== (string) $expected['default'] ) {
					return new WP_Error( 'wca_schema_column_default_mismatch', __( 'File 08 schema verification found a column default mismatch.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'column' => $name ) );
				}
			}
			$actual_extra = strtolower( trim( (string) ( isset( $actual['Extra'] ) ? $actual['Extra'] : '' ) ) );
			if ( $actual_extra !== $expected['extra'] ) {
				return new WP_Error( 'wca_schema_column_extra_mismatch', __( 'File 08 schema verification found a column extra-attribute mismatch.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'column' => $name ) );
			}
		}

		$wpdb->last_error = '';
		$indexes_raw = $wpdb->get_results( 'SHOW INDEX FROM `' . esc_sql( $table ) . '`', ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( null === $indexes_raw || '' !== (string) $wpdb->last_error ) { return new WP_Error( 'wca_schema_indexes_read_failed', __( 'File 08 schema indexes could not be verified safely.', 'worldwide-clinic-appointments' ), array( 'status' => 503, 'table' => sanitize_text_field( $table ) ) ); }
		$indexes = array();
		foreach ( (array) $indexes_raw as $row ) {
			if ( ! isset( $row['Key_name'], $row['Seq_in_index'], $row['Column_name'], $row['Non_unique'] ) ) { continue; }
			$name = strtolower( (string) $row['Key_name'] );
			if ( ! isset( $indexes[ $name ] ) ) { $indexes[ $name ] = array( 'unique' => 0 === (int) $row['Non_unique'], 'columns' => array() ); }
			$indexes[ $name ]['columns'][ (int) $row['Seq_in_index'] ] = strtolower( (string) $row['Column_name'] );
		}
		$missing_indexes = array_values( array_diff( array_keys( $expected_indexes ), array_keys( $indexes ) ) );
		if ( $missing_indexes ) { return new WP_Error( 'wca_schema_indexes_missing', __( 'File 08 schema verification found missing indexes.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'indexes' => $missing_indexes ) ); }
		foreach ( $expected_indexes as $name => $expected ) {
			$actual = $indexes[ $name ];
			ksort( $actual['columns'], SORT_NUMERIC );
			$actual_columns = array_values( $actual['columns'] );
			if ( (bool) $actual['unique'] !== (bool) $expected['unique'] ) {
				return new WP_Error( 'wca_schema_index_uniqueness_mismatch', __( 'File 08 schema verification found an index uniqueness mismatch.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'index' => $name ) );
			}
			if ( $actual_columns !== $expected['columns'] ) {
				return new WP_Error( 'wca_schema_index_columns_mismatch', __( 'File 08 schema verification found an index column-order mismatch.', 'worldwide-clinic-appointments' ), array( 'status' => 500, 'table' => sanitize_text_field( $table ), 'index' => $name ) );
			}
		}
		return true;
	}

	/** @return array<int,string> */
	private static function parse_index_columns( $list ) {
		$columns = array();
		foreach ( explode( ',', (string) $list ) as $column ) {
			$column = trim( $column );
			if ( preg_match( '/^`?([A-Za-z0-9_]+)`?(?:\\(\\d+\\))?$/', $column, $match ) ) { $columns[] = strtolower( $match[1] ); }
		}
		return $columns;
	}

	private static function normalize_schema_type( $type ) {
		return strtolower( preg_replace( '/\\s+/', ' ', trim( (string) $type ) ) );
	}

	public static function maybe_upgrade()'''
new_text, count = pattern.subn(replacement, text, count=1)
if count != 1:
    raise SystemExit(f'expected one verifier block, replaced {count}')
path.write_text(new_text, encoding='utf-8')
print('T21 R6 correction applied')
