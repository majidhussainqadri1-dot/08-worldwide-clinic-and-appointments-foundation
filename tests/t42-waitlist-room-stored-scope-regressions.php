<?php
/**
 * T42 — real private FUT01/FUT19 validator calls and source ordering.
 * Unit fixtures on 7.4/8.3, not real WordPress/MySQL or File17 integration.
 */
require __DIR__ . '/bootstrap.php';
if ( ! defined( 'HOUR_IN_SECONDS' ) ) { define( 'HOUR_IN_SECONDS', 3600 ); }
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) { define( 'MINUTE_IN_SECONDS', 60 ); }
final class SWC_Helpers {
    public static function meta($id,$key,$default=0) { return $key==='clinic_id'?51:$default; }
}
final class WCA_Authorization {
    public static $allowed=true;
    public static function can_view_appointment($appointment_id,$subject_id) {
        return self::$allowed && (int)$appointment_id===301 && (int)$subject_id===77;
    }
}
require dirname(__DIR__).'/includes/class-wca-future24.php';
$w=new ReflectionMethod('WCA_Future24','verified_waitlist_replay_scope');
$w->setAccessible(true);
$v=new ReflectionMethod('WCA_Future24','verified_virtual_room_replay_scope');
$v->setAccessible(true);
$counter=0;
$assert=static function($condition,$message)use(&$counter){
    $counter++;
    if(!$condition){fwrite(STDERR,"T42 native replay FAIL {$counter}: {$message}\n");exit(1);}
};
$fingerprint=str_repeat('a',64);
$patient=array('patient_user_id'=>77,'guardian_user_id'=>88);
$wait_payload=array('service_ref'=>'service-public-ref','date_from'=>'2026-10-20','date_to'=>'2026-10-21',
    'timezone'=>'Asia/Karachi','guardian_user_id'=>88,'auto_book'=>false);
$wait=array('clinic_id'=>51,'subject_user_id'=>77,'status'=>'waiting','parent_ref'=>$fingerprint,
    'expires_at'=>gmdate('Y-m-d H:i:s',time()+86400),'payload_json'=>json_encode($wait_payload));
$checkWait=static function($row,$context=null)use($w,$patient,$fingerprint) {
    return $w->invoke(null,$row,51,$context===null?$patient:$context,
        $fingerprint,'service-public-ref','2026-10-20','2026-10-21','Asia/Karachi');
};
$assert($checkWait($wait)===true,'valid waitlist replay remains safe');
$makeBad=static function($result,$label)use($assert) {
    $assert(is_wp_error($result)&&$result->code==='wca_waitlist_replay_scope_corrupt',$label);
};
$x=$wait;$x['payload_json']='{malformed';$makeBad($checkWait($x),'broken waitlist JSON rejected');
$x=$wait;$x['payload_json']='[]';$makeBad($checkWait($x),'array root cannot stand in for object');
$x=$wait;$x['payload_json']=json_encode(array_merge($wait_payload,array('auto_book'=>true)));$makeBad($checkWait($x),'silent auto-book consent escalation rejected');
$x=$wait;$p=$wait_payload;unset($p['auto_book']);$x['payload_json']=json_encode($p);$makeBad($checkWait($x),'missing explicit no-auto-book proof rejected');
$x=$wait;$x['payload_json']=json_encode(array_merge($wait_payload,array('guardian_user_id'=>99)));$makeBad($checkWait($x),'reassigned guardian cannot replay prior intent');
$x=$wait;$x['payload_json']=json_encode(array_merge($wait_payload,array('timezone'=>'UTC')));$makeBad($checkWait($x),'modified timezone violates original waitlist fingerprint scope');
$x=$wait;$x['payload_json']=json_encode(array_merge($wait_payload,array('service_ref'=>'other-service')));$makeBad($checkWait($x),'service mismatch rejected');
$x=$wait;$x['payload_json']=json_encode(array_merge($wait_payload,array('date_to'=>'2026-10-30')));$makeBad($checkWait($x),'date window mismatch rejected');
$x=$wait;$x['subject_user_id']=90;$makeBad($checkWait($x),'wrong patient identity rejected');
$x=$wait;$x['clinic_id']=52;$makeBad($checkWait($x),'wrong clinic rejected');
$x=$wait;$x['parent_ref']=str_repeat('b',64);$makeBad($checkWait($x),'wrong fingerprint rejected');
$x=$wait;$x['expires_at']=gmdate('Y-m-d H:i:s',time()-60);$makeBad($checkWait($x),'expired replay rejected');
$x=$wait;$makeBad($checkWait($x,array('patient_user_id'=>77,'guardian_user_id'=>99)),'current guardian differs from original enrollment');
$ref='00000000-0000-4000-8000-000000000301';
$created=gmdate('Y-m-d H:i:s',time()-90);
$expires=gmdate('Y-m-d H:i:s',time()+3510);
$room_payload=array('appointment_ref'=>$ref,'transport_owner'=>'File17',
 'recording_assumed'=>false,'teleconsult_consent_verified'=>true,'idempotent_request'=>true);
