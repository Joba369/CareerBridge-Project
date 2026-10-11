<?php
require __DIR__ . '/../includes/bootstrap.php';
[$user, $company] = employer_context();
$cid = (int) $company['id'];

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$job = null;
if ($id) {
    $job = owned_job($id, $cid);
    if (!$job) {
        http_response_code(403);
        flash('error', 'You can only edit job listings that belong to your company.');
        redirect('employer/jobs.php');
    }
}

$accKeys = array_keys(accessibility_features());
$defaults = [
    'title' => '', 'category_id' => '', 'description' => '', 'responsibilities' => '', 'requirements' => '', 'how_to_apply' => 'Apply through CareerBridge.',
    'employment_type' => 'part_time', 'work_arrangement' => 'onsite', 'experience_level' => 'fresher', 'education_level' => 0,
    'district' => $company['district'] ?? '', 'area' => '', 'salary_min' => '', 'salary_max' => '', 'salary_period' => 'month',
    'working_hours' => '', 'shift' => '', 'vacancies' => 1, 'required_skills' => '', 'preferred_skills' => '', 'deadline' => date('Y-m-d', strtotime('+21 days')),
    'acc_notes' => '',
];
foreach ($accKeys as $k) $defaults[$k] = 0;
$v = $job ? array_merge($defaults, $job) : $defaults;
$errors = [];

if (is_post()) {
    verify_csrf();
    $intent = $_POST['intent'] ?? 'save'; // publish | draft | save
    $catIds = array_map(fn($c) => (int) $c['id'], categories());
    $v = [
        'title' => input_str($_POST, 'title', 150),
        'category_id' => input_int($_POST, 'category_id'),
        'description' => input_str($_POST, 'description', 5000),
        'responsibilities' => input_str($_POST, 'responsibilities', 3000),
        'requirements' => input_str($_POST, 'requirements', 3000),
        'how_to_apply' => input_str($_POST, 'how_to_apply', 1000),
        'employment_type' => $_POST['employment_type'] ?? '',
        'work_arrangement' => $_POST['work_arrangement'] ?? '',
        'experience_level' => $_POST['experience_level'] ?? '',
        'education_level' => input_int($_POST, 'education_level', 0),
        'district' => in_array($_POST['district'] ?? '', all_districts(), true) ? $_POST['district'] : '',
        'area' => input_str($_POST, 'area', 80),
        'salary_min' => input_int($_POST, 'salary_min'),
        'salary_max' => input_int($_POST, 'salary_max'),
        'salary_period' => array_key_exists($_POST['salary_period'] ?? '', salary_periods()) ? $_POST['salary_period'] : 'month',
        'working_hours' => input_str($_POST, 'working_hours', 120),
        'shift' => array_key_exists($_POST['shift'] ?? '', shifts()) ? $_POST['shift'] : '',
        'vacancies' => input_int($_POST, 'vacancies', 1),
        'required_skills' => implode(', ', array_slice(skills_list(input_str($_POST, 'required_skills', 500)), 0, 15)),
        'preferred_skills' => implode(', ', array_slice(skills_list(input_str($_POST, 'preferred_skills', 500)), 0, 15)),
        'deadline' => input_str($_POST, 'deadline', 10),
        'acc_notes' => input_str($_POST, 'acc_notes', 1000),
    ];
    $acc = input_arr($_POST, 'acc', $accKeys);
    foreach ($accKeys as $k) $v[$k] = in_array($k, $acc, true) ? 1 : 0;

    // ---- Validation ----
    if (mb_strlen($v['title']) < 3) $errors['title'] = 'Enter a clear job title (at least 3 characters).';
    if (!in_array($v['category_id'], $catIds, true)) $errors['category_id'] = 'Choose a category.';
    if (mb_strlen($v['description']) < 30) $errors['description'] = 'Describe the role in at least 30 characters.';
    if (!array_key_exists($v['employment_type'], employment_types())) $errors['employment_type'] = 'Choose an employment type.';
    if (!array_key_exists($v['work_arrangement'], work_arrangements())) $errors['work_arrangement'] = 'Choose a work arrangement.';
    if (!array_key_exists($v['experience_level'], experience_levels())) $errors['experience_level'] = 'Choose an experience level.';
    if (!array_key_exists((int) $v['education_level'], education_levels())) $errors['education_level'] = 'Choose an education requirement.';
    if ($v['work_arrangement'] !== 'remote' && $v['district'] === '') $errors['district'] = 'Choose the district where the job is located (required for on-site and hybrid jobs).';
    if ($v['salary_min'] !== null && $v['salary_min'] < 0) $errors['salary_min'] = 'Salary cannot be negative.';
    if ($v['salary_max'] !== null && $v['salary_max'] < 0) $errors['salary_max'] = 'Salary cannot be negative.';
    if ($v['salary_min'] !== null && $v['salary_max'] !== null && $v['salary_min'] > $v['salary_max']) $errors['salary_max'] = 'Maximum must be greater than or equal to the minimum.';
    if ((isset($_POST['salary_min']) && $_POST['salary_min'] !== '' && $v['salary_min'] === null) || (isset($_POST['salary_max']) && $_POST['salary_max'] !== '' && $v['salary_max'] === null)) $errors['salary_min'] = 'Enter salary as a whole number in taka, without commas.';
    if ($v['vacancies'] === null || $v['vacancies'] < 1 || $v['vacancies'] > 999) $errors['vacancies'] = 'Enter a number between 1 and 999.';
    if ($v['deadline'] !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $v['deadline']);
        if (!$d || $d->format('Y-m-d') !== $v['deadline']) $errors['deadline'] = 'Enter a valid date.';
        elseif ($intent !== 'draft' && $v['deadline'] < date('Y-m-d') && (!$job || $job['status'] !== 'closed')) $errors['deadline'] = 'The deadline must be today or later.';
    }

    if (!$errors) {
        $status = $job['status'] ?? 'draft';
        if ($intent === 'publish') $status = 'published';
        if ($intent === 'draft') $status = 'draft';
        $data = $v;
        $data['division'] = $v['district'] ? division_of($v['district']) : null;
        foreach (['district', 'area', 'responsibilities', 'requirements', 'how_to_apply', 'working_hours', 'shift', 'required_skills', 'preferred_skills', 'deadline', 'acc_notes'] as $k) {
            if ($data[$k] === '') $data[$k] = null;
        }
        $data['status'] = $status;
        $cols = array_keys($data);
        if ($job) {
            $set = implode(', ', array_map(fn($c) => "$c = :$c", $cols));
            $params = array_combine(array_map(fn($c) => ":$c", $cols), array_values($data));
            $params[':id'] = $job['id'];
            $params[':cid'] = $cid;
            db()->prepare("UPDATE jobs SET $set WHERE id = :id AND company_id = :cid")->execute($params);
            $newId = (int) $job['id'];
        } else {
            $data['company_id'] = $cid;
            $cols = array_keys($data);
            $params = array_combine(array_map(fn($c) => ":$c", $cols), array_values($data));
            db()->prepare('INSERT INTO jobs (' . implode(', ', $cols) . ') VALUES (:' . implode(', :', $cols) . ')')->execute($params);
            $newId = (int) db()->lastInsertId();
        }
        flash('success', $status === 'published'
            ? ($job && $job['status'] === 'published' ? 'Changes saved. Your listing is live.' : 'Your job has been published and is now visible to job seekers.')
            : ($status === 'draft' ? 'Saved as a draft. Only you can see it until you publish.' : 'Changes saved.'));
        redirect($status === 'published' ? 'job.php?id=' . $newId : 'employer/jobs.php?tab=' . $status);
    }
}

