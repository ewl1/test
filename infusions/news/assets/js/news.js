document.addEventListener('DOMContentLoaded', function () {
  function insertText(textarea, value) {
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var current = textarea.value || '';
    textarea.value = current.slice(0, start) + value + current.slice(end);
    textarea.focus();
    if (typeof textarea.setSelectionRange === 'function') {
      var cursor = start + value.length;
      textarea.setSelectionRange(cursor, cursor);
    }
  }

  function insertBbcode(textarea, template) {
    var start = textarea.selectionStart || 0;
    var end = textarea.selectionEnd || 0;
    var current = textarea.value || '';
    var selected = current.slice(start, end);
    var closingIndex = template.indexOf(']');
    var openTag = closingIndex !== -1 ? template.slice(0, closingIndex + 1) : template;
    var closeTag = '';
    if (template.indexOf('[/') !== -1) {
      closeTag = template.slice(template.indexOf('[/'));
    }

    if (!closeTag) {
      insertText(textarea, template);
      return;
    }

    var inserted = openTag + selected + closeTag;
    textarea.value = current.slice(0, start) + inserted + current.slice(end);
    textarea.focus();
    if (typeof textarea.setSelectionRange === 'function') {
      var cursor = selected ? start + inserted.length : start + openTag.length;
      textarea.setSelectionRange(cursor, cursor);
    }
  }

  function initTinyMce(textarea) {
    if (!window.tinymce || !textarea || textarea.dataset.newsTinyReady === '1') {
      return;
    }

    textarea.dataset.newsTinyReady = '1';
    var config = {};
    try {
      config = JSON.parse(textarea.getAttribute('data-news-tinymce-config') || '{}');
    } catch (error) {
      config = {};
    }

    config.target = textarea;
    window.tinymce.init(config);
  }

  var textarea = document.querySelector('#news-summary[data-news-editor-mode]');
  if (textarea) {
    var mode = textarea.getAttribute('data-news-editor-mode') || 'bbcode';
    if (mode === 'tinymce' || mode === 'mixed') {
      initTinyMce(textarea);
    }
  }

  document.querySelectorAll('[data-news-editor-target]').forEach(function (button) {
    button.addEventListener('click', function () {
      var targetId = button.getAttribute('data-news-editor-target') || '';
      var textarea = document.getElementById(targetId);
      if (!textarea) {
        return;
      }

      var mode = textarea.getAttribute('data-news-editor-mode') || 'bbcode';
      if ((mode === 'tinymce' || mode === 'mixed') && window.tinymce) {
        var editor = window.tinymce.get(targetId);
        if (editor) {
          editor.focus();
          editor.insertContent(button.getAttribute('data-news-insert-html') || '');
          return;
        }
      }

      insertBbcode(textarea, button.getAttribute('data-news-insert-text') || '');
    });
  });
});
