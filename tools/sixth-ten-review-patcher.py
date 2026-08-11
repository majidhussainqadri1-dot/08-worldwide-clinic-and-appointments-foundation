from pathlib import Path
import sys

ROUND = int(sys.argv[1])

def path(name): return Path(name)
def read(name): return path(name).read_text(encoding='utf-8')
def write(name, data): path(name).write_text(data, encoding='utf-8')
def replace_once(name, old, new):
    data = read(name)
    count = data.count(old)
    if count != 1:
        raise SystemExit(f'{name}: expected one marker, found {count}: {old[:160]!r}')
    write(name, data.replace(old, new, 1))
def insert_before(name, marker, block):
    data = read(name)
    if block.strip() in data:
        return
    if marker not in data:
        raise SystemExit(f'{name}: insertion marker missing: {marker[:160]!r}')
    write(name, data.replace(marker, block + marker, 1))

if ROUND == 1:
    replace_once(
        'includes/class-wca-authorization.php',
        "\t\tif ( ! $clinic || ! $doctor_user_id ) { return false; }\n\t\t$clinic_id = absint( $clinic['id'] ?? 0 );\n",
        "\t\tif ( ! $clinic || ! $doctor_user_id ) { return false; }\n\t\t/* Serving authority is never valid for a practitioner whose current File 09/doctor authority is no longer eligible.\n\t\t * Keep this invariant at the canonical relationship root so direct/internal callers cannot bypass an edge-only check. */\n\t\tif ( ! class_exists( 'SWC_Doctor_Authority' ) || ! SWC_Doctor_Authority::is_eligible( $doctor_user_id ) ) { return false; }\n\t\t$clinic_id = absint( $clinic['id'] ?? 0 );\n"
    )

elif ROUND == 2:
    replace_once(
        'includes/class-wca-outbox.php',
        "\t\t\t\t\t$result = self::dispatch( (string) $item['topic'], (string) $item['aggregate_ref'], $payload, (string) $item['trace_id'] );",
        "\t\t\t\t\t$result = self::dispatch( (string) $item['message_id'], (string) $item['topic'], (string) $item['aggregate_ref'], $payload, (string) $item['trace_id'] );"
    )
    replace_once(
        'includes/class-wca-outbox.php',
        "\tprivate static function dispatch( $topic, $aggregate_ref, $payload, $trace_id ) {\n\t\t$envelope = array(\n\t\t\t'topic'         => $topic,",
        "\tprivate static function dispatch( $message_id, $topic, $aggregate_ref, $payload, $trace_id ) {\n\t\t$envelope = array(\n\t\t\t'message_id'    => sanitize_text_field( $message_id ),\n\t\t\t'topic'         => $topic,"
    )

elif ROUND == 3:
    insert_before(
        'includes/class-wca-repository.php',
        "\t/** @return array<int,array<string,mixed>> */\n\tpublic static function claim_outbox",
        "\n\t/** Recover abandoned processing rows after the dispatcher connection/lease disappeared.\n\t * Stable message_id fencing (included in every envelope) lets idempotent consumers\n\t * safely de-duplicate a retry if the prior worker died after external delivery.\n\t */\n\tpublic static function recover_stale_outbox( $stale_seconds = 300 ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['outbox'];\n\t\t$stale_seconds = min( HOUR_IN_SECONDS, max( 60, absint( $stale_seconds ) ) );\n\t\t$now = self::now();\n\t\t$stale_before = gmdate( 'Y-m-d H:i:s', time() - $stale_seconds );\n\t\treturn (int) $wpdb->query( $wpdb->prepare(\n\t\t\t\"UPDATE {$table}\n\t\t\t SET status=CASE WHEN attempts>=7 THEN 'dead_letter' ELSE 'retry' END,\n\t\t\t     attempts=attempts+1,\n\t\t\t     last_error=%s,\n\t\t\t     next_attempt_at=%s,\n\t\t\t     locked_at=NULL,\n\t\t\t     locked_by='',\n\t\t\t     updated_at=%s\n\t\t\t WHERE status='processing' AND locked_at IS NOT NULL AND locked_at<%s\",\n\t\t\t__( 'Previous outbox worker lease expired; delivery will be retried using the stable message id.', 'worldwide-clinic-appointments' ),\n\t\t\t$now,\n\t\t\t$now,\n\t\t\t$stale_before\n\t\t) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t}\n\n"
    )
    replace_once(
        'includes/class-wca-outbox.php',
        "\t\t\t$worker = 'wp-' . substr( hash( 'sha256', wp_generate_uuid4() ), 0, 16 );\n\t\t\t$items  = WCA_Repository::claim_outbox",
        "\t\t\t$recovered = WCA_Repository::recover_stale_outbox( 300 );\n\t\t\tif ( $recovered > 0 ) { WCA_Observability::metric( 'outbox_stale_recovered_total', $recovered ); }\n\t\t\t$worker = 'wp-' . substr( hash( 'sha256', wp_generate_uuid4() ), 0, 16 );\n\t\t\t$items  = WCA_Repository::claim_outbox"
    )

