from pathlib import Path
p=Path('assets/js/clinic.js'); s=p.read_text()

def one(old,new):
    global s
    if s.count(old)!=1: raise SystemExit(f'clinic.js anchor count {s.count(old)}: {old[:100]!r}')
    s=s.replace(old,new,1)

one("\t\tvar selectedHold = null;\n\t\tvar appointmentRequestKey = null;", """\t\tvar selectedHold = null;
\t\tvar appointmentRequestKey = null;
\t\tvar searchGeneration = 0;
\t\tvar holdGeneration = 0;

\t\tfunction invalidateSelection() {
\t\t\tholdGeneration += 1;
\t\t\tselectedHold = null;
\t\t\tappointmentRequestKey = null;
\t\t\tArray.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) {
\t\t\t\titem.setAttribute('aria-pressed', 'false');
\t\t\t});
\t\t}""")
one("\t\tsearchButton.addEventListener('click', async function () {\n\t\t\tsetStatus(root, (runtime.i18n && runtime.i18n.loading) || 'Loading…', false);\n\t\t\tslotsNode.replaceChildren();\n\t\t\tselectedHold = null;\n\t\t\tappointmentRequestKey = null;", """\t\tArray.prototype.forEach.call(['service_ref', 'date_from', 'timezone'], function (name) {
\t\t\tvar field = form.elements[name];
\t\t\tif (field) field.addEventListener('change', invalidateSelection);
\t\t});

\t\tsearchButton.addEventListener('click', async function () {
\t\t\tvar generation = ++searchGeneration;
\t\t\tinvalidateSelection();
\t\t\tsearchButton.disabled = true;
\t\t\tsetStatus(root, (runtime.i18n && runtime.i18n.loading) || 'Loading…', false);
\t\t\tslotsNode.replaceChildren();""")
one("\t\t\t\tvar result = await api('slots?' + params.toString());\n\t\t\t\tif (!result.slots || !result.slots.length) {", """\t\t\t\tvar result = await api('slots?' + params.toString());
\t\t\t\tif (generation !== searchGeneration) return;
\t\t\t\tif (!result.slots || !result.slots.length) {""")
one("""\t\t\t\t\tbutton.addEventListener('click', async function () {
\t\t\t\t\t\tArray.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) { item.setAttribute('aria-pressed', 'false'); });
\t\t\t\t\t\tbutton.disabled = true;
\t\t\t\t\t\ttry {
\t\t\t\t\t\t\tselectedHold = await api('slot-holds', {method: 'POST', body: JSON.stringify({""", """\t\t\t\t\tbutton.addEventListener('click', async function () {
\t\t\t\t\t\tvar requestGeneration = ++holdGeneration;
\t\t\t\t\t\tselectedHold = null;
\t\t\t\t\t\tappointmentRequestKey = null;
\t\t\t\t\t\tArray.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) { item.setAttribute('aria-pressed', 'false'); item.disabled = true; });
\t\t\t\t\t\ttry {
\t\t\t\t\t\t\tvar holdResult = await api('slot-holds', {method: 'POST', body: JSON.stringify({""")
one("""\t\t\t\t\t\t\t\tidempotency_key: holdRequestKey
\t\t\t\t\t\t\t})});
\t\t\t\t\t\t\tbutton.setAttribute('aria-pressed', 'true');
\t\t\t\t\t\t\tappointmentRequestKey = uuid();""", """\t\t\t\t\t\t\t\tidempotency_key: holdRequestKey
\t\t\t\t\t\t\t})});
\t\t\t\t\t\t\tif (requestGeneration !== holdGeneration) return;
\t\t\t\t\t\t\tselectedHold = holdResult;
\t\t\t\t\t\t\tbutton.setAttribute('aria-pressed', 'true');
\t\t\t\t\t\t\tappointmentRequestKey = uuid();""")
one("\t\t\t\t\t\t} catch (error) {\n\t\t\t\t\t\t\tsetStatus(root, error.message, true);\n\t\t\t\t\t\t} finally { button.disabled = false; }", """\t\t\t\t\t\t} catch (error) {
\t\t\t\t\t\t\tif (requestGeneration === holdGeneration) { selectedHold = null; appointmentRequestKey = null; setStatus(root, error.message, true); }
\t\t\t\t\t\t} finally {
\t\t\t\t\t\t\tif (requestGeneration === holdGeneration) Array.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) { item.disabled = false; });
\t\t\t\t\t\t}""")
one("\t\t\t} catch (error) { setStatus(root, error.message, true); }\n\t\t});", """\t\t\t} catch (error) {
\t\t\t\tif (generation === searchGeneration) setStatus(root, error.message, true);
\t\t\t} finally {
\t\t\t\tif (generation === searchGeneration) searchButton.disabled = false;
\t\t\t}
\t\t});""")
p.write_text(s)
