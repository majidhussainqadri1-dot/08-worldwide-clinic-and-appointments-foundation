<?php
/**
 * T33 native FUT13 mixed-policy, duplicates and no-silent-bypass regression.
 * Reuses real T32 WordPress/DB stub and loaded actual WCA_Future24, not E2E.
 */
require __DIR__ . '/t32-future24-policy-persistence-and-read-integrity.php';
$check_count = 0;
$t33 = static function( $ok, $why ) use ( &$check_count ) {
    if ( ! $ok ) { fwrite( STDERR, 'T33 FAIL: ' . $why . "\n" ); exit( 1 ); }
    $check_count++;
};
$db = $GLOBALS['wpdb'];
$block_rule = array(
    'type' => 'document', 'label' => 'Identity check',
    'key' => 'document:Identity check', 'missing_behavior' => 'block'
);
$provisional_rule = array_merge( $block_rule, array( 'missing_behavior' => 'provisional' ) );
$other_rule = array(
    'type' => 'document', 'label' => 'Another paper',
    'key' => 'document:Another paper', 'missing_behavior' => 'provisional'
);
$payload = static function( $requirements, $service = '' ) {
    return json_encode( array(
        'service_ref' => $service,
        'requirements' => $requirements,
        'blocking_is_explicit' => true,
    ) );
};
$scan = new ReflectionMethod( 'WCA_Future24', 'prerequisite_state' );
$scan->setAccessible( true );
$validate = new ReflectionMethod( 'WCA_Future24', 'verified_prerequisite_rules' );
$validate->setAccessible( true );
$policy_row = static function( $id, $requirements, $service = '' ) use ( $payload ) {
    return array( 'id' => (string) $id, 'payload_json' => $payload( $requirements, $service ) );
};
$read = static function( $rows ) use ( $db, $scan ) {
    $db->reads = 0; $db->batches = array( $rows );
    return $scan->invoke( null, 99 );
};
// D01 rejects two simultaneous rules with the SAME normalized key
// even when their missing behavior differs; never calls DB insert.
$writes_before = count( $db->writes );
$res = WCA_Future24::save_prerequisites( array(
    'clinic_id' => 3,
    'requirements' => array(
        array( 'type' => 'document', 'label' => 'Identity check', 'missing_behavior' => 'block' ),
        array( 'type' => 'document', 'label' => 'Identity check', 'missing_behavior' => 'provisional' ),
    ),
), 77 );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_rule_duplicate',
    'request allowed duplicate rule key with contrary blocking state' );
$t33( count( $db->writes ) === $writes_before, 'duplicate request reached Future24 DB write' );
$res = WCA_Future24::save_prerequisites( array(
    'clinic_id' => 3,
    'requirements' => array(
        array( 'type' => 'document', 'label' => '<b>Identity check</b>', 'missing_behavior' => 'block' ),
        array( 'type' => 'document', 'label' => 'Identity check', 'missing_behavior' => 'block' ),
    ),
), 77 );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_rule_duplicate',
    'two differently formatted raw rules with identical sanitized key were allowed' );
$t33( count( $db->writes ) === $writes_before, 'normalized duplicate persisted' );
// Harden canonical private serialized writer validator, not just public entry.
$res = $validate->invoke( null, array( $block_rule, $provisional_rule ) );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_rules_corrupt',
    'canonical FUT13 serializer allowed repeated key' );
$res = $validate->invoke( null, array( $block_rule, $other_rule ) );
$t33( is_array( $res ) && count( $res ) === 2, 'distinct well-formed rules were denied' );
// D02 historical policy contains duplicate same key: fail closed.
$res = $read( array( $policy_row( 10, array( $block_rule, $provisional_rule ) ) ) );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_policy_duplicate',
    'historical single policy duplicate downgraded a required document' );
$t33( $db->reads === 1, 'duplicate policy accidentally retried in database loop' );
$res = $read( array( $policy_row( 10, array(
    'document:Identity check', 'document:Identity check'
) ) ) );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_policy_duplicate',
    'legacy duplicate string policy was silently accepted' );
$res = $read( array( $policy_row( 10, array( 'document:Identity check', $block_rule ) ) ) );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_policy_duplicate',
    'legacy plus structured duplicate policy went unnoticed' );
// D03 two different active records disagree. Do not secretly choose
// oldest OR newest, because no cross-record override rule was approved.
$res = $read( array(
    $policy_row( 10, array( $provisional_rule ) ),
    $policy_row( 11, array( $block_rule ) )
) );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_policy_conflict',
    'older provisional silently overrode newer blocking rule' );
$res = $read( array(
    $policy_row( 10, array( $block_rule ) ),
    $policy_row( 11, array( $provisional_rule ) )
) );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_policy_conflict',
    'newer provisional silently overrode older block without owner-approved version rule' );
// Existing policies with identical behavior are compatible; unrelated
// rules remain independent, missing blocking evidence stays blocking.
$res = $read( array(
    $policy_row( 10, array( $block_rule ) ),
    $policy_row( 11, array( $block_rule, $other_rule ) )
) );
$t33( is_array( $res ) && false === $res['complete'],
    'matching block across two policy records now incorrectly becomes ready' );
$t33( $res['blocking_count'] === 1 && $res['required_count'] === 2,
    'shared rules counted twice or unrelated requirement was lost' );
$t33( $res['missing_requirements'] === array( 'document:Identity check' ),
    'missing blocking evidence silently forgiven' );
$t33( $res['provisional_missing_requirements'] === array( 'document:Another paper' ),
    'optional evidence misclassified as blocking' );
$res = $read( array( $policy_row( 10, array( $provisional_rule ) ) ) );
$t33( is_array( $res ) && true === $res['complete'],
    'single nonblocking provisional policy unexpected behavior' );
// Invalid stored service scope is not silently interpreted as global rule.
$db->reads = 0;
$db->batches = array( array( array(
    'id' => '9',
    'payload_json' => '{"service_ref":[],"requirements":[{"type":"document","label":"Identity check","key":"document:Identity check","missing_behavior":"block"}]}',
) ) );
$res = $scan->invoke( null, 99 );
$t33( is_wp_error( $res ) && $res->code === 'wca_prerequisite_policy_corrupt',
    'invalid historical service scope silently became global default' );
echo 'T33 real FUT13 duplicate and active-policy conflict regressions: PASS ' .
    $check_count . '/' . $check_count . "\n";
