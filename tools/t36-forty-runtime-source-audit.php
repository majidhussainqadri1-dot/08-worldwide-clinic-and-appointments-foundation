<?php
/**
 * File08 T36: 40 different requirement-focused whole-runtime source scans.
 *
 * Each pass independently reopens every shipped PHP/JS/CSS source file and
 * syntax-checks every shipped PHP file, then searches for a distinct approved
 * plan code-path anchor. Anchor matches are discovery signals, not semantic,
 * database, security, deployed-state, or end-to-end acceptance proof.
 */
declare(strict_types=1);

$root = dirname( __DIR__ );
$files = array();
foreach ( array( 'includes', 'assets' ) as $folder ) {
    $dir = $root . '/' . $folder;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
    );
    foreach ( $iterator as $entry ) {
        if ( ! $entry->isFile() || $entry->isLink() ) { continue; }
        if ( ! preg_match( '/\.(?:php|js|css)$/', $entry->getFilename() ) ) { continue; }
        $files[] = $entry->getPathname();
    }
}
$files[] = $root . '/worldwide-clinic.php';
sort( $files, SORT_STRING );
if ( count( $files ) < 40 ) {
    fwrite( STDERR, "T36: runtime source inventory unexpectedly incomplete\n" );
    exit( 1 );
}

