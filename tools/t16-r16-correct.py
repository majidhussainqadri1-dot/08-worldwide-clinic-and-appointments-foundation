from pathlib import Path
root=Path(__file__).resolve().parents[1]

def rep(path, old, new, count=1):
    p=root/path; s=p.read_text()
    if old not in s: raise SystemExit(f'anchor missing {path}: {old[:120]!r}')
    p.write_text(s.replace(old,new,count))

# Contracts: explicit complaint transition law.
anchor="\tpublic static function payment_statuses() {\n\t\treturn array( 'pending','created','pending_provider','authorized','captured','settled','failed','cancelled','expired','refunded','disputed','uncertain' );\n\t}\n"
insert=anchor+"""
	/** @return array<string,array<int,string>> */
	public static function complaint_transition_matrix() {
		return array(
			'submitted' => array( 'triaged' ),
			'triaged' => array( 'under_review', 'dismissed' ),
			'under_review' => array( 'awaiting_evidence', 'resolved', 'dismissed' ),
			'awaiting_evidence' => array( 'under_review', 'resolved', 'dismissed' ),
			'resolved' => array( 'appealed', 'closed' ),
			'dismissed' => array( 'appealed', 'closed' ),
			'appealed' => array( 'under_review', 'resolved', 'dismissed', 'closed' ),
			'closed' => array(),
		);
	}

	public static function can_transition_complaint( $from, $to ) {
		$from=sanitize_key((string)$from); $to=sanitize_key((string)$to); $m=self::complaint_transition_matrix();
		return isset($m[$from]) && in_array($to,$m[$from],true);
	}
"""
rep('includes/class-wca-contracts.php',anchor,insert)

# Repository: strict creation fields and complaint read/update projection.
old="""\t\t$row = array(
\t\t\t'public_ref'         => self::uuid(),
\t\t\t'appointment_id'     => absint( $data['appointment_id'] ?? 0 ),
\t\t\t'clinic_id'          => absint( $data['clinic_id'] ?? 0 ),
\t\t\t'complainant_user_id'=> absint( $data['complainant_user_id'] ?? 0 ),
\t\t\t'category'           => sanitize_key( $data['category'] ?? 'service' ),
\t\t\t'summary'            => sanitize_textarea_field( $data['summary'] ?? '' ),
\t\t\t'evidence_refs_json' => self::json( array_values( array_filter( array_map( 'sanitize_text_field', (array) ( $data['evidence_refs'] ?? array() ) ) ) ) ),
\t\t\t'purpose_limit'      => sanitize_text_field( $data['purpose_limit'] ?? 'case_resolution_only' ),
\t\t\t'status'             => 'submitted',
"""
new="""\t\t$category=sanitize_key($data['category'] ?? 'service');
\t\tif(!in_array($category,array('service','appointment','conduct','billing','access','privacy','other'),true)){return new WP_Error('wca_complaint_category',__('Complaint category is unsupported.','worldwide-clinic-appointments'),array('status'=>400));}
\t\t$summary=sanitize_textarea_field($data['summary'] ?? ''); if(strlen($summary)>2000){return new WP_Error('wca_complaint_summary_length',__('Complaint summary is too long.','worldwide-clinic-appointments'),array('status'=>400));}
\t\t$evidence=array(); foreach((array)($data['evidence_refs'] ?? array()) as $ref){$ref=sanitize_text_field((string)$ref); if(!preg_match('/^[A-Za-z0-9._:-]{8,191}$/',$ref)){return new WP_Error('wca_complaint_evidence_ref',__('Complaint evidence references must be opaque identifiers, not narrative content.','worldwide-clinic-appointments'),array('status'=>400));} $evidence[$ref]=true; if(count($evidence)>20){return new WP_Error('wca_complaint_evidence_limit',__('Too many complaint evidence references were supplied.','worldwide-clinic-appointments'),array('status'=>400));}}
\t\t$row = array(
\t\t\t'public_ref'         => self::uuid(),
\t\t\t'appointment_id'     => absint( $data['appointment_id'] ?? 0 ),
\t\t\t'clinic_id'          => absint( $data['clinic_id'] ?? 0 ),
\t\t\t'complainant_user_id'=> absint( $data['complainant_user_id'] ?? 0 ),
\t\t\t'category'           => $category,
\t\t\t'summary'            => $summary,
\t\t\t'evidence_refs_json' => self::json( array_keys($evidence) ),
\t\t\t'purpose_limit'      => 'case_resolution_only',
\t\t\t'status'             => 'submitted',
"""
rep('includes/class-wca-repository.php',old,new)