elif ROUND == 4:
    insert_before(
        'includes/class-wca-future24.php',
        "\tprivate static function offer_waitlist_for_cancelled_appointment",
        "\n\t/** Iterate every active waitlist entry in stable bounded pages so an eligible patient beyond an arbitrary first page is not starved. */\n\tprivate static function waitlist_candidates( $clinic_id ) {\n\t\tglobal $wpdb;\n\t\t$table = self::tables()['records'];\n\t\t$cursor = 0;\n\t\t$batch = 100;\n\t\tdo {\n\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare(\n\t\t\t\t\"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\tabsint( $clinic_id ), WCA_Repository::now(), $cursor, $batch\n\t\t\t), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) {\n\t\t\t\t$cursor = max( $cursor, absint( $row['id'] ?? 0 ) );\n\t\t\t\tyield $row;\n\t\t\t}\n\t\t} while ( count( $rows ) === $batch );\n\t}\n\n"
    )
    replace_once(
        'includes/class-wca-future24.php',
        "\t\t$waiting = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT * FROM {$table} WHERE feature_id='F08-FUT-01' AND clinic_id=%d AND status='waiting' AND (expires_at IS NULL OR expires_at>%s) ORDER BY created_at ASC,id ASC LIMIT 50\", $clinic_id, WCA_Repository::now() ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t$offered = 0;\n\t\t$slot_date = substr( $start, 0, 10 );\n\t\tforeach ( $waiting as $wait ) {",
        "\t\t$offered = 0;\n\t\t$slot_date = substr( $start, 0, 10 );\n\t\tforeach ( self::waitlist_candidates( $clinic_id ) as $wait ) {"
    )

elif ROUND == 5:
    insert_before(
        'includes/class-wca-future24.php',
        "\tpublic static function questionnaire_for_appointment",
        "\n\t/** Read every active Future24 policy/template row for one clinic with stable keyset pagination. */\n\tprivate static function feature_rows_for_clinic( $feature_id, $clinic_id, $status, $shape = 'payload' ) {\n\t\tglobal $wpdb;\n\t\t$table = self::tables()['records'];\n\t\t$feature_id = strtoupper( sanitize_text_field( $feature_id ) );\n\t\t$status = sanitize_key( $status );\n\t\tif ( ! isset( self::capabilities()[ $feature_id ] ) || ! $clinic_id || ! $status ) { return array(); }\n\t\t$columns = 'questionnaire' === $shape ? 'id,public_ref,payload_json,version,updated_at' : 'id,payload_json';\n\t\t$out = array();\n\t\t$cursor = 0;\n\t\t$batch = 100;\n\t\tdo {\n\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare(\n\t\t\t\t\"SELECT {$columns} FROM {$table} WHERE feature_id=%s AND clinic_id=%d AND status=%s AND id>%d ORDER BY id ASC LIMIT %d\",\n\t\t\t\t$feature_id, absint( $clinic_id ), $status, $cursor, $batch\n\t\t\t), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) { $cursor = max( $cursor, absint( $row['id'] ?? 0 ) ); $out[] = $row; }\n\t\t} while ( count( $rows ) === $batch );\n\t\treturn $out;\n\t}\n\n"
    )
    replace_once(
        'includes/class-wca-future24.php',
        "\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT public_ref,payload_json,version,updated_at FROM {$table} WHERE feature_id='F08-FUT-11' AND clinic_id=%d AND status='template_active' ORDER BY id DESC LIMIT 20\", $clinic_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared",
        "\t\t$rows = array_reverse( self::feature_rows_for_clinic( 'F08-FUT-11', $clinic_id, 'template_active', 'questionnaire' ) );"
    )

