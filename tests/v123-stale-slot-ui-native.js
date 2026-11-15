'use strict';
// Exact File08 UI behavior regression without external dependencies or network.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname, '../assets/js/clinic.js'), 'utf8');

const calls = [], domHandlers = new Map();
let keys = 0;
const classes = () => ({toggle() {}, add() {}, remove() {}});
function element() {
  const handlers = new Map();
  return {
    handlers, dataset: {}, disabled: false, textContent: '', classList: classes(),
    addEventListener(name, fn) { handlers.set(name, fn); },
    setAttribute() {}, focus() {},
    trigger(name, event) { const fn = handlers.get(name); assert.ok(fn, 'missing ' + name + ' handler'); return fn(event); }
  };
}
const slotsNode = Object.assign(element(), {
  children: [],
  replaceChildren() { this.children = []; this.textContent = ''; },
  appendChild(child) { this.children.push(child); },
  querySelectorAll(selector) { assert.equal(selector, '.wca-slot'); return this.children; }
});
const searchButton = element();
const statusNode = element();
const submitButton = element();
const serviceField = Object.assign(element(), {
  value: 'svc-1', selectedIndex: 0,
  options: [{dataset: {practitionerRef: 'doc-1', consultationType: 'offline'}}]
});
const dateField = Object.assign(element(), {value: '2026-11-25'});
const tzField = Object.assign(element(), {value: 'UTC'});
const form = Object.assign(element(), {
  elements: {
    service_ref: serviceField, date_from: dateField, timezone: tzField,
    category: Object.assign(element(), {value: 'general'}),
    reason: Object.assign(element(), {value: ''}),
    privacy_consent: Object.assign(element(), {checked: true}),
    emergency_ack: Object.assign(element(), {checked: true}),
    telehealth_consent: Object.assign(element(), {checked: false})
  },
  querySelector(selector) { return selector === '[type="submit"]' ? submitButton : null; },
  reportValidity() { return true; },
  reset() {}
});
const servicePanels = [
  {dataset: {wcaServiceTermsRef: 'svc-1'}, open: false},
  {dataset: {wcaServiceTermsRef: 'svc-2'}, open: false}
];
const root = Object.assign(element(), {
  dataset: {clinicRef: 'clinic-1'},
  querySelectorAll(selector) {
    assert.equal(selector, '[data-wca-service-terms-ref]');
    return servicePanels;
  },
  querySelector(selector) {
    return ({
      '[data-wca-booking-form]': form, '[data-wca-search-slots]': searchButton,
      '[data-wca-slots]': slotsNode, '[data-wca-status]': statusNode
    })[selector] || null;
  }
});
const document = {
  addEventListener(name, cb) { domHandlers.set(name, cb); },
  querySelectorAll(selector) { return selector === '[data-wca-booking]' ? [root] : []; },
  createElement() { return element(); }
};
const fetch = async (url, options) => new Promise(resolve => calls.push({url, options, resolve}));
const context = {
  window: {wcaRuntime: {restUrl: '/wp-json/wca/v1/'}, crypto: {
    randomUUID() { return 'test-key-' + String(++keys).padStart(8, '0'); }
  }},
  document, Intl, Date, URLSearchParams, Uint8Array, Promise, FormData, fetch,
  console, setTimeout, clearTimeout
};
vm.runInNewContext(source, context, {filename: 'assets/js/clinic.js'});
domHandlers.get('DOMContentLoaded')();
const tick = async () => new Promise(resolve => setImmediate(resolve));
const response = data => ({ok:true, status:200, headers:{get:()=> 'application/json'}, json: async()=> data});
const slot = id => ({
  slot_ref:id, display_start:id, clinic_ref:'clinic-1', service_ref:'svc-1',
  practitioner_ref:'doc-1', rule_ref:'rule-1', freshness_version:1,
  start_utc:'2026-11-25 10:00:00', end_utc:'2026-11-25 10:30:00'
});
(async () => {
  assert.equal(servicePanels[0].open, true, 'selected service terms visible initially');
  assert.equal(servicePanels[1].open, false, 'other service terms collapsed initially');
  searchButton.trigger('click');
  assert.equal(calls.length, 1, 'first availability query sent');
  serviceField.value = 'svc-2'; serviceField.trigger('change');
  assert.equal(servicePanels[0].open, false, 'old service terms collapse after change');
  assert.equal(servicePanels[1].open, true, 'new service fees and policies are visible');
  calls[0].resolve(response({slots:[slot('old-service-slot')]}));
  await tick();
  assert.equal(slotsNode.children.length, 0, 'late response for old service must never render');
  assert.equal(searchButton.disabled, false, 'filter change must permit a fresh query');

  searchButton.trigger('click');
  assert.equal(calls.length, 2, 'second query sent');
  calls[1].resolve(response({slots:[slot('fresh-slot')]}));
  await tick();
  assert.equal(slotsNode.children.length, 1, 'fresh query may show slot');
  tzField.value = 'Asia/Karachi'; tzField.trigger('change');
  assert.equal(slotsNode.children.length, 0, 'slots from previous timezone must disappear');

  searchButton.trigger('click');
  calls[2].resolve(response({slots:[slot('another-slot')]}));
  await tick();
  assert.equal(slotsNode.children.length, 1);
  slotsNode.children[0].trigger('click');
  assert.equal(calls.length, 4, 'slot hold requested');
  dateField.value = '2026-11-26'; dateField.trigger('change');
  calls[3].resolve(response({hold_token:'old-hold-token'}));
  await tick();
  await form.trigger('submit', {preventDefault() {}});
  assert.equal(calls.length, 4, 'late hold cannot be submitted after filter change');
  assert.match(statusNode.textContent, /Choose and reserve an available time first/);
  process.stdout.write('PASS: 5 stale-search / changed-service-terms / late-hold UI regressions.\n');
})().catch(err => { console.error(err); process.exitCode = 1; });