anchor="\t/** @return array<string,mixed>|WP_Error */\n\tpublic static function create_payment_intent( $data ) {"
methods=r'''
	/** @return array<string,mixed>|null|WP_Error */
	public static function get_complaint_by_ref( $ref, $for_update=false ) {
		global $wpdb; $table=WCA_Schema::tables()['complaints']; $ref=strtolower(sanitize_text_field((string)$ref));
		if(!preg_match('/^[0-9a-f-]{36}$/',$ref)){return null;}
		$sql="SELECT * FROM {$table} WHERE public_ref=%s LIMIT 1" . ($for_update?' FOR UPDATE':'');
		$row=$wpdb->get_row($wpdb->prepare($sql,$ref),ARRAY_A);
		if(null===$row && ''!==(string)$wpdb->last_error){return new WP_Error('wca_complaint_read_failed',__('Complaint state could not be read safely.','worldwide-clinic-appointments'),array('status'=>503));}
		return $row?:null;
	}

	/** @return array<string,mixed>|WP_Error */
	public static function transition_complaint( $ref, $next, $expected_version, $assigned_user_id=0, $outcome=array() ) {
		global $wpdb; $table=WCA_Schema::tables()['complaints']; $row=self::get_complaint_by_ref($ref,true); if(is_wp_error($row)){return $row;} if(!$row){return new WP_Error('wca_complaint_not_found',__('Complaint was not found.','worldwide-clinic-appointments'),array('status'=>404));}
		$next=sanitize_key((string)$next); $expected_version=absint($expected_version); if(!$expected_version || $expected_version!==absint($row['version'])){return new WP_Error('wca_complaint_version_conflict',__('Complaint state changed. Refresh before retrying.','worldwide-clinic-appointments'),array('status'=>409));}
		if(!WCA_Contracts::can_transition_complaint((string)$row['status'],$next)){return new WP_Error('wca_complaint_transition',__('That complaint transition is not permitted.','worldwide-clinic-appointments'),array('status'=>409));}
		$clean_outcome=array(); foreach((array)$outcome as $k=>$v){$k=sanitize_key((string)$k); if(!in_array($k,array('code','resolution_ref','appeal_ref'),true)||!is_scalar($v)){continue;} $clean_outcome[$k]=substr(sanitize_text_field((string)$v),0,191);}
		$update=array('status'=>$next,'assigned_user_id'=>absint($assigned_user_id),'outcome_json'=>self::json($clean_outcome),'version'=>$expected_version+1,'updated_at'=>self::now());
		$changed=$wpdb->update($table,$update,array('id'=>absint($row['id']),'version'=>$expected_version)); if(false===$changed){return new WP_Error('wca_complaint_write_failed',__('Complaint state could not be persisted safely.','worldwide-clinic-appointments'),array('status'=>503));} if(1!==(int)$changed){return new WP_Error('wca_complaint_version_conflict',__('Complaint state changed concurrently.','worldwide-clinic-appointments'),array('status'=>409));}
		$updated=self::get_complaint_by_ref($ref,false); return $updated?:new WP_Error('wca_complaint_readback_missing',__('Updated complaint state could not be verified.','worldwide-clinic-appointments'),array('status'=>503));
	}

'''
rep('includes/class-wca-repository.php',anchor,methods+anchor)

