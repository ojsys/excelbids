/*
 * ExcelBids page builder.
 *
 * Progressive enhancement only — every action is a real form POST, so the
 * builder still works with this file blocked. This adds the conveniences:
 * collapsing panels, the block picker, repeater rows and the WYSIWYG editor.
 */
(function () {
  'use strict';

  // ---------------------------------------------------------------------
  // Collapsing block panels
  // ---------------------------------------------------------------------
  document.addEventListener('click', function (event) {
    var toggle = event.target.closest('[data-bb-toggle]');
    if (!toggle) return;

    var block = toggle.closest('.bb-block');
    var body = block && block.querySelector('.bb-block-body');
    if (!body) return;

    var open = body.hasAttribute('hidden');
    if (open) {
      body.removeAttribute('hidden');
    } else {
      body.setAttribute('hidden', '');
    }
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    block.classList.toggle('is-open', open);
  });

  // Open the block the URL points at, so saving returns you to your place.
  if (window.location.hash && window.location.hash.indexOf('#block-') === 0) {
    var target = document.querySelector(window.location.hash);
    if (target) {
      var toggle = target.querySelector('[data-bb-toggle]');
      if (toggle) toggle.click();
      target.scrollIntoView({ block: 'center' });
    }
  }

  // ---------------------------------------------------------------------
  // Block picker
  // ---------------------------------------------------------------------
  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-bb-picker]');

    // Clicking anywhere else closes any open picker.
    if (!button) {
      if (!event.target.closest('.bb-picker')) {
        document.querySelectorAll('.bb-picker:not([hidden])').forEach(function (el) {
          el.setAttribute('hidden', '');
        });
      }
      return;
    }

    var picker = document.getElementById('picker-' + button.getAttribute('data-bb-picker'));
    if (!picker) return;

    var isOpen = !picker.hasAttribute('hidden');
    document.querySelectorAll('.bb-picker:not([hidden])').forEach(function (el) {
      el.setAttribute('hidden', '');
    });
    if (isOpen) return;

    picker.removeAttribute('hidden');
    var first = picker.querySelector('.bb-picker-item');
    if (first) first.focus();
  });

  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.bb-picker:not([hidden])').forEach(function (el) {
      el.setAttribute('hidden', '');
    });
  });

  // ---------------------------------------------------------------------
  // Repeater rows
  // ---------------------------------------------------------------------

  /* Rewrite the [n] index in every input name so PHP receives a clean array. */
  function reindexRepeater(repeater) {
    var name = repeater.getAttribute('data-name');
    repeater.querySelectorAll('[data-bb-row]').forEach(function (row, index) {
      row.querySelectorAll('input, select, textarea').forEach(function (input) {
        var attr = input.getAttribute('name');
        if (!attr) return;
        input.setAttribute(
          'name',
          attr.replace(
            new RegExp('settings\\[' + name + '\\]\\[\\d+\\]'),
            'settings[' + name + '][' + index + ']'
          )
        );
      });
    });
  }

  document.addEventListener('click', function (event) {
    var addButton = event.target.closest('[data-bb-row-add]');
    if (addButton) {
      var repeater = addButton.closest('[data-bb-repeater]');
      var rows = repeater.querySelector('.bb-rows');
      var last = rows.lastElementChild;
      if (!last) return;

      var clone = last.cloneNode(true);
      clone.querySelectorAll('input, select, textarea').forEach(function (input) {
        if (input.type === 'checkbox') {
          input.checked = false;
        } else if (input.tagName === 'SELECT') {
          input.selectedIndex = 0;
        } else {
          input.value = '';
        }
        // Ids must stay unique for the labels to keep working.
        if (input.id) input.id = input.id + '-' + Date.now();
      });
      clone.querySelectorAll('label[for]').forEach(function (label) {
        var field = clone.querySelector('#' + CSS.escape(label.getAttribute('for')));
        if (!field) label.removeAttribute('for');
      });

      rows.appendChild(clone);
      reindexRepeater(repeater);

      var firstInput = clone.querySelector('input[type="text"], textarea');
      if (firstInput) firstInput.focus();
      return;
    }

    var removeButton = event.target.closest('[data-bb-row-remove]');
    if (removeButton) {
      var row = removeButton.closest('[data-bb-row]');
      var owner = removeButton.closest('[data-bb-repeater]');
      if (!row || !owner) return;

      // Never leave a repeater with nothing to type into.
      if (owner.querySelectorAll('[data-bb-row]').length <= 1) {
        row.querySelectorAll('input, select, textarea').forEach(function (input) {
          if (input.type === 'checkbox') { input.checked = false; } else { input.value = ''; }
        });
        return;
      }

      row.remove();
      reindexRepeater(owner);
    }
  });

  // ---------------------------------------------------------------------
  // WYSIWYG editor
  //
  // Built on contenteditable + execCommand. Deprecated on paper, but it is
  // the only rich-text mechanism every browser supports without a library,
  // and this project ships no dependencies. Output is sanitised server-side
  // regardless, so the editor is a convenience, never a trust boundary.
  // ---------------------------------------------------------------------
  function syncEditor(area) {
    var textarea = document.getElementById(area.getAttribute('data-target'));
    if (textarea) textarea.value = area.innerHTML;
  }

  document.querySelectorAll('[data-wysiwyg]').forEach(function (wrapper) {
    var area = wrapper.querySelector('.wysiwyg-area');
    if (!area) return;

    area.addEventListener('input', function () { syncEditor(area); });
    area.addEventListener('blur', function () { syncEditor(area); });

    // Paste as plain text: pasting from Word otherwise drags in a mess of
    // markup that the server would strip anyway.
    area.addEventListener('paste', function (event) {
      event.preventDefault();
      var text = (event.clipboardData || window.clipboardData).getData('text/plain');
      document.execCommand('insertText', false, text);
      syncEditor(area);
    });

    wrapper.querySelectorAll('.wysiwyg-toolbar button').forEach(function (button) {
      // Keep the selection: mousedown would otherwise blur the editor first.
      button.addEventListener('mousedown', function (event) { event.preventDefault(); });

      button.addEventListener('click', function () {
        var command = button.getAttribute('data-cmd');
        var value = button.getAttribute('data-value') || null;

        area.focus();

        if (command === 'createLink') {
          var url = window.prompt('Link address (https://… , /about, or mailto:someone@example.com)');
          if (!url) return;
          if (!/^(https?:\/\/|mailto:|tel:|\/|#)/i.test(url)) url = 'https://' + url;
          document.execCommand('createLink', false, url);
        } else if (command === 'formatBlock') {
          document.execCommand('formatBlock', false, '<' + value + '>');
        } else {
          document.execCommand(command, false, value);
        }

        syncEditor(area);
      });
    });
  });

  // Belt and braces: sync every editor on submit, in case blur never fired.
  document.addEventListener('submit', function (event) {
    event.target.querySelectorAll('.wysiwyg-area').forEach(syncEditor);
  }, true);
})();
