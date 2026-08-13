from pathlib import Path

ROOT=Path('.')
def read(p): return (ROOT/p).read_text()
def write(p,s): (ROOT/p).write_text(s)
def once(p,old,new):
    s=read(p); n=s.count(old)
    if n!=1: raise SystemExit(f'{p}: expected 1 match, got {n}: {old[:120]!r}')
    write(p,s.replace(old,new,1))

# R4-A: Continuity mutations join the same durable HTTP idempotency/rate guard as core mutations.
p='includes/class-wca-ten-review-hardening.php'
once(p,
"\t\tif ( 0 === strpos( $route, '/wca/v1/future24/' ) || 0 === strpos( $route, '/wca/v1/continuity/' ) ) { return false; }",
"\t\t/* Future24 owns its own durable mutate() replay ledger. Continuity mutations\n\t\t * intentionally use this cross-cutting HTTP guard so every write has one\n\t\t * explicit Idempotency-Key, uniform abuse control, and mutation-status path. */\n\t\tif ( 0 === strpos( $route, '/wca/v1/future24/' ) ) { return false; }"
)
once(p,
"\t\t\t'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents)$#',\n\t\t\t'#^/wca/v1/clinics/[0-9]+/(?:submit-review|activate)$#',",
"\t\t\t'#^/wca/v1/appointment-refs/[0-9a-fA-F-]{36}/(?:transitions|payment-intents)$#',\n\t\t\t'#^/wca/v1/continuity/appointments/[0-9a-fA-F-]{36}/(?:intake(?:/submit)?|consents|followups)$#',\n\t\t\t'#^/wca/v1/continuity/followups/[0-9a-fA-F-]{36}/complete$#',\n\t\t\t'#^/wca/v1/clinics/[0-9]+/(?:submit-review|activate)$#',"
)

# R4-B: Replay claim must run before the optimistic-version guard, so a completed
# mutation can replay its durable response instead of being rejected as stale.
p='includes/class-wca-continuity-guards.php'
once(p,
"\t\tadd_filter( 'rest_pre_dispatch', array( __CLASS__, 'enforce_intake_version' ), 10, 3 );",
"\t\t/* Priority 20 is deliberate: the cross-cutting idempotency claim/replay guard\n\t\t * runs at priority 15 and must be able to return a completed replay before\n\t\t * optimistic-version validation sees an intentionally stale retry body. */\n\t\tadd_filter( 'rest_pre_dispatch', array( __CLASS__, 'enforce_intake_version' ), 20, 3 );"
)

# R4-C: Browser continuity mutations send a secure, stable-per-pending-operation key.
p='assets/js/continuity.js'
once(p,
"  var config = window.WCAContinuity;\n\n  function status(node, message, isError) {",
"""  var config = window.WCAContinuity;
  var pendingMutationKeys = Object.create(null);

  function mutationStorageKey(method, path) {
    var input = String(method || 'GET') + '|' + String(path || '');
    var a = 2166136261;
    var b = 2246822519;
    for (var i = 0; i < input.length; i += 1) {
      a ^= input.charCodeAt(i);
      a = Math.imul(a, 16777619);
      b ^= input.charCodeAt(i);
      b = Math.imul(b, 3266489917);
    }
    return 'wca_idem_' + (a >>> 0).toString(16) + (b >>> 0).toString(16);
  }

  function secureMutationKey() {
    if (window.crypto && typeof window.crypto.randomUUID === 'function') {
      return 'wca-continuity-' + window.crypto.randomUUID();
    }
    if (window.crypto && typeof window.crypto.getRandomValues === 'function') {
      var bytes = new Uint8Array(16);
      window.crypto.getRandomValues(bytes);
      return 'wca-continuity-' + Array.prototype.map.call(bytes, function (value) {
        return value.toString(16).padStart(2, '0');
      }).join('');
    }
    throw new Error('Secure mutation identity is unavailable in this browser.');
  }

  function pendingMutationKey(method, path) {
    var storageKey = mutationStorageKey(method, path);
    var key = pendingMutationKeys[storageKey] || '';
    if (!key) {
      try { key = window.sessionStorage ? window.sessionStorage.getItem(storageKey) || '' : ''; } catch (e) { key = ''; }
    }
    if (!key) {
      key = secureMutationKey();
      pendingMutationKeys[storageKey] = key;
      try { if (window.sessionStorage) window.sessionStorage.setItem(storageKey, key); } catch (e) { /* memory fallback remains authoritative for this page */ }
    }
    return { key: key, storageKey: storageKey };
  }

  function clearPendingMutation(state) {
    if (!state) return;
    delete pendingMutationKeys[state.storageKey];
    try { if (window.sessionStorage) window.sessionStorage.removeItem(state.storageKey); } catch (e) { /* no-op */ }
  }

  function status(node, message, isError) {"""
)
once(p,
"""  function request(path, options) {
    var opts = options || {};
    opts.credentials = 'same-origin';
    opts.headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.nonce
    }, opts.headers || {});
    return fetch(config.root + path.replace(/^\\//, ''), opts).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok) {
          var message = data && data.message ? data.message : 'The request could not be completed.';
          var error = new Error(message);
          error.status = response.status;
          error.data = data;
          throw error;
        }
        return data;
      });
    });
  }""",
"""  function request(path, options) {
    var opts = options || {};
    var method = String(opts.method || 'GET').toUpperCase();
    var mutation = ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1;
    var mutationState = null;
    opts.credentials = 'same-origin';
    opts.headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.nonce
    }, opts.headers || {});
    if (mutation && !opts.headers['Idempotency-Key']) {
      mutationState = pendingMutationKey(method, path);
      opts.headers['Idempotency-Key'] = mutationState.key;
    }
    return fetch(config.root + path.replace(/^\\//, ''), opts).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        var code = data && data.code ? String(data.code) : '';
        var ambiguous = !response.ok && (
          response.status >= 500 || response.status === 429 ||
          code === 'wca_idempotency_in_progress' || code === 'wca_idempotency_finalize_failed'
        );
        if (mutationState && !ambiguous) {
          clearPendingMutation(mutationState);
        }
        if (!response.ok) {
          var message = data && data.message ? data.message : 'The request could not be completed.';
          var error = new Error(message);
          error.status = response.status;
          error.data = data;
          throw error;
        }
        return data;
      });
    });
  }"""
)

# R4 permanent assertions.
p='tests/sixteenth-twenty-review-regressions.php'
s=read(p)
marker='if($fail){fwrite(STDERR,"T16 regression gate failed:'
if marker not in s: raise SystemExit('T16 test insertion marker missing')
checks="""t16h('R4 continuity routes join HTTP idempotency guard','includes/class-wca-ten-review-hardening.php','continuity/appointments/[0-9a-fA-F-]{36}');
t16h('R4 continuity completion joins HTTP idempotency guard','includes/class-wca-ten-review-hardening.php','continuity/followups/[0-9a-fA-F-]{36}/complete');
t16h('R4 Future24 retains its own mutation guard','includes/class-wca-ten-review-hardening.php',"strpos( $route, '/wca/v1/future24/' )");
t16h('R4 intake version guard runs after replay guard','includes/class-wca-continuity-guards.php',"enforce_intake_version' ), 20, 3");
t16h('R4 browser continuity sends idempotency header','assets/js/continuity.js',"'Idempotency-Key'");
t16h('R4 browser continuity persists ambiguous mutation key','assets/js/continuity.js','sessionStorage.setItem');
t16h('R4 browser mutation key uses secure randomness','assets/js/continuity.js','window.crypto.randomUUID');
"""
write(p,s.replace(marker,checks+marker,1))
