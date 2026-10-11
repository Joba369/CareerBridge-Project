<?php
require __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$title = 'Career Tools';
$nav = 'tools';
$metaDesc = 'Free career tools from CareerBridge: CV Writing & Editing Hub, transparent Smart Job Matching, Application Tracking and Fresher Career Guidance.';
require __DIR__ . '/includes/partials/header.php';

$tools = [
    ['pen', 'CV Writing & Editing', 'Build a clean, professional CV with four templates, a live preview and section-by-section tips. Switch templates any time without losing your text, then download it as a PDF.', 'cv-builder.php', 'Open the CV builder', ['4 templates', 'Live preview', 'Print / save as PDF', 'Fresher & experienced examples']],
    ['target', 'Smart Job Matching', 'Get job recommendations based on your skills, education, experience and preferences. Every score is explained, and missing skills are shown as things to learn — never as a reason to hide a job.', 'matching.php', 'See matching jobs', ['Rule-based scoring', 'Matched & missing skills', 'Clear explanation', 'Refresh any time']],
    ['checks', 'Application Tracking', 'Keep every application in one place. Record the status, interview dates and private notes, and see upcoming interviews at a glance.', 'account/applications.php', 'Open my tracker', ['7 clear statuses', 'Interview reminders', 'Private notes', 'Filter by status']],
];
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li aria-current="page">Career Tools</li></ol>
    <span class="eyebrow">Free for job seekers</span>
    <h1>Career tools that help you apply with confidence</h1>
    <p class="lead">Practical, easy-to-understand tools for every stage of your job search — from writing your first CV to preparing for interviews. No AI guesswork: every tool works on clear rules you can see.</p>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="grid grid-3">
      <?php foreach ($tools as [$ic, $name, $desc, $href, $cta, $tags]): ?>
        <article class="card card-hover feature-card">
          <span class="icon-tile"><?= icon($ic, '', 24) ?></span>
          <h2 style="font-size:1.25rem"><?= e($name) ?></h2>
          <p><?= e($desc) ?></p>
          <ul class="list-plain small" style="display:grid;gap:6px;margin:0 0 18px">
            <?php foreach ($tags as $t): ?><li class="flex items-center gap-2"><?= icon('check', 'text-brand', 16) ?> <?= e($t) ?></li><?php endforeach; ?>
          </ul>
          <a class="btn btn-primary" href="<?= e(url($href)) ?>"><?= e($cta) ?> <?= icon('arrow-right', '', 18) ?></a>
          <?php if ($href === 'account/applications.php' && !$user): ?><p class="help mb-0">Requires a free job seeker account.</p><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm section-warm">
  <div class="container">
    <div class="grid grid-2" style="align-items:stretch">
      <article class="card card-pad-lg">
        <span class="icon-tile"><?= icon('compass', '', 24) ?></span>
        <h2 style="font-size:1.35rem">Fresher Career Guidance</h2>
        <p class="muted">Learning roadmaps, skills for popular roles, portfolio project ideas, interview preparation, CV tips and safe application advice — organised by career path and level.</p>
        <div class="chips mb-4">
          <?php foreach (['Web Development', 'Digital Marketing', 'Design', 'IT Support', 'Office & Customer Service', 'Local Part-Time'] as $p): ?><span class="chip"><?= e($p) ?></span><?php endforeach; ?>
        </div>
        <div class="flex gap-2 wrap">
          <a class="btn btn-dark" href="<?= e(url('freshers.php#guides')) ?>">Browse guides</a>
          <a class="btn btn-outline" href="<?= e(url('freshers.php')) ?>">Entry-level jobs &amp; internships</a>
        </div>
      </article>
      <article class="card card-pad-lg">
        <span class="icon-tile"><?= icon('door-open', '', 24) ?></span>
        <h2 style="font-size:1.35rem">Inclusive Jobs</h2>
        <p class="muted">Find employers that list accessibility information such as step-free access, lifts, accessible toilets, flexible hours, remote options and interview accommodations. You never have to share medical information.</p>
        <div class="chips mb-4">
          <?php foreach (accessibility_features() as $label): ?><span class="chip"><?= e($label['label']) ?></span><?php endforeach; ?>
        </div>
        <a class="btn btn-dark" href="<?= e(url('inclusive.php')) ?>">Explore inclusive jobs</a>
      </article>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">Transparency</span><h2>How our tools work</h2><p>We believe you should understand every recommendation you see.</p></div></div>
    <div class="grid grid-3">
      <div class="card card-pad"><h3 style="font-size:1.05rem"><?= icon('sliders', 'text-brand', 20) ?> Clear rules, not AI</h3><p class="muted small mb-0">Job matching adds up points for skills, category, experience, education, location, job type, work arrangement and salary. The weights are published on the <a href="<?= e(url('matching.php#how')) ?>">matching page</a>.</p></div>
      <div class="card card-pad"><h3 style="font-size:1.05rem"><?= icon('lock', 'text-brand', 20) ?> Your data stays yours</h3><p class="muted small mb-0">Tracker notes are private to you. Your CV is only shared with an employer when you choose to attach it to an application.</p></div>
      <div class="card card-pad"><h3 style="font-size:1.05rem"><?= icon('shield', 'text-brand', 20) ?> Fair by design</h3><p class="muted small mb-0">Matching never uses disability, gender, religion or age. Accessibility preferences only help you filter jobs — they are never shown to employers.</p></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
