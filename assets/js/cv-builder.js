/* CareerBridge CV Writing & Editing Hub – editor logic (no AI, runs fully in the browser). */
(function () {
  'use strict';
  var R = window.CVRender, SCHEMA = R.SCHEMA, esc = R.esc;
  var CTX = window.CV_CTX || { canSave: false, userKey: 'guest' };
  var IC = window.CV_ICONS || {};
  var STORE_KEY = 'cb_cv_v1_' + CTX.userKey;
  var TEMPLATES = ['classic', 'modern', 'minimal', 'compact'];

  var root = document.getElementById('cvb');
  if (!root) return;
  var sectionWrap = document.getElementById('sectionCards');
  var preview = document.getElementById('cvPreview');
  var scaleBox = document.getElementById('previewScale');
  var frame = document.getElementById('previewFrame');
  var statusEl = document.getElementById('saveStatus');
  var pageHint = document.getElementById('pageHint');

  var state = { template: 'classic', data: R.blankData() };
  var open = { summary: true, education: true };

  /* ---------------- Example CVs (fictional) ---------------- */
  var EXAMPLES = {
    fresher: {
      template: 'classic',
      data: {
        personal: { fullName: 'Nadia Islam', headline: 'Junior Web Developer (Fresher)', email: 'nadia.example@mail.com', phone: '01700-000000', location: 'Mirpur, Dhaka', linkedin: 'linkedin.com/in/nadia-example', website: 'github.com/nadia-example' },
        summary: 'Recent Computer Science graduate with hands-on experience building responsive websites using HTML, CSS, JavaScript and PHP. Completed three portfolio projects and a two-month internship. Looking for an entry-level web developer role where I can grow my skills in a supportive team.',
        sections: [
          { key: 'summary', title: 'Career Objective', visible: true },
          { key: 'education', title: 'Education', visible: true },
          { key: 'projects', title: 'Projects', visible: true },
          { key: 'experience', title: 'Experience', visible: true },
          { key: 'skills', title: 'Skills', visible: true },
          { key: 'training', title: 'Training & Certifications', visible: true },
          { key: 'languages', title: 'Languages', visible: true },
          { key: 'references', title: 'References', visible: true }
        ],
        education: [
          { degree: 'B.Sc. in Computer Science & Engineering', institution: 'Example University of Science & Technology', location: 'Dhaka', start: '2021', end: '2025', result: 'CGPA 3.52 / 4.00', details: 'Final-year project: an online appointment system for small clinics (PHP, MySQL).' },
          { degree: 'Higher Secondary Certificate (Science)', institution: 'Example College', location: 'Dhaka', start: '2018', end: '2020', result: 'GPA 5.00 / 5.00', details: '' }
        ],
        projects: [
          { name: 'Bazar List – grocery planner', tech: 'JavaScript, LocalStorage, CSS Grid', link: 'github.com/nadia-example/bazar-list', description: 'A mobile-friendly shopping list that groups items by shop and calculates the estimated total in taka. Used by 20+ friends and family members.' },
          { name: 'Coaching Centre Website', tech: 'HTML, CSS, PHP, MySQL', link: '', description: 'Built a website with a class schedule and an admission enquiry form for a local coaching centre; enquiries are stored in a database and emailed to the owner.' }
        ],
        experience: [
          { role: 'Web Development Intern', company: 'Example Software Studio', location: 'Dhaka', start: 'Jun 2024', end: 'Aug 2024', bullets: 'Converted 6 Figma page designs into responsive HTML/CSS templates\nFixed layout bugs reported by the QA team using browser DevTools\nLearned Git branching and code review in a team of 5 developers' },
          { role: 'Private Tutor (Part-time)', company: 'Self-employed', location: 'Dhaka', start: '2022', end: '2024', bullets: 'Taught mathematics and ICT to 4 secondary school students\nPrepared weekly practice sheets and progress notes for parents' }
        ],
        skills: [
          { group: 'Technical', items: 'HTML5, CSS3, JavaScript, PHP, MySQL, Git, Responsive design' },
          { group: 'Tools', items: 'VS Code, Figma (basic), Chrome DevTools, MS Office' },
          { group: 'Soft skills', items: 'Teamwork, Clear communication, Time management' }
        ],
        training: [
          { name: 'Web Design & Development Course (3 months)', provider: 'Example IT Training Institute', year: '2023' },
          { name: 'Responsive Web Design Certificate', provider: 'Online learning platform', year: '2022' }
        ],
        languages: [{ name: 'Bangla', level: 'Native' }, { name: 'English', level: 'Professional working proficiency' }],
        references: []
      }
    },
    experienced: {
      template: 'modern',
      data: {
        personal: { fullName: 'Rafiq Hasan', headline: 'Digital Marketing Executive · 5 years', email: 'rafiq.example@mail.com', phone: '01800-000000', location: 'Agrabad, Chattogram', linkedin: 'linkedin.com/in/rafiq-example', website: '' },
        summary: 'Digital marketing professional with 5 years of experience planning social media, search and email campaigns for retail and e-commerce brands in Bangladesh. Grew organic traffic by 120% and managed monthly ad budgets of Tk 3 lakh. Comfortable leading small teams and reporting results with clear data.',
        sections: [
          { key: 'summary', title: 'Professional Summary', visible: true },
          { key: 'experience', title: 'Work Experience', visible: true },
          { key: 'education', title: 'Education', visible: true },
          { key: 'projects', title: 'Selected Campaigns', visible: true },
          { key: 'skills', title: 'Skills', visible: true },
          { key: 'training', title: 'Certifications', visible: true },
          { key: 'languages', title: 'Languages', visible: true },
          { key: 'references', title: 'References', visible: true }
        ],
        experience: [
          { role: 'Digital Marketing Executive', company: 'Example Retail Group', location: 'Chattogram', start: 'Mar 2022', end: 'Present', bullets: 'Plan and run monthly social media and search campaigns for 3 brands\nIncreased online orders by 45% in 12 months through better targeting and landing pages\nManage a monthly ad budget of Tk 3,00,000 and report ROI to management\nSupervise 2 junior content creators and 1 designer' },
          { role: 'Marketing Associate', company: 'Example E-commerce Ltd.', location: 'Dhaka', start: 'Jan 2020', end: 'Feb 2022', bullets: 'Wrote and scheduled 30+ social posts per month in Bangla and English\nSet up email newsletters that reached 18,000 subscribers with a 24% open rate\nImproved product page SEO, growing organic traffic by 120%' }
        ],
        education: [
          { degree: 'BBA in Marketing', institution: 'Example University', location: 'Chattogram', start: '2015', end: '2019', result: 'CGPA 3.41 / 4.00', details: '' }
        ],
        projects: [
          { name: 'Eid Sale Campaign 2024', tech: 'Meta Ads, Google Ads, Email', link: '', description: 'Coordinated a 3-week campaign that delivered Tk 42 lakh in online sales, 30% above target.' },
          { name: 'Website SEO Refresh', tech: 'Google Search Console, Analytics', link: '', description: 'Audited 400 product pages, fixed technical issues and rewrote descriptions; first-page keywords grew from 35 to 110.' }
        ],
        skills: [
          { group: 'Marketing', items: 'Social media strategy, SEO, Google Ads, Meta Ads, Email marketing, Content planning' },
          { group: 'Analytics', items: 'Google Analytics, Search Console, Excel reporting' },
          { group: 'Leadership', items: 'Team coordination, Budget planning, Vendor communication' }
        ],
        training: [
          { name: 'Google Ads Search Certification', provider: 'Online certification', year: '2023' },
          { name: 'Digital Marketing Professional Course', provider: 'Example Training Centre', year: '2020' }
        ],
        languages: [{ name: 'Bangla', level: 'Native' }, { name: 'English', level: 'Fluent' }],
        references: [
          { name: 'Example Reference One', position: 'Head of Marketing', organization: 'Example Retail Group', contact: 'reference1@example.com' },
          { name: 'Example Reference Two', position: 'Associate Professor, Dept. of Marketing', organization: 'Example University', contact: '01900-000000' }
        ]
      }
    }
  };

  /* ---------------- Persistence ---------------- */
  function readLocal() {
    try { var v = JSON.parse(localStorage.getItem(STORE_KEY) || 'null'); return v && v.data ? v : null; } catch (e) { return null; }
  }
  var synced = true;
  function writeLocal() {
    try {
      localStorage.setItem(STORE_KEY, JSON.stringify({ template: state.template, data: state.data, ts: Date.now(), synced: synced }));
      return true;
    } catch (e) { return false; }
  }
  function setStatus(text, tone) {
    if (!statusEl) return;
    statusEl.className = 'save-status' + (tone ? ' ' + tone : '');
    statusEl.innerHTML = (tone === 'err' ? IC.alert : IC.check) + ' <span>' + esc(text) + '</span>';
  }
  function timeNow() { return new Date().toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }); }

  var saveTimer;
  function changed() {
    synced = false;
    clearTimeout(saveTimer);
    saveTimer = setTimeout(function () {
      if (writeLocal()) setStatus((CTX.canSave ? 'Draft saved in this browser · ' : 'Saved in this browser · ') + timeNow(), '');
      else setStatus('Could not save in this browser (storage full or disabled)', 'err');
    }, 400);
    schedulePreview();
  }

  function saveServer() {
    if (!CTX.canSave) return;
    var btn = document.getElementById('saveServer');
    if (btn) { btn.disabled = true; btn.setAttribute('aria-busy', 'true'); }
    setStatus('Saving to your account…', '');
    fetch(CTX.api, {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-Token': (window.CB && CB.csrf) || '' },
      body: JSON.stringify({ template: state.template, data: state.data })
    }).then(function (r) { return r.json().catch(function () { return { ok: false, error: 'Unexpected server response.' }; }); })
      .then(function (res) {
        if (res.ok) { synced = true; writeLocal(); setStatus('Saved to your account · ' + (res.saved_at || timeNow()), 'ok'); if (window.cbToast) cbToast('CV saved to your account.'); }
        else setStatus(res.error || 'Could not save. Please try again.', 'err');
      })
      .catch(function () { setStatus('Network error – your work is still saved in this browser.', 'err'); })
      .then(function () { if (btn) { btn.disabled = false; btn.removeAttribute('aria-busy'); } });
  }

  /* ---------------- Helpers ---------------- */
  function hasContent() {
    var d = state.data;
    if (d.personal.fullName || d.summary) return true;
    return R.DEFAULT_ORDER.some(function (k) { return Array.isArray(d[k]) && d[k].some(function (it) { return Object.keys(it).some(function (f) { return String(it[f] || '').trim(); }); }); });
  }
  function getPath(path) {
    var parts = path.split('.'), o = state.data;
    for (var i = 0; i < parts.length - 1; i++) o = o[parts[i]];
    return { obj: o, key: parts[parts.length - 1] };
  }
  function blankItem(key) {
    var it = {}; SCHEMA[key].fields.forEach(function (f) { it[f[0]] = ''; }); return it;
  }
  function itemLabel(key, item, idx) {
    var t = item[SCHEMA[key].titleField];
    return t && String(t).trim() ? t : 'Entry ' + (idx + 1);
  }

  /* ---------------- Editor rendering ---------------- */
  function fieldHtml(key, idx, f) {
    var id = 'f_' + key + '_' + idx + '_' + f[0];
    var val = esc(state.data[key][idx][f[0]] || '');
    var attrs = ' id="' + id + '" data-item="' + key + '|' + idx + '|' + f[0] + '" placeholder="' + esc(f[2] || '') + '"';
    var input = f[3] === 'textarea'
      ? '<textarea class="textarea" rows="' + (key === 'experience' ? 4 : 3) + '" maxlength="2000"' + attrs + '>' + val + '</textarea>'
      : '<input class="input" maxlength="200"' + attrs + ' value="' + val + '">';
    return '<div class="field' + (f[3] === 'textarea' ? ' full' : '') + '"><label class="label" for="' + id + '">' + esc(f[1]) + '</label>' + input + '</div>';
  }

  function sectionCard(sec, pos, total) {
    var key = sec.key, sch = SCHEMA[key], isOpen = !!open[key];
    var items = sch.single ? null : state.data[key];
    var h = '<div class="cvb-card' + (isOpen ? ' open' : '') + (sec.visible ? '' : ' is-hidden') + '" data-sec="' + key + '">' +
      '<div class="cvb-card-head"><h2><button type="button" data-act="toggle" aria-expanded="' + isOpen + '" aria-controls="body_' + key + '">' +
      (IC.sec || '') + '<span class="sec-name">' + esc(sec.title) + '</span>' +
      (items ? ' <span class="cvb-count">' + items.length + '</span>' : '') + '</button></h2>' +
      '<button type="button" class="icon-btn" data-act="sec-up" aria-label="Move ' + esc(sec.title) + ' section up"' + (pos === 0 ? ' disabled' : '') + '>' + IC.up + '</button>' +
      '<button type="button" class="icon-btn" data-act="sec-down" aria-label="Move ' + esc(sec.title) + ' section down"' + (pos === total - 1 ? ' disabled' : '') + '>' + IC.down + '</button>' +
      '<button type="button" class="icon-btn" data-act="sec-vis" aria-pressed="' + (!sec.visible) + '" aria-label="' + (sec.visible ? 'Hide ' : 'Show ') + esc(sec.title) + ' section" title="' + (sec.visible ? 'Hide from CV' : 'Show on CV') + '">' + (sec.visible ? IC.eye : IC.eyeOff) + '</button>' +
      '<button type="button" class="icon-btn" data-act="toggle" tabindex="-1" aria-hidden="true">' + IC.chev + '</button>' +
      '</div><div class="cvb-card-body" id="body_' + key + '">' +
      '<div class="cvb-tip">' + IC.bulb + '<span>' + esc(sch.tip) + '</span></div>' +
      '<div class="cvb-sec-title"><div class="field"><label class="label" for="st_' + key + '">Section heading on CV</label><input class="input" id="st_' + key + '" data-sec-title="' + key + '" maxlength="60" value="' + esc(sec.title) + '"></div></div>';

    if (sch.single) {
      h += '<div class="field"><label class="label" for="f_summary">Text</label><textarea class="textarea" id="f_summary" data-path="summary" rows="5" maxlength="1200" placeholder="e.g. Final-year BBA student seeking a part-time sales role…">' + esc(state.data.summary) + '</textarea></div>';
    } else {
      items.forEach(function (it, i) {
        h += '<div class="cvb-item" data-idx="' + i + '"><div class="cvb-item-head"><strong data-item-label>' + esc(itemLabel(key, it, i)) + '</strong>' +
          '<button type="button" class="icon-btn" data-act="item-up" aria-label="Move entry up"' + (i === 0 ? ' disabled' : '') + '>' + IC.up + '</button>' +
          '<button type="button" class="icon-btn" data-act="item-down" aria-label="Move entry down"' + (i === items.length - 1 ? ' disabled' : '') + '>' + IC.down + '</button>' +
          '<button type="button" class="icon-btn danger" data-act="item-del" aria-label="Remove entry ' + esc(itemLabel(key, it, i)) + '">' + IC.trash + '</button></div>' +
          '<div class="cvb-fields">' + sch.fields.map(function (f) { return fieldHtml(key, i, f); }).join('') + '</div></div>';
      });
      h += '<button type="button" class="btn btn-outline btn-sm cvb-add" data-act="item-add">' + IC.plus + ' Add ' + (items.length ? 'another ' : '') + 'entry</button>';
    }
    return h + '</div></div>';
  }

  function renderEditor(focusSel) {
    var secs = state.data.sections;
    sectionWrap.innerHTML = secs.map(function (s, i) { return sectionCard(s, i, secs.length); }).join('');
    document.querySelectorAll('[data-path]').forEach(function (el) {
      if (el.closest('#sectionCards')) return;
      var p = getPath(el.getAttribute('data-path'));
      el.value = p.obj[p.key] || '';
    });
    document.querySelectorAll('input[name="template"]').forEach(function (r) { r.checked = r.value === state.template; });
    
    if (focusSel) {
      var el = sectionWrap.querySelector(focusSel);
      if (el && !el.disabled) el.focus();
      else if (el) { var card = el.closest('.cvb-card'); var t = card && card.querySelector('[data-act="toggle"]'); if (t) t.focus(); }
    }
  }

  /* ---------------- Preview ---------------- */
  var pvTimer;
  function schedulePreview() { clearTimeout(pvTimer); pvTimer = setTimeout(renderPreview, 120); }
  function renderPreview() {
    R.render(preview, state.data, state.template);
    fit();
  }
  function fit() {
    if (!frame.offsetParent) return; // hidden (mobile edit view)
    var cs = getComputedStyle(frame);
    var avail = frame.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
    var s = Math.min(1, avail / 794);
    scaleBox.style.width = '794px';
    scaleBox.style.transform = 'scale(' + s + ')';
    scaleBox.style.height = '';
    var h = preview.offsetHeight;
    scaleBox.style.height = (h * s) + 'px';
    scaleBox.style.marginBottom = '0';
    frame.style.height = (h * s + parseFloat(cs.paddingTop) + parseFloat(cs.paddingBottom)) + 'px';
    var pages = Math.max(1, Math.ceil((preview.scrollHeight - 4) / 1123));
    if (pageHint) pageHint.textContent = pages === 1 ? 'Fits on 1 page' : 'About ' + pages + ' pages';
  }
  window.addEventListener('resize', function () { clearTimeout(pvTimer); pvTimer = setTimeout(fit, 80); });

  /* ---------------- Events ---------------- */
  root.addEventListener('input', function (e) {
    var t = e.target;
    if (t.hasAttribute('data-path')) {
      var p = getPath(t.getAttribute('data-path')); p.obj[p.key] = t.value; changed();
    } else if (t.hasAttribute('data-item')) {
      var a = t.getAttribute('data-item').split('|'), key = a[0], idx = +a[1];
      state.data[key][idx][a[2]] = t.value;
      if (a[2] === SCHEMA[key].titleField) {
        var lab = t.closest('.cvb-item').querySelector('[data-item-label]');
        if (lab) lab.textContent = itemLabel(key, state.data[key][idx], idx);
      }
      changed();
    } else if (t.hasAttribute('data-sec-title')) {
      var k = t.getAttribute('data-sec-title');
      var sec = state.data.sections.filter(function (s) { return s.key === k; })[0];
      sec.title = t.value.trim() ? t.value : SCHEMA[k].label;
      t.closest('.cvb-card').querySelector('.sec-name').textContent = sec.title;
      changed();
    }
  });

  root.addEventListener('change', function (e) {
    if (e.target.name === 'template' && TEMPLATES.indexOf(e.target.value) >= 0) {
      state.template = e.target.value; changed(); renderPreview();
    }
  });

  root.addEventListener('click', function (e) {
    var b = e.target.closest('[data-act]');
    if (b && sectionWrap.contains(b)) {
      var card = b.closest('.cvb-card'), key = card.getAttribute('data-sec');
      var secs = state.data.sections, pos = secs.findIndex(function (s) { return s.key === key; });
      var act = b.getAttribute('data-act');
      if (act === 'toggle') { open[key] = !open[key]; renderEditor('[data-sec="' + key + '"] h2 [data-act="toggle"]'); return; }
      if (act === 'sec-up' || act === 'sec-down') {
        var np = act === 'sec-up' ? pos - 1 : pos + 1;
        if (np < 0 || np >= secs.length) return;
        secs.splice(np, 0, secs.splice(pos, 1)[0]);
        changed(); renderEditor('[data-sec="' + key + '"] [data-act="' + act + '"]'); return;
      }
      if (act === 'sec-vis') { secs[pos].visible = !secs[pos].visible; changed(); renderEditor('[data-sec="' + key + '"] [data-act="sec-vis"]'); return; }
      var items = state.data[key];
      if (act === 'item-add') {
        items.push(blankItem(key)); open[key] = true; changed();
        renderEditor('[data-sec="' + key + '"] .cvb-item:last-of-type input, [data-sec="' + key + '"] .cvb-item:last-of-type textarea');
        return;
      }
      var idx = +b.closest('.cvb-item').getAttribute('data-idx');
      if (act === 'item-del') {
        var it = items[idx];
        var filled = Object.keys(it).some(function (f) { return String(it[f] || '').trim(); });
        if (filled && !confirm('Remove “' + itemLabel(key, it, idx) + '” from ' + secs[pos].title + '?')) return;
        items.splice(idx, 1); changed();
        renderEditor('[data-sec="' + key + '"] [data-act="item-add"]');
        if (window.cbToast) cbToast('Entry removed.');
        return;
      }
      if (act === 'item-up' || act === 'item-down') {
        var ni = act === 'item-up' ? idx - 1 : idx + 1;
        if (ni < 0 || ni >= items.length) return;
        items.splice(ni, 0, items.splice(idx, 1)[0]); changed();
        renderEditor('[data-sec="' + key + '"] .cvb-item[data-idx="' + ni + '"] [data-act="' + act + '"]');
      }
    }
  });

  document.querySelectorAll('[data-example]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (hasContent() && !confirm('Replace your current CV with the example? Your current text will be overwritten.')) return;
      var ex = JSON.parse(JSON.stringify(EXAMPLES[btn.getAttribute('data-example')]));
      state.template = ex.template; state.data = R.normalize(ex.data);
      open = { summary: true, education: true, experience: true };
      changed(); renderEditor(); renderPreview();
      if (window.cbToast) cbToast('Example loaded. Replace the sample details with your own.');
    });
  });

  var clearBtn = document.getElementById('clearCv');
  if (clearBtn) clearBtn.addEventListener('click', function () {
    if (hasContent() && !confirm('Start a blank CV? This clears all sections in the editor.')) return;
    state.data = R.blankData(); open = { summary: true, education: true };
    changed(); renderEditor(); renderPreview();
  });

  var saveBtn = document.getElementById('saveServer');
  if (saveBtn) saveBtn.addEventListener('click', saveServer);
  document.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') { e.preventDefault(); if (CTX.canSave) saveServer(); else { writeLocal(); setStatus('Saved in this browser · ' + timeNow(), 'ok'); } }
  });

  /* Mobile edit/preview tabs */
  document.querySelectorAll('[data-view-btn]').forEach(function (b) {
    b.addEventListener('click', function () {
      root.setAttribute('data-view', b.getAttribute('data-view-btn'));
      document.querySelectorAll('[data-view-btn]').forEach(function (x) { x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
      fit();
    });
  });

  /* ---------------- Printing / PDF ---------------- */
  var host = null;
  function buildPrint() {
    if (host) return;
    host = document.createElement('div');
    host.className = 'cv-print-host';
    var page = document.createElement('div');
    host.appendChild(page);
    R.render(page, state.data, state.template);
    document.body.appendChild(host);
    document.body.classList.add('printing-cv');
  }
  function cleanupPrint() {
    if (host) { host.remove(); host = null; }
    document.body.classList.remove('printing-cv');
  }
  window.addEventListener('beforeprint', buildPrint);
  window.addEventListener('afterprint', cleanupPrint);
  var printBtn = document.getElementById('printCv');
  if (printBtn) printBtn.addEventListener('click', function () {
    var name = (state.data.personal.fullName || 'My').trim();
    var oldTitle = document.title;
    document.title = name + ' - CV';
    buildPrint();
    setTimeout(function () { window.print(); document.title = oldTitle; }, 50);
  });

  /* ---------------- Init ---------------- */
  var server = null;
  try { server = JSON.parse(document.getElementById('cvServerData').textContent || 'null'); } catch (e) { server = null; }
  var local = readLocal();
  if (server && server.data && (!local || local.synced !== false)) {
    state.template = server.template; state.data = R.normalize(server.data); synced = true;
    setStatus('Loaded from your account', 'ok');
  } else if (local) {
    state.template = local.template; state.data = R.normalize(local.data); synced = local.synced !== false;
    setStatus(server && !synced ? 'Restored unsaved changes from this browser' : 'Restored from this browser', '');
  } else {
    setStatus(CTX.canSave ? 'Nothing saved yet' : 'Autosaves in this browser', '');
  }
  if (TEMPLATES.indexOf(state.template) < 0) state.template = 'classic';
  renderEditor();
  renderPreview();
  if (document.fonts && document.fonts.ready) document.fonts.ready.then(fit);
})();
