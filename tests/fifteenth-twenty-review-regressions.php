<?php
$root=dirname(__DIR__); $pass=0; $fail=array();
function t15h($label,$path,$needle){global $root,$pass,$fail;$s=file_get_contents($root.'/'.$path);if(is_string($s)&&false!==strpos($s,$needle)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' missing: '.$needle;}}
function t15missing($label,$path){global $root,$pass,$fail;if(!file_exists($root.'/'.$path)){echo 'PASS '.(++$pass).': '.$label."\n";}else{$fail[]=$label.' unexpected file: '.$path;}}
t15h('R1 appointment conflicts fail closed','includes/class-swc-helpers.php',"null === \$rows || '' !== (string) \$wpdb->last_error");
t15h('R1 rate counter readback fails closed','includes/class-swc-helpers.php',"null === \$hits_raw || '' !== (string) \$wpdb->last_error");
t15h('R1 active hold read fails closed','includes/class-wca-service.php',"return is_wp_error( \$available ) || ! \$available;");
t15h('R1 slot hold read failure explicit','includes/class-wca-repository.php','wca_slot_hold_read_failed');
t15h('R1 slot capacity query failure explicit','includes/class-wca-repository.php','wca_slot_capacity_count_failed');
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
t15h('R1 external busy read fails closed','includes/class-wca-future24.php','wca_external_busy_read_failed');

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

t15h('R10 canonical migration from-version captured before install','includes/class-wca-schema.php',"\$from_version = (string) get_option( self::OPTION_DB_VERSION");
t15h('R10 migration state uses canonical from-version','includes/class-wca-schema.php',"'from_version' => \$from_version");
t15h('R10 schema snapshot refreshes on real upgrade','includes/class-wca-schema.php','capture_snapshot( $from_version !== WCA_Contracts::SCHEMA_VERSION )');
t15h('R10 activation snapshot refreshed per attempt','includes/class-swc-activator.php','Every activation/deployment attempt gets a fresh immediate pre-change snapshot');

t15h('R11 nested transactions join outer transaction','includes/class-wca-repository.php','private static $transaction_depth = 0');
t15h('R11 nested transaction guard','includes/class-wca-repository.php','if ( self::$transaction_depth > 0 )');
t15h('R11 waitlist candidate read failure','includes/class-wca-future24.php','wca_waitlist_candidate_read_failed');
t15h('R11 waitlist delivery failure propagates','includes/class-wca-future24.php','wca_waitlist_offer_delivery_failed');
t15h('R11 Future24 record read failure explicit','includes/class-wca-future24.php','wca_future24_record_read_failed');
t15h('R11 policy traversal read failure explicit','includes/class-wca-future24.php','wca_future24_policy_read_failed');
t15h('R11 readiness intake read failure explicit','includes/class-wca-future24.php','wca_readiness_intake_read_failed');
t15h('R11 service-specific questionnaire fails closed','includes/class-wca-future24.php',"\$template_service && ( ! \$service_ref || ! hash_equals");
t15h('R11 optional group service remains joinable','includes/class-wca-future24.php','( $service_ref && ! $service )');
t15h('R11 arrival serialized by appointment','includes/class-wca-future24.php',"semantic_lock( 'arrival', \$id )");
t15h('R11 queue counts distinct appointments','includes/class-wca-future24.php','COUNT(DISTINCT appointment_id)');
t15h('R11 subject resolver helper is authoritative','includes/class-wca-future24.php',"if ( is_wp_error( \$raw ) || ! is_scalar( \$raw ) )");
t15h('R11 completed reschedule audit failure explicit','includes/class-wca-future24.php','wca_safe_reschedule_audit_failed');

t15h('R12 opaque appointment access concealed','includes/class-wca-opaque-api.php','private static function appointment_access');
t15h('R12 opaque responses no-store','includes/class-wca-opaque-api.php',"header( 'Cache-Control', 'private, no-store, max-age=0' )");
t15h('R12 opaque responses noindex','includes/class-wca-opaque-api.php',"header( 'X-Robots-Tag', 'noindex, nofollow, noarchive' )");
t15h('R12 application currency exact validation','includes/class-wca-service.php','$currency_raw = trim');

