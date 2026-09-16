<?php
$root = dirname(__DIR__);
$front = file_get_contents($root . '/includes/class-wca-frontend.php');
$plugin = file_get_contents($root . '/includes/class-wca-plugin.php');
$js = file_get_contents($root . '/assets/js/clinic.js');
$checks = array(
    'server booking timezone falls back to UTC if invalid' => strpos($front, 'if ( ! WCA_Service::valid_timezone( $default_timezone ) ) { $default_timezone = ' . chr(39) . 'UTC' . chr(39) . '; }') !== false,
    'booking field uses validated default timezone' => strpos($front, 'esc_attr( $default_timezone )') !== false,
    'localized runtime timezone is validated' => strpos($plugin, 'if ( ! WCA_Service::valid_timezone( $runtime_timezone ) ) { $runtime_timezone = ' . chr(39) . 'UTC' . chr(39) . '; }') !== false,
    'browser timezone preferred for patient-local scheduling' => strpos($js, 'var browserTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone') !== false && strpos($js, 'if (browserTimezone) {') !== false && strpos($js, 'tz.value = browserTimezone;') !== false,
    'old UTC-only browser override removed' => strpos($js, "(!tz.value || tz.value === 'UTC')") === false,
);
foreach ($checks as $name=>$ok) { if (!$ok) { fwrite(STDERR,"T18 R3 FAIL: {$name}\n"); exit(1); } }
echo "T18 R3 booking timezone regressions: PASS\n";
