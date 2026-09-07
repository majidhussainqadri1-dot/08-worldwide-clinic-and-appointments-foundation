from pathlib import Path
root = Path(__file__).resolve().parents[2]
p = root / 'tests/t20-r9-calendar-provider-regressions.php'
t = p.read_text()
t = t.replace(
    "false !== strpos( $repo, \"$doctor_id !== absint( SWC_Helpers::meta( $appointment_id, 'doctor_id', 0 ) )\" )",
    "false !== strpos( $repo, '$doctor_id !== absint( SWC_Helpers::meta( $appointment_id, \\'doctor_id\\', 0 ) )' )"
)
t = t.replace(
    "false !== strpos( $repo, \"'source_event_id' => $source_event_id\" ) && false !== strpos( $repo, \"'source_occurred_at' => $source_occurred_at\" )",
    "false !== strpos( $repo, \\"'source_event_id' => $source_event_id\\" ) && false !== strpos( $repo, \\"'source_occurred_at' => $source_occurred_at\\" )"
)
# Replace the preceding escaped-double-quote variant with interpolation-safe single-quoted literals.
t = t.replace(
    'false !== strpos( $repo, "\'source_event_id\' => $source_event_id" ) && false !== strpos( $repo, "\'source_occurred_at\' => $source_occurred_at" )',
    "false !== strpos( $repo, '\\'source_event_id\\' => $source_event_id' ) && false !== strpos( $repo, '\\'source_occurred_at\\' => $source_occurred_at' )"
)
t = t.replace(
    "false !== strpos( $calendar, \"claim_idempotency( 'calendar_provider_webhook', $provider . ':' . $event_id, 0, $fingerprint )\" )",
    "false !== strpos( $calendar, 'claim_idempotency( \\'calendar_provider_webhook\\', $provider . \\':\\' . $event_id, 0, $fingerprint )' )"
)
t = t.replace(
    "substr_count( $calendar, \"'mapping_action' => $mapping_action\" ) >= 2",
    "substr_count( $calendar, \\"'mapping_action' => $mapping_action\\" ) >= 2"
)
t = t.replace(
    'substr_count( $calendar, "\'mapping_action\' => $mapping_action" ) >= 2',
    "substr_count( $calendar, '\\'mapping_action\\' => $mapping_action' ) >= 2"
)
p.write_text(t)
