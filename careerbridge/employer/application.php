<?php
require __DIR__ . '/../includes/bootstrap.php';
[$user, $company] = employer_context();
$cid = (int) $company['id'];
$statuses = application_statuses();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
// Permission check: the application must belong to a job owned by this employer's company.
$st = db()->prepare('SELECT a.*, j.title AS job_title, j.id AS job_id FROM applications a JOIN jobs j ON j.id = a.job_id WHERE a.id = ? AND j.company_id = ?');
$st->execute([$id, $cid]);
$app = $st->fetch();
if (!$app) {
    http_response_code(404);
    flash('error', 'That application was not found, or it belongs to another company.');
    redirect('employer/applications.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $status = $_POST['status'] ?? '';
    if (!array_key_exists($status, $statuses) || $status === 'withdrawn') {
        $errors['status'] = 'Choose a valid status.';
    }
    if ($app['status'] === 'withdrawn') $errors['status'] = 'The applicant withdrew this application, so its status cannot be changed.';
    $iv = trim((string) ($_POST['interview_at'] ?? ''));
    $ivDt = null;
    if ($iv !== '') {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $iv);
        if (!$d) $errors['interview_at'] = 'Enter a valid date and time.';
        else $ivDt = $d->format('Y-m-d H:i:s');
    }
    if ($status === 'interview' && !$ivDt) $errors['interview_at'] = 'Add the interview date and time so the applicant can see it.';
    $note = input_str($_POST, 'employer_note', 3000);
    if (!$errors) {
        db()->prepare('UPDATE applications a JOIN jobs j ON j.id = a.job_id SET a.status = ?, a.interview_at = ?, a.employer_note = ? WHERE a.id = ? AND j.company_id = ?')
            ->execute([$status, $ivDt, $note ?: null, $id, $cid]);
        // Keep the applicant's tracker in step with the employer's decision (their private notes are untouched).
        $trackerMap = ['submitted' => 'applied', 'under_review' => 'under_review', 'shortlisted' => 'under_review', 'interview' => 'interview', 'offer' => 'offer', 'rejected' => 'rejected'];
        if (isset($trackerMap[$status])) {
            db()->prepare('UPDATE tracker_entries SET status = ?, interview_at = COALESCE(?, interview_at) WHERE application_id = ?')
                ->execute([$trackerMap[$status], $ivDt, $id]);
        }
        flash('success', 'Application status updated to “' . $statuses[$status] . '”. The applicant can see the new status in their tracker.');
        redirect('employer/application.php?id=' . $id);
    }
    $app['status'] = $status; $app['employer_note'] = $note;
}

