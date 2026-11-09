<?php
/**
 * File08 public clinic → File26 OWNER read-only contract.
 *
 * Only the existing WCA_Central_Governance::file26_clinic_projection() may
 * authorize a clinic. No patient, appointment, slot hold, contact, native user
 * ID, payment or clinical data is copied into the search index.
 * PROPOSED until versioned outbox/tombstone, staging and founder approvals.
 */
defined('ABSPATH') || exit;
final class WCA_File26_Connector {
    const SLUG='file08-public-clinics';
    public static function register() {
        add_filter('sabri_file26_connector_manifests',array(__CLASS__,'append'),20);
        add_action('sabri_file26_connectors_ready',array(__CLASS__,'ready'),20);
        if(function_exists('sabri_file26_register_connector')){sabri_file26_register_connector(self::manifest());}
    }
    public static function manifest() {
        return array(
            'slug'=>self::SLUG,'owner_file'=>'File 08','contract_version'=>'1.0',
            'entity_types'=>array('clinic','appointment_availability'),
            'privacy_classes'=>array('public'),'visibility_fields'=>array('public'),
            'deletion_semantics'=>'owner_eligibility_availability_tombstone_gate_pending',
            'status'=>'proposed','event_contract'=>'File26.SearchProjectionChanged.v1.pending-cutover',
            'index_schema'=>'sabri.file26.document.v1.1',
            'list_batch'=>array(__CLASS__,'list_batch'),
            'can_view'=>array(__CLASS__,'can_view'),
            'fetch_object'=>array(__CLASS__,'fetch_object'),
            'health'=>array(__CLASS__,'health')
        );
    }
    public static function append($manifests) {
        $manifests=is_array($manifests)?$manifests:array();
        foreach($manifests as $row){if(is_array($row)&&($row['slug']??'')===self::SLUG){return $manifests;}}
        $manifests[]=self::manifest();return $manifests;
    }
    public static function ready($registry) {
        if(is_object($registry)&&method_exists($registry,'register')){$registry->register(self::manifest());}
    }
    private static function source_ready() {
        if(!class_exists('WCA_Central_Governance')||!class_exists('WCA_Schema')||
            !class_exists('WCA_Service')||!class_exists('SWC_Doctor_Authority')||
            !class_exists('SWC_Activator')||!SWC_Activator::dependencies_ready()||
            get_option('wca_runtime_migration_failure',false)){return false;}
        $tables=WCA_Schema::tables();
        global $wpdb;
        foreach(array('clinics','branches','services','availability','outbox') as $key){
            if(!isset($tables[$key])){return false;}
            $wpdb->last_error='';
            $got=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($tables[$key])));
            if($got!==$tables[$key]||$wpdb->last_error){return false;}
        }
        return true;
    }
    private static function project($ref,$version=0) {
        if(!self::source_ready()||!is_string($ref)||!preg_match('/^[a-f0-9-]{36}$/iD',$ref)){return null;}
        $source=WCA_Central_Governance::file26_clinic_projection($ref);
        if(is_wp_error($source)||!is_array($source)||empty($source['verified_owner'])||
            empty($source['indexable'])||($source['object_type']??'')!=='clinic'||
            (string)($source['public_ref']??'')!==$ref){return null;}
        $v=(int)($source['freshness']['version']??0);
        if($v<1||($version>0&&$v!==$version)){return null;}
        $url=esc_url_raw((string)($source['canonical_url']??''));
        $title=sanitize_text_field((string)($source['title']??''));
        if(!$url||!wp_http_validate_url($url)||$title===''){return null;}
        $summary=sanitize_text_field((string)($source['summary']??''));
        $languages=(array)($source['languages']??array());
        $locale=count($languages)>0?sanitize_text_field((string)reset($languages)):'en-US';
        return array(
            'connector_slug'=>self::SLUG,'domain'=>'worldwide_clinic',
            'object_id'=>$ref,'object_version'=>$v,'source_event_sequence'=>$v,
            'entity_type'=>'clinic','locale'=>substr($locale,0,20),
            'state'=>'published','visibility'=>'public',
            'title'=>$title,'excerpt'=>$summary,'search_text'=>$title.' '.$summary,
            'canonical_url'=>$url,'safety_class'=>'verified_public_clinic'
        );
    }
    public static function list_batch($cursor='',$limit=50,$scope=array()) {
        global $wpdb;
        if(!self::source_ready()){return new WP_Error('file08_owner_runtime_unverified','Canonical clinic schema or identity/verification is unavailable.');}
        if(!is_array($scope)||isset($scope['object_id'])){return new WP_Error('file08_search_partial_scope','Complete owner snapshot required.');}
        if($cursor!==''&&(!is_string($cursor)||!preg_match('/^[0-9]{1,18}$/D',$cursor))){return new WP_Error('file08_search_invalid_cursor','Invalid clinic cursor.');}
        $table=WCA_Schema::tables()['clinics'];$limit=max(1,min(50,(int)$limit));$last=(int)$cursor;
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT id,public_ref,version FROM $table WHERE id>%d ORDER BY id ASC LIMIT %d",$last,$limit+1),ARRAY_A);
        if(!is_array($rows)||$wpdb->last_error){return new WP_Error('file08_owner_unreadable','Canonical clinic owner read failed.');}
        $more=count($rows)>$limit;if($more){array_pop($rows);}
        $items=array();
        foreach($rows as $row){
            $doc=self::project((string)$row['public_ref'],(int)$row['version']);
            if($doc){$items[]=$doc;}
            $last=max($last,(int)$row['id']);
        }
        return array('items'=>$items,'done'=>!$more,'next_cursor'=>$more?(string)$last:'');
    }
    public static function fetch_object($type,$ref='') {
        if($type!=='clinic'||!is_string($ref)){return null;}
        return self::project($ref);
    }
    public static function can_view($document,$audience=array()) {
        if(!is_array($document)||($document['connector_slug']??'')!==self::SLUG||
            ($document['entity_type']??'')!=='clinic'||($document['visibility']??'')!=='public'){return false;}
        $current=self::fetch_object('clinic',(string)($document['object_id']??''));
        return is_array($current)&&(int)$current['object_version']===(int)($document['object_version']??0);
    }
    public static function health() {
        return self::source_ready()
            ? array('state'=>'healthy','owner_file'=>'File 08','status'=>'proposed','clinical_data_indexed'=>false,'versioned_revocation_approved'=>false)
            : array('state'=>'degraded','owner_file'=>'File 08','reason'=>'clinic_schema_identity_or_migration_unverified');
    }
}
