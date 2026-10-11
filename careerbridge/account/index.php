<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login('seeker');
$uid = (int) $user['id'];

$profile = seeker_profile($uid);
$complete = profile_completeness($profile);
$stats = db()->prepare("SELECT
   (SELECT COUNT(*) FROM tracker_entries WHERE user_id = :u) AS apps,
   (SELECT COUNT(*) FROM tracker_entries WHERE user_id = :u AND interview_at >= NOW()) AS interviews,
   (SELECT COUNT(*) FROM saved_jobs WHERE user_id = :u) AS saved,
   (SELECT COUNT(*) FROM tracker_entries WHERE user_id = :u AND status = 'offer') AS offers");
$stats->execute([':u' => $uid]);
$stats = $stats->fetch();

$upcoming = db()->prepare('SELECT * FROM tracker_entries WHERE user_id = ? AND interview_at >= NOW() ORDER BY interview_at LIMIT 4');
$upcoming->execute([$uid]);
$upcoming = $upcoming->fetchAll();

$recent = db()->prepare('SELECT * FROM tracker_entries WHERE user_id = ? ORDER BY applied_on DESC, id DESC LIMIT 5');
$recent->execute([$uid]);
$recent = $recent->fetchAll();

$recs = !empty($profile['skills']) ? recommend_jobs(matcher_profile($profile), 3) : [];
$ts = tracker_statuses();

$title = 'Dashboard';
$dashActive = 'overview';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1>Welcome, <?= e(explode(' ', $user['name'])[0]) ?></h1><p>Here's an overview of your job search.</p></div>
      <a class="btn btn-primary" href="<?= e(url('jobs.php')) ?>"><?= icon('search', '', 18) ?> Find jobs</a>
    </div>

    <?php if ($complete < 70): ?>
      <div class="card card-pad mb-4 flex items-center gap-3 wrap" style="background:var(--brand-soft);border-color:#f8d4b8">
        <div style="flex:1;min-width:220px">
          <strong>Your profile is <?= $complete ?>% complete</strong>
          <p class="small mb-0 muted">Add your skills, education and preferences to get better job recommendations.</p>
          <div class="progress mt-2" role="progressbar" aria-valuenow="<?= $complete ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Profile completeness"><span style="width:<?= $complete ?>%"></span></div>
        </div>
        <a class="btn btn-primary" href="<?= e(url('account/profile.php')) ?>">Complete profile</a>
      </div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="card stat"><div class="k"><?= icon('checks', '', 16) ?> Applications</div><div class="v"><?= (int) $stats['apps'] ?></div></div>
      <div class="card stat"><div class="k"><?= icon('calendar', '', 16) ?> Upcoming interviews</div><div class="v"><?= (int) $stats['interviews'] ?></div></div>
      <div class="card stat"><div class="k"><?= icon('bookmark', '', 16) ?> Saved jobs</div><div class="v"><?= (int) $stats['saved'] ?></div></div>
      <div class="card stat"><div class="k"><?= icon('file', '', 16) ?> Profile</div><div class="v"><?= $complete ?>%</div></div>
    </div>

    <div class="grid grid-2" style="align-items:start">
      <section class="card" aria-labelledby="up-title">
        <div class="panel-head"><h2 id="up-title">Upcoming interviews</h2><a class="small" href="<?= e(url('account/applications.php?status=interview')) ?>">View all</a></div>
        <?php if ($upcoming): ?>
          <ul class="list-plain">
            <?php foreach ($upcoming as $t): ?>
              <li><span class="icon-tile sm"><?= icon('calendar', '', 18) ?></span><div style="flex:1;min-width:0"><strong class="small" style="display:block"><?= e($t['job_title']) ?></strong><span class="small muted"><?= e($t['company']) ?></span></div><span class="small" style="font-weight:600;text-align:right"><?= e(format_date($t['interview_at'], 'D, j M')) ?><br><span class="muted"><?= e(format_date($t['interview_at'], 'g:i A')) ?></span></span></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="panel-body"><p class="muted small mb-0">No interviews scheduled. When you get an interview, add the date in your tracker and it will appear here.</p></div>
        <?php endif; ?>
      </section>

      <section class="card" aria-labelledby="recent-title">
        <div class="panel-head"><h2 id="recent-title">Recent applications</h2><a class="small" href="<?= e(url('account/applications.php')) ?>">Open tracker</a></div>
        <?php if ($recent): ?>
          <ul class="list-plain">
            <?php foreach ($recent as $t): ?>
              <li><div style="flex:1;min-width:0"><strong class="small" style="display:block"><?= e($t['job_title']) ?></strong><span class="small muted"><?= e($t['company']) ?> · <?= e(format_date($t['applied_on'], 'j M')) ?></span></div><?= badge($ts[$t['status']], status_tone($t['status'])) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="panel-body"><p class="muted small">You haven't recorded any applications yet.</p><a class="btn btn-outline btn-sm" href="<?= e(url('account/applications.php#add')) ?>">Add an application</a></div>
        <?php endif; ?>
      </section>
    </div>

    <section class="mt-4" aria-labelledby="rec-title">
      <div class="flex items-center justify-between mb-3 wrap gap-2"><h2 id="rec-title" style="font-size:1.15rem;margin:0">Recommended for you</h2><a class="link-arrow small" href="<?= e(url('matching.php')) ?>">All recommendations <?= icon('arrow-right', '', 16) ?></a></div>
      <?php if ($recs): ?>
        <div class="job-grid">
          <?php foreach ($recs as $r): ?>
            <div>
              <?= job_card($r['job']) ?>
              <p class="small muted mt-2 mb-0"><strong style="color:var(--ink)"><?= $r['match']['score'] ?>/100</strong> · <?= e($r['match']['label']) ?><?= $r['match']['matched'] ? ' · ' . count($r['match']['matched']) . ' matching skills' : '' ?></p>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?= empty_state('Add your skills to see recommendations', 'Recommendations use a simple, transparent scoring of your skills, education, experience and preferences.', '<a class="btn btn-primary" href="' . e(url('account/profile.php')) . '">Update profile</a>', 'target') ?>
      <?php endif; ?>
    </section>

    <div class="grid grid-2 mt-4">
      <a class="card card-hover card-pad flex items-center gap-3" href="<?= e(url('cv-builder.php')) ?>" style="color:inherit;text-decoration:none"><span class="icon-tile" style="margin:0"><?= icon('file', '', 22) ?></span><div><strong>CV Writing &amp; Editing</strong><p class="small muted mb-0">Edit your CV and download it as PDF.</p></div></a>
      <a class="card card-hover card-pad flex items-center gap-3" href="<?= e(url('freshers.php')) ?>" style="color:inherit;text-decoration:none"><span class="icon-tile navy" style="margin:0"><?= icon('compass', '', 22) ?></span><div><strong>Career guidance</strong><p class="small muted mb-0">Roadmaps, interview tips and project ideas.</p></div></a>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
