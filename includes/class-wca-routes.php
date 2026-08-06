<?php
/**
 * Canonical pretty routes owned by File 08.
 *
 * @package Worldwide_Clinic_Appointments
 */

defined( 'ABSPATH' ) || exit;

final class WCA_Routes {
	public static function hooks() {
		add_action( 'init', array( __CLASS__, 'register' ), 12 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'template_include', array( __CLASS__, 'template' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'headers' ), 1 );
		add_filter( 'document_title_parts', array( __CLASS__, 'title' ) );
	}

	public static function register() {
		add_rewrite_rule( '^clinic/([^/]+)/?$', 'index.php?wca_route=clinic&wca_ref=$matches[1]', 'top' );
		add_rewrite_rule( '^appointments/book/([^/]+)/?$', 'index.php?wca_route=book&wca_ref=$matches[1]', 'top' );
		add_rewrite_rule( '^appointments/?$', 'index.php?wca_route=appointments', 'top' );
		add_rewrite_rule( '^clinic/dashboard/?$', 'index.php?wca_route=dashboard', 'top' );
		add_rewrite_rule( '^appointment/([^/]+)/?$', 'index.php?wca_route=appointment&wca_ref=$matches[1]', 'top' );
	}

	public static function query_vars( $vars ) {
		$vars[] = 'wca_route';
		$vars[] = 'wca_ref';
		return array_unique( $vars );
	}

	public static function route() {
		return sanitize_key( (string) get_query_var( 'wca_route' ) );
	}

	public static function ref() {
		return sanitize_text_field( (string) get_query_var( 'wca_ref' ) );
	}

	public static function template( $template ) {
		if ( ! self::route() ) { return $template; }
		$file = WCA_DIR . 'templates/route.php';
		return file_exists( $file ) ? $file : $template;
	}

	public static function headers() {
		$route = self::route();
		if ( ! $route ) { return; }
		if ( 'clinic' === $route ) {
			header( 'Cache-Control: public, max-age=300, stale-while-revalidate=60', true );
			return;
		}
		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}
		if ( ! defined( 'DONOTCACHEPAGE' ) ) { define( 'DONOTCACHEPAGE', true ); }
		do_action( 'litespeed_control_set_nocache', 'File 08 protected route' );
		nocache_headers();
		header( 'Cache-Control: private, no-store, no-cache, max-age=0, must-revalidate', true );
		header( 'X-Robots-Tag: noindex, nofollow, noarchive, nosnippet', true );
		header( 'Referrer-Policy: same-origin', true );
		header( 'Permissions-Policy: camera=(), microphone=(), geolocation=()', true );
	}

	public static function title( $parts ) {
		$route = self::route();
		$map = array(
			'clinic'       => __( 'Clinic', 'worldwide-clinic-appointments' ),
			'book'         => __( 'Book an appointment', 'worldwide-clinic-appointments' ),
			'appointments' => __( 'My appointments', 'worldwide-clinic-appointments' ),
			'dashboard'    => __( 'Clinic dashboard', 'worldwide-clinic-appointments' ),
			'appointment'  => __( 'Appointment', 'worldwide-clinic-appointments' ),
		);
		if ( isset( $map[ $route ] ) ) { $parts['title'] = $map[ $route ]; }
		return $parts;
	}
}
