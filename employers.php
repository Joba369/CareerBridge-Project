<?php
require __DIR__ . '/includes/bootstrap.php';
$user = current_user();
$isEmployer = $user && $user['role'] === 'employer';
$stats = db()->query('SELECT (SELECT COUNT(*) FROM jobs j WHERE ' . public_job_condition() . ') jobs_n, (SELECT COUNT(*) FROM companies) co_n, (SELECT COUNT(DISTINCT district) FROM jobs WHERE district IS NOT NULL AND district <> \'\') dist_n')->fetch();
$primaryHref = $isEmployer ? 'employer/job-edit.php' : ($user ? 'register.php?role=employer' : 'register.php?role=employer');
$primaryLabel = $isEmployer ? 'Post a job' : 'Create an employer account';

$title = 'For Employers';
$nav = 'employers';
$metaDesc = 'Post local part-time, remote, IT and entry-level jobs on CareerBridge, review applications in one place and reach students, freshers and professionals across Bangladesh.';
require __DIR__ . '/includes/partials/header.php';
?>
<section class="hero" style="padding-bottom:72px">
  <div class="container hero-grid">
    <div>
      <span class="hero-pill"><?= icon('building', '', 16) ?> For shops, startups, agencies &amp; companies</span>
      <h1 style="font-size:clamp(2rem,4.2vw,3.1rem);margin-top:16px">Hire the right people, <span class="text-brand">without the hassle.</span></h1>
      <p class="hero-lead">Post part-time, full-time, remote, internship and entry-level jobs for free. Reach students, freshers and professionals across Bangladesh, and manage every application from one simple dashboard.</p>
      <div class="flex gap-2 wrap">
        <a class="btn btn-primary btn-lg" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?> <?= icon('arrow-right', '', 18) ?></a>
        <?php if ($isEmployer): ?>
          <a class="btn btn-outline btn-lg" href="<?= e(url('employer/index.php')) ?>">Go to dashboard</a>
        <?php elseif (!$user): ?>
          <a class="btn btn-outline btn-lg" href="<?= e(url('login.php')) ?>">Employer sign in</a>
        <?php endif; ?>
      </div>
      <?php if ($user && !$isEmployer): ?><p class="help">You are signed in as a job seeker. Sign out first to create a separate employer account.</p><?php endif; ?>
      <div class="trust-row">
        <div><?= icon('check-circle', '', 18) ?> Free to post</div>
        <div><?= icon('check-circle', '', 18) ?> Only you see your applicants</div>
        <div><?= icon('check-circle', '', 18) ?> Close listings any time</div>
      </div>
    </div>
    <div class="hero-art" aria-hidden="true">
      <div class="art-panel"></div>
      <div class="art-card" style="left:6%;top:8%;width:62%">
        <span class="art-sample">Sample dashboard</span>
        <div class="flex items-center justify-between"><strong class="t">Sales Assistant (Part-time)</strong><span class="badge badge-green">Published</span></div>
        <div class="small muted" style="margin:4px 0 12px">Mirpur, Dhaka · Closes in 12 days</div>
        <div class="grid" style="grid-template-columns:repeat(3,1fr);gap:8px;text-align:center">
          <div style="background:var(--bg-warm);border-radius:10px;padding:10px 4px"><strong style="font-size:1.2rem">18</strong><div class="small muted">New</div></div>
          <div style="background:var(--bg-warm);border-radius:10px;padding:10px 4px"><strong style="font-size:1.2rem">6</strong><div class="small muted">Shortlisted</div></div>
          <div style="background:var(--bg-warm);border-radius:10px;padding:10px 4px"><strong style="font-size:1.2rem">3</strong><div class="small muted">Interview</div></div>
        </div>
      </div>
      <div class="art-card" style="right:4%;top:44%;width:56%">
        <div class="flex items-center gap-2" style="margin-bottom:10px"><span class="icon-tile sm"><?= icon('file', '', 18) ?></span><div><strong class="t" style="display:block">Application received</strong><span class="small muted">CV attached · 2 min ago</span></div></div>
        <div class="progress-mini"><span style="width:70%"></span></div>
        <div class="flex gap-2" style="margin-top:12px"><span class="badge badge-blue">Under review</span><span class="badge badge-violet">Interview</span></div>
      </div>
      <div class="art-card" style="left:12%;bottom:6%;width:46%">
        <div class="flex items-center gap-2"><span class="icon-tile sm"><?= icon('door-open', '', 18) ?></span><div><strong class="t" style="display:block">Accessibility info</strong><span class="small muted">Step-free · Flexible hours</span></div></div>
      </div>
    </div>
  </div>
</section>

