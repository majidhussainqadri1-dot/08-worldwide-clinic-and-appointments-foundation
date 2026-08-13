(function () {
	'use strict';

	var runtime = window.wcaRuntime || window.swcClinic || {};
	var restUrl = String(runtime.restUrl || '/wp-json/wca/v1/').replace(/\/?$/, '/');
	var nonce = runtime.nonce || '';
	function tr(message) {
		if (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') return window.wp.i18n.__(String(message), 'worldwide-clinic-appointments');
		return String(message);
	}

	function uuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') return window.crypto.randomUUID();
		if (!window.crypto || typeof window.crypto.getRandomValues !== 'function') throw new Error(tr('Secure replay-key generation is unavailable in this browser.'));
		var bytes = new Uint8Array(16);
		window.crypto.getRandomValues(bytes);
		bytes[6] = (bytes[6] & 0x0f) | 0x40;
		bytes[8] = (bytes[8] & 0x3f) | 0x80;
		var hex = Array.prototype.map.call(bytes, function (b) { return b.toString(16).padStart(2, '0'); }).join('');
		return hex.slice(0,8)+'-'+hex.slice(8,12)+'-'+hex.slice(12,16)+'-'+hex.slice(16,20)+'-'+hex.slice(20);
	}

	async function api(path, options) {
		options = options || {};
		var headers = Object.assign({'Accept': 'application/json'}, options.headers || {});
		if (nonce) headers['X-WP-Nonce'] = nonce;
		if (options.body && !(options.body instanceof FormData)) headers['Content-Type'] = 'application/json';
		var response = await fetch(restUrl + String(path).replace(/^\//, ''), Object.assign({credentials: 'same-origin', headers: headers}, options));
		var type = response.headers.get('content-type') || '';
		var data = type.indexOf('json') !== -1 ? await response.json() : await response.text();
		if (!response.ok) {
			var message = data && data.message ? data.message : (runtime.i18n && runtime.i18n.error) || tr('The request could not be completed.');
			var error = new Error(message);
			error.status = response.status;
			error.data = data;
			throw error;
		}
		return data;
	}

	function setStatus(root, message, isError) {
		var node = root.querySelector('[data-wca-status]');
		if (!node) return;
		node.textContent = message ? tr(message) : '';
		node.classList.toggle('is-error', !!isError);
		node.classList.toggle('is-success', !!message && !isError);
	}

	function query(form, name) {
		var el = form.elements[name];
		return el ? String(el.value || '').trim() : '';
	}

	function checked(form, name) {
		var el = form.elements[name];
		return !!(el && el.checked);
	}

	function selectedServiceType(form) {
		var select = form.elements.service_ref;
		var option = select && select.options[select.selectedIndex];
		return option ? String(option.dataset.consultationType || '').toLowerCase() : '';
	}

	function initBooking(root) {
		var form = root.querySelector('[data-wca-booking-form]');
		var searchButton = root.querySelector('[data-wca-search-slots]');
		var slotsNode = root.querySelector('[data-wca-slots]');
		if (!form || !searchButton || !slotsNode) return;
		var selectedHold = null;
		var appointmentRequestKey = null;
		var searchGeneration = 0;
		var holdGeneration = 0;

		function invalidateSelection() {
			holdGeneration += 1;
			selectedHold = null;
			appointmentRequestKey = null;
			Array.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) {
				item.setAttribute('aria-pressed', 'false');
			});
		}

		var tz = form.elements.timezone;
		if (tz && (!tz.value || tz.value === 'UTC')) {
			try { tz.value = Intl.DateTimeFormat().resolvedOptions().timeZone || tz.value; } catch (e) {}
		}

		Array.prototype.forEach.call(['service_ref', 'date_from', 'timezone'], function (name) {
			var field = form.elements[name];
			if (field) field.addEventListener('change', invalidateSelection);
		});

		searchButton.addEventListener('click', async function () {
			var generation = ++searchGeneration;
			invalidateSelection();
			searchButton.disabled = true;
			setStatus(root, (runtime.i18n && runtime.i18n.loading) || 'Loading…', false);
			slotsNode.replaceChildren();
			try {
				var serviceSelect = form.elements.service_ref;
				var selectedOption = serviceSelect && serviceSelect.options[serviceSelect.selectedIndex];
				var practitionerRef = selectedOption ? String(selectedOption.dataset.practitionerRef || '') : '';
				var params = new URLSearchParams({
					clinic_ref: String(root.dataset.clinicRef || ''),
					service_ref: query(form, 'service_ref'),
					practitioner_ref: practitionerRef,
					date_from: query(form, 'date_from'),
					date_to: query(form, 'date_from'),
					timezone: query(form, 'timezone'),
					limit: '48'
				});
				var result = await api('slots?' + params.toString());
				if (generation !== searchGeneration) return;
				if (!result.slots || !result.slots.length) {
					slotsNode.textContent = tr('No available times were found.');
					setStatus(root, '', false);
					return;
				}
				result.slots.forEach(function (slot) {
					var button = document.createElement('button');
					button.type = 'button';
					button.className = 'wca-slot';
					button.textContent = slot.display_start || slot.start_local || slot.start_utc;
					button.setAttribute('aria-pressed', 'false');
					var holdRequestKey = uuid();
					button.addEventListener('click', async function () {
						var requestGeneration = ++holdGeneration;
						selectedHold = null;
						appointmentRequestKey = null;
						Array.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) { item.setAttribute('aria-pressed', 'false'); item.disabled = true; });
						try {
							var holdResult = await api('slot-holds', {method: 'POST', body: JSON.stringify({
								clinic_ref: slot.clinic_ref,
								service_ref: slot.service_ref,
								practitioner_ref: slot.practitioner_ref,
								rule_ref: slot.rule_ref,
								slot_ref: slot.slot_ref,
								freshness_version: slot.freshness_version,
								start_utc: slot.start_utc,
								end_utc: slot.end_utc,
								idempotency_key: holdRequestKey
							})});
							if (requestGeneration !== holdGeneration) return;
							selectedHold = holdResult;
							button.setAttribute('aria-pressed', 'true');
							appointmentRequestKey = uuid();
							setStatus(root, 'Time reserved temporarily. Complete your request now.', false);
						} catch (error) {
							if (requestGeneration === holdGeneration) { selectedHold = null; appointmentRequestKey = null; setStatus(root, error.message, true); }
						} finally {
							if (requestGeneration === holdGeneration) Array.prototype.forEach.call(slotsNode.querySelectorAll('.wca-slot'), function (item) { item.disabled = false; });
						}
					});
					slotsNode.appendChild(button);
				});
				setStatus(root, 'Choose an available time.', false);
			} catch (error) {
				if (generation === searchGeneration) setStatus(root, error.message, true);
			} finally {
				if (generation === searchGeneration) searchButton.disabled = false;
			}
		});

		form.addEventListener('submit', async function (event) {
			event.preventDefault();
			if (!form.reportValidity()) return;
			if (!selectedHold || !selectedHold.hold_token) {
				setStatus(root, 'Choose and reserve an available time first.', true);
				return;
			}
			var consultationType = selectedServiceType(form);
			var remote = consultationType === 'online' || consultationType === 'hybrid';
			if (remote && !checked(form, 'telehealth_consent')) {
				setStatus(root, 'Remote consultation consent is required for the selected online or hybrid service.', true);
				var tele = form.elements.telehealth_consent;
				if (tele) tele.focus();
				return;
			}
			var submit = form.querySelector('[type="submit"]');
			submit.disabled = true;
			try {
				if (!appointmentRequestKey) appointmentRequestKey = uuid();
				var result = await api('appointments', {method: 'POST', body: JSON.stringify({
					hold_token: selectedHold.hold_token,
					idempotency_key: appointmentRequestKey,
					timezone: query(form, 'timezone'),
					category: query(form, 'category'),
					reason: query(form, 'reason'),
					telehealth_consent: remote ? checked(form, 'telehealth_consent') : false,
					privacy_consent: checked(form, 'privacy_consent'),
					emergency_acknowledged: checked(form, 'emergency_ack')
				})});
				setStatus(root, tr('Appointment request submitted. Reference:') + ' ' + result.public_ref, false);
				form.reset();
				slotsNode.replaceChildren();
				selectedHold = null;
				appointmentRequestKey = null;
			} catch (error) { setStatus(root, error.message, true); }
			finally { submit.disabled = false; }
		});
	}

	function initAppointment(card) {
		var ref = String(card.dataset.wcaAppointmentRef || '');
		if (!ref) return;
		Array.prototype.forEach.call(card.querySelectorAll('[data-wca-transition]'), function (button) {
			button.addEventListener('click', async function () {
				var next = button.dataset.wcaTransition;
				if (!window.confirm(tr('Continue with') + ' “' + next.replace(/_/g, ' ') + '”?')) return;
				button.disabled = true;
				try {
					var result = await api('appointment-refs/' + encodeURIComponent(ref) + '/transitions', {method: 'POST', body: JSON.stringify({
						next_status: next,
						idempotency_key: uuid(),
						expected_status: card.dataset.wcaStatus,
						expected_version: Number(card.dataset.wcaVersion || 0),
						reason_code: 'user_action'
					})});
					card.dataset.wcaStatus = result.status;
					card.dataset.wcaVersion = result.version || result.record_version || card.dataset.wcaVersion;
					setStatus(card, tr('Appointment updated to') + ' ' + result.status.replace(/_/g, ' ') + '.', false);
					window.setTimeout(function () { window.location.reload(); }, 700);
				} catch (error) { setStatus(card, error.message, true); button.disabled = false; }
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.forEach.call(document.querySelectorAll('[data-wca-booking]'), initBooking);
		Array.prototype.forEach.call(document.querySelectorAll('[data-wca-appointment-ref]'), initAppointment);
	});
}());