/* ExcelBids — admin panel & client portal behaviour. No dependencies. */
(function () {
  'use strict';

  // --- Mobile sidebar ------------------------------------------------------
  var toggle = document.querySelector('.sidebar-toggle');
  var sidebar = document.querySelector('.sidebar');

  function closeSidebar() {
    if (!sidebar) return;
    sidebar.classList.remove('open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
    var backdrop = document.querySelector('.backdrop');
    if (backdrop) backdrop.remove();
  }

  if (toggle && sidebar) {
    toggle.addEventListener('click', function () {
      var open = sidebar.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');

      if (open) {
        var backdrop = document.createElement('div');
        backdrop.className = 'backdrop';
        backdrop.addEventListener('click', closeSidebar);
        document.body.appendChild(backdrop);
      } else {
        closeSidebar();
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeSidebar();
    });
  }

  // --- Destructive actions need an explicit confirmation -------------------
  document.addEventListener('submit', function (event) {
    var form = event.target;
    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
      event.preventDefault();
      return;
    }

    // Guard against double submission on slow connections.
    if (form.hasAttribute('data-guard-submit')) {
      var button = form.querySelector('button[type="submit"]');
      if (button) {
        setTimeout(function () {
          button.disabled = true;
          button.dataset.label = button.dataset.label || button.textContent;
          button.textContent = 'Saving…';
        }, 0);
        window.addEventListener('pageshow', function () {
          button.disabled = false;
          if (button.dataset.label) button.textContent = button.dataset.label;
        });
      }
    }
  });

  // --- Auto-submitting filter selects --------------------------------------
  document.querySelectorAll('[data-auto-submit]').forEach(function (el) {
    el.addEventListener('change', function () {
      if (el.form) el.form.submit();
    });
  });

  // --- Scroll a message thread to the newest message -----------------------
  var thread = document.querySelector('[data-scroll-bottom]');
  if (thread) thread.scrollTop = thread.scrollHeight;

  // --- Textareas that grow with their content ------------------------------
  document.querySelectorAll('textarea[data-autogrow]').forEach(function (area) {
    function grow() {
      area.style.height = 'auto';
      area.style.height = Math.min(area.scrollHeight + 2, 420) + 'px';
    }
    area.addEventListener('input', grow);
    grow();
  });

  // --- Outcome fields only matter once a bid is won or lost ----------------
  var statusSelect = document.querySelector('[data-outcome-toggle]');
  if (statusSelect) {
    var outcomeBlock = document.getElementById('outcome-fields');
    function syncOutcome() {
      if (!outcomeBlock) return;
      var decided = statusSelect.value === 'won' || statusSelect.value === 'lost';
      outcomeBlock.style.display = decided ? '' : 'none';
    }
    statusSelect.addEventListener('change', syncOutcome);
    syncOutcome();
  }

  // --- Show the chosen filename next to a file input ------------------------
  document.querySelectorAll('input[type="file"][data-filename]').forEach(function (input) {
    var target = document.querySelector(input.getAttribute('data-filename'));
    if (!target) return;
    input.addEventListener('change', function () {
      target.textContent = input.files && input.files.length
        ? input.files[0].name
        : 'No file selected';
    });
  });
})();
