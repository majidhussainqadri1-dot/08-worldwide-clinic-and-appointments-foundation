<?php
$root = dirname(__DIR__);
$service = file_get_contents($root . '/includes/class-wca-service.php');
$fail = static function($m){ fwrite(STDERR, "T21 R3 regression failed: {$m}\n"); exit(1); };
if (strpos($service, 'state_uncertain') === false) $fail('uncertain transaction state is not detected');
if (strpos($service, 'if ( ! $state_uncertain ) {') === false) $fail('idempotency release is not conditioned on verified rollback state');
if (strpos($service, 'appointment_request_uncertain_idempotency_retained_total') === false) $fail('uncertain-state retention observability is missing');
if (strpos($service, 'appointment_request_transaction_state_uncertain') === false) $fail('uncertain-state diagnostic log is missing');
if (!preg_match('~if\s*\(\s*!\s*\$state_uncertain\s*\)\s*\{\s*WCA_Repository::release_idempotency\s*\(\s*\$claim\[\'id\'\]\s*\)~s', $service)) $fail('release is not guarded by state certainty');
echo "T21 R3 uncertain-idempotency regressions passed.\n";