$title = $job ? 'Edit job' : 'Post a job';
$dashActive = $job ? 'jobs' : 'post';
require __DIR__ . '/../includes/partials/header.php';
$sel = fn($a, $b) => (string) $a === (string) $b ? ' selected' : '';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1><?= $job ? 'Edit job listing' : 'Post a new job' ?></h1><p><?= $job ? 'Status: ' . badge(ucfirst($job['status']), status_tone($job['status'])) : 'Fields marked * are required. You can save a draft at any time.' ?></p></div>
    </div>
    <?php if ($errors): ?>
      <div class="alert alert-error mb-4" role="alert"><?= icon('alert', '', 18) ?><div><strong>Please fix <?= count($errors) ?> <?= count($errors) === 1 ? 'problem' : 'problems' ?>:</strong>
        <ul class="mb-0" style="padding-left:18px;margin-top:4px"><?php foreach ($errors as $k => $msg): ?><li><a href="#<?= e($k) ?>" style="color:inherit"><?= e($msg) ?></a></li><?php endforeach; ?></ul></div></div>
    <?php endif; ?>

    <form class="card card-pad-lg" method="post" action="" novalidate>
      <?= csrf_field() ?>
      <?php if ($job): ?><input type="hidden" name="id" value="<?= (int) $job['id'] ?>"><?php endif; ?>

      <fieldset class="fieldset">
        <legend>Job basics</legend>
        <div class="field">
          <label class="label" for="title">Job title <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= invalid($errors, 'title') ?>" id="title" name="title" maxlength="150" required value="<?= e($v['title']) ?>" placeholder="e.g. Evening Café Assistant">
          <?= field_error($errors, 'title') ?>
        </div>
        <div class="form-row">
          <div class="field">
            <label class="label" for="category_id">Category <span class="req" aria-hidden="true">*</span></label>
            <select class="select<?= invalid($errors, 'category_id') ?>" id="category_id" name="category_id" required>
              <option value="">Choose a category</option>
              <?php foreach (['local' => 'Local & service', 'it' => 'Tech & digital', 'general' => 'Office & business'] as $g => $gl): ?>
                <optgroup label="<?= e($gl) ?>"><?php foreach (categories($g) as $c): ?><option value="<?= (int) $c['id'] ?>"<?= $sel($v['category_id'], $c['id']) ?>><?= e($c['name']) ?></option><?php endforeach; ?></optgroup>
              <?php endforeach; ?>
            </select>
            <?= field_error($errors, 'category_id') ?>
          </div>
          <div class="field">
            <label class="label" for="vacancies">Number of vacancies <span class="req" aria-hidden="true">*</span></label>
            <input class="input<?= invalid($errors, 'vacancies') ?>" id="vacancies" name="vacancies" type="number" min="1" max="999" required value="<?= e($v['vacancies']) ?>">
            <?= field_error($errors, 'vacancies') ?>
          </div>
        </div>
        <div class="form-row-3">
          <div class="field">
            <label class="label" for="employment_type">Employment type <span class="req" aria-hidden="true">*</span></label>
            <select class="select<?= invalid($errors, 'employment_type') ?>" id="employment_type" name="employment_type"><?php foreach (employment_types() as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($v['employment_type'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select>
            <?= field_error($errors, 'employment_type') ?>
          </div>
          <div class="field">
            <label class="label" for="work_arrangement">Work arrangement <span class="req" aria-hidden="true">*</span></label>
            <select class="select<?= invalid($errors, 'work_arrangement') ?>" id="work_arrangement" name="work_arrangement" data-arrangement><?php foreach (work_arrangements() as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($v['work_arrangement'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select>
            <?= field_error($errors, 'work_arrangement') ?>
          </div>
          <div class="field">
            <label class="label" for="experience_level">Experience level <span class="req" aria-hidden="true">*</span></label>
            <select class="select<?= invalid($errors, 'experience_level') ?>" id="experience_level" name="experience_level"><?php foreach (experience_levels() as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($v['experience_level'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select>
          </div>
        </div>
        <div class="field">
          <label class="label" for="education_level">Minimum education <span class="req" aria-hidden="true">*</span></label>
          <select class="select" id="education_level" name="education_level"><?php foreach (education_levels() as $k => $l): ?><option value="<?= $k ?>"<?= $sel($v['education_level'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select>
        </div>
      </fieldset>

      <fieldset class="fieldset">
        <legend>Location</legend>
        <div class="form-row">
          <div class="field">
            <label class="label" for="district">District <span class="req" aria-hidden="true" data-district-req>*</span></label>
            <?= district_select('district', (string) $v['district'], ['placeholder' => 'Select district', 'class' => !empty($errors['district']) ? 'is-invalid' : '']) ?>
            <?= field_error($errors, 'district') ?>
            <p class="help">Optional for fully remote jobs.</p>
          </div>
          <div class="field">
            <label class="label" for="area">Area / upazila</label>
            <input class="input" id="area" name="area" maxlength="80" value="<?= e($v['area']) ?>" placeholder="e.g. Dhanmondi, Agrabad">
          </div>
        </div>
      </fieldset>

      <fieldset class="fieldset">
        <legend>Pay &amp; hours</legend>
        <div class="form-row-3">
          <div class="field"><label class="label" for="salary_min">Pay from (Tk)</label><input class="input<?= invalid($errors, 'salary_min') ?>" id="salary_min" name="salary_min" type="number" min="0" inputmode="numeric" value="<?= e($v['salary_min']) ?>"><?= field_error($errors, 'salary_min') ?></div>
          <div class="field"><label class="label" for="salary_max">Pay to (Tk)</label><input class="input<?= invalid($errors, 'salary_max') ?>" id="salary_max" name="salary_max" type="number" min="0" inputmode="numeric" value="<?= e($v['salary_max']) ?>"><?= field_error($errors, 'salary_max') ?></div>
          <div class="field"><label class="label" for="salary_period">Per</label><select class="select" id="salary_period" name="salary_period"><?php foreach (salary_periods() as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($v['salary_period'], $k) ?>><?= e(ucfirst($l)) ?></option><?php endforeach; ?></select></div>
        </div>
        <p class="help" style="margin-top:-8px;margin-bottom:16px">Leave both empty if you prefer not to disclose pay. Listings with pay information usually receive more applications.</p>
        <div class="form-row">
          <div class="field"><label class="label" for="working_hours">Working hours</label><input class="input" id="working_hours" name="working_hours" maxlength="120" value="<?= e($v['working_hours']) ?>" placeholder="e.g. 4 PM – 9 PM, 6 days a week"></div>
          <div class="field"><label class="label" for="shift">Shift</label><select class="select" id="shift" name="shift"><option value="">Not specified</option><?php foreach (shifts() as $k => $l): ?><option value="<?= e($k) ?>"<?= $sel($v['shift'], $k) ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
        </div>
      </fieldset>

      <fieldset class="fieldset">
        <legend>Description</legend>
        <div class="field">
          <label class="label" for="description">About the role <span class="req" aria-hidden="true">*</span></label>
          <textarea class="textarea<?= invalid($errors, 'description') ?>" id="description" name="description" maxlength="5000" required><?= e($v['description']) ?></textarea>
          <?= field_error($errors, 'description') ?>
        </div>
        <div class="form-row">
          <div class="field"><label class="label" for="responsibilities">Responsibilities <span class="opt">(one per line)</span></label><textarea class="textarea" id="responsibilities" name="responsibilities" maxlength="3000"><?= e($v['responsibilities']) ?></textarea></div>
          <div class="field"><label class="label" for="requirements">Requirements / qualifications <span class="opt">(one per line)</span></label><textarea class="textarea" id="requirements" name="requirements" maxlength="3000"><?= e($v['requirements']) ?></textarea></div>
        </div>
        <div class="form-row">
          <div class="field"><label class="label" for="required_skills">Required skills</label><input class="input" id="required_skills" name="required_skills" value="<?= e($v['required_skills']) ?>" placeholder="Comma separated, e.g. Customer service, MS Excel"></div>
          <div class="field"><label class="label" for="preferred_skills">Preferred skills <span class="opt">(nice to have)</span></label><input class="input" id="preferred_skills" name="preferred_skills" value="<?= e($v['preferred_skills']) ?>" placeholder="Comma separated"></div>
        </div>
        <div class="form-row">
          <div class="field"><label class="label" for="how_to_apply">Application instructions</label><textarea class="textarea" id="how_to_apply" name="how_to_apply" maxlength="1000" style="min-height:80px"><?= e($v['how_to_apply']) ?></textarea></div>
          <div class="field"><label class="label" for="deadline">Application deadline</label><input class="input<?= invalid($errors, 'deadline') ?>" id="deadline" name="deadline" type="date" min="<?= date('Y-m-d') ?>" value="<?= e($v['deadline']) ?>"><?= field_error($errors, 'deadline') ?><p class="help">Leave empty for “open until filled”.</p></div>
        </div>
      </fieldset>

      <fieldset class="fieldset">
        <legend>Workplace accessibility &amp; flexibility <span class="opt" style="font-weight:400">(optional)</span></legend>
        <p class="small muted">Tick only the facilities and arrangements you <strong>actually provide</strong>. They are shown to job seekers exactly as you confirm them here, and help people find suitable jobs on the Inclusive Jobs page.</p>
        <div class="check-grid">
          <?php foreach (accessibility_features() as $k => $a): ?>
            <label class="check"><input type="checkbox" name="acc[]" value="<?= e($k) ?>"<?= !empty($v[$k]) ? ' checked' : '' ?>> <?= e($a['label']) ?></label>
          <?php endforeach; ?>
        </div>
        <div class="field mt-3">
          <label class="label" for="acc_notes">Accessibility details <span class="opt">(optional)</span></label>
          <textarea class="textarea" id="acc_notes" name="acc_notes" maxlength="1000" style="min-height:80px" placeholder="e.g. Office on 3rd floor with lift. Interview questions can be shared in advance."><?= e($v['acc_notes']) ?></textarea>
        </div>
      </fieldset>

      <div class="form-actions">
        <?php if (!$job || $job['status'] === 'draft'): ?>
          <button class="btn btn-primary btn-lg" type="submit" name="intent" value="publish"><?= icon('send', '', 18) ?> Publish job</button>
          <button class="btn btn-outline btn-lg" type="submit" name="intent" value="draft">Save as draft</button>
        <?php else: ?>
          <button class="btn btn-primary btn-lg" type="submit" name="intent" value="save"><?= icon('save', '', 18) ?> Save changes</button>
          <?php if ($job['status'] === 'closed'): ?><button class="btn btn-outline btn-lg" type="submit" name="intent" value="publish">Save &amp; reopen</button><?php endif; ?>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('employer/jobs.php')) ?>">Cancel</a>
      </div>
    </form>
  </div>
</div>
<script>
(function(){ var a = document.querySelector('[data-arrangement]'), r = document.querySelector('[data-district-req]');
  function upd(){ r.style.visibility = a.value === 'remote' ? 'hidden' : 'visible'; } a.addEventListener('change', upd); upd(); })();
</script>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