elif ROUND == 6:
    replace_once(
        'includes/class-wca-future24.php',
        "\t\t$rules = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT payload_json FROM {$table} WHERE feature_id='F08-FUT-13' AND clinic_id=%d AND status='rule_active' ORDER BY id DESC LIMIT 20\", $clinic_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared",
        "\t\t$rules = array_reverse( self::feature_rows_for_clinic( 'F08-FUT-13', $clinic_id, 'rule_active', 'payload' ) );"
    )

elif ROUND == 7:
    insert_before(
        'includes/class-wca-future24.php',
        "\t/* FUT-03 */",
        "\n\t/** Advance a monthly recurrence while preserving the original day-of-month when possible and clamping only to the target month's last day. */\n\tprivate static function advance_months_anchored( DateTimeImmutable $cursor, $months, $anchor_day ) {\n\t\t$months = max( 1, absint( $months ) );\n\t\t$anchor_day = min( 31, max( 1, absint( $anchor_day ) ) );\n\t\t$target = $cursor->modify( 'first day of this month' )->modify( '+' . $months . ' months' );\n\t\t$day = min( $anchor_day, absint( $target->format( 't' ) ) );\n\t\treturn $target->setDate( absint( $target->format( 'Y' ) ), absint( $target->format( 'n' ) ), $day );\n\t}\n\n"
    )
    replace_once(
        'includes/class-wca-future24.php',
        "\t\t$origin_ts = $cursor->getTimestamp();\n\t\t$custom_days = min( 365, max( 1, absint( isset( $data['custom_days'] ) ? $data['custom_days'] : $interval ) ) );",
        "\t\t$origin_ts = $cursor->getTimestamp();\n\t\t$anchor_day = absint( $cursor->format( 'j' ) );\n\t\t$custom_days = min( 365, max( 1, absint( isset( $data['custom_days'] ) ? $data['custom_days'] : $interval ) ) );"
    )
    replace_once(
        'includes/class-wca-future24.php',
        "\t\t\telseif ( 'monthly' === $frequency ) { $cursor = $cursor->modify( '+' . $interval . ' months' ); }",
        "\t\t\telseif ( 'monthly' === $frequency ) { $cursor = self::advance_months_anchored( $cursor, $interval, $anchor_day ); }"
    )

elif ROUND == 8:
    replace_once(
        'includes/class-wca-continuity-secure.php',
        "\t\t$table = self::tables()['followups'];\n\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT public_ref FROM {$table} WHERE appointment_id=%d ORDER BY due_at ASC,id ASC LIMIT 100\", $appointment_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t$out = array();\n\t\tforeach ( $rows as $row ) {\n\t\t\t$item = self::get_followup( $row['public_ref'], $actor_user_id );\n\t\t\tif ( ! is_wp_error( $item ) ) { $out[] = $item; }\n\t\t}\n\t\treturn $out;\n",
        "\t\t$table = self::tables()['followups'];\n\t\t$out = array();\n\t\t$cursor = 0;\n\t\t$batch = 100;\n\t\tdo {\n\t\t\t$rows = (array) $wpdb->get_results( $wpdb->prepare( \"SELECT id,public_ref FROM {$table} WHERE appointment_id=%d AND id>%d ORDER BY id ASC LIMIT %d\", $appointment_id, $cursor, $batch ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared\n\t\t\tforeach ( $rows as $row ) {\n\t\t\t\t$cursor = max( $cursor, absint( $row['id'] ?? 0 ) );\n\t\t\t\t$item = self::get_followup( $row['public_ref'], $actor_user_id );\n\t\t\t\tif ( ! is_wp_error( $item ) ) { $out[] = $item; }\n\t\t\t}\n\t\t} while ( count( $rows ) === $batch );\n\t\treturn $out;\n"
    )

