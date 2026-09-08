<?php
$root=dirname(__DIR__);
$w=file_get_contents($root.'/includes/class-wca-privacy.php');
$l=file_get_contents($root.'/includes/class-swc-privacy.php');
$d=chr(36);
$checks=array(
 'appointment erasure is relationship-aware'=>strpos($w,$d.'is_patient =')!==false && strpos($w,$d.'is_guardian =')!==false && strpos($w,$d.'is_doctor =')!==false,
 'patient fields use patient-scoped delete operation'=>strpos($w,'wca_privacy_patient_meta_delete')!==false,
 'doctor private note uses doctor-scoped delete operation'=>strpos($w,'wca_privacy_doctor_meta_delete')!==false,
 'future payload scrub receives requester id'=>strpos($w,'scrub_future24_payload( '.$d.'payload, '.$d.'user_id, '.$d.'subject_uuid )')!==false,
 'future numeric identifiers are requester-value scoped'=>strpos($w,'absint( '.$d.'item ) === '.$d.'user_id')!==false,
 'legacy unconditional future identity strip removed'=>strpos($w,"array( 'subject_uuid','patient_user_id','guardian_user_id','recipient_user_id' )")===false,
 'WCA privacy clears DB stale errors'=>substr_count($w,$d."wpdb->last_error = '';")>=8,
 'SWC privacy clears DB stale errors'=>substr_count($l,$d."wpdb->last_error = '';")>=3,
);
foreach($checks as $n=>$ok){if(!$ok){fwrite(STDERR,"T18 R9 FAIL: {$n}\n");exit(1);}}
echo "T18 R9 privacy subject-boundary regressions: PASS\n";
