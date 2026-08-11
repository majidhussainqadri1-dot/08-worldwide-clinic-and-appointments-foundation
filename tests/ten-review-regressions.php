<?php
/** File 08 ten-round post-closure corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function r10src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function r10has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function r10lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
function r10true( $label,$condition ) { global $failures,$checks; $checks++; if(!$condition){ $failures[]=$label; } }

$bootstrap=r10src('worldwide-clinic.php');
$hardening=r10src('includes/class-wca-ten-review-hardening.php');
$frontend=r10src('includes/class-wca-frontend.php');
$plan=r10src('includes/class-wca-plan-guard.php');
$contracts=r10src('includes/class-wca-contracts.php');
$js=r10src('assets/js/clinic.js');
$readme=r10src('readme.txt');
$repo_readme=r10src('README.md');
$status=r10src('STATUS.md');
$staging=r10src('STAGING-ACCEPTANCE.md');
$changelog=r10src('CHANGELOG.md');

// R1 canonical route + legacy browser migration + delegated staff visibility.
r10has('bootstrap hardening include',$bootstrap,'class-wca-ten-review-hardening.php');
r10has('bootstrap hardening boot',$bootstrap,'WCA_Ten_Review_Hardening::boot()');
r10has('canonical detail link',$frontend,"home_url( '/appointment/'");
r10lacks('plural modern detail link',$frontend,"home_url( '/appointments/' . rawurlencode( strtolower( \$ref ) ) . '/' )");
r10has('opaque plural compatibility redirect',$hardening,"^appointments/([0-9a-fA-F-]{36})");
r10has('legacy browser filter',$hardening,'wca_allow_legacy_numeric_browser_actions');
r10has('legacy mutation block',$hardening,'swc_submit_appointment');
r10has('legacy shortcode replacement',$hardening,"remove_shortcode( 'swc_request_appointment' )");
r10has('delegated appointment list',$frontend,"delegated_clinic_ids( \$user_id, 'appointments' )");
r10has('delegated clinic dashboard',$frontend,"delegated_clinic_ids( \$user_id, 'clinic_manage' )");
r10has('delegated dashboard object recheck',$frontend,'WCA_Authorization::can_manage_clinic');

// R2-R5 replay/rate/precondition/payment/doctor-clinic scope.
r10has('core mutation guard',$hardening,'is_core_mutation_route');
r10has('mutation rate limit',$hardening,'SWC_Helpers::rate_limit_hit');
r10has('explicit idempotency required',$hardening,'wca_idempotency_required');
r10has('full request body fingerprint',$hardening,"'body'  => \$fingerprint_body");
r10has('URL fingerprint',$hardening,"'url'   => (array) \$request->get_url_params()");
r10has('query fingerprint',$hardening,"'query' => \$fingerprint_query");
r10has('repository idempotency claim',$hardening,'WCA_Repository::claim_idempotency');
r10has('completed replay response',$hardening,"'completed' === (string) ( \$claim['status'] ?? '' )");
r10has('concurrent mutation refusal',$hardening,'wca_idempotency_in_progress');
r10has('failed claim release',$hardening,'WCA_Repository::release_idempotency');
r10has('success claim completion',$hardening,'WCA_Repository::complete_idempotency');
r10lacks('no synthetic implicit idempotency key',$hardening,'auto_idempotency');
r10has('transition expected status',$hardening,'expected_status');
r10has('transition expected version',$hardening,'expected_version');
r10has('transition precondition 428',$hardening,'wca_transition_precondition_required');
r10has('browser transition explicit key',$js,'idempotency_key: uuid()');
r10has('payer authority',$hardening,'wca_payment_payer_required');
r10has('payment object recheck',$hardening,'WCA_Authorization::can_view_appointment');
r10has('availability doctor scope',$hardening,'wca_availability_doctor_scope');
r10has('doctor serving-clinic filter',$hardening,'wca_doctor_may_serve_clinic');
r10has('schedule delegation scope',$hardening,"delegated_clinic_ids( \$doctor, 'schedule' )");

// R6 worldwide date/DST boundary correctness.
r10has('late slot route override',$hardening,"'/slots'");
r10has('slot date leading expansion',$hardening,"strtotime( \$from . ' -1 day UTC' )");
r10has('slot date trailing expansion',$hardening,"strtotime( \$to . ' +1 day UTC' )");
r10has('display-zone filtering',$hardening,"setTimezone( \$zone )");
r10has('display window',$hardening,"'display_window'");
r10has('hold leading reprojection',$plan,"strtotime( \$start . ' UTC' ) - DAY_IN_SECONDS");
r10has('hold trailing reprojection',$plan,"strtotime( \$end . ' UTC' ) + DAY_IN_SECONDS");
r10has('hold reprojection UTC',$plan,"\$query['timezone']  = 'UTC'");
r10has('exact slot evidence match',$plan,'hash_equals( $slot_ref');

// R7 branch audit/domain/search projection evidence.
r10has('branch event contract',$contracts,"'ClinicBranchChanged.v1'");
r10has('branch audit append',$hardening,"append_event( 'ClinicBranchChanged.v1'");
r10has('branch domain event enqueue',$hardening,"enqueue( 'ClinicBranchChanged.v1'");
r10has('branch search projection enqueue',$hardening,"enqueue( 'File26.SearchProjectionChanged.v1'");
r10lacks('no companion File26 table write',$hardening,'file26_');

// Current release/document parity remains part of the permanent first-cycle gate.
r10has('plugin readme current runtime',$readme,'Stable tag: 1.2.2');
r10has('repo readme current runtime',$repo_readme,'Runtime candidate: **1.2.2**');
r10has('status current runtime',$status,'Runtime candidate: **1.2.2**');
r10has('changelog current runtime',$changelog,'## 1.2.2 — 2026-08-11');
r10has('current companion list',$staging,'Files 00, 03, 07, 09, 17, 19, 20, 24, 25, and 26');
r10has('green acceptance token',$staging,'#087A4E');
r10lacks('superseded orange acceptance',$staging,'Sabri Orange primary controls');
r10has('exact deployed truth gate',$staging,'exact deployed version/files/checksum');
r10has('staging not repository truth',$status,'does not prove the current staging or live installation');

// Global negative-path and ownership invariants after ten rounds.
$runtime = implode("\n",array($bootstrap,$hardening,$frontend,$plan,$contracts));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ r10lacks('forbidden runtime primitive',$runtime,$token); }
r10has('zero commission contract',$contracts,"'commission_percent' => 0");
r10has('no donor visibility advantage',$contracts,"'donation_visibility_link' => false");
r10has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
r10has('no automated prescribing',$contracts,"'automated_prescribing' => false");
r10has('Future24 excluded from core double guard',$hardening,"0 === strpos( \$route, '/wca/v1/future24/' )");
r10has('continuity excluded from core double guard',$hardening,"0 === strpos( \$route, '/wca/v1/continuity/' )");
r10true('no obsolete dashboard patch template',!is_file($root.'/templates/dashboard-hardening.php'));

if($failures){ fwrite(STDERR,"File 08 ten-round regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo "File 08 ten-round post-closure regression assertions passed: {$checks}/{$checks}.\n";
