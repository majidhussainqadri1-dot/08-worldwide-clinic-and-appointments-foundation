from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:120]!r}')
 wr(p,s.replace(a,b,1))

p='includes/class-wca-schema.php'
once(p,"\t\t$tables  = self::tables();\n\t\t$collate = $wpdb->get_charset_collate();\n\n\t\tself::capture_snapshot();","\t\t$tables  = self::tables();\n\t\t$collate = $wpdb->get_charset_collate();\n\t\t$from_version = (string) get_option( self::OPTION_DB_VERSION, '' );\n\n\t\tself::capture_snapshot( $from_version !== WCA_Contracts::SCHEMA_VERSION );")
once(p,"\t\t$migration_state = array(\n\t\t\t'status'       => 'installed',\n\t\t\t'from_version' => (string) get_option( 'swc_db_version', '' ),","\t\t$migration_state = array(\n\t\t\t'status'       => 'installed',\n\t\t\t'from_version' => $from_version,")
once(p,"\tprivate static function capture_snapshot() {\n\t\tif ( get_option( self::OPTION_SCHEMA_SNAPSHOT, false ) ) {\n\t\t\treturn;\n\t\t}\n\t\t$snapshot = array(","\tprivate static function capture_snapshot( $refresh = false ) {\n\t\tif ( ! $refresh && get_option( self::OPTION_SCHEMA_SNAPSHOT, false ) ) {\n\t\t\treturn;\n\t\t}\n\t\t$snapshot = array(")

p='includes/class-swc-activator.php'
once(p,"\tprivate static function create_activation_snapshot() {\n\t\tif ( get_option( 'swc_activation_snapshot', false ) ) {\n\t\t\treturn;\n\t\t}\n\t\t$snapshot = array(","\tprivate static function create_activation_snapshot() {\n\t\t// Every activation/deployment attempt gets a fresh immediate pre-change snapshot.\n\t\t$snapshot = array(")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R10 canonical migration from-version captured before install','includes/class-wca-schema.php',"$from_version = (string) get_option( self::OPTION_DB_VERSION");
t15h('R10 migration state uses canonical from-version','includes/class-wca-schema.php',"'from_version' => $from_version");
t15h('R10 schema snapshot refreshes on real upgrade','includes/class-wca-schema.php','capture_snapshot( $from_version !== WCA_Contracts::SCHEMA_VERSION )');
t15h('R10 activation snapshot refreshed per attempt','includes/class-swc-activator.php','Every activation/deployment attempt gets a fresh immediate pre-change snapshot');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('gate marker missing')
wr(p,s.replace(mark,ins+mark,1))

p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R9 — transaction / CAS / projection atomicity review

R9 completed against the R8-corrected state without source modification. Owner transactions, optimistic version predicates, required event/outbox evidence and Future24 semantic serialization were re-traced. No new supported atomicity defect was proven.

R9 result: **CLEAN — no correction required.**

## R10 — migration / upgrade / rollback review

R10 completed before correction. The canonical migration state recorded the legacy SWC schema as its `from_version` instead of the actual prior WCA schema version. In addition, schema and activation snapshots could remain from an older deployment rather than the immediate pre-change state required by the rollback runbook. The R10 batch captures the true WCA from-version, refreshes the schema snapshot for a real schema transition, and refreshes the activation snapshot for every activation/deployment attempt.

R10 result: **SUPPORTED DEFECTS FOUND — full retest required before R11.**
"""; wr(p,s)
