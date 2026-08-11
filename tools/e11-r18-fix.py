#!/usr/bin/env python3
from pathlib import Path
ROOT=Path(__file__).resolve().parents[1]
def rep(path,old,new):
 p=ROOT/path; s=p.read_text()
 if old not in s: raise SystemExit('anchor missing '+path)
 p.write_text(s.replace(old,new,1))

rep('includes/class-wca-repository.php',"""\tpublic static function complete_idempotency( $id, $response_code, $response ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['idempotency'];\n\t\treturn false !== $wpdb->update( $table, array( 'status' => 'completed', 'response_code' => absint( $response_code ), 'response_json' => self::json( $response ), 'updated_at' => self::now() ), array( 'id' => absint( $id ) ) );\n\t}\n""","""\tpublic static function complete_idempotency( $id, $response_code, $response ) {\n\t\tglobal $wpdb;\n\t\t$table = WCA_Schema::tables()['idempotency'];\n\t\t$updated = $wpdb->update(\n\t\t\t$table,\n\t\t\tarray( 'status' => 'completed', 'response_code' => absint( $response_code ), 'response_json' => self::json( $response ), 'updated_at' => self::now() ),\n\t\t\tarray( 'id' => absint( $id ), 'status' => 'processing' )\n\t\t);\n\t\treturn 1 === (int) $updated;\n\t}\n""")

rep('includes/class-wca-future24.php',"""\t\tWCA_Repository::complete_idempotency( $claim['id'], $status, $result );\n\t\treturn self::respond( $result, $status );\n""","""\t\t$completed = WCA_Repository::complete_idempotency( $claim['id'], $status, $result );\n\t\tif ( ! $completed ) {\n\t\t\tWCA_Observability::log( 'error', 'future24_idempotency_finalize_failed', array( 'scope' => sanitize_key( $scope ), 'claim_id' => absint( $claim['id'] ) ) );\n\t\t\treturn new WP_Error(\n\t\t\t\t'wca_idempotency_finalize_failed',\n\t\t\t\t__( 'The scheduling mutation may have completed, but its replay record could not be finalized. Reconcile the same Idempotency-Key before retrying.', 'worldwide-clinic-appointments' ),\n\t\t\t\tarray( 'status' => 503, 'reconciliation_required' => true, 'retry_same_idempotency_key' => true )\n\t\t\t);\n\t\t}\n\t\treturn self::respond( $result, $status );\n""")

p=ROOT/'tests/eleventh-twenty-review-regressions.php'; s=p.read_text()
needle="e11has('schema stays 3.2.0',$contracts,\"SCHEMA_VERSION                  = '3.2.0'\");"
add="e11has('idempotency completion CAS',$repo,\"array( 'id' => absint( $id ), 'status' => 'processing' )\");e11has('idempotency completion row count',$repo,'return 1 === (int) $updated;');e11has('Future24 durable replay finalization',$service,'wca_service_currency');e11has('Future24 finalization failure',$rest,'protected_mutation_projection');"
# Append real Future24 assertions separately using its source.
s=s.replace("$rest=e11src('includes/class-wca-rest.php');","$rest=e11src('includes/class-wca-rest.php');$future=e11src('includes/class-wca-future24.php');",1)
if needle not in s: raise SystemExit('e11 test anchor missing')
s=s.replace(needle,needle+"e11has('idempotency completion CAS',$repo,\"array( 'id' => absint( $id ), 'status' => 'processing' )\");e11has('idempotency completion row count',$repo,'return 1 === (int) $updated;');e11has('Future24 completion checked',$future,'$completed = WCA_Repository::complete_idempotency');e11has('Future24 reconciliation failure',$future,'wca_idempotency_finalize_failed');",1)
p.write_text(s)

p=ROOT/'ELEVENTH-TWENTY-REVIEW-EVIDENCE.md'; s=p.read_text(); s=s.replace('- E11-R18 Future24/cross-file/concurrency corrected-state review.','- E11-R18 Future24 durable replay-finalization defect: successful mutations could return success even when the durable idempotency completion write failed; repository completion also treated a zero-row update as success. Fixed with processing-state CAS/one-row completion and fail-closed 503 reconciliation semantics.',1); p.write_text(s)
p=ROOT/'CHANGELOG.md'; s=p.read_text(); marker='- Runtime 1.2.11; schemas remain core 3.2.0 / continuity 1.1.0 / Future24 1.0.0.\n'; line='- Future24 durable idempotency finalization now requires a successful processing-state CAS; a failed replay-record completion returns reconciliation-required 503 instead of false success.\n';
if line not in s:
 if marker not in s: raise SystemExit('changelog marker missing')
 s=s.replace(marker,line+marker,1)
p.write_text(s)
print('Applied E11-R18 durable idempotency finalization correction')
