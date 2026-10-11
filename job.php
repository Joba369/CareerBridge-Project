<?php
require __DIR__ . '/includes/bootstrap.php';

$id = (int) ($_GET['id'] ?? 0);
$job = $id ? get_job($id) : null;
$user = current_user();
$company = null;
$isOwner = false;
if ($job && $user && $user['role'] === 'employer') {
    $company = employer_company((int) $user['id']);
    $isOwner = $company && (int) $company['id'] === (int) $job['company_id'];
}
if (!$job || ($job['status'] === 'draft' && !$isOwner)) {
    http_response_code(404);
    $title = 'Job not found';
    require __DIR__ . '/includes/partials/header.php';
    echo '<div class="container" style="padding-top:84px;padding-bottom:84px">' . empty_state('This job could not be found', 'It may have been removed by the employer, or the link may be incorrect.', '<a class="btn btn-primary" href="' . e(url('jobs.php')) . '">Browse current jobs</a>', 'briefcase') . '</div>';
    require __DIR__ . '/includes/partials/footer.php';
    exit;
}
if (!$isOwner) db()->prepare('UPDATE jobs SET views = views + 1 WHERE id = ?')->execute([$id]);

$open = job_is_open($job);
$types = employment_types();
$arr = work_arrangements();
$levels = experience_levels();
$edu = education_levels();
$left = days_left($job['deadline']);

$application = null;
$match = null;
if ($user && $user['role'] === 'seeker') {
    $st = db()->prepare('SELECT id, status, created_at FROM applications WHERE job_id = ? AND user_id = ?');
    $st->execute([$id, $user['id']]);
    $application = $st->fetch() ?: null;
    $profile = seeker_profile((int) $user['id']);
    if ($profile && !empty($profile['skills'])) $match = match_job(matcher_profile($profile), $job);
}
$accFeatures = array_filter(accessibility_features(), fn($k) => !empty($job[$k]), ARRAY_FILTER_USE_KEY);

$similar = db()->prepare(job_select_sql() . ' WHERE ' . public_job_condition() . ' AND j.category_id = ? AND j.id <> ? ORDER BY j.created_at DESC LIMIT 3');
$similar->execute([$job['category_id'], $id]);
$similar = $similar->fetchAll();

$lines = fn(?string $t) => array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $t))));

