<?php
require __DIR__ . '/../includes/bootstrap.php';
[$user, $company] = employer_context();
$errors = [];
$v = $company;

if (is_post()) {
    verify_csrf();
    $v = [
        'name' => input_str($_POST, 'name', 150),
        'industry' => input_str($_POST, 'industry', 100),
        'size' => in_array($_POST['size'] ?? '', ['1–10 employees', '11–50 employees', '51–200 employees', '201–500 employees', '500+ employees'], true) ? $_POST['size'] : '',
        'district' => in_array($_POST['district'] ?? '', all_districts(), true) ? $_POST['district'] : '',
        'address' => input_str($_POST, 'address', 255),
        'website' => input_str($_POST, 'website', 255),
        'phone' => input_str($_POST, 'phone', 30),
        'description' => input_str($_POST, 'description', 3000),
    ] + $company;
    if (mb_strlen($v['name']) < 2) $errors['name'] = 'Enter your company or business name.';
    if ($v['website'] !== '' && !filter_var($v['website'], FILTER_VALIDATE_URL)) $errors['website'] = 'Enter a full web address starting with http:// or https://';
    if ($v['phone'] !== '' && !preg_match('/^[0-9+\-\s()]{7,20}$/', $v['phone'])) $errors['phone'] = 'Enter a valid phone number.';
    if (!$errors) {
        db()->prepare('UPDATE companies SET name=?, industry=?, size=?, division=?, district=?, address=?, website=?, phone=?, description=? WHERE id = ? AND user_id = ?')
            ->execute([$v['name'], $v['industry'] ?: null, $v['size'] ?: null, $v['district'] ? division_of($v['district']) : null, $v['district'] ?: null,
                $v['address'] ?: null, $v['website'] ?: null, $v['phone'] ?: null, $v['description'] ?: null, $company['id'], $user['id']]);
        flash('success', 'Company profile saved.');
        redirect('employer/company.php');
    }
}

$title = 'Company profile';
$dashActive = 'company';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1>Company profile</h1><p>Shown on your public company page and on every job you post.</p></div>
      <a class="btn btn-outline" href="<?= e(url('company.php?id=' . $company['id'])) ?>"><?= icon('eye', '', 18) ?> View public page</a>
    </div>
    <?php if ($errors): ?><div class="alert alert-error mb-4" role="alert"><?= icon('alert', '', 18) ?><div>Please fix the highlighted fields.</div></div><?php endif; ?>
    <form class="card card-pad-lg" method="post" action="" novalidate data-validate>
      <?= csrf_field() ?>
      <div class="flex items-center gap-3 mb-4">
        <span class="logo-tile lg <?= logo_tone((int) $company['id']) ?>" aria-hidden="true"><?= e(initials($v['name'])) ?></span>
        <p class="small muted mb-0">CareerBridge shows your initials as a simple logo tile — no image upload needed.</p>
      </div>
      <div class="form-row">
        <div class="field"><label class="label" for="name">Company / business name <span class="req" aria-hidden="true">*</span></label><input class="input<?= invalid($errors, 'name') ?>" id="name" name="name" required value="<?= e($v['name']) ?>"><?= field_error($errors, 'name') ?></div>
        <div class="field"><label class="label" for="industry">Industry</label><input class="input" id="industry" name="industry" value="<?= e($v['industry']) ?>" placeholder="e.g. Restaurant & Café, Software"></div>
      </div>
      <div class="form-row">
        <div class="field"><label class="label" for="size">Company size</label>
          <select class="select" id="size" name="size"><option value="">Select size</option><?php foreach (['1–10 employees', '11–50 employees', '51–200 employees', '201–500 employees', '500+ employees'] as $sz): ?><option<?= $v['size'] === $sz ? ' selected' : '' ?>><?= e($sz) ?></option><?php endforeach; ?></select></div>
        <div class="field"><label class="label" for="district">District</label><?= district_select('district', (string) $v['district'], ['placeholder' => 'Select district']) ?></div>
      </div>
      <div class="field"><label class="label" for="address">Address</label><input class="input" id="address" name="address" value="<?= e($v['address']) ?>" placeholder="Road, area, city"></div>
      <div class="form-row">
        <div class="field"><label class="label" for="website">Website <span class="opt">(optional)</span></label><input class="input<?= invalid($errors, 'website') ?>" id="website" name="website" type="url" value="<?= e($v['website']) ?>" placeholder="https://"><?= field_error($errors, 'website') ?></div>
        <div class="field"><label class="label" for="phone">Contact phone <span class="opt">(optional)</span></label><input class="input<?= invalid($errors, 'phone') ?>" id="phone" name="phone" value="<?= e($v['phone']) ?>"><?= field_error($errors, 'phone') ?></div>
      </div>
      <div class="field"><label class="label" for="description">About the company</label><textarea class="textarea" id="description" name="description" maxlength="3000" style="min-height:140px"><?= e($v['description']) ?></textarea></div>
      <button class="btn btn-primary" type="submit"><?= icon('save', '', 18) ?> Save profile</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
