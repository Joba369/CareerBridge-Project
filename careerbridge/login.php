<?php
require __DIR__ . '/includes/bootstrap.php';

$next = safe_return($_GET['next'] ?? ($_POST['next'] ?? null), '');
if (current_user()) redirect(is_role('employer') ? 'employer/index.php' : 'account/index.php');

$errors = [];
$email = '';
if (is_post()) {
    verify_csrf();
    $email = mb_strtolower(input_str($_POST, 'email', 190));
    $password = (string) ($_POST['password'] ?? '');
    $lock = $_SESSION['login_lock_until'] ?? 0;
    if ($lock > time()) {
        $errors['form'] = 'Too many attempts. Please wait ' . ($lock - time()) . ' seconds and try again.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $errors['form'] = 'Enter your email address and password.';
    } else {
        $st = db()->prepare('SELECT * FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();
        if ($u && password_verify($password, $u['password_hash'])) {
            unset($_SESSION['login_fails'], $_SESSION['login_lock_until']);
            if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($password, PASSWORD_DEFAULT), $u['id']]);
            }
            login_user($u);
            flash('success', 'Welcome back, ' . $u['name'] . '!');
            $dest = $u['role'] === 'employer' ? url('employer/index.php') : url('account/index.php');
            if ($next && $next !== url('')) {
                // Don't send employers into seeker-only areas and vice versa.
                $isEmployerPath = str_contains($next, '/employer/');
                $isSeekerPath = str_contains($next, '/account/') || str_contains($next, '/apply.php');
                if (!($u['role'] === 'employer' && $isSeekerPath) && !($u['role'] === 'seeker' && $isEmployerPath)) $dest = $next;
            }
            redirect($dest);
        }
        $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
        if ($_SESSION['login_fails'] >= 5) { $_SESSION['login_lock_until'] = time() + 60; $_SESSION['login_fails'] = 0; }
        $errors['form'] = 'The email or password is incorrect.';
    }
}

$title = 'Sign in';
$noFlashContainer = true;
$flashList = take_flashes();
require __DIR__ . '/includes/partials/header.php';
?>
<div class="auth-wrap">
  <?php require __DIR__ . '/includes/partials/auth-side.php'; ?>
  <div class="auth-main">
    <div class="auth-card">
      <h1>Sign in</h1>
      <p class="muted">Job seekers and employers use the same sign-in page.</p>
      <?php foreach ($flashList as $fl): ?><div class="alert alert-<?= e($fl['type']) ?> mb-3"><?= icon('info', '', 18) ?><div><?= e($fl['message']) ?></div></div><?php endforeach; ?>
      <?php if (!empty($errors['form'])): ?><div class="alert alert-error mb-3" role="alert"><?= icon('alert', '', 18) ?><div><?= e($errors['form']) ?></div></div><?php endif; ?>
      <form method="post" action="" novalidate data-validate>
        <?= csrf_field() ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field">
          <label class="label" for="email">Email address</label>
          <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>" autocomplete="email" required autofocus>
        </div>
        <div class="field">
          <div class="flex justify-between items-center"><label class="label" for="password">Password</label><a class="small" href="<?= e(url('forgot-password.php')) ?>">Forgot password?</a></div>
          <input class="input" type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
      </form>
      <p class="center mt-4">New to CareerBridge? <a href="<?= e(url('register.php')) ?>">Create a free account</a></p>
      <div class="demo-creds">
        <strong>Demo accounts</strong> (password <code>Demo@1234</code>)<br>
        Job seeker: <code>seeker@demo.test</code><br>
        Employer: <code>employer@demo.test</code>
      </div>
    </div>
  </div>
</div>
<?php require __DIR__ . '/includes/partials/footer.php'; ?>
