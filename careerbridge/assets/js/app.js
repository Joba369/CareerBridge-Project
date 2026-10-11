/* CareerBridge – site-wide interactions (progressive enhancement: every
   feature also works without JavaScript through normal links and forms). */
(function () {
  'use strict';
  var $ = function (s, c) { return (c || document).querySelector(s); };
  var $$ = function (s, c) { return Array.prototype.slice.call((c || document).querySelectorAll(s)); };

  /* Toast */
  var toastTimer;
  window.cbToast = function (msg) {
    var t = $('#toast'); if (!t) return;
    t.textContent = msg; t.classList.add('show');
    clearTimeout(toastTimer); toastTimer = setTimeout(function () { t.classList.remove('show'); }, 2600);
  };

  /* Demo notice */
  var bar = $('#demoBar');
  if (bar) {
    try { if (localStorage.getItem('cb_demo_hidden') === '1') bar.hidden = true; } catch (e) {}
    var b = $('[data-dismiss-demo]', bar);
    if (b) b.addEventListener('click', function () { bar.hidden = true; try { localStorage.setItem('cb_demo_hidden', '1'); } catch (e) {} });
  }

  /* Mobile nav */
  var toggle = $('[data-nav-toggle]'), panel = $('#mobileNav');
  if (toggle && panel) {
    toggle.addEventListener('click', function () {
      var open = panel.classList.toggle('open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
      document.body.style.overflow = open ? 'hidden' : '';
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panel.classList.contains('open')) { toggle.click(); toggle.focus(); }
    });
  }

  /* Dropdowns */
  $$('[data-dropdown]').forEach(function (btn) {
    var dd = document.getElementById(btn.getAttribute('data-dropdown'));
    if (!dd) return;
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = dd.classList.toggle('open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) { if (!dd.contains(e.target)) { dd.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && dd.classList.contains('open')) { dd.classList.remove('open'); btn.setAttribute('aria-expanded', 'false'); btn.focus(); } });
  });

  /* Dismiss alerts */
  $$('[data-close-alert]').forEach(function (b) { b.addEventListener('click', function () { b.closest('.alert').remove(); }); });

  /* Confirm destructive actions */
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.matches('[data-confirm]') && !window.confirm(f.getAttribute('data-confirm'))) e.preventDefault();
  }, true);

  /* Save / unsave job (AJAX with graceful fallback) */
  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form.matches('[data-save-form]') || e.defaultPrevented) return;
    e.preventDefault();
    var btn = $('button', form); btn.disabled = true;
    fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
      .then(function (res) {
        btn.disabled = false;
        if (!res.ok || !res.d.ok) { cbToast(res.d.error || 'Could not save this job.'); return; }
        var saved = res.d.saved;
        $$('form[data-save-form] input[name="job_id"][value="' + res.d.job_id + '"]').forEach(function (inp) {
          var b = $('button', inp.form);
          b.classList.toggle('saved', saved);
          b.setAttribute('aria-pressed', saved ? 'true' : 'false');
          var lbl = b.getAttribute('aria-label') || '';
          var name = lbl.replace(/^(Save job: |Remove from saved jobs: )/, '');
          if (b.classList.contains('save-btn')) b.setAttribute('aria-label', (saved ? 'Remove from saved jobs: ' : 'Save job: ') + name);
          var txt = $('[data-save-text]', b); if (txt) txt.textContent = saved ? 'Saved' : 'Save job';
        });
        cbToast(saved ? 'Job saved to your list' : 'Removed from saved jobs');
        if (!saved && form.hasAttribute('data-remove-card')) { var card = form.closest('.job-card'); if (card) card.remove(); }
      })
      .catch(function () { btn.disabled = false; form.submit(); });
  });

  /* Filters: auto-submit on change (desktop) and mobile drawer */
  var filters = $('[data-filters]');
  if (filters) {
    var isMobile = function () { return window.matchMedia('(max-width: 960px)').matches; };
    filters.addEventListener('change', function (e) {
      if (isMobile()) return;
      if (e.target.matches('input[type="text"], input[type="search"]')) return;
      var page = $('input[name="page"]', filters); if (page) page.value = 1;
      filters.submit();
    });
    $$('[data-open-filters]').forEach(function (b) {
      b.addEventListener('click', function () { filters.classList.add('open'); document.body.style.overflow = 'hidden'; var h = $('h2', filters); if (h) { h.setAttribute('tabindex', '-1'); h.focus(); } });
    });
    $$('[data-close-filters]', filters).forEach(function (b) {
      b.addEventListener('click', function () { filters.classList.remove('open'); document.body.style.overflow = ''; });
    });
  }
  var sortSel = $('[data-sort]');
  if (sortSel) sortSel.addEventListener('change', function () { sortSel.form.submit(); });

  /* Division -> district dependent selects */
  var LOC = window.CB_LOCATIONS;
  $$('[data-division-select]').forEach(function (div) {
    var target = document.getElementById(div.getAttribute('data-target'));
    if (!target || !LOC) return;
    var fill = function () {
      var keep = target.value, d = div.value;
      var first = target.options[0] ? target.options[0].outerHTML : '<option value="">Any district</option>';
      var html = first;
      Object.keys(LOC).forEach(function (division) {
        if (d && d !== division) return;
        html += '<optgroup label="' + division + ' Division">';
        LOC[division].forEach(function (x) { html += '<option value="' + x.replace(/"/g, '&quot;') + '">' + x + '</option>'; });
        html += '</optgroup>';
      });
      target.innerHTML = html;
      if (keep && $('option[value="' + keep.replace(/"/g, '\\"') + '"]', target)) target.value = keep; else target.value = '';
    };
    div.addEventListener('change', fill);
    if (div.value) fill();
  });

  /* Character counters */
  $$('[data-maxlength-counter]').forEach(function (ta) {
    var out = document.getElementById(ta.getAttribute('data-maxlength-counter'));
    var max = parseInt(ta.getAttribute('maxlength'), 10);
    var upd = function () { if (out) out.textContent = ta.value.length + ' / ' + max; };
    ta.addEventListener('input', upd); upd();
  });

  /* Client-side validation summary: focus first invalid field */
  $$('form[data-validate]').forEach(function (f) {
    f.addEventListener('submit', function (e) {
      if (!f.checkValidity()) {
        e.preventDefault();
        var first = $(':invalid', f);
        $$(':invalid', f).forEach(function (el) { el.classList.add('is-invalid'); });
        if (first) { first.focus(); first.reportValidity && first.reportValidity(); }
      }
    });
    f.addEventListener('input', function (e) { if (e.target.checkValidity && e.target.checkValidity()) e.target.classList.remove('is-invalid'); });
  });
})();
