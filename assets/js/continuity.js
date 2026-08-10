(function () {
  'use strict';

  if (!window.WCAContinuity || !window.fetch) {
    return;
  }

  var config = window.WCAContinuity;

  function status(node, message, isError) {
    if (!node) return;
    node.textContent = message || '';
    node.classList.toggle('is-error', !!isError);
    node.classList.toggle('is-success', !isError && !!message);
  }

  function request(path, options) {
    var opts = options || {};
    opts.credentials = 'same-origin';
    opts.headers = Object.assign({
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.nonce
    }, opts.headers || {});
    return fetch(config.root + path.replace(/^\//, ''), opts).then(function (response) {
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
  }

  function formPayload(form) {
    var out = {};
    new FormData(form).forEach(function (value, key) {
      out[key] = String(value).trim();
    });
    return out;
  }

  function wirePrevisit(root) {
    var form = root.querySelector('form');
    var statusNode = root.querySelector('[data-wca-status]');
    var ref = root.getAttribute('data-appointment-ref');
    if (!form || !ref) return;

    root.querySelectorAll('[data-action]').forEach(function (button) {
      button.addEventListener('click', function () {
        var action = button.getAttribute('data-action');
        var isSubmit = action === 'submit';
        var payload = formPayload(form);
        if (!payload.reason) {
          status(statusNode, 'Please enter a short reason for the visit.', true);
          form.querySelector('[name="reason"]').focus();
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
      empty.textContent = 'No active follow-up plan is available.';
      list.appendChild(empty);
      return;
    }
    items.forEach(function (item) {
      var article = document.createElement('article');
      article.className = 'wca-followup-item';
      var heading = document.createElement('h3');
      heading.textContent = item.plan && item.plan.purpose ? item.plan.purpose : 'Follow-up';
      article.appendChild(heading);
      var due = document.createElement('p');
      due.textContent = 'Due: ' + (item.due_at_utc || '—') + ' UTC';
      article.appendChild(due);
      if (item.plan && item.plan.instructions) {
        var instructions = document.createElement('p');
        instructions.textContent = item.plan.instructions;
        article.appendChild(instructions);
      }
      if (Array.isArray(item.resources) && item.resources.length) {
        var resources = document.createElement('ul');
        item.resources.forEach(function (resource) {
          var li = document.createElement('li');
          if (resource.url) {
            var a = document.createElement('a');
            a.href = resource.url;
            a.rel = 'noopener noreferrer';
            a.textContent = resource.ref || resource.type || 'Educational resource';
            li.appendChild(a);
          } else {
            li.textContent = resource.ref || resource.type || 'Educational resource';
          }
          resources.appendChild(li);
        });
        article.appendChild(resources);
      }
      list.appendChild(article);
    });
    status(statusNode, '', false);
  }

  function wireFollowups(root) {
    var ref = root.getAttribute('data-appointment-ref');
    var statusNode = root.querySelector('[data-wca-status]');
    if (!ref) return;
    status(statusNode, 'Loading follow-up plan…', false);
    request('appointments/' + encodeURIComponent(ref) + '/followups', { method: 'GET' })
      .then(function (items) { renderFollowups(root, items); })
      .catch(function (error) { status(statusNode, error.message, true); });
  }

  document.querySelectorAll('[data-wca-previsit]').forEach(wirePrevisit);
  document.querySelectorAll('[data-wca-followups]').forEach(wireFollowups);
}());
