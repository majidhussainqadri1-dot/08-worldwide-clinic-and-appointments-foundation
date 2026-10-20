<?php
/**
 * File08-owned proposed aggregate shape for F14-FR-011 / #70.
 * THIS DOES NOT COUNT BOOKINGS, TRACK SENSITIVE REST TRAFFIC, OR
 * AUTHORIZE FILE14 TO RECEIVE HEALTH/BOOKING INFORMATION.
 * A valid shape proves neither opt-in nor ability to erase a contribution.
 */
defined( 'ABSPATH' ) || exit;
final class WCA_File14_Optin_Booking_Aggregate_V1 {
    const CONTRACT = 'wca.file14.optin-booking-cohort.v1';
    const MIN_COHORT = 10;
    public static function validate( $input ) {
        $fail = array( 'shape_valid' => false, 'publishable' => false,
            'count' => null, 'rate' => null, 'owner_consent_attested' => false,
            'reason' => 'booking_aggregate_unavailable' );
        if ( ! is_array( $input ) ) { return $fail; }
        // Closed schema. Never pass public_ref, appointment_id, patient/doctor
        // or clinical information to an aggregate consumer.
        $keys = array( 'contract','owner','stage','day_utc','optin_count',
            'minimum_cohort','purpose','gpc_excluded','minor_excluded',
            'consent_revocable','erasure_reconcilable','producer_source' );
        $actual = array_keys( $input );
        sort( $actual, SORT_STRING );
        sort( $keys, SORT_STRING );
        if ( $actual !== $keys ) { $fail['reason'] = 'unknown_or_sensitive_fields'; return $fail; }
        if ( self::CONTRACT !== $input['contract']
            || 'file08' !== $input['owner']
            || 'booking_started' !== $input['stage']
            || 'conversion_analytics' !== $input['purpose']
            || true !== $input['gpc_excluded']
            || true !== $input['minor_excluded']
            || true !== $input['consent_revocable']
            || true !== $input['erasure_reconcilable']
            || self::MIN_COHORT !== $input['minimum_cohort'] ) {
            $fail['reason'] = 'privacy_or_owner_attestation_missing'; return $fail;
        }
        $d = $input['day_utc'];
        if ( ! is_string( $d ) || ! preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/D', $d ) ) { return $fail; }
        $parts = explode( '-', $d );
        if ( ! checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] ) ) { return $fail; }
        $v = $input['optin_count'];
        if ( ! is_int( $v ) || $v < self::MIN_COHORT || $v > 100000000 ) {
            $fail['reason'] = 'cohort_not_publishable'; return $fail;
        }
        if ( ! is_string( $input['producer_source'] )
            || ! preg_match( '/^[a-f0-9]{40}$/D', $input['producer_source'] ) ) { return $fail; }
        // A caller-supplied hash/count/privacy flag is not File08 evidence.
        // Requires real owner-backend, deduped consent and erasure provenance.
        return array( 'shape_valid' => true, 'publishable' => false,
            'count' => null, 'rate' => null, 'owner_consent_attested' => false,
            'reason' => 'native_optin_provenance_and_erasure_ledger_unverified' );
    }

    public static function accepted() { return false; }
}
