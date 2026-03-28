document.addEventListener('DOMContentLoaded', function () {
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-forum-editor-target][data-forum-insert-text], [data-forum-editor-target][data-forum-smiley-code]');
    if (!button) {
      return;
    }

    var textarea = document.getElementById(button.getAttribute('data-forum-editor-target') || '');
    if (!textarea) {
      return;
    }

    event.preventDefault();

    if (button.hasAttribute('data-forum-smiley-code')) {
      forumInsertText(textarea, ' ' + (button.getAttribute('data-forum-smiley-code') || '') + ' ');
      return;
    }

    forumInsertBbcode(textarea, button.getAttribute('data-forum-insert-text') || '');
  });

  function forumInsertText(textarea, value) {
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

  function forumInsertBbcode(textarea, template) {
    if (!template) {
      return;
    }

    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var current = textarea.value;
    var selected = current.slice(start, end);
    var pair = template.match(/^(\[[^\]]+\])(\[\/[^\]]+\])$/);

    if (!pair) {
      forumInsertText(textarea, template);
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
