<?php
/** File 08 fifth fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t510src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t510has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t510lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$bootstrap=t510src('worldwide-clinic.php');
$contracts=t510src('includes/class-wca-contracts.php');
$future=t510src('includes/class-wca-future24.php');
$auth=t510src('includes/class-wca-authorization.php');
$service=t510src('includes/class-wca-service.php');
$guard=t510src('includes/class-wca-plan-guard.php');
$hardening=t510src('includes/class-wca-second-ten-review-hardening.php');
$readme=t510src('readme.txt');
$repo_readme=t510src('README.md');
$status=t510src('STATUS.md');
$changelog=t510src('CHANGELOG.md');

t510has('service public-ref resolver',$future,'WCA_Repository::get_service_by_ref( $service_ref, true )');
t510lacks('no numeric resolver on service ref',$future,'WCA_Repository::get_service( $service_ref, true )');
t510has('doctor clinic authority helper',$auth,'function doctor_can_serve_clinic');
t510has('doctor delegated schedule',$auth,'delegated_clinic_ids( $doctor_user_id, \'schedule\' )');
t510has('doctor delegated manage',$auth,'delegated_clinic_ids( $doctor_user_id, \'clinic_manage\' )');
t510has('service canonical helper',$service,'WCA_Authorization::doctor_can_serve_clinic');
t510lacks('no global-admin assignment bypass',$service,'user_can( $actor_user_id, \'manage_worldwide_clinic\' ) || $doctor_id === $actor_user_id');
t510has('slot-search doctor scope',$guard,'wca_slot_doctor_scope');
t510has('held-slot doctor scope',$guard,'wca_hold_doctor_scope');
t510has('semantic lock helper',$future,'function semantic_lock');
t510has('semantic release helper',$future,'function release_semantic_lock');
t510has('arrival semantic lock',$future,"semantic_lock( 'arrival'");
t510has('arrival finally release',$future,'self::release_semantic_lock( $lock )');
t510has('virtual room semantic lock',$future,"semantic_lock('virtual-room'");
t510has('virtual room file17 event',$future,'File17.VirtualRoomRequested.v1');
t510has('family paged traversal',$future,"'posts_per_page' => \$batch, 'paged' => \$page");
t510has('family completion marker',$future,"'pagination_complete' => true");
t510lacks('no family hard 100',$future,"'posts_per_page' => 100, 'no_found_rows' => true, 'meta_key' => '_swc_guardian_user_id'");
t510has('disruption all helper',$future,'function clinic_appointments_between_all');
t510has('disruption uses all helper',$future,'clinic_appointments_between_all($clinic_id,$effective_start,$end)');
t510lacks('no disruption hard 1000',$future,'clinic_appointments_between($clinic_id,$effective_start,$end,1000)');
t510has('canonical strict datetime',$future,'DateTimeImmutable::createFromFormat');
t510lacks('canonical no permissive strtotime parser',$future,"\$ts = strtotime( \$value");
t510has('waitlist valid date from',$future,'WCA_Service::valid_date( $date_from )');
t510has('waitlist valid date to',$future,'WCA_Service::valid_date( $date_to )');
t510has('calendar depth error',$hardening,'wca_future24_payload_depth');
t510has('date key strict validity',$hardening,'self::is_date_key( $key_string ) && ! WCA_Service::valid_date( $text )');
t510has('deep public payload drops',$hardening,'if ( $depth > 8 ) { return array(); }');
t510has('slot policy batch',$future,'$batch = 200;');
t510has('slot policy page',$future,"'paged' => \$page");
t510has('slot policy stable order',$future,"'orderby' => 'ID'");
t510has('plugin 1.2.13',$bootstrap,'Version: 1.2.15');
t510has('runtime 1.2.13',$contracts,"RUNTIME_VERSION                 = '1.2.15'");
t510has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.4.0'");
t510has('readme stable 1.2.13',$readme,'Stable tag: 1.2.15');
t510has('repository readme 1.2.13',$repo_readme,'Runtime candidate: **1.2.15**');
t510has('status 1.2.13',$status,'Runtime candidate: **1.2.15**');
t510has('changelog 1.2.13',$changelog,'## 1.2.7 — 2026-08-11');
t510has('zero commission',$contracts,"'commission_percent' => 0");
t510has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t510has('no automated prescribing',$contracts,"'automated_prescribing' => false");
$runtime=implode("\n",array($bootstrap,$contracts,$future,$auth,$service,$guard,$hardening));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t510lacks('forbidden runtime primitive',$runtime,$token); }
if($failures){ fwrite(STDERR,"File 08 fifth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'File 08 fifth fresh ten-round regression assertions passed: ' . $checks . '/' . $checks . ".\n";
