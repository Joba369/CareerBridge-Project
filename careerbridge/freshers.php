<?php
require __DIR__ . '/includes/bootstrap.php';

$f = read_job_filters($_GET);
$result = search_jobs($f, 'fresher', max(1, (int) ($_GET['page'] ?? 1)));
$show = [
    'q' => true, 'location' => true, 'remote_option' => true, 'category' => null,
    'types' => ['full_time', 'part_time', 'internship', 'contract', 'temporary', 'shift'],
    'arrangements' => true, 'salary' => true, 'skills' => true,
];
$scopeLabel = 'Fresher-level roles & internships';

// Guides, organised by career path and level
$paths = db()->query('SELECT career_path, COUNT(*) n FROM guides GROUP BY career_path ORDER BY MIN(sort_order)')->fetchAll();
$pathNames = array_column($paths, 'career_path');
$gpath = in_array($_GET['gpath'] ?? '', $pathNames, true) ? $_GET['gpath'] : '';
$glevel = in_array($_GET['glevel'] ?? '', ['Beginner', 'Intermediate'], true) ? $_GET['glevel'] : '';
$sql = 'SELECT slug, title, career_path, level, type, summary, read_minutes FROM guides WHERE 1';
$params = [];
if ($gpath) { $sql .= ' AND career_path = ?'; $params[] = $gpath; }
if ($glevel) { $sql .= ' AND level = ?'; $params[] = $glevel; }
$st = db()->prepare($sql . ' ORDER BY sort_order, id');
$st->execute($params);
$guides = $st->fetchAll();
$hiddenFields = array_filter(['gpath' => $gpath, 'glevel' => $glevel]);

$typeIcons = ['Learning roadmap' => 'route', 'Project ideas' => 'bulb', 'CV tips' => 'file', 'Interview preparation' => 'message', 'Application guidance' => 'send', 'Skills guide' => 'layers', 'Career guide' => 'compass'];

$roles = [
    ['code', 'Junior Web Developer', 'HTML, CSS, JavaScript, Git, one back-end language (PHP or Node.js), responsive design', 'web-development-roadmap'],
    ['palette', 'Junior UI/UX or Graphic Designer', 'Figma, design principles, typography, colour, Photoshop/Illustrator or Canva, a portfolio', 'design-first-portfolio'],
    ['megaphone', 'Digital Marketing Assistant', 'Social media, content writing, basic SEO, Facebook/Google Ads basics, Excel reporting', 'digital-marketing-roadmap'],
    ['headset', 'IT Support Assistant', 'Windows troubleshooting, networking basics, hardware, MS Office, patient communication', 'it-support-fundamentals'],
    ['monitor', 'Office Assistant / Data Entry', 'Typing speed, MS Word & Excel, email etiquette, accuracy, Bangla & English writing', 'skills-for-office-and-service-roles'],
    ['store', 'Retail & Customer Service', 'Customer service, cash handling, product knowledge, teamwork, punctuality', 'first-part-time-job-students'],
];

$title = 'Fresher Career Guidance';
$nav = 'tools';
$metaDesc = 'Entry-level jobs, internships and practical career guides for freshers in Bangladesh: roadmaps, skills by role, project ideas, CV tips and interview preparation.';
require __DIR__ . '/includes/partials/header.php';
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li><a href="<?= e(url('tools.php')) ?>">Career Tools</a></li><li aria-current="page">Fresher Career Guidance</li></ol>
    <span class="eyebrow">For students &amp; new graduates</span>
    <h1>Start your career with a clear plan</h1>
    <p class="lead">Practical guides written for freshers in Bangladesh — what skills each role needs, step-by-step roadmaps, project ideas, CV tips and interview preparation — plus entry-level jobs and internships you can apply for today.</p>
    <div class="hero-tags">
      <span>Jump to:</span>
      <a href="#guides">Guides</a><a href="#skills">Skills by role</a><a href="#journey">Your first-job plan</a><a href="#jobs">Entry-level jobs</a>
    </div>
  </div>
</section>