$cvData = $app['cv_snapshot'] ? json_decode($app['cv_snapshot'], true) : null;
$title = 'Application from ' . $app['full_name'];
$dashActive = 'apps';
$extraCss = ['css/cv.css'];
if ($cvData) $extraJs = ['js/cv-render.js'];
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <ol class="breadcrumbs"><li><a href="<?= e(url('employer/applications.php')) ?>">Applications</a></li><li><a href="<?= e(url('employer/applications.php?job=' . $app['job_id'])) ?>"><?= e($app['job_title']) ?></a></li></ol>
    <div class="dash-title">
      <div class="flex items-center gap-3"><span class="initials" style="width:48px;height:48px;font-size:1rem"><?= e(initials($app['full_name'])) ?></span><div><h1><?= e($app['full_name']) ?></h1><p>Applied for <strong><?= e($app['job_title']) ?></strong> · <?= e(format_date($app['created_at'], 'j M Y, g:i A')) ?></p></div></div>
      <?= badge($statuses[$app['status']], status_tone($app['status'])) ?>
    </div>

    <div class="grid" style="grid-template-columns:minmax(0,1fr) 320px;align-items:start" data-app-grid>
      <div class="stack">
        <section class="card card-pad" aria-labelledby="ci">
          <h2 id="ci" style="font-size:1.05rem">Applicant information</h2>
          <dl class="kv" style="grid-template-columns:140px 1fr">
            <dt>Email</dt><dd style="text-align:left"><a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a></dd>
            <dt>Phone</dt><dd style="text-align:left"><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $app['phone'])) ?>"><?= e($app['phone']) ?></a></dd>
            <dt>Education</dt><dd style="text-align:left"><?= e($app['education_summary'] ?: '—') ?></dd>
          </dl>
          <?php if ($app['skills']): ?><p class="small mt-3 mb-0"><strong>Skills</strong></p><div class="chips mt-2"><?php foreach (skills_list($app['skills']) as $s): ?><span class="chip"><?= e($s) ?></span><?php endforeach; ?></div><?php endif; ?>
          <?php if ($app['cover_letter']): ?><p class="small mt-3 mb-0"><strong>Message from the applicant</strong></p><div class="note-box"><?= e($app['cover_letter']) ?></div><?php endif; ?>
        </section>

        <?php if ($app['accommodation_request']): ?>
          <section class="card card-pad" aria-labelledby="acc" style="border-color:#bfe3cd">
            <h2 id="acc" style="font-size:1.05rem"><?= icon('door-open', '', 18) ?> Interview accommodation request</h2>
            <p class="small muted">Shared voluntarily by the applicant for this application only. Please treat it confidentially and use it only to arrange the interview.</p>
            <div class="note-box"><?= e($app['accommodation_request']) ?></div>
          </section>
        <?php endif; ?>

        <section class="card card-pad" aria-labelledby="cv">
          <h2 id="cv" style="font-size:1.05rem">CV</h2>
          <?php if ($cvData): ?>
            <p class="small muted">A copy of the applicant's CareerBridge CV, saved when they applied.</p>
            <div class="cv-viewer"><div id="cvPreview" class="cv-page"></div></div>
            <script>window.addEventListener('DOMContentLoaded', function(){ CVRender.render(document.getElementById('cvPreview'), <?= json_encode($cvData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($app['cv_template'] ?: 'classic') ?>); var v = document.querySelector('.cv-viewer'); CVRender.fit(v); window.addEventListener('resize', function(){ CVRender.fit(v); }); });</script>
          <?php elseif ($app['cv_file']): ?>
            <a class="btn btn-outline" href="<?= e(url('cv-file.php?application=' . $app['id'])) ?>"><?= icon('download', '', 18) ?> Download CV (PDF)</a>
          <?php else: ?>
            <p class="small muted mb-0">The applicant applied without a CV.</p>
          <?php endif; ?>
        </section>
      </div>

      <aside>
        <form class="card card-pad" method="post" action="" novalidate>
          <h2 style="font-size:1.05rem">Update progress</h2>
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $app['id'] ?>">
          <?php if ($app['status'] === 'withdrawn'): ?>
            <div class="notice"><?= icon('info', '', 18) ?><div>The applicant withdrew this application.</div></div>
          <?php else: ?>
            <div class="field">
              <label class="label" for="status">Status</label>
              <select class="select<?= invalid($errors, 'status') ?>" id="status" name="status">
                <?php foreach ($statuses as $k => $l): if ($k === 'withdrawn') continue; ?><option value="<?= e($k) ?>"<?= $app['status'] === $k ? ' selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
              </select>
              <?= field_error($errors, 'status') ?>
            </div>
            <div class="field">
              <label class="label" for="interview_at">Interview date &amp; time</label>
              <input class="input<?= invalid($errors, 'interview_at') ?>" id="interview_at" name="interview_at" type="datetime-local" value="<?= $app['interview_at'] ? e(date('Y-m-d\TH:i', strtotime($app['interview_at']))) : '' ?>">
              <?= field_error($errors, 'interview_at') ?>
              <p class="help">Visible to the applicant.</p>
            </div>
            <div class="field">
              <label class="label" for="employer_note">Internal note <span class="opt">(private to your company)</span></label>
              <textarea class="textarea" id="employer_note" name="employer_note" maxlength="3000"><?= e($app['employer_note']) ?></textarea>
            </div>
            <button class="btn btn-primary btn-block" type="submit">Save update</button>
          <?php endif; ?>
        </form>
        <p class="small muted mt-3">The applicant sees the status and interview time, never your internal note.</p>
      </aside>
    </div>
  </div>
</div>
<style>@media (max-width: 1100px){ [data-app-grid]{ grid-template-columns: minmax(0,1fr) !important; } }</style>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
