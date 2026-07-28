/* ExcelBids — public site behaviour. No dependencies. */
(function () {
  'use strict';

  // --- FAQ accordion -------------------------------------------------------
  document.querySelectorAll('.faq-q').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var item = btn.closest('.faq-item');
      if (!item) return;
      var answer = item.querySelector('.faq-a');
      var isOpen = item.classList.contains('open');

      document.querySelectorAll('.faq-item.open').forEach(function (el) {
        el.classList.remove('open');
        var a = el.querySelector('.faq-a');
        if (a) a.style.maxHeight = null;
        var q = el.querySelector('.faq-q');
        if (q) q.setAttribute('aria-expanded', 'false');
      });

      if (!isOpen && answer) {
        item.classList.add('open');
        answer.style.maxHeight = answer.scrollHeight + 'px';
        btn.setAttribute('aria-expanded', 'true');
      }
    });
  });

  // Keep an open answer correctly sized when the viewport reflows.
  var resizeTimer;
  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(function () {
      var open = document.querySelector('.faq-item.open .faq-a');
      if (open) open.style.maxHeight = open.scrollHeight + 'px';
    }, 150);
  });

  // --- Mobile navigation ---------------------------------------------------
  var toggle = document.querySelector('.nav-toggle');
  var links = document.querySelector('.navlinks');
  if (toggle && links) {
    toggle.addEventListener('click', function () {
      var open = links.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    links.addEventListener('click', function (event) {
      if (event.target.tagName === 'A') {
        links.classList.remove('open');
        toggle.setAttribute('aria-expanded', 'false');
      }
    });
  }

  // --- Consultation form ---------------------------------------------------
  var form = document.querySelector('form[data-guard-submit]');
  if (form) {
    form.addEventListener('submit', function () {
      var button = form.querySelector('button[type="submit"]');
      if (button && !button.disabled) {
        button.disabled = true;
        button.dataset.label = button.textContent;
        button.textContent = 'Sending…';
        // Re-enable if the browser restores the page from bfcache.
        window.addEventListener('pageshow', function () {
          button.disabled = false;
          if (button.dataset.label) button.textContent = button.dataset.label;
        });
      }
    });
  }

  // --- Deadline field: never accept a date in the past ----------------------
  var deadline = document.querySelector('input[type="date"][data-min-today]');
  if (deadline && !deadline.min) {
    deadline.min = new Date().toISOString().split('T')[0];
  }
})();
