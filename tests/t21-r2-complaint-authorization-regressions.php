<?php
$root = dirname(__DIR__);
$service = file_get_contents($root . '/includes/class-wca-service.php');
$rest = file_get_contents($root . '/includes/class-wca-rest.php');
$fail = static function($m){ fwrite(STDERR, "T21 R2 regression failed: {$m}\n"); exit(1); };
if (strpos($service, 'complaint_projection( $ref, $actor_user_id = 0, $purpose = \'\' )') === false) $fail('complaint projection lacks explicit purpose');
if (strpos($service, 'user_can( $actor_user_id, \'manage_worldwide_clinic\' )') === false) $fail('complaint admin authority is not bound to explicit actor');
if (strpos($service, 'current_user_can(\'manage_worldwide_clinic\')') !== false) $fail('ambient current-user complaint authority remains');
if (strpos($service, 'allowed_purposes = array( \'complaint\', \'support_case\', \'privacy_request\', \'incident\' )') === false) $fail('complaint purpose allowlist missing');
if (strpos($service, 'require_step_up( \'complaint_\' . $purpose, $actor_user_id )') === false) $fail('complaint step-up missing');
if (strpos($service, 'ComplaintAccessed.v1') === false || strpos($service, 'wca_complaint_access_audit_failed') === false) $fail('complaint access audit fail-closed contract missing');
if (strpos($rest, 'X-WCA-Access-Purpose') === false || strpos($rest, 'complaint_projection($request[\'ref\'],get_current_user_id(),$purpose)') === false) $fail('REST purpose is not propagated');
echo "T21 R2 complaint authorization regressions passed.\n";