# Service: strict clinic scope, safe projection, complainant appeal, verified CF02 projection.
rep('includes/class-wca-service.php',"\t\tadd_action( 'cf03_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed_event' ), 10, 1 );",
"\t\tadd_action( 'cf03_payment_status_changed', array( __CLASS__, 'handle_payment_status_changed_event' ), 10, 1 );\n\t\tadd_action( 'cf02_case_status_changed', array( __CLASS__, 'handle_cf02_case_status_changed' ), 10, 1 );")

old="""\t\t$appointment_id = absint( $data['appointment_id'] ?? 0 );
\t\tif ( $appointment_id ) {
\t\t\t$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
\t\t\tif ( is_wp_error( $access ) ) { return $access; }
\t\t\t$data['clinic_id'] = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) );
\t\t}
\t\t$data['complainant_user_id'] = $actor_user_id;
"""
new="""\t\t$appointment_id = absint( $data['appointment_id'] ?? 0 );
\t\tif ( $appointment_id ) {
\t\t\t$access = WCA_Authorization::can_view_appointment( $appointment_id, $actor_user_id );
\t\t\tif ( is_wp_error( $access ) ) { return $access; }
\t\t\t$data['clinic_id'] = absint( SWC_Helpers::meta( $appointment_id, 'clinic_id' ) );
\t\t} else {
\t\t\t$clinic_id=absint($data['clinic_id'] ?? 0); if(!$clinic_id){return new WP_Error('wca_complaint_scope',__('A complaint must reference an appointment or clinic.','worldwide-clinic-appointments'),array('status'=>400));}
\t\t\tWCA_Repository::clear_read_error(); $clinic=WCA_Repository::get_clinic($clinic_id,false); $read_error=WCA_Repository::consume_read_error(); if(is_wp_error($read_error)){return $read_error;} if(!$clinic){return new WP_Error('wca_complaint_clinic_missing',__('Complaint clinic was not found.','worldwide-clinic-appointments'),array('status'=>404));}
\t\t}
\t\t$data['purpose_limit']='case_resolution_only';
\t\t$data['complainant_user_id'] = $actor_user_id;
"""
rep('includes/class-wca-service.php',old,new)

