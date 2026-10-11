<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login('seeker');

$st = db()->prepare(job_select_sql() . ' JOIN saved_jobs s ON s.job_id = j.id WHERE s.user_id = ? ORDER BY s.created_at DESC');
$st->execute([$user['id']]);
$jobs = $st->fetchAll();

$title = 'Saved jobs';
$dashActive = 'saved';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title"><div><h1>Saved jobs</h1><p>Jobs you bookmarked. Click the bookmark again to remove a job.</p></div></div>
    <?php if ($jobs): ?>
      <div class="job-list">
        <?php foreach ($jobs as $job): ?>
          <?php if (!job_is_open($job)): ?><p class="small mb-0" style="margin-top:6px"><?= badge($job['status'] === 'closed' ? 'Listing closed' : 'Deadline passed', 'slate') ?></p><?php endif; ?>
          <?= str_replace('data-save-form', 'data-save-form data-remove-card', job_card($job, 'row')) ?>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <?= empty_state('No saved jobs yet', 'Use the bookmark icon on any job to save it here for later.', '<a class="btn btn-primary" href="' . e(url('jobs.php')) . '">Browse jobs</a>', 'bookmark') ?>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
