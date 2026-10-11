<?php
require __DIR__ . '/includes/bootstrap.php';
$id = (int) ($_GET['id'] ?? 0);
$st = db()->prepare('SELECT * FROM companies WHERE id = ?');
$st->execute([$id]);
$co = $st->fetch();
if (!$co) {
    http_response_code(404);
    $title = 'Company not found';
    require __DIR__ . '/includes/partials/header.php';
    echo '<div class="container" style="padding-top:84px;padding-bottom:84px">' . empty_state('This company could not be found', 'The link may be incorrect or the employer account was removed.', '<a class="btn btn-primary" href="' . e(url('jobs.php')) . '">Browse jobs</a>', 'building') . '</div>';
    require __DIR__ . '/includes/partials/footer.php';
    exit;
}
$jobs = db()->prepare(job_select_sql() . ' WHERE j.company_id = ? AND ' . public_job_condition() . ' ORDER BY j.created_at DESC');
$jobs->execute([$id]);
$jobs = $jobs->fetchAll();
$closed = db()->prepare("SELECT COUNT(*) FROM jobs j WHERE j.company_id = ? AND j.status <> 'draft' AND NOT (" . public_job_condition() . ')');
$closed->execute([$id]);
$closedCount = (int) $closed->fetchColumn();

$title = $co['name'];
$nav = 'jobs';
$metaDesc = 'Company profile and open jobs from ' . $co['name'] . ' on CareerBridge.';
require __DIR__ . '/includes/partials/header.php';
?>
<section class="page-hero">
  <div class="container">
    <ol class="breadcrumbs"><li><a href="<?= e(url('index.php')) ?>">Home</a></li><li><a href="<?= e(url('jobs.php')) ?>">Jobs</a></li><li aria-current="page"><?= e($co['name']) ?></li></ol>
    <div class="detail-head">
      <span class="logo-tile lg <?= logo_tone((int) $co['id']) ?>" aria-hidden="true"><?= e(initials($co['name'])) ?></span>
      <div>
        <h1 class="mb-0"><?= e($co['name']) ?> <?php if ($co['is_demo']): ?><span class="badge badge-demo" style="vertical-align:middle">Demo company</span><?php endif; ?></h1>
        <p class="muted mb-0" style="margin-top:6px">
          <?= e($co['industry'] ?: 'Employer') ?><?php if ($co['district']): ?> · <?= icon('pin', '', 16) ?> <?= e($co['district']) ?><?php endif; ?><?php if ($co['size']): ?> · <?= e($co['size']) ?> employees<?php endif; ?>
        </p>
      </div>
    </div>
  </div>
</section>
<div class="container detail-layout">
  <div>
    <section class="card card-pad-lg mb-4">
      <h2 style="font-size:1.15rem">About the company</h2>
      <?php if ($co['description']): ?>
        <div class="prose"><p><?= nl2br(e($co['description'])) ?></p></div>
      <?php else: ?>
        <p class="muted mb-0">This employer has not added a description yet.</p>
      <?php endif; ?>
      <?php if ($co['is_demo']): ?><p class="small muted mb-0 mt-3"><?= icon('info', '', 14) ?> This is a fictional demo company created for the CareerBridge university project.</p><?php endif; ?>
    </section>
    <h2 style="font-size:1.25rem" id="open-jobs"><?= count($jobs) ?> open <?= count($jobs) === 1 ? 'job' : 'jobs' ?></h2>
    <?php if ($jobs): ?>
      <div class="job-list"><?php foreach ($jobs as $j) echo job_card($j, 'row'); ?></div>
    <?php else: ?>
      <?= empty_state('No open jobs right now', $closedCount ? 'This employer has ' . $closedCount . ' closed or expired listing(s). Check back later for new opportunities.' : 'Check back later for new opportunities.', '<a class="btn btn-outline" href="' . e(url('jobs.php')) . '">Browse other jobs</a>', 'briefcase') ?>
    <?php endif; ?>
  </div>
  <aside class="sticky-side">
    <div class="card card-pad">
      <h2 style="font-size:1rem">Company details</h2>
      <dl class="kv">
        <?php if ($co['industry']): ?><dt>Industry</dt><dd><?= e($co['industry']) ?></dd><?php endif; ?>
        <?php if ($co['size']): ?><dt>Size</dt><dd><?= e($co['size']) ?></dd><?php endif; ?>
        <?php if ($co['district']): ?><dt>Location</dt><dd><?= e(trim(($co['address'] ? $co['address'] . ', ' : '') . $co['district'])) ?></dd><?php endif; ?>
        <?php if ($co['website']): ?><dt>Website</dt><dd><?php if ($co['is_demo']): ?><span class="muted">Not available (demo)</span><?php else: ?><a href="<?= e(preg_match('#^https?://#i', $co['website']) ? $co['website'] : 'https://' . $co['website']) ?>" rel="noopener nofollow" target="_blank"><?= e(preg_replace('#^https?://#i', '', $co['website'])) ?> <?= icon('external', '', 14) ?></a><?php endif; ?></dd><?php endif; ?>
        <dt>On CareerBridge since</dt><dd><?= e(format_date($co['created_at'], 'M Y')) ?></dd>
      </dl>
    </div>
    <div class="callout small"><strong>Stay safe:</strong> genuine employers never ask for a fee to apply. <a href="<?= e(url('guide.php?slug=job-application-guidance')) ?>">How to spot job scams</a></div>
  </aside>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