<section class="section-sm" id="guides" style="scroll-margin-top:90px">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">Learn</span><h2>Career guides</h2><p>Educational articles organised by career path and level. Read them in any order.</p></div></div>
    <nav aria-label="Filter guides by career path">
      <div class="pills">
        <a class="pill<?= $gpath === '' ? ' active' : '' ?>" href="<?= e(query_with(['gpath' => null, 'page' => null])) ?>#guides"<?= $gpath === '' ? ' aria-current="true"' : '' ?>>All paths</a>
        <?php foreach ($paths as $p): ?>
          <a class="pill<?= $gpath === $p['career_path'] ? ' active' : '' ?>" href="<?= e(query_with(['gpath' => $p['career_path'], 'page' => null])) ?>#guides"<?= $gpath === $p['career_path'] ? ' aria-current="true"' : '' ?>><?= e($p['career_path']) ?> <span class="count"><?= (int) $p['n'] ?></span></a>
        <?php endforeach; ?>
      </div>
    </nav>
    <div class="flex gap-2 items-center wrap mb-4 small">
      <span class="muted">Level:</span>
      <?php foreach (['' => 'All levels', 'Beginner' => 'Beginner', 'Intermediate' => 'Intermediate'] as $lv => $ll): ?>
        <a class="chip<?= $glevel === $lv ? ' chip-active' : '' ?>" href="<?= e(query_with(['glevel' => $lv ?: null, 'page' => null])) ?>#guides"<?= $glevel === $lv ? ' aria-current="true"' : '' ?>><?= e($ll) ?></a>
      <?php endforeach; ?>
    </div>
    <?php if ($guides): ?>
      <div class="grid grid-3">
        <?php foreach ($guides as $g): ?>
          <article class="card card-hover guide-card">
            <div class="guide-meta">
              <span class="badge badge-brand"><?= icon($typeIcons[$g['type']] ?? 'book', '', 14) ?> <?= e($g['type']) ?></span>
              <span class="badge badge-grey"><?= e($g['level']) ?></span>
            </div>
            <h3><a href="<?= e(url('guide.php?slug=' . $g['slug'])) ?>"><?= e($g['title']) ?></a></h3>
            <p><?= e($g['summary']) ?></p>
            <div class="flex justify-between items-center small muted"><span><?= e($g['career_path']) ?></span><span><?= icon('clock', '', 14) ?> <?= (int) $g['read_minutes'] ?> min read</span></div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <?= empty_state('No guides for this combination yet', 'Try another level or career path.', '<a class="btn btn-outline" href="' . e(url('freshers.php')) . '#guides">Show all guides</a>', 'book') ?>
    <?php endif; ?>
  </div>
</section>

<section class="section-sm section-warm" id="skills" style="scroll-margin-top:90px">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">Skills by role</span><h2>What employers usually look for</h2><p>Typical starting skills for popular entry-level roles. You don't need everything on day one — use this as a learning checklist.</p></div></div>
    <div class="grid grid-3">
      <?php foreach ($roles as [$ic, $role, $skills, $slug]): ?>
        <div class="card card-pad">
          <div class="flex items-center gap-3 mb-3"><span class="icon-tile sm"><?= icon($ic, '', 20) ?></span><h3 class="mb-0" style="font-size:1.02rem"><?= e($role) ?></h3></div>
          <div class="chips mb-3"><?php foreach (array_map('trim', explode(',', $skills)) as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div>
          <a class="link-arrow small" href="<?= e(url('guide.php?slug=' . $slug)) ?>">Read the guide <?= icon('arrow-right', '', 16) ?></a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm" id="journey" style="scroll-margin-top:90px">
  <div class="container">
    <div class="grid grid-2" style="gap:48px;align-items:start">
      <div>
        <span class="eyebrow">Your first-job plan</span>
        <h2>Five steps from student to employee</h2>
        <p class="muted">A simple plan that works for most career paths. Each step links to a tool or guide on CareerBridge.</p>
        <div class="callout small mt-3"><strong>Tip:</strong> employers hiring freshers care most about attitude, basic skills and proof that you can learn — projects, part-time work and volunteering all count.</div>
      </div>
      <div class="roadmap">
        <?php foreach ([
            ['Choose a direction', 'Pick one or two career paths that interest you. Read the skills list for each role above.', 'freshers.php#skills', 'Skills by role'],
            ['Build core skills', 'Follow a learning roadmap and practise a little every day. Free resources are enough to start.', 'guide.php?slug=web-development-roadmap', 'Example roadmap'],
            ['Create proof of your skills', 'Complete 2–3 small projects, a part-time job or volunteering. This is what you talk about in interviews.', 'guide.php?slug=frontend-project-ideas', 'Project ideas'],
            ['Write a focused CV', 'Use the CV builder with the fresher example. Keep it to one page and highlight projects.', 'cv-builder.php', 'Open CV builder'],
            ['Apply and track', 'Apply to suitable roles, prepare for interviews and track every application.', 'account/applications.php', 'Application tracker'],
        ] as $i => [$h, $p, $href, $lbl]): ?>
          <div class="roadmap-step"><span class="n"><?= $i + 1 ?></span><div><h4><?= e($h) ?></h4><p><?= e($p) ?> <a href="<?= e(url($href)) ?>"><?= e($lbl) ?></a></p></div></div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<section class="section-sm section-warm" id="jobs" style="padding-bottom:0;scroll-margin-top:90px">
  <div class="container"><div class="section-head mb-0" style="margin-bottom:0"><div><span class="eyebrow">Apply</span><h2>Entry-level jobs &amp; internships</h2><p>Roles open to freshers, plus internships at every level.</p></div></div></div>
</section>
<div class="section-warm" style="padding-bottom:40px">
<?php require __DIR__ . '/includes/partials/listing.php'; ?>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
