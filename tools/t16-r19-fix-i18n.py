from pathlib import Path

DOMAIN = 'worldwide-clinic-appointments'

def one(path, old, new):
    p=Path(path); s=p.read_text()
    if s.count(old)!=1:
        raise SystemExit(f'{path}: anchor count {s.count(old)} for {old[:100]!r}')
    p.write_text(s.replace(old,new,1))

# Core booking client.
p=Path('assets/js/clinic.js'); s=p.read_text()
old="\tvar nonce = runtime.nonce || '';"
new="""\tvar nonce = runtime.nonce || '';
\tfunction tr(message) {
\t\tif (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') return window.wp.i18n.__(String(message), 'worldwide-clinic-appointments');
\t\treturn String(message);
\t}"""
if s.count(old)!=1: raise SystemExit('clinic tr anchor mismatch')
s=s.replace(old,new,1)
s=s.replace("throw new Error('Secure replay-key generation is unavailable in this browser.')", "throw new Error(tr('Secure replay-key generation is unavailable in this browser.'))")
s=s.replace("(runtime.i18n && runtime.i18n.error) || 'The request could not be completed.'", "(runtime.i18n && runtime.i18n.error) || tr('The request could not be completed.')")
s=s.replace("node.textContent = message || '';", "node.textContent = message ? tr(message) : '';")
s=s.replace("slotsNode.textContent = 'No available times were found.';", "slotsNode.textContent = tr('No available times were found.');")
s=s.replace("window.confirm('Continue with “' + next.replace(/_/g, ' ') + '”?')", "window.confirm(tr('Continue with') + ' “' + next.replace(/_/g, ' ') + '”?')")
s=s.replace("setStatus(root, 'Appointment request submitted. Reference: ' + result.public_ref, false);", "setStatus(root, tr('Appointment request submitted. Reference:') + ' ' + result.public_ref, false);")
s=s.replace("setStatus(card, 'Appointment updated to ' + result.status.replace(/_/g, ' ') + '.', false);", "setStatus(card, tr('Appointment updated to') + ' ' + result.status.replace(/_/g, ' ') + '.', false);")
p.write_text(s)

# Continuity client: status messages plus all directly rendered static labels.
p=Path('assets/js/continuity.js'); s=p.read_text()
old="  var config = window.WCAContinuity;"
new="""  var config = window.WCAContinuity;
  function tr(message) {
    if (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') return window.wp.i18n.__(String(message), 'worldwide-clinic-appointments');
    return String(message);
  }"""
