<?php
require __DIR__ . '/includes/bootstrap.php';

$user = require_login('seeker');
$id = (int) ($_GET['id'] ?? $_POST['job_id'] ?? 0);
$job = $id ? get_job($id) : null;
if (!$job || $job['status'] === 'draft') { flash('error', 'That job could not be found.'); redirect('jobs.php'); }
if (!job_is_open($job)) { flash('error', 'Applications for this job are closed.'); redirect('job.php?id=' . $id); }

$st = db()->prepare('SELECT id FROM applications WHERE job_id = ? AND user_id = ?');
$st->execute([$id, $user['id']]);
if ($st->fetch()) { flash('info', 'You have already applied for this job. You can follow its progress in your tracker.'); redirect('job.php?id=' . $id); }

$profile = seeker_profile((int) $user['id']);
$cvRow = db()->prepare('SELECT template, data, updated_at FROM cvs WHERE user_id = ?');
$cvRow->execute([$user['id']]);
$cvRow = $cvRow->fetch() ?: null;

$edu = education_levels();
$v = [
    'full_name' => $user['name'],
    'email' => $user['email'],
    'phone' => $profile['phone'] ?? '',
    'education_summary' => $profile['education_detail'] ?? (isset($profile['education_level']) && $profile['education_level'] !== null ? $edu[(int) $profile['education_level']] : ''),
    'skills' => $profile['skills'] ?? '',
    'cover_letter' => '',
    'accommodation_request' => '',
    'cv_option' => $cvRow ? 'builder' : 'none',
];
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach (['full_name' => 120, 'email' => 190, 'phone' => 30, 'education_summary' => 255, 'skills' => 500, 'cover_letter' => 2000, 'accommodation_request' => 1000] as $k => $max) {
        $v[$k] = input_str($_POST, $k, $max);
    }
    $v['cv_option'] = in_array($_POST['cv_option'] ?? '', ['builder', 'upload', 'none'], true) ? $_POST['cv_option'] : 'none';

    if (mb_strlen($v['full_name']) < 2) $errors['full_name'] = 'Please enter your full name.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    $digits = preg_replace('/\D/', '', $v['phone']);
    if (strlen($digits) < 10 || strlen($digits) > 15 || !preg_match('/^[0-9+\-\s()]+$/', $v['phone'])) $errors['phone'] = 'Please enter a valid phone number, e.g. 01712-345678.';
    if (empty($_POST['consent'])) $errors['consent'] = 'Please confirm that you agree to share this information with the employer.';
    if ($v['cv_option'] === 'builder' && !$cvRow) $errors['cv_option'] = 'You have not saved a CareerBridge CV yet. Choose another option or create one first.';

    $storedFile = null;
    if ($v['cv_option'] === 'upload') {
        $f = $_FILES['cv_file'] ?? null;
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) {
            $errors['cv_file'] = 'Please choose a PDF file to upload.';
        } elseif ($f['error'] !== UPLOAD_ERR_OK) {
            $errors['cv_file'] = 'The file could not be uploaded. Please try again (maximum 2 MB).';
        } elseif ($f['size'] > MAX_CV_UPLOAD_BYTES) {
            $errors['cv_file'] = 'The file is larger than 2 MB.';
        } else {
            $head = (string) file_get_contents($f['tmp_name'], false, null, 0, 5);
            if ($head !== '%PDF-' || strtolower(pathinfo($f['name'], PATHINFO_EXTENSION)) !== 'pdf') {
                $errors['cv_file'] = 'Only PDF files are accepted.';
            }
        }
        if (!$errors) {
            $storedFile = bin2hex(random_bytes(16)) . '.pdf';
            if (!move_uploaded_file($f['tmp_name'], __DIR__ . '/uploads/cvs/' . $storedFile)) {
                $errors['cv_file'] = 'The file could not be saved on the server. Check that the uploads/cvs folder is writable.';
                $storedFile = null;
            }
        }
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO applications (job_id, user_id, full_name, email, phone, education_summary, skills, cover_letter, accommodation_request, cv_snapshot, cv_template, cv_file)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$id, $user['id'], $v['full_name'], $v['email'], $v['phone'], $v['education_summary'] ?: null, $v['skills'] ?: null,
                    $v['cover_letter'] ?: null, $v['accommodation_request'] ?: null,
                    $v['cv_option'] === 'builder' ? $cvRow['data'] : null, $v['cv_option'] === 'builder' ? $cvRow['template'] : null, $storedFile]);
            $appId = (int) $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO tracker_entries (user_id, application_id, job_id, job_title, company, location, job_url, applied_on, status)
                           VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'applied')")
                ->execute([$user['id'], $appId, $id, $job['title'], $job['company_name'], format_location($job), url('job.php?id=' . $id)]);
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            if ($storedFile) @unlink(__DIR__ . '/uploads/cvs/' . $storedFile);
            throw $ex;
        }
        flash('success', 'Application sent to ' . $job['company_name'] . '. It has been added to your application tracker.');
        redirect('account/applications.php');
    }
}

