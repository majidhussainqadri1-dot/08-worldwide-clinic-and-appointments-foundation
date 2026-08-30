(function () {
  'use strict';

  if (!window.WCAContinuity || !window.fetch) {
    return;
  }

  var config = window.WCAContinuity;
  function tr(message) {
    if (window.wp && window.wp.i18n && typeof window.wp.i18n.__ === 'function') return window.wp.i18n.__(String(message), 'worldwide-clinic-appointments');
    return String(message);
  }
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
    throw new Error(tr('Secure mutation identity is unavailable in this browser.'));
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

  function status(node, message, isError) {
    if (!node) return;
    node.textContent = message ? tr(message) : '';
    node.classList.toggle('is-error', !!isError);
    node.classList.toggle('is-success', !isError && !!message);
  }

  function request(path, options) {
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
    return fetch(config.root + path.replace(/^\//, ''), opts).then(function (response) {
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
          var message = data && data.message ? data.message : tr('The request could not be completed.');
          var error = new Error(message);
          error.status = response.status;
          error.data = data;
          throw error;
        }
        return data;
      });
    });
  }

  function formPayload(form) {
    var out = {};
    new FormData(form).forEach(function (value, key) {
      out[key] = String(value).trim();
    });
    return out;
  }

  function setField(form, name, value) {
    var field = form.querySelector('[name="' + name + '"]');
    if (field && typeof value !== 'undefined' && value !== null) {
      field.value = String(value);
    }
  }

  function loadIntake(root, readOnly) {
    var ref = root.getAttribute('data-appointment-ref');
    var form = root.querySelector('form');
    var statusNode = root.querySelector('[data-wca-status]');
    if (!ref) return Promise.resolve();
    return request('appointments/' + encodeURIComponent(ref) + '/intake', { method: 'GET' })
      .then(function (data) {
        if (!data || !data.payload) return;
        if (form && !readOnly) {
          Object.keys(data.payload).forEach(function (name) {
            setField(form, name, data.payload[name]);
          });
          if (data.record_version) {
            form.dataset.recordVersion = String(data.record_version);
          }
          status(statusNode, data.status === 'submitted' ? 'Submitted pre-visit information loaded.' : 'Saved draft loaded.', false);
          return;
        }
        var target = root.querySelector('[data-wca-intake-readonly]');
        if (target) {
          target.textContent = '';
          Object.keys(data.payload).forEach(function (name) {
            if (!data.payload[name]) return;
            var row = document.createElement('div');
            var strong = document.createElement('strong');
            strong.textContent = name.replace(/_/g, ' ') + ': ';
            row.appendChild(strong);
            row.appendChild(document.createTextNode(String(data.payload[name])));
            target.appendChild(row);
          });
        }
      })
      .catch(function (error) {
        if (error.status === 404) {
          if (readOnly) status(statusNode, 'No submitted pre-visit information is available.', false);
          return;
        }
        status(statusNode, error.message, true);
      });
  }

  function wirePrevisit(root) {
    var form = root.querySelector('form');
    var statusNode = root.querySelector('[data-wca-status]');
    var ref = root.getAttribute('data-appointment-ref');
    if (!form || !ref) return;

    loadIntake(root, false);

    root.querySelectorAll('[data-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-action');
        var isSubmit = action === 'submit';
        var payload = formPayload(form);
        if (form.dataset.recordVersion) {
          payload.expected_version = form.dataset.recordVersion;
        }
        if (!payload.reason) {
          status(statusNode, 'Please enter a short reason for the visit.', true);
          var reason = form.querySelector('[name="reason"]');
          if (reason) reason.focus();
          return;
        }
        root.querySelectorAll('button').forEach(function (b) { b.disabled = true; });
        status(statusNode, 'Saving securely…', false);
        var path = 'appointments/' + encodeURIComponent(ref) + '/intake' + (isSubmit ? '/submit' : '');
        request(path, { method: isSubmit ? 'POST' : 'PUT', body: JSON.stringify(payload) })
          .then(function (data) {
            if (data && data.record_version) {
              form.dataset.recordVersion = String(data.record_version);
            }
            status(statusNode, isSubmit ? 'Pre-visit information submitted securely.' : 'Draft saved securely.', false);
          })
          .catch(function (error) {
            status(statusNode, error.message, true);
            if (error.status === 409) {
              loadIntake(root, false);
            }
          })
          .finally(function () {
            root.querySelectorAll('button').forEach(function (b) { b.disabled = false; });
          });
      });
    });
  }

  function renderFollowups(root, items) {
    var list = root.querySelector('[data-wca-followup-list]');
    var statusNode = root.querySelector('[data-wca-status]');
    if (!list) return;
    list.textContent = '';
    if (!Array.isArray(items) || !items.length) {
      var empty = document.createElement('p');
      empty.textContent = tr('No active follow-up plan is available.');
      list.appendChild(empty);
      return;
    }
    items.forEach(function (item) {
      var article = document.createElement('article');
      article.className = 'wca-followup-item';
      var heading = document.createElement('h3');
      heading.textContent = item.plan && item.plan.purpose ? item.plan.purpose : tr('Follow-up');
      article.appendChild(heading);
      var due = document.createElement('p');
      due.textContent = tr('Due:') + ' ' + (item.due_at_utc || '—') + ' UTC';
      article.appendChild(due);
      if (item.plan && item.plan.instructions) {
        var instructions = document.createElement('p');
        instructions.textContent = item.plan.instructions;
        article.appendChild(instructions);
      }
      var resourcesData = item.plan && Array.isArray(item.plan.resources) ? item.plan.resources : [];
      if (resourcesData.length) {
        var resources = document.createElement('ul');
        resourcesData.forEach(function (resource) {
          var li = document.createElement('li');
          if (resource.url) {
            var a = document.createElement('a');
            a.href = resource.url;
            a.rel = 'noopener noreferrer';
            a.textContent = resource.ref || resource.type || tr('Educational resource');
            li.appendChild(a);
          } else {
            li.textContent = resource.ref || resource.type || tr('Educational resource');
          }
          resources.appendChild(li);
        });
        article.appendChild(resources);
      }
      list.appendChild(article);
    });
    status(statusNode, '', false);
  }

  function refreshFollowups(root) {
    var ref = root.getAttribute('data-appointment-ref');
    var statusNode = root.querySelector('[data-wca-status]');
    if (!ref) return Promise.resolve();
    status(statusNode, 'Loading follow-up plan…', false);
    return request('appointments/' + encodeURIComponent(ref) + '/followups', { method: 'GET' })
      .then(function (items) { renderFollowups(root, items); })
      .catch(function (error) { status(statusNode, error.message, true); });
  }

  function wireFollowups(root) {
    refreshFollowups(root);
    var form = root.querySelector('[data-wca-followup-create]');
    var ref = root.getAttribute('data-appointment-ref');
    var statusNode = root.querySelector('[data-wca-status]');
    if (!form || !ref) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var payload = formPayload(form);
      if (payload.due_at_utc) {
        payload.due_at_utc = payload.due_at_utc.replace('T', ' ') + (payload.due_at_utc.length === 16 ? ':00' : '');
      }
      if (payload.resource_ref || payload.resource_url) {
        payload.resources = [{ type: 'educational', ref: payload.resource_ref || '', url: payload.resource_url || '' }];
      }
      delete payload.resource_ref;
      delete payload.resource_url;
      status(statusNode, 'Creating follow-up plan…', false);
      form.querySelectorAll('button,input,textarea').forEach(function (el) { el.disabled = true; });
      request('appointments/' + encodeURIComponent(ref) + '/followups', { method: 'POST', body: JSON.stringify(payload) })
        .then(function () {
          form.reset();
          status(statusNode, 'Follow-up plan created.', false);
          return refreshFollowups(root);
        })
        .catch(function (error) { status(statusNode, error.message, true); })
        .finally(function () {
          form.querySelectorAll('button,input,textarea').forEach(function (el) { el.disabled = false; });
        });
    });
  }

  function scopeLabel(scope) {
    var labels = {
      teleconsult: tr('Teleconsultation / call context'),
      recording: tr('Recording consent'),
      messaging: tr('Clinic-linked messaging'),
      privacy_notice: tr('Current privacy notice'),
      followup: tr('Follow-up plan and reminders')
    };
    return labels[scope] || scope.replace(/_/g, ' ');
  }

  function wireConsents(root) {
    var ref = root.getAttribute('data-appointment-ref');
    var statusNode = root.querySelector('[data-wca-status]');
    var list = root.querySelector('[data-wca-consent-list]');
    if (!ref || !list) return Promise.resolve(null);
    status(statusNode, 'Loading consent status…', false);
    return request('appointments/' + encodeURIComponent(ref) + '/consents', { method: 'GET' })
      .then(function (data) {
        list.textContent = '';
        Object.keys(data.scopes || {}).forEach(function (scope) {
          var state = data.scopes[scope] || {};
          var row = document.createElement('div');
          row.className = 'wca-consent-row';
          var label = document.createElement('span');
          label.textContent = scopeLabel(scope) + ': ' + (state.status === 'granted' ? tr('Granted') : tr('Not granted'));
          row.appendChild(label);
          if (data.can_manage_consents) {
            var button = document.createElement('button');
            button.type = 'button';
            button.className = 'wca-button wca-button-secondary';
            button.textContent = state.status === 'granted' ? tr('Revoke') : tr('Grant');
            button.addEventListener('click', function () {
              button.disabled = true;
              var method = state.status === 'granted' ? 'DELETE' : 'POST';
              request('appointments/' + encodeURIComponent(ref) + '/consents', { method: method, body: JSON.stringify({ scope: scope }) })
                .then(function () { return wireConsents(root); })
                .catch(function (error) { status(statusNode, error.message, true); })
                .finally(function () { button.disabled = false; });
            });
            row.appendChild(button);
          }
          list.appendChild(row);
        });
        status(statusNode, '', false);
        return data;
      })
      .catch(function (error) {
        status(statusNode, error.message, true);
        return null;
      });
  }

  function buildAutoExperience(ref) {
    if (!ref || document.querySelector('[data-wca-auto-continuity]')) return;
    var shell = document.querySelector('.wca-shell');
    if (!shell) return;

    var wrapper = document.createElement('section');
    wrapper.className = 'wca-continuity-stack';
    wrapper.setAttribute('data-wca-auto-continuity', '');

    var consent = document.createElement('section');
    consent.className = 'wca-card wca-continuity';
    consent.setAttribute('data-wca-consents', '');
    consent.setAttribute('data-appointment-ref', ref);
    var consentTitle = document.createElement('h2');
    consentTitle.textContent = tr('Consultation consent and context');
    consent.appendChild(consentTitle);
    var consentIntro = document.createElement('p');
    consentIntro.textContent = tr('Consent is purpose-specific and may be withdrawn. Recording is never assumed from teleconsultation consent.');
    consent.appendChild(consentIntro);
    var consentList = document.createElement('div');
    consentList.setAttribute('data-wca-consent-list', '');
    consent.appendChild(consentList);
    var consentStatus = document.createElement('p');
    consentStatus.setAttribute('data-wca-status', '');
    consentStatus.setAttribute('role', 'status');
    consentStatus.setAttribute('aria-live', 'polite');
    consent.appendChild(consentStatus);
    wrapper.appendChild(consent);

    shell.appendChild(wrapper);

    wireConsents(consent).then(function (state) {
      if (!state) return;

      var intake = document.createElement('section');
      intake.className = 'wca-card wca-continuity';
      intake.setAttribute('data-appointment-ref', ref);
      var intakeTitle = document.createElement('h2');
      intakeTitle.textContent = tr('Pre-visit information');
      intake.appendChild(intakeTitle);
      var emergency = document.createElement('div');
      emergency.className = 'wca-alert';
      emergency.setAttribute('role', 'note');
      emergency.textContent = tr('Do not wait here for an emergency. For severe or life-threatening symptoms, seek qualified local emergency care now.');
      intake.appendChild(emergency);

      if (state.can_edit_intake) {
        intake.setAttribute('data-wca-previsit', '');
        var form = document.createElement('form');
        form.className = 'wca-form';
        [
          ['reason', 'Reason for visit', 1500, true],
          ['category', 'Category', 80, false],
          ['symptoms_summary', 'Short symptom summary', 3000, false],
          ['medications_summary', 'Current medicines summary', 2000, false],
          ['allergies_summary', 'Allergies or sensitivities', 1500, false],
          ['accessibility_needs', 'Accessibility or communication needs', 1000, false],
          ['notes', 'Other necessary pre-visit notes', 2000, false]
        ].forEach(function (spec) {
          var label = document.createElement('label');
          label.appendChild(document.createTextNode(spec[1]));
          var field = spec[0] === 'category' ? document.createElement('input') : document.createElement('textarea');
          field.name = spec[0];
          field.maxLength = spec[2];
          if (spec[3]) field.required = true;
          label.appendChild(field);
          form.appendChild(label);
        });
        var actions = document.createElement('div');
        actions.className = 'wca-actions';
        var save = document.createElement('button');
        save.type = 'button'; save.className = 'wca-button wca-button-secondary'; save.setAttribute('data-action', 'save'); save.textContent = tr('Save draft');
        var submit = document.createElement('button');
        submit.type = 'button'; submit.className = 'wca-button'; submit.setAttribute('data-action', 'submit'); submit.textContent = tr('Submit securely');
        actions.appendChild(save); actions.appendChild(submit); form.appendChild(actions);
        var intakeStatus = document.createElement('p');
        intakeStatus.setAttribute('data-wca-status', ''); intakeStatus.setAttribute('role', 'status'); intakeStatus.setAttribute('aria-live', 'polite'); form.appendChild(intakeStatus);
        intake.appendChild(form);
        wrapper.appendChild(intake);
        wirePrevisit(intake);
      } else {
        var readonly = document.createElement('div');
        readonly.setAttribute('data-wca-intake-readonly', '');
        intake.appendChild(readonly);
        var intakeStatusRead = document.createElement('p');
        intakeStatusRead.setAttribute('data-wca-status', ''); intakeStatusRead.setAttribute('role', 'status'); intakeStatusRead.setAttribute('aria-live', 'polite'); intake.appendChild(intakeStatusRead);
        wrapper.appendChild(intake);
        loadIntake(intake, true);
      }

      var follow = document.createElement('section');
      follow.className = 'wca-card wca-continuity';
      follow.setAttribute('data-wca-followups', '');
      follow.setAttribute('data-appointment-ref', ref);
      var followTitle = document.createElement('h2');
      followTitle.textContent = tr('Follow-up plan');
      follow.appendChild(followTitle);
      var followIntro = document.createElement('p');
      followIntro.textContent = tr('Follow-up is doctor-defined and may include approved educational resources. This surface does not generate treatment with AI.');
      follow.appendChild(followIntro);
      if (state.can_create_followup) {
        var create = document.createElement('form');
        create.className = 'wca-form';
        create.setAttribute('data-wca-followup-create', '');
        var dueLabel = document.createElement('label'); dueLabel.textContent = tr('Due date and time (UTC)');
        var due = document.createElement('input'); due.type = 'datetime-local'; due.name = 'due_at_utc'; due.required = true; dueLabel.appendChild(due); create.appendChild(dueLabel);
        var purposeLabel = document.createElement('label'); purposeLabel.textContent = tr('Purpose');
        var purpose = document.createElement('input'); purpose.name = 'purpose'; purpose.maxLength = 191; purpose.required = true; purposeLabel.appendChild(purpose); create.appendChild(purposeLabel);
        var instructionLabel = document.createElement('label'); instructionLabel.textContent = tr('Instructions');
        var instructions = document.createElement('textarea'); instructions.name = 'instructions'; instructions.maxLength = 5000; instructionLabel.appendChild(instructions); create.appendChild(instructionLabel);
        var refLabel = document.createElement('label'); refLabel.textContent = tr('Approved educational resource reference (optional)');
        var resourceRef = document.createElement('input'); resourceRef.name = 'resource_ref'; resourceRef.maxLength = 191; refLabel.appendChild(resourceRef); create.appendChild(refLabel);
        var urlLabel = document.createElement('label'); urlLabel.textContent = tr('Approved same-site resource URL (optional)');
        var resourceUrl = document.createElement('input'); resourceUrl.name = 'resource_url'; resourceUrl.type = 'url'; urlLabel.appendChild(resourceUrl); create.appendChild(urlLabel);
        var createButton = document.createElement('button'); createButton.type = 'submit'; createButton.className = 'wca-button'; createButton.textContent = tr('Create follow-up'); create.appendChild(createButton);
        follow.appendChild(create);
      }
      var followList = document.createElement('div'); followList.setAttribute('data-wca-followup-list', ''); followList.setAttribute('aria-live', 'polite'); follow.appendChild(followList);
      var followStatus = document.createElement('p'); followStatus.setAttribute('data-wca-status', ''); followStatus.setAttribute('role', 'status'); followStatus.setAttribute('aria-live', 'polite'); follow.appendChild(followStatus);
      wrapper.appendChild(follow);
      wireFollowups(follow);
    });
  }

  document.querySelectorAll('[data-wca-previsit]').forEach(wirePrevisit);
  document.querySelectorAll('[data-wca-followups]').forEach(wireFollowups);
  document.querySelectorAll('[data-wca-consents]').forEach(wireConsents);
  if (config.appointmentRef) {
    buildAutoExperience(config.appointmentRef);
  }
}());