<section class="section-sm" style="padding-top:0">
  <div class="container">
    <div class="stat-grid" style="grid-template-columns:repeat(3,minmax(0,1fr))">
      <div class="card stat"><span class="k">Open listings</span><span class="v"><?= (int) $stats['jobs_n'] ?></span></div>
      <div class="card stat"><span class="k">Employers</span><span class="v"><?= (int) $stats['co_n'] ?></span></div>
      <div class="card stat"><span class="k">Districts with jobs</span><span class="v"><?= (int) $stats['dist_n'] ?></span></div>
    </div>
    <p class="small muted center mt-2">Figures come from this installation's database, which includes clearly labelled demo data.</p>
  </div>
</section>

<section class="section-sm section-warm">
  <div class="container">
    <div class="section-head"><div><span class="eyebrow">Why CareerBridge</span><h2>Everything you need to hire well</h2><p>Simple tools designed for small businesses and growing teams alike.</p></div></div>
    <div class="grid grid-3">
      <?php foreach ([
          ['store', 'Local hiring made easy', 'List the exact district and area of your shop, café or coaching centre so nearby students can find you. Add shift times and pay per hour, day or month.'],
          ['laptop', 'Remote & IT roles', 'Post remote, hybrid or on-site tech roles with required and preferred skills. Candidates see clearly how their skills match.'],
          ['inbox', 'One inbox for applications', 'See every applicant, their CV (built on CareerBridge or uploaded as PDF), cover note and contact details in one place.'],
          ['checks', 'Track progress', 'Move applicants through Under review, Shortlisted, Interview, Offer or Not selected. Candidates see their status update automatically.'],
          ['door-open', 'Show you are inclusive', 'Tell candidates about step-free access, lifts, accessible toilets, flexible hours, remote options and interview accommodations.'],
          ['shield', 'Private and secure', 'Applications for your jobs are visible only to your account. Your notes on applicants stay private to you.'],
      ] as [$ic, $h, $p]): ?>
        <div class="card card-pad feature-card"><span class="icon-tile"><?= icon($ic, '', 22) ?></span><h3 style="font-size:1.08rem"><?= e($h) ?></h3><p class="mb-0"><?= e($p) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="section-head center"><div><span class="eyebrow">How it works</span><h2>From job post to first day in four steps</h2></div></div>
    <div class="grid grid-4 steps">
      <?php foreach ([
          ['Create your account', 'Register as an employer and add your company profile — name, industry, location and a short description.'],
          ['Post a job', 'Add the title, category, location, salary, work arrangement, skills, qualifications and deadline. Save as a draft or publish straight away.'],
          ['Review applications', 'Read CVs and cover notes, add private notes and update each applicant\'s status.'],
          ['Hire & close', 'Schedule interviews, make an offer and close the listing when the role is filled.'],
      ] as $i => [$h, $p]): ?>
        <div class="card step"><span class="step-num"><?= $i + 1 ?></span><h3 style="font-size:1.05rem"><?= e($h) ?></h3><p class="muted small mb-0"><?= e($p) ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm section-warm">
  <div class="container grid grid-2" style="gap:48px;align-items:start">
    <div>
      <span class="eyebrow">Questions</span>
      <h2>Frequently asked questions</h2>
      <p class="muted">Can't find an answer? See <a href="<?= e(url('about.php')) ?>">About CareerBridge</a>.</p>
    </div>
    <div class="stack-sm">
      <?php foreach ([
          ['Does it cost anything to post a job?', 'No. Posting and managing jobs on CareerBridge is free.'],
          ['Who can see the applications I receive?', 'Only your employer account. Other employers and job seekers cannot see your applicants, and your private notes are never shown to candidates.'],
          ['Can I edit or close a job after publishing?', 'Yes. You can edit a listing at any time, close it when the role is filled, and reopen it with a new deadline. Drafts can be deleted.'],
          ['What happens after the deadline?', 'The listing is automatically hidden from search. You can still review applications you already received.'],
          ['Do I need to provide accessibility information?', 'It is optional but encouraged. Only describe facilities that really exist — candidates rely on this information.'],
      ] as [$q, $a]): ?>
        <details class="card card-pad faq"><summary><strong><?= e($q) ?></strong><?= icon('chevron-down', '', 18) ?></summary><p class="muted mb-0 mt-2"><?= e($a) ?></p></details>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section-sm">
  <div class="container">
    <div class="cta-band">
      <div><h2 style="margin-bottom:8px">Ready to find your next team member?</h2><p style="margin:0">Create your free employer account and publish your first job in minutes.</p></div>
      <div class="cta-stack"><a class="btn btn-primary btn-lg" href="<?= e(url($primaryHref)) ?>"><?= e($primaryLabel) ?></a></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
