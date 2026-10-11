<?php
require __DIR__ . '/../includes/bootstrap.php';
[$user, $company] = employer_context();
$cid = (int) $company['id'];

if (is_post()) {
    verify_csrf();
    $job = owned_job((int) ($_POST['id'] ?? 0), $cid);
    if (!$job) {
        http_response_code(403);
        flash('error', 'You can only manage your own company\'s job listings.');
        redirect('employer/jobs.php');
    }
    switch ($_POST['action'] ?? '') {
        case 'publish':
        case 'reopen':
            if ($job['deadline'] && $job['deadline'] < date('Y-m-d')) {
                flash('error', 'The deadline for “' . $job['title'] . '” has passed. Edit the listing and set a new deadline before publishing.');
                redirect('employer/job-edit.php?id=' . $job['id']);
            }
            db()->prepare("UPDATE jobs SET status = 'published' WHERE id = ? AND company_id = ?")->execute([$job['id'], $cid]);
            flash('success', '“' . $job['title'] . '” is now published and visible to job seekers.');
            break;
        case 'close':
            db()->prepare("UPDATE jobs SET status = 'closed' WHERE id = ? AND company_id = ?")->execute([$job['id'], $cid]);
            flash('success', '“' . $job['title'] . '” has been closed. It no longer accepts applications.');
            break;
        case 'delete':
            if ($job['status'] !== 'draft') {
                flash('error', 'Only drafts can be deleted. Close published listings instead, so applicants keep their records.');
            } else {
                db()->prepare("DELETE FROM jobs WHERE id = ? AND company_id = ? AND status = 'draft'")->execute([$job['id'], $cid]);
                flash('success', 'Draft deleted.');
            }
            break;
    }
    redirect(safe_return($_POST['return'] ?? null, 'employer/jobs.php'));
}

$tab = in_array($_GET['tab'] ?? '', ['published', 'draft', 'closed'], true) ? $_GET['tab'] : 'published';
$counts = db()->prepare('SELECT status, COUNT(*) FROM jobs WHERE company_id = ? GROUP BY status');
$counts->execute([$cid]);
$counts = $counts->fetchAll(PDO::FETCH_KEY_PAIR);
$st = db()->prepare("SELECT j.*, cat.name AS category_name,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) apps,
        (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'submitted') new_apps
     FROM jobs j JOIN categories cat ON cat.id = j.category_id WHERE j.company_id = ? AND j.status = ? ORDER BY j.updated_at DESC");
$st->execute([$cid, $tab]);
$jobs = $st->fetchAll();
$types = employment_types();

$title = 'Job listings';
$dashActive = 'jobs';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1>Job listings</h1><p>Create, publish, edit and close your vacancies.</p></div>
      <a class="btn btn-primary" href="<?= e(url('employer/job-edit.php')) ?>"><?= icon('plus', '', 18) ?> Post a job</a>
    </div>
    <nav class="tabs" aria-label="Listing status">
      <?php foreach (['published' => 'Active', 'draft' => 'Drafts', 'closed' => 'Closed'] as $k => $l): ?>
        <a href="?tab=<?= $k ?>"<?= $tab === $k ? ' aria-current="page"' : '' ?>><?= e($l) ?> <span class="count"><?= (int) ($counts[$k] ?? 0) ?></span></a>
      <?php endforeach; ?>
    </nav>

    <?php if (!$jobs): ?>
      <?= empty_state(['published' => 'No active listings', 'draft' => 'No drafts', 'closed' => 'No closed listings'][$tab], $tab === 'published' ? 'Post a job to start receiving applications.' : 'Listings will appear here when their status changes.', $tab === 'published' ? '<a class="btn btn-primary" href="' . e(url('employer/job-edit.php')) . '">Post a job</a>' : '', 'briefcase') ?>
    <?php else: ?>
      <div class="card table-wrap">
        <table class="table stack">
          <thead><tr><th scope="col">Job</th><th scope="col">Applications</th><th scope="col">Views</th><th scope="col">Deadline</th><th scope="col"><span class="sr-only">Actions</span></th></tr></thead>
          <tbody>
            <?php foreach ($jobs as $j): $expired = $j['deadline'] && $j['deadline'] < date('Y-m-d'); ?>
              <tr>
                <td class="td-main">
                  <a class="title-link" href="<?= e(url('job.php?id=' . $j['id'])) ?>"><?= e($j['title']) ?></a>
                  <span class="sub"><?= e($j['category_name']) ?> · <?= e($types[$j['employment_type']]) ?> · <?= e(format_location($j)) ?></span>
                  <?php if ($expired && $tab === 'published'): ?><span class="sub" style="color:var(--red)">Deadline passed — hidden from search</span><?php endif; ?>
                </td>
                <td data-label="Applications"><a href="<?= e(url('employer/applications.php?job=' . $j['id'])) ?>"><?= (int) $j['apps'] ?></a><?php if ($j['new_apps']): ?> <?= badge($j['new_apps'] . ' new', 'blue') ?><?php endif; ?></td>
                <td data-label="Views"><?= (int) $j['views'] ?></td>
                <td data-label="Deadline"><?= $j['deadline'] ? e(format_date($j['deadline'])) : '—' ?></td>
                <td data-label="Actions">
                  <div class="flex gap-2 wrap" style="justify-content:flex-end">
                    <a class="btn btn-outline btn-sm" href="<?= e(url('employer/job-edit.php?id=' . $j['id'])) ?>"><?= icon('edit', '', 14) ?> Edit</a>
                    <form method="post" action=""<?= $tab === 'published' ? ' data-confirm="Close this listing? It will stop accepting applications."' : ($tab === 'draft' ? '' : '') ?>>
                      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
                      <?php if ($tab === 'published'): ?><button class="btn btn-outline btn-sm" name="action" value="close">Close</button>
                      <?php elseif ($tab === 'draft'): ?><button class="btn btn-primary btn-sm" name="action" value="publish">Publish</button>
                      <?php else: ?><button class="btn btn-outline btn-sm" name="action" value="reopen">Reopen</button><?php endif; ?>
                    </form>
                    <?php if ($tab === 'draft'): ?>
                      <form method="post" action="" data-confirm="Delete this draft permanently?"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $j['id'] ?>"><button class="btn btn-danger btn-sm" name="action" value="delete" aria-label="Delete draft <?= e($j['title']) ?>"><?= icon('trash', '', 14) ?></button></form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
