<?php
/**
 * T38 — FUT18 current File00 subject identity and stored active-participant recheck.
 * Pure PHP fixture with real WCA_Future24 stored-payload validator, plus exact
 * source method-order contract for the duplicate reuse path. Not WordPress/MySQL.
 */
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
}
final class WCA_Authorization {
    public static $subject_by_user = array(
        77 => '00000000-0000-4000-8000-000000000077',
        88 => '00000000-0000-4000-8000-000000000088',
    );
    public static function subject_uuid( $id ) {
        return self::$subject_by_user[(int)$id] ?? '';
    }
}
require dirname( __DIR__ ) . '/includes/class-wca-future24.php';
$method = new ReflectionMethod( 'WCA_Future24', 'verified_support_participant_payload' );
$method->setAccessible( true );
$subject = '00000000-0000-4000-8000-000000000077';
$payload = array(
    'subject_uuid' => $subject,
    'role' => 'support',
    'appointment_bound' => true,
    'revocable' => true,
    'clinical_write_authority' => false,
);
$call = static function( $body, $id = 77 ) use ( $method ) {
    return $method->invoke( null, array( 'subject_user_id' => $id, 'payload_json' => $body ) );
};
$count = 0;
$assert = static function( $ok, $label ) use ( &$count ) {
    $count++;
    if ( ! $ok ) {
        fwrite( STDERR, "T38 participant integrity FAIL {$count}: {$label}\n" );
        exit( 1 );
    }
};
$allowed = $call( json_encode( $payload ) );
$assert( is_array( $allowed ) && $allowed['subject_uuid'] === $subject, 'valid canonical subject matches stored user ID' );
$wrong_user = $call( json_encode( $payload ), 88 );
$assert( is_wp_error( $wrong_user ) && 'wca_support_participant_payload_corrupt' === $wrong_user->code,
    'same-shape persisted subject UUID must NOT resolve to a different File00 account' );
$missing_owner = $call( json_encode( $payload ), 99 );
$assert( is_wp_error( $missing_owner ), 'missing File00 authoritative subject must fail closed' );
$role_only_collision = array_merge( $payload, array( 'subject_uuid' => '00000000-0000-4000-8000-000000000088' ) );
$collision = $call( json_encode( $role_only_collision ), 77 );
$assert( is_wp_error( $collision ), 'existing participant same role but hijacked subject must fail closed' );
// If user membership's canonical identity rotates/changes, stored record must not silently replay.
WCA_Authorization::$subject_by_user[77] = '00000000-0000-4000-8000-000000000088';
$changed = $call( json_encode( $payload ) );
$assert( is_wp_error( $changed ), 'revoked/changed File00 subject UUID invalidates stored operational role' );
WCA_Authorization::$subject_by_user[77] = $subject;

// Ensure the existing-row/idempotency branch re-validates entire stored scope,
// not merely matching role text before public_record($existing).
$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-wca-future24.php' );
$start = strpos( $source, 'public static function add_participant(' );
$end = strpos( $source, 'public static function revoke_participant(', $start );
$section = substr( $source, $start, $end - $start );
$existing = strpos( $section, 'if($existing)' );
$verify = strpos( $section, 'verified_support_participant_payload( $existing )' );
$replay = strpos( $section, 'return self::public_record($existing);' );
$assert( $existing !== false && $verify !== false && $replay !== false &&
    $existing < $verify && $verify < $replay,
    'duplicate active participant must verify stored identity/scope before replay success' );
$assert( false !== strpos( $section, '$payload[\'subject_uuid\']' ) && false !== strpos( $section, '$subject' ),
    'duplicate participant replay must also bind stored subject to requested subject UUID' );
echo 'T38 active participant identity and exact replay regressions PASS ' . $count . '/' . $count .
    " (source/runtime-fixture only; no staging/live claim)\n";
