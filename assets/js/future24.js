(function () {
	'use strict';
	var cfg = window.WCAFuture24 || {};
	var rootUrl = String(cfg.root || '/wp-json/wca/v1/future24/').replace(/\/?$/, '/');
	var baseRest = String(cfg.baseRest || '/wp-json/wca/v1/').replace(/\/?$/, '/');
	var nonce = cfg.nonce || '';

	async function api(path, base) {
		var headers = {Accept: 'application/json'};
		if (nonce) headers['X-WP-Nonce'] = nonce;
		var response = await fetch((base || rootUrl) + String(path).replace(/^\//, ''), {credentials: 'same-origin', headers: headers});
		var data = await response.json();
		if (!response.ok) throw new Error(data && data.message ? data.message : 'The request could not be completed.');
		return data;
	}

	function text(node, value) { if (node) node.textContent = value || ''; }

	async function loadCenter(center) {
		var ref = String(center.dataset.appointmentRef || '').toLowerCase();
		var status = center.querySelector('[data-wca-status]');
		try {
			if (ref) {
				var ready = await api('appointments/' + encodeURIComponent(ref) + '/readiness');
				var readiness = center.querySelector('[data-wca-f24-readiness]');
				text(readiness, ready.ready ? 'Ready for appointment.' : 'Appointment has actions remaining before it is fully ready.');
			}
			var family = await api('family');
			var familyNode = center.querySelector('[data-wca-f24-family]');
			if (familyNode) {
				familyNode.replaceChildren();
				var heading = document.createElement('h3');
				heading.textContent = 'Family and guardian appointments';
				familyNode.appendChild(heading);
				var count = Array.isArray(family.appointments) ? family.appointments.length : 0;
				var p = document.createElement('p');
				p.textContent = family.guardian ? (count + ' authorized appointment(s) available.') : 'No verified guardian context is active for this account.';
				familyNode.appendChild(p);
			}
			text(status, 'Scheduling intelligence loaded.');
		} catch (error) {
			text(status, error.message);
			if (status) status.classList.add('is-error');
		}
	}

	function wireCalendarLinks() {
		Array.prototype.forEach.call(document.querySelectorAll('a[href*="/appointment-refs/"][href*="calendar.ics"]'), function (link) {
			var match = String(link.href).match(/\/appointment-refs\/([0-9a-fA-F-]{36})\/calendar\.ics/);
			if (!match) return;
			link.addEventListener('click', async function (event) {
				event.preventDefault();
				link.setAttribute('aria-busy', 'true');
				try {
					var signed = await api('calendar-links/' + encodeURIComponent(match[1].toLowerCase()), baseRest);
					if (!signed || !signed.url) throw new Error('Calendar download is unavailable.');
					window.location.assign(signed.url);
				} catch (error) {
					var card = link.closest('[data-wca-appointment-ref]');
					var status = card ? card.querySelector('[data-wca-status]') : null;
					text(status, error.message);
				} finally { link.removeAttribute('aria-busy'); }
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		Array.prototype.forEach.call(document.querySelectorAll('[data-wca-f24-center]'), loadCenter);
		wireCalendarLinks();
	});
}());
