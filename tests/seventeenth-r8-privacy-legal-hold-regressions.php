<?php
$root = dirname( __DIR__ );
$wca = file_get_contents( $root . '/includes/class-wca-privacy.php' );
$swc = file_get_contents( $root . '/includes/class-swc-privacy.php' );
$continuity = file_get_contents( $root . '/includes/class-wca-continuity-secure.php' );
$checks = array(
    'canonical hold preserves native true after filters' => false !== strpos( $wca, 'return $native || $filtered;' ),
    'Future24 hold is monotonic restrictive' => substr_count( $wca, 'return $native || $filtered;' ) >= 2,
    'legacy eraser checks canonical appointment legal hold' => false !== strpos( $swc, "WCA_Privacy::legal_hold( \$appointment_id )" ),
    'legacy eraser uses monotonic cursor rather than destructive offset paging' => false !== strpos( $swc, 'related_ids_after( $user->ID, $cursor' ),
    'legacy held rows advance the cursor without mutation' => false !== strpos( $swc, '$last_id = max( $last_id, absint( $appointment_id ) );' ),
    'legacy eraser reports unchanged legal-hold retention' => false !== strpos( $swc, 'retained unchanged under an active legal hold' ),
    'continuity hold inherits appointment hold' => false !== strpos( $continuity, 'WCA_Privacy::legal_hold( $appointment_id )' ),
    'continuity hold cannot be weakened by extension filter' => false !== strpos( $continuity, 'return $native || $filtered;' ),
    'continuity guardian erasure has its own cursor' => false !== strpos( $continuity, "\$base . '_guardian'" ),
    'continuity guardian rows are legal-hold checked before unlink' => false !== strpos( $continuity, "self::legal_hold( 'intake', \$guardian_row )" ),
    'continuity guardian unlink is row-scoped' => false !== strpos( $continuity, "array( 'id' => \$row_id, 'guardian_user_id' => \$user_id )" ),
    'legacy blanket guardian unlink removed' => false === strpos( $continuity, "array( 'guardian_user_id' => \$user_id ), array( '%d' ), array( '%d' )" ),
);
$pass = 0;
foreach ( $checks as $label => $ok ) {
    if ( ! $ok ) { fwrite( STDERR, "[FAIL] {$label}\n" ); exit( 1 ); }
    echo "[PASS] {$label}\n";
    $pass++;
}
echo "R8 privacy/legal-hold assertions: {$pass}/" . count( $checks ) . " PASS\n";