if s.count(old)!=1: raise SystemExit('continuity tr anchor mismatch')
s=s.replace(old,new,1)
s=s.replace("throw new Error('Secure mutation identity is unavailable in this browser.')", "throw new Error(tr('Secure mutation identity is unavailable in this browser.'))")
s=s.replace("node.textContent = message || '';", "node.textContent = message ? tr(message) : '';")
s=s.replace(": 'The request could not be completed.';", ": tr('The request could not be completed.');")
replacements={
"empty.textContent = 'No active follow-up plan is available.';":"empty.textContent = tr('No active follow-up plan is available.');",
"item.plan && item.plan.purpose ? item.plan.purpose : 'Follow-up'":"item.plan && item.plan.purpose ? item.plan.purpose : tr('Follow-up')",
"due.textContent = 'Due: ' +":"due.textContent = tr('Due:') + ' ' +",
"resource.ref || resource.type || 'Educational resource'":"resource.ref || resource.type || tr('Educational resource')",
"teleconsult: 'Teleconsultation / call context'":"teleconsult: tr('Teleconsultation / call context')",
"recording: 'Recording consent'":"recording: tr('Recording consent')",
"messaging: 'Clinic-linked messaging'":"messaging: tr('Clinic-linked messaging')",
"privacy_notice: 'Current privacy notice'":"privacy_notice: tr('Current privacy notice')",
"followup: 'Follow-up plan and reminders'":"followup: tr('Follow-up plan and reminders')",
"(state.status === 'granted' ? 'Granted' : 'Not granted')":"(state.status === 'granted' ? tr('Granted') : tr('Not granted'))",
"state.status === 'granted' ? 'Revoke' : 'Grant'":"state.status === 'granted' ? tr('Revoke') : tr('Grant')",
"consentTitle.textContent = 'Consultation consent and context';":"consentTitle.textContent = tr('Consultation consent and context');",
"consentIntro.textContent = 'Consent is purpose-specific and may be withdrawn. Recording is never assumed from teleconsultation consent.';":"consentIntro.textContent = tr('Consent is purpose-specific and may be withdrawn. Recording is never assumed from teleconsultation consent.');",
"intakeTitle.textContent = 'Pre-visit information';":"intakeTitle.textContent = tr('Pre-visit information');",
"emergency.textContent = 'Do not wait here for an emergency. For severe or life-threatening symptoms, seek qualified local emergency care now.';":"emergency.textContent = tr('Do not wait here for an emergency. For severe or life-threatening symptoms, seek qualified local emergency care now.');",
"save.textContent = 'Save draft';":"save.textContent = tr('Save draft');",
"submit.textContent = 'Submit securely';":"submit.textContent = tr('Submit securely');",
"followTitle.textContent = 'Follow-up plan';":"followTitle.textContent = tr('Follow-up plan');",
"followIntro.textContent = 'Follow-up is doctor-defined and may include approved educational resources. This surface does not generate treatment with AI.';":"followIntro.textContent = tr('Follow-up is doctor-defined and may include approved educational resources. This surface does not generate treatment with AI.');",
"dueLabel.textContent = 'Due date and time (UTC)'":"dueLabel.textContent = tr('Due date and time (UTC)')",
"purposeLabel.textContent = 'Purpose'":"purposeLabel.textContent = tr('Purpose')",
"instructionLabel.textContent = 'Instructions'":"instructionLabel.textContent = tr('Instructions')",
"refLabel.textContent = 'Approved educational resource reference (optional)'":"refLabel.textContent = tr('Approved educational resource reference (optional)')",
"urlLabel.textContent = 'Approved same-site resource URL (optional)'":"urlLabel.textContent = tr('Approved same-site resource URL (optional)')",
"createButton.textContent = 'Create follow-up'":"createButton.textContent = tr('Create follow-up')",
}
for old,new in replacements.items():
    count=s.count(old)
    if count<1: raise SystemExit(f'continuity visible-string anchor missing: {old}')
    s=s.replace(old,new)
p.write_text(s)

# Future24 client.
p=Path('assets/js/future24.js'); s=p.read_text()
old="\tvar nonce = cfg.nonce || '';"
new="""\tvar nonce = cfg.nonce || '';
\tfunction tr(message) {
\t\tif (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') return window.wp.i18n.__(String(message), 'worldwide-clinic-appointments');
\t\treturn String(message);
\t}"""
if s.count(old)!=1: raise SystemExit('future24 tr anchor mismatch')
s=s.replace(old,new,1)
s=s.replace(": 'The request could not be completed.');", ": tr('The request could not be completed.'));")
s=s.replace("function text(node, value) { if (node) node.textContent = value || ''; }", "function text(node, value) { if (node) { node.textContent = value ? tr(value) : ''; node.classList.remove('is-error'); } }")
s=s.replace("heading.textContent = 'Family and guardian appointments';", "heading.textContent = tr('Family and guardian appointments');")
s=s.replace("(count + ' authorized appointment(s) available.')", "(count + ' ' + tr('authorized appointment(s) available.'))")
s=s.replace("'No verified guardian context is active for this account.'", "tr('No verified guardian context is active for this account.')")
s=s.replace("throw new Error('Calendar download is unavailable.')", "throw new Error(tr('Calendar download is unavailable.'))")
s=s.replace("throw new Error('Calendar download destination is not permitted.')", "throw new Error(tr('Calendar download destination is not permitted.'))")
p.write_text(s)
