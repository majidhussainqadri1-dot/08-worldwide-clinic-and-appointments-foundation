from pathlib import Path
p=Path('includes/class-wca-cli.php'); s=p.read_text()
old="\tpublic static function migrate() { WCA_Schema::maybe_upgrade(); $count = WCA_Compatibility::migrate_legacy_statuses( 5000 ); WP_CLI::success( sprintf( 'Schema verified; %d legacy statuses migrated.', $count ) ); }"
new="\tpublic static function migrate() { WCA_Schema::maybe_upgrade(); $count = WCA_Compatibility::migrate_legacy_statuses( 5000 ); if ( is_wp_error( $count ) ) { WP_CLI::error( $count->get_error_message() ); return; } WP_CLI::success( sprintf( 'Schema verified; %d legacy statuses migrated.', $count ) ); }"
if s.count(old)!=1: raise SystemExit('CLI migrate anchor mismatch')
p.write_text(s.replace(old,new,1))
