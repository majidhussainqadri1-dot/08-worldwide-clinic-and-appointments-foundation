from pathlib import Path
R=Path('.')
def rd(p): return (R/p).read_text()
def wr(p,s): (R/p).write_text(s)
def once(p,a,b):
 s=rd(p); n=s.count(a)
 if n!=1: raise SystemExit(f'{p}: expected 1 got {n}: {a[:140]!r}')
 wr(p,s.replace(a,b,1))

p='assets/js/clinic.js'
once(p,"\tfunction uuid() {\n\t\tif (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();\n\t\treturn 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {\n\t\t\tvar r = Math.random() * 16 | 0;\n\t\t\treturn (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);\n\t\t});\n\t}","\tfunction uuid() {\n\t\tif (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();\n\t\tif (!window.crypto || typeof window.crypto.getRandomValues !== 'function') throw new Error('Secure replay-key generation is unavailable in this browser.');\n\t\tvar bytes = new Uint8Array(16);\n\t\twindow.crypto.getRandomValues(bytes);\n\t\tbytes[6] = (bytes[6] & 0x0f) | 0x40;\n\t\tbytes[8] = (bytes[8] & 0x3f) | 0x80;\n\t\tvar hex = Array.prototype.map.call(bytes, function (b) { return b.toString(16).padStart(2, '0'); }).join('');\n\t\treturn hex.slice(0,8)+'-'+hex.slice(8,12)+'-'+hex.slice(12,16)+'-'+hex.slice(16,20)+'-'+hex.slice(20);\n\t}")
once(p,"\t\tvar selectedHold = null;","\t\tvar selectedHold = null;\n\t\tvar appointmentRequestKey = null;")
once(p,"\t\t\tselectedHold = null;","\t\t\tselectedHold = null;\n\t\t\tappointmentRequestKey = null;")
# slot button gets a stable key for retries of the same displayed slot.
once(p,"\t\t\t\t\tbutton.setAttribute('aria-pressed', 'false');\n\t\t\t\t\tbutton.addEventListener('click', async function () {","\t\t\t\t\tbutton.setAttribute('aria-pressed', 'false');\n\t\t\t\t\tvar holdRequestKey = uuid();\n\t\t\t\t\tbutton.addEventListener('click', async function () {")
once(p,"\t\t\t\t\t\t\t\tidempotency_key: uuid()","\t\t\t\t\t\t\t\tidempotency_key: holdRequestKey")
once(p,"\t\t\t\t\t\t\tbutton.setAttribute('aria-pressed', 'true');\n\t\t\t\t\t\t\tsetStatus(root, 'Time reserved temporarily. Complete your request now.', false);","\t\t\t\t\t\t\tbutton.setAttribute('aria-pressed', 'true');\n\t\t\t\t\t\t\tappointmentRequestKey = uuid();\n\t\t\t\t\t\t\tsetStatus(root, 'Time reserved temporarily. Complete your request now.', false);")
once(p,"\t\t\ttry {\n\t\t\t\tvar result = await api('appointments', {method: 'POST', body: JSON.stringify({\n\t\t\t\t\thold_token: selectedHold.hold_token,\n\t\t\t\t\tidempotency_key: uuid(),","\t\t\ttry {\n\t\t\t\tif (!appointmentRequestKey) appointmentRequestKey = uuid();\n\t\t\t\tvar result = await api('appointments', {method: 'POST', body: JSON.stringify({\n\t\t\t\t\thold_token: selectedHold.hold_token,\n\t\t\t\t\tidempotency_key: appointmentRequestKey,")
once(p,"\t\t\t\tselectedHold = null;","\t\t\t\tselectedHold = null;\n\t\t\t\tappointmentRequestKey = null;")

p='assets/js/future24.js'
once(p,"\t\t\t\t\tif (!signed || !signed.url) throw new Error('Calendar download is unavailable.');\n\t\t\t\t\twindow.location.assign(signed.url);","\t\t\t\t\tif (!signed || !signed.url) throw new Error('Calendar download is unavailable.');\n\t\t\t\t\tvar target = new URL(String(signed.url), window.location.origin);\n\t\t\t\t\tif (target.origin !== window.location.origin) throw new Error('Calendar download destination is not permitted.');\n\t\t\t\t\twindow.location.assign(target.href);")

p='tests/fifteenth-twenty-review-regressions.php'; s=rd(p)
ins="""
t15h('R14 secure browser UUID fallback','assets/js/clinic.js','crypto.getRandomValues');
t15h('R14 stable slot-hold replay key','assets/js/clinic.js','idempotency_key: holdRequestKey');
t15h('R14 stable appointment replay key','assets/js/clinic.js','idempotency_key: appointmentRequestKey');
t15h('R14 calendar same-origin validation','assets/js/future24.js','target.origin !== window.location.origin');
"""
mark='if($fail){fwrite(STDERR,"T15 regression gate failed:'
if mark not in s: raise SystemExit('gate marker missing')
wr(p,s.replace(mark,ins+mark,1))
p='FIFTEENTH-TWENTY-REVIEW-EVIDENCE.md'; s=rd(p); s += """

## R14 — browser workflows / JavaScript / accessibility / deep-link review

R14 completed before correction. Browser replay keys used an insecure Math.random fallback; hold and appointment retries did not preserve the original semantic replay key across ambiguous retry; and signed calendar navigation did not enforce same-origin at the browser edge. The post-review batch uses Web Crypto only, gives each displayed slot and appointment intent a stable retry key until success/context change, and validates signed calendar destinations against the current origin.

R14 result: **SUPPORTED DEFECTS FOUND — corrected together after review completion; full retest required before R15.**
"""; wr(p,s)
