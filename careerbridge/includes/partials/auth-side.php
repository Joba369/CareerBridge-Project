<aside class="auth-side" aria-hidden="true">
  <div style="position:relative;z-index:1">
    <a class="brand" href="<?= e(url('index.php')) ?>" style="color:#fff" tabindex="-1"><?= logo_mark() ?><span>Career<b>Bridge</b></span></a>
    <h2 class="mt-4"><?= e($sideTitle ?? 'Your career journey starts here') ?></h2>
    <ul>
      <?php foreach (($sidePoints ?? [
          'Save jobs and apply with your CareerBridge CV',
          'See transparent, rule-based job matches',
          'Track applications, interviews and private notes',
          'Employers: post jobs and manage applicants',
      ]) as $pt): ?>
        <li><?= icon('check-circle', '', 20) ?><span><?= e($pt) ?></span></li>
      <?php endforeach; ?>
    </ul>
  </div>
</aside>
