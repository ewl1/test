document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[onclick]').forEach(function (el) {
    if (el.hasAttribute('data-confirm-message')) {
      return;
    }

    var onclick = el.getAttribute('onclick') || '';
    if (onclick.indexOf('confirm(') === -1) {
      return;
    }

    var match = onclick.match(/confirm\((['"])(.*?)\1\)/);
    if (match && match[2]) {
      el.setAttribute('data-confirm-message', match[2]);
      el.removeAttribute('onclick');
    }
  });

  document.querySelectorAll('[data-confirm-message]').forEach(function (el) {
    el.addEventListener('click', function (event) {
      var message = el.getAttribute('data-confirm-message');
      if (message && !window.confirm(message)) {
        event.preventDefault();
        event.stopPropagation();
      }
    });
  });

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    if (window.bootstrap) new bootstrap.Tooltip(el);
  });

  document.querySelectorAll('[data-password-toggle]').forEach(function (toggle) {
    var selector = toggle.getAttribute('data-password-target') || 'input[type="password"]';

    function syncTargets() {
      var scope = toggle.closest('form') || document;
      scope.querySelectorAll(selector).forEach(function (input) {
        input.setAttribute('type', toggle.checked ? 'text' : 'password');
      });
    }

    toggle.addEventListener('change', syncTargets);
    syncTargets();
  });

  document.querySelectorAll('.sortable-panel-list').forEach(function (list) {
    if (window.Sortable) {
      new Sortable(list, {
        group: 'panels',
        animation: 150,
        onEnd: syncSortablePanels
      });
    }
  });

  function syncSortablePanels() {
    document.querySelectorAll('.sortable-panel-list').forEach(function (list) {
      var position = list.dataset.position;
      list.querySelectorAll('.panel-item').forEach(function (item, index) {
        var id = item.dataset.id;
        var posInput = document.querySelector('input[name="panels[' + id + '][position]"]');
        var sortInput = document.querySelector('input[name="panels[' + id + '][sort_order]"]');
        if (posInput) posInput.value = position;
        if (sortInput) sortInput.value = index + 1;
        var badge = item.querySelector('.panel-position-badge');
        if (badge) badge.textContent = position;
      });
    });
  }

  syncSortablePanels();

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-bbcode-target][data-bbcode-insert]');
    if (!button) {
      return;
    }

    var textarea = document.getElementById(button.getAttribute('data-bbcode-target') || '');
    if (!textarea) {
      return;
    }

    event.preventDefault();
    insertBbcode(textarea, button.getAttribute('data-bbcode-insert') || '');
  });

  function insertText(textarea, value) {
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var current = textarea.value;
    textarea.value = current.slice(0, start) + value + current.slice(end);
    textarea.focus();

    var cursor = start + value.length;
    if (typeof textarea.setSelectionRange === 'function') {
      textarea.setSelectionRange(cursor, cursor);
    }
  }

  function insertBbcode(textarea, template) {
    if (!template) {
      return;
    }

    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var current = textarea.value;
    var selected = current.slice(start, end);
    var pair = template.match(/^(\[[^\]]+\])(\[\/[^\]]+\])$/);

    if (!pair) {
      insertText(textarea, template);
      return;
    }

    var inserted = pair[1] + selected + pair[2];
    textarea.value = current.slice(0, start) + inserted + current.slice(end);
    textarea.focus();

    var cursor = selected ? start + inserted.length : start + pair[1].length;
    if (typeof textarea.setSelectionRange === 'function') {
      textarea.setSelectionRange(cursor, cursor);
    }
  }
});
