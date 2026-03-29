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

  document.querySelectorAll('[data-smiley-form]').forEach(function (form) {
    var codeInput = form.querySelector('[data-smiley-code-input]');
    var typeInput = form.querySelector('[data-smiley-type-input]');
    var emojiInput = form.querySelector('[data-smiley-emoji-input]');

    if (!codeInput || !typeInput || !emojiInput) {
      return;
    }

    var defaultMap = {
      ':)': '🙂',
      ';)': '😉',
      ':D': '😄',
      ':(': '🙁',
      ':P': '😛',
      '<3': '❤️'
    };

    defaultMap[':)'] = '\uD83D\uDE42';
    defaultMap[';)'] = '\uD83D\uDE09';
    defaultMap[':D'] = '\uD83D\uDE04';
    defaultMap[':('] = '\uD83D\uDE41';
    defaultMap[':|'] = '\uD83D\uDE10';
    defaultMap[':P'] = '\uD83D\uDE1B';
    defaultMap['<3'] = '\u2764\uFE0F';

    function suggestedEmojiForCode(rawCode) {
      var code = (rawCode || '').trim();
      if (!code) {
        return '';
      }

      if (Object.prototype.hasOwnProperty.call(defaultMap, code)) {
        return defaultMap[code];
      }

      if (/[^\x20-\x7E]/.test(code)) {
        return code;
      }

      return '';
    }

    function syncSmileyEmoji(force) {
      if (typeInput.value !== 'emoji') {
        return;
      }

      var current = (emojiInput.value || '').trim();
      var suggested = suggestedEmojiForCode(codeInput.value || '');

      if (!suggested) {
        if (force && emojiInput.dataset.autoFilled === '1') {
          emojiInput.value = '';
        }
        return;
      }

      if (force || current === '' || emojiInput.dataset.autoFilled === '1') {
        emojiInput.value = suggested;
        emojiInput.dataset.autoFilled = '1';
      }
    }

    emojiInput.addEventListener('input', function () {
      emojiInput.dataset.autoFilled = '0';
    });

    codeInput.addEventListener('input', function () {
      syncSmileyEmoji(false);
    });

    typeInput.addEventListener('change', function () {
      syncSmileyEmoji(true);
    });

    form.addEventListener('submit', function () {
      syncSmileyEmoji(true);
    });

    if ((emojiInput.value || '').trim() === '') {
      emojiInput.dataset.autoFilled = '1';
    }

    syncSmileyEmoji(true);
  });

  var panelsForm = document.querySelector('[data-panels-form]');
  var panelsFeedback = document.querySelector('[data-panels-feedback]');
  var panelsFeedbackText = document.querySelector('[data-panels-feedback-text]');
  var panelsFeedbackBadge = document.querySelector('[data-panels-feedback-badge]');
  var panelsSaveButton = document.querySelector('[data-panels-save-button]');
  var panelLayoutDirty = false;

  function setPanelListState(list) {
    if (!list) {
      return;
    }

    var items = list.querySelectorAll('.panel-item');
    var countBadge = list.closest('.admin-panel-column');
    if (countBadge) {
      countBadge = countBadge.querySelector('[data-panel-count]');
    }

    if (countBadge) {
      countBadge.textContent = String(items.length);
    }

    list.classList.toggle('is-empty', items.length === 0);
    var emptyState = list.querySelector('[data-empty-state]');
    if (emptyState) {
      emptyState.classList.toggle('d-none', items.length > 0);
    }
  }

  function setPanelFeedback(level, title, message) {
    if (!panelsFeedback) {
      return;
    }

    panelsFeedback.classList.remove('alert-info', 'alert-warning', 'alert-success');
    panelsFeedback.classList.add(level === 'dirty' ? 'alert-warning' : (level === 'saved' ? 'alert-success' : 'alert-info'));
    panelsFeedback.innerHTML = '<strong>' + title + '</strong> ' + message;

    if (panelsFeedbackBadge) {
      var badgeClass = level === 'dirty' ? 'text-bg-warning' : (level === 'saved' ? 'text-bg-success' : 'text-bg-secondary');
      var badgeLabel = level === 'dirty' ? 'Busena: neissaugota' : (level === 'saved' ? 'Busena: issaugota' : 'Busena: sinchronizuota');
      panelsFeedbackBadge.innerHTML = '<span class="badge ' + badgeClass + '">' + badgeLabel + '</span>';
    }

    if (panelsFeedbackText) {
      panelsFeedbackText.textContent = message;
    }
  }

  function setPanelLayoutDirty(message) {
    panelLayoutDirty = true;
    if (panelsForm) {
      panelsForm.dataset.dirty = '1';
    }

    if (panelsSaveButton) {
      panelsSaveButton.classList.add('admin-save-pulse');
    }

    setPanelFeedback('dirty', 'Neissaugoti pakeitimai.', message || 'Paneliu isdestymas buvo pakeistas. Issaugokite, kad nauja tvarka butu pritaikyta svetainei.');
  }

  function clearPanelLayoutDirty(message) {
    panelLayoutDirty = false;
    if (panelsForm) {
      panelsForm.dataset.dirty = '0';
    }

    if (panelsSaveButton) {
      panelsSaveButton.classList.remove('admin-save-pulse');
    }

    setPanelFeedback('saved', 'Viskas sinchronizuota.', message || 'Paneliu isdestymas atitinka dabartine issaugota tvarka.');
  }

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
        if (badge) {
          badge.textContent = position;
        }
      });

      setPanelListState(list);
    });
  }

  document.querySelectorAll('.sortable-panel-list').forEach(function (list) {
    setPanelListState(list);

    if (window.Sortable) {
      new Sortable(list, {
        group: 'panels',
        animation: 180,
        handle: '.admin-panel-handle',
        chosenClass: 'is-chosen',
        ghostClass: 'is-ghost',
        dragClass: 'is-dragging',
        onStart: function () {
          document.querySelectorAll('.sortable-panel-list').forEach(function (targetList) {
            targetList.classList.add('is-drop-target');
          });
        },
        onEnd: function () {
          syncSortablePanels();
          document.querySelectorAll('.sortable-panel-list').forEach(function (targetList) {
            targetList.classList.remove('is-drop-target');
          });
          setPanelLayoutDirty('Paneliu pozicijos arba eiles buvo pakeistos. Nepamirskite issaugoti isdestymo.');
        }
      });
    }
  });

  if (panelsForm) {
    panelsForm.addEventListener('change', function (event) {
      if (event.target && event.target.name && event.target.name.indexOf('panels[') === 0) {
        setPanelLayoutDirty('Paneliu nustatymai buvo pakeisti. Issaugokite isdestyma, kad pakeitimai neliktu tik sioje sesijoje.');
      }
    });

    panelsForm.addEventListener('submit', function () {
      panelLayoutDirty = false;
      if (panelsSaveButton) {
        panelsSaveButton.classList.remove('admin-save-pulse');
      }
      setPanelFeedback('saved', 'Issaugoma...', 'Paneliu isdestymas issaugomas. Po perkrovimo turetumete matyti atnaujinta tvarka.');
    });
  }

  window.addEventListener('beforeunload', function (event) {
    if (!panelLayoutDirty) {
      return;
    }

    event.preventDefault();
    event.returnValue = '';
  });

  syncSortablePanels();
  clearPanelLayoutDirty();

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
