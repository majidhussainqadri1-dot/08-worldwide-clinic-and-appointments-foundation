<?php
/** File08 must explicitly degrade when no File00 reverse subject contract is installed. */
require __DIR__ . '/bootstrap.php';
if(!function_exists('sanitize_text_field')){function sanitize_text_field($v){return trim(strip_tags((string)$v));}}
require dirname(__DIR__).'/includes/class-wca-future24.php';
if(function_exists('smc_get_user_id_by_subject_uuid')){fwrite(STDERR,"Fixture polluted with provider\n");exit(1);}
$method=new ReflectionMethod('WCA_Future24','subject_user_id');
$method->setAccessible(true);
$id=$method->invoke(null,'00000000-0000-4000-8000-000000000077');
if($id!==0){fwrite(STDERR,"T38 File00 missing-provider FAIL: guessed identity\n");exit(1);}
echo "T38 absent File00 provider degrades to no-identity safely: PASS 1/1\n";
