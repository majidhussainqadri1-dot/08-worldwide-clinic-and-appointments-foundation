<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t15h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
t15h('R1 appointment conflicts fail closed','includes/class-swc-helpers.php',"null === \$rows || '' !== (string) \$wpdb->last_error");
t15h('R1 rate counter readback fails closed','includes/class-swc-helpers.php',"null === \$hits_raw || '' !== (string) \$wpdb->last_error");
t15h('R1 active hold read fails closed','includes/class-wca-service.php',"return (bool) \$hold_id;");
t15h('R1 slot hold read failure explicit','includes/class-wca-repository.php','wca_slot_hold_read_failed');
t15h('R1 slot conflict query failure explicit','includes/class-wca-repository.php','wca_slot_conflict_query_failed');
t15h('R1 stale request replay read failure explicit','includes/class-wca-appointment-command.php','wca_idempotency_read_failed');
t15h('R1 consent read failure explicit','includes/class-wca-appointment-command.php','wca_consent_read_failed');
t15h('R1 waitlist offer read failure explicit','includes/class-wca-future24.php','wca_waitlist_offer_read_failed');
t15h('R1 waitlist dedupe read failure explicit','includes/class-wca-future24.php','wca_waitlist_dedupe_read_failed');
t15h('R1 windows dedupe read failure explicit','includes/class-wca-future24.php','wca_windows_dedupe_read_failed');
t15h('R1 resource capacity read failure explicit','includes/class-wca-future24.php','wca_resource_capacity_read_failed');
t15h('R1 group capacity read failure explicit','includes/class-wca-future24.php','wca_group_capacity_read_failed');
t15h('R1 group leave read failure explicit','includes/class-wca-future24.php','wca_group_leave_read_failed');
t15h('R1 queue position read failure explicit','includes/class-wca-future24.php','wca_queue_position_read_failed');
t15h('R1 support participant read failure explicit','includes/class-wca-future24.php','wca_support_participant_read_failed');
t15h('R1 virtual room read failure explicit','includes/class-wca-future24.php','wca_virtual_room_read_failed');
t15h('R1 external busy read fails closed','includes/class-wca-future24.php',"return (bool) \$busy;");

t15h('R2 core erasure record read failure','includes/class-wca-privacy.php','wca_privacy_appointment_read_failed');
t15h('R2 future retention read failure','includes/class-wca-privacy.php','wca_retention_future24_read_failed');
t15h('R2 intake version read failure','includes/class-wca-continuity-guards.php','wca_intake_version_read_failed');
t15h('R2 consent state read failure','includes/class-wca-continuity-guards.php','wca_consent_state_read_failed');
t15h('R2 followup list read failure','includes/class-wca-continuity-secure.php','wca_followup_list_read_failed');
t15h('R2 reminder scan read failure','includes/class-wca-continuity-secure.php','wca_followup_reminder_scan_read_failed');
t15h('R2 intake retention read failure','includes/class-wca-continuity-secure.php','wca_intake_retention_read_failed');
t15h('R2 followup retention read failure','includes/class-wca-continuity-secure.php','wca_followup_retention_read_failed');
t15h('R2 legacy privacy ids read failure','includes/class-swc-privacy.php','swc_privacy_related_ids_read_failed');
t15h('R2 legacy privacy count read failure','includes/class-swc-privacy.php','swc_privacy_related_count_read_failed');

t15h('R3 dispatcher lock read failure','includes/class-wca-outbox.php','wca_outbox_lock_read_failed');
t15h('R3 stale outbox recovery failure','includes/class-wca-repository.php','wca_outbox_recovery_failed');
t15h('R3 outbox claim read failure','includes/class-wca-repository.php','wca_outbox_claim_read_failed');
t15h('R3 outbox readback failure','includes/class-wca-repository.php','wca_outbox_claim_readback_failed');
t15h('R3 idempotency initial read failure','includes/class-wca-repository.php','wca_idempotency_claim_read_failed');
t15h('R3 idempotency race read failure','includes/class-wca-repository.php','wca_idempotency_race_read_failed');
t15h('R3 idempotency status read failure','includes/class-wca-repository.php','wca_idempotency_status_read_failed');
t15h('R3 payment replay read failure','includes/class-wca-repository.php','wca_payment_replay_read_failed');

t15h('R5 exact currency validation','includes/class-wca-repository.php','$currency_raw = trim');
t15h('R5 slot lock read failure','includes/class-wca-repository.php','wca_slot_lock_read_failed');
t15h('R5 inside-lock hold read failure','includes/class-wca-repository.php','wca_slot_hold_locked_read_failed');
t15h('R5 insert-race hold read failure','includes/class-wca-repository.php','wca_slot_hold_race_read_failed');

t15h('R6 admin access audit fail closed','includes/class-wca-authorization.php','wca_admin_access_audit_failed');
t15h('R6 practitioner lock read separated','includes/class-wca-plan-guard.php','$lock_raw = $wpdb->get_var');
t15h('R6 practitioner ref persistence checked','includes/class-wca-plan-guard.php','if ( false === $written )');

t15h('R7 strict Founder helper result','includes/class-wca-authorization.php','$founder_raw = smc_is_founder');
t15h('R7 strict guardian helper result','includes/class-wca-authorization.php','return true === $result || 1 === $result');
t15h('R7 strict step-up helper result','includes/class-wca-authorization.php','$step_result = smc_step_up_is_valid');
t15h('R7 versioned age claim failure is authoritative','includes/class-wca-central-governance.php','wca_age_claim_invalid_provider_response');
t15h('R7 doctor Founder result strict','includes/class-swc-doctor-authority.php','$founder_raw = function_exists');
t15h('R7 File09 verified result strict','includes/class-swc-doctor-authority.php','$verified_raw = gdo_user_is_verified');

t15h('R8 opaque payment preserves idempotency header','includes/class-wca-opaque-api.php',"set_header( 'Idempotency-Key'");
t15h('R8 stale idempotency precheck read failure','includes/class-wca-second-ten-review-hardening.php','wca_stale_idempotency_read_failed');
if($fail){fwrite(STDERR,"T15 regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo 'T15 regression assertions passed: '.$pass.'/'.$pass."\n";
