from pathlib import Path
p=Path('includes/class-wca-schema.php')
s=p.read_text()
old="""\t\t$written = SWC_Helpers::update_option_strict( self::OPTION_DB_VERSION, WCA_Contracts::SCHEMA_VERSION, 'wca_schema_version_write' );
\t\tif ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 canonical schema version could not be persisted.' ); }
\t\t$migration_state = array(
\t\t\t'status'       => 'installed',
\t\t\t'from_version' => $from_version,
\t\t\t'to_version'   => WCA_Contracts::SCHEMA_VERSION,
\t\t\t'completed_at' => current_time( 'mysql', true ),
\t\t);
\t\t$written = SWC_Helpers::update_option_strict( self::OPTION_MIGRATION_STATE, $migration_state, 'wca_migration_state_write' );
\t\tif ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 canonical migration state could not be persisted.' ); }"""
new="""\t\t$migration_state = array(
\t\t\t'status'       => 'installed',
\t\t\t'from_version' => $from_version,
\t\t\t'to_version'   => WCA_Contracts::SCHEMA_VERSION,
\t\t\t'completed_at' => current_time( 'mysql', true ),
\t\t);
\t\t$written = SWC_Helpers::update_option_strict( self::OPTION_MIGRATION_STATE, $migration_state, 'wca_migration_state_write' );
\t\tif ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 canonical migration state could not be persisted.' ); }
\t\t$written = SWC_Helpers::update_option_strict( self::OPTION_DB_VERSION, WCA_Contracts::SCHEMA_VERSION, 'wca_schema_version_write' );
\t\tif ( is_wp_error( $written ) ) { throw new RuntimeException( 'File 08 canonical schema version could not be persisted.' ); }"""
if s.count(old)!=1: raise SystemExit('schema marker anchor mismatch')
p.write_text(s.replace(old,new,1))
