<?php
require __DIR__ . '/../includes/bootstrap.php';
[$user, $company] = employer_context();
$cid = (int) $company['id'];

$s = db()->prepare("SELECT
   (SELECT COUNT(*) FROM jobs WHERE company_id = :c AND status = 'published') active_n,
   (SELECT COUNT(*) FROM jobs WHERE company_id = :c AND status = 'draft') draft_n,
   (SELECT COUNT(*) FROM jobs WHERE company_id = :c AND status = 'closed') closed_n,
   (SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = :c) apps_n,
   (SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = :c AND a.status = 'submitted') new_n,
   (SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = :c AND a.interview_at >= NOW()) iv_n");
$s->execute([':c' => $cid]);
$s = $s->fetch();

$recent = db()->prepare('SELECT a.id, a.full_name, a.status, a.created_at, j.title FROM applications a JOIN jobs j ON j.id = a.job_id WHERE j.company_id = ? ORDER BY a.created_at DESC LIMIT 6');
$recent->execute([$cid]);
$recent = $recent->fetchAll();

$jobs = db()->prepare("SELECT j.*, (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) apps FROM jobs j WHERE j.company_id = ? AND j.status = 'published' ORDER BY j.created_at DESC LIMIT 5");
$jobs->execute([$cid]);
$jobs = $jobs->fetchAll();

$profileDone = array_filter([$company['industry'], $company['district'], $company['description']]);
$as = application_statuses();

$title = 'Employer dashboard';
$dashActive = 'overview';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1><?= e($company['name']) ?></h1><p>Employer dashboard<?= $company['is_demo'] ? ' · <span class="badge badge-demo">Demo company</span>' : '' ?></p></div>
      <a class="btn btn-primary" href="<?= e(url('employer/job-edit.php')) ?>"><?= icon('plus', '', 18) ?> Post a job</a>
    </div>

    <?php if (count($profileDone) < 3): ?>
      <div class="notice mb-4"><?= icon('building', '', 18) ?><div><strong>Complete your company profile.</strong> Job seekers are more likely to apply when they know who you are. <a href="<?= e(url('employer/company.php')) ?>">Edit company profile</a></div></div>
    <?php endif; ?>

    <div class="stat-grid">
      <div class="card stat"><div class="k"><?= icon('briefcase', '', 16) ?> Active listings</div><div class="v"><?= (int) $s['active_n'] ?></div></div>
      <div class="card stat"><div class="k"><?= icon('inbox', '', 16) ?> Applications</div><div class="v"><?= (int) $s['apps_n'] ?></div></div>
      <div class="card stat"><div class="k"><?= icon('star', '', 16) ?> New (unreviewed)</div><div class="v"><?= (int) $s['new_n'] ?></div></div>
      <div class="card stat"><div class="k"><?= icon('calendar', '', 16) ?> Upcoming interviews</div><div class="v"><?= (int) $s['iv_n'] ?></div></div>
    </div>

    <div class="grid grid-2" style="align-items:start">
      <section class="card" aria-labelledby="ra">
        <div class="panel-head"><h2 id="ra">Recent applications</h2><a class="small" href="<?= e(url('employer/applications.php')) ?>">View all</a></div>
        <?php if ($recent): ?>
          <ul class="list-plain">
            <?php foreach ($recent as $a): ?>
              <li><span class="initials"><?= e(initials($a['full_name'])) ?></span><div style="flex:1;min-width:0"><a class="small" style="font-weight:600;color:var(--ink);display:block" href="<?= e(url('employer/application.php?id=' . $a['id'])) ?>"><?= e($a['full_name']) ?></a><span class="small muted"><?= e($a['title']) ?> · <?= e(time_ago($a['created_at'])) ?></span></div><?= badge($as[$a['status']], status_tone($a['status'])) ?></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="panel-body"><p class="small muted mb-0">No applications yet. They will appear here when job seekers apply to your listings.</p></div>
        <?php endif; ?>
      </section>

      <section class="card" aria-labelledby="al">
        <div class="panel-head"><h2 id="al">Active listings</h2><a class="small" href="<?= e(url('employer/jobs.php')) ?>">Manage all</a></div>
        <?php if ($jobs): ?>
          <ul class="list-plain">
            <?php foreach ($jobs as $j): ?>
              <li><div style="flex:1;min-width:0"><a class="small" style="font-weight:600;color:var(--ink);display:block" href="<?= e(url('employer/applications.php?job=' . $j['id'])) ?>"><?= e($j['title']) ?></a><span class="small muted"><?= e(plural((int) $j['apps'], 'application')) ?> · <?= e(plural((int) $j['views'], 'view')) ?><?= $j['deadline'] ? ' · closes ' . e(format_date($j['deadline'], 'j M')) : '' ?></span></div><a class="btn btn-ghost btn-sm" href="<?= e(url('employer/job-edit.php?id=' . $j['id'])) ?>" aria-label="Edit <?= e($j['title']) ?>"><?= icon('edit', '', 16) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="panel-body"><p class="small muted">You have no active listings.</p><a class="btn btn-outline btn-sm" href="<?= e(url('employer/job-edit.php')) ?>">Post your first job</a></div>
        <?php endif; ?>
        <div class="panel-body small muted" style="border-top:1px solid var(--line-2)"><?= e(plural((int) $s['draft_n'], 'draft')) ?> · <?= e(plural((int) $s['closed_n'], 'closed listing')) ?></div>
      </section>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
