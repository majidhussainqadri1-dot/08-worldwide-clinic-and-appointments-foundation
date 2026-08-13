from pathlib import Path


def replace_once(path, old, new, label):
    p = Path(path)
    s = p.read_text()
    count = s.count(old)
    if count != 1:
        raise SystemExit(f"{label}: expected 1 match, found {count}")
    p.write_text(s.replace(old, new, 1))


repo = "includes/class-wca-repository.php"

# R8-A: slot hold TTL must reject invalid caller intent rather than silently clamp.
replace_once(
    repo,
    "\t\t$idempotency_key = hash( 'sha256', $idempotency_plain );\n\t\t$branch = $branch_id ? self::get_branch( $branch_id ) : null;",
    "\t\t$idempotency_key = hash( 'sha256', $idempotency_plain );\n\t\t$ttl = WCA_Service::strict_int( $data['ttl'] ?? 600, 300, 1800 );\n\t\tif ( null === $ttl ) { return new WP_Error( 'wca_slot_ttl_range', __( 'Slot-hold TTL must be an integer from 300 through 1800 seconds.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$branch = $branch_id ? self::get_branch( $branch_id ) : null;",
    "slot TTL strict validation",
)
replace_once(
    repo,
    "'expires_at'      => gmdate( 'Y-m-d H:i:s', time() + min( 1800, max( 300, absint( $data['ttl'] ?? 600 ) ) ) ),",
    "'expires_at'      => gmdate( 'Y-m-d H:i:s', time() + $ttl ),",
    "slot TTL persistence",
)

# R8-B: clinical context TTL must reject invalid values rather than silently clamp.
replace_once(
    repo,
    "\t\t$table = WCA_Schema::tables()['clinical_context'];\n\t\t$row = array(",
    "\t\t$table = WCA_Schema::tables()['clinical_context'];\n\t\t$ttl = WCA_Service::strict_int( $data['ttl'] ?? 300, 60, HOUR_IN_SECONDS );\n\t\tif ( null === $ttl ) { return new WP_Error( 'wca_context_ttl_range', __( 'Clinical-context TTL must be an integer from 60 through 3600 seconds.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$row = array(",
    "context TTL strict validation",
)
replace_once(
    repo,
    "'expires_at'                    => gmdate( 'Y-m-d H:i:s', time() + min( HOUR_IN_SECONDS, max( 60, absint( $data['ttl'] ?? 300 ) ) ) ),",
    "'expires_at'                    => gmdate( 'Y-m-d H:i:s', time() + $ttl ),",
    "context TTL persistence",
)

# R8-C: reject payment amounts outside the platform integer range before casting.
replace_once(
    repo,
    "\t\t$amount_raw = $data['amount_minor'] ?? 0;\n\t\tif ( is_bool( $amount_raw ) || ! preg_match( '/^\\d+$/', trim( (string) $amount_raw ) ) ) { return new WP_Error( 'wca_payment_amount_invalid', __( 'Payment amount must be a non-negative integer in minor currency units.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }\n\t\t$amount_minor = (int) $amount_raw;",
    "\t\t$amount_raw = $data['amount_minor'] ?? 0;\n\t\t$amount_minor = WCA_Service::strict_int( $amount_raw, 0, PHP_INT_MAX );\n\t\tif ( null === $amount_minor ) { return new WP_Error( 'wca_payment_amount_invalid', __( 'Payment amount must be a non-negative integer in minor currency units within the supported range.', 'worldwide-clinic-appointments' ), array( 'status' => 400 ) ); }",
    "payment amount strict range",
)

# Permanent R8 regression gates.
test = Path("tests/sixteenth-twenty-review-regressions.php")
s = test.read_text()
marker = 'if($fail){fwrite(STDERR,"T16 regression gate failed:\\n- ".implode("\\n- ",$fail)."\\n");exit(1);}'
if marker not in s:
    raise SystemExit("T16 regression marker missing")
additions = """t16h('R8 slot hold TTL rejects out-of-range intent','includes/class-wca-repository.php','wca_slot_ttl_range');
t16h('R8 slot hold TTL persists validated value','includes/class-wca-repository.php',\"time() + $ttl\");
t16h('R8 clinical context TTL rejects out-of-range intent','includes/class-wca-repository.php','wca_context_ttl_range');
t16h('R8 payment amount uses strict integer range','includes/class-wca-repository.php',\"WCA_Service::strict_int( $amount_raw, 0, PHP_INT_MAX )\");
"""
test.write_text(s.replace(marker, additions + marker, 1))
