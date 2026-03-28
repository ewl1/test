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
});