elif ROUND == 9:
    replace_once('worldwide-clinic.php', ' * Version: 1.2.5', ' * Version: 1.2.6')
    replace_once('worldwide-clinic.php', "define( 'WCA_VERSION', '1.2.5' );", "define( 'WCA_VERSION', '1.2.6' );")
    replace_once('includes/class-wca-contracts.php', "const RUNTIME_VERSION                 = '1.2.5';", "const RUNTIME_VERSION                 = '1.2.6';")

    for test in Path('tests').glob('*.php'):
        data = test.read_text(encoding='utf-8')
        if '1.2.5' in data:
            test.write_text(data.replace('1.2.5', '1.2.6'), encoding='utf-8')

    replace_once('readme.txt', 'Stable tag: 1.2.5', 'Stable tag: 1.2.6')
    replace_once('readme.txt', 'Version 1.2.5 implements', 'Version 1.2.6 implements')
    fifth_line = "The fifth fresh corrective audit fixes Future24 public service-reference resolution, actor-independent doctor-to-clinic serving authority and held-slot rechecks, cross-key semantic concurrency for arrival/virtual-room requests, complete paged guardian/disruption/policy scans, and strict fail-closed Future24 calendar parsing/depth handling."
    sixth_line = fifth_line + "\n\nThe sixth fresh corrective audit moves current doctor eligibility into the canonical clinic-serving relationship root; gives outbox consumers a stable message identity and recovers abandoned processing leases; removes remaining waitlist/questionnaire/prerequisite/follow-up fixed-window truncation; and preserves end-of-month intent for monthly recurrence generation."
    replace_once('readme.txt', fifth_line, sixth_line)
    marker = "= 1.2.5 =\n"
    section = "= 1.2.6 =\n* Completed a sixth fresh sequential review-and-correct cycle against exact v1.2.5 repository state.\n* Canonical doctor-to-clinic serving authority now fails closed when current doctor eligibility is revoked.\n* Outbox envelopes now carry stable message IDs and abandoned processing leases are recovered for idempotent retry/dead-letter handling.\n* Removed remaining fixed-window starvation/truncation from waitlist offers, dynamic questionnaire templates, prerequisite policies, and follow-up lists.\n* Monthly recurrence preserves the originating day-of-month and clamps only to the target month's valid last day.\n* Runtime is 1.2.6; core schema remains 3.2.0; continuity schema remains 1.1.0; Future24 schema remains 1.0.0.\n* Repository/CI/package evidence remains distinct from staging/live evidence.\n\n"
    insert_before('readme.txt', marker, section)

    replace_once('README.md', 'Runtime candidate: **1.2.5**', 'Runtime candidate: **1.2.6**')
    fifth_para = "The **fifth fresh 10-round corrective audit** closes a further set of canonical-root and scale/concurrency gaps: Future24 service references now resolve through the public-ref repository path; doctor-to-clinic serving authority is actor-independent and rechecked at public slot/hold booking edges; arrival and virtual-room semantic de-duplication is serialized across distinct replay keys; guardian-family and disruption affected sets are fully paged; Future24 UTC/date parsing fails closed at the canonical root; nested calendar/DTO depth no longer fails open; and slot buffer/travel/continuous-consultation policy scans are no longer silently capped at 100 appointments."
    sixth_para = fifth_para + "\n\nThe **sixth fresh 10-round corrective audit** hardens the remaining relationship, outbox-liveness, scale and recurrence edges: doctor-to-clinic serving truth now includes current doctor eligibility at its canonical root; outbox delivery exposes a stable message identity and abandoned processing leases can be recovered without silent permanent stalls; waitlist offers, questionnaire templates, prerequisite rules and follow-up lists no longer stop at arbitrary first-page ceilings; and monthly series retain their intended day-of-month across short months."
    replace_once('README.md', fifth_para, sixth_para)

    status = read('STATUS.md')
    status = status.replace('Runtime candidate: **1.2.5**', 'Runtime candidate: **1.2.6**', 1)
    status = status.replace('## Fifth fresh 10-round corrective audit', '## Sixth fresh 10-round corrective audit', 1)
    old_block = "A fifth fresh sequential 10-round review-and-correct cycle was run against the corrected v1.2.4 repository state. Findings were corrected before the next round proceeded. The cycle hardens:\n\n- Future24 public service-reference resolution at every clinic-scoped capability;\n- actor-independent current doctor-to-clinic serving authority, including public slot search and held-slot booking rechecks;\n- cross-key semantic concurrency for patient arrival and File17 virtual-room requests;\n- complete paged guardian-family and disruption affected-set traversal;\n- strict canonical Future24 UTC/date parsing and fail-closed nested calendar/DTO depth handling;\n- complete paged slot buffer/travel/continuous-consultation policy evaluation;\n- release/test/document identity for runtime candidate 1.2.5 while retaining core schema 3.2.0, continuity schema 1.1.0, and Future24 schema 1.0.0."
    new_block = "A sixth fresh sequential review-and-correct cycle was run against the corrected v1.2.5 repository state. Findings were corrected before the next round proceeded. The cycle hardens:\n\n- current doctor eligibility at the canonical doctor-to-clinic serving relationship root;\n- stable outbox message identity for idempotent consumers plus recovery/dead-letter handling for abandoned processing leases;\n- complete paged waitlist-offer candidate traversal;\n- complete active questionnaire-template and prerequisite-policy traversal;\n- end-of-month-stable monthly recurrence generation;\n- complete paged follow-up listing;\n- release/test/document identity for runtime candidate 1.2.6 while retaining core schema 3.2.0, continuity schema 1.1.0, and Future24 schema 1.0.0."
    if old_block not in status:
        raise SystemExit('STATUS sixth-cycle block marker missing')
    status = status.replace(old_block, new_block, 1)
    status = status.replace('fifth-cycle source corrections are present', 'sixth-cycle source corrections are present', 1)
    status = status.replace('No older v1.2.1/v1.2.2/v1.2.3/v1.2.4 artifact or older CI run may be used as evidence for the v1.2.5 candidate.', 'No older v1.2.1/v1.2.2/v1.2.3/v1.2.4/v1.2.5 artifact or older CI run may be used as evidence for the v1.2.6 candidate.', 1)
    write('STATUS.md', status)

    changelog = read('CHANGELOG.md')
    changelog_section = "## 1.2.6 — 2026-08-11\n\n- Completed a sixth fresh sequential 10-round review-and-correct cycle against exact v1.2.5 repository state.\n- Moved current doctor eligibility into the canonical doctor-to-clinic serving relationship root.\n- Added stable outbox message identity and abandoned-processing lease recovery/dead-letter progression.\n- Removed remaining fixed-window truncation from waitlist offers, questionnaire templates, prerequisite rules and follow-up listing.\n- Corrected monthly recurrence month-end drift by preserving the originating day-of-month and clamping only when the target month is shorter.\n- Advanced runtime identity to 1.2.6 without schema inflation: core 3.2.0, continuity 1.1.0, Future24 1.0.0.\n- Added a permanent sixth-ten-review regression gate. Repository/package/CI, staging, live and operational evidence remain separate states.\n\n"
    insert_before('CHANGELOG.md', '## 1.2.5 — 2026-08-11\n', changelog_section)

    sixth_test = r'''<?php
/** File 08 sixth fresh ten-round corrective regression gate. */
$root = dirname( __DIR__ );
$failures = array();
$checks = 0;
function t610src( $path ) { global $root,$failures; $file=$root.'/'.$path; if(!is_file($file)){ $failures[]='Missing '.$path; return ''; } $data=file_get_contents($file); return is_string($data)?$data:''; }
function t610has( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false===strpos($source,$needle)){ $failures[]=$label.' missing: '.$needle; } }
function t610lacks( $label,$source,$needle ) { global $failures,$checks; $checks++; if(false!==strpos($source,$needle)){ $failures[]=$label.' forbidden: '.$needle; } }
$bootstrap=t610src('worldwide-clinic.php');
$contracts=t610src('includes/class-wca-contracts.php');
$auth=t610src('includes/class-wca-authorization.php');
$outbox=t610src('includes/class-wca-outbox.php');
$repo=t610src('includes/class-wca-repository.php');
$future=t610src('includes/class-wca-future24.php');
$continuity=t610src('includes/class-wca-continuity-secure.php');
$readme=t610src('readme.txt');
$repo_readme=t610src('README.md');
$status=t610src('STATUS.md');
$changelog=t610src('CHANGELOG.md');
t610has('doctor eligibility at serving root',$auth,"SWC_Doctor_Authority::is_eligible( $doctor_user_id )");
t610has('outbox dispatch stable message id argument',$outbox,"self::dispatch( (string) $item['message_id']");
t610has('outbox envelope stable message id',$outbox,"'message_id'    => sanitize_text_field( $message_id )");
t610has('stale outbox recovery method',$repo,'function recover_stale_outbox');
t610has('stale processing selector',$repo,"WHERE status='processing' AND locked_at IS NOT NULL AND locked_at<%s");
t610has('stale retry progression',$repo,"status=CASE WHEN attempts>=7 THEN 'dead_letter' ELSE 'retry' END");
t610has('dispatcher invokes stale recovery',$outbox,'WCA_Repository::recover_stale_outbox( 300 )');
t610has('waitlist keyset iterator',$future,'function waitlist_candidates');
t610has('waitlist iterator consumption',$future,'foreach ( self::waitlist_candidates( $clinic_id ) as $wait )');
t610lacks('no waitlist first-50 ceiling',$future,"status='waiting' AND (expires_at IS NULL OR expires_at>%s) ORDER BY created_at ASC,id ASC LIMIT 50");
t610has('feature row keyset helper',$future,'function feature_rows_for_clinic');
t610has('questionnaire complete traversal',$future,"feature_rows_for_clinic( 'F08-FUT-11', $clinic_id, 'template_active', 'questionnaire' )");
t610lacks('no questionnaire limit20',$future,"status='template_active' ORDER BY id DESC LIMIT 20");
t610has('prerequisite complete traversal',$future,"feature_rows_for_clinic( 'F08-FUT-13', $clinic_id, 'rule_active', 'payload' )");
t610lacks('no prerequisite limit20',$future,"status='rule_active' ORDER BY id DESC LIMIT 20");
t610has('anchored monthly helper',$future,'function advance_months_anchored');
t610has('monthly recurrence uses anchor',$future,'self::advance_months_anchored( $cursor, $interval, $anchor_day )');
t610lacks('no raw monthly modify drift',$future,"$cursor = $cursor->modify( '+' . $interval . ' months' )");
t610has('followup keyset cursor',$continuity,"WHERE appointment_id=%d AND id>%d ORDER BY id ASC LIMIT %d");
t610lacks('no followup limit100',$continuity,"WHERE appointment_id=%d ORDER BY due_at ASC,id ASC LIMIT 100");
t610has('plugin 1.2.6',$bootstrap,'Version: 1.2.6');
t610has('runtime 1.2.6',$contracts,"RUNTIME_VERSION                 = '1.2.6'");
t610has('core schema unchanged',$contracts,"SCHEMA_VERSION                  = '3.2.0'");
t610has('readme stable 1.2.6',$readme,'Stable tag: 1.2.6');
t610has('repository readme 1.2.6',$repo_readme,'Runtime candidate: **1.2.6**');
t610has('status 1.2.6',$status,'Runtime candidate: **1.2.6**');
t610has('changelog 1.2.6',$changelog,'## 1.2.6 — 2026-08-11');
t610has('zero commission',$contracts,"'commission_percent' => 0");
t610has('no automated diagnosis',$contracts,"'automated_diagnosis' => false");
t610has('no automated prescribing',$contracts,"'automated_prescribing' => false");
$runtime=implode("\n",array($bootstrap,$contracts,$auth,$outbox,$repo,$future,$continuity));
foreach(array('eval(','base64_decode(','shell_exec(','unserialize(') as $token){ t610lacks('forbidden runtime primitive',$runtime,$token); }
if($failures){ fwrite(STDERR,"File 08 sixth-ten-review regression gate failed:\n- ".implode("\n- ",$failures)."\n"); exit(1); }
echo 'File 08 sixth fresh ten-round regression assertions passed: ' . $checks . '/' . $checks . ".\n";
'''
    Path('tests/sixth-ten-review-regressions.php').write_text(sixth_test, encoding='utf-8')
    replace_once(
        'tests/run-all.php',
        "'fifth-ten-review-regressions.php' );",
        "'fifth-ten-review-regressions.php', 'sixth-ten-review-regressions.php' );"
    )

else:
    raise SystemExit('Unsupported sixth review round')

print(f'round {ROUND} patch complete')
