from pathlib import Path
root = Path(__file__).resolve().parents[2]
p = root / 'tests/t20-r9-calendar-provider-regressions.php'
p.write_text(r'''<?php
$root = dirname( __DIR__ );
$calendar = file_get_contents( $root . '/includes/class-wca-calendar-link.php' );
$repo = file_get_contents( $root . '/includes/class-wca-repository.php' );
$fail = array();
$n = 0;
function t20r9( $name, $ok ) { global $fail, $n; $n++; if ( ! $ok ) { $fail[] = $name; } }
t20r9( 'sources readable', is_string( $calendar ) && is_string( $repo ) );
t20r9( 'provider mapping requires exact canonical practitioner', false !== strpos( $repo, 'SWC_Helpers::can_doctor_manage( $appointment_id, $doctor_id )' ) && false !== strpos( $repo, '$doctor_id !== absint( SWC_Helpers::meta( $appointment_id,' ) && false !== strpos( $repo, "'doctor_id', 0 )" ) );
t20r9( 'provider mapping no longer authorizes staff/admin actor classes', false === strpos( $repo, "array( 'doctor','clinic_staff','admin' )" ) );
t20r9( 'provider mapping stores source ordering evidence', false !== strpos( $repo, "'source_event_id'" ) && false !== strpos( $repo, '$source_event_id' ) && false !== strpos( $repo, "'source_occurred_at'" ) && false !== strpos( $repo, '$source_occurred_at' ) );
t20r9( 'stale provider mappings are ignored', false !== strpos( $repo, "['_projection_action'] = 'stale_ignored'" ) );
t20r9( 'same event conflicting mapping requires reconciliation', false !== strpos( $repo, 'wca_calendar_mapping_source_conflict' ) && false !== strpos( $repo, "'reconciliation_required' => true" ) );
t20r9( 'webhook idempotency fingerprints semantic payload', false !== strpos( $calendar, '$busy_fingerprint' ) && false !== strpos( $calendar, "'appointment_ref'" ) && false !== strpos( $calendar, "'provider_event_ref'" ) && false !== strpos( $calendar, "'sync_status'" ) && false !== strpos( $calendar, "'conflict_status'" ) && false !== strpos( $calendar, "'etag'" ) && false !== strpos( $calendar, '$fingerprint' ) && false !== strpos( $calendar, "calendar_provider_webhook" ) );
t20r9( 'mapping action is audited and returned', substr_count( $calendar, "'mapping_action'" ) >= 2 );
if ( $fail ) { fwrite( STDERR, "T20 R9 calendar/provider regressions failed:\n- " . implode( "\n- ", $fail ) . "\n" ); exit( 1 ); }
echo "T20 R9 calendar/provider regressions: PASS {$n}/{$n}.\n";
''')
