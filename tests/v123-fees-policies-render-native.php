<?php
/** V12.3 F08-FR-002 output test: real WCA_Frontend, stubbed WordPress HTML functions. */
error_reporting( E_ALL );
set_error_handler( static function ( $severity, $message ) {
    if ( error_reporting() & $severity ) { throw new ErrorException( $message, 0, $severity ); }
    return false;
} );
defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );
function __( $s, $d = '' ) { return $s; }
function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $s ) { return esc_html( $s ); }
function esc_html_e( $s, $d = '' ) { echo esc_html( $s ); }
function esc_url( $s ) { return esc_attr( $s ); }
function home_url( $s = '' ) { return 'https://example.test' . $s; }
function absint( $s ) { return abs( (int) $s ); }
function is_wp_error( $s ) { return false; }
function is_user_logged_in() { return true; }
function sanitize_text_field( $s ) { return trim( (string) $s ); }
function sanitize_key( $s ) { return strtolower( (string) $s ); }
function wp_unslash( $s ) { return $s; }
function wp_timezone_string() { return 'UTC'; }
function wp_date( $fmt ) { return '2026-11-25'; }
function number_format_i18n( $n, $d ) { return number_format( (float) $n, (int) $d, '.', ',' ); }
function selected( $a, $b ) { if ( (string) $a === (string) $b ) { echo ' selected="selected"'; } }
final class WCA_Service {
    public static function valid_timezone( $z ) { return true; }
    public static function public_clinic_projection( $ref ) {
        return array(
            'public_ref' => 'clinic-001', 'name' => 'Sabri Clinic', 'summary' => 'Public summary',
            'languages' => array( 'Urdu', 'Arabic' ), 'branches' => array(),
            'policies' => array( 'refund' => 'Clinic policy: contact us before any change', 'platform_commission_percent' => 0 ),
            'services' => array(
                array(
                    'public_ref' => 'svc-1', 'practitioner_ref' => 'doctor-1', 'name' => 'Home Visit',
                    'consultation_type' => 'home_visit', 'duration_minutes' => 30,
                    'fee_minor' => 5000, 'fee_max_minor' => 8500, 'currency' => 'PKR',
                    'tax_policy' => 'Tax included', 'refund_policy' => 'Refund within 24 hours',
                    'cancellation_policy' => 'Cancel 24 hours before',
                ),
                array(
                    'public_ref' => 'svc-2', 'practitioner_ref' => 'doctor-1', 'name' => 'Online followup',
                    'consultation_type' => 'online', 'duration_minutes' => 20,
                    'fee_minor' => 2500, 'fee_max_minor' => 0, 'currency' => 'PKR',
                    'tax_policy' => '', 'refund_policy' => '', 'cancellation_policy' => '',
                ),
            ),
        );
    }
}
require dirname( __DIR__ ) . '/includes/class-wca-frontend.php';
function v123_render( $method, $ref ) {
    $m = new ReflectionMethod( 'WCA_Frontend', $method );
    $m->setAccessible( true );
    return $m->invoke( null, $ref );
}
$_GET['service'] = 'svc-1';
$clinic = v123_render( 'clinic', 'clinic-001' );
$booking = v123_render( 'booking', 'clinic-001' );
$checks = array(
    'clinic renders fee minimum' => false !== strpos( $clinic, 'PKR 50.00' ),
    'clinic renders upper fee bound' => false !== strpos( $clinic, 'PKR 85.00' ),
    'clinic reveals service tax policy' => false !== strpos( $clinic, 'Tax included' ),
    'clinic reveals refund policy' => false !== strpos( $clinic, 'Refund within 24 hours' ),
    'clinic reveals cancellation policy' => false !== strpos( $clinic, 'Cancel 24 hours before' ),
    'booking shows service fee range' => false !== strpos( $booking, 'PKR 50.00' ) && false !== strpos( $booking, 'PKR 85.00' ),
    'booking shows service policies' => false !== strpos( $booking, 'Tax included' ) && false !== strpos( $booking, 'Refund within 24 hours' ) && false !== strpos( $booking, 'Cancel 24 hours before' ),
    'home visit is correctly labelled' => false !== strpos( $clinic, 'Home visit' ),
    'platform zero commission disclosed in booking' => false !== strpos( $booking, '0%' ),
    'booking explicitly pending not confirmed' => false !== strpos( $booking, 'This is a request, not a confirmed appointment' ),
);
foreach ( $checks as $name => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "V12.3 FR002 FAIL: {$name}\n" ); exit( 1 ); }
}
echo 'PASS: ' . count( $checks ) . " real PHP frontend fee, policy and appointment-truth output checks.\n";
