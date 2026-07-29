(function () {
	'use strict';

	function toggleReschedule(form) {
		var status = form.querySelector('select[name="status"]');
		var box = form.querySelector('.swc-reschedule, .swc-admin-reschedule');
		if (!status || !box) return;
		var active = status.value === 'reschedule-requested';
		box.hidden = !active;
		box.querySelectorAll('input,select').forEach(function (field) {
			field.required = active;
		});
	}

	function syncConsultationModes() {
		var doctor = document.getElementById('swc-doctor-select');
		var mode = document.getElementById('swc-consultation-type');
		if (!doctor || !mode) return;
		var selected = doctor.options[doctor.selectedIndex];
		var previous = mode.value;
		Array.prototype.forEach.call(mode.options, function (option) {
			if (!option.value) return;
			var enabled = option.value === 'online' ? selected && selected.dataset.online === '1' : selected && selected.dataset.inPerson === '1';
			option.disabled = !enabled;
			option.hidden = !enabled;
		});
		if (!mode.querySelector('option[value="' + previous + '"]:not([disabled])')) mode.value = '';
	}

	function useBrowserTimezone() {
		var select = document.getElementById('swc-patient-timezone');
		if (!select || typeof Intl === 'undefined' || !Intl.DateTimeFormat) return;
		try {
			var zone = Intl.DateTimeFormat().resolvedOptions().timeZone;
			if (zone && select.querySelector('option[value="' + CSS.escape(zone) + '"]')) select.value = zone;
		} catch (error) {
			// Keep the server-selected safe fallback.
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('.swc-doctor-form, .swc-admin td form').forEach(function (form) {
			var status = form.querySelector('select[name="status"]');
			if (!status) return;
			status.addEventListener('change', function () { toggleReschedule(form); });
			toggleReschedule(form);
		});
		var doctor = document.getElementById('swc-doctor-select');
		if (doctor) doctor.addEventListener('change', syncConsultationModes);
		syncConsultationModes();
		useBrowserTimezone();
	});
}());
