<?php
require __DIR__ . '/includes/bootstrap.php';

$sent = false;
$devLink = null;
$error = '';
$email = '';
if (is_post()) {
    verify_csrf();
    $email = mb_strtolower(input_str($_POST, 'email', 190));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($u = $st->fetch()) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')->execute([$u['id']]);
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 1 HOUR)')
                ->execute([$u['id'], hash('sha256', $token)]);
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
            $link = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('reset-password.php?token=' . $token);
            // In production this link would be e-mailed. XAMPP has no mail server by default,
            // so in development mode we show it on screen instead.
            if (APP_DEV_MODE) $devLink = $link;
        }
        $sent = true; // Same message whether or not the account exists (prevents account discovery).
    }
}

$title = 'Reset your password';
require __DIR__ . '/includes/partials/header.php';
?>
<div class="auth-wrap">
  <?php $sideTitle = 'Forgot your password?'; $sidePoints = ['Reset links expire after 1 hour', 'Each link can be used only once', 'Your saved jobs and applications stay safe']; require __DIR__ . '/includes/partials/auth-side.php'; ?>
  <div class="auth-main">
    <div class="auth-card">
      <h1>Reset your password</h1>
      <?php if ($sent): ?>
        <div class="alert alert-success" role="status"><?= icon('check-circle', '', 18) ?><div>If an account exists for <strong><?= e($email) ?></strong>, a password reset link has been created.</div></div>
        <?php if ($devLink): ?>
          <div class="alert alert-warning mt-3"><?= icon('info', '', 18) ?><div><strong>Development mode:</strong> e-mail sending is not configured on this local server, so the reset link is shown here instead.<br><a href="<?= e($devLink) ?>" style="word-break:break-all">Open the reset link</a></div></div>
        <?php elseif (!APP_DEV_MODE): ?>
          <p class="muted mt-3">Note: e-mail delivery must be configured on the server for reset links to arrive.</p>
        <?php endif; ?>
        <p class="mt-4"><a href="<?= e(url('login.php')) ?>">Back to sign in</a></p>
      <?php else: ?>
        <p class="muted">Enter the email address you used to register and we'll create a link to set a new password.</p>
        <?php if ($error): ?><div class="alert alert-error mb-3" role="alert"><?= icon('alert', '', 18) ?><div><?= e($error) ?></div></div><?php endif; ?>
        <form method="post" action="" novalidate data-validate>
          <?= csrf_field() ?>
          <div class="field">
            <label class="label" for="email">Email address</label>
            <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="email">
          </div>
          <button class="btn btn-primary btn-block btn-lg" type="submit">Create reset link</button>
        </form>
        <p class="center mt-4"><a href="<?= e(url('login.php')) ?>">Back to sign in</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