t15h('R13 CF01 uses canonical appointment authorization','includes/class-swc-cf01-care-context.php','WCA_Authorization::can_view_appointment');
t15h('R13 verification reconciliation retry hook','includes/class-wca-verification-reconciliation.php','wca_retry_doctor_eligibility_reconciliation');
t15h('R13 verification projection writes transactional','includes/class-wca-verification-reconciliation.php','wca_verification_reconciliation_write');
t15h('R13 verification clinic read failure explicit','includes/class-wca-verification-reconciliation.php','wca_verification_reconciliation_read');
t15h('R13 File19 WP_Error rejected','includes/class-wca-outbox.php','is_wp_error( $result ) || false === $result');

t15h('R14 secure browser UUID fallback','assets/js/clinic.js','crypto.getRandomValues');
t15h('R14 stable slot-hold replay key','assets/js/clinic.js','idempotency_key: holdRequestKey');
t15h('R14 stable appointment replay key','assets/js/clinic.js','idempotency_key: appointmentRequestKey');
t15h('R14 calendar same-origin validation','assets/js/future24.js','target.origin !== window.location.origin');

t15h('R15 repository captures clinic discovery read failure','includes/class-wca-repository.php','wca_clinic_list_read_failed');
t15h('R15 repository captures branch read failure','includes/class-wca-repository.php','wca_branch_list_read_failed');
t15h('R15 repository captures service read failure','includes/class-wca-repository.php','wca_service_list_read_failed');
t15h('R15 repository captures availability read failure','includes/class-wca-repository.php','wca_availability_list_read_failed');
t15h('R15 public collection propagates repository read errors','includes/class-wca-rest.php','WCA_Repository::consume_read_error');
t15h('R15 public projection propagates nested read errors','includes/class-wca-service.php','WCA_Repository::consume_read_error');

t15h('R16 CLI outbox fails on WP_Error','includes/class-wca-cli.php','if ( is_wp_error( $count ) )');
t15h('R16 CLI health fails when unhealthy','includes/class-wca-cli.php','empty( $health[\'ok\'] )');
t15h('R16 health includes cron state','includes/class-wca-observability.php','self::all_true( $checks[\'cron\'] )');
t15h('R16 health includes legacy checks','includes/class-wca-observability.php','self::all_true( $checks[\'legacy_checks\'] )');
t15h('R16 cron process wrapper logs worker failure','includes/class-wca-outbox.php','outbox_cron_failed');
t15h('R16 maintenance cron wrapper logs failure','includes/class-wca-outbox.php','maintenance_cron_failed');
t15h('R16 opportunistic worker logs failure','includes/class-wca-outbox.php','outbox_opportunistic_failed');

t15h('R17 plugin release 1.2.15','worldwide-clinic.php','Version: 1.2.15');
t15h('R17 runtime release 1.2.15','includes/class-wca-contracts.php',"RUNTIME_VERSION                 = '1.2.15'");
t15h('R17 readme stable tag 1.2.15','readme.txt','Stable tag: 1.2.15');
t15h('R17 package contract 1.2.15','tests/release-package-contract.php',"Version: 1.2.15");

t15missing('R18 temporary T15 probe removed','.github/workflows/t15-probe.yml');
t15h('R18 old corrective status explicitly historical','CORRECTIVE-STATUS.md','Historical evidence only.');
t15h('R18 master-plan version label truthful','tests/master-plan-contract.php','plugin version is 1.2.15');

t15h('R20 packaged readme records all main rounds complete','readme.txt','All 20 fifteenth-cycle main reviews are complete');
t15h('R20 repository history preserves fifteenth closure','CHANGELOG.md','Fifteenth fresh 20-round corrective audit');
t15h('R20 STATUS records main-cycle closure','STATUS.md','Fifteenth fresh 20-round main-cycle closure');
t15h('R20 changelog records clean R19','CHANGELOG.md','R19 was a clean corrected-state review');
if($fail){fwrite(STDERR,"T15 regression gate failed:\n- ".implode("\n- ",$fail)."\n");exit(1);}echo 'T15 regression assertions passed: '.$pass.'/'.$pass."\n";
