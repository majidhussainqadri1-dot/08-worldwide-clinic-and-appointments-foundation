<?php
/** T38: execute File08's File00-owned reverse subject resolver and forward parity check. */
require __DIR__ . '/bootstrap.php';
if(!function_exists('sanitize_text_field')) {
    function sanitize_text_field($v){return trim(strip_tags((string)$v));}
}
$GLOBALS['subject_provider_value']=77;
$GLOBALS['subject_provider_throw']=false;
function smc_get_user_id_by_subject_uuid($subject){
    if($GLOBALS['subject_provider_throw']) { throw new RuntimeException('simulated owner failure'); }
    return $GLOBALS['subject_provider_value'];
}
final class WCA_Authorization {
    public static $canonical=array(
      77=>'00000000-0000-4000-8000-000000000077',
      88=>'00000000-0000-4000-8000-000000000088'
    );
    public static function subject_uuid($id){return self::$canonical[$id]??'';}
}
require dirname(__DIR__) . '/includes/class-wca-future24.php';
$method=new ReflectionMethod('WCA_Future24','subject_user_id');
$method->setAccessible(true);
$subject='00000000-0000-4000-8000-000000000077';
$call=static function($uuid)use($method){return $method->invoke(null,$uuid);};
$count=0;
$check=static function($yes,$text)use(&$count){$count++;if(!$yes){fwrite(STDERR,"T38 File00 consumer FAIL: ".$text."\n");exit(1);}};
$check($call($subject)===77,'canonical File00 reverse and forward subject match');
$GLOBALS['subject_provider_value']=88;
$check($call($subject)===0,'provider returned wrong File00 user is rejected');
$GLOBALS['subject_provider_value']='77';
$check($call($subject)===0,'insecure numeric-string identity is rejected');
$GLOBALS['subject_provider_value']=array(77);
$check($call($subject)===0,'non-scalar contract identity is rejected');
$GLOBALS['subject_provider_value']=new WP_Error('backend_unavailable','unknown');
$check($call($subject)===0,'WP_Error contract is rejected');
$GLOBALS['subject_provider_value']=77;
$GLOBALS['subject_provider_throw']=true;
$check($call($subject)===0,'owner exception is fail closed');
$GLOBALS['subject_provider_throw']=false;
$check($call('00000000-0000-4000-8000-000000000000')===0,'syntactically valid but unmapped subject is rejected');
$check($call('bad')===0,'malformed UUID is rejected');
$check($call('00000000-0000-4000-8000-000000000077 ')===77,'sanitized harmless whitespace is normalized');
$src=file_get_contents(dirname(__DIR__).'/includes/class-wca-future24.php');
$begin=strpos($src,'private static function subject_user_id(');
$end=strpos($src,'/* FUT-20 */',$begin);
$section=substr($src,$begin,$end-$begin);
$check($begin!==false && strpos($section,"'_smc_subject_uuid'")===false &&
    strpos($section,"get_users(")===false,
    'File08 cannot directly query File00-owned usermeta or use incorrect legacy alias');
echo "T38 File00 reverse/forward identity contract consumer PASS {$count}/{$count} (PHP unit fixtures only)\n";
