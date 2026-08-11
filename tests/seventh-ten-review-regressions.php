<?php
/** File 08 seventh fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t710src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t710has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t710lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$bootstrap=t710src('worldwide-clinic.php');
$contracts=t710src('includes/class-wca-contracts.php');
$privacy=t710src('includes/class-wca-privacy.php');
$continuity=t710src('includes/class-wca-continuity-secure.php');
$future=t710src('includes/class-wca-future24.php');
$readme=t710src('readme.txt');
$repo_readme=t710src('README.md');
$status=t710src('STATUS.md');
$changelog=t710src('CHANGELOG.md');
t710has('continuity eraser cursor namespace',$continuity,'wca_continuity_erase_');
t710has('continuity eraser keyset cursor',$continuity,'AND id>%d ORDER BY id ASC LIMIT %d');
t710has('continuity eraser computed done',$continuity,'\'done\' => $done');
t710has('continuity retention bounded keyset',$continuity,'$batch = 200;');
t710has('continuity intake retention cursor',$continuity,'updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d');
t710has('continuity followup retention cursor',$continuity,"status IN ('completed','cancelled') AND id>%d ORDER BY id ASC LIMIT %d");
t710has('future24 retention keyset batch',$privacy,'$batch = 250;');
t710has('future24 retention cursor',$privacy,'updated_at<%s AND id>%d ORDER BY id ASC LIMIT %d');
t710has('windows explicit overflow error',$future,'wca_windows_limit');
t710has('windows full validated iteration',$future,'foreach ( $raw_windows as $window )');
t710has('prerequisite explicit overflow error',$future,'wca_prerequisite_rules_limit');
t710has('prerequisite full validated iteration',$future,'foreach ( $raw_requirements as $item )');
t710has('followup resource overflow error',$continuity,'wca_followup_resource_limit');
t710has('followup resource full iteration',$continuity,'foreach ( (array) $resources as $resource )');
t710lacks('no prerequisite evidence first-100 slice',$future,'array_slice( $raw_evidence, 0, 100, true )');
t710has('episode explicit overflow error',$future,'wca_episode_appointment_limit');
t710has('episode full validated iteration',$future,'foreach ( $raw_refs as $ref )');
t710has('heatmap complete traversal',$future,'$ids = self::clinic_appointments_between_all( $clinic_id, $from, $to );');
t710lacks('no heatmap 2000 cap',$future,'clinic_appointments_between( $clinic_id, $from, $to, 2000 )');
t710has('no-show complete traversal marker',$future,'time() - 365 * DAY_IN_SECONDS');
t710lacks('no no-show 2000 cap',$future,'clinic_appointments( $clinic_id, 365, 2000 )');
t710has('plugin 1.2.7',$bootstrap,'Version: 1.2.7');
t710has('runtime 1.2.7',$contracts,"RUNTIME_VERSION                 = '1.2.7'");
t710has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
t710has('readme stable 1.2.7',$readme,'Stable tag: 1.2.7');
t710has('repository readme 1.2.7',$repo_readme,'Runtime candidate: **1.2.7**');
t710has('status 1.2.7',$status,'Runtime candidate: **1.2.7**');
t710has('changelog 1.2.7',$changelog,'## 1.2.7 — 2026-08-11');
t710has('zero commission',$contracts,"'commission_percent' => 0");
t710has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t710has('no automated prescribing',$contracts,"'automated_prescribing' => false");
$runtime=implode("\n",array($bootstrap,$contracts,$privacy,$continuity,$future));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t710lacks('forbidden runtime primitive',$runtime,$token); }
if($failures){ fwrite(STDERR,"File 08 seventh-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'File 08 seventh fresh ten-round regression assertions passed: ' . $checks . '/' . $checks . ".\n";
