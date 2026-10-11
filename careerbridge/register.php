<?php
require __DIR__ . '/includes/bootstrap.php';

if (current_user()) redirect(is_role('employer') ? 'employer/index.php' : 'account/index.php');

$role = ($_POST['role'] ?? $_GET['role'] ?? 'seeker') === 'employer' ? 'employer' : 'seeker';
$errors = [];
$v = ['name' => '', 'email' => '', 'company' => '', 'district' => ''];

if (is_post()) {
    verify_csrf();
    $v['name']     = input_str($_POST, 'name', 120);
    $v['email']    = mb_strtolower(input_str($_POST, 'email', 190));
    $v['company']  = input_str($_POST, 'company', 150);
    $v['district'] = in_array($_POST['district'] ?? '', all_districts(), true) ? $_POST['district'] : '';
    $password = (string) ($_POST['password'] ?? '');
    $confirm  = (string) ($_POST['password_confirm'] ?? '');

    if (mb_strlen($v['name']) < 2) $errors['name'] = 'Please enter your full name.';
    if (!filter_var($v['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Use at least 8 characters with at least one letter and one number.';
    }
    if ($password !== $confirm) $errors['password_confirm'] = 'The passwords do not match.';
    if ($role === 'employer' && mb_strlen($v['company']) < 2) $errors['company'] = 'Please enter your company or business name.';
    if (empty($_POST['terms'])) $errors['terms'] = 'Please confirm that the information you provide is accurate.';

    if (!$errors) {
        $st = db()->prepare('SELECT 1 FROM users WHERE email = ?');
        $st->execute([$v['email']]);
        if ($st->fetch()) $errors['email'] = 'An account with this email already exists. Try signing in instead.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO users (role, name, email, password_hash) VALUES (?, ?, ?, ?)')
                ->execute([$role, $v['name'], $v['email'], password_hash($password, PASSWORD_DEFAULT)]);
            $uid = (int) $pdo->lastInsertId();
            if ($role === 'employer') {
                $pdo->prepare('INSERT INTO companies (user_id, name, division, district) VALUES (?, ?, ?, ?)')
                    ->execute([$uid, $v['company'], $v['district'] ? division_of($v['district']) : null, $v['district'] ?: null]);
            } else {
                $pdo->prepare('INSERT INTO seeker_profiles (user_id, division, district) VALUES (?, ?, ?)')
                    ->execute([$uid, $v['district'] ? division_of($v['district']) : null, $v['district'] ?: null]);
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            throw $ex;
        }
        login_user(['id' => $uid]);
        if ($role === 'employer') {
            flash('success', 'Your employer account is ready. Complete your company profile, then post your first job.');
            redirect('employer/company.php');
        }
        flash('success', 'Welcome to CareerBridge! Complete your profile to get better job matches.');
        redirect('account/profile.php');
    }
}

$title = 'Create an account';
require __DIR__ . '/includes/partials/header.php';
?>
<div class="auth-wrap">
  <?php
  $sideTitle = 'Join CareerBridge for free';
  require __DIR__ . '/includes/partials/auth-side.php'; ?>
  <div class="auth-main">
    <div class="auth-card">
      <h1>Create your account</h1>
      <p class="muted">It's free and takes less than a minute.</p>
      <?php if ($errors): ?><div class="alert alert-error mb-3" role="alert"><?= icon('alert', '', 18) ?><div>Please fix the highlighted fields below.</div></div><?php endif; ?>
      <form method="post" action="" novalidate data-validate>
        <?= csrf_field() ?>
        <fieldset style="border:0;padding:0;margin:0">
          <legend class="label">I want to</legend>
          <div class="role-switch" data-role-switch>
            <label><input type="radio" name="role" value="seeker"<?= $role === 'seeker' ? ' checked' : '' ?>> <?= icon('search', '', 18) ?> Find a job</label>
            <label><input type="radio" name="role" value="employer"<?= $role === 'employer' ? ' checked' : '' ?>> <?= icon('building', '', 18) ?> Hire people</label>
          </div>
        </fieldset>

        <div class="field">
          <label class="label" for="name">Full name <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= invalid($errors, 'name') ?>" id="name" name="name" value="<?= e($v['name']) ?>" autocomplete="name" required minlength="2">
          <?= field_error($errors, 'name') ?>
        </div>
        <div class="field" data-employer-only<?= $role === 'employer' ? '' : ' hidden' ?>>
          <label class="label" for="company">Company / business name <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= invalid($errors, 'company') ?>" id="company" name="company" value="<?= e($v['company']) ?>" autocomplete="organization">
          <?= field_error($errors, 'company') ?>
        </div>
        <div class="field">
          <label class="label" for="email">Email address <span class="req" aria-hidden="true">*</span></label>
          <input class="input<?= invalid($errors, 'email') ?>" type="email" id="email" name="email" value="<?= e($v['email']) ?>" autocomplete="email" required>
          <?= field_error($errors, 'email') ?>
        </div>
        <div class="field">
          <label class="label" for="district">District <span class="opt">(optional)</span></label>
          <?= district_select('district', $v['district'], ['placeholder' => 'Select your district']) ?>
        </div>
        <div class="form-row">
          <div class="field">
            <label class="label" for="password">Password <span class="req" aria-hidden="true">*</span></label>
            <input class="input<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" autocomplete="new-password" required minlength="8">
            <?= $errors['password'] ?? false ? field_error($errors, 'password') : '<p class="help">8+ characters, a letter and a number.</p>' ?>
          </div>
          <div class="field">
            <label class="label" for="password_confirm">Confirm password <span class="req" aria-hidden="true">*</span></label>
            <input class="input<?= invalid($errors, 'password_confirm') ?>" type="password" id="password_confirm" name="password_confirm" autocomplete="new-password" required>
            <?= field_error($errors, 'password_confirm') ?>
          </div>
        </div>
        <div class="field">
          <label class="check"><input type="checkbox" name="terms" value="1" required> I confirm the information I provide is accurate, and I understand this is a university demo project.</label>
          <?= field_error($errors, 'terms') ?>
        </div>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Create account</button>
      </form>
      <p class="center mt-4">Already have an account? <a href="<?= e(url('login.php')) ?>">Sign in</a></p>
    </div>
  </div>
</div>
<script>
document.querySelectorAll('[data-role-switch] input').forEach(function (r) {
  r.addEventListener('change', function () {
    var emp = document.querySelector('[data-role-switch] input:checked').value === 'employer';
    var box = document.querySelector('[data-employer-only]');
    box.hidden = !emp; document.getElementById('company').required = emp;
  });
});
</script>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