anchor="\tprivate static function approved_payment_provider( $provider ) {"
service_methods=r'''
	/** @return array<string,mixed>|WP_Error */
	public static function complaint_projection( $ref, $actor_user_id=0 ) {
		$actor_user_id=absint($actor_user_id?:get_current_user_id()); $row=WCA_Repository::get_complaint_by_ref($ref,false); if(is_wp_error($row)){return $row;} if(!$row){return new WP_Error('wca_complaint_not_found',__('Complaint was not found.','worldwide-clinic-appointments'),array('status'=>404));}
		$allowed=$actor_user_id===absint($row['complainant_user_id']) || current_user_can('manage_worldwide_clinic') || current_user_can('manage_options');
		if(!$allowed){return new WP_Error('wca_complaint_forbidden',__('You cannot view this complaint.','worldwide-clinic-appointments'),array('status'=>403));}
		return array('public_ref'=>(string)$row['public_ref'],'appointment_ref'=>$row['appointment_id']?(string)SWC_Helpers::meta(absint($row['appointment_id']),'public_ref'):'','category'=>(string)$row['category'],'summary'=>(string)$row['summary'],'evidence_refs'=>WCA_Repository::decode_json($row['evidence_refs_json'],array()),'purpose_limit'=>'case_resolution_only','status'=>(string)$row['status'],'version'=>absint($row['version']),'created_at'=>(string)$row['created_at'],'updated_at'=>(string)$row['updated_at']);
	}

	/** @return array<string,mixed>|WP_Error */
	public static function appeal_complaint( $ref, $expected_version, $actor_user_id=0 ) {
		$actor_user_id=absint($actor_user_id?:get_current_user_id()); $row=WCA_Repository::get_complaint_by_ref($ref,false); if(is_wp_error($row)){return $row;} if(!$row){return new WP_Error('wca_complaint_not_found',__('Complaint was not found.','worldwide-clinic-appointments'),array('status'=>404));} if($actor_user_id!==absint($row['complainant_user_id'])){return new WP_Error('wca_complaint_appeal_forbidden',__('Only the complainant may appeal this complaint.','worldwide-clinic-appointments'),array('status'=>403));}
		return WCA_Repository::transaction(function() use($ref,$expected_version,$actor_user_id){$updated=WCA_Repository::transition_complaint($ref,'appealed',$expected_version,0,array()); if(is_wp_error($updated)){return $updated;} $trace=WCA_Observability::trace_id(); $audit=WCA_Repository::append_event('AppointmentComplaintAppealed.v1','complaint',$ref,array('complaint_ref'=>$ref,'status'=>'appealed','trace_id'=>$trace),$actor_user_id,$trace); if(is_wp_error($audit)){return $audit;} $queued=WCA_Repository::enqueue('CF02.CaseAppealRequested.v1',$ref,array('case_type'=>'appointment_complaint','complaint_ref'=>$ref,'purpose_limit'=>'case_resolution_only'),$trace); return is_wp_error($queued)?$queued:$updated;},'wca_complaint_appeal_transaction');
	}

	public static function handle_cf02_case_status_changed( $event ) { $result=self::consume_cf02_case_status_event($event); if(is_wp_error($result)){WCA_Observability::log('error','cf02_complaint_projection_failed',array('code'=>$result->get_error_code()));} return $result; }

	/** @return array<string,mixed>|WP_Error */
	public static function consume_cf02_case_status_event( $event ) {
		if(!is_array($event)||true!==($event['verified']??false)||'CF02'!==(string)($event['source']??'')){return new WP_Error('wca_cf02_case_unverified',__('Only a verified CF02 case fact may update File 08 complaint state.','worldwide-clinic-appointments'),array('status'=>403));}
		$event_id=sanitize_text_field($event['event_id']??''); $ref=strtolower(sanitize_text_field($event['complaint_ref']??'')); $next=sanitize_key($event['status']??''); $expected=absint($event['expected_version']??0); if(!preg_match('/^[A-Za-z0-9._:-]{8,191}$/',$event_id)||!preg_match('/^[0-9a-f-]{36}$/',$ref)||!isset(WCA_Contracts::complaint_transition_matrix()[$next])||!$expected){return new WP_Error('wca_cf02_case_event_invalid',__('Verified CF02 complaint event is malformed.','worldwide-clinic-appointments'),array('status'=>400));}
		$claim=WCA_Repository::claim_idempotency('cf02_complaint_status',$event_id,0,array('complaint_ref'=>$ref,'status'=>$next,'expected_version'=>$expected)); if(is_wp_error($claim)){return $claim;} if('completed'===(string)($claim['status']??'')){return $claim['response'];} if(empty($claim['claimed_new'])){return new WP_Error('wca_cf02_case_event_in_progress',__('This complaint case fact is already being reconciled.','worldwide-clinic-appointments'),array('status'=>409));}
		$result=WCA_Repository::transaction(function() use($event,$ref,$next,$expected,$claim,$event_id){$updated=WCA_Repository::transition_complaint($ref,$next,$expected,absint($event['assigned_user_id']??0),(array)($event['outcome']??array())); if(is_wp_error($updated)){return $updated;} $trace=WCA_Observability::trace_id(); $audit=WCA_Repository::append_event('ComplaintCaseStatusProjected.v1','complaint',$ref,array('event_id'=>WCA_Repository::uuid(),'source_event_id'=>$event_id,'complaint_ref'=>$ref,'status'=>$next,'trace_id'=>$trace),0,$trace); if(is_wp_error($audit)){return $audit;} $response=array('public_ref'=>$ref,'status'=>$next,'version'=>absint($updated['version']),'updated_at'=>(string)$updated['updated_at']); if(!WCA_Repository::complete_idempotency($claim['id'],200,$response)){return new WP_Error('wca_cf02_case_finalize',__('Complaint case projection could not be finalized safely.','worldwide-clinic-appointments'),array('status'=>500));} return $response;},'wca_cf02_complaint_projection_transaction'); if(is_wp_error($result)){WCA_Repository::release_idempotency($claim['id']);} return $result;
	}

'''
rep('includes/class-wca-service.php',anchor,service_methods+anchor)

