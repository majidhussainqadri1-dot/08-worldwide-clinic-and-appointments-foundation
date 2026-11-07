<?php
/**
 * F08 NFR startup: plugins_loaded runs before WordPress init. Calling
 * add_rewrite_rule then dereferences a null $wp_rewrite. Only the runtime
 * WCA_Plugin::boot() -> WCA_Routes::hooks() init flow may register routes.
 * This is a bounded source invariant; real WP7.0.1 CI checks startup logs.
 */
$source=file_get_contents(dirname(__DIR__).'/includes/class-swc-activator.php');
$runtime=file_get_contents(dirname(__DIR__).'/includes/class-wca-plugin.php');
$routes=file_get_contents(dirname(__DIR__).'/includes/class-wca-routes.php');
$start=strpos($source,'public static function maybe_upgrade()');
$end=strpos($source,'public static function register_type()',$start);
if(false===$start||false===$end||$end<=$start){fwrite(STDERR,"FAIL: cannot locate actual upgrade method\n");exit(1);}
$method=substr($source,$start,$end-$start);
if(strpos($method,'WCA_Routes::register();')!==false){fwrite(STDERR,"FAIL: pre-init upgrade registers rewrite rules before wp_rewrite exists\n");exit(1);}
if(strpos($runtime,'WCA_Routes::hooks();')===false||strpos($routes,"add_action( 'init', array( __CLASS__, 'register' ), 12 )")===false){fwrite(STDERR,"FAIL: canonical init-time route registration not retained\n");exit(1);}
echo "PASS File08 rewrite registration only on WordPress init, not plugins_loaded migration\n";