$room=array('appointment_id'=>301,'clinic_id'=>51,'subject_user_id'=>77,'status'=>'room_requested',
 'created_at'=>$created,'expires_at'=>$expires,'payload_json'=>json_encode($room_payload));
$checkRoom=static function($row)use($v,$ref){return $v->invoke(null,$row,301,$ref);};
$assert($checkRoom($room)===true,'valid File17 room request can replay');
$makeRoomBad=static function($row,$label)use($checkRoom,$assert) {
    $y=$checkRoom($row);
    $assert(is_wp_error($y)&&$y->code==='wca_virtual_room_replay_scope_corrupt',$label);
};
$x=$room;$x['payload_json']='[]';$makeRoomBad($x,'room payload must be JSON object');
$x=$room;$x['payload_json']='{';$makeRoomBad($x,'invalid room JSON cannot replay');
$x=$room;$x['payload_json']=json_encode(array_merge($room_payload,array('appointment_ref'=>'00000000-0000-4000-8000-000000000399')));$makeRoomBad($x,'room appointment public ref mismatch');
$x=$room;$x['payload_json']=json_encode(array_merge($room_payload,array('transport_owner'=>'File08')));$makeRoomBad($x,'room cannot assign File08 as transport owner');
$x=$room;$x['payload_json']=json_encode(array_merge($room_payload,array('recording_assumed'=>true)));$makeRoomBad($x,'room cannot assume recording');
$x=$room;$x['payload_json']=json_encode(array_merge($room_payload,array('teleconsult_consent_verified'=>false)));$makeRoomBad($x,'unverified historical consent rejected');
$x=$room;$x['payload_json']=json_encode(array_merge($room_payload,array('idempotent_request'=>false)));$makeRoomBad($x,'non-idempotent room request rejected');
$x=$room;$x['appointment_id']=302;$makeRoomBad($x,'room appointment identity differs');
$x=$room;$x['clinic_id']=52;$makeRoomBad($x,'old clinic room request cannot replay');
$x=$room;$x['subject_user_id']=99;$makeRoomBad($x,'old requester no longer belongs to appointment');
$x=$room;$x['status']='room_cancelled';$makeRoomBad($x,'room state mismatch');
$x=$room;$x['expires_at']=gmdate('Y-m-d H:i:s',time()+7200);$makeRoomBad($x,'oversized original room TTL rejected');
$x=$room;$x['expires_at']=gmdate('Y-m-d H:i:s',time()-30);$makeRoomBad($x,'expired File17 request rejected');
WCA_Authorization::$allowed=false;$makeRoomBad($room,'original requester appointment access revoked');WCA_Authorization::$allowed=true;
$src=file_get_contents(dirname(__DIR__).'/includes/class-wca-future24.php');
$from=strpos($src,'public static function join_waitlist(');$to=strpos($src,'/* FUT-02 */',$from);
$part=substr($src,$from,$to-$from);
$read=strpos($part,'if ( $existing )');$guard=strpos($part,'verified_waitlist_replay_scope( $existing');$replay=strpos($part,'return self::public_record( $existing );');
$assert($read!==false&&$guard!==false&&$replay!==false&&$read<$guard&&$guard<$replay,'waitlist scope guarded before replay');
$from=strpos($src,'public static function request_virtual_room(');$to=strpos($src,'private static function subject_user_id(',$from);
$part=substr($src,$from,$to-$from);
$read=strpos($part,'if($existing)');$guard=strpos($part,'verified_virtual_room_replay_scope( $existing');$replay=strpos($part,'return self::public_record($existing);');
$assert($read!==false&&$guard!==false&&$replay!==false&&$read<$guard&&$guard<$replay,'virtual room scope guarded before replay');
echo "T42 FUT01/FUT19 native stored-scope regressions PASS {$counter}/{$counter} (PHP unit fixtures only)\n";
