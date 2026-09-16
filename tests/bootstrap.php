<?php
if ( ! defined( 'ABSPATH' ) ) { define( 'ABSPATH', __DIR__ . '/' ); }
if ( ! function_exists( 'sanitize_key' ) ) { function sanitize_key( $key ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ); } }
if ( ! function_exists( '__' ) ) { function __( $text ) { return $text; } }
if ( ! function_exists( 'absint' ) ) { function absint( $value ) { return abs( (int) $value ); } }
if ( ! class_exists( 'WP_Error' ) ) { class WP_Error { public function __construct( $code = '', $message = '', $data = null ) { $this->code=$code; $this->message=$message; $this->data=$data; } } }
if ( ! function_exists( 'is_wp_error' ) ) { function is_wp_error( $thing ) { return $thing instanceof WP_Error; } }
function wca_test_assert( $condition, $message ) {
	static $count = 0;
	$count++;
	if ( ! $condition ) { fwrite( STDERR, "FAIL {$count}: {$message}\n" ); exit( 1 ); }
	echo "PASS {$count}: {$message}\n";
}
