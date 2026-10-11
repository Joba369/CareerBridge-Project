<?php
require __DIR__ . '/includes/bootstrap.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$reset = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()');
    $st->execute([hash('sha256', $token)]);
    $reset = $st->fetch() ?: null;
}
$errors = [];
if ($reset && is_post()) {
    verify_csrf();
    $p = (string) ($_POST['password'] ?? '');
    if (strlen($p) < 8 || !preg_match('/[A-Za-z]/', $p) || !preg_match('/\d/', $p)) $errors['password'] = 'Use at least 8 characters with at least one letter and one number.';
    if ($p !== (string) ($_POST['password_confirm'] ?? '')) $errors['password_confirm'] = 'The passwords do not match.';
    if (!$errors) {
        db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($p, PASSWORD_DEFAULT), $reset['user_id']]);
        db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$reset['id']]);
        flash('success', 'Your password has been updated. Please sign in with your new password.');
        redirect('login.php');
    }
}
$title = 'Choose a new password';
require __DIR__ . '/includes/partials/header.php';
?>
<div class="auth-wrap">
  <?php $sideTitle = 'Choose a new password'; $sidePoints = ['Use at least 8 characters', 'Mix letters and numbers', 'Avoid passwords you use elsewhere']; require __DIR__ . '/includes/partials/auth-side.php'; ?>
  <div class="auth-main">
    <div class="auth-card">
      <h1>Set a new password</h1>
      <?php if (!$reset): ?>
        <div class="alert alert-error" role="alert"><?= icon('alert', '', 18) ?><div>This reset link is invalid, has expired or has already been used.</div></div>
        <p class="mt-4"><a class="btn btn-primary" href="<?= e(url('forgot-password.php')) ?>">Request a new link</a></p>
      <?php else: ?>
        <form method="post" action="" novalidate data-validate>
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">
          <div class="field">
            <label class="label" for="password">New password</label>
            <input class="input<?= invalid($errors, 'password') ?>" type="password" id="password" name="password" required minlength="8" autocomplete="new-password">
            <?= field_error($errors, 'password') ?>
          </div>
          <div class="field">
            <label class="label" for="password_confirm">Confirm new password</label>
            <input class="input<?= invalid($errors, 'password_confirm') ?>" type="password" id="password_confirm" name="password_confirm" required autocomplete="new-password">
            <?= field_error($errors, 'password_confirm') ?>
          </div>
          <button class="btn btn-primary btn-block btn-lg" type="submit">Update password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
