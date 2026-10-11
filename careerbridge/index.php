<?php
require __DIR__ . '/includes/bootstrap.php';

$title = APP_NAME;
$nav = 'home';

$featured = latest_jobs(6, 'j.is_featured = 1');
$latest   = latest_jobs(5);
$counts = db()->query("SELECT j.category_id, COUNT(*) n FROM jobs j WHERE " . public_job_condition() . " GROUP BY j.category_id")->fetchAll(PDO::FETCH_KEY_PAIR);
$totals = db()->query("SELECT
    SUM(j.employment_type IN ('part_time','temporary','shift') AND j.work_arrangement='onsite') AS local_n,
    SUM(j.work_arrangement IN ('remote','hybrid') OR cat.grp='it') AS remote_n,
    SUM(j.experience_level='fresher' OR j.employment_type='internship') AS fresher_n,
    SUM(j.acc_step_free OR j.acc_lift OR j.acc_toilet OR j.acc_flexible_hours OR j.acc_remote_option OR j.acc_interview OR j.work_arrangement='remote') AS inclusive_n
  FROM jobs j JOIN categories cat ON cat.id = j.category_id WHERE " . public_job_condition())->fetch();

require __DIR__ . '/includes/partials/header.php';
?>

<!-- ============ HERO ============ -->
<section class="hero" aria-labelledby="hero-title">
  <div class="container hero-grid">
    <div>
      <div class="hero-pill"><span class="dot"><?= icon('pin', '', 13) ?></span> Jobs across all 8 divisions of Bangladesh</div>
      <h1 id="hero-title">Find the Right Job. <span class="accent">Build Your Future.</span></h1>
      <p class="hero-lead">Discover local part-time jobs, remote opportunities, and career options across Bangladesh. Your career journey starts here.</p>

      <form class="search-box" action="<?= e(url('jobs.php')) ?>" method="get" role="search" aria-label="Search jobs">
        <div class="input-icon">
          <?= icon('search', '', 18) ?>
          <label class="sr-only" for="hero-q">Job title or keyword</label>
          <input class="input" id="hero-q" name="q" type="search" placeholder="Job title, skill or keyword" autocomplete="off">
        </div>
        <div class="input-icon">
          <?= icon('pin', '', 18) ?>
          <label class="sr-only" for="hero-loc">Location</label>
          <?= district_select('district', '', ['id' => 'hero-loc', 'placeholder' => 'Any location', 'remote' => true]) ?>
        </div>
        <button class="btn btn-primary" type="submit"><?= icon('search', '', 18) ?> Find Jobs</button>
      </form>
      <p class="hero-note"><?= icon('check-circle', 'text-brand', 18) ?> Opportunities for students, freshers and professionals.</p>
      <div class="hero-tags" aria-label="Popular searches">
        <span>Popular:</span>
        <a href="<?= e(url('local.php?category=tutoring')) ?>">Tutoring</a>
        <a href="<?= e(url('local.php?category=restaurant-cafe')) ?>">Café &amp; restaurant</a>
        <a href="<?= e(url('remote.php?arrangement[]=remote')) ?>">Work from home</a>
        <a href="<?= e(url('jobs.php?type[]=internship')) ?>">Internships</a>
        <a href="<?= e(url('remote.php?category=frontend')) ?>">Frontend</a>
      </div>
    </div>

    <!-- Artwork made only from interface elements and abstract shapes -->
    <div class="hero-art" aria-hidden="true">
      <div class="art-panel">
        <svg class="art-map" viewBox="0 0 400 360" preserveAspectRatio="xMidYMid slice">
          <rect width="400" height="360" fill="#FBF8F4"/>
          <g stroke="#EFE7DC" stroke-width="1">
            <?php for ($i = 0; $i <= 400; $i += 40): ?><line x1="<?= $i ?>" y1="0" x2="<?= $i ?>" y2="360"/><?php endfor; ?>
            <?php for ($i = 0; $i <= 360; $i += 40): ?><line x1="0" y1="<?= $i ?>" x2="400" y2="<?= $i ?>"/><?php endfor; ?>
          </g>
          <path d="M-10 250 C 70 220, 120 290, 200 260 S 330 200, 420 230 L 420 300 C 330 270, 260 330, 190 320 S 60 290, -10 320Z" fill="#E7F0FA"/>
          <g fill="none" stroke="#fff" stroke-width="14" stroke-linecap="round">
            <path d="M40 -10 C 60 80, 150 120, 160 200 S 120 330, 150 380"/>
            <path d="M-10 120 C 100 110, 240 150, 410 90"/>
            <path d="M250 -10 C 240 90, 300 160, 290 380"/>
          </g>
          <g fill="none" stroke="#EADFD1" stroke-width="1.5">
            <path d="M40 -10 C 60 80, 150 120, 160 200 S 120 330, 150 380"/>
            <path d="M-10 120 C 100 110, 240 150, 410 90"/>
            <path d="M250 -10 C 240 90, 300 160, 290 380"/>
          </g>
          <rect x="185" y="30" width="42" height="30" rx="5" fill="#F3ECE2"/><rect x="310" y="150" width="56" height="36" rx="6" fill="#F3ECE2"/><rect x="60" y="170" width="46" height="34" rx="6" fill="#F3ECE2"/>
          <path d="M120 135 C 170 150, 215 125, 268 118 S 310 180, 330 196" fill="none" stroke="#F07A2A" stroke-width="3" stroke-dasharray="2 9" stroke-linecap="round"/>
          <circle cx="268" cy="118" r="38" fill="#F07A2A" opacity=".10"/>
          <circle cx="268" cy="118" r="22" fill="#F07A2A" opacity=".14"/>
          <g transform="translate(256 88)"><path d="M12 0C5.4 0 0 5.2 0 11.7 0 20.4 12 30 12 30s12-9.6 12-18.3C24 5.2 18.6 0 12 0Z" fill="#F07A2A"/><circle cx="12" cy="11.5" r="4.5" fill="#fff"/></g>
          <g transform="translate(108 108)"><path d="M12 0C5.4 0 0 5.2 0 11.7 0 20.4 12 30 12 30s12-9.6 12-18.3C24 5.2 18.6 0 12 0Z" fill="#111C33"/><circle cx="12" cy="11.5" r="4.5" fill="#fff"/></g>
          <g transform="translate(320 168)"><path d="M10 0C4.5 0 0 4.3 0 9.7 0 17 10 25 10 25s10-8 10-15.3C20 4.3 15.5 0 10 0Z" fill="#F7A66C"/><circle cx="10" cy="9.5" r="3.6" fill="#fff"/></g>
          <g transform="translate(200 220)"><path d="M10 0C4.5 0 0 4.3 0 9.7 0 17 10 25 10 25s10-8 10-15.3C20 4.3 15.5 0 10 0Z" fill="#F7A66C"/><circle cx="10" cy="9.5" r="3.6" fill="#fff"/></g>
        </svg>
      </div>

      <div class="art-card c1">
        <span class="art-sample">Sample</span>
        <div class="row">
          <span class="logo-tile sm t1"><?= icon('coffee', '', 18) ?></span>
          <div><div class="t">Café Service Assistant</div><div class="s">Dhanmondi, Dhaka</div></div>
        </div>
        <div class="meta-row"><span class="chip">Part-time</span><span class="chip">Evening</span><span class="chip">Tk 9k–11k</span></div>
      </div>

      <div class="art-card c2">
        <span class="art-sample">Sample</span>
        <div class="row">
          <span class="logo-tile sm t2"><?= icon('laptop', '', 18) ?></span>
          <div><div class="t">Junior Frontend Developer</div><div class="s">Remote · Bangladesh</div></div>
        </div>
        <div class="meta-row"><span class="chip">React</span><span class="chip">JavaScript</span><span class="chip">Remote</span></div>
      </div>

      <div class="art-card c3">
        <div class="row" style="justify-content:space-between"><span class="t">Profile match</span><span class="t" style="color:var(--brand)">82%</span></div>
        <div class="progress-mini"><span style="width:82%"></span></div>
        <div class="meta-row"><span class="chip chip-match"><?= icon('check', '', 12) ?> React</span><span class="chip chip-gap">TypeScript</span></div>
      </div>
    </div>
  </div>
</section>

<!-- ============ EXPLORE OPPORTUNITIES ============ -->
<section class="section" aria-labelledby="explore-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Explore opportunities</span>
        <h2 id="explore-title">Work that fits your life</h2>
        <p>Whether you need a few evening hours near home, a remote role in tech, or your very first job — start with the path that suits you.</p>
      </div>
      <a class="link-arrow" href="<?= e(url('jobs.php')) ?>">Browse all jobs <?= icon('arrow-right', '', 18) ?></a>
    </div>
    <div class="grid grid-4">
      <?php
      $explore = [
          ['store', 'Local Part-Time Jobs', 'Find opportunities at nearby shops, restaurants, cafés, supermarkets, coaching centres, and small businesses.', 'local.php', (int) $totals['local_n'], ['Shifts', 'Evenings', 'Weekends']],
          ['laptop', 'Remote & IT Jobs', 'Discover work-from-home, remote, hybrid, and IT-related opportunities.', 'remote.php', (int) $totals['remote_n'], ['Remote', 'Hybrid', 'IT']],
          ['cap', 'Fresher Opportunities', 'Explore entry-level jobs, internships, beginner-friendly opportunities, and career preparation resources.', 'freshers.php', (int) $totals['fresher_n'], ['Internships', 'Entry-level']],
          ['door-open', 'Inclusive Jobs', 'Discover jobs with accessible workplaces, flexible arrangements, and suitable accommodations when employers provide this information.', 'inclusive.php', (int) $totals['inclusive_n'], ['Accessible', 'Flexible']],
      ];
      foreach ($explore as [$ic, $h, $p, $href, $n, $tags]): ?>
        <article class="card card-hover feature-card">
          <span class="icon-tile"><?= icon($ic, '', 24) ?></span>
          <h3><?= e($h) ?></h3>
          <div class="tags"><?php foreach ($tags as $t): ?><span class="chip"><?= e($t) ?></span><?php endforeach; ?></div>
          <p><?= e($p) ?></p>
          <div class="flex items-center justify-between">
            <a class="link-arrow" href="<?= e(url($href)) ?>" aria-label="Explore <?= e($h) ?>">Explore <?= icon('arrow-right', '', 18) ?></a>
            <span class="small muted"><?= $n ?> open</span>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ FEATURED JOBS ============ -->
<section class="section section-warm" aria-labelledby="featured-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Featured jobs</span>
        <h2 id="featured-title">Highlighted opportunities</h2>
        <p>A selection of current listings. All listings shown here are demo data.</p>
      </div>
      <a class="link-arrow" href="<?= e(url('jobs.php')) ?>">View all jobs <?= icon('arrow-right', '', 18) ?></a>
    </div>
    <?php if ($featured): ?>
      <div class="job-grid">
        <?php foreach ($featured as $job) echo job_card($job); ?>
      </div>
    <?php else: ?>
      <?= empty_state('No featured jobs right now', 'Check back soon or browse all current listings.', '<a class="btn btn-primary" href="' . e(url('jobs.php')) . '">Browse jobs</a>') ?>
    <?php endif; ?>
  </div>
</section>

<!-- ============ CAREER TOOLS ============ -->
<section class="section" aria-labelledby="tools-title">
  <div class="container">
    <div class="section-head center">
      <div>
        <span class="eyebrow">Career tools</span>
        <h2 id="tools-title">Everything you need to apply with confidence</h2>
        <p>Simple, free tools that work without any paid services — write your CV, see which jobs fit your profile and keep track of every application.</p>
      </div>
    </div>
    <div class="grid grid-3">
      <article class="card card-hover feature-card">
        <span class="icon-tile"><?= icon('file', '', 24) ?></span>
        <h3>CV Writing &amp; Editing</h3>
        <p>Choose from four professional templates, fill in simple forms, reorder sections and download a clean PDF. Includes fresher and experienced examples.</p>
        <a class="link-arrow" href="<?= e(url('cv-builder.php')) ?>">Open CV builder <?= icon('arrow-right', '', 18) ?></a>
      </article>
      <article class="card card-hover feature-card">
        <span class="icon-tile"><?= icon('target', '', 24) ?></span>
        <h3>Smart Job Matching</h3>
        <p>A transparent, rule-based score compares your skills, education, experience and preferences with each job — and shows skills worth learning next.</p>
        <a class="link-arrow" href="<?= e(url('matching.php')) ?>">See your matches <?= icon('arrow-right', '', 18) ?></a>
      </article>
      <article class="card card-hover feature-card">
        <span class="icon-tile"><?= icon('checks', '', 24) ?></span>
        <h3>Application Tracking</h3>
        <p>Record every application, update its status, add private notes and see upcoming interviews in one clean dashboard.</p>
        <a class="link-arrow" href="<?= e(url('account/applications.php')) ?>">Track applications <?= icon('arrow-right', '', 18) ?></a>
      </article>
    </div>
    <div class="card card-pad mt-4 flex items-center justify-between gap-3 wrap" style="background:var(--bg-warm)">
      <div class="flex items-center gap-3">
        <span class="icon-tile navy" style="margin:0"><?= icon('compass', '', 24) ?></span>
        <div>
          <h3 class="mb-0">Fresher Career Guidance</h3>
          <p class="muted mb-0">Learning roadmaps, project ideas, interview preparation and CV tips — written in simple language.</p>
        </div>
      </div>
      <a class="btn btn-dark" href="<?= e(url('freshers.php')) ?>">Explore guidance <?= icon('arrow-right', '', 18) ?></a>
    </div>
  </div>
</section>

<!-- ============ BROWSE BY CATEGORY ============ -->
<section class="section section-warm" aria-labelledby="cat-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Browse by category</span>
        <h2 id="cat-title">Explore jobs by field</h2>
      </div>
    </div>
    <div class="grid grid-4">
      <?php foreach (categories() as $cat): $n = (int) ($counts[$cat['id']] ?? 0);
        $target = $cat['grp'] === 'local' ? 'local.php' : ($cat['grp'] === 'it' ? 'remote.php' : 'jobs.php'); ?>
        <a class="cat-tile" href="<?= e(url($target . '?category=' . $cat['slug'])) ?>">
          <span class="icon-tile"><?= icon($cat['icon'], '', 20) ?></span>
          <div><strong><?= e($cat['name']) ?></strong><small><?= $n ?> open <?= $n === 1 ? 'job' : 'jobs' ?></small></div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ LATEST ============ -->
<section class="section" aria-labelledby="latest-title">
  <div class="container">
    <div class="section-head">
      <div>
        <span class="eyebrow">Latest opportunities</span>
        <h2 id="latest-title">Recently posted</h2>
      </div>
      <a class="link-arrow" href="<?= e(url('jobs.php?sort=newest')) ?>">See all new jobs <?= icon('arrow-right', '', 18) ?></a>
    </div>
    <div class="job-list">
      <?php foreach ($latest as $job) echo job_card($job, 'row'); ?>
    </div>
  </div>
</section>

<!-- ============ HOW IT WORKS ============ -->
<section class="section section-warm" aria-labelledby="how-title">
  <div class="container">
    <div class="section-head center">
      <div>
        <span class="eyebrow">How CareerBridge works</span>
        <h2 id="how-title">From search to offer in four steps</h2>
      </div>
    </div>
    <div class="grid grid-4 steps">
      <?php foreach ([
          ['Create your profile', 'Add your education, skills and preferences. Accessibility preferences are optional and private.'],
          ['Find matching jobs', 'Search and filter by location, hours and type, or let the rule-based matcher suggest roles.'],
          ['Apply with your CV', 'Build a CV in minutes and apply directly through CareerBridge.'],
          ['Track your progress', 'Update statuses, note interview dates and keep private notes for each application.'],
      ] as $i => [$h, $p]): ?>
        <div class="card step">
          <span class="step-num"><?= $i + 1 ?></span>
          <h3><?= e($h) ?></h3>
          <p><?= e($p) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ============ EMPLOYERS ============ -->
<section class="section" aria-labelledby="emp-title">
  <div class="container">
    <div class="cta-band">
      <div style="position:relative;z-index:1">
        <span class="eyebrow" style="color:#ffb888">For employers</span>
        <h2 id="emp-title">Hire students, freshers and professionals near you</h2>
        <p>Shops, cafés, coaching centres, startups and IT companies can post vacancies, describe workplace accessibility and manage applicants in one place.</p>
        <ul>
          <li><?= icon('check-circle', '', 20) ?> Post part-time, shift, remote and full-time roles</li>
          <li><?= icon('check-circle', '', 20) ?> Review applications and update progress</li>
          <li><?= icon('check-circle', '', 20) ?> Share accessibility facilities you provide</li>
        </ul>
        <div class="flex gap-2 wrap">
          <a class="btn btn-primary btn-lg" href="<?= e(url('register.php?role=employer')) ?>">Create employer account</a>
          <a class="btn btn-lg" style="color:#fff;border-color:rgba(255,255,255,.25)" href="<?= e(url('employers.php')) ?>">Learn more</a>
        </div>
      </div>
      <div class="cta-stack" aria-hidden="true">
        <div class="mini"><div><strong>Café Service Assistant</strong><span>Part-time · Published</span></div><span class="badge badge-green">Active</span></div>
        <div class="mini"><div><strong>New applications</strong><span>Review and shortlist</span></div><?= icon('inbox', '', 22) ?></div>
        <div class="mini"><div><strong>Step-free access · Flexible hours</strong><span>Accessibility details</span></div><?= icon('door-open', '', 22) ?></div>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/partials/footer.php'; ?>
