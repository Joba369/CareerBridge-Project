<?php
require __DIR__ . '/../includes/bootstrap.php';
require __DIR__ . '/../includes/profile.php';
$user = require_login('seeker');
$uid = (int) $user['id'];

$p = seeker_profile($uid);
$errors = [];
$accKeys = array_keys(accessibility_features());
$accPrefs = skills_list($p['accessibility_prefs'] ?? '');

if (is_post()) {
    verify_csrf();
    $in = read_profile_input($_POST);
    $errors = validate_profile($in);
    $bio = input_str($_POST, 'bio', 1000);
    $accPrefs = input_arr($_POST, 'acc', $accKeys);
    if (!$errors) {
        save_profile($uid, $in, ['bio' => $bio, 'accessibility_prefs' => implode(',', $accPrefs)]);
        flash('success', 'Your profile has been saved. Recommendations are now updated.');
        redirect('account/profile.php');
    }
    $p = array_merge($p, $in, ['bio' => $bio]);
}

$title = 'My profile';
$dashActive = 'profile';
require __DIR__ . '/../includes/partials/header.php';
?>
<div class="container dash">
  <?php require __DIR__ . '/../includes/partials/dash-nav.php'; ?>
  <div>
    <div class="dash-title">
      <div><h1>My profile</h1><p>Used to pre-fill applications and to calculate your job matches.</p></div>
      <span class="small muted">Profile <?= profile_completeness($p) ?>% complete</span>
    </div>
    <?php if ($errors): ?><div class="alert alert-error mb-4" role="alert"><?= icon('alert', '', 18) ?><div>Please fix the highlighted fields.</div></div><?php endif; ?>

    <form class="card card-pad-lg" method="post" action="" novalidate data-validate>
      <?= csrf_field() ?>
      <fieldset class="fieldset">
        <legend>Basic information</legend>
        <div class="form-row">
          <div class="field">
            <label class="label" for="headline">Headline</label>
            <input class="input" id="headline" name="headline" maxlength="150" value="<?= e($p['headline'] ?? '') ?>" placeholder="e.g. HSC graduate looking for evening work">
          </div>
          <div class="field">
            <label class="label" for="phone">Phone</label>
            <input class="input<?= invalid($errors, 'phone') ?>" id="phone" name="phone" value="<?= e($p['phone'] ?? '') ?>" inputmode="tel" placeholder="01712-345678">
            <?= field_error($errors, 'phone') ?>
          </div>
        </div>
        <div class="form-row">
          <div class="field">
            <label class="label" for="education_detail">Education details</label>
            <input class="input" id="education_detail" name="education_detail" maxlength="200" value="<?= e($p['education_detail'] ?? '') ?>" placeholder="e.g. BBA (ongoing), Dhaka College">
          </div>
          <div class="field">
            <label class="label" for="area">Area <span class="opt">(optional)</span></label>
            <input class="input" id="area" name="area" maxlength="80" value="<?= e($p['area'] ?? '') ?>" placeholder="e.g. Mirpur">
          </div>
        </div>
        <div class="field">
          <label class="label" for="bio">About me <span class="opt">(optional)</span></label>
          <textarea class="textarea" id="bio" name="bio" maxlength="1000"><?= e($p['bio'] ?? '') ?></textarea>
        </div>
      </fieldset>

      <fieldset class="fieldset">
        <legend>Skills &amp; preferences (used for matching)</legend>
        <?= profile_match_fields($p, $errors) ?>
      </fieldset>

      <fieldset class="fieldset">
        <legend>Accessibility preferences <span class="opt" style="font-weight:400">(optional &amp; private)</span></legend>
        <p class="small muted">Choose any workplace features you would like to filter for. This is completely voluntary. It is only used to pre-fill filters on the <a href="<?= e(url('inclusive.php')) ?>">Inclusive Jobs</a> page — it is <strong>never</strong> shown to employers and <strong>never</strong> used in job matching scores. We don't ask for any medical information.</p>
        <div class="check-grid">
          <?php foreach (accessibility_features() as $k => $a): ?>
            <label class="check"><input type="checkbox" name="acc[]" value="<?= e($k) ?>"<?= in_array($k, $accPrefs, true) ? ' checked' : '' ?>> <?= e($a['label']) ?></label>
          <?php endforeach; ?>
        </div>
      </fieldset>

      <div class="form-actions">
        <button class="btn btn-primary" type="submit"><?= icon('save', '', 18) ?> Save profile</button>
        <a class="btn btn-ghost" href="<?= e(url('matching.php')) ?>">View my matches</a>
      </div>
    </form>
  </div>
</div>
<?php require __DIR__ . '/../includes/partials/footer.php'; ?>
