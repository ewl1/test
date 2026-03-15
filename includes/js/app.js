document.addEventListener('DOMContentLoaded', function () {
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
      const position = list.dataset.position;
      list.querySelectorAll('.panel-item').forEach(function (item, index) {
        const id = item.dataset.id;
        const posInput = document.querySelector('input[name="panels[' + id + '][position]"]');
        const sortInput = document.querySelector('input[name="panels[' + id + '][sort_order]"]');
        if (posInput) posInput.value = position;
        if (sortInput) sortInput.value = index + 1;
        const badge = item.querySelector('.panel-position-badge');
        if (badge) badge.textContent = position;
      });
    });
  }

  syncSortablePanels();
});
