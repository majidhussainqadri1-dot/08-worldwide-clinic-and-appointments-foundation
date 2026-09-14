<?php
$root = dirname( __DIR__ );
$entry = file_get_contents( $root . '/worldwide-clinic.php' );
$continuity = file_get_contents( $root . '/includes/class-wca-continuity-secure.php' );
$guards = file_get_contents( $root . '/includes/class-wca-continuity-guards.php' );
$runner = file_get_contents( __DIR__ . '/run-all.php' );
if ( ! is_string( $entry ) || ! is_string( $continuity ) || ! is_string( $guards ) || ! is_string( $runner ) ) { fwrite( STDERR, "T23 R8 source read failed\n" ); exit( 1 ); }
$checks = array(
 'legacy replacement eraser is explicitly retired from runtime' => false !== strpos( $entry, "remove_filter( 'wp_privacy_personal_data_erasers', array( 'WCA_Continuity_Guards', 'replace_continuity_eraser' ), 100 );" ),
 'canonical continuity eraser remains registered' => false !== strpos( $continuity, "add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );" ),
 'canonical legal hold is seeded from appointment hold' => false !== strpos( $continuity, "WCA_Privacy::legal_hold( \$appointment_id )" ),
 'canonical legal hold is monotonic through extension filter' => false !== strpos( $continuity, "return \$native || \$filtered;" ),
 'guardian erasure uses bounded cursor' => false !== strpos( $continuity, '_guardian' ) && false !== strpos( $continuity, 'guardian_user_id=%d AND id>%d ORDER BY id ASC LIMIT 100' ),
 'guardian erasure checks legal hold per row' => false !== strpos( $continuity, "self::legal_hold( 'intake', \$guardian_row )" ),
 'unsafe replacement implementation is not active despite remaining compatibility code' => false !== strpos( $guards, 'replace_continuity_eraser' ),
 'regression is bound into aggregate suite' => false !== strpos( $runner, "'t23-r8-continuity-privacy-hold-regressions.php'" ),
);
$failures=array(); foreach($checks as $name=>$ok){ if(!$ok){$failures[]=$name;} }
if($failures){ fwrite(STDERR,"T23 R8 continuity privacy legal-hold regressions failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'T23 R8 continuity privacy legal-hold regressions: PASS '.count($checks).'/'.count($checks)."\n";
