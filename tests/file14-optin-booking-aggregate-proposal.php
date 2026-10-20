<?php
/** Forty File08 aggregate proposal shape checks; never count real patients. */
define('ABSPATH', __DIR__);
require __DIR__.'/../includes/class-wca-file14-optin-booking-aggregate-v1.php';
$good=array('contract'=>WCA_File14_Optin_Booking_Aggregate_V1::CONTRACT,
 'owner'=>'file08','stage'=>'appointment_requested','day_utc'=>'2026-10-20',
 'optin_count'=>10,'distinct_consented_subjects'=>10,'minimum_cohort'=>10,'purpose'=>'conversion_analytics',
 'gpc_excluded'=>true,'minor_excluded'=>true,'consent_revocable'=>true,
 'erasure_reconcilable'=>true,'producer_source'=>str_repeat('a',40));
$cases=array();
foreach(array(10,12,25) as $n){$c=$good;$c['optin_count']=$n;$cases[]=array($c,true);}
for($i=0;$i<37;$i++){
 $c=$good;
 $c['producer_source']=str_pad(dechex($i+1),40,'b');
 switch($i%22){
 case 0:$c['contract']='invalid';break;
 case 1:$c['owner']='file14';break;
 case 2:$c['stage']='booking_completed';break;
 case 3:$c['day_utc']='2026-02-30';break;
 case 4:$c['day_utc']='2026-10-20T00:00:00';break;
 case 5:$c['optin_count']=9;break;
 case 6:$c['optin_count']='11';break;
 case 7:$c['minimum_cohort']=9;break;
 case 8:$c['gpc_excluded']=false;break;
 case 9:$c['gpc_excluded']='true';break;
 case 10:$c['minor_excluded']=false;break;
 case 11:$c['consent_revocable']=false;break;
 case 12:$c['erasure_reconcilable']=false;break;
 case 13:$c['purpose']='treatment';break;
 case 14:$c['producer_source']='HEAD';break;
 case 15:$c['patient_user_id']=13;break;
 case 16:$c['appointment_public_ref']='secret';break;
 case 17:$c['clinical_note']='private';break;
 case 18:unset($c['day_utc']);break;
 case 19:$c['optin_count']=90;$c['distinct_consented_subjects']=9;break;
 case 20:$c['distinct_consented_subjects']=11;break;
 case 21:$c['distinct_consented_subjects']='10';break;
 }
 $cases[]=array($c,false);
}
if(count($cases)!==40)exit(2);
foreach($cases as $i=>$a){
 $v=WCA_File14_Optin_Booking_Aggregate_V1::validate($a[0]);
 if(!is_array($v)||$v['shape_valid']!==$a[1]
    ||$v['publishable']!==false||$v['owner_consent_attested']!==false
    ||$v['count']!==null||$v['rate']!==null){
    fwrite(STDERR,'File08 aggregate proposal case '.($i+1).' failed '.var_export($v,true).PHP_EOL);
    exit(1);
 }
 echo 'File08 booking aggregate schema '.sprintf('%02d',$i+1).'/40 PASS'.PHP_EOL;
}
if(WCA_File14_Optin_Booking_Aggregate_V1::accepted())exit(1);
echo 'File08 40/40 aggregate schema tests PASS; native opt-in aggregate and erasure lifecycle NOT IMPLEMENTED'.PHP_EOL;
