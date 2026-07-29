<?php
defined( 'ABSPATH' ) || exit;
final class SWC_Plugin {
	public function run(){(new SWC_Appointments())->hooks();(new SWC_Frontend())->hooks();(new SWC_Admin())->hooks();(new SWC_Privacy())->hooks();add_action('wp_enqueue_scripts',array($this,'assets'));add_action('admin_enqueue_scripts',array($this,'admin_assets'));add_action('template_redirect',array($this,'private_headers'));add_filter('wp_robots',array($this,'robots'));}
	public function assets(){global $post;$m=SWC_Helpers::pages();$needed=$post instanceof WP_Post&&(in_array($post->ID,array_map('absint',$m),true)||false!==strpos($post->post_content,'[swc_'));if(!$needed){return;}wp_enqueue_style('swc-clinic',SWC_URL.'assets/css/clinic.css',array(),SWC_VERSION);wp_enqueue_script('swc-clinic',SWC_URL.'assets/js/clinic.js',array(),SWC_VERSION,true);}
	public function admin_assets($hook){if(false!==strpos($hook,'clinic-management')||false!==strpos($hook,'clinic-settings')){wp_enqueue_style('swc-admin',SWC_URL.'assets/css/admin.css',array(),SWC_VERSION);}}
	public function private_headers(){$m=SWC_Helpers::pages();foreach(array('request','patient','doctor','availability') as $key){if(!empty($m[$key])&&is_page($m[$key])){nocache_headers();return;}}}
	public function robots($robots){$m=SWC_Helpers::pages();foreach(array('request','patient','doctor','availability') as $key){if(!empty($m[$key])&&is_page($m[$key])){$robots['noindex']=true;$robots['noarchive']=true;}}return $robots;}
}
