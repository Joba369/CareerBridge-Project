<?php
require __DIR__ . '/../includes/bootstrap.php';
$user = require_login();
$uid = (int) $user['id'];
$errors = [];
$section = '';

if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $st = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $st->execute([$uid]);
    $hash = (string) $st->fetchColumn();

    if ($action === 'account') {
        $section = 'account';
        $name = input_str($_POST, 'name', 120);
        $email = mb_strtolower(input_str($_POST, 'email', 190));
        if (mb_strlen($name) < 2) $errors['name'] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Please enter a valid email address.';
        else {
            $chk = db()->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
            $chk->execute([$email, $uid]);
            if ($chk->fetch()) $errors['email'] = 'Another account already uses this email.';
        }
        if (!$errors) {
            db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')->execute([$name, $email, $uid]);
            flash('success', 'Account details updated.');
            redirect('account/settings.php');
        }
    } elseif ($action === 'password') {
        $section = 'password';
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $hash)) $errors['current_password'] = 'Your current password is incorrect.';
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) $errors['new_password'] = 'Use at least 8 characters with a letter and a number.';
        if ($new !== (string) ($_POST['confirm_password'] ?? '')) $errors['confirm_password'] = 'The passwords do not match.';
        if (!$errors) {
            db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            session_regenerate_id(true);
            flash('success', 'Password changed.');
            redirect('account/settings.php');
        }
    } elseif ($action === 'delete') {
        $section = 'delete';
        if (!password_verify((string) ($_POST['delete_password'] ?? ''), $hash)) {
            $errors['delete_password'] = 'Password is incorrect.';
        } else {
            // Remove uploaded CV files belonging to this user before the rows cascade-delete.
            $files = db()->prepare('SELECT cv_file FROM applications WHERE user_id = ? AND cv_file IS NOT NULL');
            $files->execute([$uid]);
            $list = $files->fetchAll(PDO::FETCH_COLUMN);
            if ($user['role'] === 'employer') {
                $ef = db()->prepare('SELECT a.cv_file FROM applications a JOIN jobs j ON j.id = a.job_id JOIN companies c ON c.id = j.company_id WHERE c.user_id = ? AND a.cv_file IS NOT NULL');
                $ef->execute([$uid]);
                $list = array_merge($list, $ef->fetchAll(PDO::FETCH_COLUMN));
            }
            foreach ($list as $f) @unlink(__DIR__ . '/../uploads/cvs/' . basename($f));
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $_SESSION = [];
            session_regenerate_id(true);
            flash('success', 'Your account and its data have been deleted.');
            redirect('index.php');
        }
    }
}

$title = 'Account settings';
$dashActive = 'settings';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div class="stack">
    <div class="dash-title"><div><h1>Account settings</h1><p>Manage your sign-in details and account.</p></div></div>

    <form class="card card-pad-lg" method="post" action="" novalidate data-validate>
      <h2 style="font-size:1.1rem">Account details</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="account">
      <div class="form-row">
        <div class="field"><label class="label" for="name">Name</label><input class="input<?= invalid($errors, 'name') ?>" id="name" name="name" value="<?= e($section === 'account' ? ($_POST['name'] ?? '') : $user['name']) ?>" required><?= field_error($errors, 'name') ?></div>
        <div class="field"><label class="label" for="email">Email</label><input class="input<?= invalid($errors, 'email') ?>" id="email" name="email" type="email" value="<?= e($section === 'account' ? ($_POST['email'] ?? '') : $user['email']) ?>" required><?= field_error($errors, 'email') ?></div>
      </div>
      <button class="btn btn-primary" type="submit">Save details</button>
    </form>

    <form class="card card-pad-lg" method="post" action="" novalidate data-validate>
      <h2 style="font-size:1.1rem">Change password</h2>
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <div class="field"><label class="label" for="current_password">Current password</label><input class="input<?= invalid($errors, 'current_password') ?>" id="current_password" name="current_password" type="password" autocomplete="current-password" required><?= field_error($errors, 'current_password') ?></div>
      <div class="form-row">
        <div class="field"><label class="label" for="new_password">New password</label><input class="input<?= invalid($errors, 'new_password') ?>" id="new_password" name="new_password" type="password" autocomplete="new-password" required minlength="8"><?= field_error($errors, 'new_password') ?></div>
        <div class="field"><label class="label" for="confirm_password">Confirm new password</label><input class="input<?= invalid($errors, 'confirm_password') ?>" id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required><?= field_error($errors, 'confirm_password') ?></div>
      </div>
      <button class="btn btn-primary" type="submit">Change password</button>
    </form>

    <form class="card card-pad-lg" method="post" action="" data-confirm="Permanently delete your account and all of its data? This cannot be undone." style="border-color:#f1c7c2">
      <h2 style="font-size:1.1rem;color:var(--red)">Delete account</h2>
      <p class="small muted">This permanently deletes your profile, CV, saved jobs, applications and tracker notes<?= $user['role'] === 'employer' ? ', plus your company profile, job listings and the applications they received' : '' ?>.</p>
      <?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <div class="field" style="max-width:360px"><label class="label" for="delete_password">Confirm with your password</label><input class="input<?= invalid($errors, 'delete_password') ?>" id="delete_password" name="delete_password" type="password" required><?= field_error($errors, 'delete_password') ?></div>
      <button class="btn btn-danger" type="submit"><?= icon('trash', '', 16) ?> Delete my account</button>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