# REST: read and appeal routes, opaque ref only.
route_anchor="\t\tregister_rest_route( self::NAMESPACE, '/complaints', array(\n\t\t\t'methods' => WP_REST_Server::CREATABLE,\n\t\t\t'callback' => array( __CLASS__, 'complaint' ),\n\t\t\t'permission_callback' => array( __CLASS__, 'authenticated' ),\n\t\t) );"
route_new=route_anchor+"""
		register_rest_route( self::NAMESPACE, '/complaints/(?P<ref>[0-9a-fA-F-]{36})', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array( __CLASS__, 'complaint_detail' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );
		register_rest_route( self::NAMESPACE, '/complaints/(?P<ref>[0-9a-fA-F-]{36})/appeal', array(
			'methods' => WP_REST_Server::CREATABLE,
			'callback' => array( __CLASS__, 'complaint_appeal' ),
			'permission_callback' => array( __CLASS__, 'authenticated' ),
		) );"""
rep('includes/class-wca-rest.php',route_anchor,route_new)

anchor="\tpublic static function health() {"
rest_methods=r'''
	public static function complaint_detail( WP_REST_Request $request ) { $rate=self::rate_limit('complaint_read',60,HOUR_IN_SECONDS); if(is_wp_error($rate)){return $rate;} return self::respond(WCA_Service::complaint_projection($request['ref'],get_current_user_id())); }

	public static function complaint_appeal( WP_REST_Request $request ) { $rate=self::rate_limit('complaint_appeal',10,HOUR_IN_SECONDS); if(is_wp_error($rate)){return $rate;} $data=self::data($request); return self::respond(self::protected_mutation_projection(WCA_Service::appeal_complaint($request['ref'],absint($data['expected_version']??0),get_current_user_id()),'complaint')); }

'''
rep('includes/class-wca-rest.php',anchor,rest_methods+anchor)

# Permanent T16 checks.
p=root/'tests/sixteenth-twenty-review-regressions.php'; s=p.read_text(); marker='if($fail){fwrite(STDERR,"T16 regression gate failed:'; idx=s.index(marker)
checks="""t16h('R16 complaint lifecycle has explicit transition law','includes/class-wca-contracts.php','complaint_transition_matrix');
t16h('R16 complaint purpose limit is server-owned','includes/class-wca-repository.php',"'purpose_limit'      => 'case_resolution_only'");
t16h('R16 evidence references are opaque and bounded','includes/class-wca-repository.php','wca_complaint_evidence_limit');
t16h('R16 complaint storage read failure explicit','includes/class-wca-repository.php','wca_complaint_read_failed');
t16h('R16 complaint state update uses CAS','includes/class-wca-repository.php','wca_complaint_version_conflict');
t16h('R16 complaint detail is protected opaque route','includes/class-wca-rest.php','complaints/(?P<ref>[0-9a-fA-F-]{36})');
t16h('R16 complainant appeal route exists','includes/class-wca-rest.php','/appeal');
t16h('R16 appeal is atomic with CF02 handoff','includes/class-wca-service.php','wca_complaint_appeal_transaction');
t16h('R16 CF02 status projection requires verified source','includes/class-wca-service.php','wca_cf02_case_unverified');
t16h('R16 CF02 status projection is idempotent','includes/class-wca-service.php',"'cf02_complaint_status'");
t16h('R16 complaint projection exposes fixed purpose limit','includes/class-wca-service.php',"'purpose_limit'=>'case_resolution_only'");
"""
p.write_text(s[:idx]+checks+s[idx:])
print('R16 closed ledger applied')
