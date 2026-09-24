<?php
$root = dirname( __DIR__ );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
$rest = file_get_contents( $root . '/includes/class-wca-rest.php' );
$service = file_get_contents( $root . '/includes/class-wca-service.php' );
$admin = file_get_contents( $root . '/includes/class-wca-admin.php' );
$obs = file_get_contents( $root . '/includes/class-wca-observability.php' );
$cli = file_get_contents( $root . '/includes/class-wca-cli.php' );
$plugin = file_get_contents( $root . '/worldwide-clinic.php' );
$legacy_admin = file_get_contents( $root . '/includes/class-swc-admin.php' );
foreach ( array( $repo,$rest,$service,$admin,$obs,$cli,$plugin,$legacy_admin ) as $source ) { if ( ! is_string( $source ) ) { fwrite( STDERR, "T19 R19 source read failed\n" ); exit( 1 ); } }
$checks = array(
    'repository hard-caps owned child collections' => false !== strpos( $repo, 'const MAX_COLLECTION_ROWS = 100;' ),
    'branch collection SQL is bounded' => false !== strpos( $repo, "ORDER BY name ASC,id ASC LIMIT %d" ),
    'service collection appends a hard-capped limit' => false !== strpos( $repo, '$params[] = $limit;') && false !== strpos( $repo, "ORDER BY name ASC,id ASC LIMIT %d" ),
    'availability collection SQL is bounded' => false !== strpos( $repo, "ORDER BY id ASC LIMIT %d" ),
    'clinic collection supports lightweight discovery rows' => false !== strpos( $repo, 'array_key_exists( \'hydrate_children\', $args )') && false !== strpos( $rest, "'hydrate_children' => false" ),
    'public projection avoids private child hydration before service authorization' => false !== strpos( $service, 'get_clinic( $id_or_slug, false, false )') && false !== strpos( $service, '$private_services = self::repository_read'),
    'operations screen is attached to visible clinic admin parent' => false !== strpos( $admin, "add_submenu_page( 'clinic-management'") && false === strpos( $admin, "add_submenu_page( 'edit.php?post_type=' . SWC_Helpers::TYPE" ),
    'operations screen exposes protected due-outbox control' => false !== strpos( $admin, "wp_nonce_field( 'wca_retry_outbox' )") && false !== strpos( $admin, 'Process due outbox'),
    'manual outbox processing propagates failure state' => false !== strpos( $admin, 'is_wp_error( $result ) ? \'failed\' : \'done\'') && false !== strpos( $admin, 'manual_outbox_process_failed'),
    'queue inspection exposes dead-letter and due state' => false !== strpos( $repo, 'public static function outbox_queue_status()') && false !== strpos( $repo, "'dead_letter'") && false !== strpos( $repo, "'oldest_due_at'"),
    'health consumes queue inspection' => false !== strpos( $obs, 'WCA_Repository::outbox_queue_status()') && false !== strpos( $obs, "'outbox_queue'     => \$queue" ),
    'CLI registration is idempotent' => false !== strpos( $cli, 'private static $registered = false;') && false !== strpos( $cli, 'self::$registered = true;'),
    'recovery CLI is registered before runtime migration attempt' => ( $p = strpos( $plugin, 'WCA_CLI::register();') ) !== false && ( $t = strpos( $plugin, 'SWC_Activator::maybe_upgrade();' ) ) !== false && $p < $t,
    'CLI migrate repairs all owned schema layers' => false !== strpos( $cli, 'SWC_Activator::install_schema();') && false !== strpos( $cli, 'WCA_Schema::install();') && false !== strpos( $cli, 'WCA_Continuity::install_schema();') && false !== strpos( $cli, 'WCA_Future24::install_schema();'),
    'admin complete repair repairs all owned schema layers' => false !== strpos( $legacy_admin, 'WCA_Schema::install();') && false !== strpos( $legacy_admin, 'WCA_Continuity::install_schema();') && false !== strpos( $legacy_admin, 'WCA_Future24::install_schema();'),
    'CLI exposes queue inspection command' => false !== strpos( $cli, "WP_CLI::add_command( 'wca queue'") && false !== strpos( $cli, 'public static function queue()'),
);
foreach ( $checks as $name => $ok ) { if ( ! $ok ) { fwrite( STDERR, "T19 R19 FAIL: {$name}\n" ); exit( 1 ); } }
echo 'T19 R19 operability/performance regressions: PASS ' . count( $checks ) . '/' . count( $checks ) . "\n";
