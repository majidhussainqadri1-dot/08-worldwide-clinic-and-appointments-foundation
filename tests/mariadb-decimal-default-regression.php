<?php
/**
 * Real MariaDB 11.4 fail-first regression: a DECIMAL(24,6) DEFAULT 0 reads
 * back as 0.000000 in SHOW FULL COLUMNS. Never accept a different number.
 * Minimal bounded wpdb metadata fixture; separate seven-plugin real MariaDB CI.
 */
define( 'ABSPATH', __DIR__ . '/' );
class WP_Error {
  private $code; private $message; private $data;
  public function __construct($code,$message,$data=array()){$this->code=$code;$this->message=$message;$this->data=$data;}
  public function get_error_code(){return $this->code;}
  public function get_error_data(){return $this->data;}
  public function get_error_message(){return $this->message;}
}
function is_wp_error($x){return $x instanceof WP_Error;}
function __($x,$domain=''){return $x;}
function sanitize_key($x){return strtolower((string)$x);}
function sanitize_text_field($x){return (string)$x;}
function esc_sql($x){return (string)$x;}
define('ARRAY_A','ARRAY_A');
class WCA_Test_WPDB {
 public $last_error='';
 public $default='0.000000';
 public $type='decimal(24,6)';
 public function get_results($sql,$mode){if(strpos($sql,'SHOW FULL COLUMNS')!==false){return array(array('Field'=>'sum_value','Type'=>$this->type,'Null'=>'NO','Default'=>$this->default,'Extra'=>''));}return array();}
}
$GLOBALS['wpdb']=new WCA_Test_WPDB();
require dirname(__DIR__).'/includes/class-wca-schema.php';
function verify_test($ok,$m){if(!$ok){fwrite(STDERR,"FAIL: {$m}\n");exit(1);}}
$sql="CREATE TABLE wp_wca_metrics (\n sum_value decimal(24,6) NOT NULL DEFAULT 0\n) DEFAULT CHARSET=utf8mb4;";
verify_test(true===WCA_Schema::verify_definition_sql($sql),'MariaDB padded DECIMAL default 0.000000 must match SQL DEFAULT 0');
$wpdb->default='0.000001';
$invalid=WCA_Schema::verify_definition_sql($sql);
verify_test(is_wp_error($invalid) && $invalid->get_error_code()==='wca_schema_column_default_mismatch','nonzero DECIMAL default must still fail');
$wpdb->default='abc';
verify_test(is_wp_error(WCA_Schema::verify_definition_sql($sql)),'nonnumeric default must fail');
$wpdb->default='0.000000';
$wpdb->type='varchar(191)';
verify_test(is_wp_error(WCA_Schema::verify_definition_sql($sql)),'wrong physical column type must fail');
echo "PASS File08 decimal schema default safe normalization and fail-closed deviations\n";
