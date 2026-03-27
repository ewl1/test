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

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-editor-target][data-insert-text], [data-editor-target][data-smiley-code]');
    if (!button) {
      return;
    }

    var targetId = button.getAttribute('data-editor-target');
    var textarea = document.getElementById(targetId);
    if (!textarea) {
      return;
    }

    event.preventDefault();

    if (button.hasAttribute('data-smiley-code')) {
      insertText(textarea, ' ' + (button.getAttribute('data-smiley-code') || '') + ' ');
      return;
    }

    insertBbcode(textarea, button.getAttribute('data-insert-text') || '');
  });

  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    if (window.bootstrap) new bootstrap.Tooltip(el);
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

  function insertText(textarea, value) {
    if (!textarea) return;

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
    if (!textarea || !template) return;

    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var current = textarea.value;
    var selected = current.slice(start, end);

    var pair = detectBbcodePair(template);
    if (!pair) {
      insertText(textarea, template);
      return;
    }

    var inserted = pair.open + selected + pair.close;
    textarea.value = current.slice(0, start) + inserted + current.slice(end);
    textarea.focus();

    var cursorStart = selected ? start + inserted.length : start + pair.open.length;
    var cursorEnd = selected ? cursorStart : cursorStart;
    if (typeof textarea.setSelectionRange === 'function') {
      textarea.setSelectionRange(cursorStart, cursorEnd);
    }
  }

  function detectBbcodePair(template) {
    var match = template.match(/^(\[[^\]]+\])(\[\/[^\]]+\])$/);
    if (!match) {
      return null;
    }

    return {
      open: match[1],
      close: match[2]
    };
  }

  syncSortablePanels();
});
