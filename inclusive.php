<?php
require __DIR__ . '/includes/bootstrap.php';
$user = current_user();

// Voluntary accessibility preferences from the seeker's profile pre-fill the filters (only on the default view).
$prefs = [];
if ($user && $user['role'] === 'seeker') {
    $prefs = array_values(array_intersect(skills_list(seeker_profile((int) $user['id'])['accessibility_prefs'] ?? ''), array_keys(accessibility_features())));
}
if ($prefs && !$_GET) {
    redirect('inclusive.php?' . http_build_query(['acc' => $prefs, 'from' => 'prefs']));
}

$f = read_job_filters($_GET);
$result = search_jobs($f, 'inclusive', max(1, (int) ($_GET['page'] ?? 1)));
$show = [
    'q' => true, 'location' => true, 'remote_option' => true, 'category' => null,
    'types' => ['full_time', 'part_time', 'internship', 'contract', 'temporary', 'shift'],
    'arrangements' => true, 'levels' => true, 'salary' => true, 'acc' => true,
];
$hiddenFields = isset($_GET['all']) ? ['all' => '1'] : [];
$scopeLabel = 'With accessibility information or remote work';
$nearbyNote = '';
if (($_GET['from'] ?? '') === 'prefs') {
    $nearbyNote = '<div class="notice mb-4">' . icon('sliders', '', 18) . '<div>Filters were pre-selected from the accessibility preferences in <a href="' . e(url('account/profile.php')) . '">your profile</a>. These preferences are private and never shared with employers. <a href="' . e(url('inclusive.php?all=1')) . '">Show all inclusive jobs</a></div></div>';
}

$counts = db()->query("SELECT SUM(acc_step_free) a, SUM(acc_lift) b, SUM(acc_toilet) c, SUM(acc_flexible_hours) d, SUM(acc_remote_option OR work_arrangement = 'remote') r, SUM(acc_interview) i
                       FROM jobs j WHERE " . public_job_condition())->fetch();

$title = 'Inclusive Jobs';
$nav = 'jobs';
$metaDesc = 'Find jobs in Bangladesh with employer-provided accessibility information: step-free access, lifts, accessible toilets, flexible hours, remote work and interview accommodations.';
require __DIR__ . '/includes/partials/header.php';
$features = accessibility_features();
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li aria-current="page">Inclusive Jobs</li></ol>
    <span class="eyebrow">Disability-inclusive opportunities</span>
    <h1>Inclusive Jobs</h1>
    <p class="lead">Explore roles from employers who describe the accessibility of their workplace and hiring process. Filter by the support that matters to you — you never need to share medical information.</p>
    <div class="hero-tags">
      <span>Quick filters:</span>
      <a href="<?= e(url('inclusive.php?all=1&arrangement[]=remote')) ?>">Fully remote</a>
      <a href="<?= e(url('inclusive.php?all=1&acc[]=acc_flexible_hours')) ?>">Flexible hours</a>
      <a href="<?= e(url('inclusive.php?all=1&acc[]=acc_step_free')) ?>">Step-free access</a>
      <a href="<?= e(url('inclusive.php?all=1&acc[]=acc_interview')) ?>">Interview accommodations</a>
    </div>
  </div>
</section>

<section class="section-sm" style="padding-bottom:0">
  <div class="container">
    <div class="grid grid-3">
      <?php
      $map = ['acc_step_free' => 'a', 'acc_lift' => 'b', 'acc_toilet' => 'c', 'acc_flexible_hours' => 'd', 'acc_remote_option' => 'r', 'acc_interview' => 'i'];
      foreach ($features as $k => $feat): $n = (int) ($counts[$map[$k]] ?? 0); ?>
        <a class="card card-hover card-pad flex items-center gap-3" style="color:inherit;text-decoration:none" href="<?= e(url('inclusive.php?all=1&acc[]=' . $k)) ?>">
          <span class="icon-tile sm"><?= icon($feat['icon'], '', 20) ?></span>
          <span><strong style="display:block"><?= e($feat['label']) ?></strong><span class="small muted"><?= $n ?> open <?= $n === 1 ? 'listing' : 'listings' ?></span></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/partials/listing.php'; ?>

<section class="section-sm section-warm">
  <div class="container">
    <div class="grid grid-3">
      <div class="card card-pad"><span class="icon-tile"><?= icon('info', '', 22) ?></span><h2 style="font-size:1.1rem">Where this information comes from</h2><p class="muted small mb-0">Accessibility details are written by each employer when they post a job. CareerBridge does not inspect workplaces, so please confirm anything important with the employer before an interview.</p></div>
      <div class="card card-pad"><span class="icon-tile"><?= icon('lock', '', 22) ?></span><h2 style="font-size:1.1rem">Your privacy comes first</h2><p class="muted small mb-0">Saving accessibility preferences is optional and only pre-fills these filters for you. They are never used in job matching and never shown to employers. When applying, you can choose to request an interview accommodation — that is the only thing an employer sees.</p></div>
      <div class="card card-pad"><span class="icon-tile"><?= icon('keyboard', '', 22) ?></span><h2 style="font-size:1.1rem">An accessible website</h2><p class="muted small mb-0">CareerBridge works with a keyboard alone, supports screen readers with labelled forms and landmarks, shows clear focus outlines and uses readable colour contrast. <a href="<?= e(url('about.php#accessibility')) ?>">Read our accessibility statement</a>.</p></div>
    </div>
    <div class="cta-band mt-4" style="margin-top:28px">
      <div><h2 style="color:#fff;margin-bottom:6px;font-size:1.35rem">Hiring? Describe your workplace accessibility.</h2><p style="margin:0;color:#c9d0dc">Adding accessibility details to a listing helps more qualified candidates apply with confidence.</p></div>
      <div class="cta-stack"><a class="btn btn-primary" href="<?= e(url('employer/job-edit.php')) ?>">Post an inclusive job</a></div>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