$title = 'Apply: ' . $job['title'];
require __DIR__ . '/includes/partials/header.php';
?>
<div class="container" style="max-width:860px;padding-top:28px;padding-bottom:80px">
  <ol class="breadcrumbs"><li><a href="<?= e(url('jobs.php')) ?>">Jobs</a></li><li><a href="<?= e(url('job.php?id=' . $id)) ?>"><?= e($job['title']) ?></a></li><li aria-current="page">Apply</li></ol>

  <div class="card card-pad flex items-center gap-3 mb-4">
    <span class="logo-tile <?= logo_tone((int) $job['company_id']) ?>" aria-hidden="true"><?= e(initials($job['company_name'])) ?></span>
    <div style="flex:1">
      <h1 style="font-size:1.35rem;margin:0">Apply for <?= e($job['title']) ?></h1>
      <p class="muted mb-0"><?= e($job['company_name']) ?> · <?= e(format_location($job)) ?><?php if ($job['is_demo']): ?> · <span class="badge badge-demo">Demo listing</span><?php endif; ?></p>
    </div>
  </div>

  <?php if ($job['is_demo']): ?>
    <div class="notice mb-4"><?= icon('info', '', 18) ?><div>This is a demo listing. Your application will be stored in this local CareerBridge database and shown to the demo employer account only.</div></div>
  <?php endif; ?>
  <?php if ($errors): ?><div class="alert alert-error mb-4" role="alert"><?= icon('alert', '', 18) ?><div>Please fix the highlighted fields below.</div></div><?php endif; ?>

  <form class="card card-pad-lg" method="post" action="" enctype="multipart/form-data" novalidate data-validate>
    <?= csrf_field() ?>
    <input type="hidden" name="job_id" value="<?= $id ?>">

    <fieldset class="fieldset">
      <legend>Contact details</legend>
      <div class="form-row">
        <div class="field">
          <label class="label" for="full_name">Full name <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= invalid($errors, 'full_name') ?>" id="full_name" name="full_name" value="<?= e($v['full_name']) ?>" required autocomplete="name">
          <?= field_error($errors, 'full_name') ?>
        </div>
        <div class="field">
          <label class="label" for="phone">Phone number <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= invalid($errors, 'phone') ?>" id="phone" name="phone" value="<?= e($v['phone']) ?>" required autocomplete="tel" inputmode="tel" placeholder="01712-345678">
          <?= field_error($errors, 'phone') ?>
        </div>
      </div>
      <div class="field">
        <label class="label" for="email">Email address <span class="req" aria-hidden="true">*</span></label>
        <input class="input<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= e($v['email']) ?>" required autocomplete="email">
        <?= field_error($errors, 'email') ?>
      </div>
    </fieldset>

    <fieldset class="fieldset">
      <legend>Your background</legend>
      <div class="field">
        <label class="label" for="education_summary">Education <span class="opt">(optional)</span></label>
        <input class="input" id="education_summary" name="education_summary" value="<?= e($v['education_summary']) ?>" placeholder="e.g. HSC (Science), 2024 · or B.Sc. in CSE (ongoing)">
      </div>
      <div class="field">
        <label class="label" for="skills">Relevant skills <span class="opt">(optional)</span></label>
        <input class="input" id="skills" name="skills" value="<?= e($v['skills']) ?>" placeholder="Separate with commas">
        <?php if ($req = skills_list($job['required_skills'])): ?><p class="help">This job asks for: <?= e(implode(', ', $req)) ?></p><?php endif; ?>
      </div>
      <div class="field">
        <label class="label" for="cover_letter">Short message to the employer <span class="opt">(optional)</span></label>
        <textarea class="textarea" id="cover_letter" name="cover_letter" maxlength="2000" data-maxlength-counter="cl-count" placeholder="2–5 sentences: why you are interested and why you are a good fit."><?= e($v['cover_letter']) ?></textarea>
        <p class="help" id="cl-count"></p>
      </div>
    </fieldset>

    <fieldset class="fieldset">
      <legend>CV</legend>
      <?= field_error($errors, 'cv_option') ?>
      <label class="check"><input type="radio" name="cv_option" value="builder"<?= $v['cv_option'] === 'builder' ? ' checked' : '' ?><?= $cvRow ? '' : ' disabled' ?>>
        <span><strong>Attach my CareerBridge CV</strong><br><span class="small muted"><?= $cvRow ? 'Last saved ' . e(time_ago($cvRow['updated_at'])) . '. A copy is attached, so later edits will not change this application.' : 'You have not saved a CV yet. ' ?><?php if (!$cvRow): ?><a href="<?= e(url('cv-builder.php')) ?>">Create one in the CV builder</a><?php endif; ?></span></span></label>
      <label class="check"><input type="radio" name="cv_option" value="upload"<?= $v['cv_option'] === 'upload' ? ' checked' : '' ?>> <span><strong>Upload a PDF</strong> <span class="small muted">(maximum 2 MB)</span></span></label>
      <div class="field" style="margin-left:28px">
        <label class="sr-only" for="cv_file">CV file (PDF)</label>
        <input class="input<?= invalid($errors, 'cv_file') ?>" type="file" id="cv_file" name="cv_file" accept="application/pdf,.pdf">
        <?= field_error($errors, 'cv_file') ?>
      </div>
      <label class="check"><input type="radio" name="cv_option" value="none"<?= $v['cv_option'] === 'none' ? ' checked' : '' ?>> <span>Apply without a CV <span class="small muted">(fine for many part-time roles)</span></span></label>
    </fieldset>

    <fieldset class="fieldset">
      <legend>Interview accommodations <span class="opt" style="font-weight:400">(optional)</span></legend>
      <p class="small muted">If you need any adjustments for the interview — for example a ground-floor room, extra time, written questions or a video call with captions — you can tell the employer here. Only the employer for this job can see it. You do not need to share any medical information.</p>
      <div class="field">
        <label class="sr-only" for="accommodation_request">Accommodation request</label>
        <textarea class="textarea" id="accommodation_request" name="accommodation_request" maxlength="1000" style="min-height:80px"><?= e($v['accommodation_request']) ?></textarea>
      </div>
    </fieldset>

    <div class="field">
      <label class="check"><input type="checkbox" name="consent" value="1" required> I agree to share the information above with <?= e($job['company_name']) ?> for this application.</label>
      <?= field_error($errors, 'consent') ?>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary btn-lg" type="submit"><?= icon('send', '', 18) ?> Submit application</button>
      <a class="btn btn-ghost" href="<?= e(url('job.php?id=' . $id)) ?>">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