$title = $job['title'] . ' at ' . $job['company_name'];
$metaDesc = mb_substr($job['description'], 0, 155);
$nav = $job['category_group'] === 'it' ? 'remote' : (in_array($job['employment_type'], ['part_time', 'temporary', 'shift'], true) ? 'local' : 'jobs');
require __DIR__ . '/includes/partials/header.php';
?>
<div class="container">
  <div class="detail-layout">
    <article>
      <ol class="breadcrumbs">
        <li><a href="<?= e(url('index.php')) ?>">Home</a></li>
        <li><a href="<?= e(url('jobs.php')) ?>">Jobs</a></li>
        <li><a href="<?= e(url('jobs.php?category=' . $job['category_slug'])) ?>"><?= e($job['category_name']) ?></a></li>
      </ol>

      <div class="card card-pad-lg">
        <div class="detail-head">
          <span class="logo-tile lg <?= logo_tone((int) $job['company_id']) ?>" aria-hidden="true"><?= e(initials($job['company_name'])) ?></span>
          <div style="flex:1;min-width:0">
            <h1><?= e($job['title']) ?></h1>
            <div class="flex items-center gap-2 wrap">
              <a href="<?= e(url('company.php?id=' . $job['company_id'])) ?>" style="font-weight:600"><?= e($job['company_name']) ?></a>
              <?php if ($job['is_demo']): ?><span class="badge badge-demo">Demo listing</span><?php endif; ?>
              <?php if ($job['status'] === 'closed'): ?><?= badge('Closed', 'slate') ?><?php elseif ($job['status'] === 'draft'): ?><?= badge('Draft — only you can see this', 'grey') ?><?php elseif (!$open): ?><?= badge('Deadline passed', 'red') ?><?php endif; ?>
              <span class="small muted">· Posted <?= e(time_ago($job['created_at'])) ?></span>
            </div>
          </div>
        </div>

        <?php if ($job['is_demo']): ?>
          <div class="notice mt-4"><?= icon('info', '', 18) ?><div>This is a <strong>fictional demo listing</strong> created to demonstrate CareerBridge. It is not a real vacancy, and the employer shown is not a real company.</div></div>
        <?php endif; ?>

        <dl class="detail-meta">
          <div><?= icon('pin') ?><span><dt>Location</dt><dd><?= e(format_location($job)) ?></dd></span></div>
          <div><?= icon('wallet') ?><span><dt>Salary / pay</dt><dd><?= e(format_salary($job)) ?></dd></span></div>
          <div><?= icon('briefcase') ?><span><dt>Type</dt><dd><?= e($types[$job['employment_type']]) ?> · <?= e($arr[$job['work_arrangement']]) ?></dd></span></div>
          <div><?= icon('clock') ?><span><dt>Working hours</dt><dd><?= e($job['working_hours'] ?: 'Not specified') ?></dd></span></div>
          <div><?= icon('chart') ?><span><dt>Experience</dt><dd><?= e($levels[$job['experience_level']]) ?></dd></span></div>
          <div><?= icon('cap') ?><span><dt>Education</dt><dd><?= e($edu[(int) $job['education_level']]) ?></dd></span></div>
          <div><?= icon('grid') ?><span><dt>Vacancies</dt><dd><?= (int) $job['vacancies'] ?></dd></span></div>
          <div><?= icon('calendar') ?><span><dt>Application deadline</dt><dd><?= $job['deadline'] ? e(format_date($job['deadline'], 'l, j F Y')) : 'Open until filled' ?></dd></span></div>
        </dl>

        <div class="prose">
          <h2>About the role</h2>
          <p><?= nl2br(e($job['description'])) ?></p>

          <?php if ($r = $lines($job['responsibilities'])): ?>
            <h2>Responsibilities</h2>
            <ul><?php foreach ($r as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>

          <?php if ($r = $lines($job['requirements'])): ?>
            <h2>Requirements</h2>
            <ul><?php foreach ($r as $l): ?><li><?= e($l) ?></li><?php endforeach; ?></ul>
          <?php endif; ?>

          <h2>Skills</h2>
          <?php $req = skills_list($job['required_skills']); $pref = skills_list($job['preferred_skills']); ?>
          <?php if (!$req && !$pref): ?><p>No specific skills listed. Training may be provided.</p><?php endif; ?>
          <?php if ($req): ?><p class="mb-0"><strong>Required</strong></p><div class="chips mb-4 mt-2"><?php foreach ($req as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
          <?php if ($pref): ?><p class="mb-0"><strong>Preferred (nice to have)</strong></p><div class="chips mb-4 mt-2"><?php foreach ($pref as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>

          <h2 id="accessibility">Workplace accessibility &amp; flexibility</h2>
          <?php if ($accFeatures): ?>
            <p class="small muted">The following information was provided by the employer.</p>
            <ul class="acc-list">
              <?php foreach ($accFeatures as $a): ?><li><span class="icon-tile"><?= icon($a['icon'], '', 18) ?></span><?= e($a['label']) ?></li><?php endforeach; ?>
            </ul>
            <?php if ($job['acc_notes']): ?><div class="note-box mt-3"><?= e($job['acc_notes']) ?></div><?php endif; ?>
          <?php else: ?>
            <p>The employer has not provided accessibility information for this listing. You are welcome to ask them directly — you do not need to share medical details.</p>
          <?php endif; ?>

          <h2>How to apply</h2>
          <p><?= nl2br(e($job['how_to_apply'] ?: 'Apply through CareerBridge using the Apply button.')) ?></p>
          <div class="callout small"><strong>Stay safe:</strong> CareerBridge employers should never ask you to pay a fee to apply or to get a job. <a href="<?= e(url('guide.php?slug=job-application-guidance')) ?>">Read our safety tips</a>.</div>
        </div>
      </div>
    </article>

    <aside class="sticky-side" aria-label="Apply and job summary">
      <div class="card card-pad">
        <p class="salary" style="font-size:1.15rem;margin-bottom:4px"><?= e(format_salary($job)) ?></p>
        <p class="small muted">
          <?php if ($left !== null && $open): ?>
            <?= $left === 0 ? '<span class="deadline-soon">Closes today</span>' : ($left <= 3 ? '<span class="deadline-soon">Closes in ' . $left . ' days</span>' : 'Closes in ' . $left . ' days') ?>
          <?php elseif (!$open): ?>Applications are closed.<?php else: ?>Open until filled<?php endif; ?>
        </p>

        <?php if ($isOwner): ?>
          <a class="btn btn-primary btn-block" href="<?= e(url('employer/job-edit.php?id=' . $id)) ?>"><?= icon('edit', '', 18) ?> Edit listing</a>
          <a class="btn btn-outline btn-block mt-2" href="<?= e(url('employer/applications.php?job=' . $id)) ?>"><?= icon('inbox', '', 18) ?> View applications</a>
        <?php elseif ($user && $user['role'] === 'employer'): ?>
          <div class="notice"><?= icon('info', '', 18) ?><div>You are signed in as an employer. Switch to a job seeker account to apply.</div></div>
        <?php elseif ($application): ?>
          <div class="alert alert-success"><?= icon('check-circle', '', 18) ?><div>You applied on <?= e(format_date($application['created_at'])) ?>.<br>Employer status: <strong><?= e(application_statuses()[$application['status']]) ?></strong></div></div>
          <a class="btn btn-outline btn-block mt-2" href="<?= e(url('account/applications.php')) ?>">Open application tracker</a>
        <?php elseif ($open): ?>
          <a class="btn btn-primary btn-block btn-lg" href="<?= e(url('apply.php?id=' . $id)) ?>"><?= icon('send', '', 18) ?> Apply now</a>
        <?php else: ?>
          <button class="btn btn-outline btn-block" disabled>Applications closed</button>
        <?php endif; ?>

        <?php if (!$user): ?>
          <a class="btn btn-outline btn-block mt-2" href="<?= e(url('login.php?next=' . urlencode(url('job.php?id=' . $id)))) ?>"><?= icon('bookmark', '', 18) ?> Sign in to save</a>
        <?php elseif ($user['role'] === 'seeker'): $saved = in_array($id, saved_job_ids(), true); ?>
          <form method="post" action="<?= e(url('api/save-job.php')) ?>" data-save-form class="mt-2">
            <?= csrf_field() ?><input type="hidden" name="job_id" value="<?= $id ?>"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI']) ?>">
            <button class="btn btn-outline btn-block <?= $saved ? 'saved' : '' ?>" type="submit" aria-pressed="<?= $saved ? 'true' : 'false' ?>"><?= icon('bookmark', '', 18) ?> <span data-save-text><?= $saved ? 'Saved' : 'Save job' ?></span></button>
          </form>
        <?php endif; ?>
      </div>

      <?php if ($match): ?>
        <div class="card card-pad">
          <div class="flex items-center gap-3">
            <div class="score" style="--p: <?= $match['score'] ?>"><b><?= $match['score'] ?></b></div>
            <div><strong><?= e($match['label']) ?></strong><p class="small muted mb-0">Rule-based score from your profile</p></div>
          </div>
          <?php if ($match['matched']): ?><p class="small mt-3 mb-0"><strong>Matching skills</strong></p><div class="chips mt-2"><?php foreach ($match['matched'] as $s): ?><span class="chip chip-match"><?= icon('check', '', 12) ?><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
          <?php $gaps = array_merge($match['missing_required'], $match['missing_preferred']); if ($gaps): ?><p class="small mt-3 mb-0"><strong>Skills to develop</strong></p><div class="chips mt-2"><?php foreach ($gaps as $s): ?><span class="chip chip-gap"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
          <a class="link-arrow small mt-3" href="<?= e(url('matching.php')) ?>">How matching works <?= icon('arrow-right', '', 16) ?></a>
        </div>
      <?php endif; ?>

      <div class="card card-pad">
        <div class="flex items-center gap-3 mb-3">
          <span class="logo-tile <?= logo_tone((int) $job['company_id']) ?>" aria-hidden="true"><?= e(initials($job['company_name'])) ?></span>
          <div><strong><?= e($job['company_name']) ?></strong><p class="small muted mb-0"><?= e($job['company_district'] ?: 'Bangladesh') ?></p></div>
        </div>
        <a class="link-arrow small" href="<?= e(url('company.php?id=' . $job['company_id'])) ?>">Company profile &amp; jobs <?= icon('arrow-right', '', 16) ?></a>
      </div>
    </aside>
  </div>

  <?php if ($similar): ?>
    <section class="mb-4" style="padding-bottom:64px" aria-labelledby="similar-title">
      <h2 id="similar-title" style="font-size:1.35rem">Similar jobs</h2>
      <div class="job-grid"><?php foreach ($similar as $s) echo job_card($s); ?></div>
    </section>
  <?php endif; ?>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
