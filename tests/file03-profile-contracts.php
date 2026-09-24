<?php
$adapter=file_get_contents(dirname(__DIR__).'/includes/class-wca-file03-adapter.php');
$query=file_get_contents(dirname(__DIR__).'/includes/class-wca-query-api.php');
$main=file_get_contents(dirname(__DIR__).'/worldwide-clinic.php');
foreach(['sabri_file08_public_clinic_projection_v1','sabri_file08_profile_reviews_projection_v1','sabri_file08_register_profile_delegation_provider','delegate_can_schedule'] as $t){if(strpos($adapter,$t)===false){fwrite(STDERR,"Missing File03 adapter token: $t\n");exit(1);}}
if(strpos($main,"WCA_File03_Adapter::register_hooks();")===false){fwrite(STDERR,"File03 adapter not registered early\n");exit(1);}
if(strpos($query,'$profile_delegate_scope')===false||strpos($query,'! $profile_delegate_scope')===false){fwrite(STDERR,"File03 schedule delegation not revalidated in query\n");exit(1);}
if(strpos($adapter,"'items' => array()")===false){fwrite(STDERR,"Truthful empty review projection guard missing\n");exit(1);}
echo "File08 -> File03 clinic/delegation contracts: PASS\n";
