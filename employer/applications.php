<?php
require __DIR__ . '/../includes/bootstrap.php';
[$user, $company] = employer_context();
$cid = (int) $company['id'];
$statuses = application_statuses();

$jobFilter = (int) ($_GET['job'] ?? 0);
if ($jobFilter && !owned_job($jobFilter, $cid)) { flash('error', 'That listing does not belong to your company.'); redirect('employer/applications.php'); }
$statusFilter = array_key_exists($_GET['status'] ?? '', $statuses) ? $_GET['status'] : '';

$where = 'j.company_id = :cid';
$params = [':cid' => $cid];
if ($jobFilter) { $where .= ' AND a.job_id = :job'; $params[':job'] = $jobFilter; }
$countSt = db()->prepare("SELECT a.status, COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE $where GROUP BY a.status");
$countSt->execute($params);
$counts = $countSt->fetchAll(PDO::FETCH_KEY_PAIR);
if ($statusFilter) { $where .= ' AND a.status = :st'; $params[':st'] = $statusFilter; }

$page = max(1, (int) ($_GET['page'] ?? 1));
$per = 15;
$total = db()->prepare("SELECT COUNT(*) FROM applications a JOIN jobs j ON j.id = a.job_id WHERE $where");
$total->execute($params);
$total = (int) $total->fetchColumn();
$pages = max(1, (int) ceil($total / $per));
$page = min($page, $pages);
$st = db()->prepare("SELECT a.id, a.full_name, a.email, a.phone, a.status, a.created_at, a.interview_at, a.cv_snapshot IS NOT NULL AS has_cv, a.cv_file, j.title, j.id AS job_id
    FROM applications a JOIN jobs j ON j.id = a.job_id WHERE $where ORDER BY a.created_at DESC LIMIT $per OFFSET " . (($page - 1) * $per));
$st->execute($params);
$apps = $st->fetchAll();

$myJobs = db()->prepare('SELECT id, title, status FROM jobs WHERE company_id = ? ORDER BY created_at DESC');
$myJobs->execute([$cid]);
$myJobs = $myJobs->fetchAll();

$title = 'Applications';
$dashActive = 'apps';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title"><div><h1>Applications received</h1><p>Only applications for your own listings are shown here.</p></div></div>

    <form method="get" action="" class="flex gap-2 wrap items-center mb-3">
      <label class="sr-only" for="job">Filter by job</label>
      <select class="select" id="job" name="job" style="max-width:360px" onchange="this.form.submit()">
        <option value="">All job listings</option>
        <?php foreach ($myJobs as $j): ?><option value="<?= (int) $j['id'] ?>"<?= $jobFilter === (int) $j['id'] ? ' selected' : '' ?>><?= e($j['title']) ?><?= $j['status'] !== 'published' ? ' (' . $j['status'] . ')' : '' ?></option><?php endforeach; ?>
      </select>
      <?php if ($statusFilter): ?><input type="hidden" name="status" value="<?= e($statusFilter) ?>"><?php endif; ?>
      <noscript><button class="btn btn-outline btn-sm">Filter</button></noscript>
    </form>
    <nav class="pills" aria-label="Filter by status">
      <a href="<?= e(query_with(['status' => null, 'page' => null])) ?>"<?= !$statusFilter ? ' aria-current="page"' : '' ?>>All (<?= array_sum($counts) ?>)</a>
      <?php foreach ($statuses as $k => $l): ?><a href="<?= e(query_with(['status' => $k, 'page' => null])) ?>"<?= $statusFilter === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?> (<?= (int) ($counts[$k] ?? 0) ?>)</a><?php endforeach; ?>
    </nav>

    <?php if (!$apps): ?>
      <?= empty_state('No applications here yet', 'When job seekers apply to your listings, their applications will appear in this list.', '<a class="btn btn-primary" href="' . e(url('employer/job-edit.php')) . '">Post a job</a>', 'inbox') ?>
    <?php else: ?>
      <div class="card table-wrap">
        <table class="table stack">
          <thead><tr><th scope="col">Applicant</th><th scope="col">Job</th><th scope="col">Applied</th><th scope="col">CV</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Open</span></th></tr></thead>
          <tbody>
          <?php foreach ($apps as $a): ?>
            <tr>
              <td class="td-main"><a class="title-link" href="<?= e(url('employer/application.php?id=' . $a['id'])) ?>"><?= e($a['full_name']) ?></a><span class="sub"><?= e($a['email']) ?> · <?= e($a['phone']) ?></span></td>
              <td data-label="Job"><?= e($a['title']) ?></td>
              <td data-label="Applied"><?= e(format_date($a['created_at'], 'j M Y')) ?></td>
              <td data-label="CV"><?= $a['has_cv'] ? 'CareerBridge CV' : ($a['cv_file'] ? 'PDF' : '—') ?></td>
              <td data-label="Status"><?= badge($statuses[$a['status']], status_tone($a['status'])) ?><?php if ($a['interview_at']): ?><span class="sub"><?= e(format_date($a['interview_at'], 'j M, g:i A')) ?></span><?php endif; ?></td>
              <td data-label=""><a class="btn btn-outline btn-sm" href="<?= e(url('employer/application.php?id=' . $a['id'])) ?>">Review</a></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?= pagination($page, $pages) ?>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