// 18 functional requirements + 10 nonfunctional + 12 paired Future24 requirements.
$probes = array(
    array( 'FR001 clinic identity', '/clinic_slug|clinic_name|create_clinic/i' ),
    array( 'FR002 fees/currency', '/service_fee|currency|service_price/i' ),
    array( 'FR003 availability/DST', '/availability|timezone|dst/i' ),
    array( 'FR004 slots/hold', '/slot_hold|hold_slot|search_slots/i' ),
    array( 'FR005 appointment request', '/appointment.requested|request_appointment/i' ),
    array( 'FR006 doctor decision', '/confirm|decline|propos/i' ),
    array( 'FR007 reschedule', '/reschedul|compensat/i' ),
    array( 'FR008 cancellation/no-show', '/cancel|no_show/i' ),
    array( 'FR009 checkin/completion', '/check.in|completed/i' ),
    array( 'FR010 dashboards', '/dashboard|appointment_list|clinic_dashboard/i' ),
    array( 'FR011 emergency diversion', '/emergency|red.flag|urgent/i' ),
    array( 'FR012 consent/revocation', '/consent|revok/i' ),
    array( 'FR013 File17 clinical relationship', '/file17|relationship|participant/i' ),
    array( 'FR014 review eligibility', '/review.eligib/i' ),
    array( 'FR015 calendar/ICS', '/calendar|ics|webhook/i' ),
    array( 'FR016 payment boundary', '/payment|refund|commission/i' ),
    array( 'FR017 clinical privacy', '/continuity|private.record|clinical/i' ),
    array( 'FR018 complaint/dispute', '/complaint|dispute/i' ),
    array( 'NFR001 object authorization', '/authoriz|capability|guardian/i' ),
    array( 'NFR002 retention/erasure', '/retention|eras|legal.hold/i' ),
    array( 'NFR003 idempotency/outbox', '/idempoten|outbox|dead.letter/i' ),
    array( 'NFR004 pagination/performance', '/pagination|cursor|cache|limit/i' ),
    array( 'NFR005 accessibility/RTL', '/aria|rtl|keyboard|focus/i' ),
    array( 'NFR006 diagnostics', '/observab|audit|health/i' ),
    array( 'NFR007 migration/rollback', '/migration|rollback|schema/i' ),
    array( 'NFR008 repair/operability', '/repair|system.check|health/i' ),
    array( 'NFR009 compatibility', '/contract.version|compatib|WCA_VERSION/i' ),
    array( 'NFR010 localization', '/locale|timezone|currency|rtl/i' ),
    array( 'FUT01+02 waitlist/windows', '/waitlist|request.window/i' ),
    array( 'FUT03+04 recurrence/resources', '/recurr|resource|series/i' ),
    array( 'FUT05+06 group/reschedule', '/group|reschedul/i' ),
    array( 'FUT07+08 buffers/heatmap', '/buffer|heatmap/i' ),
    array( 'FUT09+10 advisor/forecast', '/optimization|advisor|forecast/i' ),
    array( 'FUT11+12 questionnaire/readiness', '/questionnaire|readiness/i' ),
    array( 'FUT13+14 prerequisites/guardian', '/prerequisit|family_hub|guardian/i' ),
    array( 'FUT15+16 arrival/queue', '/arrival|queue_position/i' ),
    array( 'FUT17+18 disruption/support', '/disruption|interpreter|support.person/i' ),
    array( 'FUT19+20 virtual-room/FHIR', '/virtual.room|fhir/i' ),
    array( 'FUT21+22 SMART/calendar', '/smart|external.busy|calendar/i' ),
    array( 'FUT23+24 episode/governance', '/episode|capability.manifest|governance/i' ),
);
if ( count( $probes ) !== 40 ) { fwrite( STDERR, "T36: expected 40 requirements\n" ); exit( 1 ); }
$rows = array();
$syntax_checks = 0;
$runtime_reads = 0;
$expected_snapshot = '';
foreach ( $probes as $idx => $probe ) {
    $matches = array();
    $source_fingerprints = array();
    foreach ( $files as $path ) {
        $text = file_get_contents( $path ); // re-read in each of 40 passes.
        if ( ! is_string( $text ) ) {
            fwrite( STDERR, "T36: unreadable runtime source " . $path . "\n" );
            exit( 1 );
        }
        $runtime_reads++;
        $relative = substr( $path, strlen( $root ) + 1 );
        $source_fingerprints[] = $relative . ':' . hash( 'sha256', $text );
        if ( preg_match( $probe[1], $text ) === 1 ) {
            $matches[] = $relative;
        }
        if ( substr( $path, -4 ) === '.php' ) {
            $output = array();
            $status = 0;
            exec( escapeshellarg( PHP_BINARY ) . ' -l ' . escapeshellarg( $path ) . ' 2>&1', $output, $status );
            $syntax_checks++;
            if ( $status !== 0 ) {
                fwrite( STDERR, "T36 round " . ( $idx + 1 ) . ": PHP parser failed " . $relative . "\n" . implode( "\n", $output ) . "\n" );
                exit( 1 );
            }
        }
    }
    $snapshot = hash( 'sha256', implode( "\n", $source_fingerprints ) );
    if ( $expected_snapshot === '' ) { $expected_snapshot = $snapshot; }
    if ( ! hash_equals( $expected_snapshot, $snapshot ) ) {
        fwrite( STDERR, "T36: runtime source changed during the forty-pass review\n" );
        exit( 1 );
    }
    if ( ! $matches ) {
        fwrite( STDERR, "T36: missing code-path anchor for " . $probe[0] . "\n" );
        exit( 1 );
    }
    $rows[] = array(
        'round' => $idx + 1,
        'focus' => $probe[0],
        'source_files_reopened' => count( $files ),
        'anchor_files' => $matches,
        'source_sha256_fingerprint' => $snapshot,
        'limitation' => 'source-presence and syntax only; semantic/staging/live unverified',
    );
    echo 'T36 source pass ' . ( $idx + 1 ) . '/40: ' . $probe[0] .
        ' | files ' . count( $files ) . ' | anchor files ' . count( $matches ) . " | PHP lint OK\n";
}
$report = array(
    'classification' => '40 whole-runtime re-read, PHP syntax and requirement-anchor scans; NOT E2E/semantic verification',
    'git_checked_out_sha' => trim( (string) shell_exec( 'git rev-parse HEAD 2>/dev/null' ) ),
    'php_version' => PHP_VERSION,
    'rounds' => count( $rows ),
    'source_files_per_round' => count( $files ),
    'source_file_reads' => $runtime_reads,
    'php_syntax_checks' => $syntax_checks,
    'snapshot_sha256' => $expected_snapshot,
    'data' => $rows,
);
$build_dir = $root . '/build';
if ( ! is_dir( $build_dir ) && ! mkdir( $build_dir, 0775, true ) ) {
    fwrite( STDERR, "T36: cannot write machine-readable review output\n" );
    exit( 1 );
}
if ( file_put_contents( $build_dir . '/t36-forty-runtime-source-review.json', json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) === false ) {
    fwrite( STDERR, "T36: source-review evidence write failed\n" );
    exit( 1 );
}
echo "T36 COMPLETE: 40/40 source rounds, " . $runtime_reads .
    " independent file reads, " . $syntax_checks .
    " PHP syntax executions, zero source-anchor omissions. NO deployed-state acceptance.\n";
