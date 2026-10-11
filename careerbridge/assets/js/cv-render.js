/* CareerBridge CV renderer – turns CV data (JSON) into a formatted A4 document.
   Shared by the CV builder (live preview) and the employer application view.
   No AI, no external services. */
(function (global) {
  'use strict';

  var SCHEMA = {
    summary: { label: 'Career Objective', single: true,
      tip: 'Write 2–4 lines: the role you want, your strongest skills, and what you can offer. Freshers can focus on goals and eagerness to learn.' },
    education: { label: 'Education', titleField: 'degree',
      tip: 'List your most recent qualification first. Include results only if they help you.',
      fields: [
        ['degree', 'Degree / certificate', 'e.g. B.Sc. in Computer Science & Engineering'],
        ['institution', 'Institution', 'e.g. Name of university or college'],
        ['location', 'Location', 'e.g. Dhaka'],
        ['start', 'Start year', 'e.g. 2021'],
        ['end', 'End year', 'e.g. 2025 or Present'],
        ['result', 'Result (optional)', 'e.g. CGPA 3.60 / 4.00'],
        ['details', 'Details (optional)', 'Relevant courses, thesis, awards', 'textarea']
      ] },
    experience: { label: 'Work Experience', titleField: 'role',
      tip: 'Part-time jobs, internships, tutoring and volunteering all count. Start each bullet with an action verb and add numbers where you can.',
      fields: [
        ['role', 'Job title', 'e.g. Sales Assistant (Part-time)'],
        ['company', 'Organisation', 'e.g. Name of shop or company'],
        ['location', 'Location', 'e.g. Chattogram'],
        ['start', 'Start', 'e.g. Jan 2023'],
        ['end', 'End', 'e.g. Present'],
        ['bullets', 'Achievements & responsibilities (one per line)', 'Served 60+ customers daily\nManaged the cash counter accurately', 'textarea']
      ] },
    skills: { label: 'Skills', titleField: 'group',
      tip: 'Group related skills. Only list skills you can confidently discuss in an interview.',
      fields: [
        ['group', 'Skill group', 'e.g. Technical, Office, Soft skills'],
        ['items', 'Skills (comma separated)', 'e.g. HTML, CSS, JavaScript']
      ] },
    projects: { label: 'Projects', titleField: 'name',
      tip: 'Very valuable for freshers. Say what you built, the tools you used and the result.',
      fields: [
        ['name', 'Project name', 'e.g. Bazar List App'],
        ['tech', 'Tools / technologies', 'e.g. JavaScript, LocalStorage'],
        ['link', 'Link (optional)', 'e.g. github.com/yourname/project'],
        ['description', 'Description', 'What it does and what you achieved', 'textarea']
      ] },
    training: { label: 'Training & Certifications', titleField: 'name',
      tip: 'Include short courses, workshops and certificates relevant to the job.',
      fields: [
        ['name', 'Course / certificate', 'e.g. Digital Marketing Fundamentals'],
        ['provider', 'Provider', 'e.g. Training institute or online platform'],
        ['year', 'Year', 'e.g. 2024']
      ] },
    languages: { label: 'Languages', titleField: 'name',
      tip: 'Be honest about your level: Native, Fluent, Professional or Basic.',
      fields: [
        ['name', 'Language', 'e.g. Bangla'],
        ['level', 'Level', 'e.g. Native']
      ] },
    references: { label: 'References', titleField: 'name',
      tip: 'Ask permission before listing someone. If you leave this empty, “Available on request” is shown.',
      fields: [
        ['name', 'Name', 'Full name'],
        ['position', 'Position', 'e.g. Assistant Professor'],
        ['organization', 'Organisation', 'e.g. Department, University'],
        ['contact', 'Contact', 'Phone or email']
      ] }
  };

  var DEFAULT_ORDER = ['summary', 'education', 'experience', 'projects', 'skills', 'training', 'languages', 'references'];

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function filled(item) {
    return item && Object.keys(item).some(function (k) { return String(item[k] || '').trim() !== ''; });
  }
  function lines(t) {
    return String(t || '').split(/\r?\n/).map(function (l) { return l.replace(/^\s*[-•*]\s*/, '').trim(); }).filter(Boolean);
  }
  function dates(a, b) {
    a = (a || '').trim(); b = (b || '').trim();
    if (a && b) return esc(a) + ' – ' + esc(b);
    return esc(a || b);
  }

  function blankData() {
    var d = { personal: { fullName: '', headline: '', email: '', phone: '', location: '', linkedin: '', website: '' }, summary: '', sections: [] };
    DEFAULT_ORDER.forEach(function (k) {
      d.sections.push({ key: k, title: SCHEMA[k].label, visible: true });
      if (!SCHEMA[k].single) d[k] = [];
    });
    return d;
  }

  /** Make sure data has every expected property (older saves, partial data). */
  function normalize(d) {
    var base = blankData();
    if (!d || typeof d !== 'object') return base;
    d.personal = Object.assign(base.personal, d.personal || {});
    if (typeof d.summary !== 'string') d.summary = '';
    var seen = {};
    d.sections = (Array.isArray(d.sections) ? d.sections : []).filter(function (s) {
      if (!s || !SCHEMA[s.key] || seen[s.key]) return false; seen[s.key] = true;
      if (typeof s.title !== 'string' || !s.title.trim()) s.title = SCHEMA[s.key].label;
      s.visible = s.visible !== false; return true;
    });
    DEFAULT_ORDER.forEach(function (k) { if (!seen[k]) d.sections.push({ key: k, title: SCHEMA[k].label, visible: true }); });
    DEFAULT_ORDER.forEach(function (k) { if (!SCHEMA[k].single && !Array.isArray(d[k])) d[k] = []; });
    return d;
  }

  function sectionBody(key, d) {
    var items = d[key] || [];
    var h = '';
    switch (key) {
      case 'summary':
        return d.summary.trim() ? '<p class="cv-summary">' + esc(d.summary).replace(/\n/g, '<br>') + '</p>' : '';
      case 'education':
        items.filter(filled).forEach(function (e) {
          h += '<div class="cv-entry"><div class="cv-entry-head"><div><div class="cv-entry-title">' + esc(e.degree) + '</div>' +
            '<div class="cv-entry-sub">' + esc(e.institution) + (e.location ? ', ' + esc(e.location) : '') + '</div></div>' +
            '<div class="cv-date">' + dates(e.start, e.end) + '</div></div>' +
            (e.result ? '<div class="cv-entry-meta">' + esc(e.result) + '</div>' : '') +
            (e.details ? '<p class="cv-text">' + esc(e.details).replace(/\n/g, '<br>') + '</p>' : '') + '</div>';
        });
        return h;
      case 'experience':
        items.filter(filled).forEach(function (e) {
          var b = lines(e.bullets);
          h += '<div class="cv-entry"><div class="cv-entry-head"><div><div class="cv-entry-title">' + esc(e.role) + '</div>' +
            '<div class="cv-entry-sub">' + esc(e.company) + (e.location ? ', ' + esc(e.location) : '') + '</div></div>' +
            '<div class="cv-date">' + dates(e.start, e.end) + '</div></div>' +
            (b.length ? '<ul class="cv-bullets">' + b.map(function (x) { return '<li>' + esc(x) + '</li>'; }).join('') + '</ul>' : '') + '</div>';
        });
        return h;
      case 'skills':
        items.filter(filled).forEach(function (s) {
          h += '<div class="cv-skill"><span class="cv-skill-group">' + esc(s.group || 'Skills') + '</span><span class="cv-skill-items">' +
            String(s.items || '').split(',').map(function (x) { return x.trim(); }).filter(Boolean).map(function (x) { return '<span class="cv-tag">' + esc(x) + '</span>'; }).join('') + '</span></div>';
        });
        return h;
      case 'projects':
        items.filter(filled).forEach(function (p) {
          h += '<div class="cv-entry"><div class="cv-entry-head"><div><div class="cv-entry-title">' + esc(p.name) + '</div>' +
            (p.tech ? '<div class="cv-entry-sub">' + esc(p.tech) + '</div>' : '') + '</div>' +
            (p.link ? '<div class="cv-date cv-link">' + esc(p.link) + '</div>' : '') + '</div>' +
            (p.description ? '<p class="cv-text">' + esc(p.description).replace(/\n/g, '<br>') + '</p>' : '') + '</div>';
        });
        return h;
      case 'training':
        items.filter(filled).forEach(function (t) {
          h += '<div class="cv-line"><span><strong>' + esc(t.name) + '</strong>' + (t.provider ? ' — ' + esc(t.provider) : '') + '</span><span class="cv-date">' + esc(t.year) + '</span></div>';
        });
        return h;
      case 'languages':
        items.filter(filled).forEach(function (l) {
          h += '<div class="cv-line"><span><strong>' + esc(l.name) + '</strong></span><span class="cv-muted">' + esc(l.level) + '</span></div>';
        });
        return h;
      case 'references':
        var refs = items.filter(filled);
        if (!refs.length) return '<p class="cv-text">Available on request.</p>';
        return '<div class="cv-refs">' + refs.map(function (r) {
          return '<div><div class="cv-entry-title">' + esc(r.name) + '</div><div class="cv-entry-sub">' + esc(r.position) +
            (r.organization ? '<br>' + esc(r.organization) : '') + '</div>' + (r.contact ? '<div class="cv-muted">' + esc(r.contact) + '</div>' : '') + '</div>';
        }).join('') + '</div>';
    }
    return '';
  }

  function section(s, d) {
    var body = sectionBody(s.key, d);
    if (!body) return '';
    return '<section class="cv-section cv-sec-' + s.key + '"><h2 class="cv-h">' + esc(s.title) + '</h2>' + body + '</section>';
  }

  function contactItems(p) {
    return [p.email, p.phone, p.location, p.linkedin, p.website].filter(function (x) { return x && String(x).trim(); });
  }

  function render(el, data, template) {
    var d = normalize(JSON.parse(JSON.stringify(data || {})));
    template = ['classic', 'modern', 'minimal', 'compact'].indexOf(template) >= 0 ? template : 'classic';
    var p = d.personal;
    var visible = d.sections.filter(function (s) { return s.visible; });
    var name = p.fullName && p.fullName.trim() ? esc(p.fullName) : '<span class="cv-placeholder">Your Name</span>';
    var html = '';

    if (template === 'modern') {
      var side = ['skills', 'languages', 'training'];
      html += '<div class="cv-modern"><aside class="cv-side">' +
        '<div class="cv-name">' + name + '</div>' + (p.headline ? '<div class="cv-headline">' + esc(p.headline) + '</div>' : '') +
        '<div class="cv-contact-list">' + contactItems(p).map(function (c) { return '<div>' + esc(c) + '</div>'; }).join('') + '</div>' +
        visible.filter(function (s) { return side.indexOf(s.key) >= 0; }).map(function (s) { return section(s, d); }).join('') +
        '</aside><div class="cv-main">' +
        visible.filter(function (s) { return side.indexOf(s.key) < 0; }).map(function (s) { return section(s, d); }).join('') +
        '</div></div>';
    } else {
      html += '<header class="cv-header"><div class="cv-name">' + name + '</div>' +
        (p.headline ? '<div class="cv-headline">' + esc(p.headline) + '</div>' : '') +
        '<div class="cv-contact">' + contactItems(p).map(function (c) { return '<span>' + esc(c) + '</span>'; }).join('') + '</div></header>' +
        visible.map(function (s) { return section(s, d); }).join('');
    }
    el.className = 'cv-page cv-t-' + template;
    el.innerHTML = html;
  }

  /** Scale a rendered .cv-page down so it fits inside its container (keeps A4 proportions). */
  function fit(viewer) {
    var page = viewer.querySelector('.cv-page');
    if (!page) return;
    var box = page.parentNode.classList.contains('cv-fit') ? page.parentNode : null;
    if (!box) { box = document.createElement('div'); box.className = 'cv-fit'; page.parentNode.insertBefore(box, page); box.appendChild(page); }
    var cs = getComputedStyle(viewer);
    var avail = viewer.clientWidth - parseFloat(cs.paddingLeft) - parseFloat(cs.paddingRight);
    var s = Math.min(1, avail / 794);
    page.style.transformOrigin = 'top left';
    page.style.transform = 'scale(' + s + ')';
    page.style.margin = '0';
    box.style.width = (794 * s) + 'px';
    box.style.height = (page.offsetHeight * s) + 'px';
    box.style.margin = '0 auto';
    box.style.overflow = 'hidden';
  }

  global.CVRender = { render: render, fit: fit, SCHEMA: SCHEMA, DEFAULT_ORDER: DEFAULT_ORDER, blankData: blankData, normalize: normalize, esc: esc };
})(window);
