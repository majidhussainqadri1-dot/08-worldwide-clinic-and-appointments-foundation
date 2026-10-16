<?php
/**
 * T41 FUT05: execute File08 persisted group-member private scope validator and
 * check application method call ordering on join/idempotent leave/update.
 * Standalone PHP fixtures; not real WordPress/MySQL/guardian deployment.
 */
require __DIR__ . '/bootstrap.php';
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $text ) { return trim( strip_tags( (string) $text ) ); }
}
final class WCA_Authorization {
    public static $subjects = array( 77 => '00000000-0000-4000-8000-000000000077' );
    public static function subject_uuid( $id ) { return self::$subjects[ (int) $id ] ?? ''; }
}
require dirname(__DIR__) . '/includes/class-wca-future24.php';
$validator = new ReflectionMethod( 'WCA_Future24', 'verified_group_member_scope' );
$validator->setAccessible( true );
$session_ref = '00000000-0000-4000-8000-000000001005';
$member_ref = '00000000-0000-4000-8000-000000000077';
$session = array( 'clinic_id' => 51, 'public_ref' => $session_ref );
$payload = array( 'member_ref' => $member_ref, 'guardian_user_id' => 0, 'peer_identity_visible' => false );
$row = array(
    'clinic_id' => 51, 'subject_user_id' => 77, 'parent_ref' => $session_ref,
    'status' => 'group_member', 'payload_json' => json_encode( $payload ),
);
$check = static function( $actual, $label, $should_pass = false ) {
    static $count = 0;
    $count++;
    if ( $should_pass ? $actual !== true : ( ! is_wp_error( $actual ) || $actual->code !== 'wca_group_member_scope_corrupt' ) ) {
        fwrite(STDERR, "T41 FUT05 FAIL {$count} {$label}\n");exit(1);
    }
    return $count;
};
$call = static function($r,$s=null,$id=77,$ref=null)use($validator,$session,$session_ref) {
    return $validator->invoke(null,$r,$s === null ? $session : $s,$id,$ref === null ? $session_ref : $ref);
};
$count=$check($call($row),'valid patient private group-member',true);
foreach(array('group_left','group_cancelled') as $status) {
    $v=$row;$v['status']=$status;
    $count=$check($call($v),'legal idempotent group leave/cancel status '.$status,true);
}
$v=$row;$v['payload_json']=json_encode(array_merge($payload,array('guardian_user_id'=>88)));
$count=$check($call($v),'legitimate guardian reference can remain numeric',true);
$v=$row;$v['payload_json']='{broken json';
$count=$check($call($v),'malformed stored JSON');
$v=$row;$v['payload_json']='[]';
$count=$check($call($v),'array root instead of object');
$v=$row;$v['payload_json']=json_encode(array_merge($payload,array('member_ref'=>'00000000-0000-4000-8000-000000000088')));
$count=$check($call($v),'stored File00 subject belongs to different patient');
$v=$row;$v['payload_json']=json_encode(array_merge($payload,array('peer_identity_visible'=>true)));
$count=$check($call($v),'privacy visibility privilege escalation');
$v=$row;$v['payload_json']=json_encode(array_diff_key($payload,array('peer_identity_visible'=>1)));
$count=$check($call($v),'privacy flag absent');
$v=$row;$v['payload_json']=json_encode(array_merge($payload,array('guardian_user_id'=>'88')));
$count=$check($call($v),'persisted guardian ID has wrong type');
$v=$row;$v['payload_json']=json_encode(array_merge($payload,array('guardian_user_id'=>-2)));
$count=$check($call($v),'persisted negative guardian ID');
$v=$row;$v['clinic_id']=52;
$count=$check($call($v),'stored member clinic changed');
$v=$row;$v['subject_user_id']=88;
$count=$check($call($v),'stored member patient ID changed');
$v=$row;$v['parent_ref']='00000000-0000-4000-8000-000000001006';
$count=$check($call($v),'stored group session parent changed');
$v=$row;$v['status']='group_open';
$count=$check($call($v),'member record has invalid state');
$count=$check($call($row,array('clinic_id'=>0)),'session clinic has no canonical owner');
$count=$check($call($row,$session,88),'current File00 subject missing for request actor');
WCA_Authorization::$subjects[77]='00000000-0000-4000-8000-000000000088';
$count=$check($call($row),'current File00 canonical member identity changed');
WCA_Authorization::$subjects[77]=$member_ref;
$source=file_get_contents(dirname(__DIR__).'/includes/class-wca-future24.php');
$from=strpos($source,'public static function join_group_session(');
$until=strpos($source,'public static function leave_group_session(',$from);
$join=substr($source,$from,$until-$from);
$read=strpos($join,'if ( $existing_member )');
$guard=strpos($join,'verified_group_member_scope( $existing_member');
$return=strpos($join,'return self::public_record( $existing_member );');
$count=$check($read!==false&&$guard!==false&&$return!==false&&$read<$guard&&$guard<$return?true:new WP_Error('wca_group_member_scope_corrupt'),'join replay guard before success',true);
$from=$until;$until=strpos($source,'public static function cancel_group_session(',$from);
$leave=substr($source,$from,$until-$from);
$read=strpos($leave,'if ( ! $member )');
$guard=strpos($leave,'verified_group_member_scope( $member');
$return=strpos($leave,"in_array( (string) \$member['status'], array( 'group_left', 'group_cancelled' )");
$update=strpos($leave,'$wpdb->update(');
$count=$check($read!==false&&$guard!==false&&$return!==false&&$update!==false&&$read<$guard&&$guard<$return&&$guard<$update?true:new WP_Error('wca_group_member_scope_corrupt'),'group leave validates identity and privacy before no-op or SQL',true);
echo "T41 FUT05 persisted group scope native fixtures PASS {$count}/{$count} (unit fixtures, no deployed claim)\n";
